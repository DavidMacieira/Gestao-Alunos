-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 22-Mar-2026 às 22:22
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gestao_alunos`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int(11) NOT NULL,
  `morada` varchar(255) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `estado` enum('Rascunho','Submetida','Aprovada','Rejeitada') DEFAULT 'Rascunho',
  `observacoes_validacao` text DEFAULT NULL,
  `validado_por` int(11) DEFAULT NULL,
  `data_validacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `alunos`
--

INSERT INTO `alunos` (`id`, `morada`, `data_nascimento`, `telefone`, `foto`, `curso_id`, `estado`, `observacoes_validacao`, `validado_por`, `data_validacao`) VALUES
(1, 'Rua das Flores, 123, 4750-000 Barcelos', '2001-04-15', '912 345 678', '../uploads/fotos/foto_1_1774001483.png', 1, 'Aprovada', NULL, 4, '2026-03-20 09:28:31'),
(5, 'Avenida Central, 45, 4750-100 Braga', '2002-08-22', '965 432 100', NULL, 1, 'Submetida', NULL, NULL, NULL),
(6, NULL, NULL, NULL, NULL, 2, 'Rascunho', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sigla` varchar(20) DEFAULT NULL,
  `horario_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `cursos`
--

INSERT INTO `cursos` (`id`, `nome`, `sigla`, `horario_pdf`) VALUES
(1, 'Desenvolvimento Web e Multimédia', 'DWM', NULL),
(2, 'Comércio Eletrónico', 'CE', NULL),
(3, 'Design de Moda', 'DM', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `disciplinas`
--

CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL,
  `nome_disciplina` varchar(100) NOT NULL,
  `sigla` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `disciplinas`
--

INSERT INTO `disciplinas` (`id`, `nome_disciplina`, `sigla`) VALUES
(1, 'Programação Web', 'PW'),
(2, 'Bases de Dados', 'BD'),
(3, 'Matemática', 'Mat'),
(4, 'Design Gráfico', 'DG'),
(5, 'Marketing Digital', 'MD'),
(6, 'Sistemas de Informação', 'SI'),
(7, 'Desenvolvimento Mobile', 'DM'),
(8, 'Segurança Informática', 'SI2');

-- --------------------------------------------------------

--
-- Estrutura da tabela `notas`
--

CREATE TABLE `notas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) DEFAULT NULL,
  `disciplina_id` int(11) DEFAULT NULL,
  `nota` decimal(4,2) DEFAULT NULL,
  `epoca` varchar(50) DEFAULT NULL,
  `ano_letivo` varchar(20) DEFAULT NULL,
  `pauta_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `notas`
--

INSERT INTO `notas` (`id`, `aluno_id`, `disciplina_id`, `nota`, `epoca`, `ano_letivo`, `pauta_id`) VALUES
(1, 1, 1, 15.00, 'Normal', '2024/2025', 1),
(2, 1, 2, 12.50, 'Normal', '2024/2025', 2),
(3, 1, 3, 9.00, 'Normal', '2023/2024', NULL),
(4, 1, 4, 17.00, 'Normal', '2023/2024', NULL),
(5, 1, 6, 8.50, 'Normal', '2023/2024', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `pautas`
--

CREATE TABLE `pautas` (
  `id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `ano_letivo` varchar(20) DEFAULT NULL,
  `epoca` enum('Normal','Recurso','Especial') DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `criado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pautas`
--

INSERT INTO `pautas` (`id`, `disciplina_id`, `ano_letivo`, `epoca`, `data_criacao`, `criado_por`) VALUES
(1, 1, '2024/2025', 'Normal', '2026-03-20 09:28:31', 3),
(2, 2, '2024/2025', 'Normal', '2026-03-20 09:28:31', 3);

-- --------------------------------------------------------

--
-- Estrutura da tabela `pedidos_matricula`
--

CREATE TABLE `pedidos_matricula` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) DEFAULT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `ano_letivo` varchar(20) DEFAULT NULL,
  `estado` enum('Pendente','Aprovado','Rejeitado') DEFAULT 'Pendente',
  `data_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL,
  `funcionario_id` int(11) DEFAULT NULL,
  `data_decisao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pedidos_matricula`
--

INSERT INTO `pedidos_matricula` (`id`, `aluno_id`, `curso_id`, `ano_letivo`, `estado`, `data_pedido`, `observacoes`, `funcionario_id`, `data_decisao`) VALUES
(1, 1, 1, '2024/2025', 'Aprovado', '2026-03-20 09:28:31', NULL, 3, '2026-03-20 09:28:31'),
(2, 5, 1, '2024/2025', 'Pendente', '2026-03-20 09:28:31', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `perfis`
--

CREATE TABLE `perfis` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `perfis`
--

INSERT INTO `perfis` (`id`, `nome`) VALUES
(1, 'Aluno'),
(2, 'Admin'),
(3, 'Funcionário'),
(4, 'Gestor Pedagógico');

-- --------------------------------------------------------

--
-- Estrutura da tabela `plano_estudos`
--

CREATE TABLE `plano_estudos` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `disciplina_id` int(11) DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `semestre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `plano_estudos`
--

INSERT INTO `plano_estudos` (`id`, `curso_id`, `disciplina_id`, `ano`, `semestre`) VALUES
(1, 1, 1, 1, 1),
(2, 1, 2, 1, 1),
(3, 1, 3, 1, 2),
(4, 1, 7, 2, 1),
(5, 1, 8, 2, 2),
(6, 2, 3, 1, 1),
(7, 2, 5, 1, 1),
(8, 2, 6, 1, 2),
(9, 3, 4, 1, 1),
(10, 3, 3, 1, 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizadores`
--

CREATE TABLE `utilizadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `perfil_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `utilizadores`
--

INSERT INTO `utilizadores` (`id`, `nome`, `email`, `password`, `perfil_id`, `created_at`) VALUES
(1, 'João Almeida', 'aluno@ipca.pt', '$2y$10$SffUY18F5iesX8OvrayDC.UZI.UYIB8h8wVd2tpLIgs/GTawYI9ey', 1, '2026-03-20 09:28:30'),
(2, 'Administrador', 'admin@ipca.pt', '$2y$10$SffUY18F5iesX8OvrayDC.UZI.UYIB8h8wVd2tpLIgs/GTawYI9ey', 2, '2026-03-20 09:28:30'),
(3, 'Funcionário SA', 'funcionario@ipca.pt', '$2y$10$SffUY18F5iesX8OvrayDC.UZI.UYIB8h8wVd2tpLIgs/GTawYI9ey', 3, '2026-03-20 09:28:30'),
(4, 'Gestor Pedagógico', 'gestor@ipca.pt', '$2y$10$SffUY18F5iesX8OvrayDC.UZI.UYIB8h8wVd2tpLIgs/GTawYI9ey', 4, '2026-03-20 09:28:30'),
(5, 'Maria Santos', 'maria@ipca.pt', '$2y$10$SffUY18F5iesX8OvrayDC.UZI.UYIB8h8wVd2tpLIgs/GTawYI9ey', 1, '2026-03-20 09:28:30'),
(6, 'Pedro Costa', 'pedro@ipca.pt', '$2y$10$SffUY18F5iesX8OvrayDC.UZI.UYIB8h8wVd2tpLIgs/GTawYI9ey', 1, '2026-03-20 09:28:30');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `validado_por` (`validado_por`);

--
-- Índices para tabela `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `pauta_id` (`pauta_id`);

--
-- Índices para tabela `pautas`
--
ALTER TABLE `pautas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices para tabela `pedidos_matricula`
--
ALTER TABLE `pedidos_matricula`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices para tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `disciplina_id` (`disciplina_id`);

--
-- Índices para tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `perfil_id` (`perfil_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `notas`
--
ALTER TABLE `notas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `pautas`
--
ALTER TABLE `pautas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pedidos_matricula`
--
ALTER TABLE `pedidos_matricula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `alunos`
--
ALTER TABLE `alunos`
  ADD CONSTRAINT `alunos_ibfk_1` FOREIGN KEY (`id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alunos_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `alunos_ibfk_3` FOREIGN KEY (`validado_por`) REFERENCES `utilizadores` (`id`);

--
-- Limitadores para a tabela `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`),
  ADD CONSTRAINT `notas_ibfk_3` FOREIGN KEY (`pauta_id`) REFERENCES `pautas` (`id`);

--
-- Limitadores para a tabela `pautas`
--
ALTER TABLE `pautas`
  ADD CONSTRAINT `pautas_ibfk_1` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`),
  ADD CONSTRAINT `pautas_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `utilizadores` (`id`);

--
-- Limitadores para a tabela `pedidos_matricula`
--
ALTER TABLE `pedidos_matricula`
  ADD CONSTRAINT `pedidos_matricula_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_matricula_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `pedidos_matricula_ibfk_3` FOREIGN KEY (`funcionario_id`) REFERENCES `utilizadores` (`id`);

--
-- Limitadores para a tabela `plano_estudos`
--
ALTER TABLE `plano_estudos`
  ADD CONSTRAINT `plano_estudos_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `plano_estudos_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`);

--
-- Limitadores para a tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD CONSTRAINT `utilizadores_ibfk_1` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
