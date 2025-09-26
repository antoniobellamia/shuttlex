-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Set 21, 2025 alle 17:16
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shuttlex`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `luogo`
--

CREATE TABLE `luogo` (
  `id` varchar(3) NOT NULL,
  `denominazione` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `luogo`
--

INSERT INTO `luogo` (`id`, `denominazione`) VALUES
('cia', 'LARGO CIAIA - Veterinaria'),
('cxc', 'CAMPUSX'),
('eco', 'ECONOMIA'),
('med', 'POLICLINICO - MEDICINA'),
('pol', 'POLIBA'),
('stz', 'UNIBA - STAZIONE');

-- --------------------------------------------------------

--
-- Struttura della tabella `orari`
--

CREATE TABLE `orari` (
  `id` int(11) NOT NULL,
  `orario` time DEFAULT NULL,
  `tipoGiorno` tinyint(1) NOT NULL DEFAULT 1,
  `tratta` int(11) UNSIGNED NOT NULL,
  `idLuogo` varchar(3) NOT NULL DEFAULT 'stz'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `orari`
--

INSERT INTO `orari` (`id`, `orario`, `tipoGiorno`, `tratta`, `idLuogo`) VALUES
(1, '07:10:00', 0, 1, 'cxc'),
(2, '07:20:00', 0, 1, 'pol'),
(3, '07:25:00', 0, 1, 'cia'),
(4, '07:30:00', 0, 1, 'stz'),
(5, '07:35:00', 0, 1, 'med'),
(6, '07:40:00', 0, 1, 'med'),
(7, '08:00:00', 0, 1, 'cxc'),
(8, '08:00:01', 0, 2, 'cxc'),
(9, '08:10:00', 0, 2, 'pol'),
(10, '08:20:00', 0, 2, 'cxc'),
(11, '08:30:00', 0, 3, 'cxc'),
(12, '08:40:00', 0, 3, 'pol'),
(13, '08:50:00', 0, 3, 'cia'),
(14, '08:55:00', 0, 3, 'stz'),
(15, '09:00:00', 0, 3, 'med'),
(16, '09:10:00', 0, 3, 'med'),
(17, '09:20:00', 0, 3, 'cxc'),
(18, '09:30:00', 0, 4, 'cxc'),
(19, '09:40:00', 0, 4, 'pol'),
(20, '09:45:00', 0, 4, 'cia'),
(21, '09:50:00', 0, 4, 'stz'),
(22, '09:55:00', 0, 4, 'med'),
(23, '10:10:00', 0, 4, 'eco'),
(24, '10:20:00', 0, 4, 'cxc'),
(25, '11:00:00', 0, 5, 'cxc'),
(26, '13:00:00', 0, 6, 'cxc'),
(27, '15:00:00', 0, 7, 'cxc'),
(28, '16:30:00', 0, 8, 'cxc'),
(29, '18:15:00', 0, 9, 'cxc'),
(30, '20:30:00', 0, 10, 'cxc'),
(31, '12:30:00', 0, 5, 'cxc'),
(32, '14:40:00', 0, 6, 'cxc'),
(33, '16:20:00', 0, 7, 'cxc'),
(34, '17:55:00', 0, 8, 'cxc'),
(35, '19:40:00', 0, 9, 'cxc'),
(36, '21:00:00', 0, 10, 'cxc'),
(37, '11:10:00', 0, 5, 'pol'),
(38, '13:10:00', 0, 6, 'pol'),
(39, '15:10:00', 0, 7, 'pol'),
(40, '16:40:00', 0, 8, 'pol'),
(41, '18:20:00', 0, 9, 'pol'),
(42, '12:25:00', 0, 5, 'pol'),
(43, '14:35:00', 0, 6, 'pol'),
(44, '16:10:00', 0, 7, 'pol'),
(45, '17:45:00', 0, 8, 'pol'),
(46, '19:25:00', 0, 9, 'pol'),
(47, '11:15:00', 0, 5, 'cia'),
(48, '13:20:00', 0, 6, 'cia'),
(49, '15:15:00', 0, 7, 'cia'),
(50, '16:50:00', 0, 8, 'cia'),
(51, '18:30:00', 0, 9, 'cia'),
(52, '12:20:00', 0, 5, 'cia'),
(53, '14:25:00', 0, 6, 'cia'),
(54, '16:05:00', 0, 7, 'cia'),
(55, '17:40:00', 0, 8, 'cia'),
(56, '19:20:00', 0, 9, 'cia'),
(57, '11:20:00', 0, 5, 'stz'),
(58, '13:25:00', 0, 6, 'stz'),
(59, '15:20:00', 0, 7, 'stz'),
(60, '16:55:00', 0, 8, 'stz'),
(61, '18:35:00', 0, 9, 'stz'),
(62, '20:45:00', 0, 10, 'stz'),
(63, '12:15:00', 0, 5, 'stz'),
(64, '14:20:00', 0, 6, 'stz'),
(65, '16:00:00', 0, 7, 'stz'),
(66, '17:35:00', 0, 8, 'stz'),
(67, '19:15:00', 0, 9, 'stz'),
(68, '11:25:00', 0, 5, 'med'),
(69, '13:30:00', 0, 6, 'med'),
(70, '15:25:00', 0, 7, 'med'),
(71, '17:00:00', 0, 8, 'med'),
(72, '18:40:00', 0, 9, 'med'),
(73, '12:10:00', 0, 5, 'med'),
(74, '14:15:00', 0, 6, 'med'),
(75, '15:55:00', 0, 7, 'med'),
(76, '17:25:00', 0, 8, 'med'),
(77, '19:10:00', 0, 9, 'med'),
(78, '12:00:00', 0, 5, 'eco'),
(79, '14:10:00', 0, 6, 'eco'),
(80, '15:45:00', 0, 7, 'eco'),
(81, '17:15:00', 0, 8, 'eco'),
(82, '19:00:00', 0, 9, 'eco'),
(83, '10:00:00', 1, 1, 'cxc'),
(84, '13:00:00', 1, 2, 'cxc'),
(85, '17:00:00', 1, 3, 'cxc'),
(86, '19:00:00', 1, 4, 'cxc'),
(87, '20:00:00', 1, 5, 'cxc'),
(88, '21:30:00', 1, 6, 'cxc'),
(89, '10:40:00', 1, 1, 'cxc'),
(90, '13:40:00', 1, 2, 'cxc'),
(91, '17:40:00', 1, 3, 'cxc'),
(92, '19:40:00', 1, 4, 'cxc'),
(93, '20:40:00', 1, 5, 'cxc'),
(94, '21:50:00', 1, 6, 'cxc'),
(95, '10:20:00', 1, 1, 'stz'),
(96, '13:20:00', 1, 2, 'stz'),
(97, '17:20:00', 1, 3, 'stz'),
(98, '19:20:00', 1, 4, 'stz'),
(99, '20:20:00', 1, 5, 'stz'),
(100, '21:40:00', 1, 6, 'stz');

-- --------------------------------------------------------

--
-- Struttura della tabella `tratte`
--

CREATE TABLE `tratte` (
  `numero` int(11) UNSIGNED NOT NULL,
  `tipo` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `tratte`
--

INSERT INTO `tratte` (`numero`, `tipo`, `note`) VALUES
(1, 0, NULL),
(1, 1, NULL),
(2, 0, NULL),
(2, 1, NULL),
(3, 0, NULL),
(3, 1, NULL),
(4, 0, NULL),
(4, 1, NULL),
(5, 0, NULL),
(5, 1, NULL),
(6, 0, NULL),
(6, 1, 'SOLO SABATO'),
(7, 0, NULL),
(8, 0, NULL),
(9, 0, NULL),
(10, 0, NULL);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `luogo`
--
ALTER TABLE `luogo`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `orari`
--
ALTER TABLE `orari`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orario` (`orario`,`tipoGiorno`),
  ADD KEY `idLuogo` (`idLuogo`),
  ADD KEY `tipoGiorno` (`tipoGiorno`,`tratta`),
  ADD KEY `fk_orari_tratte` (`tratta`,`tipoGiorno`);

--
-- Indici per le tabelle `tratte`
--
ALTER TABLE `tratte`
  ADD PRIMARY KEY (`numero`,`tipo`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `orari`
--
ALTER TABLE `orari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `orari`
--
ALTER TABLE `orari`
  ADD CONSTRAINT `fk_orari_tratte` FOREIGN KEY (`tratta`,`tipoGiorno`) REFERENCES `tratte` (`numero`, `tipo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orari_ibfk_1` FOREIGN KEY (`idLuogo`) REFERENCES `luogo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
