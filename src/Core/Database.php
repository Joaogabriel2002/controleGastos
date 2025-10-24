<?php
// Arquivo: src/Core/Database.php

class Database {
    
    // Propriedades da conexão
    private $host = DB_HOST;
    private $port = DB_PORT;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    
    // A conexão PDO
    private $conn;

    // Método que retorna a conexão
    public function getConnection() {
        // Se a conexão já existir, retorna ela
        if ($this->conn) {
            return $this->conn;
        }

        // Se não, cria uma nova conexão
        // DSN (Data Source Name)
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Configura o PDO para lançar exceções em caso de erro
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Configura para buscar resultados como arrays associativos
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Em caso de falha, exibe o erro e "mata" a aplicação
            die("Erro de Conexão: " . $e->getMessage());
        }

        return $this->conn;
    }
}