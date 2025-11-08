<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';
require_once '../src/Models/Categoria.php';
require_once '../src/Models/Conta.php';

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();

// Instancia os models
$transacaoModel = new Transacao($conn);
$categoriaModel = new Categoria($conn);
$contaModel = new Conta($conn);

// 3. Lógica de Filtros
// Captura todos os filtros do GET
$filtros = $_GET; 

// 3.1 VERIFICA SE É PARA "DAR BAIXA"
if (isset($filtros['efetivar_id'])) {
    if ($transacaoModel->marcarComoEfetivado($filtros['efetivar_id'])) {
        unset($filtros['efetivar_id']);
        header("Location: relatorio.php?" . http_build_query($filtros));
        exit;
    }
}

// *** NOVO: 3.2 VERIFICA SE É PARA "EXCLUIR" ***
if (isset($filtros['excluir_id'])) {
    if ($transacaoModel->excluir($filtros['excluir_id'])) {
        // Sucesso
        unset($filtros['excluir_id']);
    } else {
        // Falha (não precisa de mensagem, só remove o param)
        unset($filtros['excluir_id']);
    }
    // Recarrega a página com os filtros atuais
    header("Location: relatorio.php?" . http_build_query($filtros));
    exit;
}
// *** FIM DA MUDANÇA ***

// 3.3 Define o Mês/Ano padrão
$data_padrao = strtotime('+1 month');
$mes_padrao = date('m', $data_padrao);
$ano_padrao = date('Y', $data_padrao);
$filtros['mes'] = $filtros['mes'] ?? $mes_padrao;
$filtros['ano'] = $filtros['ano'] ?? $ano_padrao;
$mes_selecionado = $filtros['mes'];
$ano_selecionado = $filtros['ano'];

// Arrays para os dropdowns de data
$meses = [
    '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
    '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
];
$anos = range(date('Y') + 1, date('Y') - 5);

// 4. Buscar dados
$listaTransacoes = $transacaoModel->buscarTransacoesFiltradas($filtros);
$listaCategorias = $categoriaModel->buscarTodas();
$listaContas = $contaModel->buscarTodas('todas'); // Busca todas para o filtro

// 5. Incluir o topo da página
$tituloPagina = "Relatório de Lançamentos";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="bg-white shadow rounded-lg overflow-x-auto">
    
    <form action="relatorio.php" method="GET">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th-table">Descrição</th>
                    <th class="th-table" style="width: 150px;">Vencimento</th>
                    <th class="th-table">Valor</th>
                    <th class="th-table" style="width: 120px;">Tipo</th>
                    <th class="th-table">Categoria</th>
                    <th class="th-table">Conta</th>
                    <th class="th-table" style="width: 120px;">Status</th>
                    <th class="th-table" style="width: 150px;">Ações</th>
                </tr>
                <tr class="bg-gray-100">
                    <th class="px-3 py-2">
                        <input type="text" name="filtro_descricao" placeholder="Filtrar Desc..."
                               value="<?php echo htmlspecialchars($filtros['filtro_descricao'] ?? ''); ?>"
                               class="filter-input">
                    </th>
                    <th class="px-3 py-2">
                        <div class="flex space-x-1">
                            <select name="mes" class="filter-input w-1/2">
                                <option value="">Mês</option>
                                <?php foreach ($meses as $num => $nome): ?>
                                    <option value="<?php echo $num; ?>" <?php echo ($num == $mes_selecionado) ? 'selected' : ''; ?>>
                                        <?php echo $nome; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="ano" class="filter-input w-1/2">
                                <option value="">Ano</option>
                                <?php foreach ($anos as $ano): ?>
                                    <option value="<?php echo $ano; ?>" <?php echo ($ano == $ano_selecionado) ? 'selected' : ''; ?>>
                                        <?php echo $ano; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </th>
                    <th class="px-3 py-2"></th>
                    <th class="px-3 py-2">
                        <select name="filtro_tipo" class="filter-input">
                            <option value="">Todos</option>
                            <option value="entrada" <?php echo ($filtros['filtro_tipo'] ?? '') == 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                            <option value="saida" <?php echo ($filtros['filtro_tipo'] ?? '') == 'saida' ? 'selected' : ''; ?>>Saída</option>
                        </select>
                    </th>
                    <th class="px-3 py-2">
                        <select name="filtro_categoria" class="filter-input">
                            <option value="">Todas</option>
                            <?php foreach ($listaCategorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" <?php echo ($filtros['filtro_categoria'] ?? '') == $categoria['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th class="px-3 py-2">
                        <select name="filtro_conta" class="filter-input">
                            <option value="">Todas</option>
                            <?php foreach ($listaContas as $conta): ?>
                                <option value="<?php echo $conta['id']; ?>" <?php echo ($filtros['filtro_conta'] ?? '') == $conta['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($conta['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th class="px-3 py-2">
                        <select name="filtro_status" class="filter-input">
                            <option value="">Todos</option>
                            <option value="pago" <?php echo ($filtros['filtro_status'] ?? '') == 'pago' ? 'selected' : ''; ?>>Pago</option>
                            <option value="pendente" <?php echo ($filtros['filtro_status'] ?? '') == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        </select>
                    </th>
                    <th class="px-3 py-2 text-right space-x-2">
                        <button type="submit" 
                                class="py-1 px-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            Filtrar
                        </button>
                        <a href="relatorio.php" 
                           class="py-1 px-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Limpar
                        </a>
                    </th>
                </tr>
            </thead>
            
            <?php 
            $total_entradas = 0;
            $total_saidas = 0;
            ?>

            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($listaTransacoes)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">Nenhum lançamento encontrado para estes filtros.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($listaTransacoes as $transacao): ?>
                        <?php
                        if ($transacao['tipo'] == 'entrada') {
                            $total_entradas += $transacao['valor'];
                        } else {
                            $total_saidas += $transacao['valor'];
                        }
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($transacao['descricao']); ?>
                                <?php if ($transacao['parcela_total'] > 1): ?>
                                    <span class="block text-xs text-gray-500">
                                        (Parc. <?php echo $transacao['parcela_atual']; ?>/<?php echo $transacao['parcela_total']; ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="td-table">
                                <?php echo (new DateTime($transacao['data_vencimento']))->format('d/m/Y'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $transacao['tipo'] == 'saida' ? 'text-red-600' : 'text-green-600'; ?>">
                                <?php echo ($transacao['tipo'] == 'saida' ? '-' : '+'); ?>
                                R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                            </td>
                            <td class="td-table">
                                <?php echo ucfirst($transacao['tipo']); ?>
                            </td>
                            <td class="td-table"><?php echo htmlspecialchars($transacao['nome_categoria']); ?></td>
                            <td class="td-table"><?php echo htmlspecialchars($transacao['nome_conta']); ?></td>
                            <td class="td-table">
                                <?php if (!is_null($transacao['data_efetivacao'])): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Pago
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pendente
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if (is_null($transacao['data_efetivacao'])): // IF é Previsto ?>
                                    <a href="relatorio.php?efetivar_id=<?php echo $transacao['id']; ?>&<?php echo http_build_query($filtros); ?>" 
                                       class="text-green-600 hover:text-green-900">
                                       Baixar
                                    </a>
                                    
                                    <a href="relatorio.php?excluir_id=<?php echo $transacao['id']; ?>&<?php echo http_build_query($filtros); ?>" 
                                       class="text-red-600 hover:text-red-900 ml-3"
                                       onclick="return confirm('Tem certeza que deseja excluir este lançamento previsto?');">
                                       Excluir
                                    </a>
                                <?php endif; // Fim do IF é Previsto ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
    
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        <?php $balanco_relatorio = $total_entradas - $total_saidas; ?>
        <div class="flex flex-wrap justify-end gap-x-6 gap-y-2">
            <div>
                <span class="text-sm font-medium text-gray-500">Total Entradas:</span>
                <span class="text-lg font-semibold text-green-600">
                    R$ <?php echo number_format($total_entradas, 2, ',', '.'); ?>
                </span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Total Saídas:</span>
                <span class="text-lg font-semibold text-red-600">
                    R$ <?php echo number_format($total_saidas, 2, ',', '.'); ?>
                </span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Balanço do Filtro:</span>
                <span class="text-lg font-semibold <?php echo $balanco_relatorio >= 0 ? 'text-blue-600' : 'text-red-600'; ?>">
                    R$ <?php echo number_format($balanco_relatorio, 2, ',', '.'); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
    .th-table {
        padding: 0.75rem 0.75rem; text-align: left; font-size: 0.75rem;
        font-weight: 500; color: #6B7280; text-transform: uppercase;
        vertical-align: top;
    }
    .td-table {
        padding: 1rem 0.75rem; white-space: nowrap; font-size: 0.875rem;
        color: #6B7280;
    }
    .filter-input {
        width: 100%;
        border-radius: 0.375rem; border: 1px solid #D1D5DB;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        font-size: 0.875rem; padding: 0.25rem 0.5rem;
    }
</style>

<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>