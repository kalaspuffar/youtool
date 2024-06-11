<?php
require_once(__DIR__ . '/../include/functions.php');

$stmt = $mysqli->prepare('SELECT * FROM users');
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

foreach ($users as $user) {
    $user = getUserAccess($user['id']);

    $videoList = [];

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
        
        $channelVideoList = $user['channel_id'];
        if (substr($user['channel_id'], 0, 2) == 'UC') {
            $channelVideoList = 'UU' . substr($user['channel_id'], 2);
        }
    
        $videos = file_get_contents('https://content.googleapis.com/youtube/v3/playlistItems?playlistId=' . $channelVideoList . '&maxResults=50&part=id,snippet&pageToken=' . $nextToken, false, $context);                           
    
        $decoded = json_decode($videos);                            
        $nextToken = isset($decoded->nextPageToken) ? $decoded->nextPageToken : false;
        foreach ($decoded->items as $video) {
            array_push($videoList, $video);
        }    
    }
    
    file_put_contents(__DIR__ . '/../data/videos_' . $user['id'] . '.json', json_encode($videoList));
}