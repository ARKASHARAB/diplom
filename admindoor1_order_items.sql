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
-- Структура таблицы `admindoor1_order_items`
--
-- Создание: Мар 19 2026 г., 12:02
--

DROP TABLE IF EXISTS `admindoor1_order_items`;
CREATE TABLE `admindoor1_order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_article` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `selected_color` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selected_size` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `admindoor1_order_items`
--

INSERT INTO `admindoor1_order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_article`, `quantity`, `price`, `total`, `selected_color`, `selected_size`, `created_at`) VALUES
(1, 1, 1979, 'Буклет Stockholm, Winter', 'VFD-000538-538', 1, '30.00', '30.00', 'Не указан', '[]', '2026-03-19 12:26:12'),
(2, 2, 45, 'Glanta Ett', 'Ц0000088898-45', 1, '15225.00', '15225.00', 'Cotton', '600*2000', '2026-04-08 06:01:22'),
(3, 3, 936, 'Atum 7', 'Ц0000036679-936', 1, '5424.00', '5424.00', 'Wenge', '600*2000', '2026-04-08 06:01:38'),
(4, 4, 940, 'Atum 5', 'Ц0000074292-940', 1, '5424.00', '5424.00', 'Wenge', '600*2000', '2026-04-08 06:01:50'),
(5, 5, 923, 'Atum 23', 'Ц0000049542-923', 1, '5424.00', '5424.00', 'Wenge', '600*2000', '2026-04-08 06:02:20'),
(6, 6, 940, 'Atum 5', 'Ц0000074292-940', 1, '5424.00', '5424.00', 'Wenge', '600*2000', '2026-04-08 06:03:19'),
(7, 6, 923, 'Atum 23', 'Ц0000049542-923', 1, '5424.00', '5424.00', 'Wenge', '600*2000', '2026-04-08 06:03:19'),
(8, 6, 942, 'Atum 2', 'Ц0000022591-942', 1, '4928.00', '4928.00', 'Wenge', '600*2000', '2026-04-08 06:03:19'),
(9, 6, 867, 'Atum Pro 27', 'Ц0000071923-867', 1, '5184.00', '5184.00', 'Brun Oak', '600*2000', '2026-04-08 06:03:19'),
(10, 6, 866, 'Atum Pro 28', 'Ц0000072026-866', 1, '5184.00', '5184.00', 'Brun Oak', '600*2000', '2026-04-08 06:03:19'),
(11, 7, 961, 'Atum 6', 'Ц0000070945-961', 1, '4928.00', '4928.00', 'Terra Vellum', '600*2000', '2026-04-08 06:03:52'),
(12, 7, 942, 'Atum 2', 'Ц0000022591-942', 1, '4928.00', '4928.00', 'Wenge', '600*2000', '2026-04-08 06:03:52'),
(13, 7, 923, 'Atum 23', 'Ц0000049542-923', 1, '5424.00', '5424.00', 'Wenge', '600*2000', '2026-04-08 06:03:52'),
(14, 8, 870, 'Atum Pro 25', 'Ц0000070751-870', 1, '5184.00', '5184.00', 'Brun Oak', '600*2000', '2026-04-08 06:04:36'),
(15, 8, 862, 'Atum Pro 30', 'Ц0000072226-862', 1, '5184.00', '5184.00', 'Brun Oak', '600*2000', '2026-04-08 06:04:36'),
(16, 8, 864, 'Atum Pro 29', 'Ц0000072121-864', 1, '5536.00', '5536.00', 'Brun Oak', '600*2000', '2026-04-08 06:04:36'),
(17, 8, 896, 'Atum Pro 30', 'Ц0000072262-896', 1, '5536.00', '5536.00', 'Scansom Oak', '600*2000,700*2000,800*2000,900*2000', '2026-04-08 06:04:36'),
(18, 9, 961, 'Atum 6', 'Ц0000070945-961', 1, '4928.00', '4928.00', 'Terra Vellum', '600*2000', '2026-04-08 06:05:02'),
(19, 9, 942, 'Atum 2', 'Ц0000022591-942', 1, '4928.00', '4928.00', 'Wenge', '600*2000', '2026-04-08 06:05:02'),
(20, 9, 923, 'Atum 23', 'Ц0000049542-923', 1, '5424.00', '5424.00', 'Wenge', '600*2000', '2026-04-08 06:05:02'),
(21, 10, 961, 'Atum 6', 'Ц0000070945-961', 1, '4928.00', '4928.00', 'Terra Vellum', '600*2000', '2026-04-09 14:20:57'),
(22, 11, 862, 'Atum Pro 30', 'Ц0000072226-862', 1, '5184.00', '5184.00', 'Brun Oak', '600*2000', '2026-04-10 06:59:37');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admindoor1_order_items`
--
ALTER TABLE `admindoor1_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admindoor1_order_items`
--
ALTER TABLE `admindoor1_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
