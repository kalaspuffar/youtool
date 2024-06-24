<?php
require_once(__DIR__ . '/../include/head.php');

$videoId = $_GET["videoId"];

$headerId = -1;
$headerSnippet = '';
$footerId = -1;
$footerSnippet = '';    

$stmt = $mysqli->prepare('SELECT * FROM category WHERE userId = ?');
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$categories = fetchAssocAll($stmt, 'id');

$stmt = $mysqli->prepare(
    'SELECT v.*, GROUP_CONCAT(c.categoryId) as categories FROM ' . 
    'video as v LEFT JOIN category_to_video as c ON v.id = c.videoId ' .
    'WHERE v.youtubeId = ? AND userId = ?'
);

$stmt->bind_param("si", $videoId, $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
if (!isset($data['id'])) {
    $stmt = $mysqli->prepare('INSERT INTO video (youtubeId, userId, active) VALUES (?, ?, 0)');
    $stmt->bind_param("si", $videoId, $user['id']);
    $stmt->execute();
    $stmt = $mysqli->prepare('SELECT * FROM video WHERE youtubeId = ? AND userId = ?');
    $stmt->bind_param("si", $videoId, $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
} else {
    $stmt = $mysqli->prepare('SELECT b.id, b.type, b.snippet FROM block as b, video_to_block as vb WHERE vb.videoId = ? AND b.userId = ? AND vb.blockId = b.id');
    $stmt->bind_param("ii", $data["id"], $user['id']);
    $stmt->execute();
    $blockData = fetchAssocAll($stmt, 'type');
    $headerId = isset($blockData['header']['id']) ? $blockData['header']['id'] : -1;
    $headerSnippet = isset($blockData['header']) ? $blockData['header']['snippet'] : '';
    $footerId = isset($blockData['footer']['id']) ? $blockData['footer']['id'] : -1;
    $footerSnippet = isset($blockData['footer']) ? $blockData['footer']['snippet'] : '';    
}



$active = isset($data['active']) && $data['active'];
$internal = isset($data['internal']) && $data['internal'];
$background = '';
if ($active) $background = 'style="background-color: #4f1a59;"';
if ($internal) $background = 'style="background-color: #90EE90;"';
$title = isset($data['title']) ? $data['title'] : '';
$description = isset($data['description']) ? $data['description'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit video</title>
    <meta name="description" content="Small site to handle your YouTube channel.">
    <meta name="author" content="Daniel Persson">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/normalize.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/skeleton.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/custom.css?r=<?php echo $CSS_UPDATE ?>">   
</head>
<body <?php echo $background ?>>
    <div class="section hero">
        <div class="container">
            <?php require_once(__DIR__ . '/../include/topbar.php'); ?>

            <div class="row">
                <button id="loadVideo">Load video</button>
                <button id="activateVideo">Activate</button>
                <button id="internalVideo">Internal</button>
                <button id="generateVideo">Generate</button>
                <button id="publishVideo">Publish</button>
            </div>

            <div class="row">
                <div class="one-half column">
                    <div class="u-full-width column">
                        <h4>Video details</h4>

                        <?php
                        if ($user['id'] == 1) {
                        // IN DEVELOPMENT
                        ?>
                        <div class="row">
                            <select id="titleSelector" class="u-full-width">
                                <option value="">None</option>
                            </select>
                            <button id="generateTitleButton">Generate titles</button>
                        </div>
                        <?php } ?>

                        <textarea id="videoTitle" rows="2" class="u-full-width"><?php echo $title ?></textarea>
                        <textarea id="videoDesc" rows="29" class="u-full-width" <?php echo $active ? 'disabled' : '' ?>><?php echo $description ?></textarea>
                    </div>
                    <iframe width="450" height="253" src="https://www.youtube.com/embed/<?php echo $videoId ?>"frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="one-half column">
                    <div class="u-full-width column">
                        <h4>Categories</h4>
                        <?php 
                            $categorySelectHeight = (count($categories) * 21) + 4;
                        ?>
                        <select name="category" id="videoCategory" class="u-full-width" multiple style="height: <?php echo $categorySelectHeight ?>px;">
                            <?php
                                $selectedCategories = explode(',', $data["categories"]);                           
                                foreach ($categories as $categoryKey => $category) {
                                    $selected = in_array($categoryKey, $selectedCategories) ? 'selected' : '';
                                    echo '<option ' . $selected . ' value="' . $categoryKey . '">' . $category["name"] . '</option>';
                                }
                            ?>
                        </select>

                        <h4>Header</h4>
                        <input type="hidden" value="<?php echo $headerId ?>" id="headerId"/>
                        <textarea id="header" rows="8" class="u-full-width"><?php echo $headerSnippet ?></textarea>
                        <h4>Footer</h4>
                        <input type="hidden" value="<?php echo $footerId ?>" id="footerId"/>
                        <textarea id="footer" rows="8" class="u-full-width"><?php echo $footerSnippet ?></textarea>
                    </div>
                </div>
            </div>
            <?php require_once(__DIR__ . '/../include/footer.php'); ?>
        </div>
    </div>

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

        const loadVideoEl = document.getElementById('loadVideo');
        const activateVideoEl = document.getElementById('activateVideo');
        const internalVideoEl = document.getElementById('internalVideo');
        const generateVideoEl = document.getElementById('generateVideo');
        const publishVideoEl = document.getElementById('publishVideo');

        const videoTitleEl = document.getElementById('videoTitle');
        const videoDescEl = document.getElementById('videoDesc');
        const headerEl = document.getElementById('header');
        const footerEl = document.getElementById('footer');
        const videoCategoryEl = document.getElementById('videoCategory');       

        loadVideoEl.addEventListener('click', loadVideo.bind());
        activateVideoEl.addEventListener('click', activateVideo.bind());
        internalVideoEl.addEventListener('click', internalVideo.bind());
        generateVideoEl.addEventListener('click', generateVideo.bind());
        publishVideoEl.addEventListener('click', publishVideo.bind());
        
        videoTitleEl.addEventListener('keyup', function() {delay(videoTitleEl, videoTitleSave.bind())});
        videoTitleEl.addEventListener('paste', function() {delay(videoTitleEl, videoTitleSave.bind())});
        videoDescEl.addEventListener('keyup', function() {delay(videoDescEl, videoDescSave.bind())});
        videoDescEl.addEventListener('paste', function() {delay(videoDescEl, videoDescSave.bind())});
        headerEl.addEventListener('keyup', function() {delay(headerEl, headerSave.bind())});
        headerEl.addEventListener('paste', function() {delay(headerEl, headerSave.bind())});
        footerEl.addEventListener('keyup', function() {delay(footerEl, footerSave.bind())});
        footerEl.addEventListener('paste', function() {delay(footerEl, footerSave.bind())});
        videoCategoryEl.addEventListener('click', function() {delay(videoCategoryEl, categorySave.bind())});
        videoCategoryEl.addEventListener('change', function() {delay(videoCategoryEl, categorySave.bind())});       

        function loadVideo() {
            const data = {
                'videoId': '<?php echo $data["youtubeId"] ?>',
                'op': 'load'
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                videoTitleEl.value = body.title;
                videoDescEl.value = body.description;
            });
        }
        function activateVideo() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'op': 'activate'
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                location.href = location.href;
            });
        }
        function internalVideo() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'op': 'internal'
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                location.href = location.href;
            });
        }    
        function generateVideo() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'op': 'generate'
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                videoDescEl.value = body.description;
            });
        }
        function publishVideo() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'op': 'publish'
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                if (body.message) {
                    alert(body.message);
                }
            });
        }

        function videoTitleSave() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'title': videoTitleEl.value
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                videoTitleEl.style.border = '';
            })
        }

        function videoDescSave() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'description': videoDescEl.value
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                videoDescEl.style.border = '';
            })
        }
        function headerSave() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'blockId': document.getElementById('headerId').value,
                'header': headerEl.value
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                document.getElementById('headerId').value = body.id;
                headerEl.style.border = '';
            })
        }
        function footerSave() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'blockId': document.getElementById('footerId').value,
                'footer': footerEl.value
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                document.getElementById('footerId').value = body.id;
                footerEl.style.border = '';
            })
        }

        function categorySave() {
            var videoCategoryOptions = videoCategoryEl.selectedOptions;
            var categoryValues = Array.from(videoCategoryOptions).map(({ value }) => value);
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'category': categoryValues,
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                videoCategoryEl.style.border = '';
            })
        }

        <?php if ($user['id'] == 1) { ?>
        // IN DEVELOPMENT
        const generateTitleButtonEl = document.getElementById('generateTitleButton');
        const titleSelectorEl = document.getElementById('titleSelector');

        generateTitleButtonEl.addEventListener("click", function(e) {
            titleSelectorEl.style.border = '2px solid red';
            const data = {
                'description': videoDescEl.value,
            }
            fetch("assistantAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                for (let i = 0; i < body.choices.length; i++) {
                    choice = body.choices[i];
                    var opt = document.createElement('option');
                    opt.value = choice.message.content.replaceAll('"', '');
                    opt.innerHTML = choice.message.content.replaceAll('"', '');
                    titleSelectorEl.appendChild(opt);
                    titleSelectorEl.style.border = '';
                }
            });
        });

        titleSelectorEl.addEventListener('change', function() {
            videoTitleEl.value = titleSelectorEl.value;
            videoTitleSave();
        });
        <?php } ?>
    </script>
</body>
</html>