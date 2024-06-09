<?php
require_once(__DIR__ . '/../include/functions.php');

if (!isset($_COOKIE['auth_key'])) {
    header("Location: https://youtool.app");
}
$stmt = $mysqli->prepare("SELECT * FROM users WHERE auth_key = ?");
$stmt->bind_param("s", $_COOKIE['auth_key']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: https://youtool.app");
}
$user = $result->fetch_assoc();
$user = getUserAccess($user['id']);