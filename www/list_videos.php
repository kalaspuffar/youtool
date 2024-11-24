<?php
require_once(__DIR__ . '/../include/head.php');

$stmt = $mysqli->prepare('SELECT * FROM category WHERE userId = ?');
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$categories = fetchAssocAll($stmt, 'id');

if (isset($_GET['filter'])) {
    $_COOKIE['filter'] = $_GET['filter'];
}
$filter = isset($_COOKIE["filter"]) ? $_COOKIE["filter"] : "";
setcookie("filter", $filter, time()+3600);

if (isset($_GET['category'])) {
    $_COOKIE['category'] = $_GET['category'];
}
$selectedCategory = isset($_COOKIE["category"]) ? $_COOKIE["category"] : "";
setcookie("category", $selectedCategory, time()+3600);

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
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="images/site.webmanifest">   
</head>
<body>
    <div class="section hero">
        <div class="container">
            <?php require_once(__DIR__ . '/../include/topbar.php'); ?>

            <div class="row">
                <div class="u-full-width column">
                    <div class="row">
                        <form method="GET" action="#">
                            <?php
                            if ($user['id'] == 1) {
                            // IN DEVELOPMENT
                            ?>
                            <input type="text" id="searchBox" placeholder="Search"/>
                            <?php } ?>
                            <select name="filter" onchange="javascript:submit()">
                                <option value="">Active</option>
                                <option <?php echo $filter == "inactive" ? "selected" : "" ?> value="inactive">Not Active</option>
                                <option <?php echo $filter == "internal" ? "selected" : "" ?> value="internal">Internal</option>                                    
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

                        updateVideos($user);

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
                            $videoId = $video->snippet->resourceId->videoId;
                            if (in_array($videoId, $configuredVideos)) continue;
                            ?>
                            <a href="edit_video.php?videoId=<?php echo $videoId  ?>">
                                <img src="https://i.ytimg.com/vi/<?php echo $videoId ?>/default.jpg"/>
                            </a>
                            <?php
                        }    
                    } else {
                        $active = 1;
                        if ($filter == 'inactive') {
                            $active = 0;
                        }
                        $internal = 0;
                        if ($filter == 'internal') {
                            $active = 0;
                            $internal = 1;
                        }
                        if ($filter == 'generating') {
                            $stmt = $mysqli->prepare('SELECT * FROM video WHERE active = true AND generated = false AND userId = ? ORDER BY youtubePublishedAt DESC');
                            $stmt->bind_param("i", $user['id']);
                            $stmt->execute();    
                        } else if ($filter == 'publishing') {
                            $stmt = $mysqli->prepare('SELECT * FROM video WHERE active = true AND published = false AND userId = ? ORDER BY youtubePublishedAt DESC');
                            $stmt->bind_param("i", $user['id']);
                            $stmt->execute();    
                        } else if ($selectedCategory == '') {
                            $stmt = $mysqli->prepare('SELECT * FROM video WHERE active = ? AND internal = ? AND userId = ? ORDER BY youtubePublishedAt DESC');
                            $stmt->bind_param("iii", $active, $internal, $user['id']);
                            $stmt->execute();    
                        } else {
                            $stmt = $mysqli->prepare(
                                'SELECT * FROM video WHERE active = ? AND userId = ? AND id IN (' .
                                    'SELECT videoId FROM category_to_video WHERE categoryId = ?' .
                                ') ORDER BY youtubePublishedAt DESC'
                            );
                            $stmt->bind_param("iii", $active, $user['id'], $selectedCategory);
                            $stmt->execute();
                        }
                        $result = $stmt->get_result();

                        ?>
                        <div id="videoList">
                            <p>Found videos: <?php echo $result->num_rows ?></p>

                            <?php

                            $items = $result->fetch_all(MYSQLI_ASSOC);
                            foreach ($items as $video) {
                                ?>
                                <a href="edit_video.php?videoId=<?php echo $video["youtubeId"] ?>">
                                    <img src="https://i.ytimg.com/vi/<?php echo $video["youtubeId"] ?>/default.jpg"/>
                                </a>
                                <?php
                            }
                        ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <?php
            if ($user['id'] == 1) {
            // IN DEVELOPMENT
            ?>
            <script>

                function makeDelay(ms) {
                    var timer = 0;
                    return function(el, callback){
                        el.style.border = '3px solid red';
                        clearTimeout (timer);
                        timer = setTimeout(callback, ms);
                    };
                };
                var delay = makeDelay(400);

                const searchBoxEl = document.getElementById('searchBox');
                const videoListEl = document.getElementById('videoList');
                searchBoxEl.addEventListener('keyup', function() {delay(searchBoxEl, searchVideos.bind())});
                searchBoxEl.addEventListener('paste', function() {delay(searchBoxEl, searchVideos.bind())});

                function searchVideos() {
                    const data = {
                        'search': searchBoxEl.value
                    }
                    fetch("backAjax.php", {
                        method: 'POST',
                        body: JSON.stringify(data)
                    }).then((res) => res.json()
                    ).then((body) => {
                        videoListEl.innerHTML = '';
                        const foundTitleEl = document.createElement('p');
                        foundTitleEl.innerHTML = 'Found videos: ' + body.length;
                        videoListEl.appendChild(foundTitleEl);

                        for (let i = 0; i < body.length; i++) {
                            video = body[i];
                            const videoLinkEl = document.createElement('a');
                            videoLinkEl.href = 'edit_video.php?videoId=' + video['youtubeId'];
                            const videoImgEl = document.createElement('img');
                            videoImgEl.src = 'https://i.ytimg.com/vi/' + video['youtubeId'] + '/default.jpg';
                            videoLinkEl.appendChild(videoImgEl);
                            videoListEl.appendChild(videoLinkEl);
                        }

                        searchBoxEl.style.border = '';
                    })
                }
            </script>
            <?php } ?>

            <?php require_once(__DIR__ . '/../include/footer.php'); ?>
        </div>
    </div>
</body>
</html>