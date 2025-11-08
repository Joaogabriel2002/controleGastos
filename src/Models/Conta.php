<?php
// Arquivo: src/Models/Conta.php
// ATUALIZADO para 'tipo_pote'

class Conta {
    
    private $conn;
    private $tabela = 'contas';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Busca contas, opcionalmente filtrando por tipo de pote.
     * @param string $tipo_pote 'todas', 'trabalho', 'economia', 'emprestimos'
     */
    public function buscarTodas($tipo_pote = 'todas') {
        $query = "SELECT id, nome, saldo_inicial, tipo_conta, tipo_pote
                  FROM " . $this->tabela;
        
        // Filtra pelo tipo de pote, se não for 'todas'
        if ($tipo_pote != 'todas') {
            $query .= " WHERE tipo_pote = :tipo_pote ";
        }
        
        $query .= " ORDER BY nome ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($tipo_pote != 'todas') {
            $stmt->bindParam(':tipo_pote', $tipo_pote);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Salva uma nova conta.
     */
    public function salvar($nome, $saldo_inicial, $tipo_conta, $tipo_pote = 'trabalho') {
        $query = "INSERT INTO " . $this->tabela . " (nome, saldo_inicial, tipo_conta, tipo_pote) 
                  VALUES (:nome, :saldo_inicial, :tipo_conta, :tipo_pote)";
        
        $stmt = $this->conn->prepare($query);
        
        $nome = htmlspecialchars(strip_tags($nome));
        $tipo_conta = htmlspecialchars(strip_tags($tipo_conta));
        
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':saldo_inicial', $saldo_inicial);
        $stmt->bindParam(':tipo_conta', $tipo_conta);
        $stmt->bindParam(':tipo_pote', $tipo_pote);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Busca uma única conta pelo seu ID.
     */
    public function buscarPorId($id) {
        $query = "SELECT id, nome, saldo_inicial, tipo_conta, tipo_pote 
                  FROM " . $this->tabela . " 
                  WHERE id = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza uma conta existente.
     */
    public function atualizar($id, $nome, $saldo_inicial, $tipo_conta, $tipo_pote = 'trabalho') {
        $query = "UPDATE " . $this->tabela . " 
                  SET nome = :nome, saldo_inicial = :saldo_inicial, 
                      tipo_conta = :tipo_conta, tipo_pote = :tipo_pote 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $id = htmlspecialchars(strip_tags($id));
        $nome = htmlspecialchars(strip_tags($nome));
        $tipo_conta = htmlspecialchars(strip_tags($tipo_conta));
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':saldo_inicial', $saldo_inicial);
        $stmt->bindParam(':tipo_conta', $tipo_conta);
        $stmt->bindParam(':tipo_pote', $tipo_pote);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Exclui uma conta do banco de dados.
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
}