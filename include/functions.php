<?php
require_once(__DIR__ . '/../include/dbconnect.php');

function getUserAccess($userId) {
    global $mysqli, $YOUTUBE_API_JSON;

    $stmt = $mysqli->prepare('SELECT * FROM users WHERE expire_time > DATE_ADD(NOW(), INTERVAL 10 MINUTE) AND id = ?');
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user;
    }

    $stmt = $mysqli->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $keyStr = file_get_contents($YOUTUBE_API_JSON);
    $keyData = json_decode($keyStr);

    $data = [
        'client_id' => $keyData->web->client_id,
        'client_secret' => $keyData->web->client_secret,
        'grant_type' => 'refresh_token',
        'refresh_token' => $user['refresh_token']
    ];

    $query = http_build_query($data);
    $options = array(
        'http' => array(
            'method'  => "POST",
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($query)."\r\n".
                        "User-Agent:YouTool/0.1\r\n",
            'content' => $query,
        ),
    );
    $context = stream_context_create($options);
    $tokenResult = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
    if ($tokenResult === false) {
        return false;
    }
    $tokenData = json_decode($tokenResult);

    $writeAccess = $tokenData->scope == 'https://www.googleapis.com/auth/youtube.force-ssl' ? 1 : 0;
    $stmt = $mysqli->prepare(
        'UPDATE users SET access_token = ?, expire_time = DATE_ADD(NOW(), INTERVAL ? SECOND), write_access = ? WHERE id = ?'
    );
    $stmt->bind_param("siii", 
        $tokenData->access_token,
        $tokenData->expires_in,
        $writeAccess,
        $userId
    );
    $stmt->execute();    

    $stmt = $mysqli->prepare('SELECT * FROM users WHERE expire_time > DATE_ADD(NOW(), INTERVAL 10 MINUTE) AND id = ?');
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user;
    }

    return false;
}

function generateDescription($id, $userId) {
    global $mysqli;

    $blocks = [];
    $stmt = $mysqli->prepare('SELECT * FROM block WHERE override_categories = true AND active = true AND userId = ? AND type NOT IN ("header", "footer")');
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    array_push($blocks, ...$result->fetch_all(MYSQLI_ASSOC));

    $stmt = $mysqli->prepare('SELECT * FROM block WHERE userId = ? AND id IN (SELECT blockId FROM video_to_block WHERE videoId = ?)');
    $stmt->bind_param("ii", $userId, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    array_push($blocks, ...$result->fetch_all(MYSQLI_ASSOC));

    $stmt = $mysqli->prepare('SELECT categoryId FROM category_to_video WHERE videoId = ?');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $categories = [];
    foreach ($data as $categoryRes) {
        array_push($categories, $categoryRes['categoryId']);
    }
    
    if (count($categories) > 0) {
        $stmt = $mysqli->prepare(
            'SELECT * FROM block WHERE userId = ? AND active = true AND id IN ' . 
            '(SELECT blockId FROM category_to_block WHERE categoryId in (' . implode(',', $categories) . '))'
        );
        $stmt->bind_param("i", $userId);        
        $stmt->execute();
        $result = $stmt->get_result();
        array_push($blocks, ...$result->fetch_all(MYSQLI_ASSOC));    
    }

    $typeOrder = ['header', 'ads', 'social', 'footer'];

    $snippets = [];
    foreach ($typeOrder as $type) {
        foreach ($blocks as $block) {
            if ($block['type'] == $type) {
                array_push($snippets, $block['snippet']);
            }
        }
    }
    return implode("\n\n", $snippets);
}

function sendCommentResponse($commentId, $response, $userId) {
    global $mysqli, $user, $YOUTUBE_API_QUOTA_UPDATE_COST;

    $user = getUserAccess($userId);

    if ($user === false) {
        return [
            "message" => "Missing access key, login and try again"
        ];
    }
    if ($user['write_access'] != 1) {
        return ["message" => "Write access missing"];
    }
    if (userPayedUntil($user) === false) {
        return ["message" => "Feature requires a subscription"];
    }

    $commentData = [
        "snippet" => [
            "textOriginal" => $response,
            "parentId" => $commentId
        ] 
    ];

    $postdata = json_encode($commentData);

    $result = callYoutubeAPI(
        $user, 
        'https://www.googleapis.com/youtube/v3/comments?part=snippet', 
        'POST', 
        $postdata, 
        $YOUTUBE_API_QUOTA_UPDATE_COST
    );

    if ($result === false) {
        return [
            "message" => "Failed posting comment."
        ];
    }

    $reply = json_decode($result);

    $stmt = $mysqli->prepare('SELECT * FROM comment WHERE userId = ? AND commentId = ?');
    $stmt->bind_param("is", $user['id'], $commentId);
    $stmt->execute();
    $parentResult = $stmt->get_result();
    $parentComment = $parentResult->fetch_assoc();

    $timestamp = strtotime($reply->snippet->publishedAt);
    $stmt = $mysqli->prepare(
        'INSERT INTO comment (' . 
            'userId, videoId, commentId, authorDisplayName, authorProfileImageUrl, publishedAt, textDisplay, likeCount, visible, parentId' .
            ') VALUES (?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?, ?, 1, ?)'
    );
    $stmt->bind_param("iisssisii", 
        $user['id'],
        $parentComment['videoId'],
        $commentId,
        $reply->snippet->authorDisplayName,
        $reply->snippet->authorProfileImageUrl,
        $timestamp,
        $reply->snippet->textDisplay,
        $reply->snippet->likeCount,
        $parentComment['id']
    );
    $stmt->execute();

    return [
        "id" => $parentComment['id']
    ];
}

function loadComment($commentId, $userId) {
    global $mysqli, $YOUTUBE_API_QUOTA_LIST_COST;
    $user = getUserAccess($userId);

    if ($user === false) {
        return [
            "message" => "Missing access key, login and try again"
        ];
    }

    $result = callYoutubeAPI(
        $user, 
        'https://www.googleapis.com/youtube/v3/commentThreads?part=id,snippet,replies&id=' . $commentId, 
        'GET', 
        '', 
        $YOUTUBE_API_QUOTA_LIST_COST
    );

    if ($result === false) {
        return [
            "message" => "Failed fetching comment."
        ];
    }

    $commentData = json_decode($result);

    return [
        "id" => updateComment($userId, $commentData->items[0])
    ];
}

function updateComment($userId, $comment) {
    global $mysqli;

    $stmt = $mysqli->prepare('DELETE FROM comment WHERE userId = ? AND commentId = ?');
    $stmt->bind_param("is", $userId, $comment->id);
    $stmt->execute();

    $stmt = $mysqli->prepare('SELECT id FROM video WHERE youtubeId = ?');
    $stmt->bind_param("s", $comment->snippet->videoId);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        echo $comment->snippet->videoId . "\n";
        return;
    }

    $videoData = $res->fetch_assoc();

    $stmt = $mysqli->prepare(
        'INSERT INTO comment (' . 
            'userId, videoId, commentId, authorDisplayName, authorProfileImageUrl, publishedAt, textDisplay, likeCount, visible' .
            ') VALUES (?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?, ?, 1)'
    );
    $timestamp = strtotime($comment->snippet->topLevelComment->snippet->publishedAt);
    $stmt->bind_param("iisssisi",
        $userId,
        $videoData['id'],
        $comment->id,
        $comment->snippet->topLevelComment->snippet->authorDisplayName,
        $comment->snippet->topLevelComment->snippet->authorProfileImageUrl,
        $timestamp,
        $comment->snippet->topLevelComment->snippet->textDisplay,
        $comment->snippet->topLevelComment->snippet->likeCount,
    );
    $stmt->execute();
    $currentCommentId = $stmt->insert_id;

    if (isset($comment->replies)) {
        foreach ($comment->replies->comments as $reply) {        
            $timestamp = strtotime($reply->snippet->publishedAt);
            $stmt = $mysqli->prepare(
                'INSERT INTO comment (' . 
                    'userId, videoId, commentId, authorDisplayName, authorProfileImageUrl, publishedAt, textDisplay, likeCount, visible, parentId' .
                    ') VALUES (?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?, ?, 1, ?)'
            );
            $stmt->bind_param("iisssisii", 
                $userId,
                $videoData['id'],
                $comment->id,
                $reply->snippet->authorDisplayName,
                $reply->snippet->authorProfileImageUrl,
                $timestamp,
                $reply->snippet->textDisplay,
                $reply->snippet->likeCount,
                $currentCommentId
            );
            $stmt->execute();
        }
    }  
    return $currentCommentId;  
}

function loadVideo($youtubeId, $userId) {
    global $mysqli, $YOUTUBE_API_QUOTA_LIST_COST;
    $user = getUserAccess($userId);

    if ($user === false) {
        return [
            "message" => "Missing access key, login and try again"
        ];
    }

    $result = callYoutubeAPI(
        $user, 
        'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $youtubeId, 
        'GET', 
        '', 
        $YOUTUBE_API_QUOTA_LIST_COST
    );

    if ($result === false) {
        return [
            "message" => "Failed fetching video."
        ];
    }

    $videoData = json_decode($result);
    $timestamp = strtotime($videoData->items[0]->snippet->publishedAt);

    $stmt = $mysqli->prepare('UPDATE video SET title = ?, description = ?, youtubeCategoryId = ?, youtubePublishedAt = FROM_UNIXTIME(?) WHERE youtubeId = ? AND userId = ?');
    $stmt->bind_param("sssisi",
        $videoData->items[0]->snippet->title, 
        $videoData->items[0]->snippet->description, 
        $videoData->items[0]->snippet->categoryId,
        $timestamp,
        $youtubeId,
        $userId
    );
    $stmt->execute();

    return $videoData;
}

function submitDescription($id, $userId) {
    global $mysqli, $YOUTUBE_API_QUOTA_UPDATE_COST;

    $user = getUserAccess($userId);

    if ($user === false) {
        return ["status" => "failure", "message" => "Missing access key, login and try again"];
    }
    if ($user['write_access'] != 1) {
        return ["status" => "failure", "message" => "Write access missing"];
    }
    if (userPayedUntil($user) === false) {
        return ["status" => "failure", "message" => "Feature requires a subscription"];
    }

    $stmt = $mysqli->prepare('SELECT * FROM video WHERE id = ? AND userId = ?');
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();

    if (empty($video["title"]) || empty($video["youtubeCategoryId"])) {
        $videoDataFromYoutube = loadVideo($video["youtubeId"], $userId);
        $video['title'] = $videoDataFromYoutube->items[0]->snippet->title;
        $video['youtubeCategoryId'] = $videoDataFromYoutube->items[0]->snippet->categoryId;
    }

    $data = [
        "id" => $video["youtubeId"],
        "snippet" => [
            "title" => $video["title"],
            "description" => $video["description"],
            "categoryId" => $video["youtubeCategoryId"]
        ]
    ];
    $postdata = json_encode($data);

    $result = callYoutubeAPI(
        $user, 
        'https://www.googleapis.com/youtube/v3/videos?part=snippet', 
        'PUT', 
        $postdata, 
        $YOUTUBE_API_QUOTA_UPDATE_COST
    );

    if ($result !== false) {
        $stmt = $mysqli->prepare('UPDATE video SET published = true, publishedAt = NOW() WHERE id = ? AND userId = ?');
        $stmt->bind_param("ii", $id, $user['id']);
        $stmt->execute();

        return ["status" => "ok", "message" => "Published"];
    }
}

function updateQuotaCost($cost) {
    global $mysqli;

    $stmt = $mysqli->prepare('INSERT INTO quota (quota_day, count) ' .
        'VALUES (IF(NOW() < CONCAT(CURDATE(), " 07:00:00"), DATE_SUB(CURDATE(), INTERVAL 1 DAY), CURDATE()), ?) '.
        'ON DUPLICATE KEY UPDATE ' .
        'count = count + ?');
    $stmt->bind_param("ii", $cost, $cost);
    $stmt->execute();
}

function callYoutubeAPI($user, $url, $method, $postdata, $cost) {
    $headers = [
        'Host: www.googleapis.com',
        'Authorization: Bearer ' . $user['access_token']
    ];

    $result = curlCall($url, $method, $headers, $postdata);

    updateQuotaCost($cost);

    return $result[1];
}

function showQuota() {
    global $mysqli, $YOUTUBE_API_QUOTA_PER_DAY;

    $stmt = $mysqli->prepare('SELECT count FROM quota WHERE quota_day = ' .
        'IF(NOW() < CONCAT(CURDATE(), " 07:00:00"), DATE_SUB(CURDATE(), INTERVAL 1 DAY), CURDATE())');
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $count = isset($data['count']) ? $data['count'] : 0;
    echo $count . '/' . $YOUTUBE_API_QUOTA_PER_DAY;
}

function userPayedUntil($user) {
    global $mysqli;

    $stmt = $mysqli->prepare('SELECT payed_until FROM users WHERE payed_until > NOW() AND id = ?');
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return isset($data['payed_until']) ? $data['payed_until'] : false;
}

function showPayment() {
    global $user;

    $payedUntil = userPayedUntil($user);
    if ($payedUntil === false) {
        echo 'Standard';
    } else {
        echo 'Pro until ' . $payedUntil;
    }
}

function updateVideos($user) {    
    global $YOUTUBE_API_QUOTA_LIST_COST;

    $filename = __DIR__ . '/../data/videos_' . $user['id'] . '.json';
    if (file_exists($filename) && filemtime($filename) > time() - 3600) {
        return;
    }

    $videoList = [];

    $nextToken = '';
    while($nextToken !== false) {    
        $channelVideoList = $user['channel_id'];
        if (substr($user['channel_id'], 0, 2) == 'UC') {
            $channelVideoList = 'UU' . substr($user['channel_id'], 2);
        }

        $result = callYoutubeAPI(
            $user,
            'https://content.googleapis.com/youtube/v3/playlistItems?playlistId=' . $channelVideoList . '&maxResults=50&part=id,snippet&pageToken=' . $nextToken,
            'GET',
            '',
            $YOUTUBE_API_QUOTA_LIST_COST
        );

        $decoded = json_decode($result);
        $nextToken = isset($decoded->nextPageToken) ? $decoded->nextPageToken : false;
        foreach ($decoded->items as $video) {
            array_push($videoList, $video);
        }    
    }
    
    file_put_contents(__DIR__ . '/../data/videos_' . $user['id'] . '.json', json_encode($videoList));    
}

function searchVideos($user, $channelName, $language) {
    global $YOUTUBE_API_QUOTA_LIST_COST, $YOUTUBE_API_QUOTA_SEARCH_COST;

    $filename = __DIR__ . '/../data/search_' . $user['id'] . '.json';
    if (file_exists($filename) && filemtime($filename) > time() - 3600) {
        return;
    }

    $channelRes = callYoutubeAPI(
        $user,
        'https://www.googleapis.com/youtube/v3/channels?part=id&forHandle=' . $channelName,
        'GET',
        '',
        $YOUTUBE_API_QUOTA_LIST_COST
    );
    $channelJSON = json_decode($channelRes);

    if (isset($channelJSON->items)) {
        $videoList = [];

        $result = callYoutubeAPI(
            $user,
            'https://www.googleapis.com/youtube/v3/search?order=viewCount&maxResults=25&type=video&part=snippet&relevanceLanguage=' . $language . '&channelId=' . $channelJSON->items[0]->id,
            'GET',
            '',
            $YOUTUBE_API_QUOTA_SEARCH_COST
        );

        $decoded = json_decode($result);
        foreach ($decoded->items as $video) {
            array_push($videoList, $video);
        }

        file_put_contents(__DIR__ . '/../data/search_' . $user['id'] . '.json', json_encode($videoList));
    }
}

function curlCall($url, $method, $headers, $data) {
    array_push($headers, 'Content-Type: application/json');
    array_push($headers, 'Content-Length: ' . strlen($data));
    array_push($headers, 'User-Agent: YouTool/0.1');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if ($curl_error) {
        $response = [
            "status_code" => $info['http_code'],
            "message" => $curl_error
        ];
        return [$info['http_code'], json_encode($response)];
    } else {
        return [$info['http_code'], $response];
    }
}