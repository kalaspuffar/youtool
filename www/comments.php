<?php
require_once(__DIR__ . '/../include/head.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List comments</title>
    <meta name="description" content="Small site to handle your YouTube channel.">
    <meta name="author" content="Daniel Persson">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/normalize.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/skeleton.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/custom.css?r=<?php echo $CSS_UPDATE ?>">   
    <link rel="stylesheet" href="css/comments.css?r=<?php echo $CSS_UPDATE ?>">   
</head>
<body>
    <div class="section hero">
        <div class="container">
        <div class="row">
                <div class="one-half column">
                    <div class="u-full-width column">
                        <h2>List comments</h2>

                        <div class="row">
                            <a class="button" href="listVideos.php">List videos</a>
                            <a class="button" href="category.php">Categories</a>
                            <a class="button" href="block.php">Block editor</a>
                        </div>

                        <ul class="main_list">
                        <?php
                            $MAX_TEXT_LEN = 100;

                            $stmt = $mysqli->prepare('SELECT * FROM video WHERE userId = ?');
                            $stmt->bind_param("i", $user['id']);
                            $stmt->execute();
                            $videos = fetchAssocAll($stmt, 'id');
                            
                            $stmt = $mysqli->prepare('SELECT * FROM comment WHERE userId = ? AND parentId IS NULL AND visible = true');
                            $stmt->bind_param("i", $user['id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $commentList = $result->fetch_all(MYSQLI_ASSOC);
                            foreach ($commentList as $comment) {
                                $textDisplay = htmlspecialchars($comment['textDisplay']);
                                ?>
                                <li data-id="<?php echo $comment['id'] ?>" class="list_comment">
                                    <img src="https://i.ytimg.com/vi/<?php echo $videos[$comment['videoId']]['youtubeId'] ?>/default.jpg" />
                                    <div>
                                        <p><b><?php echo $comment['authorDisplayName'] ?></b></p>
                                        <p><i><?php echo $comment['publishedAt'] ?></i></p>
                                        <p>
                                        <?php
                                        if (strlen($textDisplay) > $MAX_TEXT_LEN) {
                                            echo substr($textDisplay, 0, $MAX_TEXT_LEN) . '...';
                                        } else {
                                            echo $textDisplay;
                                        }
                                        ?>
                                        </p>
                                    </div>
                                </li>
                                <?php
                            }
                        ?>
                        </ul>
                    </div> 
                </div>
                <div class="one-half column">
                    <?php 
                    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                        $stmt = $mysqli->prepare('SELECT * FROM comment WHERE userId = ? AND id = ? AND parentId IS NULL');
                        $stmt->bind_param("ii", $user['id'], $_GET['id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $comment = $result->fetch_assoc();
                        $youtubeId = $videos[$comment['videoId']]['youtubeId'];
                        ?>
                            <div class="item_content">
                                <div>
                                    <img id="video_img" src="https://i.ytimg.com/vi/<?php echo $youtubeId ?>/default.jpg" />
                                    <div class="desc">
                                        <div id="video_title"><?php echo $videos[$comment['videoId']]['title']; ?></div>
                                        <a id="video_link" src="https://www.youtube.com/watch?v=<?php echo $youtubeId ?>">Link</a>
                                    </div>
                                    <button id="hide_button">Hide</button>
                                </div>
                                <div>
                                    <hr/>
                                </div>      
                                <div class="comment">
                                    <img id="top_comment_image" src="<?php echo $comment['authorProfileImageUrl'] ?>" />
                                    <div class="desc">
                                        <p id="top_comment_name"><b><?php echo $comment['authorDisplayName'] ?></b></p>
                                        <p id="top_comment_date"><i><?php echo $comment['publishedAt'] ?></i></p>
                                        <p id="top_comment_text"><?php echo $comment['textDisplay']; ?></p>
                                    </div>
                                </div>
                                <div id="replies_list">
                                    <?php
                                        $stmt = $mysqli->prepare('SELECT * FROM comment WHERE userId = ? AND parentId = ?');
                                        $stmt->bind_param("ii", $user['id'], $comment['id']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $replies = $result->fetch_all(MYSQLI_ASSOC);
                                        foreach ($replies as $reply) {
                                            ?>
                                            <div class="comment">
                                                <img id="top_comment_image" src="<?php echo $reply['authorProfileImageUrl'] ?>" />
                                                <div class="desc">
                                                    <p id="top_comment_name"><b><?php echo $reply['authorDisplayName'] ?></b></p>
                                                    <p id="top_comment_date"><i><?php echo $reply['publishedAt'] ?></i></p>
                                                    <p id="top_comment_text"><?php echo $reply['textDisplay']; ?></p>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    ?>
                                </div>
                            </div>
                        <?php
                    } 
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        const comments = document.querySelectorAll('.list_comment');
        for (var i = 0; i < comments.length; i++) {
            comments[i].addEventListener('click', function(e) {
                var node = e.target;
                while (node.nodeName != 'LI') {
                    node = node.parentNode;
                }
                location.href = '?id=' + node.dataset.id;
            });
        }
    </script>
</body>
</html>