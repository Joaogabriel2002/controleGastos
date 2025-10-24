<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';
require_once '../src/Models/Conta.php';

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$transacaoModel = new Transacao($conn);
$contaModel = new Conta($conn);

// --- LÓGICA DO FILTRO DE MÊS ---
// *** MUDANÇA: Define o padrão como Mês Seguinte ***
$data_padrao = strtotime('+1 month');
$mes_padrao = date('m', $data_padrao);
$ano_padrao = date('Y', $data_padrao);

// Captura o mês/ano do GET, ou usa o mês/ano SEGUINTE como padrão
$mes_selecionado = $_GET['mes'] ?? $mes_padrao;
$ano_selecionado = $_GET['ano'] ?? $ano_padrao;
// *** FIM DA MUDANÇA ***

$meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
$anos = range(date('Y') + 1, date('Y') - 5);

// --- LÓGICA DO FILTRO DE VISÃO (Geral vs Mês) ---
$visao_saldo = $_GET['visao_saldo'] ?? 'geral'; // 'geral' ou 'mes'

// 2.1. Buscar dados-base
$listaContas = $contaModel->buscarTodas();

// 2.2. Buscar dados FILTRADOS PELO MÊS (Para os Cards)
$resumoEfetivado = $transacaoModel->buscarResumoEfetivadoPorMes($mes_selecionado, $ano_selecionado);
$resumoPendentes = $transacaoModel->buscarResumoPendentesPorMes($mes_selecionado, $ano_selecionado);

// 2.3. Calcular saldos (Lógica de Saldo com FILTRO)
$data_filtro_saldo = null;
$titulo_tabela_saldo = "Saldos Totais das Contas (Consolidado Geral)";

if ($visao_saldo == 'mes') {
    // Pega o último dia do mês selecionado
    $ultimo_dia = date('Y-m-t', strtotime("{$ano_selecionado}-{$mes_selecionado}-01"));
    $data_filtro_saldo = $ultimo_dia;
    $titulo_tabela_saldo = "Saldos das Contas (Posição em " . date('d/m/Y', strtotime($ultimo_dia)) . ")";
}

// Busca os totais de saldo usando o filtro (nulo para 'geral', data para 'mes')
$totaisPorConta = $transacaoModel->buscarTotaisEfetivadosPorConta($data_filtro_saldo);

$contasComSaldo = [];
$saldoGeralTotal = 0;
foreach ($listaContas as $conta) {
    $contaId = $conta['id'];
    $saldo = $conta['saldo_inicial']; 
    if (isset($totaisPorConta[$contaId])) {
        $movimentacoes = $totaisPorConta[$contaId];
        $saldo = $saldo + $movimentacoes['entrada'] - $movimentacoes['saida'];
    }
    $conta['saldo_atual'] = $saldo;
    $contasComSaldo[] = $conta;
    $saldoGeralTotal += $saldo;
}

// 2.4. Calcular totais para os cards (Baseado no MÊS)
$totalRecebidoMes = $resumoEfetivado['total_recebido'];
$totalPagoMes = $resumoEfetivado['total_pago'];
$balancoMes = $totalRecebidoMes - $totalPagoMes;
$totalAReceberMes = $resumoPendentes['total_a_receber'];
$totalAPagarMes = $resumoPendentes['total_a_pagar'];
$balancoPendenteMes = $totalAReceberMes - $totalAPagarMes;

// 3. Incluir o topo da página
$tituloPagina = "Dashboard";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="bg-white p-4 shadow rounded-lg mb-6">
    <form action="index.php" method="GET" class="flex items-center space-x-3">
        <label for="mes" class="text-sm font-medium text-gray-700">Selecionar Mês:</label>
        <select name="mes" id="mes" class="rounded-md border-gray-300 shadow-sm">
            <?php foreach ($meses as $num => $nome): ?>
                <option value="<?php echo $num; ?>" <?php echo ($num == $mes_selecionado) ? 'selected' : ''; ?>>
                    <?php echo $nome; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="ano" id="ano" class="rounded-md border-gray-300 shadow-sm">
            <?php foreach ($anos as $ano): ?>
                <option value="<?php echo $ano; ?>" <?php echo ($ano == $ano_selecionado) ? 'selected' : ''; ?>>
                    <?php echo $ano; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" 
                class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            Filtrar
        </button>
        <input type="hidden" name="visao_saldo" value="<?php echo $visao_saldo; ?>">
    </form>
</div>

<h2 class="text-lg font-semibold text-gray-900 mb-2">Fluxo de Caixa Efetivado (<?php echo $meses[$mes_selecionado].'/'.$ano_selecionado; ?>)</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 shadow rounded-lg border-l-4 border-green-500">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Recebido</h3>
        <p class="mt-1 text-3xl font-semibold text-green-600">
            R$ <?php echo number_format($totalRecebidoMes, 2, ',', '.'); ?>
        </p>
    </div>
    <div class="bg-white p-6 shadow rounded-lg border-l-4 border-red-500">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Pago</h3>
        <p class="mt-1 text-3xl font-semibold text-red-600">
            R$ <?php echo number_format($totalPagoMes, 2, ',', '.'); ?>
        </p>
    </div>
    <div class="bg-white p-6 shadow rounded-lg border-l-4 <?php echo $balancoMes >= 0 ? 'border-blue-500' : 'border-gray-500'; ?>">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Balanço do Mês</h3>
        <p class="mt-1 text-3xl font-semibold <?php echo $balancoMes >= 0 ? 'text-blue-600' : 'text-gray-600'; ?>">
            R$ <?php echo number_format($balancoMes, 2, ',', '.'); ?>
        </p>
    </div>
</div>

<h2 class="text-lg font-semibold text-gray-900 mb-2">Pendências (Vencimento em <?php echo $meses[$mes_selecionado].'/'.$ano_selecionado; ?>)</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 shadow rounded-lg border-l-4 border-green-400">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">A Receber</h3>
        <p class="mt-1 text-3xl font-semibold text-green-500">
            R$ <?php echo number_format($totalAReceberMes, 2, ',', '.'); ?>
        </p>
    </div>
    <div class="bg-white p-6 shadow rounded-lg border-l-4 border-red-400">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">A Pagar</h3>
        <p class="mt-1 text-3xl font-semibold text-red-500">
            R$ <?php echo number_format($totalAPagarMes, 2, ',', '.'); ?>
        </p>
    </div>
    <div class="bg-white p-6 shadow rounded-lg border-l-4 <?php echo $balancoPendenteMes >= 0 ? 'border-blue-400' : 'border-gray-400'; ?>">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Balanço Pendente</h3>
        <p class="mt-1 text-3xl font-semibold <?php echo $balancoPendenteMes >= 0 ? 'text-blue-500' : 'text-gray-500'; ?>">
            R$ <?php echo number_format($balancoPendenteMes, 2, ',', '.'); ?>
        </p>
    </div>
</div>


<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="flex flex-wrap justify-between items-center p-6">
        <div>
            <h2 class="text-xl font-semibold"><?php echo $titulo_tabela_saldo; ?></h2>
            
            <div class="mt-2 text-sm">
                <span class="font-medium">Ver Saldo:</span>
                
                <?php
                // Links que preservam o filtro de mês/ano
                $link_base = "index.php?mes={$mes_selecionado}&ano={$ano_selecionado}";
                $link_geral = $link_base . "&visao_saldo=geral";
                $link_mes = $link_base . "&visao_saldo=mes";
                
                $classe_geral = ($visao_saldo == 'geral') ? 'font-bold text-indigo-700' : 'text-indigo-600 hover:text-indigo-900';
                $classe_mes = ($visao_saldo == 'mes') ? 'font-bold text-indigo-700' : 'text-indigo-600 hover:text-indigo-900';
                ?>
                
                <a href="<?php echo $link_geral; ?>" class="<?php echo $classe_geral; ?>">
                    Geral (Consolidado)
                </a>
                <span class="mx-2 text-gray-400">|</span>
                <a href="<?php echo $link_mes; ?>" class="<?php echo $classe_mes; ?>">
                    No Mês Selecionado
                </a>
            </div>
        </div>
        <div class="text-right mt-4 md:mt-0">
             <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Patrimônio / Saldo Total (<?php echo $visao_saldo; ?>)</h3>
             <p class="text-2xl font-semibold <?php echo $saldoGeralTotal >= 0 ? 'text-gray-900' : 'text-red-600'; ?>">
                R$ <?php echo number_format($saldoGeralTotal, 2, ',', '.'); ?>
             </p>
        </div>
    </div>
    
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Inicial</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Calculado</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($contasComSaldo)): ?>
                <tr>
                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhuma conta cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($contasComSaldo as $conta): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($conta['nome']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            R$ <?php echo number_format($conta['saldo_inicial'], 2, ',', '.'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold <?php echo $conta['saldo_atual'] >= 0 ? 'text-gray-900' : 'text-red-600'; ?>">
                            R$ <?php echo number_format($conta['saldo_atual'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// 4. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>