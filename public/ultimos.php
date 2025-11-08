<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';
// (Não precisamos de Categoria/Conta/Pessoa Models aqui, só do Transacao)

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$transacaoModel = new Transacao($conn); 

$mensagem_sucesso = '';
$mensagem_erro = '';

// Copiamos a lógica de "Baixar" e "Excluir" do relatorio.php
// (precisamos manter os filtros _GET nulos para o http_build_query)
$filtros = []; 

// 2.1 VERIFICA SE É PARA "DAR BAIXA"
if (isset($_GET['efetivar_id'])) {
    if ($transacaoModel->marcarComoEfetivado($_GET['efetivar_id'])) {
        $mensagem_sucesso = "Transação marcada como paga/recebida!";
    } else {
        $mensagem_erro = "Erro ao dar baixa.";
    }
}

// 2.2 VERIFICA SE É PARA "EXCLUIR"
if (isset($_GET['excluir_id'])) {
    if ($transacaoModel->excluir($_GET['excluir_id'])) {
        $mensagem_sucesso = "Lançamento excluído com sucesso!";
    } else {
        $mensagem_erro = "Erro ao excluir.";
    }
}

// 3. Buscar os dados
// Este método busca os 50 mais recentes, misturando previstos e realizados
$listaTransacoes = $transacaoModel->buscarTodasComDetalhes();

// 4. Incluir o topo da página
$tituloPagina = "Últimos Lançamentos"; // O título da nova página
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<?php if ($mensagem_sucesso): ?>
    <div class='rounded-md bg-green-50 p-4 mb-4 text-sm text-green-700'><?php echo $mensagem_sucesso; ?></div>
<?php endif; ?>
<?php if ($mensagem_erro): ?>
    <div class='rounded-md bg-red-50 p-4 mb-4 text-sm text-red-700'><?php echo $mensagem_erro; ?></div>
<?php endif; ?>


<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="th-table">Descrição</th>
                <th class="th-table">Valor</th>
                <th class="th-table">Categoria</th>
                <th class="th-table">Conta</th>
                <th class="th-table">Vencimento</th>
                <th class="th-table">Status</th>
                <th class="th-table text-right">Ações</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($listaTransacoes)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Nenhum lançamento encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($listaTransacoes as $transacao): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($transacao['descricao']); ?>
                            <?php if ($transacao['parcela_total'] > 1): ?>
                                <span class="block text-xs text-gray-500">
                                    (Parc. <?php echo $transacao['parcela_atual']; ?>/<?php echo $transacao['parcela_total']; ?>)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $transacao['tipo'] == 'saida' ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo ($transacao['tipo'] == 'saida' ? '-' : '+'); ?>
                            R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                        </td>
                        <td class="td-table"><?php echo htmlspecialchars($transacao['nome_categoria']); ?></td>
                        <td class="td-table"><?php echo htmlspecialchars($transacao['nome_conta']); ?></td>
                        <td class="td-table"><?php echo (new DateTime($transacao['data_vencimento']))->format('d/m/Y'); ?></td>
                        
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

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                            <?php if (is_null($transacao['data_efetivacao'])): // Se for PREVISTO ?>
                                <a href="ultimos.php?efetivar_id=<?php echo $transacao['id']; ?>" 
                                   class="text-green-600 hover:text-green-900" title="Marcar como pago/recebido">
                                   Baixar
                                </a>
                                <a href="ultimos.php?excluir_id=<?php echo $transacao['id']; ?>" 
                                   class="text-red-600 hover:text-red-900" title="Excluir"
                                   onclick="return confirm('Tem certeza?');">
                                   Excluir
                                </a>
                            <?php else: // Se for CONCRETIZADO (pago) ?>
                                <a href="ultimos.php?excluir_id=<?php echo $transacao['id']; ?>" 
                                   class="text-red-600 hover:text-red-900" title="Excluir"
                                   onclick="return confirm('Tem certeza?');">
                                   Excluir
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .th-table {
        padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem;
        font-weight: 500; color: #6B7280; text-transform: uppercase;
    }
    .td-table {
        padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem;
        color: #6B7280;
    }
</style>

<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>