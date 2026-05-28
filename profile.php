<?php
session_start();
if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}
require_once "connect.php";

$userId = $_SESSION['id'];
$sql = "SELECT * FROM users WHERE id='$userId'";
$result = $connection->query($sql);
$user = $result->fetch_assoc();

// Получаем достижения студента
// $achievementsRes = $connection->query("SELECT a.name, a.description, a.icon 
//                                        FROM user_achievements ua
//                                        JOIN achievements a ON ua.achievementId = a.id
//                                        WHERE ua.userId = $userId");

// Получаем историю прохождения лекций
$historyRes = $connection->query("SELECT l.nameLecture, t.mark, t.datatime
                                  FROM total t
                                  JOIN lecture l ON t.lectureId = l.id
                                  WHERE t.userId = $userId
                                  ORDER BY t.datatime DESC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Профиль студента</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="profile-container">
    <aside class="profile-sidebar">
      <div class="profile-header">
        <div class="profile-avatar">
          <img src="images/user-icon.png" alt="Профиль">
  </div>
        <h2><?php echo $user['name'] . ' ' . $user['surname']; ?></h2>
        <p>Статус: <?php echo ucfirst($user['status']); ?></p>
      </div>

      <div class="profile-achievements">
        <div class="achievements">
          <!-- <?php // while ($ach = $achievementsRes->fetch_assoc()): ?>
            <div class="achievement">
              <img src="<?php // echo $ach['icon']; ?>" alt="<?php // echo $ach['name']; ?>">
              <p><?php // echo $ach['name']; ?></p>
            </div>
          <?php // endwhile; ?> -->
        </div>
      </div>
    </aside>

    <main class="profile-main">
      <h3>История прохождения лекций</h3>
      <table>
        <thead>
          <tr>
            <th>Лекция</th>
            <th>Балл</th>
            <th>Дата</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($h = $historyRes->fetch_assoc()): ?>
            <tr>
              <td><?php echo $h['nameLecture']; ?></td>
              <td><?php echo $h['mark']; ?></td>
              <td><?php echo $h['datatime']; ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <a href="index.php" class="btn">На главную</a>
    </main>
  </div>
</body>
</html>

