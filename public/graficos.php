<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$transacaoModel = new Transacao($conn);

// Lógica do Seletor (Toggle)
$tipo_dado = $_GET['tipo_dado'] ?? 'realizado'; // 'realizado' ou 'previsto'
$contexto = 'trabalho';

// Títulos dinâmicos
$titulo_analise = ($tipo_dado == 'realizado') ? 'Análise de Gastos (Efetivados)' : 'Análise de Gastos (Pendentes)';
$titulo_fluxo = ($tipo_dado == 'realizado') ? 'Entradas x Saídas (Últimos 6 Meses Efetivados)' : 'Entradas x Saídas (Próximos 6 Meses Pendentes)';

// 3. Buscar os dados para os gráficos

// --- Card 1: Dívidas Pendentes (Sempre Previsto) ---
$totalDividaPendente = $transacaoModel->buscarTotalPendenteGeral($contexto);
$numeroParcelasPendentes = $transacaoModel->buscarNumeroParcelasPendentes($contexto);

// --- Gráfico 2: Top 5 Gastos por CATEGORIA (com toggle) ---
$dadosGastoCategoria = $transacaoModel->buscarGastoPorCategoria($contexto, 5, $tipo_dado);
$labelsGastoCategoria = [];
$dataGastoCategoria = [];
foreach ($dadosGastoCategoria as $item) {
    $labelsGastoCategoria[] = $item['nome'];
    $dataGastoCategoria[] = $item['total_gasto'];
}
$jsonGastoCategoria_Labels = json_encode($labelsGastoCategoria);
$jsonGastoCategoria_Data = json_encode($dataGastoCategoria);

// --- Gráfico 3: Fluxo de Caixa (com toggle) ---
$dadosFluxoCaixa = $transacaoModel->buscarFluxoCaixaUltimosMeses($contexto, 6, $tipo_dado);
$jsonFluxoCaixa_Labels = json_encode($dadosFluxoCaixa['labels']);
$jsonFluxoCaixa_Datasets = json_encode($dadosFluxoCaixa['datasets']);

// 4. Incluir o topo da página
$tituloPagina = "Gráficos e Análises";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="bg-white p-4 shadow rounded-lg mb-6">
    <div class="flex items-center space-x-4">
        <span class="text-sm font-medium text-gray-700">Mostrar dados:</span>
        <?php
        $link_realizado = "graficos.php?tipo_dado=realizado";
        $link_previsto = "graficos.php?tipo_dado=previsto";
        $classe_realizado = ($tipo_dado == 'realizado') ? 'py-2 px-4 rounded-md text-sm font-medium bg-indigo-600 text-white' : 'py-2 px-4 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-100';
        $classe_previsto = ($tipo_dado == 'previsto') ? 'py-2 px-4 rounded-md text-sm font-medium bg-indigo-600 text-white' : 'py-2 px-4 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-100';
        ?>
        <a href="<?php echo $link_realizado; ?>" class="<?php echo $classe_realizado; ?>">
            Realizado (Efetivado)
        </a>
        <a href="<?php echo $link_previsto; ?>" class="<?php echo $classe_previsto; ?>">
            Previsto (Pendente)
        </a>
    </div>
</div>

<h2 class="text-lg font-semibold text-gray-900 mb-4">Resumo da Dívida (Pendentes)</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <a href="dividas.php" class="block bg-white p-6 shadow rounded-lg border-l-4 border-red-500 hover:bg-gray-50 transition-colors duration-150">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total a Quitar (por Descrição)</h3>
        <p class="mt-1 text-3xl font-semibold text-red-600">
            R$ <?php echo number_format($totalDividaPendente, 2, ',', '.'); ?>
        </p>
        <span class="text-xs text-indigo-600 font-medium mt-2 block">Clique para detalhar</span>
    </a>
    
    <a href="dividas.php" class="block bg-white p-6 shadow rounded-lg border-l-4 border-yellow-500 hover:bg-gray-50 transition-colors duration-150">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total a Quitar (Nº de Parcelas)</h3>
        <p class="mt-1 text-3xl font-semibold text-yellow-600">
            <?php echo $numeroParcelasPendentes; ?> parcelas
        </p>
        <span class="text-xs text-indigo-600 font-medium mt-2 block">Clique para detalhar</span>
    </a>

    <a href="dividas-por-mes.php" class="block bg-white p-6 shadow rounded-lg border-l-4 border-blue-500 hover:bg-gray-50 transition-colors duration-150">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total a Quitar (por Mês)</h3>
        <p class="mt-1 text-3xl font-semibold text-blue-600">
            R$ <?php echo number_format($totalDividaPendente, 2, ',', '.'); ?>
        </p>
        <span class="text-xs text-indigo-600 font-medium mt-2 block">Clique para ver o extrato</span>
    </a>
</div>

<h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo $titulo_analise; ?></h2>
<div class="grid grid-cols-1 gap-6 mb-6">
    <div class="bg-white p-6 shadow rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 5 Categorias de Gasto</h3>
        <canvas id="graficoGastosCategoria"></canvas>
    </div>
</div>

<h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo $titulo_fluxo; ?></h2>
<div class="grid grid-cols-1 gap-6">
    <div class="lg:col-span-1 bg-white p-6 shadow rounded-lg">
        <canvas id="graficoFluxoCaixa"></canvas>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de Gastos por CATEGORIA
    const ctxGastos = document.getElementById('graficoGastosCategoria').getContext('2d');
    new Chart(ctxGastos, {
        type: 'doughnut',
        data: {
            labels: <?php echo $jsonGastoCategoria_Labels; ?>,
            datasets: [{
                label: 'Total Gasto',
                data: <?php echo $jsonGastoCategoria_Data; ?>,
                backgroundColor: ['rgba(220, 38, 38, 0.8)', 'rgba(234, 88, 12, 0.8)', 'rgba(250, 204, 21, 0.8)', 'rgba(101, 163, 13, 0.8)', 'rgba(59, 130, 246, 0.8)'],
                hoverOffset: 4
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } } }
    });

    // *** REMOVIDO: Gráfico de Gastos por CONTA ***

    // Gráfico de Fluxo de Caixa (Barras)
    const ctxFluxo = document.getElementById('graficoFluxoCaixa').getContext('2d');
    new Chart(ctxFluxo, {
        type: 'bar',
        data: {
            labels: <?php echo $jsonFluxoCaixa_Labels; ?>,
            datasets: <?php echo $jsonFluxoCaixa_Datasets; ?>
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: false },
                y: { stacked: false, beginAtZero: true }
            },
            plugins: { legend: { position: 'top' } }
        }
    });
</script>

<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>