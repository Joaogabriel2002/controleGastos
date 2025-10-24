<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';
require_once '../src/Core/CalendarioHelper.php'; // Inclui o novo Helper

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$transacaoModel = new Transacao($conn);

// --- LÓGICA DO FILTRO DE MÊS ---
// Padrão é o MÊS ATUAL (diferente do dashboard)
$mes_selecionado = $_GET['mes'] ?? date('m');
$ano_selecionado = $_GET['ano'] ?? date('Y');

$meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
$anos = range(date('Y') + 1, date('Y') - 5); 
// --- FIM DO FILTRO ---

// 2.1. Buscar dados
// Busca os totais PENDENTES agrupados por dia
$dadosCalendario = $transacaoModel->buscarPendentesAgrupadosPorDia($mes_selecionado, $ano_selecionado);

// 2.2. Instanciar o Calendário
$calendario = new CalendarioHelper($mes_selecionado, $ano_selecionado, $dadosCalendario);


// 3. Incluir o topo da página
$tituloPagina = "Calendário de Pendências";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="bg-white p-4 shadow rounded-lg mb-6">
    <form action="calendario.php" method="GET" class="flex items-center space-x-3">
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
    </form>
</div>

<?php
// Desenha o calendário
$calendario->desenhar();
?>

<?php
// 4. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>