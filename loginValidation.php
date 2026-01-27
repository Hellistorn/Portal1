<?php
session_start();
require_once "connect.php";

if ($connection->connect_errno != 0) {
    echo "Ошибка: " . $connection->connect_errno . "<br>";
    echo "Описание: " . $connection->connect_error;
} else {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE name='$name' AND surname='$surname' AND password='$password'";

    if ($result = $connection->query($sql)) {
        $usersCount = $result->num_rows;
        if ($usersCount > 0) {
            $_SESSION['logged-in'] = true;
            $row = $result->fetch_assoc();
            $name = $row['name'];
            $surname = $row['surname'];
            $userId = $row['id'];

            $result->free_result();

            $_SESSION['name'] = $name;
            $_SESSION['surname'] = $surname;
            $_SESSION['id'] = $userId;
            unset($_SESSION['loginError']);
            header('Location: index.php');
        } else {
            $_SESSION['loginError'] = '<span class="error-msg">Неправильный ввод.</span>';
            header('Location: login.php');
        }
    }
    $connection->close();
}
