<?php
session_start();

require "connect.php";

$correct = $_POST['radio'];
$content = $_POST['content'];

$quetions = $connection->prepare("INSERT INTO `quetions` (`quetionContent`, `lectureId`) VALUES (?, ?)");
$quetions->bind_param('si', $content, $_GET['lect']);
$quetions->execute();

$quetionId = $connection->insert_id;

$options = $connection->prepare("INSERT INTO `options` (`optionContent`, `quetionId`, `correctness`) VALUES (?, ?, ?)");

for ($i = 1; $i < 5; $i++) {
    $isCorrect = ($i == $correct) ? 1 : 0;
    if (!empty($_POST['option-' . $i])) {
        $options->bind_param('sii', $_POST['option-' . $i], $quetionId, $isCorrect);
        $options->execute();
    }
}

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
