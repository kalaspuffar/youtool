<?php
require_once(__DIR__ . '/../include/functions.php');

$stmt = $mysqli->prepare('SELECT * FROM users');
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

foreach ($users as $user) {
    $user = getUserAccess($user['id']);

    $commentList = [];

    $nextToken = '';
    while($nextToken !== false) {

        $result = callYoutubeAPI(
            $user,
            'https://content.googleapis.com/youtube/v3/commentThreads?&allThreadsRelatedToChannelId=' . $user['channel_id'] . '&maxResults=100&part=id,snippet,replies&pageToken=' . $nextToken,
            'GET',
            '',
            $YOUTUBE_API_QUOTA_LIST_COST
        );

        $decoded = json_decode($result);
        $nextToken = isset($decoded->nextPageToken) ? $decoded->nextPageToken : false;
        foreach ($decoded->items as $comment) {
            array_push($commentList, $comment);
        }    
    }
    
    file_put_contents(__DIR__ . '/../data/comments_' . $user['id'] . '.json', json_encode($commentList));
}