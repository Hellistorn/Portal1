<?php
require 'connect.php';

// Проверка параметра
if (!isset($_GET['dl']) || !is_numeric($_GET['dl']) || intval($_GET['dl']) <= 0) {
    header('Location: index.php');
    exit;
}

$dl = intval($_GET['dl']);

// Удаляем все варианты ответов, связанные с вопросами этой лекции
$stmt = $connection->prepare("SELECT id FROM quetions WHERE lectureId = ?");
$stmt->bind_param('i', $dl);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }

    $idList = implode(',', $ids);
    $connection->query("DELETE FROM options WHERE quetionId IN ($idList)");
}

// Удаляем вопросы
$stmt = $connection->prepare("DELETE FROM quetions WHERE lectureId = ?");
$stmt->bind_param('i', $dl);
$stmt->execute();

// Удаляем саму лекцию
$stmt = $connection->prepare("DELETE FROM lecture WHERE id = ?");
$stmt->bind_param('i', $dl);
$stmt->execute();

// Теперь редирект
if (isset($_GET['lect']) && $_GET['lect'] != $dl) {
    $lect = intval($_GET['lect']);
    $pg = (isset($_GET['pg']) && is_numeric($_GET['pg']) && $_GET['pg'] > 0) ? intval($_GET['pg']) : 1;
    header("Location: index.php?lect=$lect&pg=$pg");
} else {
    header("Location: index.php");
}
exit;
