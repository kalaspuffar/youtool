<?php
require_once(__DIR__ . '/../include/head.php');

$stmt = $mysqli->prepare('SELECT * FROM category WHERE userId = ?');
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$categories = fetchAssocAll($stmt, 'id');
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

                        <h5>Update to write access:</h5>
                        <a href="https://accounts.google.com/o/oauth2/auth?client_id=326206426889-v2nr3cr60pie5o6rdhv11schbrfl5340.apps.googleusercontent.com&redirect_uri=https://youtool.app/redirect.php&scope=https://www.googleapis.com/auth/youtube.force-ssl&response_type=code&access_type=offline">
                            <img src="images/web_dark_rd_ctn.svg" id="signin_button"/>
                        </a><br/>

                        <div class="row">
                            <a class="button" href="category.php">Categories</a>
                            <a class="button" href="block.php">Block editor</a>
                            <a class="button" href="comments.php">List comments</a>
                        </div>

                        <?php 
                        $filter = isset($_GET["filter"]) ? $_GET["filter"] : "";
                        $selectedCategory = isset($_GET["category"]) ? $_GET["category"] : "";
                        ?>
                        <div class="row">
                            <form method="GET" action="#">
                                <select name="filter" onchange="javascript:submit()">
                                    <option value="">Active</option>
                                    <option <?php echo $filter == "inactive" ? "selected" : "" ?> value="inactive">Not Active</option>
                                    <option <?php echo $filter == "generating" ? "selected" : "" ?> value="generating">Not Generated</option>
                                    <option <?php echo $filter == "publishing" ? "selected" : "" ?> value="publishing">Not Published</option>
                                    <option <?php echo $filter == "unconfig" ? "selected" : "" ?> value="unconfig">Not Configured</option>
                                </select>

                                <select name="category" onchange="javascript:submit()">
                                    <option value="">Category</option>
                                    <?php
                                        foreach ($categories as $categoryKey => $category) {
                                            $selected = $categoryKey == $selectedCategory ? 'selected' : '';
                                            echo '<option ' . $selected . ' value="' . $categoryKey . '">' . $category["name"] . '</option>';
                                        }
                                    ?>
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

                            $videoList = file_get_contents(__DIR__ . '/../data/videos_' . $user['id'] . '.json');

                            foreach (json_decode($videoList) as $video) {
                                if (in_array($video->contentDetails->videoId, $configuredVideos)) continue;
                                ?>
                                <a href="video.php?videoId=<?php echo $video->contentDetails->videoId ?>">
                                    <img src="https://i.ytimg.com/vi/<?php echo $video->contentDetails->videoId ?>/default.jpg"/>
                                </a>
                                <?php
                            }    
                        } else {
                            $active = 1;
                            if (isset($_GET['filter']) && $_GET['filter'] == 'inactive') {
                                $active = 0;
                            }
                            if (isset($_GET['filter']) && $_GET['filter'] == 'generating') {
                                $stmt = $mysqli->prepare('SELECT * FROM video WHERE active = true AND generated = false AND userId = ?');
                                $stmt->bind_param("i", $user['id']);
                                $stmt->execute();    
                            } else if (isset($_GET['filter']) && $_GET['filter'] == 'publishing') {
                                $stmt = $mysqli->prepare('SELECT * FROM video WHERE active = true AND published = false AND userId = ?');
                                $stmt->bind_param("i", $user['id']);
                                $stmt->execute();    
                            } else if ($selectedCategory == '') {
                                $stmt = $mysqli->prepare('SELECT * FROM video WHERE active = ? AND userId = ?');
                                $stmt->bind_param("ii", $active, $user['id']);
                                $stmt->execute();    
                            } else {
                                $stmt = $mysqli->prepare(
                                    'SELECT * FROM video WHERE active = ? AND userId = ? AND id IN (' .
                                        'SELECT videoId FROM category_to_video WHERE categoryId = ?' .
                                    ')'
                                );
                                $stmt->bind_param("iii", $active, $user['id'], $selectedCategory);
                                $stmt->execute();
                            }
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