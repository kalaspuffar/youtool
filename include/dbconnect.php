<?php
require_once(__DIR__ . '/../etc/config.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = mysqli_connect($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
if (!$mysqli) {
    echo 'Connection failed<br>';
    echo 'Error number: ' . mysqli_connect_errno() . '<br>';
    echo 'Error message: ' . mysqli_connect_error() . '<br>';
    die();
}

function fetchAssocAll($stmt, $key) {
    $returnRes = [];
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    foreach($data as $row) {
        $returnRes[$row[$key]] = $row;
    }
    return $returnRes;
}