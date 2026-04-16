-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Апр 16 2026 г., 14:07
-- Версия сервера: 8.0.34-26-beget-1-1
-- Версия PHP: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `n9998335_magaz`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admindoor1_settings`
--
-- Создание: Мар 19 2026 г., 10:25
--

DROP TABLE IF EXISTS `admindoor1_settings`;
CREATE TABLE `admindoor1_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('text','number','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `admindoor1_settings`
--

INSERT INTO `admindoor1_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `group_name`, `created_at`) VALUES
(1, 'site_name', 'Двери Магазин', 'text', 'Название сайта', 'general', '2026-03-19 10:25:57'),
(2, 'items_per_page', '12', 'number', 'Товаров на странице', 'catalog', '2026-03-19 10:25:57'),
(3, 'currency', '₽', 'text', 'Валюта', 'catalog', '2026-03-19 10:25:57'),
(4, 'maintenance_mode', '0', 'boolean', 'Режим обслуживания', 'system', '2026-03-19 10:25:57'),
(5, 'backup_enabled', '1', 'boolean', 'Автоматическое резервирование', 'system', '2026-03-19 10:25:57'),
(6, 'backup_frequency', 'daily', 'text', 'Частота резервирования', 'system', '2026-03-19 10:25:57'),
(7, 'last_backup', '', 'text', 'Последнее резервирование', 'system', '2026-03-19 10:25:57'),
(8, 'smtp_host', '', 'text', 'SMTP сервер', 'email', '2026-03-19 10:25:57'),
(9, 'smtp_port', '587', 'number', 'SMTP порт', 'email', '2026-03-19 10:25:57'),
(10, 'smtp_user', '', 'text', 'SMTP пользователь', 'email', '2026-03-19 10:25:57'),
(11, 'smtp_pass', '', 'text', 'SMTP пароль', 'email', '2026-03-19 10:25:57'),
(12, 'admin_email', 'admin@example.com', 'text', 'Email администратора', 'email', '2026-03-19 10:25:57');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admindoor1_settings`
--
ALTER TABLE `admindoor1_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `group_name` (`group_name`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admindoor1_settings`
--
ALTER TABLE `admindoor1_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
