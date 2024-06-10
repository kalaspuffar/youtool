<?php
require_once(__DIR__ . '/../include/head.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List videos</title>
    <meta name="description" content="Small site to handle your YouTube channel.">
    <meta name="author" content="Daniel Persson">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/normalize.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/skeleton.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/custom.css?r=<?php echo $CSS_UPDATE ?>">   
</head>
<body>
    <div class="section hero">
        <div class="container">
        <div class="row">
                <div class="one-half column">
                    <div class="u-full-width column">
                        <h2>List videos</h2>

                        <div class="row">
                            <a class="button" href="category.php">Categories</a>
                            <a class="button" href="block.php">Block editor</a>
                        </div>

                        <?php 
                        $filter = isset($_GET["filter"]) ? $_GET["filter"] : "";
                        ?>
                        <div class="row">
                            <form method="GET" action="#">
                                <select name="filter" onchange="javascript:submit()">
                                    <option value="">Active</option>
                                    <option <?php echo $filter == "inactive" ? "selected" : "" ?> value="inactive">Not Active</option>
                                    <option <?php echo $filter == "unconfig" ? "selected" : "" ?> value="unconfig">Not Configured</option>
                                </select>
                            </form>
                        </div>
                        <?php
                        if ($filter == "unconfig") {
                            $stmt = $mysqli->prepare('SELECT youtubeId FROM video WHERE userId = ?');
                            $stmt->bind_param("i", $user['id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $items = $result->fetch_all(MYSQLI_ASSOC);
                            $configuredVideos = [];
                            foreach ($items as $video) {
                                array_push($configuredVideos, $video["youtubeId"]);
                            }                            

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

                                $videos = file_get_contents('https://content.googleapis.com/youtube/v3/playlistItems?playlistId=' . $channelVideoList . '&maxResults=50&part=contentDetails&pageToken=' . $nextToken, false, $context);                           

                                $decoded = json_decode($videos);                            
                                $nextToken = isset($decoded->nextPageToken) ? $decoded->nextPageToken : false;
                                foreach ($decoded->items as $video) {
                                    if (in_array($video->contentDetails->videoId, $configuredVideos)) continue;
                                    ?>
                                    <a href="video.php?videoId=<?php echo $video->contentDetails->videoId ?>">
                                        <img src="https://i.ytimg.com/vi/<?php echo $video->contentDetails->videoId ?>/default.jpg"/>
                                    </a>
                                    <?php
                                }    
                            }    
                        } else {
                            $stmt = $mysqli->prepare('SELECT * FROM video WHERE active = ? AND userId = ?');
                            $active = 1;
                            if (isset($_GET['filter']) && $_GET['filter'] == 'inactive') {
                                $active = 0;
                            }
                            $stmt->bind_param("ii", $active, $user['id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $items = $result->fetch_all(MYSQLI_ASSOC);
                            foreach ($items as $video) {
                                ?>
                                <a href="video.php?videoId=<?php echo $video["youtubeId"] ?>">
                                    <img src="https://i.ytimg.com/vi/<?php echo $video["youtubeId"] ?>/default.jpg"/>
                                </a>
                                <?php
                            }
                        }
                        ?>                        
                    </div>
                </div>
                <div class="one-half column">
                    <img class="playful" src="https://cataas.com/cat">
                </div>
            </div>
        </div>
    </div>
</body>
</html>