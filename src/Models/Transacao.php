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
    // ... (cole isso no final da classe Transacao, antes do '}') ...

    /**
     * CARD 1: Busca o total de todas as parcelas pendentes (Dívida Futura).
     * @param string $contexto 'trabalho' ou 'economia'
     * @return float O valor total pendente
     */
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

    /**
     * GRÁFICO 2: Busca os gastos efetivados, agrupados por categoria.
     * @param string $contexto 'trabalho' ou 'economia'
     * @param int $limite Quantas categorias (Top 5, Top 10)
     * @return array [ ['nome' => 'Alimentação', 'total_gasto' => 500], ... ]
     */
    public function buscarGastoPorCategoria($contexto = 'trabalho', $limite = 5, $tipo_dado = 'realizado') {
        // Define o filtro de status (Realizado ou Previsto)
        $status_check = ($tipo_dado == 'realizado') ? 't.data_efetivacao IS NOT NULL' : 't.data_efetivacao IS NULL';

        $query = "SELECT 
                    cat.nome, 
                    SUM(t.valor) as total_gasto
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

    /**
     * GRÁFICO 3: Busca o fluxo de caixa (Entrada x Saída) dos últimos 6 meses.
     * @param string $contexto 'trabalho' ou 'economia'
     * @param int $meses Quantidade de meses para trás
     * @return array Dados formatados para Chart.js
     */
    public function buscarFluxoCaixaUltimosMeses($contexto = 'trabalho', $meses = 6, $tipo_dado = 'realizado') {
        
        if ($tipo_dado == 'realizado') {
            $date_column = 't.data_efetivacao';
            $status_check = 't.data_efetivacao IS NOT NULL';
            // 6 meses para trás (incluindo o mês atual)
            $date_range_check = "t.data_efetivacao >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01')";
            $order_by = "ano ASC, mes ASC";
        } else { // 'previsto'
            $date_column = 't.data_vencimento';
            $status_check = 't.data_efetivacao IS NULL';
            // 6 meses para frente (incluindo o mês atual)
            $date_range_check = "t.data_vencimento >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND t.data_vencimento < DATE_FORMAT(CURDATE() + INTERVAL 6 MONTH, '%Y-%m-01')";
            $order_by = "ano ASC, mes ASC";
        }

        $query = "SELECT 
                    YEAR($date_column) as ano,
                    MONTH($date_column) as mes,
                    t.tipo,
                    SUM(t.valor) as total
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

        // Processa os dados em PHP para formatar para o Chart.js
        $labels = [];
        $dados_formatados = [];
        $meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        // Inicializa os arrays para os 6 meses
        if ($tipo_dado == 'realizado') {
            // 6 meses para trás
            for ($i = $meses - 1; $i >= 0; $i--) {
                $timestamp = strtotime(date('Y-m-01') . " -$i months");
                $mes_ano_key = date('Y-m', $timestamp);
                $labels[] = $meses_nomes[date('n', $timestamp) - 1] . '/' . date('y', $timestamp);
                $dados_formatados[$mes_ano_key] = ['entrada' => 0, 'saida' => 0];
            }
        } else {
            // 6 meses para frente
            for ($i = 0; $i < $meses; $i++) {
                $timestamp = strtotime(date('Y-m-01') . " +$i months");
                $mes_ano_key = date('Y-m', $timestamp);
                $labels[] = $meses_nomes[date('n', $timestamp) - 1] . '/' . date('y', $timestamp);
                $dados_formatados[$mes_ano_key] = ['entrada' => 0, 'saida' => 0];
            }
        }
        
        // Preenche com os dados do banco
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
        
        // Separa em arrays finais
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
    // ... (cole isso no final da classe Transacao, antes do '}') ...

    /**
     * CARD 1.5: Busca o NÚMERO de parcelas pendentes (Dívida Futura).
     * @param string $contexto 'trabalho' ou 'economia'
     * @return int O número de transações pendentes
     */
    public function buscarNumeroParcelasPendentes($contexto = 'trabalho') {
        // Esta query CONTA as linhas de transação pendentes
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

    // ... (cole isso no final da classe Transacao, antes do '}') ...

    /**
     * GRÁFICO 1 (Drill-down): Busca o total de dívidas pendentes agrupado por categoria.
     * @param string $contexto 'trabalho' ou 'economia'
     * @return array [ ['nome_categoria' => 'Financiamento', 'total_pendente' => 20000, 'total_parcelas' => 48], ... ]
     */
    /**
     * GRÁFICO 1 (Drill-down): Busca o total de dívidas pendentes agrupado por DESCRIÇÃO.
     * @param string $contexto 'trabalho' ou 'economia'
     * @return array [ ['descricao' => 'Financiamento Carro', 'total_pendente' => 20000, 'total_parcelas' => 48], ... ]
     */
    public function buscarTotalPendentePorDescricao($contexto = 'trabalho') {
        // Esta query não precisa mais da tabela 'categorias'
        $query = "SELECT 
                    t.descricao,
                    SUM(t.valor) as total_pendente,
                    COUNT(t.id) as total_parcelas
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  WHERE t.data_efetivacao IS NULL
                    AND t.tipo = 'saida'
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1') . "
                  GROUP BY t.descricao  -- A MUDANÇA ESTÁ AQUI
                  HAVING total_pendente > 0
                  ORDER BY total_pendente DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // ... (cole isso no final da classe Transacao, antes do '}') ...

    /**
     * GRÁFICO 1 (Drill-down Categoria): Busca o total de dívidas pendentes agrupado por CATEGORIA.
     * @param string $contexto 'trabalho' ou 'economia'
     * @return array [ ['nome_categoria' => 'Financiamento', 'total_pendente' => 20000, 'total_parcelas' => 48], ... ]
     */
    public function buscarTotalPendentePorCategoria($contexto = 'trabalho') {
        $query = "SELECT 
                    cat.nome as nome_categoria,
                    SUM(t.valor) as total_pendente,
                    COUNT(t.id) as total_parcelas
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

    // ... (cole isso no final da classe Transacao, antes do '}') ...

    /**
     * GRÁFICO 4: Busca os gastos efetivados, agrupados por CONTA.
     * @param string $contexto 'trabalho' ou 'economia'
     * @param int $limite Quantas contas (Top 5, Top 10)
     * @return array [ ['nome_conta' => 'Cartão Nubank', 'total_gasto' => 1200], ... ]
     */
    // ... (cole isso no final da classe Transacao, antes do '}') ...

    /**
     * GRÁFICO 1 (Drill-down Mês): Busca o total de dívidas pendentes agrupado por MÊS/ANO.
     * @param string $contexto 'trabalho' ou 'economia'
     * @return array [ ['ano' => 2025, 'mes' => 11, 'total_pendente' => 500], ... ]
     */
   /**
     * GRÁFICO 1 (Drill-down Mês/Conta): Busca o total de dívidas pendentes agrupado por MÊS/ANO e por CONTA.
     * @param string $contexto 'trabalho' ou 'economia'
     * @return array [ ['ano' => 2025, 'mes' => 11, 'nome_conta' => 'Cartão Nubank', 'total_pendente' => 500], ... ]
     */
    public function buscarTotalPendentePorMesEConta($contexto = 'trabalho') {
        $query = "SELECT 
                    YEAR(t.data_vencimento) as ano,
                    MONTH(t.data_vencimento) as mes,
                    co.nome as nome_conta,
                    SUM(t.valor) as total_pendente
                  FROM " . $this->tabela . " t
                  INNER JOIN contas c ON t.conta_id = c.id
                  INNER JOIN contas co ON t.conta_id = co.id -- (mesma tabela 'contas' para pegar o nome)
                  WHERE t.data_efetivacao IS NULL
                    AND t.tipo = 'saida'
                    AND c.is_economia = " . ($contexto == 'trabalho' ? '0' : '1') . "
                  GROUP BY ano, mes, t.conta_id, co.nome
                  HAVING total_pendente > 0
                  ORDER BY ano ASC, mes ASC, total_pendente DESC"; // Ordena por data, e depois por valor
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}