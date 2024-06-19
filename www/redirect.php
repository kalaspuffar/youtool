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

    var_dump($tokenData);

    $options = array(
        'http' => array(
            'method'  => "GET",
            'header' => "Content-Type: application/json\r\n" .
                        "Content-Length: 0\r\n" .
                        "Authorization: Bearer " . $tokenData->access_token . "\r\n" .
                        "User-Agent: YouTool/0.1\r\n"            
        ),
    );
    $context = stream_context_create($options);
    $channelResult = file_get_contents('https://www.googleapis.com/youtube/v3/channels?mine=true', false, $context);

    updateQuotaCost($YOUTUBE_API_QUOTA_LIST_COST);

    if ($channelResult === false) {
        die('FAILED TO LOGIN!');
    }
    
    $channelData = json_decode($channelResult);
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
        $query = 'INSERT INTO users (access_token, refresh_token, expire_time, write_access, auth_key, channel_id) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?, ?)';
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
        $stmt->bind_param("ss", 
            $authKey,
            $channelId
        );
    }

    $stmt->execute();
}
header("Location: https://youtool.app");
