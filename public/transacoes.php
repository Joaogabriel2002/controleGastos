<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';
require_once '../src/Models/Categoria.php';
require_once '../src/Models/Conta.php';
require_once '../src/Models/Pessoa.php'; // *** NOVO ***

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();

// Instancia todos os models
$transacaoModel = new Transacao($conn);
$categoriaModel = new Categoria($conn);
$contaModel = new Conta($conn);
$pessoaModel = new Pessoa($conn); // *** NOVO ***

$mensagem_sucesso = '';
$mensagem_erro = '';

// 2.1. VERIFICA SE É PARA SALVAR (Formulário via POST)
// (Nenhuma mudança aqui, o Model->salvar() já foi preparado para 'pessoa_id')
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_transacao'])) {
    if (empty($_POST['descricao']) || empty($_POST['valor']) || empty($_POST['tipo']) || 
        empty($_POST['data_vencimento']) || empty($_POST['conta_id']) || empty($_POST['categoria_id'])) {
        
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios.";
    
    } else {
        if ($transacaoModel->salvar($_POST)) {
            $mensagem_sucesso = "Transação salva com sucesso!";
        } else {
            $mensagem_erro = "Erro ao salvar a transação.";
        }
    }
}

// 2.2. VERIFICA SE É PARA EXCLUIR (Link via GET)
if (isset($_GET['excluir_id'])) {
    if ($transacaoModel->excluir($_GET['excluir_id'])) {
        $mensagem_sucesso = "Transação excluída com sucesso!";
    } else {
        $mensagem_erro = "Erro ao excluir a transação.";
    }
}

// 2.3. VERIFICA SE É PARA "DAR BAIXA" (EFETIVAR)
if (isset($_GET['efetivar_id'])) {
    if ($transacaoModel->marcarComoEfetivado($_GET['efetivar_id'])) {
        $mensagem_sucesso = "Transação marcada como paga/recebida!";
    } else {
        $mensagem_erro = "Erro ao dar baixa na transação.";
    }
}

// 3. Buscar dados para os Dropdowns e Tabela
$listaCategorias = $categoriaModel->buscarTodas();
$listaContas = $contaModel->buscarTodas();
$listaPessoas = $pessoaModel->buscarTodas(); // *** NOVO ***
$listaTransacoes = $transacaoModel->buscarTodasComDetalhes();

// 4. Incluir o topo da página
$tituloPagina = "Lançamentos";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<?php if ($mensagem_sucesso): ?>
    <div class='rounded-md bg-green-50 p-4 mb-4 text-sm text-green-700'>
        <?php echo $mensagem_sucesso; ?>
    </div>
<?php endif; ?>
<?php if ($mensagem_erro): ?>
    <div class='rounded-md bg-red-50 p-4 mb-4 text-sm text-red-700'>
        <?php echo $mensagem_erro; ?>
    </div>
<?php endif; ?>


<div class="bg-white p-6 shadow rounded-lg mb-6">
    <h2 class="text-xl font-semibold mb-4">Novo Lançamento</h2>
    
    <form action="transacoes.php" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <input type="hidden" name="salvar_transacao" value="1">
        
        <div class="md:col-span-2">
            <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
            <input type="text" name="descricao" id="descricao" required class="mt-1 input-form">
        </div>
        
        <div>
            <label for="valor" class="block text-sm font-medium text-gray-700">Valor (R$)</label>
            <input type="number" name="valor" id="valor" step="0.01" required class="mt-1 input-form">
        </div>
        
        <div>
            <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo</label>
            <select name="tipo" id="tipo" required class="mt-1 input-form">
                <option value="">Selecione...</option>
                <option value="saida">Saída (Gasto)</option>
                <option value="entrada">Entrada (Ganho)</option>
            </select>
        </div>

        <div>
            <label for="data_vencimento" class="block text-sm font-medium text-gray-700">Data Vencimento</label>
            <input type="date" name="data_vencimento" id="data_vencimento" value="<?php echo date('Y-m-d'); ?>" required class="mt-1 input-form">
        </div>

        <div>
            <label for="conta_id" class="block text-sm font-medium text-gray-700">Conta</label>
            <select name="conta_id" id="conta_id" required class="mt-1 input-form">
                <option value="">Selecione...</option>
                <?php foreach ($listaContas as $conta): ?>
                    <option value="<?php echo $conta['id']; ?>">
                        <?php echo htmlspecialchars($conta['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="categoria_id" class="block text-sm font-medium text-gray-700">Categoria</label>
            <select name="categoria_id" id="categoria_id" required class="mt-1 input-form">
                <option value="">Selecione...</option>
                <?php foreach ($listaCategorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" class="tipo-<?php echo $categoria['tipo']; ?>">
                        <?php echo htmlspecialchars($categoria['nome']); ?> (<?php echo $categoria['tipo']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="pessoa_id" class="block text-sm font-medium text-gray-700">Pessoa (Opcional)</label>
            <select name="pessoa_id" id="pessoa_id" class="mt-1 input-form">
                <option value="">Nenhuma</option>
                <?php foreach ($listaPessoas as $pessoa): ?>
                    <option value="<?php echo $pessoa['id']; ?>">
                        <?php echo htmlspecialchars($pessoa['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="parcela_total" class="block text-sm font-medium text-gray-700">Nº de Parcelas</label>
            <input type="number" name="parcela_total" id="parcela_total" value="1" min="1" class="mt-1 input-form">
        </div>
        
        <div class="md:col-span-5 flex justify-end">
            <button type="submit" 
                    class="w-full md:w-auto py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Salvar Lançamento
            </button>
        </div>
    </form>
</div>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <h2 class="text-xl font-semibold p-6">Últimos Lançamentos</h2>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="th-table">Descrição</th>
                <th class="th-table">Valor</th>
                <th class="th-table">Categoria</th>
                <th class="th-table">Conta</th>
                <th class="th-table">Vencimento</th>
                <th class="th-table">Parcela</th>
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
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $transacao['tipo'] == 'saida' ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo ($transacao['tipo'] == 'saida' ? '-' : '+'); ?>
                            R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                        </td>
                        <td class="td-table"><?php echo htmlspecialchars($transacao['nome_categoria']); ?></td>
                        <td class="td-table"><?php echo htmlspecialchars($transacao['nome_conta']); ?></td>
                        
                        <td class="td-table">
                            <?php echo (new DateTime($transacao['data_vencimento']))->format('d/m/Y'); ?>
                            <?php if (!is_null($transacao['data_efetivacao'])): ?>
                                <span class="block text-xs text-green-600 font-semibold">
                                    (Pago)
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="td-table">
                            <?php if ($transacao['parcela_total'] > 1): ?>
                                <?php echo $transacao['parcela_atual']; ?>/<?php echo $transacao['parcela_total']; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                            <?php if (is_null($transacao['data_efetivacao'])): ?>
                                <a href="transacoes.php?efetivar_id=<?php echo $transacao['id']; ?>" 
                                   class="text-green-600 hover:text-green-900"
                                   title="Marcar como pago/recebido">
                                   Baixar
                                </a>
                            <?php endif; ?>
                            
                            <a href="transacoes.php?excluir_id=<?php echo $transacao['id']; ?>" 
                               class="text-red-600 hover:text-red-900"
                               title="Excluir"
                               onclick="return confirm('Tem certeza que deseja excluir esta transação?');">
                                Excluir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .input-form {
        width: 100%;
        border-radius: 0.375rem;
        border: 1px solid #D1D5DB; /* border-gray-300 */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
    }
    .th-table {
        padding: 0.75rem 1.5rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 500;
        color: #6B7280; /* text-gray-500 */
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .td-table {
        padding: 1rem 1.5rem;
        white-space: nowrap;
        font-size: 0.875rem;
        color: #6B7280; /* text-gray-500 */
    }
</style>

<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>