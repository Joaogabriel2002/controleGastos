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
// *** MUDANÇA: Chama o novo método ***
$listaDividas = $transacaoModel->buscarTotalPendentePorDescricao('trabalho');

// Calcula o total geral para o cabeçalho
$totalGeral = array_sum(array_column($listaDividas, 'total_pendente'));

// 4. Incluir o topo da página
$tituloPagina = "Detalhamento da Dívida Pendente";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="flex justify-between items-center p-6">
        <h2 class="text-xl font-semibold">Dívidas Pendentes por Descrição</h2>
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
                <th class="th-table">Descrição da Dívida</th>
                <th class="th-table text-right">Nº de Parcelas Pendentes</th>
                <th class="th-table text-right">Valor Total Pendente</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($listaDividas)): ?>
                <tr>
                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhuma dívida pendente encontrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($listaDividas as $divida): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($divida['descricao']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">
                            <?php echo $divida['total_parcelas']; ?> parcelas
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-600">
                            R$ <?php echo number_format($divida['total_pendente'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
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