<?php
require_once(__DIR__ . '/../include/dbconnect.php');

$data = json_decode(file_get_contents('php://input'));
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
    $stmt->bind_param("s", $data->footerd);
    $stmt->execute();
    $blockId = $stmt->insert_id;

    $stmt = $mysqli->prepare('INSERT INTO video_to_block (videoId, blockId) VALUES (?, ?)');
    $stmt->bind_param("ii", $data->videoId, $blockId);
    $stmt->execute();
    echo '{"id":' . $blockId . '}';
    exit;
}