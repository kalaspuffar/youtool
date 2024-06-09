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
}

function generateDescription($id) {
    global $mysqli;

    $blocks = [];
    $stmt = $mysqli->prepare('SELECT * FROM block WHERE override_categories = true AND active = true AND type NOT IN ("header", "footer")');
    $stmt->execute();
    $result = $stmt->get_result();
    array_push($blocks, ...$result->fetch_all(MYSQLI_ASSOC));

    $stmt = $mysqli->prepare('SELECT * FROM block WHERE id IN (SELECT blockId FROM video_to_block WHERE videoId = ?)');
    $stmt->bind_param("i", $id);
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
            'SELECT * FROM block WHERE id IN ' . 
            '(SELECT blockId FROM category_to_block WHERE categoryId in (' . implode(',', $categories) . '))'
        );
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

function loadVideo($youtubeId, $userId) {
    $user = getUserAccess($userId);
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
    return json_decode($result);
}

function submitDescription($id, $userId) {
    global $mysqli;

    $user = getUserAccess($userId);

    if ($user['write_access'] != 1) {
        return '{"message": "Write access missing"}';
    }
    if (!isset($user['payed_until'])) {
        return '{"message": "Feature requires a subscription"}';
    }

    $stmt = $mysqli->prepare('SELECT youtubeId, description FROM video WHERE id = ?');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();

    $videoDataFromYoutube = loadVideo($video["youtubeId"], $userId);

    $data = [
        "id" => $video["youtubeId"],
        "snippet" => [
            "title" => $videoDataFromYoutube->items[0]->snippet->title,
            "description" => $video["description"],
            "categoryId" => $videoDataFromYoutube->items[0]->snippet->categoryId
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
        $stmt = $mysqli->prepare('UPDATE video SET submitted = true WHERE id = ?');
        $stmt->bind_param("i", $id);
        $stmt->execute();

       return '{"message": "Published"}';
    }
}