<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Transacao.php';
require_once '../src/Models/Categoria.php';
require_once '../src/Models/Conta.php';
require_once '../src/Models/Pessoa.php';

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();

$transacaoModel = new Transacao($conn);
$categoriaModel = new Categoria($conn);
$contaModel = new Conta($conn);
$pessoaModel = new Pessoa($conn);

$mensagem_sucesso = '';
$mensagem_erro = '';

// 2.1. VERIFICA SE É PARA SALVAR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_transacao'])) {
    
    if (empty($_POST['descricao']) || empty($_POST['valor']) || 
        empty($_POST['data_vencimento']) || empty($_POST['conta_id'])) {
        
        $mensagem_erro = "Por favor, preencha Descrição, Valor, Data e Conta de Origem.";
    
    } else {
        if (!empty($_POST['destino_conta_id'])) {
            // É uma Transferência
            if ($_POST['conta_id'] == $_POST['destino_conta_id']) {
                $mensagem_erro = "A conta de Origem e Destino não podem ser a mesma.";
            } else {
                if ($transacaoModel->salvarTransferencia($_POST)) {
                    $mensagem_sucesso = "Transferência salva com sucesso!";
                } else {
                    $mensagem_erro = "Erro ao salvar a transferência. (Verifique se a categoria 'Transferência' existe)";
                }
            }
        } else {
            // É um Lançamento Normal
            if (empty($_POST['tipo']) || empty($_POST['categoria_id'])) {
                 $mensagem_erro = "Para um lançamento normal, 'Tipo' e 'Categoria' são obrigatórios.";
            } else {
                if ($transacaoModel->salvar($_POST)) {
                    $mensagem_sucesso = "Lançamento salvo com sucesso!";
                } else {
                    $mensagem_erro = "Erro ao salvar o lançamento.";
                }
            }
        }
    }
}

// 2.2. VERIFICA SE É PARA EXCLUIR
if (isset($_GET['excluir_id'])) {
    if ($transacaoModel->excluir($_GET['excluir_id'])) {
        $mensagem_sucesso = "Transação excluída com sucesso!";
    } else { $mensagem_erro = "Erro ao excluir."; }
}

// 2.3. VERIFICA SE É PARA "DAR BAIXA"
if (isset($_GET['efetivar_id'])) {
    if ($transacaoModel->marcarComoEfetivado($_GET['efetivar_id'])) {
        $mensagem_sucesso = "Transação marcada como paga/recebida!";
    } else { $mensagem_erro = "Erro ao dar baixa."; }
}

// 3. Buscar dados
$listaCategorias = $categoriaModel->buscarTodas();
$listaContas = $contaModel->buscarTodas('todas'); // Puxa TODAS as contas (Trabalho, Economia, Empréstimos)
$listaPessoas = $pessoaModel->buscarTodas(); 

// 4. Incluir o topo da página
$tituloPagina = "Novo Lançamento";
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


<div class="bg-white p-6 shadow rounded-lg mb-6">
    <h2 class="text-xl font-semibold mb-4">Novo Lançamento / Transferência</h2>
    
    <form action="transacoes.php" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            <label for="data_vencimento" class="block text-sm font-medium text-gray-700">Data Vencimento/Transferência</label>
            <input type="date" name="data_vencimento" id="data_vencimento" value="<?php echo date('Y-m-d'); ?>" required class="mt-1 input-form">
        </div>
        
        <hr class="md:col-span-4 my-2 border-t border-gray-200">
        
        <div class="md:col-span-2">
            <label for="conta_id" class="block text-sm font-medium text-gray-700">Conta (Origem)</label>
            <select name="conta_id" id="conta_id" required class="mt-1 input-form">
                <option value="">Selecione...</option>
                <?php foreach ($listaContas as $conta): ?>
                    <option value="<?php echo $conta['id']; ?>">
                        <?php echo htmlspecialchars($conta['nome']); ?>
                        (<?php echo htmlspecialchars(ucfirst($conta['tipo_pote'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="md:col-span-2">
            <label for="destino_conta_id" class="block text-sm font-medium text-gray-700">Conta de Destino (Opcional: p/ Transferência)</label>
            <select name="destino_conta_id" id="destino_conta_id" class="mt-1 input-form">
                <option value="">Nenhuma (Lançamento Normal)</option>
                <?php foreach ($listaContas as $conta): ?>
                    <option value="<?php echo $conta['id']; ?>">
                        <?php echo htmlspecialchars($conta['nome']); ?>
                        (<?php echo htmlspecialchars(ucfirst($conta['tipo_pote'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <hr class="md:col-span-4 my-2 border-t border-gray-200">

        <div id="campos_normais" class="md:col-span-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <div class="campo_normal">
                <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo</label>
                <select name="tipo" id="tipo" class="mt-1 input-form">
                    <option value="">Selecione...</option>
                    <option value="saida">Saída (Gasto)</option>
                    <option value="entrada">Entrada (Ganho)</option>
                </select>
            </div>
            
            <div class="campo_normal">
                <label for="categoria_id" class="block text-sm font-medium text-gray-700">Categoria</label>
                <select name="categoria_id" id="categoria_id" class="mt-1 input-form">
                    <option value="">Selecione...</option>
                    <?php foreach ($listaCategorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>">
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="campo_normal">
                <label for="parcela_total" class="block text-sm font-medium text-gray-700">Nº de Parcelas</label>
                <input type="number" name="parcela_total" id="parcela_total" value="1" min="1" class="mt-1 input-form">
            </div>
            
            <div class="campo_normal">
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
        </div>
        
        <div class="md:col-span-4 flex justify-end">
            <button type="submit" 
                    class="w-full md:w-auto py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Salvar Lançamento
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const destinoSelect = document.getElementById('destino_conta_id');
    const camposNormais = document.getElementById('campos_normais');
    
    function toggleCamposNormais() {
        if (destinoSelect.value) { 
            camposNormais.style.display = 'none'; 
        } else {
            camposNormais.style.display = 'grid'; 
        }
    }
    toggleCamposNormais();
    destinoSelect.addEventListener('change', toggleCamposNormais);
});
</script>

<style>
    .input-form {
        width: 100%;
        border-radius: 0.375rem;
        border: 1px solid #D1D5DB;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
</style>

<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>