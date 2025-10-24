<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Gastos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full">

<div class="min-h-full">
    <nav class="bg-gray-800">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-white font-bold text-xl">Gastos</span>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <?php 
                                // Pega o nome do arquivo atual (ex: "categorias.php")
                                $pagina_atual = basename($_SERVER['PHP_SELF']);
                                
                               $links = [
                                    'index.php' => 'Dashboard',
                                    'economias.php' => 'Economias',
                                    'calendario.php' => 'Calendário', 
                                    'transacoes.php' => 'Lançamentos',
                                    'relatorio.php' => 'Relatório',
                                    'graficos.php' => 'Gráficos', // <-- NOVO LINK
                                    'categorias.php' => 'Categorias',
                                    'contas.php' => 'Contas',
                                    'pessoas.php' => 'Pessoas'
                                ];

                                foreach ($links as $url => $titulo) {
                                    // Compara o link com a página atual
                                    $ativo = ($pagina_atual == $url)
                                        ? 'bg-gray-900 text-white' // Classe se ATIVO
                                        : 'text-gray-300 hover:bg-gray-700 hover:text-white'; // Classe se INATIVO
                                    
                                    echo "<a href='{$url}' class='{$ativo} rounded-md px-3 py-2 text-sm font-medium'>{$titulo}</a>";
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            </div>
    </header>

    <main>
        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
        

