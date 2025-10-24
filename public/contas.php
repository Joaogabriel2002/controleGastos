<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Conta.php'; 

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$contaModel = new Conta($conn); 

$mensagem_sucesso = '';
$mensagem_erro = '';

// 2.1. VERIFICA SE É PARA SALVAR (Formulário via POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_conta'])) {
    if (!empty($_POST['nome']) && isset($_POST['saldo_inicial'])) {
        
        // *** MUDANÇA: Captura o valor do checkbox 'is_economia' ***
        $is_economia = isset($_POST['is_economia']) ? 1 : 0;
        
        // Passa o novo valor para o método salvar (que precisaremos atualizar)
        if ($contaModel->salvar($_POST['nome'], $_POST['saldo_inicial'], $_POST['tipo_conta'], $is_economia)) {
            $mensagem_sucesso = "Conta '{$_POST['nome']}' salva com sucesso!";
        } else {
            $mensagem_erro = "Erro ao salvar a conta.";
        }
    } else {
        $mensagem_erro = "Por favor, preencha pelo menos o Nome e o Saldo Inicial.";
    }
}

// 2.2. VERIFICA SE É PARA EXCLUIR (Link via GET)
if (isset($_GET['excluir_id'])) {
    if ($contaModel->excluir($_GET['excluir_id'])) {
        $mensagem_sucesso = "Conta excluída com sucesso!";
    } else {
        $mensagem_erro = "Erro ao excluir a conta. Verifique se ela não está sendo usada em uma transação.";
    }
}

// 3. Buscar os dados para exibir na tabela
$listaContas = $contaModel->buscarTodas(); // Vamos atualizar isso no próximo passo

// 4. Incluir o topo da página
$tituloPagina = "Gerenciar Contas";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <div class="md:col-span-1">
        <div class="bg-white p-6 shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">Nova Conta</h2>
            
            <form action="contas.php" method="POST">
                <input type="hidden" name="salvar_conta" value="1">
                
                <div class="mb-4">
                    <label for="nome" class="block text-sm font-medium text-gray-700">Nome da Conta</label>
                    <input type="text" name="nome" id="nome" placeholder="Ex: Carteira, Nubank" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div class="mb-4">
                    <label for="saldo_inicial" class="block text-sm font-medium text-gray-700">Saldo Inicial (R$)</label>
                    <input type="number" name="saldo_inicial" id="saldo_inicial" step="0.01" value="0.00" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="mb-4">
                    <label for="tipo_conta" class="block text-sm font-medium text-gray-700">Tipo (Opcional)</label>
                    <input type="text" name="tipo_conta" id="tipo_conta" placeholder="Ex: banco, carteira" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div class="mb-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_economia" id="is_economia" value="1"
                               class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_economia" class="ml-2 block text-sm font-medium text-gray-900">
                            É uma conta de Economia? (Ex: Poupança, Investimento)
                        </label>
                    </div>
                </div>
                
                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="md:col-span-2">
        
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Conta</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($listaContas)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhuma conta cadastrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listaContas as $conta): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($conta['nome']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($conta['is_economia']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Economia
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Trabalho / Dia-a-dia
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <a href="conta-editar.php?id=<?php echo $conta['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                    <a href="contas.php?excluir_id=<?php echo $conta['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza?');">
                                        Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>