-- ============================================================
-- Sistema de Gerenciamento de Biblioteca Escolar
-- Execute este script para criar e popular o banco de dados
-- ============================================================

CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca;

-- ── Tabela de Usuários (Alunos / Professores) ────────────────
CREATE TABLE IF NOT EXISTS tbusuarios (
  idusuario INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  matricula VARCHAR(30) UNIQUE,
  tipo ENUM('aluno','professor','funcionario') DEFAULT 'aluno',
  curso VARCHAR(100),
  serie VARCHAR(20),
  turma VARCHAR(20),
  telefone VARCHAR(20),
  email VARCHAR(150),
  status ENUM('ativo','inativo') DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Tabela de Livros ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tblivros (
  idlivro INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  autor VARCHAR(150),
  editora VARCHAR(100),
  ano INT,
  isbn VARCHAR(20),
  categoria VARCHAR(80),
  quantidade INT DEFAULT 1,
  quantidadedisponivel INT DEFAULT 1,
  estante VARCHAR(20),
  prateleira VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Tabela de Empréstimos ────────────────────────────────────
CREATE TABLE IF NOT EXISTS tbemprestimos (
  idemprestimo INT AUTO_INCREMENT PRIMARY KEY,
  idusuario INT NOT NULL,
  idlivro INT NOT NULL,
  dataemprestimo DATE NOT NULL,
  dataprevista DATE NOT NULL,
  datadevolucao DATE DEFAULT NULL,
  status ENUM('ativo','atrasado','devolvido') DEFAULT 'ativo',
  FOREIGN KEY (idusuario) REFERENCES tbusuarios(idusuario) ON DELETE CASCADE,
  FOREIGN KEY (idlivro) REFERENCES tblivros(idlivro) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Tabela de Administradores ────────────────────────────────
CREATE TABLE IF NOT EXISTS tbadministradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  senha VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Dados de Exemplo ─────────────────────────────────────────

-- Admin padrão (senha: admin123)
INSERT INTO tbadministradores (nome, senha) VALUES
('Maria Fontes', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Usuários
INSERT INTO tbusuarios (nome, matricula, tipo, curso, serie, turma, telefone, email, status) VALUES
('Ana Lima',         '2026001', 'aluno', 'Ensino Médio', '3', 'A', '(11) 91234-5678', 'ana.lima@escola.edu.br', 'ativo'),
('Bruno Souza',      '2026002', 'aluno', 'Ensino Médio', '2', 'B', '(11) 92345-6789', 'bruno.souza@escola.edu.br', 'ativo'),
('Carla Mendes',     '2026003', 'aluno', 'Ensino Fundamental', '8', 'A', '(11) 93456-7890', 'carla.mendes@escola.edu.br', 'ativo'),
('Diego Ferreira',   '2026004', 'aluno', 'Ensino Médio', '3', 'B', '(11) 94567-8901', 'diego.ferreira@escola.edu.br', 'ativo'),
('Eduarda Costa',    '2026005', 'aluno', 'Ensino Fundamental', '7', 'A', '(11) 95678-9012', 'eduarda.costa@escola.edu.br', 'ativo'),
('Felipe Alves',     '2026006', 'aluno', 'Ensino Médio', '1', 'A', '(11) 96789-0123', 'felipe.alves@escola.edu.br', 'ativo'),
('Gabriela Nunes',   '2026007', 'aluno', 'Ensino Fundamental', '9', 'B', '(11) 97890-1234', 'gabriela.nunes@escola.edu.br', 'ativo');

-- Livros
INSERT INTO tblivros (titulo, autor, editora, ano, isbn, categoria, quantidade, quantidadedisponivel, estante, prateleira) VALUES
('Dom Casmurro',                'Machado de Assis',       'Ática',       1899, '9788508100002', 'Romance',      5, 3, 'A', '1'),
('O Cortiço',                   'Aluísio Azevedo',        'FTD',         1890, '9788532250001', 'Naturalismo',  4, 4, 'A', '2'),
('Grande Sertão: Veredas',      'João Guimarães Rosa',    'Nova Aguilar', 1956, '9788520928003', 'Regionalismo', 3, 1, 'B', '1'),
('A Moreninha',                 'Joaquim Manuel de Macedo','Saraiva',     1844, '9788502001009', 'Romance',      2, 2, 'B', '2'),
('Vidas Secas',                 'Graciliano Ramos',       'Record',      1938, '9788501011506', 'Regionalismo', 6, 5, 'C', '1'),
('Iracema',                     'José de Alencar',        'Martin Claret',1865, '9788572325202', 'Indianismo',   3, 0, 'C', '2'),
('O Guarani',                   'José de Alencar',        'Ática',       1857, '9788508000019', 'Indianismo',   4, 2, 'D', '1'),
('Capitães da Areia',           'Jorge Amado',            'Companhia das Letras',1937,'9788535924022','Romance', 5, 3, 'D', '2');

-- Empréstimos
INSERT INTO tbemprestimos (idusuario, idlivro, dataemprestimo, dataprevista, datadevolucao, status) VALUES
(1, 1, '2026-09-28', '2026-10-12', NULL, 'ativo'),
(2, 3, '2026-10-01', '2026-10-15', NULL, 'ativo'),
(5, 5, '2026-09-15', '2026-09-29', NULL, 'atrasado'),
(4, 6, '2026-09-20', '2026-10-04', NULL, 'atrasado'),
(7, 7, '2026-09-25', '2026-10-09', NULL, 'ativo'),
(1, 8, '2026-09-10', '2026-09-24', '2026-09-24', 'devolvido'),
(5, 4, '2026-10-05', '2026-10-19', NULL, 'ativo');
