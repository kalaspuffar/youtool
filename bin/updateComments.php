<?php
require_once(__DIR__ . '/../include/functions.php');


$stmt = $mysqli->prepare('SELECT * FROM users');
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

foreach ($users as $user) {
    $json = file_get_contents(__DIR__ . '/../data/comments_' . $user['id'] . '.json');
    $jsonComments = json_decode($json);

    $fileComments = [];
    foreach ($jsonComments as $comment) {
        $times = [];
        array_push($times, strtotime($comment->snippet->topLevelComment->snippet->publishedAt));
        if (isset($comment->replies)) {
            foreach ($comment->replies->comments as $reply) {
                array_push($times, strtotime($reply->snippet->publishedAt));
            }    
        }
        $fileComments[$comment->id] = $times;
    }

    $stmt = $mysqli->prepare('SELECT commentId, UNIX_TIMESTAMP(publishedAt) as publishedAt FROM comment');
    $stmt->execute();  
    $result = $stmt->get_result();
    $myComments = $result->fetch_all(MYSQLI_ASSOC);

    $mysqlComments = [];
    foreach ($myComments as $comment) {    
        if (!isset($mysqlComments[$comment['commentId']])) {
            $mysqlComments[$comment['commentId']] = [];
        }
        array_push($mysqlComments[$comment['commentId']], $comment['publishedAt']);
    }

    $changed = [];
    foreach($fileComments as $id => $times) {
        if (!isset($mysqlComments[$id])) {
            array_push($changed, $id);
        } else if (count($times) >= count($mysqlComments[$id])) {
            sort($mysqlComments[$id]);
            sort($times);
            if ($mysqlComments[$id] !== $times) {
                array_push($changed, $id);
            }
        }
    }

    $fileComments = [];
    foreach ($jsonComments as $comment) {
        $times = [];
        array_push($times, strtotime($comment->snippet->topLevelComment->snippet->publishedAt));
        if (isset($comment->replies)) {
            foreach ($comment->replies->comments as $reply) {
                array_push($times, strtotime($reply->snippet->publishedAt));
            }    
        }
        $fileComments[$comment->id] = $times;
    }

    foreach ($jsonComments as $comment) {
        if (!in_array($comment->id, $changed)) continue;
        updateComment($user['id'], $comment);
    }
}