<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>YouTool</title>
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
                        <h2>YouTool</h2>                        
                        <a href="https://accounts.google.com/o/oauth2/auth?client_id=326206426889-v2nr3cr60pie5o6rdhv11schbrfl5340.apps.googleusercontent.com&redirect_uri=https://youtool.app/redirect.php&scope=https://www.googleapis.com/auth/youtube.readonly&response_type=code&access_type=offline">
                            <img src="images/web_dark_rd_ctn.svg" id="signin_button"/>
                        </a>
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