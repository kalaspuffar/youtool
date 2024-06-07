<?php
require_once(__DIR__ . '/../include/dbconnect.php');

$videoId = $_GET["videoId"];

$headerId = -1;
$headerSnippet = '';
$footerId = -1;
$footerSnippet = '';    

$stmt = $mysqli->prepare('SELECT * FROM video WHERE youtubeId = ?');
$stmt->bind_param("s", $videoId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    $stmt = $mysqli->prepare('INSERT INTO video (youtubeId) VALUES (?)');
    $stmt->bind_param("s", $videoId);
    $stmt->execute();
    $stmt = $mysqli->prepare('SELECT * FROM video WHERE youtubeId = ?');
    $stmt->bind_param("s", $videoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
} else {
    $data = $result->fetch_assoc();
    $stmt = $mysqli->prepare('SELECT b.id, b.type, b.snippet FROM block as b, video_to_block as vb WHERE vb.videoId = ? AND vb.blockId = b.id');
    $stmt->bind_param("i", $data["id"]);
    $stmt->execute();
    $blockData = fetchAssocAll($stmt, 'type');
    $headerId = isset($blockData['header']['id']) ? $blockData['header']['id'] : -1;
    $headerSnippet = isset($blockData['header']) ? $blockData['header']['snippet'] : '';
    $footerId = isset($blockData['footer']['id']) ? $blockData['footer']['id'] : -1;
    $footerSnippet = isset($blockData['footer']) ? $blockData['footer']['snippet'] : '';    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit video</title>
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
            <h3>Edit video: <?php $videoId ?></h3>
            <div class="row">
                <a class="button" href="index.php">Home</a>
                <a class="button" href="category.php">Categories</a>
                <a class="button" href="block.php">Block editor</a>
            </div>
            <div class="row">
                <div class="one-half column">
                    <div class="u-full-width column">
                        <h4>Description</h4>
                        <textarea id="videoDesc" rows="20" class="u-full-width"><?php echo $data["description"] ?></textarea>
                    </div>
                </div>
                <div class="one-half column">
                    <div class="u-full-width column">
                        <h4>Header</h4>
                        <input type="hidden" value="<?php echo $headerId ?>" id="headerId"/>
                        <textarea id="header" rows="8" class="u-full-width"><?php echo $headerSnippet ?></textarea>
                        <h4>Footer</h4>
                        <input type="hidden" value="<?php echo $footerId ?>" id="footerId"/>
                        <textarea id="footer" rows="8" class="u-full-width"><?php echo $footerSnippet ?></textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <button id="loadVideo">Load video</button>
                <button id="activateVideo">Activate</button>
                <button id="generateVideo">Generate</button>
                <button id="publishVideo">Publish</button>
            </div>
        </div>
    </div>

    <script>
        function makeDelay(ms) {
            var timer = 0;
            return function(callback){
                clearTimeout (timer);
                timer = setTimeout(callback, ms);
            };
        };
        var delay = makeDelay(400);

        const loadVideoEl = document.getElementById('loadVideo');
        const activateVideoEl = document.getElementById('activateVideo');
        const generateVideoEl = document.getElementById('generateVideo');
        const publishVideoEl = document.getElementById('publishVideo');

        const videoDescEl = document.getElementById('videoDesc');
        const headerEl = document.getElementById('header');
        const footerEl = document.getElementById('footer');

        loadVideoEl.addEventListener('click', loadVideo.bind());
        activateVideoEl.addEventListener('click', activateVideo.bind());
        generateVideoEl.addEventListener('click', generateVideo.bind());
        publishVideoEl.addEventListener('click', publishVideo.bind());

        videoDescEl.addEventListener('keyup', function() {delay(videoDescSave.bind())});
        videoDescEl.addEventListener('paste', function() {delay(videoDescSave.bind())});
        headerEl.addEventListener('keyup', function() {delay(headerSave.bind())});
        headerEl.addEventListener('paste', function() {delay(headerSave.bind())});
        footerEl.addEventListener('keyup', function() {delay(footerSave.bind())});
        footerEl.addEventListener('paste', function() {delay(footerSave.bind())});

        function loadVideo() {
            var url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=';
            url += '<?php echo $videoId ?>&key=<?php echo $YOUTUBE_API_KEY ?>';
            fetch(url)
                .then((res) => res.json())
                .then((data) => {
                    videoDescEl.value = data.items[0].snippet.description;
                    videoDescSave();
                });
        }
        function activateVideo() {
            alert('Not implemented yet');
        }
        function generateVideo() {
            alert('Not implemented yet');
        }
        function publishVideo() {
            alert('Not implemented yet');
        }
        function videoDescSave() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'description': videoDescEl.value
            }
            fetch("videoAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                console.log(body);
            })
        }
        function headerSave() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'blockId': document.getElementById('headerId').value,
                'header': headerEl.value
            }
            fetch("videoAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                document.getElementById('headerId').value = body.id;
            })
        }
        function footerSave() {
            const data = {
                'videoId': <?php echo $data["id"] ?>,
                'blockId': document.getElementById('footerId').value,
                'footer': footerEl.value
            }
            fetch("videoAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                document.getElementById('footerId').value = body.id;
            })
        }
    </script>
</body>
</html>