<?php
require_once(__DIR__ . '/../include/functions.php');

$stmt = $mysqli->prepare(
    'SELECT id, userId FROM video WHERE active = true AND published = false AND userId IN (' . 
        'SELECT id FROM users WHERE write_access = 1 AND payed_until > NOW()'
    . ')'
);
$stmt->execute();
$result = $stmt->get_result();
$videos = $result->fetch_all(MYSQLI_ASSOC);

foreach ($videos as $video) {
    submitDescription($video['id'], $video['userId']);
}