<?php
require_once(__DIR__ . '/../include/head.php');
if ($user['id'] != 1) {
    die('in development');
}
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
                    <table>
                        <tr>
                            <th>Id</th>
                            <th>Advertiser Name</th>
                            <th>Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Snippet/Track/Landing</th>
                        </tr>
                    <?php
                    $stmt = $mysqli->prepare('SELECT * FROM ads ORDER BY startTime DESC');
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $items = $result->fetch_all(MYSQLI_ASSOC);
                    foreach ($items as $ad) {
                        ?>
                        <tr>
                            <td><?php echo $ad["id"] ?></td>
                            <td><?php echo $ad["advertiserName"] ?></td>
                            <td><?php echo $ad["name"] ?></td>
                            <td><?php echo $ad["startTime"] ?></td>
                            <td><?php echo $ad["endTime"] ?></td>                        
                            <td>
                                <?php echo $ad["snippet"] ?><br><br>
                                <?php echo $ad["trackingLink"] ?><br><br>
                                <?php echo $ad["landingPage"] ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    <table>
                </div>
            </div>

            <?php require_once(__DIR__ . '/../include/footer.php'); ?>
        </div>
    </div>
</body>
</html>