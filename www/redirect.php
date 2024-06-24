<?php
require_once(__DIR__ . '/../include/functions.php');

if (
    $_REQUEST['scope'] == 'https://www.googleapis.com/auth/youtube.readonly' ||
    $_REQUEST['scope'] == 'https://www.googleapis.com/auth/youtube.force-ssl'
) {
    $keyStr = file_get_contents($YOUTUBE_API_JSON);
    $keyData = json_decode($keyStr);

    $data = [
        'client_id' => $keyData->web->client_id,
        'client_secret' => $keyData->web->client_secret,
        'redirect_uri' => $keyData->web->redirect_uris[0],
        'grant_type' => 'authorization_code',
        'code' => $_REQUEST['code']
    ];

    $query = http_build_query($data);
    $options = array(
        'http' => array(
            'method'  => "POST",
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($query)."\r\n".
                        "User-Agent:YouTool/0.1\r\n",
            'content' => $query,
        ),
    );
    $context = stream_context_create($options);
    $tokenResult = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
    if ($tokenResult === false) {
        die('FAILED TO LOGIN!');
    }    
    $tokenData = json_decode($tokenResult);
    if (!isset($tokenData->access_token)) {
        die('FAILED TO LOGIN!');
    }

    $user = [
        'access_token' => $tokenData->access_token
    ];

    $channelResult = callYoutubeAPI(
        $user,
        'https://www.googleapis.com/youtube/v3/channels?mine=true',
        'GET',
        '',
        $YOUTUBE_API_QUOTA_LIST_COST
    );

    if ($channelResult === false) {
        die('FAILED TO LOGIN!');
    }
    
    $channelData = json_decode($channelResult);
    if (!isset($channelData->items)) {
        require_once(__DIR__ . '/../include/head_optional.php');
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Block editor</title>
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

            <div class="row">
                <h5 style="color:red">Error: No Youtube channel specified, all accounts are connected to a channel so without a channel you will can't continue.</h5>
                <a class="button primary" href="https://youtool.app/payments.php">Return</a>
            </div>

            <?php require_once(__DIR__ . '/../include/footer.php'); ?>
        </div>
    </div>
<body>
<html>
        <?php
        exit;
    }
    $channelId = $channelData->items[0]->id;

    $authKey = hash("sha256", random_bytes(2000));
    setcookie("auth_key", $authKey, time()+3600);

    $writeAccess = $_REQUEST['scope'] == 'https://www.googleapis.com/auth/youtube.force-ssl' ? 1 : 0;

    $stmt = $mysqli->prepare('SELECT * FROM users WHERE channel_id = ?');
    $stmt->bind_param("s", $channelId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {        
        $query = 'UPDATE users SET auth_key = ? WHERE channel_id = ?';
        if (isset($tokenData->refresh_token)) {
            $query = 'UPDATE users SET access_token = ?, refresh_token = ?, expire_time = DATE_ADD(NOW(), INTERVAL ? SECOND), write_access = ?, auth_key = ? WHERE channel_id = ?';
        }
    } else {
        $query = 'INSERT INTO users (access_token, expire_time, write_access, auth_key, channel_id) VALUES (?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?, ?)';
        if (isset($tokenData->refresh_token)) {
            $query = 'INSERT INTO users (access_token, refresh_token, expire_time, write_access, auth_key, channel_id) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?, ?)';
        }
    }
    $stmt = $mysqli->prepare($query);
    if (isset($tokenData->refresh_token)) {
        $stmt->bind_param("ssiiss", 
            $tokenData->access_token,
            $tokenData->refresh_token,
            $tokenData->expires_in,
            $writeAccess,
            $authKey,
            $channelId
        );
    } else {
        if ($result->num_rows > 0) {
            $stmt->bind_param("ss",
                $authKey,
                $channelId
            );
        } else {
            $stmt->bind_param("siiss",
                $tokenData->access_token,
                $tokenData->expires_in,
                $writeAccess,
                $authKey,
                $channelId
            );
        }
    }

    $stmt->execute();
}
header("Location: https://youtool.app/payments.php");
