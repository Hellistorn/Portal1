<?php
session_start();

require "connect.php";

$stmt = $connection->prepare("INSERT INTO `lecture` (`nameLecture`, `lectureContent`, `forGroup`, `adminId`) VALUES(?, ?, ?, ?)");
$stmt->bind_param('sssi', $_POST['name'], $_POST['content'], $_POST['group'], $_SESSION['id']);
$stmt->execute();

if (
    !isset($_GET['lect']) ||
    !is_numeric($_GET['lect']) ||
    intval($_GET['lect']) <= 0
) {
    header('Location: index.php');
    exit;
}

if (
    !isset($_GET['pg']) ||
    !is_numeric($_GET['pg']) ||
    intval($_GET['pg']) <= 0
) {
    header('Location: index.php?lect=' . urlencode($_GET['lect']) . '&pg=1');
    exit;
}

header('Location: index.php?lect=' . urlencode($_GET['lect']) . '&pg=' . urlencode($_GET['pg']));
exit;



$connection->close();
