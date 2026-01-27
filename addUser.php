<?php
session_start();

require "connect.php";

$stmt = $connection->prepare("INSERT INTO `users` (`name`, `status`, `surname`, `password`, `group_name`, `createrAdmin`) VALUES(?,?,?,?,?,?)");
$stmt->bind_param('sssssi', $_POST['name'], $_POST['status'], $_POST['surname'], $_POST['password'], $_POST['group'], $_SESSION['id']);
$stmt->execute();

if (
    !isset($_GET['group']) ||
    empty($_GET['group'])
) {
    header('Location: index.php');
    exit;
}

header('Location: index.php?group=' . urlencode($_GET['group']));
exit;


$connection->close();
