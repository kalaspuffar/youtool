<?php
require_once(__DIR__ . '/../include/head_optional.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Start page</title>
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
            <?php require_once(__DIR__ . '/../include/topbar.php'); ?>

            <p>Manage your Youtube content</p>

            <iframe width="560" height="315" src="https://www.youtube.com/embed/IzPgbDcY2ns" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>

            <div class="row">
                <a class="button button-primary" href="payments.php">Continue</a>
            </div>


            <?php require_once(__DIR__ . '/../include/footer.php'); ?>
        </div>
    </div>
</body>
</html>