<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$transacaoModel = new Transacao($conn);

// *** MUDANÇA: Lógica do Seletor ***
// Pega o tipo de agrupamento do GET, o padrão é 'descricao' (o que você pediu)
$group_by = $_GET['group_by'] ?? 'descricao';
$titulo_relatorio = "Dívidas Pendentes por Descrição";
$coluna_principal = "Descrição da Dívida";

// 3. Buscar dados
if ($group_by == 'categoria') {
    // Se pediu por categoria, chama o método de categoria
    $listaDividas = $transacaoModel->buscarTotalPendentePorCategoria('trabalho');
    $titulo_relatorio = "Dívidas Pendentes por Categoria";
    $coluna_principal = "Categoria";
} else {
    // Senão, chama o método padrão (por descrição)
    $listaDividas = $transacaoModel->buscarTotalPendentePorDescricao('trabalho');
}
// *** FIM DA MUDANÇA ***

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
    <div class="flex flex-wrap justify-between items-center p-6">
        <div>
            <h2 class="text-xl font-semibold"><?php echo $titulo_relatorio; ?></h2>
            
            <div class="mt-2 text-sm">
                <span class="font-medium">Agrupar por:</span>
                <?php
                $link_descricao = "dividas.php?group_by=descricao";
                $link_categoria = "dividas.php?group_by=categoria";
                
                $classe_desc = ($group_by == 'descricao') ? 'font-bold text-indigo-700' : 'text-indigo-600 hover:text-indigo-900';
                $classe_cat = ($group_by == 'categoria') ? 'font-bold text-indigo-700' : 'text-indigo-600 hover:text-indigo-900';
                ?>
                <a href="<?php echo $link_descricao; ?>" class="<?php echo $classe_desc; ?>">
                    Descrição
                </a>
                <span class="mx-2 text-gray-400">|</span>
                <a href="<?php echo $link_categoria; ?>" class="<?php echo $classe_cat; ?>">
                    Categoria
                </a>
            </div>
        </div>
        <div class="text-right mt-4 md:mt-0">
             <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Geral a Quitar</h3>
             <p class="text-2xl font-semibold text-red-600">
                R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?>
             </p>
        </div>
    </div>
    
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="th-table"><?php echo $coluna_principal; ?></th>
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
                            <?php 
                            // Exibe 'descricao' ou 'nome_categoria' dependendo do filtro
                            $nome_item = ($group_by == 'categoria') ? $divida['nome_categoria'] : $divida['descricao'];
                            echo htmlspecialchars($nome_item); 
                            ?>
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