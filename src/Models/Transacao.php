<?php
// Arquivo: src/Models/Transacao.php

class Transacao {
    
    private $conn;
    private $tabela = 'transacoes';

    // Construtor
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Salva uma nova transação (e suas parcelas, se houver).
     * @param array $dados Os dados vindos do formulário
     * @return bool
     */
    public function salvar($dados) {
        // Query base para inserção
        $query = "INSERT INTO " . $this->tabela . "
                  (descricao, valor, tipo, data_vencimento, 
                   parcela_atual, parcela_total, 
                   conta_id, categoria_id, pessoa_id)
                  VALUES
                  (:descricao, :valor, :tipo, :data_vencimento, 
                   :parcela_atual, :parcela_total, 
                   :conta_id, :categoria_id, :pessoa_id)";
        
        try {
            // Inicia uma transação (modo "tudo ou nada")
            // Se uma parcela falhar, nenhuma é salva.
            $this->conn->beginTransaction();

            $total_parcelas = (int)$dados['parcela_total'];
            if ($total_parcelas <= 0) {
                $total_parcelas = 1;
            }

            for ($i = 1; $i <= $total_parcelas; $i++) {
                // Prepara a query para cada parcela
                $stmt = $this->conn->prepare($query);

                // Calcula a data de vencimento
                // Adiciona $i-1 meses à data de vencimento inicial
                $data_vencimento = new DateTime($dados['data_vencimento']);
                if ($i > 1) {
                    $data_vencimento->modify("+" . ($i - 1) . " months");
                }
                
                // Limpa e "binda" os dados
                $stmt->bindValue(':descricao', htmlspecialchars(strip_tags($dados['descricao'])));
                $stmt->bindValue(':valor', $dados['valor']);
                $stmt->bindValue(':tipo', htmlspecialchars(strip_tags($dados['tipo'])));
                $stmt->bindValue(':data_vencimento', $data_vencimento->format('Y-m-d'));
                $stmt->bindValue(':parcela_atual', $i);
                $stmt->bindValue(':parcela_total', $total_parcelas);
                $stmt->bindValue(':conta_id', (int)$dados['conta_id']);
                $stmt->bindValue(':categoria_id', (int)$dados['categoria_id']);
                
                // Trata o campo opcional pessoa_id
                $pessoa_id = !empty($dados['pessoa_id']) ? (int)$dados['pessoa_id'] : null;
                $stmt->bindValue(':pessoa_id', $pessoa_id, PDO::PARAM_INT);

                $stmt->execute();
            }

            // Se tudo deu certo, "commita" (salva) as mudanças
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Se algo deu errado, "rollback" (desfaz) tudo
            $this->conn->rollBack();
            // Opcional: registrar o erro $e->getMessage() em um log
            return false;
        }
    }

    /**
     * Busca todas as transações com detalhes (nomes das contas/categorias).
     * @return array Lista de transações
     */
    public function buscarTodasComDetalhes() {
        $query = "SELECT 
                    t.id, 
                    t.descricao, 
                    t.valor, 
                    t.tipo, 
                    t.data_vencimento,
                    t.data_efetivacao,
                    t.parcela_atual,
                    t.parcela_total,
                    c.nome as nome_categoria,
                    co.nome as nome_conta
                  FROM 
                    " . $this->tabela . " t
                  LEFT JOIN 
                    categorias c ON t.categoria_id = c.id
                  LEFT JOIN 
                    contas co ON t.conta_id = co.id
                  ORDER BY 
                    t.data_vencimento DESC
                  LIMIT 50"; // Limita para não sobrecarregar
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Exclui uma transação (e idealmente suas "irmãs" de parcela, mas vamos simplificar)
     * @param int $id
     * @return bool
     */
    public function excluir($id) {
        // Simples: exclui apenas o ID clicado
        // Avançado: deveria excluir todas com a mesma "chave de parcela"
        $query = "DELETE FROM " . $this->tabela . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // ... (cole isso no final de src/Models/Transacao.php, antes do último '}') ...

    /**
     * Busca os totais de entradas e saídas agrupados por conta.
     * Isso nos diz o total movimentado em CADA conta.
     * @return array [conta_id => ['entrada' => valor, 'saida' => valor]]
     */
    /**
     * Busca os totais de entradas e saídas EFETIVADAS (pagas/recebidas) 
     * agrupados por conta, opcionalmente até uma data limite.
     * * @param string|null $data_fim Data no formato 'YYYY-MM-DD'. 
     * Se nulo, busca todos (Geral).
     * @return array [conta_id => ['entrada' => valor, 'saida' => valor]]
     */
    public function buscarTotaisEfetivadosPorConta($data_fim = null, $tipo_filtro = 'trabalho') {
        $query = "SELECT 
                    t.conta_id, t.tipo, SUM(t.valor) as total_movimentado
                  FROM 
                    " . $this->tabela . " t
                  INNER JOIN 
                    contas c ON t.conta_id = c.id
                  WHERE 
                    t.data_efetivacao IS NOT NULL";
        
        // Filtro de data (para o "Saldo no Mês")
        if ($data_fim !== null) {
            $query .= " AND t.data_efetivacao <= :data_fim";
        }
        
        // *** O FILTRO DE ECONOMIA ***
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
        
        // ... (o resto da função de reorganizar o array continua igual) ...
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
    /**
     * Busca o resumo de transações PENDENTES (que ainda não foram pagas/recebidas).
     * Usamos o campo 'data_efetivacao' para saber o que está pendente.
     * @return array ['total_a_pagar' => valor, 'total_a_receber' => valor]
     */
    public function buscarResumoPendentes() {
        $query = "SELECT 
                    tipo, 
                    SUM(valor) as total_pendente
                  FROM 
                    " . $this->tabela . "
                  WHERE 
                    data_efetivacao IS NULL 
                    AND data_vencimento >= CURDATE() -- Opcional: só pendentes futuros
                  GROUP BY 
                    tipo";
        
        $stmt = $this->conn->prepare($query);
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
    /**
     * Marca uma transação como efetivada (paga/recebida).
     * Define a data_efetivacao como a data atual do servidor.
     * @param int $id O ID da transação
     * @return bool
     */
    public function marcarComoEfetivado($id) {
        $query = "UPDATE " . $this->tabela . " 
                  SET data_efetivacao = CURDATE() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    // ... (cole isso no final da classe Transacao, antes do '}') ...

    /**
     * Busca o resumo de transações EFETIVADAS (pagas/recebidas) em um mês específico.
     * Isso é o seu FLUXO DE CAIXA do mês.
     * @param int $mes
     * @param int $ano
     * @return array ['total_recebido' => valor, 'total_pago' => valor]
     */
    public function buscarResumoEfetivadoPorMes($mes, $ano, $tipo_filtro = 'trabalho') {
        $query = "SELECT 
                    t.tipo, SUM(t.valor) as total_efetivado
                  FROM 
                    " . $this->tabela . " t
                  INNER JOIN
                    contas c ON t.conta_id = c.id
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
        // ... (o resto da função continua igual) ...
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
    /**
     * Busca o resumo de transações PENDENTES com vencimento em um mês específico.
     * @param int $mes
     * @param int $ano
     * @return array ['total_a_pagar' => valor, 'total_a_receber' => valor]
     */
    public function buscarResumoPendentesPorMes($mes, $ano, $tipo_filtro = 'trabalho') {
        $query = "SELECT 
                    t.tipo, SUM(t.valor) as total_pendente
                  FROM 
                    " . $this->tabela . " t
                  INNER JOIN
                    contas c ON t.conta_id = c.id
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
        // ... (o resto da função continua igual) ...
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
    // ... (cole isso no final da classe Transacao, antes do '}') ...

    /**
     * Busca transações de forma filtrada e paginada (como um relatório).
     * @param array $filtros Array com os filtros (ex: ['filtro_descricao' => 'Netflix'])
     * @return array Lista de transações
     */
    /**
     * Busca transações de forma filtrada e paginada (como um relatório).
     * @param array $filtros Array com os filtros (ex: ['filtro_descricao' => 'Netflix'])
     * @return array Lista de transações
     */
    public function buscarTransacoesFiltradas($filtros = []) {
        // Query base com todos os JOINs
        $query = "SELECT 
                    t.id, t.descricao, t.valor, t.tipo, 
                    t.data_vencimento, t.data_efetivacao,
                    t.parcela_atual, t.parcela_total,
                    c.nome as nome_categoria,
                    co.nome as nome_conta,
                    p.nome as nome_pessoa
                  FROM 
                    " . $this->tabela . " t
                  LEFT JOIN 
                    categorias c ON t.categoria_id = c.id
                  LEFT JOIN 
                    contas co ON t.conta_id = co.id
                  LEFT JOIN 
                    pessoas p ON t.pessoa_id = p.id";
        
        $where_clauses = [];
        $params = [];

        // --- FILTROS DINÂMICOS ---
        
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

        // *** NOVOS FILTROS DE DATA ***
        // Filtra por data de VENCIMENTO
        if (!empty($filtros['mes'])) {
            $where_clauses[] = "MONTH(t.data_vencimento) = :mes";
            $params[':mes'] = $filtros['mes'];
        }
        if (!empty($filtros['ano'])) {
            $where_clauses[] = "YEAR(t.data_vencimento) = :ano";
            $params[':ano'] = $filtros['ano'];
        }
        // *** FIM DOS NOVOS FILTROS ***

        if (!empty($filtros['filtro_status'])) {
            if ($filtros['filtro_status'] == 'pago') {
                $where_clauses[] = "t.data_efetivacao IS NOT NULL";
            } else if ($filtros['filtro_status'] == 'pendente') {
                $where_clauses[] = "t.data_efetivacao IS NULL";
            }
        }

        // Constrói a cláusula WHERE
        if (count($where_clauses) > 0) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $query .= " ORDER BY t.data_vencimento DESC LIMIT 200"; // Limita a 200
        
        $stmt = $this->conn->prepare($query);
        
        // Binda os parâmetros
        foreach ($params as $key => $value) {
            // Garante que os números sejam tratados como inteiros
            if (is_numeric($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um resumo de entradas e saídas PENDENTES, agrupados por dia.
     * @param int $mes
     * @param int $ano
     * @return array [dia => ['entrada' => valor, 'saida' => valor]]
     */
    public function buscarPendentesAgrupadosPorDia($mes, $ano) {
        $query = "SELECT 
                    DAY(data_vencimento) as dia,
                    tipo, 
                    SUM(valor) as total_dia
                  FROM 
                    " . $this->tabela . " t
                  INNER JOIN
                    contas c ON t.conta_id = c.id
                  WHERE 
                    t.data_efetivacao IS NULL 
                    AND MONTH(t.data_vencimento) = :mes
                    AND YEAR(t.data_vencimento) = :ano
                    AND c.is_economia = 0"; // <-- Importante: Ignora economias
        
        $query .= " GROUP BY 
                    DAY(data_vencimento), tipo
                  ORDER BY 
                    dia ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reorganiza o array para ficar fácil de usar
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
}