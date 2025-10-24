-- Define o banco de dados a ser usado. 
-- Se ele não existir, crie-o primeiro com: CREATE DATABASE seu_controle_gastos;
-- USE seu_controle_gastos;

-- ---
-- Tabela 1: contas
-- Armazena onde o dinheiro está (bancos, carteira, etc.)
-- ---
CREATE TABLE `contas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL COMMENT 'Ex: Carteira, Conta Nubank, Itaú',
  `saldo_inicial` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tipo_conta` VARCHAR(50) NULL COMMENT 'Ex: carteira, banco, investimento',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---
-- Tabela 2: categorias
-- Classifica as entradas e saídas (Ex: Alimentação, Salário)
-- ---
CREATE TABLE `categorias` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL COMMENT 'Ex: Moradia, Alimentação, Lazer, Salário',
  `tipo` ENUM('entrada', 'saida') NOT NULL COMMENT 'Define se a categoria soma ou subtrai',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---
-- Tabela 3: pessoas
-- Opcional, mas útil para rastrear dívidas a pagar ou receber
-- ---
CREATE TABLE `pessoas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL COMMENT 'Ex: João Silva, Empresa X',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---
-- Tabela 4: transacoes
-- O coração do sistema, registra cada movimentação
-- ---
CREATE TABLE `transacoes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `descricao` VARCHAR(255) NOT NULL COMMENT 'Ex: Compra no mercado, Salário Mês 10',
  `valor` DECIMAL(10, 2) NOT NULL COMMENT 'Valor da transação (sempre positivo)',
  `tipo` ENUM('entrada', 'saida') NOT NULL COMMENT 'Redundante com categoria, mas útil para cálculos rápidos',
  `data_vencimento` DATE NOT NULL COMMENT 'Data que a conta vence ou que o dinheiro entra',
  `data_efetivacao` DATE NULL COMMENT 'Data que foi PAGO/RECEBIDO. NULO = pendente',
  
  `parcela_atual` INT NOT NULL DEFAULT 1,
  `parcela_total` INT NOT NULL DEFAULT 1,

  -- Chaves Estrangeiras (Relacionamentos)
  `conta_id` INT NOT NULL COMMENT 'De qual conta saiu ou entrou o dinheiro',
  `categoria_id` INT NOT NULL COMMENT 'Qual a classificação desse gasto/ganho',
  `pessoa_id` INT NULL COMMENT 'Quem está devendo ou para quem você deve (opcional)',
  
  PRIMARY KEY (`id`),
  
  -- Definição das chaves estrangeiras
  CONSTRAINT `fk_transacoes_conta`
    FOREIGN KEY (`conta_id`)
    REFERENCES `contas` (`id`)
    ON DELETE RESTRICT -- Impede excluir uma conta que tenha transações
    ON UPDATE CASCADE,
    
  CONSTRAINT `fk_transacoes_categoria`
    FOREIGN KEY (`categoria_id`)
    REFERENCES `categorias` (`id`)
    ON DELETE RESTRICT -- Impede excluir uma categoria que tenha transações
    ON UPDATE CASCADE,
    
  CONSTRAINT `fk_transacoes_pessoa`
    FOREIGN KEY (`pessoa_id`)
    REFERENCES `pessoas` (`id`)
    ON DELETE SET NULL -- Se excluir a pessoa, a transação fica sem pessoa
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;