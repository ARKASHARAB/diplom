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
-- Структура таблицы `admindoor1_orders`
--
-- Создание: Мар 19 2026 г., 12:02
--

DROP TABLE IF EXISTS `admindoor1_orders`;
CREATE TABLE `admindoor1_orders` (
  `id` int NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_address` text COLLATE utf8mb4_unicode_ci,
  `delivery_method` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'pickup',
  `payment_method` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'cash',
  `delivery_cost` decimal(10,2) DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('new','processing','confirmed','shipped','delivered','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `admin_comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `admindoor1_orders`
--

INSERT INTO `admindoor1_orders` (`id`, `order_number`, `user_id`, `session_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `delivery_method`, `payment_method`, `delivery_cost`, `subtotal`, `total`, `status`, `payment_status`, `comment`, `admin_comment`, `created_at`) VALUES
(1, 'ORD-20260319-8776', 4, 'f795569091e4c43c886e4db79c477498', '123', '1@1.cam', '111111111', '', 'pickup', 'cash', '0.00', '30.00', '30.00', 'processing', 'failed', '', '1', '2026-03-19 12:26:12'),
(2, 'ORD-20260408-1290', 4, 'f8f13ac0f4388bd262c405b65ade8a75', '123', '11@mail.ru', '+7 (544) 444-44-44', '', 'pickup', 'cash', '0.00', '15225.00', '15225.00', 'new', 'pending', '', NULL, '2026-04-08 06:01:22'),
(3, 'ORD-20260408-3736', 4, 'f8f13ac0f4388bd262c405b65ade8a75', '123', '11@mail.ru', '+7 (544) 444-44-44', '', 'pickup', 'cash', '0.00', '5424.00', '5424.00', 'new', 'pending', '', NULL, '2026-04-08 06:01:38'),
(4, 'ORD-20260408-6888', 4, 'f8f13ac0f4388bd262c405b65ade8a75', '123', '11@mail.ru', '+7 (544) 444-44-44', '', 'pickup', 'cash', '0.00', '5424.00', '5424.00', 'new', 'pending', '', NULL, '2026-04-08 06:01:50'),
(5, 'ORD-20260408-7808', 4, 'f8f13ac0f4388bd262c405b65ade8a75', '123', '11@mail.ru', '+7 (544) 444-44-44', '', 'pickup', 'cash', '0.00', '5424.00', '5424.00', 'new', 'pending', '', NULL, '2026-04-08 06:02:20'),
(6, 'ORD-20260408-9700', 4, 'f8f13ac0f4388bd262c405b65ade8a75', '123', '11@mail.ru', '+7 (544) 444-44-44', '', 'pickup', 'cash', '0.00', '26144.00', '26144.00', 'new', 'pending', '', NULL, '2026-04-08 06:03:19'),
(7, 'ORD-20260408-6098', 4, 'f8f13ac0f4388bd262c405b65ade8a75', '123', '11@mail.ru', '+712212121323', '', 'pickup', 'cash', '0.00', '15280.00', '15280.00', 'new', 'pending', '', NULL, '2026-04-08 06:03:52'),
(8, 'ORD-20260408-8912', 4, 'f8f13ac0f4388bd262c405b65ade8a75', '123', '11@mail.ru', '12331', '', 'pickup', 'cash', '0.00', '21440.00', '21440.00', 'new', 'pending', '', NULL, '2026-04-08 06:04:36'),
(9, 'ORD-20260408-5644', 4, 'f8f13ac0f4388bd262c405b65ade8a75', '123', '11@mail.ru', '213', '', 'pickup', 'cash', '0.00', '15280.00', '15280.00', 'new', 'pending', '', NULL, '2026-04-08 06:05:02'),
(10, 'ORD-20260409-5173', NULL, 'b0592708b4deb299aefb0ae8b6e6addd', '!12', '213123@mail.ru', '213', '', 'pickup', 'cash', '0.00', '4928.00', '4928.00', 'new', 'pending', '', NULL, '2026-04-09 14:20:57'),
(11, 'ORD-20260410-3358', 0, '5147b9c59f69f4c5c03f783c94a20fa0', 'Vlad Bulka', '11@mail.ru', '+7 (544) 444-44-44', '', 'pickup', 'cash', '0.00', '5184.00', '5184.00', 'confirmed', 'failed', '', 'f', '2026-04-10 06:59:37');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admindoor1_orders`
--
ALTER TABLE `admindoor1_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admindoor1_orders`
--
ALTER TABLE `admindoor1_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
