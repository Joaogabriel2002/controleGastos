<?php
// Arquivo: src/Models/Transacao.php
// VERSÃO CORRIGIDA (Bug da Transação Aninhada)

class Transacao {
    
    private $conn;
    private $tabela = 'transacoes';

    // Construtor
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * MÉTODO INTERNO (PRIVADO): Insere os dados de uma transação.
     * Este método NÃO gerencia beginTransaction/commit.
     * Ele é feito para ser chamado por outros métodos públicos.
     */
    private function _salvarUmaTransacao($dados) {
        $query = "INSERT INTO " . $this->tabela . "
                  (descricao, valor, tipo, data_vencimento, 
                   parcela_atual, parcela_total, 
                   conta_id, categoria_id, pessoa_id)
                  VALUES
                  (:descricao, :valor, :tipo, :data_vencimento, 
                   :parcela_atual, :parcela_total, 
                   :conta_id, :categoria_id, :pessoa_id)";
        
        $total_parcelas = (int)($dados['parcela_total'] ?? 1);
        if ($total_parcelas <= 0) {
            $total_parcelas = 1;
        }

        for ($i = 1; $i <= $total_parcelas; $i++) {
            $stmt = $this->conn->prepare($query);

            $data_vencimento = new DateTime($dados['data_vencimento']);
            if ($i > 1) {
                $data_vencimento->modify("+" . ($i - 1) . " months");
            }
            
            $stmt->bindValue(':descricao', htmlspecialchars(strip_tags($dados['descricao'])));
            $stmt->bindValue(':valor', $dados['valor']);
            $stmt->bindValue(':tipo', htmlspecialchars(strip_tags($dados['tipo'])));
            $stmt->bindValue(':data_vencimento', $data_vencimento->format('Y-m-d'));
            $stmt->bindValue(':parcela_atual', $i);
            $stmt->bindValue(':parcela_total', $total_parcelas);
            $stmt->bindValue(':conta_id', (int)$dados['conta_id']);
            $stmt->bindValue(':categoria_id', (int)$dados['categoria_id']);
            
            $pessoa_id = !empty($dados['pessoa_id']) ? (int)$dados['pessoa_id'] : null;
            $stmt->bindValue(':pessoa_id', $pessoa_id, PDO::PARAM_INT);

            if(!$stmt->execute()) {
                // Se uma parcela falhar, joga uma Exceção para o 'catch' pegar
                throw new Exception("Falha ao inserir parcela " . $i);
            }
        }
    }


    /**
     * MÉTODO PÚBLICO: Salva um Lançamento Normal (Entrada/Saída/Parcelado).
     * Gerencia a transação e chama o _salvarUmaTransacao.
     * @param array $dados Os dados vindos do formulário
     * @return bool
     */
    public function salvar($dados) {
        try {
            $this->conn->beginTransaction();
            $this->_salvarUmaTransacao($dados); // Chama o método interno
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    /**
     * MÉTODO PÚBLICO: Salva uma Transferência (cria duas transações).
     * Gerencia a transação e chama o _salvarUmaTransacao DUAS VEZES.
     * @param array $dados Os dados vindos do formulário
     * @return bool
     */
    public function salvarTransferencia($dados) {
        
        // 1. Busca a categoria "Transferência"
        $categoria_query = "SELECT id, tipo FROM categorias WHERE nome LIKE '%Transferência%' LIMIT 1";
        $cat_stmt = $this->conn->prepare($categoria_query);
        $cat_stmt->execute();
        $categoria_transferencia = $cat_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$categoria_transferencia) {
            // Se o usuário não criou a categoria "Transferência"
            return false; 
        }

        // 2. Prepara os dados de Saída
        $dados_saida = $dados;
        $dados_saida['tipo'] = 'saida';
        $dados_saida['categoria_id'] = $categoria_transferencia['id'];
        $dados_saida['parcela_total'] = 1;
        $dados_saida['pessoa_id'] = null;
        
        // 3. Prepara os dados de Entrada
        $dados_entrada = $dados;
        $dados_entrada['tipo'] = 'entrada';
        $dados_entrada['categoria_id'] = $categoria_transferencia['id'];
        $dados_entrada['conta_id'] = $dados['destino_conta_id'];
        $dados_entrada['parcela_total'] = 1; 
        $dados_entrada['pessoa_id'] = null;

        // 4. Executa a transação
        try {
            $this->conn->beginTransaction();
            
            // Salva a Saída (usando o método interno)
            $this->_salvarUmaTransacao($dados_saida);
            
            // Salva a Entrada (usando o método interno)
            $this->_salvarUmaTransacao($dados_entrada);
            
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Se algo deu errado
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    // --- MÉTODOS DE BUSCA E ATUALIZAÇÃO (Sem alterações) ---

    public function buscarTodasComDetalhes() {
        $query = "SELECT 
                    t.id, t.descricao, t.valor, t.tipo, t.data_vencimento,
                    t.data_efetivacao, t.parcela_atual, t.parcela_total,
                    c.nome as nome_categoria, co.nome as nome_conta
                  FROM " . $this->tabela . " t
                  LEFT JOIN categorias c ON t.categoria_id = c.id
                  LEFT JOIN contas co ON t.conta_id = co.id
                  ORDER BY t.data_vencimento DESC LIMIT 50";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function excluir($id) {
        $query = "DELETE FROM " . $this->tabela . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) { return true; }
        return false;
    }

    public function marcarComoEfetivado($id) {
        $query = "UPDATE " . $this->tabela . " SET data_efetivacao = data_vencimento WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) { return true; }
        return false;
    }

    public function buscarTotaisEfetivadosPorConta($data_fim = null, $tipo_filtro = 'trabalho') {
        $query = "SELECT 
                    t.conta_id, t.tipo, SUM(t.valor) as total_movimentado
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE t.data_efetivacao IS NOT NULL";
        
        if ($data_fim !== null) {
            $query .= " AND t.data_efetivacao <= :data_fim";
        }
        if ($tipo_filtro == 'trabalho') {
            $query .= " AND c.is_economia = 0 ";
        } elseif ($tipo_filtro == 'economia') {
            $query .= " AND c.is_economia = 1 ";
        }
        $query .= " GROUP BY t.conta_id, t.tipo";
        
        $stmt = $this->conn->prepare($query);
        if ($data_fim !== null) {
            $stmt->bindParam(':data_fim', $data_fim);
        }
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totaisPorConta = [];
        foreach ($resultados as $resultado) {
            $contaId = $resultado['conta_id'];
            $tipo = $resultado['tipo'];
            $total = $resultado['total_movimentado'];
            if (!isset($totaisPorConta[$contaId])) {
                $totaisPorConta[$contaId] = ['entrada' => 0, 'saida' => 0];
            }
            $totaisPorConta[$contaId][$tipo] = $total;
        }
        return $totaisPorConta;
    }

    public function buscarResumoEfetivadoPorMes($mes, $ano, $tipo_filtro = 'trabalho') {
        $query = "SELECT 
                    t.tipo, SUM(t.valor) as total_efetivado
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE 
                    t.data_efetivacao IS NOT NULL 
                    AND MONTH(t.data_efetivacao) = :mes
                    AND YEAR(t.data_efetivacao) = :ano
                    AND c.is_economia = " . ($tipo_filtro == 'trabalho' ? '0' : '1');
        $query .= " GROUP BY t.tipo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resumo = ['total_recebido' => 0, 'total_pago' => 0];
        foreach ($resultados as $resultado) {
            if ($resultado['tipo'] == 'entrada') {
                $resumo['total_recebido'] = $resultado['total_efetivado'];
            } else if ($resultado['tipo'] == 'saida') {
                $resumo['total_pago'] = $resultado['total_efetivado'];
            }
        }
        return $resumo;
    }

    public function buscarResumoPendentesPorMes($mes, $ano, $tipo_filtro = 'trabalho') {
        $query = "SELECT 
                    t.tipo, SUM(t.valor) as total_pendente
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE 
                    t.data_efetivacao IS NULL 
                    AND MONTH(t.data_vencimento) = :mes
                    AND YEAR(t.data_vencimento) = :ano
                    AND c.is_economia = " . ($tipo_filtro == 'trabalho' ? '0' : '1');
        $query .= " GROUP BY t.tipo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resumo = ['total_a_pagar' => 0, 'total_a_receber' => 0];
        foreach ($resultados as $resultado) {
            if ($resultado['tipo'] == 'saida') {
                $resumo['total_a_pagar'] = $resultado['total_pendente'];
            } else if ($resultado['tipo'] == 'entrada') {
                $resumo['total_a_receber'] = $resultado['total_pendente'];
            }
        }
        return $resumo;
    }

    public function buscarTransacoesFiltradas($filtros = []) {
        $query = "SELECT 
                    t.id, t.descricao, t.valor, t.tipo, 
                    t.data_vencimento, t.data_efetivacao,
                    t.parcela_atual, t.parcela_total,
                    c.nome as nome_categoria,
                    co.nome as nome_conta,
                    p.nome as nome_pessoa
                  FROM " . $this->tabela . " t
                  LEFT JOIN categorias c ON t.categoria_id = c.id
                  LEFT JOIN contas co ON t.conta_id = co.id
                  LEFT JOIN pessoas p ON t.pessoa_id = p.id";
        
        $where_clauses = [];
        $params = [];
        
        if (!empty($filtros['filtro_descricao'])) {
            $where_clauses[] = "t.descricao LIKE :descricao";
            $params[':descricao'] = '%' . $filtros['filtro_descricao'] . '%';
        }
        if (!empty($filtros['filtro_conta'])) {
            $where_clauses[] = "t.conta_id = :conta_id";
            $params[':conta_id'] = $filtros['filtro_conta'];
        }
        if (!empty($filtros['filtro_tipo'])) {
            $where_clauses[] = "t.tipo = :tipo";
            $params[':tipo'] = $filtros['filtro_tipo'];
        }
        if (!empty($filtros['filtro_categoria'])) {
            $where_clauses[] = "t.categoria_id = :categoria_id";
            $params[':categoria_id'] = $filtros['filtro_categoria'];
        }
        if (!empty($filtros['mes'])) {
            $where_clauses[] = "MONTH(t.data_vencimento) = :mes";
            $params[':mes'] = $filtros['mes'];
        }
        if (!empty($filtros['ano'])) {
            $where_clauses[] = "YEAR(t.data_vencimento) = :ano";
            $params[':ano'] = $filtros['ano'];
        }
        if (!empty($filtros['filtro_status'])) {
            if ($filtros['filtro_status'] == 'pago') {
                $where_clauses[] = "t.data_efetivacao IS NOT NULL";
            } else if ($filtros['filtro_status'] == 'pendente') {
                $where_clauses[] = "t.data_efetivacao IS NULL";
            }
        }
        
        if (count($where_clauses) > 0) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY t.data_vencimento DESC LIMIT 200";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            if (is_numeric($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPendentesAgrupadosPorDia($mes, $ano) {
        $query = "SELECT 
                    DAY(data_vencimento) as dia, tipo, SUM(valor) as total_dia
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE 
                    t.data_efetivacao IS NULL 
                    AND MONTH(t.data_vencimento) = :mes
                    AND YEAR(t.data_vencimento) = :ano
                    AND c.is_economia = 0";
        $query .= " GROUP BY DAY(data_vencimento), tipo ORDER BY dia ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dias = [];
        foreach ($resultados as $resultado) {
            $dia = (int)$resultado['dia'];
            $tipo = $resultado['tipo'];
            $total = $resultado['total_dia'];
            if (!isset($dias[$dia])) {
                $dias[$dia] = ['entrada' => 0, 'saida' => 0];
            }
            $dias[$dia][$tipo] = $total;
        }
        return $dias;
    }

    public function buscarTotalPendenteGeral($contexto = 'trabalho') {
        $query = "SELECT SUM(t.valor) as total_pendente
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE t.data_efetivacao IS NULL
                    AND t.tipo = 'saida'
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1');
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total_pendente'] ?? 0;
    }

    public function buscarNumeroParcelasPendentes($contexto = 'trabalho') {
        $query = "SELECT COUNT(t.id) as total_parcelas
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE t.data_efetivacao IS NULL
                    AND t.tipo = 'saida'
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1');
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total_parcelas'] ?? 0;
    }

    public function buscarGastoPorCategoria($contexto = 'trabalho', $limite = 5, $tipo_dado = 'realizado') {
        $status_check = ($tipo_dado == 'realizado') ? 't.data_efetivacao IS NOT NULL' : 't.data_efetivacao IS NULL';
        $query = "SELECT 
                    cat.nome, SUM(t.valor) as total_gasto
                  FROM " . $this->tabela . " t
                  INNER JOIN categorias cat ON t.categoria_id = cat.id
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE $status_check
                    AND t.tipo = 'saida'
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1') . "
                  GROUP BY cat.nome
                  ORDER BY total_gasto DESC
                  LIMIT " . (int)$limite;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function buscarTotalPendentePorDescricao($contexto = 'trabalho') {
        $query = "SELECT 
                    t.descricao, SUM(t.valor) as total_pendente, COUNT(t.id) as total_parcelas
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE t.data_efetivacao IS NULL
                    AND t.tipo = 'saida'
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1') . "
                  GROUP BY t.descricao
                  HAVING total_pendente > 0
                  ORDER BY total_pendente DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarTotalPendentePorMesEConta($contexto = 'trabalho') {
        $query = "SELECT 
                    YEAR(t.data_vencimento) as ano,
                    MONTH(t.data_vencimento) as mes,
                    co.nome as nome_conta,
                    SUM(t.valor) as total_pendente
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  INNER JOIN contas co ON t.conta_id = co.id
                  WHERE t.data_efetivacao IS NULL
                    AND t.tipo = 'saida'
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1') . "
                  GROUP BY ano, mes, t.conta_id, co.nome
                  HAVING total_pendente > 0
                  ORDER BY ano ASC, mes ASC, total_pendente DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function buscarTotalPendentePorCategoria($contexto = 'trabalho') {
        $query = "SELECT 
                    cat.nome as nome_categoria, SUM(t.valor) as total_pendente, COUNT(t.id) as total_parcelas
                  FROM " . $this->tabela . " t
                  INNER JOIN categorias cat ON t.categoria_id = cat.id
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE t.data_efetivacao IS NULL
                    AND t.tipo = 'saida'
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1') . "
                  GROUP BY cat.id, cat.nome
                  HAVING total_pendente > 0
                  ORDER BY total_pendente DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarFluxoCaixaUltimosMeses($contexto = 'trabalho', $meses = 6, $tipo_dado = 'realizado') {
        
        if ($tipo_dado == 'realizado') {
            $date_column = 't.data_efetivacao';
            $status_check = 't.data_efetivacao IS NOT NULL';
            $date_range_check = "t.data_efetivacao >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01')";
            $order_by = "ano ASC, mes ASC";
        } else { // 'previsto'
            $date_column = 't.data_vencimento';
            $status_check = 't.data_efetivacao IS NULL';
            $date_range_check = "t.data_vencimento >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND t.data_vencimento < DATE_FORMAT(CURDATE() + INTERVAL 6 MONTH, '%Y-%m-01')";
            $order_by = "ano ASC, mes ASC";
        }

        $query = "SELECT 
                    YEAR($date_column) as ano, MONTH($date_column) as mes, t.tipo, SUM(t.valor) as total
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE $status_check
                    AND $date_range_check
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1') . "
                  GROUP BY ano, mes, t.tipo
                  ORDER BY $order_by";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $dados_formatados = [];
        $meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        if ($tipo_dado == 'realizado') {
            for ($i = $meses - 1; $i >= 0; $i--) {
                $timestamp = strtotime(date('Y-m-01') . " -$i months");
                $mes_ano_key = date('Y-m', $timestamp);
                $labels[] = $meses_nomes[date('n', $timestamp) - 1] . '/' . date('y', $timestamp);
                $dados_formatados[$mes_ano_key] = ['entrada' => 0, 'saida' => 0];
            }
        } else {
            for ($i = 0; $i < $meses; $i++) {
                $timestamp = strtotime(date('Y-m-01') . " +$i months");
                $mes_ano_key = date('Y-m', $timestamp);
                $labels[] = $meses_nomes[date('n', $timestamp) - 1] . '/' . date('y', $timestamp);
                $dados_formatados[$mes_ano_key] = ['entrada' => 0, 'saida' => 0];
            }
        }
        
        foreach ($resultados as $row) {
            $mes_ano_key = sprintf('%04d-%02d', $row['ano'], $row['mes']);
            if (isset($dados_formatados[$mes_ano_key])) {
                if ($row['tipo'] == 'entrada') {
                    $dados_formatados[$mes_ano_key]['entrada'] = (float)$row['total'];
                } else {
                    $dados_formatados[$mes_ano_key]['saida'] = (float)$row['total'];
                }
            }
        }
        
        $data_entrada = [];
        $data_saida = [];
        foreach ($dados_formatados as $dados_mes) {
            $data_entrada[] = $dados_mes['entrada'];
            $data_saida[] = $dados_mes['saida'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Entradas', 'data' => $data_entrada, 'backgroundColor' => 'rgba(22, 163, 74, 0.7)'],
                ['label' => 'Saídas', 'data' => $data_saida, 'backgroundColor' => 'rgba(220, 38, 38, 0.7)']
            ]
        ];
    }
}