<?php
session_start();

if (!(isset($_SESSION['logged-in']))) {
    header('Location: login.php');
    exit();
}

require 'connect.php';

$connection = new mysqli($host, $db_user, $db_password, $db_name);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin panel</title>
</head>

<body>

</body>

</html>