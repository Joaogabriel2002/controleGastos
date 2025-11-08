<?php
// 1. Incluir o topo da página
$tituloPagina = "Outros Cadastros e Configurações";
require_once '../src/includes/header.php'; 
?>

<script>
    document.querySelector('header.bg-white div').innerHTML = 
        `<h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo $tituloPagina; ?></h1>`;
</script>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <a href="contas.php" 
       class="block p-6 bg-white shadow rounded-lg border-l-4 border-indigo-500
              hover:bg-gray-50 transition-colors duration-150">
        <h2 class="text-xl font-semibold mb-2 text-gray-900">Gerenciar Contas</h2>
        <p class="text-sm text-gray-600">
            Cadastre suas contas de trabalho (dia-a-dia) e suas contas de economia (poupança, investimentos).
        </p>
    </a>
    
    <a href="categorias.php" 
       class="block p-6 bg-white shadow rounded-lg border-l-4 border-blue-500
              hover:bg-gray-50 transition-colors duration-150">
        <h2 class="text-xl font-semibold mb-2 text-gray-900">Gerenciar Categorias</h2>
        <p class="text-sm text-gray-600">
            Classifique suas entradas e saídas (Ex: Alimentação, Salário, Transferência).
        </p>
    </a>
    
    <a href="pessoas.php" 
       class="block p-6 bg-white shadow rounded-lg border-l-4 border-yellow-500
              hover:bg-gray-50 transition-colors duration-150">
        <h2 class="text-xl font-semibold mb-2 text-gray-900">Gerenciar Pessoas</h2>
        <p class="text-sm text-gray-600">
            Cadastre pessoas ou empresas para rastrear dívidas a pagar ou a receber.
        </p>
    </a>

</div>

<?php
// 2. Incluir o rodapé
require_once '../src/includes/footer.php'; 
?>