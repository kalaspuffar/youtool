<?php
require_once(__DIR__ . '/../include/functions.php');

$stmt = $mysqli->prepare('SELECT id FROM block WHERE type = "ads" AND adsId IS NOT NULL AND adsId != -1');
$stmt->execute();
$result = $stmt->get_result();
$blocks = $result->fetch_all(MYSQLI_ASSOC);

foreach ($blocks as $block) {
    updateAdTimes($block['id']);
}