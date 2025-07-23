<?php
$mysqli = new mysqli("172.20.10.4", "school", "StrongPass123", "quizmasterr");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>
