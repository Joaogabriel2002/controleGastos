<?php
// Arquivo: src/Core/CalendarioHelper.php

class CalendarioHelper {
    
    private $mes;
    private $ano;
    private $dadosDias = [];

    /**
     * @param int $mes
     * @param int $ano
     * @param array $dadosDias Dados vindos do TransacaoModel
     */
    public function __construct($mes, $ano, $dadosDias = []) {
        $this->mes = $mes;
        $this->ano = $ano;
        $this->dadosDias = $dadosDias;
    }

    /**
     * Desenha o calendário HTML
     */
    public function desenhar() {
        // Timestamp do primeiro dia do mês
        $primeiroDiaTimestamp = mktime(0, 0, 0, $this->mes, 1, $this->ano);
        
        // Quantos dias tem no mês
        $totalDiasMes = date('t', $primeiroDiaTimestamp);
        
        // Em que dia da semana (0=Dom, 6=Sab) o mês começa
        $diaSemanaPrimeiroDia = date('w', $primeiroDiaTimestamp);
        
        // Nomes dos dias da semana
        $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];

        // --- Inicia o HTML da tabela ---
        echo '<div class="bg-white shadow rounded-lg overflow-hidden">';
        echo '<table class="min-w-full">';
        
        // Cabeçalho com os dias da semana
        echo '<thead class="bg-gray-50">';
        echo '<tr>';
        foreach ($diasSemana as $dia) {
            echo '<th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">' . $dia . '</th>';
        }
        echo '</tr>';
        echo '</thead>';
        
        // Corpo do calendário
        echo '<tbody class="divide-y divide-gray-200">';
        
        $diaAtual = 1;
        echo '<tr>';

        // 1. Células vazias antes do dia 1
        for ($i = 0; $i < $diaSemanaPrimeiroDia; $i++) {
            echo '<td class="h-32 bg-gray-50 border border-gray-100"></td>';
        }

        // 2. Células dos dias do mês
        while ($diaAtual <= $totalDiasMes) {
            
            // Quebra a linha no Sábado (dia 6)
            if (date('w', mktime(0, 0, 0, $this->mes, $diaAtual, $this->ano)) == 6) {
                // Sábado
                $this->desenharCelulaDia($diaAtual);
                echo '</tr><tr>'; // Fecha a linha atual e abre uma nova
            } else {
                // Outros dias
                $this->desenharCelulaDia($diaAtual);
            }
            
            $diaAtual++;
        }

        // 3. Células vazias depois do último dia
        if ($diaAtual > $totalDiasMes) {
            $diaSemanaUltimoDia = date('w', mktime(0, 0, 0, $this->mes, $totalDiasMes, $this->ano));
            for ($i = $diaSemanaUltimoDia + 1; $i <= 6; $i++) {
                echo '<td class="h-32 bg-gray-50 border border-gray-100"></td>';
            }
        }

        echo '</tr>'; // Fecha a última linha
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    /**
     * Desenha a célula de um dia específico
     */
    private function desenharCelulaDia($dia) {
        $hoje = date('Y-m-d');
        $dataCelula = sprintf('%04d-%02d-%02d', $this->ano, $this->mes, $dia);
        
        // Ajuste para pegar a data de hoje (considerando seu fuso -03:00)
        $hoje = date('Y-m-d', strtotime('now'));
        
        $classeDia = ($dataCelula == $hoje) ? 'bg-indigo-100' : 'bg-white';
        
        echo "<td class='h-32 border border-gray-200 align-top p-2 {$classeDia}'>";
        
        // Número do dia
        echo "<div class='font-semibold text-gray-800'>" . $dia . "</div>";
        
        // Dados (se existirem)
        if (isset($this->dadosDias[$dia])) {
            $dados = $this->dadosDias[$dia];
            
            if ($dados['entrada'] > 0) {
                echo "<div class='mt-1 text-xs text-green-600 font-medium' title='A Receber'>";
                echo "+ R$ " . number_format($dados['entrada'], 2, ',', '.');
                echo "</div>";
            }
            if ($dados['saida'] > 0) {
                echo "<div class='mt-1 text-xs text-red-600 font-medium' title='A Pagar'>";
                echo "- R$ " . number_format($dados['saida'], 2, ',', '.');
                echo "</div>";
            }
        }
        
        echo "</td>";
    }
}