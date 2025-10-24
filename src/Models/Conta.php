<?php
// Arquivo: src/Models/Conta.php

class Conta {
    
    private $conn;
    private $tabela = 'contas';

    // Construtor
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Busca todas as contas no banco de dados.
     * @return array Lista de contas
     */
    public function buscarTodas() {
        $query = "SELECT id, nome, saldo_inicial, tipo_conta
                  FROM " . $this->tabela . " 
                  ORDER BY nome ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Salva uma nova conta no banco de dados.
     * @param string $nome
     * @param float $saldo_inicial
     * @param string|null $tipo_conta
     * @return bool
     */
    public function salvar($nome, $saldo_inicial, $tipo_conta) {
        $query = "INSERT INTO " . $this->tabela . " (nome, saldo_inicial, tipo_conta) 
                  VALUES (:nome, :saldo_inicial, :tipo_conta)";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpa os dados
        $nome = htmlspecialchars(strip_tags($nome));
        $tipo_conta = htmlspecialchars(strip_tags($tipo_conta));
        // O saldo_inicial é um número, o PDO lida com ele
        
        // Binda os valores
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':saldo_inicial', $saldo_inicial);
        $stmt->bindParam(':tipo_conta', $tipo_conta);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Exclui uma conta do banco de dados.
     * @param int $id
     * @return bool
     */
    public function excluir($id) {
        $query = "DELETE FROM " . $this->tabela . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // ... (cole isso no final da classe Conta, antes do '}') ...

    /**
     * Busca uma única conta pelo seu ID.
     * @param int $id O ID da conta
     * @return array|false Dados da conta ou false se não encontrar
     */
    public function buscarPorId($id) {
        $query = "SELECT id, nome, saldo_inicial, tipo_conta 
                  FROM " . $this->tabela . " 
                  WHERE id = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza uma conta existente no banco de dados.
     * @param int $id
     * @param string $nome
     * @param float $saldo_inicial
     * @param string|null $tipo_conta
     * @return bool
     */
    public function atualizar($id, $nome, $saldo_inicial, $tipo_conta) {
        $query = "UPDATE " . $this->tabela . " 
                  SET nome = :nome, saldo_inicial = :saldo_inicial, tipo_conta = :tipo_conta 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpa os dados
        $id = htmlspecialchars(strip_tags($id));
        $nome = htmlspecialchars(strip_tags($nome));
        $tipo_conta = htmlspecialchars(strip_tags($tipo_conta));
        
        // Binda os valores
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':saldo_inicial', $saldo_inicial);
        $stmt->bindParam(':tipo_conta', $tipo_conta);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}