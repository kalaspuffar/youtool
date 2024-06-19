<?php
require_once(__DIR__ . '/../include/functions.php');

$stmt = $mysqli->prepare('SELECT * FROM users');
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

foreach ($users as $user) {
    $user = getUserAccess($user['id']);

    updateVideos($user);
}