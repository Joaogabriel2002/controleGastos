<?php
// 1. Incluir os arquivos fundamentais
require_once '../config/database.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/Conta.php'; // Model de Conta

// 2. Lógica da Página
$db = new Database();
$conn = $db->getConnection();
$contaModel = new Conta($conn); // Instancia o Model

$mensagem_sucesso = '';
$mensagem_erro = '';
$conta = null;

// 2.1. VERIFICA SE UM ID FOI PASSADO (via GET)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: contas.php");
    exit;
}

$id = $_GET['id'];

// 2.2. VERIFICA SE O FORMULÁRIO FOI ENVIADO (via POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['atualizar_conta'])) {
    if (!empty($_POST['nome']) && isset($_POST['saldo_inicial']) && !empty($_POST['id'])) {
        
        // Tenta atualizar
        if ($contaModel->atualizar($_POST['id'], $_POST['nome'], $_POST['saldo_inicial'], $_POST['tipo_conta'])) {
            $mensagem_sucesso = "Conta atualizada com sucesso! Redirecionando...";
            header("refresh:2;url=contas.php");
        } else {
            $mensagem_erro = "Erro ao atualizar a conta.";
        }
    } else {
        $mensagem_erro = "Por favor, preencha os campos obrigatórios.";
    }
}

// 2.3. BUSCAR OS DADOS DA CONTA PARA PREENCHER O FORMULÁRIO
$conta = $contaModel->buscarPorId($id);

// Se não encontrou a conta, redireciona
if (!$conta) {
    header("Location: contas.php");
    exit;
}


// 3. Incluir o topo da página
$tituloPagina = "Editar Conta";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900">
            <?php echo $tituloPagina; ?>: 
            <span class="text-indigo-600"><?php echo htmlspecialchars($conta['nome']); ?></span>
        </h1>`;
</script>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-1">
        
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

        <div class="bg-white p-6 shadow rounded-lg">
            
            <form action="conta-editar.php?id=<?php echo $id; ?>" method="POST">
                <input type="hidden" name="id" value="<?php echo $conta['id']; ?>">
                <input type="hidden" name="atualizar_conta" value="1">
                
                <div class="mb-4">
                    <label for="nome" class="block text-sm font-medium text-gray-700">Nome da Conta</label>
                    <input type="text" name="nome" id="nome" required 
                           value="<?php echo htmlspecialchars($conta['nome']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div class="mb-4">
                    <label for="saldo_inicial" class="block text-sm font-medium text-gray-700">Saldo Inicial (R$)</label>
                    <input type="number" name="saldo_inicial" id="saldo_inicial" step="0.01" required
                           value="<?php echo htmlspecialchars($conta['saldo_inicial']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="mb-4">
                    <label for="tipo_conta" class="block text-sm font-medium text-gray-700">Tipo (Opcional)</label>
                    <input type="text" name="tipo_conta" id="tipo_conta" 
                           value="<?php echo htmlspecialchars($conta['tipo_conta']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div class="flex justify-between items-center">
                    <button type="submit" 
                            class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Salvar Alterações
                    </button>
                    <a href="contas.php" class="text-sm text-gray-600 hover:text-gray-900">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="md:col-span-2">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
            <p class="text-sm text-yellow-700">
                <strong>Atenção:</strong> O 'Saldo Inicial' é a base de todo o cálculo. Defina aqui o seu saldo (ou dívida) no momento em que você <strong>começou</strong> a usar o sistema.
                <br>Para cartões de crédito ou financiamentos, use um valor negativo (ex: <strong>-1500.00</strong>).
            </p>
        </div>
    </div>
</div>

<?php
// 4. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>