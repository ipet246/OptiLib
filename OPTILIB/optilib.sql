-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-08-2026 a las 15:19:58
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `optilib`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'Maquinaria e Instrumentos'),
(2, 'Seguridad e Higiene'),
(3, 'Cristales y Stock Óptico'),
(4, 'Marcos y Monturas'),
(5, 'Herramientas y Insumos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklists`
--

CREATE TABLE `checklists` (
  `id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `responsable` varchar(100) NOT NULL,
  `maquinas_sin_agua` tinyint(1) DEFAULT 0,
  `equipos_limpios` tinyint(1) DEFAULT 0,
  `corriente_agua_cortada` tinyint(1) DEFAULT 0,
  `caja_prueba_ordenada` tinyint(1) DEFAULT 0,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `codigo_serial` varchar(50) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `subtipo` varchar(50) DEFAULT NULL,
  `forma_diseno` varchar(50) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  `estado` enum('Disponible','En Uso','En Mantenimiento','Baja','Rayado/Dañado') DEFAULT 'Disponible',
  `fecha_adquisicion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT 'default.png',
  `ultimo_usuario` varchar(100) DEFAULT NULL,
  `alerta_enviada` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `items`
--

INSERT INTO `items` (`id`, `codigo_serial`, `categoria_id`, `nombre`, `subtipo`, `forma_diseno`, `cantidad`, `estado`, `fecha_adquisicion`, `observaciones`, `imagen`, `ultimo_usuario`, `alerta_enviada`) VALUES
(1, 'INS-PUP-001', 1, 'Pupilómetro', 'Instrumento', 'N/A', 11, 'Disponible', NULL, NULL, 'inst_6a86584874705.jpeg', 'Técnico Óptico', 0),
(2, 'INS-FRN-001', 1, 'Frontofocómetro', 'Instrumento', 'N/A', 1, 'Disponible', NULL, NULL, 'inst_6a8658317e8bf.jpeg', 'Técnico Óptico', 0),
(3, 'INS-CJP-001', 1, 'Caja de Prueba', 'Instrumento', 'N/A', 1, 'Disponible', NULL, NULL, 'inst_6a8657ac39838.jpeg', 'Técnico Óptico', 0),
(4, 'INS-BIS-001', 1, 'Biseladora / Minibisel', 'Maquinaria', 'N/A', 1, 'En Mantenimiento', NULL, NULL, 'inst_6a86554d47e8d.jpeg', 'Encargado de Lab', 0),
(5, 'CRI-ORG-001', 3, 'Cristales Orgánicos', 'Orgánico', 'Estándar', 131, 'Disponible', NULL, NULL, 'inst_6a8657d88b8fd.webp', 'Técnico Óptico', 0),
(6, 'CRI-PLC-001', 3, 'Cristales Policarbonato', 'Policarbonato', 'Estándar', 40, 'Disponible', NULL, NULL, 'inst_6a86581247930.webp', 'Técnico Óptico', 0),
(7, 'MRC-MET-001', 4, 'Montura Completa Metal', 'Metal', 'Rectangular', 15, 'Disponible', NULL, NULL, 'default.png', 'Encargado de Lab', 0),
(8, 'SEG-ANT-001', 2, 'Antiparras de Seguridad', 'Protección', 'N/A', 10, 'Disponible', NULL, NULL, 'inst_6a86575029f70.webp', 'Técnico Óptico', 0),
(9, 'SEG-CHQ-001', 2, 'Chaquetas / Cofias', 'Indumentaria', 'N/A', 5, 'Disponible', NULL, NULL, 'default.png', 'Administración', 0),
(10, 'HER-DES-001', 5, 'Destornilladores', 'Set de precisión', 'N/A', 3, 'Disponible', NULL, NULL, 'inst_6a8658d4dbd43.jpeg', 'Técnico Óptico', 0),
(11, 'HER-TRN-001', 1, 'Torno (perforador de lentes)', 'Maquinaria', 'N/A', 1, 'Disponible', NULL, NULL, 'inst_6a865903a063e.jpeg', 'Encargado de Lab', 0),
(12, 'HER-PZB-001', 5, 'Pinza de desbaste', 'Metal / Goma', 'N/A', 2, 'Disponible', NULL, NULL, 'inst_6a86591113165.jpeg', 'Técnico Óptico', 0),
(13, 'HER-PGC-001', 5, 'Pinza gira cristal', 'Metal / Goma', 'N/A', 2, 'Disponible', NULL, NULL, 'inst_6a86591717a26.jpeg', 'Técnico Óptico', 0),
(14, 'HER-CAL-001', 1, 'Caloventor', 'Eléctrico', 'N/A', 1, 'Disponible', NULL, NULL, 'inst_6a86592c64336.jpeg', 'Encargado de Lab', 0),
(15, 'HER-LIM-001', 5, 'Limas', 'Set de aguja', 'N/A', 2, 'Disponible', NULL, NULL, 'inst_6a86593db1582.jpeg', 'Técnico Óptico', 0),
(16, 'INS-OPT-001', 1, 'Cartel de optotipo', 'Iluminado', 'Rectangular', 1, 'Disponible', NULL, NULL, 'inst_6a86594d0c8d2.jpeg', 'Técnico Óptico', 0),
(17, 'HER-SLD-001', 1, 'Soldadora embutidora', 'Maquinaria', 'N/A', 1, 'Disponible', NULL, NULL, 'inst_6a86595993b0b.jpeg', 'Encargado de Lab', 0),
(18, 'HER-DIA-001', 1, 'Máquina Diamantada (a cinta)', 'Maquinaria', 'N/A', 1, 'Disponible', NULL, NULL, 'inst_6a865965aacdb.jpeg', 'Encargado de Lab', 0),
(20, 'INS-002', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(21, 'INS-003', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(22, 'INS-004', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(23, 'INS-005', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(24, 'INS-006', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(25, 'INS-007', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(26, 'INS-008', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(27, 'INS-009', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(28, 'INS-010', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0),
(29, 'INS-011', 1, 'Biseladora / Minibisel', 'Maquinaria', '', 1, 'Disponible', NULL, '', 'default.png', 'Encargado de Lab', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `descripcion_falla` text NOT NULL,
  `estado_mantenimiento` enum('En Revisión','En Reparación','Reparado / Listo') DEFAULT 'En Revisión',
  `tecnico_cargo` varchar(100) DEFAULT NULL,
  `fecha_reporte` datetime DEFAULT current_timestamp(),
  `fecha_solucion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mantenimientos`
--

INSERT INTO `mantenimientos` (`id`, `item_id`, `descripcion_falla`, `estado_mantenimiento`, `tecnico_cargo`, `fecha_reporte`, `fecha_solucion`) VALUES
(1, 10, 'dos de los destornilladores estan quebrados se requiere reposicion de material', 'Reparado / Listo', 'tecnico optico santiago', '2026-08-20 09:52:45', '2026-08-20 09:55:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_proveedor`
--

CREATE TABLE `pedidos_proveedor` (
  `id` int(11) NOT NULL,
  `proveedor` varchar(100) NOT NULL,
  `material` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `estado` enum('Simulado / Pendiente','Recibido en Stock') DEFAULT 'Simulado / Pendiente',
  `fecha_pedido` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos_proveedor`
--

INSERT INTO `pedidos_proveedor` (`id`, `proveedor`, `material`, `cantidad`, `estado`, `fecha_pedido`) VALUES
(1, 'Distribuidora de Lentes Global', 'Lote Cristales Policarbonato', 10, 'Simulado / Pendiente', '2026-08-19 22:11:33');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `checklists`
--
ALTER TABLE `checklists`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_serial` (`codigo_serial`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indices de la tabla `pedidos_proveedor`
--
ALTER TABLE `pedidos_proveedor`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `checklists`
--
ALTER TABLE `checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pedidos_proveedor`
--
ALTER TABLE `pedidos_proveedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `mantenimientos_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
