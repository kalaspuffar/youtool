<?php
require_once(__DIR__ . '/../include/head.php');

$data = json_decode(file_get_contents('php://input'));

if (isset($data->op) && isset($data->videoId) && $data->op == 'activate') {
    $stmt = $mysqli->prepare('UPDATE video SET active = !active, internal = false WHERE id = ? AND userId = ?');
    $stmt->bind_param("ii", $data->videoId, $user['id']);
    $stmt->execute();
    echo '{}';
    exit;
}

if (isset($data->op) && isset($data->videoId) && $data->op == 'internal') {
    $stmt = $mysqli->prepare('UPDATE video SET internal = !internal, active = false WHERE id = ? AND userId = ?');
    $stmt->bind_param("ii", $data->videoId, $user['id']);
    $stmt->execute();
    echo '{}';
    exit;
}

if (isset($data->op) && isset($data->videoId) && $data->op == 'generate') {
    $description = generateDescription($data->videoId, $user['id']);
    $arr = [
        "description" => $description
    ];
    echo json_encode($arr);
    exit;
}

if (isset($data->op) && isset($data->videoId) && $data->op == 'load') {
    $videoData = loadVideo($data->videoId, $user['id']);
    $arr = [
        "title" => $videoData->items[0]->snippet->title,
        "description" => $videoData->items[0]->snippet->description
    ];
    echo json_encode($arr);
    exit;
}


if (isset($data->op) && isset($data->videoId) && $data->op == 'publish') {
    $result = submitDescription($data->videoId, $user['id']);
    echo json_encode($result);
    exit;
}

if (isset($data->commentId) && isset($data->response)) {
    $result = sendCommentResponse($data->commentId, $data->response, $user['id']);
    echo json_encode($result);
    exit;
}

if (isset($data->snippet)) {
    if (!isset($data->blockId) || !is_numeric($data->blockId)) {
        die("No blockId supplied");
    }
    $override = $data->categoryOverride ? 1 : 0;
    $startTime = $data->startTime ? $data->startTime : null;
    $endTime = $data->endTime ? $data->endTime : null;
    if ($data->blockId != -1) {
        $stmt = $mysqli->prepare('UPDATE block SET snippet = ?, type = ?, startTime = ?, endTime = ?, override_categories = ?, changed = 1 WHERE id = ?');
        $stmt->bind_param("ssssii", $data->snippet, $data->type, $startTime, $endTime, $override, $data->blockId);
        $stmt->execute();
        $blockId = $data->blockId;
    } else {
        $stmt = $mysqli->prepare('INSERT INTO block (snippet, type, startTime, endTime, override_categories, userId, changed) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $stmt->bind_param("ssssii", 
            $data->snippet, 
            $data->type, 
            $startTime, 
            $endTime, 
            $override, 
            $user['id']
        );
        $stmt->execute();
        $blockId = $stmt->insert_id;    
    }

    $stmt = $mysqli->prepare('DELETE FROM category_to_block WHERE blockId = ?');
    $stmt->bind_param("i", $blockId);
    $stmt->execute();

    foreach ($data->category as $category) {
        $stmt = $mysqli->prepare('INSERT INTO category_to_block (blockId, categoryId) VALUES (?, ?)');
        $stmt->bind_param("ii", $blockId, $category);
        $stmt->execute();    
    }
    echo '{"id":' . $blockId . '}';
    exit;
}

if (!isset($data->videoId) || !is_numeric($data->videoId)) {
    die("No videoId supplied");
}
if (isset($data->title)) {
    $stmt = $mysqli->prepare('UPDATE video SET title = ? WHERE id = ? AND userId = ?');
    $stmt->bind_param("sii", $data->title, $data->videoId, $user['id']);
    $stmt->execute();
    echo '{"id":' . $data->videoId . '}';
    exit;
}
if (isset($data->description)) {
    $stmt = $mysqli->prepare('UPDATE video SET description = ? WHERE id = ? AND userId = ?');
    $stmt->bind_param("sii", $data->description, $data->videoId, $user['id']);
    $stmt->execute();
    echo '{"id":' . $data->videoId . '}';
    exit;
}
if (isset($data->category)) {
    $stmt = $mysqli->prepare('DELETE FROM category_to_video WHERE videoId = ?');
    $stmt->bind_param("i", $data->videoId);
    $stmt->execute();

    foreach ($data->category as $category) {
        $stmt = $mysqli->prepare('INSERT INTO category_to_video (videoId, categoryId) VALUES (?, ?)');
        $stmt->bind_param("ii", $data->videoId, $category);
        $stmt->execute();    
    }
    echo '{"id":' . $data->videoId . '}';
    exit;    
}

if (!isset($data->blockId) || !is_numeric($data->blockId)) {
    die("No blockId supplied");
}

if (isset($data->header)) {
    $stmt = $mysqli->prepare('UPDATE video SET generated = false WHERE id = ? AND userId = ?');
    $stmt->bind_param("ii", $data->videoId, $user['id']);    
    $stmt->execute();

    if ($data->blockId != -1) {
        $stmt = $mysqli->prepare('UPDATE block SET snippet = ? WHERE id = ? AND userId = ?');
        $stmt->bind_param("sii", $data->header, $data->blockId, $user['id']);
        $stmt->execute();  
        echo '{"id":' . $data->blockId . '}';
        exit;    
    }

    $stmt = $mysqli->prepare('INSERT INTO block (snippet, userId, type) VALUES (?, ?, "header")');
    $stmt->bind_param("si", $data->header, $user['id']);
    $stmt->execute();
    $blockId = $stmt->insert_id;

    $stmt = $mysqli->prepare('INSERT INTO video_to_block (videoId, blockId) VALUES (?, ?)');
    $stmt->bind_param("ii", $data->videoId, $blockId);
    $stmt->execute();
    echo '{"id":' . $blockId . '}';
    exit;
}

if (isset($data->footer)) {
    $stmt = $mysqli->prepare('UPDATE video SET generated = false WHERE id = ? AND userId = ?');
    $stmt->bind_param("ii", $data->videoId, $user['id']);
    $stmt->execute();

    if ($data->blockId != -1) {
        $stmt = $mysqli->prepare('UPDATE block SET snippet = ? WHERE id = ? AND userId = ?');
        $stmt->bind_param("sii", $data->footer, $data->blockId, $user['id']);
        $stmt->execute();    
        echo '{"id":' . $data->blockId . '}';
        exit;    
    }    

    $stmt = $mysqli->prepare('INSERT INTO block (snippet, userId, type) VALUES (?, ?, "footer")');
    $stmt->bind_param("si", $data->footer, $user['id']);
    $stmt->execute();
    $blockId = $stmt->insert_id;

    $stmt = $mysqli->prepare('INSERT INTO video_to_block (videoId, blockId) VALUES (?, ?)');
    $stmt->bind_param("ii", $data->videoId, $blockId);
    $stmt->execute();
    echo '{"id":' . $blockId . '}';
    exit;
}
