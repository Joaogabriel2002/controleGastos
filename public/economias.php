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

// --- DEFINIÇÃO DE CONTEXTO ---
// Esta página mostra APENAS contas de 'economia'
$contexto = 'economia'; 

// 2.1. Buscar dados-base
// Busca APENAS contas de economia
$listaContas = $contaModel->buscarTodas($contexto); 

// 2.2. Calcular Saldos
// Busca totais APENAS de contas de economia
$totaisPorConta = $transacaoModel->buscarTotaisEfetivadosPorConta(null, $contexto); 

$contasComSaldo = [];
$saldoGeralEconomias = 0; 

foreach ($listaContas as $conta) {
    $contaId = $conta['id'];
    $saldo = $conta['saldo_inicial']; 
    if (isset($totaisPorConta[$contaId])) {
        $movimentacoes = $totaisPorConta[$contaId];
        $saldo = $saldo + $movimentacoes['entrada'] - $movimentacoes['saida'];
    }
    $conta['saldo_atual_calculado'] = $saldo;
    $contasComSaldo[] = $conta;
    $saldoGeralEconomias += $saldo;
}

// 3. Incluir o topo da página
$tituloPagina = "Minhas Economias";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="flex justify-between items-center p-6">
        <h2 class="text-xl font-semibold">Saldos das Contas de Economia</h2>
        <div class="text-right">
             <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Guardado</h3>
             <p class="text-2xl font-semibold text-blue-600">
                R$ <?php echo number_format($saldoGeralEconomias, 2, ',', '.'); ?>
             </p>
        </div>
    </div>
    
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Inicial</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Atual</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($contasComSaldo)): ?>
                <tr>
                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhuma conta de economia cadastrada.</td>
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
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-blue-600">
                            R$ <?php echo number_format($conta['saldo_atual_calculado'], 2, ',', '.'); ?>
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