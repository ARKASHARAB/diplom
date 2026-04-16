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
-- Структура таблицы `admindoor1_color_settings`
--
-- Создание: Апр 02 2026 г., 02:14
--

DROP TABLE IF EXISTS `admindoor1_color_settings`;
CREATE TABLE `admindoor1_color_settings` (
  `id` int NOT NULL,
  `color_name` varchar(100) NOT NULL,
  `color_code` varchar(7) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `admindoor1_color_settings`
--

INSERT INTO `admindoor1_color_settings` (`id`, `color_name`, `color_code`, `display_name`, `sort_order`, `is_active`, `created_at`) VALUES
(87, 'белый', '#FFFFFF', 'Белый', 10, 1, '2026-04-02 05:23:42'),
(88, 'черный', '#000000', 'Черный', 20, 1, '2026-04-02 05:23:42'),
(89, 'серый', '#808080', 'Серый', 30, 1, '2026-04-02 05:23:42'),
(90, 'коричневый', '#8B4513', 'Коричневый', 40, 1, '2026-04-02 05:23:42'),
(91, 'бежевый', '#F5F5DC', 'Бежевый', 50, 1, '2026-04-02 05:23:42'),
(92, 'венге', '#4A2C2C', 'Венге', 60, 1, '2026-04-02 05:23:42'),
(93, 'дуб', '#8B5A2B', 'Дуб', 70, 1, '2026-04-02 05:23:42'),
(94, 'орех', '#5D3A1A', 'Орех', 80, 1, '2026-04-02 05:23:42'),
(95, 'красный', '#FF4444', 'Красный', 90, 1, '2026-04-02 05:23:42'),
(96, 'синий', '#4444FF', 'Синий', 100, 1, '2026-04-02 05:23:42'),
(97, 'зеленый', '#44FF44', 'Зеленый', 110, 1, '2026-04-02 05:23:42'),
(98, 'желтый', '#FFFF44', 'Желтый', 120, 1, '2026-04-02 05:23:42'),
(99, 'AB', '#cda434', 'AB', 500, 1, '2026-04-02 13:41:42'),
(100, 'AC', '#b87333', 'AC', 501, 1, '2026-04-02 13:41:42'),
(101, 'Antic Loft', '#c4c6c4', 'Antic Loft', 502, 1, '2026-04-02 13:41:42'),
(102, 'Bianco', '#edeae8', 'Bianco', 503, 1, '2026-04-02 13:41:42'),
(103, 'Bianco Classic PG', '#cfd1d1', 'Bianco Classic PG', 504, 1, '2026-04-02 13:41:42'),
(104, 'BL', '#000', 'BL', 505, 1, '2026-04-02 13:41:42'),
(105, 'BL-S', '#252222', 'BL-S', 506, 1, '2026-04-02 13:41:42'),
(106, 'BN', '#383737', 'BN', 507, 1, '2026-04-02 13:41:42'),
(107, 'Brun Oak', '#776b5f', 'Brun Oak', 508, 1, '2026-04-02 13:41:42'),
(108, 'Cappuccino', '#bfb6ad', 'Cappuccino', 509, 1, '2026-04-02 13:41:42'),
(110, 'Cinnamon', '#745342', 'Cinnamon', 511, 1, '2026-04-02 13:41:42'),
(111, 'COF', '#7c5537', 'COF', 512, 1, '2026-04-02 13:41:42'),
(112, 'COF-S', '#7f6e40', 'COF-S', 513, 1, '2026-04-02 13:41:42'),
(113, 'Cotton', '#b2b3ae', 'Cotton', 514, 1, '2026-04-02 13:41:42'),
(114, 'CP', '#a49c9c', 'CP', 515, 1, '2026-04-02 13:41:42'),
(115, 'Emalex Ice', '#ccc8c8', 'Emalex Ice', 516, 1, '2026-04-02 13:41:42'),
(116, 'Emalex Steel', '#cfd1d1', 'Emalex Steel', 517, 1, '2026-04-02 13:41:42'),
(117, 'Fleet Soft', '#b8b3af', 'Fleet Soft', 518, 1, '2026-04-02 13:41:42'),
(118, 'GP', '#c9b48f', 'GP', 519, 1, '2026-04-02 13:41:42'),
(119, 'GR/CP-S55', '#736f6f', 'GR/CP-S55', 520, 1, '2026-04-02 13:41:42'),
(120, 'GR/PC', '#6f6964', 'GR/PC', 521, 1, '2026-04-02 13:41:42'),
(121, 'Graphite', '#666467', 'Graphite', 522, 1, '2026-04-02 13:41:42'),
(122, 'Grey', '#444444', 'Grey', 523, 1, '2026-04-02 13:41:42'),
(123, 'Grey Wood', '#494747', 'Grey Wood', 524, 1, '2026-04-02 13:41:42'),
(124, 'Griz Soft', '#bbbabc', 'Griz Soft', 525, 1, '2026-04-02 13:41:42'),
(125, 'Honey Classic PB', '#855937', 'Honey Classic PB', 526, 1, '2026-04-02 13:41:42'),
(126, 'IB', '#8d7253', 'IB', 527, 1, '2026-04-02 13:41:42'),
(127, 'Ivory', '#e6e2d6', 'Ivory', 528, 1, '2026-04-02 13:41:42'),
(128, 'Ivory PC', '#dddad1', 'Ivory PC', 529, 1, '2026-04-02 13:41:42'),
(129, 'Ivory PG', '#e1ded6', 'Ivory PG', 530, 1, '2026-04-02 13:41:42'),
(130, 'Jet Loft', '#1e1b1a', 'Jet Loft', 531, 1, '2026-04-02 13:41:42'),
(131, 'Latte', '#0b6eed', 'Latte', 532, 1, '2026-04-02 13:41:42'),
(132, 'Latte L', '#222876', 'Latte L', 533, 1, '2026-04-02 13:41:42'),
(133, 'Lin Vellum', '#b0afa3', 'Lin Vellum', 534, 1, '2026-04-02 13:41:42'),
(134, 'MAB', '#7dd3a2', 'MAB', 535, 1, '2026-04-02 13:41:42'),
(135, 'MAB/AB', '#a4f177', 'MAB/AB', 536, 1, '2026-04-02 13:41:42'),
(136, 'Mocco', '#225e05', 'Mocco', 537, 1, '2026-04-02 13:41:42'),
(137, 'Mouse', '#8e8581', 'Mouse', 538, 1, '2026-04-02 13:41:42'),
(138, 'Nord Vellum', '#efeeeb', 'Nord Vellum', 539, 1, '2026-04-02 13:41:42'),
(139, 'OMB', '#937b53', 'OMB', 540, 1, '2026-04-02 13:41:42'),
(140, 'OMB/CH', '#857c61', 'OMB/CH', 541, 1, '2026-04-02 13:41:42'),
(141, 'OMS', '#857c6f', 'OMS', 542, 1, '2026-04-02 13:41:42'),
(142, 'OMS/GR', '#c2baba', 'OMS/GR', 543, 1, '2026-04-02 13:41:42'),
(143, 'PC', '#dcdcdc', 'PC', 544, 1, '2026-04-02 13:41:42'),
(144, 'PC/W', '#1ea487', 'PC/W', 545, 1, '2026-04-02 13:41:42'),
(145, 'PG', '#fff', 'PG', 546, 1, '2026-04-02 13:41:42'),
(146, 'PG/W', '#f5f44a', 'PG/W', 547, 1, '2026-04-02 13:41:42'),
(147, 'Polar', '#d3d3d3', 'Polar', 548, 1, '2026-04-02 13:41:42'),
(148, 'Polar PC', '#e3e3e3', 'Polar PC', 549, 1, '2026-04-02 13:41:42'),
(149, 'Polar PG', '#e4e4e4', 'Polar PG', 550, 1, '2026-04-02 13:41:42'),
(150, 'Polar PS', '#dcdcdc', 'Polar PS', 551, 1, '2026-04-02 13:41:42'),
(151, 'Polar Soft', '#dcdcdc', 'Polar Soft', 552, 1, '2026-04-02 13:41:42'),
(152, 'Sand Vellum', '#cfb7a0', 'Sand Vellum', 553, 1, '2026-04-02 13:41:42'),
(153, 'SC', '#c5c5bb', 'SC', 554, 1, '2026-04-02 13:41:42'),
(154, 'SC/CP', '#f3f3f1', 'SC/CP', 555, 1, '2026-04-02 13:41:42'),
(155, 'SC/CP-S', '#f3f3f1', 'SC/CP-S', 556, 1, '2026-04-02 13:41:42'),
(156, 'SC/CP-S55', '#f3f3f1', 'SC/CP-S55', 557, 1, '2026-04-02 13:41:42'),
(157, 'SC/W-S55', '#f3f3f1', 'SC/W-S55', 558, 1, '2026-04-02 13:41:42'),
(158, 'Scansom Oak', '#cac5bf', 'Scansom Oak', 559, 1, '2026-04-02 13:41:42'),
(159, 'SG', '#edd7a8', 'SG', 560, 1, '2026-04-02 13:41:42'),
(160, 'SG/GP', '#e3cc9b', 'SG/GP', 561, 1, '2026-04-02 13:41:42'),
(161, 'SN', '#eaeae8', 'SN', 562, 1, '2026-04-02 13:41:42'),
(162, 'SN/BN', '#eaeae8', 'SN/BN', 563, 1, '2026-04-02 13:41:42'),
(163, 'SN/BN-S', '#eaeae8', 'SN/BN-S', 564, 1, '2026-04-02 13:41:42'),
(164, 'SN/CP', '#eaeae8', 'SN/CP', 565, 1, '2026-04-02 13:41:42'),
(165, 'Snow', '#dadada', 'Snow', 566, 1, '2026-04-02 13:41:42'),
(166, 'Stone', '#f3f3f1', 'Stone', 567, 1, '2026-04-02 13:41:42'),
(167, 'Stone Oak', '#bdb9b6', 'Stone Oak', 568, 1, '2026-04-02 13:41:42'),
(168, 'Terra Vellum', '#5a524c', 'Terra Vellum', 569, 1, '2026-04-02 13:41:42'),
(169, 'Truffo', '#54493f', 'Truffo', 570, 1, '2026-04-02 13:41:42'),
(170, 'W/PC', '#ebe9e9', 'W/PC', 571, 1, '2026-04-02 13:41:42'),
(171, 'W/PG', '#ebe9e9', 'W/PG', 572, 1, '2026-04-02 13:41:42'),
(172, 'Wenge', '#362d27', 'Wenge', 573, 1, '2026-04-02 13:41:42'),
(173, 'Не указан', '#cdded4', 'Не указан', 574, 1, '2026-04-02 13:41:42');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admindoor1_color_settings`
--
ALTER TABLE `admindoor1_color_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_color_name` (`color_name`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admindoor1_color_settings`
--
ALTER TABLE `admindoor1_color_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
