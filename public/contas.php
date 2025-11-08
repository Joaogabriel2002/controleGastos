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

// 2.1. VERIFICA SE É PARA SALVAR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_conta'])) {
    if (!empty($_POST['nome']) && isset($_POST['saldo_inicial'])) {
        
        $tipo_pote = $_POST['tipo_pote'] ?? 'trabalho';
        
        if ($contaModel->salvar($_POST['nome'], $_POST['saldo_inicial'], $_POST['tipo_conta'], $tipo_pote)) {
            $mensagem_sucesso = "Conta '{$_POST['nome']}' salva com sucesso!";
        } else {
            $mensagem_erro = "Erro ao salvar a conta.";
        }
    } else {
        $mensagem_erro = "Por favor, preencha pelo menos o Nome e o Saldo Inicial.";
    }
}

// 2.2. VERIFICA SE É PARA EXCLUIR
if (isset($_GET['excluir_id'])) {
    if ($contaModel->excluir($_GET['excluir_id'])) {
        $mensagem_sucesso = "Conta excluída com sucesso!";
    } else {
        $mensagem_erro = "Erro ao excluir a conta. Verifique se ela não está sendo usada em uma transação.";
    }
}

// 3. Buscar os dados para exibir na tabela
$listaContas = $contaModel->buscarTodas('todas'); // Mostra todas as contas

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
        
        <?php if ($mensagem_sucesso): ?>
            <div class='rounded-md bg-green-50 p-4 mb-4 text-sm text-green-700'><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if ($mensagem_erro): ?>
            <div class='rounded-md bg-red-50 p-4 mb-4 text-sm text-red-700'><?php echo $mensagem_erro; ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">Nova Conta</h2>
            
            <form action="contas.php" method="POST">
                <input type="hidden" name="salvar_conta" value="1">
                
                <div class="mb-4">
                    <label for="nome" class="block text-sm font-medium text-gray-700">Nome da Conta</label>
                    <input type="text" name="nome" id="nome" required class="mt-1 input-form">
                </div>
                
                <div class="mb-4">
                    <label for="saldo_inicial" class="block text-sm font-medium text-gray-700">Saldo Inicial (R$)</label>
                    <input type="number" name="saldo_inicial" id="saldo_inicial" step="0.01" value="0.00" required class="mt-1 input-form">
                </div>

                <div class="mb-4">
                    <label for="tipo_conta" class="block text-sm font-medium text-gray-700">Tipo (Opcional)</label>
                    <input type="text" name="tipo_conta" id="tipo_conta" placeholder="Ex: banco, carteira" class="mt-1 input-form">
                </div>
                
                <div class="mb-4">
                    <label for="tipo_pote" class="block text-sm font-medium text-gray-700">Qual o "Pote" desta conta?</label>
                    <select name="tipo_pote" id="tipo_pote" required class="mt-1 input-form">
                        <option value="trabalho">Trabalho / Dia-a-dia (Aparece no Dashboard)</option>
                        <option value="economia">Economias (Aparece na aba Economias)</option>
                        <option value="emprestimos">Empréstimos / Dívidas a Receber</option>
                    </select>
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
                        <th class="th-table">Nome</th>
                        <th class="th-table">Tipo de Pote</th>
                        <th class="th-table text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($listaContas)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhuma conta cadastrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        // Mapeia os nomes técnicos para nomes amigáveis
                        $nomes_potes = [
                            'trabalho' => 'Trabalho / Dia-a-dia',
                            'economia' => 'Economia',
                            'emprestimos' => 'Empréstimos'
                        ];
                        // Mapeia os potes para cores
                        $cores_potes = [
                            'trabalho' => 'bg-gray-100 text-gray-800',
                            'economia' => 'bg-blue-100 text-blue-800',
                            'emprestimos' => 'bg-yellow-100 text-yellow-800'
                        ];
                        ?>
                        <?php foreach ($listaContas as $conta): ?>
                            <tr>
                                <td class="td-table-main">
                                    <?php echo htmlspecialchars($conta['nome']); ?>
                                </td>
                                <td class="td-table">
                                    <?php 
                                    $pote = $conta['tipo_pote'];
                                    $cor = $cores_potes[$pote] ?? 'bg-gray-100 text-gray-800';
                                    $nome = $nomes_potes[$pote] ?? ucfirst($pote);
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $cor; ?>">
                                        <?php echo $nome; ?>
                                    </span>
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

<style>
    .input-form {
        width: 100%;
        border-radius: 0.375rem;
        border: 1px solid #D1D5DB; /* border-gray-300 */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
    }
    .th-table {
        padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem;
        font-weight: 500; color: #6B7280; text-transform: uppercase;
    }
    .td-table {
        padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem;
        color: #6B7280;
    }
    .td-table-main {
        padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem;
        font-weight: 500; color: #111827; /* text-gray-900 */
    }
</style>

<?php
// 5. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>