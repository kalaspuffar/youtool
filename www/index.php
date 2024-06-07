<?php
require_once(__DIR__ . '/../include/dbconnect.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>YouTool</title>
    <meta name="description" content="Small site to play quick quizes.">
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
                        <h2>YouTool</h2>

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
                                    <option value="">Configured</option>
                                    <option <?php echo $filter == "unconfig" ? "selected" : "" ?> value="unconfig">Not Configured</option>
                                </select>
                            </form>
                        </div>
                        <?php
                        if ($filter == "unconfig") {
                            $nextToken = '';
                            while($nextToken !== false) {
                                $videos = file_get_contents('https://content.googleapis.com/youtube/v3/playlistItems?playlistId=UUnG-TN23lswO6QbvWhMtxpA&maxResults=50&part=contentDetails&key=' . $YOUTUBE_API_KEY . '&pageToken=' . $nextToken);
                                $decoded = json_decode($videos);                            
                                $nextToken = isset($decoded->nextPageToken) ? $decoded->nextPageToken : false;
                                foreach ($decoded->items as $video) {
                                    ?>
                                    <a href="video.php?videoId=<?php echo $video->contentDetails->videoId ?>">
                                        <img src="https://i.ytimg.com/vi/<?php echo $video->contentDetails->videoId ?>/default.jpg"/>
                                    </a>
                                    <?php
                                }    
                            }    
                        } else {
                            $stmt = $mysqli->prepare('SELECT * FROM video');
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