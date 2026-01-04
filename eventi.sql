-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Creato il: Dic 10, 2026 alle 17:02
-- Versione del server: 10.6.24-MariaDB-ubu2204
-- Versione PHP: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eventi`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `attivi`
--

CREATE TABLE `attivi` (
  `id` int(11) NOT NULL,
  `codice_evento` varchar(50) NOT NULL,
  `tipo_evento` varchar(50) NOT NULL,
  `evento` varchar(255) NOT NULL,
  `localita` varchar(100) NOT NULL,
  `data_inserimento` datetime NOT NULL,
  `data_evento` datetime NOT NULL,
  `ora_dalle` time NOT NULL,
  `ora_alle` time NOT NULL,
  `calendario` enum('SI','NO','CONC') NOT NULL,
  `preventivo` enum('SI','NO','CONC') NOT NULL,
  `accettazione` enum('SI','NO','CONC') NOT NULL,
  `games_inserito` enum('SI','NO','CONC') NOT NULL,
  `games_completo` enum('SI','NO','CONC') NOT NULL,
  `allegato5` enum('SI','NO','CONC') NOT NULL,
  `allegato5_pdf` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `stato` enum('attesa','chiuso','rifiutato') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `chiusi`
--

CREATE TABLE `chiusi` (
  `id` int(11) NOT NULL,
  `data_chiusura` datetime DEFAULT NULL,
  `data_evento` datetime DEFAULT NULL,
  `evento` varchar(255) DEFAULT NULL,
  `localita` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ruolo` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `login`
-- User: admin
-- Pwd: Admin123
--

INSERT INTO `login` (`id`, `username`, `password`, `ruolo`, `email`) VALUES
(1, 'admin', '$2y$10$E/EYAYmjjS788ip8.MpgAO7XNPPxypB3HxFajRDNO6zg8at1BZGOG', 'Admin', 'mail@lamiasoccorso.org');

-- --------------------------------------------------------

--
-- Struttura della tabella `rifiutati`
--

CREATE TABLE `rifiutati` (
  `id` int(11) NOT NULL,
  `evento` varchar(255) DEFAULT NULL,
  `localita` varchar(255) DEFAULT NULL,
  `data_evento` datetime DEFAULT NULL,
  `data_rifiuto` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `storico_eventi`
--

CREATE TABLE `storico_eventi` (
  `id` int(11) NOT NULL,
  `tipo_evento` varchar(50) NOT NULL,
  `evento` varchar(255) NOT NULL,
  `localita` varchar(100) NOT NULL,
  `data_evento` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indici per le tabelle `attivi`
--
ALTER TABLE `attivi`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `chiusi`
--
ALTER TABLE `chiusi`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `rifiutati`
--
ALTER TABLE `rifiutati`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `storico_eventi`
--
ALTER TABLE `storico_eventi`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per la tabella `attivi`
--
ALTER TABLE `attivi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `chiusi`
--
ALTER TABLE `chiusi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `rifiutati`
--
ALTER TABLE `rifiutati`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `storico_eventi`
--
ALTER TABLE `storico_eventi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
