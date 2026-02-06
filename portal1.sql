-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Фев 06 2026 г., 07:52
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `portal1`
--

-- --------------------------------------------------------

--
-- Структура таблицы `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `answers`
--

CREATE TABLE `answers` (
  `id` int(255) NOT NULL,
  `userId` int(50) NOT NULL,
  `quetionId` int(50) NOT NULL,
  `correct` int(10) NOT NULL,
  `lectureId` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `answers`
--

INSERT INTO `answers` (`id`, `userId`, `quetionId`, `correct`, `lectureId`) VALUES
(28, 1, 66, 1, 38),
(29, 26, 66, 1, 38),
(30, 26, 67, 1, 39);

-- --------------------------------------------------------

--
-- Структура таблицы `lecture`
--

CREATE TABLE `lecture` (
  `id` int(20) NOT NULL,
  `nameLecture` text NOT NULL,
  `lectureContent` text NOT NULL,
  `forGroup` text NOT NULL,
  `adminId` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `lecture`
--

INSERT INTO `lecture` (`id`, `nameLecture`, `lectureContent`, `forGroup`, `adminId`) VALUES
(39, 'Учебная лекция|проверочная лекция', 'Тут будет содержание лекций', 'ТСТП-23', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `options`
--

CREATE TABLE `options` (
  `id` int(30) NOT NULL,
  `optionContent` text NOT NULL,
  `quetionId` int(30) NOT NULL,
  `correctness` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `options`
--

INSERT INTO `options` (`id`, `optionContent`, `quetionId`, `correctness`) VALUES
(220, 'ng b', 67, 0),
(221, 'zfgb ', 67, 0),
(222, 'bds znb', 67, 1),
(223, ' srht', 67, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `quetions`
--

CREATE TABLE `quetions` (
  `id` int(30) NOT NULL,
  `quetionContent` text NOT NULL,
  `lectureId` int(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `quetions`
--

INSERT INTO `quetions` (`id`, `quetionContent`, `lectureId`) VALUES
(67, 'fdcn', 39);

-- --------------------------------------------------------

--
-- Структура таблицы `total`
--

CREATE TABLE `total` (
  `id` int(30) NOT NULL,
  `userId` int(30) NOT NULL,
  `mark` int(10) NOT NULL,
  `datatime` datetime NOT NULL,
  `lectureId` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `total`
--

INSERT INTO `total` (`id`, `userId`, `mark`, `datatime`, `lectureId`) VALUES
(12, 26, 100, '2026-01-27 11:50:12', 38),
(13, 26, 100, '2026-02-04 13:14:57', 39);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(255) NOT NULL,
  `name` text NOT NULL,
  `status` text NOT NULL,
  `surname` text NOT NULL,
  `password` text NOT NULL,
  `group_name` text NOT NULL,
  `createrAdmin` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `status`, `surname`, `password`, `group_name`, `createrAdmin`) VALUES
(1, 'Мария', 'admin', 'Викторовна', '123', '', 0),
(25, 'Арыстан', 'user', 'Халитов', '123', 'ИТС-23', 1),
(26, 'Салам', 'user', 'Салам', '123', 'ТСТП-23', 1),
(27, 'Арыстан', 'user', 'Халитов', '123', 'ТСТП-23', 1),
(28, 'Раиль', 'user', 'Курмаев', '123', 'ТСТП-23', 1),
(29, 'Максим', 'user', 'Голубев', '123', 'ТСТП-23', 1),
(30, 'Никита', 'user', 'Диль', '123', 'ТСТП-23', 1),
(31, 'Кемаль', 'user', 'Балтабай', '123', 'ТСТП-23', 1),
(32, 'Артём', 'user', 'Шишканов', '123', 'ТСТП-23', 1),
(33, 'Ислам', 'user', 'Дюйсенов', '123', 'ТСТП-23', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `users_group`
--

CREATE TABLE `users_group` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `adminId` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `users_group`
--

INSERT INTO `users_group` (`id`, `name`, `adminId`) VALUES
(1, 'ТСТП-23', 1),
(6, 'ИТС-23', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `user_achievements`
--

CREATE TABLE `user_achievements` (
  `userId` int(11) NOT NULL,
  `achievementId` int(11) NOT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `lecture`
--
ALTER TABLE `lecture`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `quetions`
--
ALTER TABLE `quetions`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `total`
--
ALTER TABLE `total`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users_group`
--
ALTER TABLE `users_group`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`userId`,`achievementId`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT для таблицы `lecture`
--
ALTER TABLE `lecture`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT для таблицы `options`
--
ALTER TABLE `options`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT для таблицы `quetions`
--
ALTER TABLE `quetions`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT для таблицы `total`
--
ALTER TABLE `total`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT для таблицы `users_group`
--
ALTER TABLE `users_group`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
