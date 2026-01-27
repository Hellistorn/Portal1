<?php
session_start();

require "connect.php";

$sql = "SELECT * FROM lecture WHERE id = " . $_GET['li'];
$res = $connection->query($sql);
$row = $res->fetch_assoc();

$oldname = $row['nameLecture'];
$oldcontent = $row['lectureContent'];
$oldgroup = $row['forGroup'];

$newname = !empty($_POST['name']) ? $_POST['name'] : $oldname;
$newcontent = !empty($_POST['content']) ? $_POST['content'] : $oldcontent;
$newgroup = !empty($_POST['group']) ? $_POST['group'] : $oldgroup;

$stmt = $connection->prepare("UPDATE lecture SET nameLecture = ?, lectureContent = ?, forGroup = ?, adminId = ? WHERE id = ?");
$stmt->bind_param('sssii', $newname, $newcontent, $newgroup, $_SESSION['id'], $_GET['li']);
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
