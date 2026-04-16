-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Апр 16 2026 г., 14:06
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
-- Структура таблицы `admindoor1_logs`
--
-- Создание: Мар 19 2026 г., 10:25
--

DROP TABLE IF EXISTS `admindoor1_logs`;
CREATE TABLE `admindoor1_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `admindoor1_logs`
--

INSERT INTO `admindoor1_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'logout', NULL, NULL, NULL, '212.248.79.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-19 11:16:23'),
(2, NULL, 'admin_created', NULL, NULL, 'Создан администратор: 123', '212.248.79.130', NULL, '2026-03-19 11:27:24'),
(3, 4, 'product_updated', NULL, NULL, 'Товар: Защелка 2071 (ID: 1684)', '212.248.79.130', NULL, '2026-03-19 11:46:42'),
(4, 4, 'product_updated', NULL, NULL, 'Товар: ЛУ-51 (Дуб натуральный, дг) (ID: 2625)', '212.248.79.130', NULL, '2026-03-19 11:49:52'),
(5, 4, 'product_updated', NULL, NULL, 'Товар: ЛУ-51 (Дуб натуральный, дг) (ID: 2625)', '212.248.79.130', NULL, '2026-03-19 11:49:52'),
(6, 4, 'product_created', NULL, NULL, 'Товар: ЛУ-511 (Дуб натуральный, дг) (ID: 2645)', '212.248.79.130', NULL, '2026-03-19 11:52:29'),
(7, 4, 'product_deleted', NULL, NULL, 'Удален товар: ЛУ-511 (Дуб натуральный, дг)', '212.248.79.130', NULL, '2026-03-19 11:52:56'),
(8, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260319-8776 на сумму 30 руб.', '212.248.79.130', NULL, '2026-03-19 12:26:12'),
(9, 4, 'order_updated', NULL, NULL, 'Заказ #1, новый статус: processing', '212.248.79.130', NULL, '2026-03-19 12:32:07'),
(10, 4, 'order_updated', NULL, NULL, 'Заказ #1, новый статус: processing', '212.248.79.130', NULL, '2026-03-19 12:32:21'),
(11, 4, 'product_updated', NULL, NULL, 'Товар: Atum 5 (ID: 1146)', '212.248.79.130', NULL, '2026-03-27 11:32:29'),
(12, 4, 'product_deleted', NULL, NULL, 'Удален товар: Плинтус Stockholm', '104.28.230.246', NULL, '2026-04-02 02:08:08'),
(13, 4, 'product_updated', NULL, NULL, 'Товар: Atum 5 (ID: 1146)', '212.248.79.130', NULL, '2026-04-02 11:36:01'),
(14, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260408-1290 на сумму 15225 руб.', '109.61.17.107', NULL, '2026-04-08 06:01:22'),
(15, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260408-3736 на сумму 5424 руб.', '109.61.17.107', NULL, '2026-04-08 06:01:38'),
(16, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260408-6888 на сумму 5424 руб.', '109.61.17.107', NULL, '2026-04-08 06:01:50'),
(17, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260408-7808 на сумму 5424 руб.', '109.61.17.107', NULL, '2026-04-08 06:02:20'),
(18, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260408-9700 на сумму 26144 руб.', '109.61.17.107', NULL, '2026-04-08 06:03:19'),
(19, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260408-6098 на сумму 15280 руб.', '109.61.17.107', NULL, '2026-04-08 06:03:52'),
(20, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260408-8912 на сумму 21440 руб.', '109.61.17.107', NULL, '2026-04-08 06:04:36'),
(21, 4, 'order_created', NULL, NULL, 'Заказ #ORD-20260408-5644 на сумму 15280 руб.', '109.61.17.107', NULL, '2026-04-08 06:05:02'),
(22, NULL, 'order_created', NULL, NULL, 'Заказ #ORD-20260409-5173 на сумму 4928 руб.', '212.248.79.130', NULL, '2026-04-09 14:20:57'),
(23, 0, 'order_created', NULL, NULL, 'Заказ #ORD-20260410-3358 на сумму 5184 руб.', '104.28.198.247', NULL, '2026-04-10 06:59:37'),
(24, 0, 'logout', NULL, NULL, NULL, '104.28.198.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 06:59:41'),
(25, 4, 'order_updated', NULL, NULL, 'Заказ #11, новый статус: confirmed', '104.28.198.247', NULL, '2026-04-10 07:00:23');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admindoor1_logs`
--
ALTER TABLE `admindoor1_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admindoor1_logs`
--
ALTER TABLE `admindoor1_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
