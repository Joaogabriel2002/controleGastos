<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$transacaoModel = new Transacao($conn);

// 3. Buscar os dados para os gráficos (contexto 'trabalho')

// Card 1: Total de dívidas (Valor)
$totalDividaPendente = $transacaoModel->buscarTotalPendenteGeral('trabalho');

// *** NOVO: Card 2: Total de dívidas (Parcelas) ***
$numeroParcelasPendentes = $transacaoModel->buscarNumeroParcelasPendentes('trabalho');


// Gráfico 2: Top 5 gastos
$dadosGastoCategoria = $transacaoModel->buscarGastoPorCategoria('trabalho', 5);
$labelsGastoCategoria = [];
$dataGastoCategoria = [];
foreach ($dadosGastoCategoria as $item) {
    $labelsGastoCategoria[] = $item['nome'];
    $dataGastoCategoria[] = $item['total_gasto'];
}
$jsonGastoCategoria_Labels = json_encode($labelsGastoCategoria);
$jsonGastoCategoria_Data = json_encode($dataGastoCategoria);


// Gráfico 3: Fluxo de Caixa (Entrada x Saída 6 meses)
$dadosFluxoCaixa = $transacaoModel->buscarFluxoCaixaUltimosMeses('trabalho', 6);
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

<h2 class="text-lg font-semibold text-gray-900 mb-4">Resumo da Dívida (Contas de Trabalho)</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <a href="dividas.php" 
       class="block bg-white p-6 shadow rounded-lg border-l-4 border-red-500
              hover:bg-gray-50 transition-colors duration-150">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total a Quitar (Valor)</h3>
        <p class="mt-1 text-3xl font-semibold text-red-600">
            R$ <?php echo number_format($totalDividaPendente, 2, ',', '.'); ?>
        </p>
        <span class="text-xs text-indigo-600 font-medium mt-2 block">Clique para detalhar por categoria</span>
    </a>
    
    <a href="dividas.php" 
       class="block bg-white p-6 shadow rounded-lg border-l-4 border-yellow-500
              hover:bg-gray-50 transition-colors duration-150">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total a Quitar (Nº de Parcelas)</h3>
        <p class="mt-1 text-3xl font-semibold text-yellow-600">
            <?php echo $numeroParcelasPendentes; ?> parcelas
        </p>
        <span class="text-xs text-indigo-600 font-medium mt-2 block">Clique para detalhar</span>
    </a>

    <div class="hidden md:block"></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-1 bg-white p-6 shadow rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 5 Categorias de Gasto (Efetivados)</h3>
        <canvas id="graficoGastosCategoria"></canvas>
    </div>

    <div class="lg:col-span-2 bg-white p-6 shadow rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Fluxo de Caixa (Últimos 6 Meses Efetivados)</h3>
        <canvas id="graficoFluxoCaixa"></canvas>
    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Gráfico 2: Doughnut (Gastos por Categoria)
    const ctxGastos = document.getElementById('graficoGastosCategoria').getContext('2d');
    new Chart(ctxGastos, {
        type: 'doughnut',
        data: {
            labels: <?php echo $jsonGastoCategoria_Labels; ?>,
            datasets: [{
                label: 'Total Gasto',
                data: <?php echo $jsonGastoCategoria_Data; ?>,
                backgroundColor: [
                    'rgba(220, 38, 38, 0.8)',  // Vermelho
                    'rgba(234, 88, 12, 0.8)',  // Laranja
                    'rgba(250, 204, 21, 0.8)', // Amarelo
                    'rgba(101, 163, 13, 0.8)', // Verde
                    'rgba(59, 130, 246, 0.8)'  // Azul
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Gráfico 3: Bar (Fluxo de Caixa)
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
                x: {
                    stacked: false 
                },
                y: {
                    stacked: false,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
</script>


<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>