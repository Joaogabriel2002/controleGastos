<?php
// Arquivo: src/Models/Categoria.php

class Categoria {
    
    // Propriedade para guardar a conexão com o banco
    private $conn;
    private $tabela = 'categorias'; // Nome da tabela

    // O construtor recebe a conexão PDO quando a classe é instanciada
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Busca todas as categorias no banco de dados.
     * @return array Lista de categorias
     */
    public function buscarTodas() {
        // Query SQL para selecionar tudo
        $query = "SELECT id, nome, tipo FROM " . $this->tabela . " ORDER BY nome ASC";
        
        // Prepara a query
        $stmt = $this->conn->prepare($query);
        
        // Executa a query
        $stmt->execute();
        
        // Retorna todos os resultados como um array associativo
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Salva uma nova categoria no banco de dados.
     * @param string $nome O nome da categoria
     * @param string $tipo 'entrada' ou 'saida'
     * @return bool True se foi sucesso, False se falhou
     */
    public function salvar($nome, $tipo) {
        // Query SQL para inserir dados
        // Usamos "named parameters" (:nome, :tipo) para prevenir SQL Injection
        $query = "INSERT INTO " . $this->tabela . " (nome, tipo) VALUES (:nome, :tipo)";
        
        // Prepara a query
        $stmt = $this->conn->prepare($query);
        
        // "Limpa" os dados (remove tags HTML, etc.)
        $nome = htmlspecialchars(strip_tags($nome));
        $tipo = htmlspecialchars(strip_tags($tipo));
        
        // "Binda" (liga) os valores da variável PHP com os parâmetros da query
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':tipo', $tipo);
        
        // Executa a query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    /**
     * Exclui uma categoria do banco de dados.
     * @param int $id O ID da categoria a ser excluída
     * @return bool True se foi sucesso, False se falhou
     */
    public function excluir($id) {
        // Query SQL para deletar
        $query = "DELETE FROM " . $this->tabela . " WHERE id = :id";
        
        // Prepara a query
        $stmt = $this->conn->prepare($query);
        
        // Limpa e binda o ID
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(':id', $id);
        
        // Executa a query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}