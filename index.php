<?php
session_start();

if (!(isset($_SESSION['logged-in']))) {
  header('Location: login.php');
  exit();
}

require_once "connect.php";

if ($connection->connect_errno != 0) {
  echo "Ошибка: " . $connection->connect_errno . "<br>";
  echo "Описание: " . $connection->connect_error;
  exit();
}

$name = $_SESSION['name'];
$surname = $_SESSION['surname'];
$userId = $_SESSION['id'];

$sql = "SELECT * FROM users WHERE id='$userId'";

$result = $connection->query($sql);
$row = $result->fetch_assoc();
$status = $row['status'];
$group_name = $row['group_name']; // Текстовое имя группы из профиля студента

$groups = "SELECT * FROM users_group";
$groupsRes = $connection->query($groups);

$groupsOptions = '';

while ($grp = $groupsRes->fetch_assoc()) {
    $groupsOptions .= "<option value='{$grp['id']}'>{$grp['name']}</option>";
}
?>
<!doctype html>
<html lang="ru">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Учебный портал — Лекции и Тесты</title>
  <link rel="stylesheet" href="css/style.css" />
</head>

<body>
  <div id="app">
    <aside id="sidebar">
      <div class="sidebar-header">
        <h2>Лекции</h2>


        <div class="admin-controls" id="adminControls">
          <?php
          if ($status == 'admin') {
            echo ('<button id="btnAddLecture" class="btn primary">+</button>
              <a id="btnLogoutAdmin" class="btn danger" href="logout.php">Выйти</a>');
          } else {
            echo ('<a id="btnLogoutAdmin" class="btn danger" href="logout.php">Выйти</a>');
          }

          ?>

        </div>
      </div>
            <form method="get" class="lecture-search">
        <input
          type="text"
          name="search"
          placeholder="Поиск лекции..."
          value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
        >
        <?php if (!empty($_GET['group'])): ?>
          <input type="hidden" name="group" value="<?= htmlspecialchars($_GET['group']) ?>">
        <?php endif; ?>
      </form>
      <div id="lecturesList" class="lectures-list">
        <?php
          $search = '';
          if (!empty($_GET['search'])) {
            $search = $connection->real_escape_string($_GET['search']);
          }

          $searchSql = '';
          if (!empty($search)) {
            $searchSql = " AND nameLecture LIKE '%$search%'";
          }

        if ($status == 'admin' && empty($_GET['group'])) {
          $lectures = "SELECT * FROM lecture WHERE adminId='$userId' $searchSql";
        } elseif ($status == 'admin' && !empty($_GET['group'])) {
          $selectedGroup = $_GET['group'];
          $lectures = "SELECT * FROM lecture 
             WHERE forGroup='$selectedGroup' 
             AND adminId='$userId' 
             $searchSql";
        } else {
          // --- КОНВЕРТАЦИЯ ИМЕНИ ГРУППЫ В ID ДЛЯ СТУДЕНТА ---
          $group_id = 0;
          if (!empty($group_name)) {
            $clean_group_name = $connection->real_escape_string($group_name);
            $getGroupIdQuery = "SELECT id FROM users_group WHERE name = '$clean_group_name'";
            $groupIdResult = $connection->query($getGroupIdQuery);
            if ($groupIdResult && $groupIdResult->num_rows > 0) {
                $groupRow = $groupIdResult->fetch_assoc();
                $group_id = $groupRow['id']; // Получили числовой ID группы (например, 7)
            }
          }
          $lectures = "SELECT * FROM lecture WHERE forGroup='$group_id' $searchSql";
        }

        if ($status == 'admin' && !empty($_GET['group'])) {
          if ($lecturesResult = $connection->query($lectures)) {
            $lecturesCount = $lecturesResult->num_rows;
            if ($lecturesCount > 0) {
              while ($lecturesRow = mysqli_fetch_array($lecturesResult)) {
                $q = "SELECT * FROM quetions WHERE lectureId='" . $lecturesRow['id'] . "'";
                $res = $connection->query($q);
                $qCount = $res->num_rows;
                echo ('<div class="lecture-item">  
                          <a href="index.php?group=' . $_GET['group'] . '&gl=' . $lecturesRow['id'] . '" style="width:100%;">
                            <div>' . $lecturesRow['nameLecture'] . '
                              <div class="small">' . $qCount . ' вопрос(ов)</div>
                            </div>
                          </a>
                        </div>');
              }
            }
          }
        } else {
          if ($lecturesResult = $connection->query($lectures)) {
            $lecturesCount = $lecturesResult->num_rows;
            if ($lecturesCount > 0) {
              $il = 1;
              echo ('<script>let lecture = []; let deleteBtnCount = 0;</script>');
              while ($lecturesRow = mysqli_fetch_array($lecturesResult)) {
                $q = "SELECT * FROM quetions WHERE lectureId='" . $lecturesRow['id'] . "'";
                $res = $connection->query($q);
                $qCount = $res->num_rows;
                $pageLecture = (!empty($_GET['lect'])) ? '&lect=' . $_GET['lect'] . '&' : '';
                $n = (!empty($_GET['pg'])) ? 'pg=' . $_GET['pg'] : '';
                if ($status == 'admin') {
                  echo ('<div class="lecture-item">  
                          <a href="index.php?lect=' . $lecturesRow['id'] . '&pg=1" class="lecture-item-link">
                            <div>' . $lecturesRow['nameLecture'] . '
                            <div class="small">' . $qCount . ' вопрос(ов)</div>
                          </div>
                          </a>
                            <div class="lecture-controls">
                              <button class="icon-btn" id="updateLecture-' . $il . '">✎</button>
                              <a class="icon-btn" href="deleteLecture.php?dl=' . $lecturesRow['id'] . $pageLecture . $n . '">🗑</a>
                            </div>
                          </div>');
                  echo ('<script>lecture.push({id: ' . intval($il) . ', idLecture: ' . intval($lecturesRow['id']) . '}); deleteBtnCount++;</script>');
                } else {
                  echo ('<div class="lecture-item"><a href="index.php?lect=' . $lecturesRow['id'] . '&pg=1"><div>' . $lecturesRow['nameLecture'] . '<div class="small">' . $qCount . ' вопрос(ов)</div></div></a></div>');
                }
                $il++;
              }
            } else {
              echo ('Здесь пока нет лекций');
            }
          } else {
            echo ('conn error');
          }
        }
        ?>

      </div>
    </aside>

    <div class="overlay"></div>

    <main id="main">
      <header class="main-header">
        <div>
          <button id="showBar" class="btn primary">☰</button>
          <h1 id="lectureTitle">
            <?php
            if (!empty($_GET['pg']) && $_GET['pg'] == 1) {
              echo ('Лекция:');
            } elseif (!empty($_GET['pg']) && $_GET['pg'] > 1) {
              echo ('Вопрос:');
            } elseif (!empty($_GET['pg']) && $_GET['pg'] == 'result') {
              echo ('Результат:');
            } elseif (!empty($_GET['group'])) {
              echo ('Группа:');
            } else {
              echo ('Главная');
            }
            ?></h1>
        </div>

        <div class="meta">
          <?php
          if ($status == 'admin' && !empty($_GET['lect']) && empty($_GET['group'])) {
            echo ('<span id="lectureMeta"></span>
            <div id="lectureActions" class="lecture-actions"><button class="btn" id="addQuetion">Добавить вопрос</button>'); ?>
            <ul class="menu">
              <li class="menu-item">Группы
                <ul class="submenu">
                  <?php
                  $allGroup = "SELECT * FROM users_group";
                  $agRes = $connection->query($allGroup);
                  $agCount = $agRes->num_rows;
                  if ($agCount > 0) {
                    while ($agRow = mysqli_fetch_array($agRes)) {
                      echo ('<li><a href="index.php?group=' . $agRow['name'] . '">' . $agRow['name'] . '</a></li>');
                    }
                    echo ('<li><button id="addGroupBtn" class="btn primary">Добавить</button></li>');
                  } else {
                    echo ('<li><button id="addGroupBtn" class="btn primary">Добавить</button></li>');
                  }
                  ?>
                </ul>
              </li>
            </ul>
          <?php
            echo ('</div>');
          } elseif ($status == 'admin' && empty($_GET['lect']) && empty($_GET['group'])) {
          ?>
            <ul class="menu">
              <li class="menu-item">Группы
                <ul class="submenu">
                  <?php
                  $allGroup = "SELECT * FROM users_group";
                  $agRes = $connection->query($allGroup);
                  $agCount = $agRes->num_rows;
                  if ($agCount > 0) {
                    while ($agRow = mysqli_fetch_array($agRes)) {
                      echo ('<li><a href="index.php?group=' . $agRow['name'] . '">' . $agRow['name'] . '</a></li>');
                    }
                    echo ('<li><button id="addGroupBtn" class="btn primary">Добавить</button></li>');
                  } else {
                    echo ('<li><button id="addGroupBtn" class="btn primary">Добавить</button></li>');
                  }
                  ?>
                </ul>
              </li>
            </ul>
          <?php
          } elseif ($status == 'admin' && !empty($_GET['group'])) {
            echo ('<span id="lectureMeta"></span>
            <div id="lectureActions" class="lecture-actions"><a id="homeBtn" href="index.php" class="btn">Главная</a>');
            if (!empty($_GET['gl'])) {
              echo '<a id="addUser" href="index.php?group=' . $_GET['group'] . '" class="btn">Добавить пользователя</a></a>';
            }
          ?>
            <ul class="menu">
              <li class="menu-item">Группы
                <ul class="submenu">
                  <?php
                  $allGroup = "SELECT * FROM users_group";
                  $agRes = $connection->query($allGroup);
                  $agCount = $agRes->num_rows;
                  if ($agCount > 0) {
                    while ($agRow = mysqli_fetch_array($agRes)) {
                      echo ('<li><a href="index.php?group=' . $agRow['name'] . '">' . $agRow['name'] . '</a></li>');
                    }
                    echo ('<li><button id="addGroupBtn" class="btn primary">Добавить</button></li>');
                  } else {
                    echo ('<li><button id="addGroupBtn" class="btn primary">Добавить</button></li>');
                  }
                  ?>
                </ul>
              </li>
            </ul>
          <?php
            echo ('</div>');
          }
          ?>


        </div>        
        <div class="header-logo">
          <a href="index.php">
            <img src="images/VTK.jpeg" alt="Логотип" />
          </a>
        </div>
              <div class="user-profile">
              <img src="images/user-icon.png" alt="Профиль" id="profileIcon">
              <div class="profile-menu hidden">
                <p><?php echo $name . ' ' . $surname; ?></p>
                <a class="btn" href="profile.php">Мой профиль</a>
                <a href="logout.php">Выйти</a>
              </div>
            </div>
      </header>

      <section id="lectureContent" class="lecture-content">

        <?php
        if (!empty($_GET['lect'])) {
          $selectedLectureId = $_GET['lect'];
          $selectedLecture = "SELECT * FROM lecture WHERE id='$selectedLectureId'";
          $lectureResult = $connection->query($selectedLecture);
          $lectureRow = $lectureResult->fetch_assoc();

          $tasks = "SELECT * FROM quetions WHERE lectureId='$selectedLectureId'";
          $quetionResult = $connection->query($tasks);
          $quetionCount = $quetionResult->num_rows;

          $pageContent[0] = $lectureRow['lectureContent'];
          while ($quetionRow = mysqli_fetch_array($quetionResult)) {
            $pageContent[] = [
              'id' => $quetionRow['id'],
              'content' => $quetionRow['quetionContent']
            ];
          }

          if (!empty($_GET['pg'])) {
            if ($_GET['pg'] == 1) {
              echo ('<div id="lectureText" class="lecture-text"><p>' . $lectureRow['lectureContent'] . '</p></div>');
              echo ('<div class="navigation">
                    <a id="btnPrev" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  - 1 . '" style="visibility: hidden;">Предыдущая</a>');
              if ($quetionCount > 0) {
                echo ('<a id="btnNext" class="btn primary" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  + 1 . '" style="position:fixed;padding:10px;font-size:15px;right:50px;">Следующая</a>
                      </div>');
              } else {
                echo ('</div');
              }
            } elseif ($_GET['pg'] == count($pageContent)) {
              if ($status == 'admin') {
                echo ('<div id="lectureText" class="lecture-text"><span>' . $pageContent[$_GET['pg'] - 1]['content'] . '</span><a href="deleteQuetion.php?lect=' . $_GET['lect'] . '&pg=' . $_GET['pg'] . '&q=' . $pageContent[$_GET['pg'] - 1]['id'] . '" class="btn primary">удалить вопрос</a></div>');
              } else {
                echo ('<div id="lectureText" class="lecture-text">' . $pageContent[$_GET['pg'] - 1]['content'] . '</div>');
              }


              $options = "SELECT * FROM options WHERE quetionId='" . $pageContent[$_GET['pg'] - 1]['id'] . "'";
              $optResult = $connection->query($options);
              $optCount = $optResult->num_rows;

              $answers = "SELECT * FROM answers WHERE userId='" . $_SESSION['id'] . "' AND quetionId='" . $pageContent[$_GET['pg'] - 1]['id'] . "'";
              $answersResult = $connection->query($answers);
              $answersCount = $answersResult->num_rows;
              $answersRow = $answersResult->fetch_assoc();

              if ($answersCount > 0) {
                echo ('<form id="check" method="post" action="test.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg'] . '&q=' . $pageContent[$_GET['pg'] - 1]['id'] . '">');
                while ($optRow = mysqli_fetch_array($optResult)) {
                  if ($status == 'admin') {
                    echo ('<label><input type="radio" class="quetion" name="q" value="' . $optRow['correctness'] . '" required>' . $optRow['optionContent'] . '<a class="btn primary" href="deleteOption.php?lect=' . $_GET['lect'] . '&pg=' . $_GET['pg'] . '&opt=' . $optRow['id'] . '" style="position:relative;float:right;height:20px;padding:0px 5px;">🗑</a></label>');
                  } else {
                    echo ('<label><input type="radio" class="quetion" name="q" value="' . $optRow['correctness'] . '" required>' . $optRow['optionContent'] . "</label>");
                  }
                }

                if ($status == 'admin') {
                  echo ('<button id="addOptions" data-id="' . $pageContent[$_GET['pg'] - 1]['id']  . '" class="btn primary" type="button" style="margin: 10px 0;">Добавить варианты</button>');
                }

                if ($answersRow['correct'] == 1) {
                  echo ('<h4 class="correct">Вы ответили верно на этот вопросы<span>✔️</span></h4></form>');
                } else {
                  echo ('<h4 class="wrong">Вы ответили неверно на этот вопрос<span>❌</span></h4></form>');
                }

                $show = "SELECT * FROM answers WHERE lectureId =" . $_GET['lect'] . " AND userId = $userId";
                $shRes = $connection->query($show);
                $shCount = $shRes->num_rows;

                if ((count($pageContent) - 1) == intval($shCount)) {
                  echo ('<div class="navigation">
                    <a id="btnPrev" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  - 1 . '">Предыдущая</a>
                    <a id="toLect" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=1">К лекции</a>
                    <a id="showTotal" class="btn primary" href="index.php?lect=' . $selectedLectureId . '&pg=result">Показать результат</a>
                    </div>');
                } else {
                  echo ('<div class="navigation">
                    <a id="btnPrev" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  - 1 . '">Предыдущая</a>
                    <a id="toLect" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=1">К лекции</a>
                    </div>');
                  echo ((count($pageContent) - 1) . intval($shCount));
                }
              } else {
                echo ('<form id="check" method="post" action="test.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg'] . '&q=' . $pageContent[$_GET['pg'] - 1]['id'] . '">');
                while ($optRow = mysqli_fetch_array($optResult)) {
                  if ($status == 'admin') {
                    echo ('<label><input type="radio" class="quetion" name="q" value="' . $optRow['correctness'] . '" required>' . $optRow['optionContent'] . '<a class="btn primary" href="deleteOption.php?lect=' . $_GET['lect'] . '&pg=' . $_GET['pg'] . '&opt=' . $optRow['id'] . '" style="position:relative;float:right;height:20px;padding:0px 5px;">🗑</a></label>');
                  } else {
                    echo ('<label><input type="radio" class="quetion" name="q" value="' . $optRow['correctness'] . '" required>' . $optRow['optionContent'] . "</label>");
                  }
                }
                if ($status == 'admin') {
                  echo ('<button id="addOptions" data-id="' . $pageContent[$_GET['pg'] - 1]['id']  . '" class="btn primary" type="button" style="margin: 10px 0;">Добавить варианты</button>');
                }

                if ($optCount > 0) {
                  echo ('<button type="submit" class="btn primary" id="submitBtn">Проверить</button></form>');
                } else {
                  echo ('<h4>Нет вариантов ответа</h4></form>');
                }

                $show = "SELECT * FROM answers WHERE lectureId =" . $_GET['lect'] . " AND userId = $userId";
                $shRes = $connection->query($show);
                $shCount = $shRes->num_rows;

                if ((count($pageContent) - 1) == intval($shCount)) {
                  echo ('<div class="navigation">
                    <a id="btnPrev" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  - 1 . '">Предыдущая</a>
                    <a id="toLect" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=1">К лекции</a>
                    <a id="showTotal" class="btn primary" href="index.php?lect=' . $selectedLectureId . '&pg=result">Показать результат</a>
                    </div>');
                } else {
                  echo ('<div class="navigation" style="justify-content:left;">
                    <a id="btnPrev" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  - 1 . '">Предыдущая</a>
                    <a id="toLect" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=1">К лекции</a>
                    </div>');
                }
              }
            } elseif ($_GET['pg'] == 'result') {
              $allQ = count($pageContent) - 1;

              $sqlA = "SELECT * FROM answers WHERE userId = $userId AND lectureId =" . $_GET['lect'];
              $rA = $connection->query($sqlA);
              $aA = $rA->num_rows;

              $sqlQ = "SELECT * FROM quetions WHERE lectureId =" . $_GET['lect'];
              $rQ = $connection->query($sqlQ);
              $aQ = $rQ->num_rows;

              $sql1 = "SELECT * FROM answers WHERE userId = '$userId' AND correct = '1' AND lectureId = '" . $_GET['lect'] . "'";
              $r1 = $connection->query($sql1);
              $correctA = $r1->num_rows;

              $t = "SELECT * FROM total WHERE userId = $userId AND lectureId =" . $_GET['lect'];
              $resT = $connection->query($t);
              $tCount = $resT->num_rows;

              if ($tCount < 1) {
                if ($aQ == $aA) {
                  $now = date("Y-m-d H:i:s");
                  $mark = intdiv(($correctA / $allQ * 100), 1);
                  $total = $connection->prepare("INSERT INTO `total` (`userId`, `mark`, `datatime`, `lectureId`) VALUES(?, ?, ?, ?)");
                  $total->bind_param('iisi', $userId, $mark, $now, $_GET['lect']);
                  $total->execute();
                }
              }

              echo ('<div class="total">
                      <h2>Ваши результаты:</h2>
                      <h1>' . intdiv(($correctA / $allQ * 100), 1)  . '</h1>
                      <h3>' . $correctA . ' из ' . $allQ . ' верных ответов</h3>
                      <div>
                        <a id="btnPrev" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=' .  count($pageContent) . '">Предыдущая</a>
                        <a class="btn primary" href="index.php">На главную</a>
                      </div>
                    </div>
                  ');
            } else {
              if ($status == 'admin') {
                echo ('<div id="lectureText" class="lecture-text"><span>' . $pageContent[$_GET['pg'] - 1]['content'] . '</span><a href="deleteQuetion.php?lect=' . $_GET['lect'] . '&pg=' . $_GET['pg'] . '&q=' . $pageContent[$_GET['pg'] - 1]['id'] . '" class="btn primary">удалить вопрос</a></div>');
              } else {
                echo ('<div id="lectureText" class="lecture-text">' . $pageContent[$_GET['pg'] - 1]['content'] . '</div>');
              }

              $options = "SELECT * FROM options WHERE quetionId='" . $pageContent[$_GET['pg'] - 1]['id'] . "'";
              $optResult = $connection->query($options);
              $optCount = $optResult->num_rows;

              $answers = "SELECT * FROM answers WHERE userId='" . $_SESSION['id'] . "' AND quetionId='" . $pageContent[$_GET['pg'] - 1]['id'] . "'";
              $answersResult = $connection->query($answers);
              $answersCount = $answersResult->num_rows;
              $answersRow = $answersResult->fetch_assoc();

              if ($answersCount > 0) {
                echo ('<form id="check" method="post" action="test.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg'] . '&q=' . $pageContent[$_GET['pg'] - 1]['id'] . '">');
                while ($optRow = mysqli_fetch_array($optResult)) {
                  if ($status == 'admin') {
                    echo ('<label><input type="radio" class="quetion" name="q" value="' . $optRow['correctness'] . '" required>' . $optRow['optionContent'] . '<a class="btn primary" href="deleteOption.php?lect=' . $_GET['lect'] . '&pg=' . $_GET['pg'] . '&opt=' . $optRow['id'] . '" style="position:relative;float:right;height:20px;padding:0px 5px;">🗑</a></label>');
                  } else {
                    echo ('<label><input type="radio" class="quetion" name="q" value="' . $optRow['correctness'] . '" required>' . $optRow['optionContent'] . "</label>");
                  }
                }
                if ($status == 'admin') {
                  echo ('<button id="addOptions" data-id="' . $pageContent[$_GET['pg'] - 1]['id']  . '" class="btn primary" type="button" style="margin: 10px 0;">Добавить варианты</button>');
                }

                if ($answersRow['correct'] == 1) {
                  echo ('<h4 class="correct">Вы ответили верно на этот вопрос<span>✔️</span></h4></form>');
                } else {
                  echo ('<h4 class="wrong">Вы ответили неверно на этот вопрос<span>❌</span></h4></form>');
                }

                echo ('<div class="navigation">
                  <a id="btnPrev" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  - 1 . '">Предыдущая</a>
                  <a id="toLect" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=1">К лекции</a>
                  <a id="btnNext" class="btn primary" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  + 1 . '">Следующая</a>
                  </div>');
              } else {
                echo ('<form id="check" method="post" action="test.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg'] . '&q=' . $pageContent[$_GET['pg'] - 1]['id'] . '">');
                while ($optRow = mysqli_fetch_array($optResult)) {
                  if ($status == 'admin') {
                    echo ('<label><input type="radio" class="quetion" name="q" value="' . $optRow['correctness'] . '" required>' . $optRow['optionContent'] . '<a class="btn primary" href="deleteOption.php?lect=' . $_GET['lect'] . '&pg=' . $_GET['pg'] . '&opt=' . $optRow['id'] . '" style="position:relative;float:right;height:20px;padding:0px 5px;">🗑</a></label>');
                  } else {
                    echo ('<label><input type="radio" class="quetion" name="q" value="' . $optRow['correctness'] . '" required>' . $optRow['optionContent'] . "</label>");
                  }
                }
                if ($status == 'admin') {
                  echo ('<button id="addOptions" data-id="' . $pageContent[$_GET['pg'] - 1]['id']  . '" class="btn primary" type="button" style="margin: 10px 0;">Добавить варианты</button>');
                }
                if ($optCount > 0) {
                  echo ('<button type="submit" class="btn primary" id="submitBtn">Проверить</button></form>');
                } else {
                  echo ('<h4>Нет вариантов ответа</h4></form>');
                }

                echo ('<div class="navigation">
                  <a id="btnPrev" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  - 1 . '">Предыдущая</a>
                  <a id="toLect" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=1">К лекции</a>
                  <a id="btnNext" class="btn primary" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  + 1 . '">Следующая</a>
                  </div>');
              }
            }
            if ($_GET['pg'] != 'result' && $_GET['pg'] > count($pageContent)) {
              header('Location: index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg'] - 1);
            }
          } else {
            echo ('<div id="lectureText" class="lecture-text">' . $lectureRow['lectureContent'] . '</div>');
            echo ('<div class="navigation">
                    <a id="btnPrev" class="btn"href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  - 1 . '">Предыдущая</a>
                    <a id="toLect" class="btn" href="index.php?lect=' . $selectedLectureId . '&pg=1">К лекции</a>
                    <a id="btnNext" class="btn primary" href="index.php?lect=' . $selectedLectureId . '&pg=' . $_GET['pg']  + 1 . '">Следующая</a>
                  </div>');
          }
        } elseif (!empty($_GET['group'])) {
          $selectedGroup = $connection->real_escape_string($_GET['group']);
          if (!empty($_GET['gl'])) {
            echo ('<table aria-label="Таблица оценок студентов">
                <thead>
                  <tr>
                    <th>Студент</th>
                    <th>Время</th>
                    <th>Балл</th>
                  </tr>
                </thead>
                <tbody>');
            $users = "SELECT * FROM users WHERE group_name='$selectedGroup' AND createrAdmin = $userId";
            $usersRes = $connection->query($users);
            $usersCount = $usersRes->num_rows;
            if ($usersCount > 0) {
              while ($userRow = mysqli_fetch_array($usersRes)) {
                $total = "SELECT * FROM total WHERE userId=" . $userRow['id'] . " AND lectureId=" . $_GET['gl'];
                $totalRes = $connection->query($total);
                $totalRow = $totalRes->fetch_assoc();
                if (isset($totalRow['datatime']) && isset($totalRow['mark'])) {
                  echo '<tr>
                          <td>' . $userRow['surname'] . ' ' . $userRow['name'] . '</td>
                          <td class="time">' . $totalRow['datatime'] . '</td>
                          <td class="score">' . $totalRow['mark'] . '</td>
                        </tr>';
                } else {
                  echo '<tr>
                          <td>' . $userRow['surname'] . ' ' . $userRow['name'] . '</td>
                          <td class="time">-</td>
                          <td class="score">-</td>
                        </tr>';
                }
              }
            }
            echo ('</tbody>
              </table>');
          } else {
              echo ('<div id="lectureText" class="lecture-text">
                <form action="addUser.php?group=' . $_GET['group'] . '" method="POST">
                <label>Имя</label>
                <input type="text" name="name" placeholder="Введите имя" required>
                <label>Фамилия</label>
                <input type="text" name="surname" placeholder="Введите фамилия" required>
                <label>Пароль</label>
                <input type="text" name="password" placeholder="Введите пароль" required>
                <div>
                  <label><input type="radio" name="status" value="user" required checked>Студент</label>
                  <label><input id="adminRadio" type="radio" name="status" value="admin" required>Учитель</label>
                </div>
                <label for="groupInput">Группа</label>
                <select id="groupInput" name="group">'); 
                
                echo ('<option value="" disabled selected>Выберите группу</option>');
                  
                $groups = "SELECT * FROM users_group";
                $groupsRes = $connection->query($groups);
                while ($groupsRow = mysqli_fetch_array($groupsRes)) {
                    echo "<option value='" . $groupsRow['name'] . "'>" . $groupsRow['name'] . "</option>";
                }
              echo ('</select>
                  <button type="submit" class="btn primary">Создать пользователя</button>
                </form>
              </div>'); 
          }
        } else {
          echo ('<div id="lectureText" class="lecture-text">Выберите лекцию в панели лекций</div>');
        }
        ?>

      </section>
    </main>
  </div>

  <div id="modalOverlay" class="modal-overlay hidden">
    <form id="modalForm" method="post">
      <div class="modal">
        <h3 id="modalTitle"></h3>
        <div id="modalBody"></div>
        <div class="modal-actions">
          <button id="modalCancel" class="btn" type="button">Отмена</button>
          <button id="modalSave" class="btn primary">Сохранить</button>
        </div>
      </div>
    </form>
  </div>

  <script src="js/scripts.js"></script>
  <script>
    const groupsOptions = `<?= $groupsOptions ?>`;
  </script>
  
</body>

</html>
<?php $connection->close(); ?>