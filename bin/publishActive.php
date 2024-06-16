<?php
require_once(__DIR__ . '/../include/functions.php');


$stmt = $mysqli->prepare('SELECT count FROM quota WHERE quota_day = ' .
'IF(NOW() < CONCAT(CURDATE(), " 07:00:00"), DATE_SUB(@today, INTERVAL 1 DAY), CURDATE())');
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$quotaLeft = $YOUTUBE_API_QUOTA_PER_DAY - $data['count'] - $YOUTUBE_API_QUOTA_BUFFERT;
$allowedVideosToUpdate = (int)($quotaLeft / $YOUTUBE_API_QUOTA_UPDATE_COST);

$stmt = $mysqli->prepare(
    'SELECT id, userId FROM video WHERE active = true AND published = false AND userId IN (' . 
        'SELECT id FROM users WHERE write_access = 1 AND payed_until > NOW()'
    . ')'
);
$stmt->execute();
$result = $stmt->get_result();
$videos = $result->fetch_all(MYSQLI_ASSOC);

$count = 0;
foreach ($videos as $video) {
    if ($count >= $allowedVideosToUpdate) {
        break;
    }
    submitDescription($video['id'], $video['userId']);
    $count++;
}