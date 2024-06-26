<?php
require_once(__DIR__ . '/../include/dbconnect.php');

$stmt = $mysqli->prepare(
    'UPDATE block SET active = false, changed = true WHERE (startTime > now() OR endTime < now()) AND active = true'
);
$stmt->execute();

$stmt = $mysqli->prepare(
    'UPDATE block SET active = true, changed = true WHERE ' . 
    ' (startTime is null OR startTime <= now()) AND ' .
    ' (endTime is null OR endTime >= now()) AND active = false'
);
$stmt->execute();

$stmt = $mysqli->prepare('SELECT * FROM block WHERE override_categories = true AND changed = true');
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $stmt = $mysqli->prepare('UPDATE video SET generated = false');
    $stmt->execute();    
} else {
    $stmt = $mysqli->prepare(
        'SELECT UNIQUE c.categoryId, b.id FROM block as b LEFT JOIN category_to_block AS c ON (b.id = c.blockId)'.
        ' WHERE changed = true'
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $categories = [];
    foreach ($data as $categoryRes) {
        if ($categoryRes['categoryId'] != NULL) {
            array_push($categories, $categoryRes['categoryId']);
        }        
    }

    if (count($categories) > 0) {
        $stmt = $mysqli->prepare(
            'UPDATE video SET generated = false' .
            ' WHERE id IN (SELECT videoId FROM category_to_video WHERE categoryId IN (' . implode(',', $categories) . '))'
        );    
        $stmt->execute();
    }
}

$stmt = $mysqli->prepare('UPDATE block SET changed = false');
$stmt->execute();