<?php
session_start();

require "connect.php";

$q = "SELECT * FROM quetions WHERE id=" . $_GET['q'];
$resQ = $connection->query($q);
$qCount = $resQ->num_rows;
if ($qCount > 0) {
    while ($qRow = mysqli_fetch_array($resQ)) {
        $o = "DELETE FROM options WHERE quetionId =" . $qRow['id'];
        $connection->query($o);
    }
}

$quetions = "DELETE FROM quetions WHERE id =" . $_GET['q'];
$connection->query($quetions);

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
