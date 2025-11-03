<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$transacaoModel = new Transacao($conn);

// 3. Buscar dados
$listaDividas = $transacaoModel->buscarTotalPendentePorMesEConta('trabalho');

// Calcula o total geral para o cabeçalho
$totalGeral = array_sum(array_column($listaDividas, 'total_pendente'));

// Array de nomes de meses para exibição
$meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

// 4. Incluir o topo da página
$tituloPagina = "Detalhamento da Dívida por Mês e Conta";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="flex justify-between items-center p-6">
        <h2 class="text-xl font-semibold">Dívidas Pendentes por Mês e Conta</h2>
        <div class="text-right">
             <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Geral a Quitar</h3>
             <p class="text-2xl font-semibold text-red-600">
                R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?>
             </p>
        </div>
    </div>
    
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="th-table">Conta</th>
                <th class="th-table text-right">Valor Pendente</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            <?php if (empty($listaDividas)): ?>
                <tr>
                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">Nenhuma dívida pendente encontrada.</td>
                </tr>
            <?php else: ?>
                <?php 
                // *** LÓGICA DE QUEBRA DE GRUPO COM SUBTOTAL ***
                $mes_ano_atual = ""; // Variável de controle
                $subtotal_mes = 0;   // Variável para o subtotal

                foreach ($listaDividas as $divida): 
                    
                    // Formata o Mês/Ano (ex: "Novembro/2025")
                    $nome_mes = $meses[sprintf('%02d', $divida['mes'])];
                    $mes_ano_item = $nome_mes . ' / ' . $divida['ano'];

                    // Se o Mês/Ano deste item for DIFERENTE do anterior...
                    if ($mes_ano_item != $mes_ano_atual):
                        
                        // *** NOVO: Imprime o subtotal do mês anterior (se não for o primeiro) ***
                        if ($mes_ano_atual != ""): // Não imprime antes do primeiro cabeçalho
                ?>
                            <tr class="bg-white">
                                <td class="px-6 py-3 text-sm font-semibold text-gray-700 text-right pl-10">
                                    Subtotal (<?php echo htmlspecialchars($mes_ano_atual); ?>):
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                                    R$ <?php echo number_format($subtotal_mes, 2, ',', '.'); ?>
                                </td>
                            </tr>
                <?php
                        endif;
                        
                        // *** NOVO: Zera o subtotal para o novo mês ***
                        $subtotal_mes = 0;
                        
                        // Atualiza a variável de controle
                        $mes_ano_atual = $mes_ano_item; 
                ?>
                        <tr class="bg-gray-100 border-t-2 border-gray-200">
                            <td colspan="2" class="px-6 py-3 text-sm font-semibold text-gray-900">
                                <?php echo htmlspecialchars($mes_ano_atual); ?>
                            </td>
                        </tr>
                <?php 
                    endif; // Fim da quebra de grupo 
                
                    // *** NOVO: Soma o valor ao subtotal do mês corrente ***
                    $subtotal_mes += $divida['total_pendente'];
                ?>
                    
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 pl-10">
                            <?php echo htmlspecialchars($divida['nome_conta']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-600">
                            R$ <?php echo number_format($divida['total_pendente'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php 
                endforeach; 
                
                // *** NOVO: Imprime o subtotal do ÚLTIMO mês (após o fim do loop) ***
                if ($mes_ano_atual != ""): 
                ?>
                    <tr class="bg-white border-t border-gray-200">
                        <td class="px-6 py-3 text-sm font-semibold text-gray-700 text-right pl-10">
                            Subtotal (<?php echo htmlspecialchars($mes_ano_atual); ?>):
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                            R$ <?php echo number_format($subtotal_mes, 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php
                endif;
                ?>

            <?php endif; ?>
        </tbody>
    </table>
    <div class="p-4 bg-gray-50 border-t">
        <a href="graficos.php" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Voltar para Gráficos</a>
    </div>
</div>

<style>
    .th-table {
        padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem;
        font-weight: 500; color: #6B7280; text-transform: uppercase;
    }
</style>

<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>