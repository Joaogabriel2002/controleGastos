<?php
// Arquivo: src/Models/Pessoa.php

class Pessoa {
    
    private $conn;
    private $tabela = 'pessoas';

    // Construtor
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Busca todas as pessoas no banco de dados.
     * @return array Lista de pessoas
     */
    public function buscarTodas() {
        $query = "SELECT id, nome FROM " . $this->tabela . " ORDER BY nome ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Salva uma nova pessoa no banco de dados.
     * @param string $nome
     * @return bool
     */
    public function salvar($nome) {
        $query = "INSERT INTO " . $this->tabela . " (nome) VALUES (:nome)";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpa os dados
        $nome = htmlspecialchars(strip_tags($nome));
        
        // Binda os valores
        $stmt->bindParam(':nome', $nome);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Exclui uma pessoa do banco de dados.
     * @param int $id
     * @return bool
     */
    public function excluir($id) {
        // Lembre-se: A 'constraint' na tabela transacoes 
        // foi definida como ON DELETE SET NULL.
        // Isso significa que excluir uma pessoa NÃO apaga a transação,
        // apenas deixa o campo pessoa_id como NULL nela.
        
        $query = "DELETE FROM " . $this->tabela . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}