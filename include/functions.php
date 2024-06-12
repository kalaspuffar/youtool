<?php
require_once(__DIR__ . '/../include/dbconnect.php');

function getUserAccess($userId) {
    global $mysqli;

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

    $keyStr = file_get_contents(__DIR__ . '/../etc/client_secret_326206426889-v2nr3cr60pie5o6rdhv11schbrfl5340.apps.googleusercontent.com.json');
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
            'SELECT * FROM block WHERE userId = ? AND id IN ' . 
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
    global $mysqli;

    $user = getUserAccess($userId);

    if ($user === false) {
        return [
            "message" => "Missing access key, login and try again"
        ];
    }

    $commentData = [
        "snippet" => [
            "textOriginal" => $response,
            "parentId" => $commentId
        ] 
    ];

    $postdata = json_encode($commentData);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  =>
                "Host: www.googleapis.com\r\n" .
                "Content-Length: " . strlen($postdata) . "\r\n" .
                "Content-Type: application/json\r\n" .
                "Authorization: Bearer " . $user['access_token'] . "\r\n" .
                "User-Agent: YouTool/0.1\r\n",
            'content' => $postdata
        ]
    ]);

    $result = file_get_contents(
        "https://www.googleapis.com/youtube/v3/comments?part=snippet", 
        false, 
        $context
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
    global $mysqli;
    $user = getUserAccess($userId);

    if ($user === false) {
        return [
            "message" => "Missing access key, login and try again"
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  =>
                "Host: www.googleapis.com\r\n" .
                "Content-Length: 0\r\n" .
                "Content-Type: application/json\r\n" .
                "Authorization: Bearer " . $user['access_token'] . "\r\n" .
                "User-Agent: YouTool/0.1\r\n",
        ]
    ]);

    $result = file_get_contents(
        "https://www.googleapis.com/youtube/v3/commentThreads?part=id,snippet,replies&id=" . $commentId, 
        false, 
        $context
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
    global $mysqli;
    $user = getUserAccess($userId);

    if ($user === false) {
        return [
            "message" => "Missing access key, login and try again"
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  =>
                "Host: www.googleapis.com\r\n" .
                "Content-Length: 0\r\n" .
                "Content-Type: application/json\r\n" .
                "Authorization: Bearer " . $user['access_token'] . "\r\n" .
                "User-Agent: YouTool/0.1\r\n",
        ]
    ]);

    $result = file_get_contents(
        "https://www.googleapis.com/youtube/v3/videos?part=snippet&id=" . $youtubeId, 
        false, 
        $context
    );

    if ($result === false) {
        return [
            "message" => "Failed fetching video."
        ];
    }

    $videoData = json_decode($result);

    $stmt = $mysqli->prepare('UPDATE video SET title = ?, description = ?, youtubeCategoryId = ? WHERE youtubeId = ? AND userId = ?');
    $stmt->bind_param("ssssi", 
        $videoData->items[0]->snippet->title, 
        $videoData->items[0]->snippet->description, 
        $videoData->items[0]->snippet->categoryId,
        $youtubeId, 
        $userId
    );
    $stmt->execute();

    return $videoData;
}

function submitDescription($id, $userId) {
    global $mysqli;

    $user = getUserAccess($userId);

    if ($user === false) {
        return ["message" => "Missing access key, login and try again"];
    }
    if ($user['write_access'] != 1) {
        return ["message" => "Write access missing"];
    }
    if (!isset($user['payed_until'])) {
        return ["message" => "Feature requires a subscription"];
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

    $context = stream_context_create([
        'http' => [
            'method'  => 'PUT',
            'header'  =>
                "Host: www.googleapis.com\r\n" .
                "Content-Length: " . strlen($postdata) . "\r\n" .
                "Content-Type: application/json\r\n" .
                "Authorization: Bearer " . $user['access_token'] . "\r\n" .
                "User-Agent: YouTool/0.1\r\n",
            'content' => $postdata
        ]
    ]);

    $result = file_get_contents(
        "https://www.googleapis.com/youtube/v3/videos?part=snippet", 
        false, 
        $context
    );

    if ($result !== false) {
        $stmt = $mysqli->prepare('UPDATE video SET published = true WHERE id = ? AND userId = ?');
        $stmt->bind_param("ii", $id, $user['id']);
        $stmt->execute();

        return ["message" => "Published"];
    }
}