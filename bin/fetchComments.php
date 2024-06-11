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
    
        $options = array(
            'http' => array(
                'method'  => "GET",
                'header' => "Content-Type: application/json\r\n" .
                            "Content-Length: 0\r\n" .
                            "Authorization: Bearer " . $user['access_token'] . "\r\n" .
                            "User-Agent: YouTool/0.1\r\n"
            ),
        );
        $context = stream_context_create($options);
        
        $comments = file_get_contents('https://content.googleapis.com/youtube/v3/commentThreads?&allThreadsRelatedToChannelId=' . $user['channel_id'] . '&maxResults=100&part=id,snippet&pageToken=' . $nextToken, false, $context);                           
    
        $decoded = json_decode($comments);                   
        $nextToken = isset($decoded->nextPageToken) ? $decoded->nextPageToken : false;
        foreach ($decoded->items as $comment) {
            array_push($commentList, $comment);
        }    
    }
    
    file_put_contents(__DIR__ . '/../data/comments_' . $user['id'] . '.json', json_encode($commentList));
}