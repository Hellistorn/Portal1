<?php
$host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "portal1";

$connection = new mysqli($host, $db_user, $db_password, $db_name);

if (!$connection) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

mysqli_set_charset($connection, "utf8mb4");
