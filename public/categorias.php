<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Categoria.php'; // Incluímos o novo Model

// 2. Lógica da Página (Processamento de Formulários)
$db = new Database();
$conn = $db->getConnection();
$categoriaModel = new Categoria($conn); // Instancia o Model, passando a conexão

$mensagem_sucesso = '';
$mensagem_erro = '';

// 2.1. VERIFICA SE É PARA SALVAR (Formulário via POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_categoria'])) {
    if (!empty($_POST['nome']) && !empty($_POST['tipo'])) {
        // Tenta salvar
        if ($categoriaModel->salvar($_POST['nome'], $_POST['tipo'])) {
            $mensagem_sucesso = "Categoria '{$_POST['nome']}' salva com sucesso!";
        } else {
            $mensagem_erro = "Erro ao salvar a categoria.";
        }
    } else {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    }
}

// 2.2. VERIFICA SE É PARA EXCLUIR (Link via GET)
if (isset($_GET['excluir_id'])) {
    // Tenta excluir
    if ($categoriaModel->excluir($_GET['excluir_id'])) {
        $mensagem_sucesso = "Categoria excluída com sucesso!";
    } else {
        $mensagem_erro = "Erro ao excluir a categoria. Verifique se ela não está sendo usada em uma transação.";
    }
}

// 3. Buscar os dados para exibir na tabela
$listaCategorias = $categoriaModel->buscarTodas();


// 4. Incluir o topo da página
$tituloPagina = "Gerenciar Categorias";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <div class="md:col-span-1">
        <div class="bg-white p-6 shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">Nova Categoria</h2>
            
            <form action="categorias.php" method="POST">
                <input type="hidden" name="salvar_categoria" value="1">
                
                <div class="mb-4">
                    <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" id="nome" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div class="mb-4">
                    <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="tipo" id="tipo" required 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Selecione...</option>
                        <option value="saida">Saída (Gasto)</option>
                        <option value="entrada">Entrada (Ganho)</option>
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

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($listaCategorias)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhuma categoria cadastrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listaCategorias as $categoria): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($categoria['tipo'] == 'saida'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Saída
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Entrada
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="categorias.php?excluir_id=<?php echo $categoria['id']; ?>" 
                                       class="text-red-600 hover:text-red-900"
                                       onclick="return confirm('Tem certeza que deseja excluir esta categoria?');">
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