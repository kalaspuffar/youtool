<?php
require_once(__DIR__ . '/../include/functions.php');

$stmt = $mysqli->prepare('SELECT * FROM users');
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

$lastMonthStart = (new DateTime('first day of last month'))->format('Y-m-d');
$lastMonthEnd = (new DateTime('last day of last month'))->format('Y-m-d');

foreach ($users as $user) {
    $user = getUserAccess($user['id']);

    if ($user == false) {
        continue;
    }

    $result = callYoutubeAPI(
        $user,
        'https://content.googleapis.com/youtube/v3/channels?id=' . $user['channel_id'] . '&part=snippet,statistics',
        'GET',
        '',
        $YOUTUBE_API_QUOTA_LIST_COST
    );

    $decoded = json_decode($result);
    $channelStat = $decoded->items[0];
    
    $stmt = $mysqli->prepare('SELECT * FROM video WHERE active = true and userId = ? ORDER BY youtubePublishedAt DESC LIMIT 5');
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $videos = $result->fetch_all(MYSQLI_ASSOC);

    $videoIds = [];
    foreach ($videos as $video) {
        $videoIds[] = $video['youtubeId'];
    }

    $result = callYoutubeAPI(
        $user,
        'https://www.googleapis.com/youtube/v3/videos?id=' . implode(',', $videoIds) . '&part=snippet,statistics',
        'GET',
        '',
        $YOUTUBE_API_QUOTA_LIST_COST
    );

    $videoStats = json_decode($result);
    $channelStat->videos = $videoStats->items;
    
    $result = callYoutubeAPI(
        $user,
        'https://youtubeanalytics.googleapis.com/v2/reports?ids=channel==' . $user['channel_id'] . "&startDate=$lastMonthStart&endDate=$lastMonthEnd&metrics=views&dimensions=country&sort=-views",
        'GET',
        '',
        $YOUTUBE_API_QUOTA_LIST_COST
    );

    $decoded = json_decode($result);

    if (!isset($decoded->error)) {
        $channelStat->countryStat = $decoded->rows;
        $result = callYoutubeAPI(
            $user,
            'https://youtubeanalytics.googleapis.com/v2/reports?ids=channel==' . $user['channel_id'] . "&startDate=$lastMonthStart&endDate=$lastMonthEnd&metrics=viewerPercentage&dimensions=ageGroup,gender&sort=-viewerPercentage",
            'GET',
            '',
            $YOUTUBE_API_QUOTA_LIST_COST
        );

        $decoded = json_decode($result);

        $channelStat->demoStat = $decoded->rows;
    }

    file_put_contents(__DIR__ . '/../data/mediakit_' . $user['id'] . '.json', json_encode($channelStat));
}