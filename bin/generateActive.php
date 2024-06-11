<?php
require_once(__DIR__ . '/../include/functions.php');

$stmt = $mysqli->prepare('SELECT * FROM video WHERE active = true AND generated = false');
$stmt->execute();
$result = $stmt->get_result();
$videos = $result->fetch_all(MYSQLI_ASSOC);

foreach ($videos as $video) {
    $description = generateDescription($video['id'], $video['userId']);
    $stmt = $mysqli->prepare('UPDATE video SET description = ?, generated = true, published = false WHERE id = ?');
    $stmt->bind_param("si", $description, $video['id']);
    $stmt->execute();
}