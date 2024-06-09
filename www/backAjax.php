<?php
require_once(__DIR__ . '/../include/head.php');

$data = json_decode(file_get_contents('php://input'));

if (isset($data->op) && isset($data->videoId) && $data->op == 'activate') {
    $stmt = $mysqli->prepare('UPDATE video SET active = !active WHERE id = ?');
    $stmt->bind_param("i", $data->videoId);
    $stmt->execute();
    echo '{}';
    exit;
}

if (isset($data->op) && isset($data->videoId) && $data->op == 'generate') {
    $description = generateDescription($data->videoId);
    $arr = [
        "description" => $description
    ];
    echo json_encode($arr);
    exit;
}

if (isset($data->op) && isset($data->videoId) && isset($data->userId) && $data->op == 'load') {
    $videoData = loadVideo($data->videoId, $data->userId);
    $arr = [
        "description" => $videoData->items[0]->snippet->description
    ];
    echo json_encode($arr);
    exit;
}


if (isset($data->op) && isset($data->videoId) && isset($data->userId) && $data->op == 'publish') {
    $result = submitDescription($data->videoId, $data->userId);
    echo $result;
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
        $stmt = $mysqli->prepare('INSERT INTO block (snippet, type, startTime, endTime, override_categories, changed) VALUES (?, ?, ?, ?, ?, 1)');
        $stmt->bind_param("ssssi", $data->snippet, $data->type, $startTime, $endTime, $override);
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
if (isset($data->description)) {
    $stmt = $mysqli->prepare('UPDATE video SET description = ? WHERE id = ?');
    $stmt->bind_param("si", $data->description, $data->videoId);
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
    if ($data->blockId != -1) {
        $stmt = $mysqli->prepare('UPDATE block SET snippet = ? WHERE id = ?');
        $stmt->bind_param("si", $data->header, $data->blockId);
        $stmt->execute();
        echo '{"id":' . $data->blockId . '}';
        exit;    
    }

    $stmt = $mysqli->prepare('INSERT INTO block (snippet, type) VALUES (?, "header")');
    $stmt->bind_param("s", $data->header);
    $stmt->execute();
    $blockId = $stmt->insert_id;

    $stmt = $mysqli->prepare('INSERT INTO video_to_block (videoId, blockId) VALUES (?, ?)');
    $stmt->bind_param("ii", $data->videoId, $blockId);
    $stmt->execute();
    echo '{"id":' . $blockId . '}';
    exit;
}

if (isset($data->footer)) {
    if ($data->blockId != -1) {
        $stmt = $mysqli->prepare('UPDATE block SET snippet = ? WHERE id = ?');
        $stmt->bind_param("si", $data->footer, $data->blockId);
        $stmt->execute();
        echo '{"id":' . $data->blockId . '}';
        exit;    
    }    

    $stmt = $mysqli->prepare('INSERT INTO block (snippet, type) VALUES (?, "footer")');
    $stmt->bind_param("s", $data->footer);
    $stmt->execute();
    $blockId = $stmt->insert_id;

    $stmt = $mysqli->prepare('INSERT INTO video_to_block (videoId, blockId) VALUES (?, ?)');
    $stmt->bind_param("ii", $data->videoId, $blockId);
    $stmt->execute();
    echo '{"id":' . $blockId . '}';
    exit;
}
