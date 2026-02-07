<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - TOP Model</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <style>
        /* Scrollbar fina e moderna */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    <aside class="w-64 bg-gray-800 border-r border-gray-700 hidden md:flex flex-col">
        <div class="h-16 flex items-center justify-center border-b border-gray-700">
            <h1 class="text-2xl font-bold tracking-wider">TOP<span class="text-pink-500">ADMIN</span></h1>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 pl-2">Geral</p>
            
            <a href="<?= url('/admin') ?>" class="flex items-center px-4 py-3 bg-gray-700 text-white rounded-lg transition-colors">
                <i class="fas fa-chart-pie w-6"></i>
                <span class="font-medium">Dashboard</span>
            </a>

            <a href="<?= url('/admin/users') ?>" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                <i class="fas fa-users w-6"></i>
                <span class="font-medium">Usuários</span>
            </a>

            <a href="<?= url('/admin/photos') ?>" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors group">
                <i class="fas fa-camera w-6"></i>
                <span class="font-medium">Moderar Fotos</span>
                <?php if($pendingPhotos > 0): ?>
                    <span class="ml-auto bg-pink-600 text-white text-xs font-bold px-2 py-0.5 rounded-full group-hover:bg-pink-500"><?= $pendingPhotos ?></span>
                <?php endif; ?>
            </a>

            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-8 mb-2 pl-2">Financeiro</p>
            
            <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                <i class="fas fa-dollar-sign w-6"></i>
                <span class="font-medium">Pagamentos</span>
            </a>
            
            <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                <i class="fas fa-tags w-6"></i>
                <span class="font-medium">Planos</span>
            </a>
        </nav>

        <div class="p-4 border-t border-gray-700">
            <a href="<?= url('/') ?>" target="_blank" class="flex items-center text-sm text-gray-400 hover:text-white">
                <i class="fas fa-external-link-alt mr-2"></i> Ver Site
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="h-16 bg-gray-800 border-b border-gray-700 flex items-center justify-between px-6">
            <button class="md:hidden text-gray-400 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            <div class="flex items-center ml-auto space-x-4">
                <div class="flex items-center text-sm text-gray-300">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=random" class="w-8 h-8 rounded-full mr-2">
                    <span class="font-bold">Administrador</span>
                </div>
                <a href="<?= url('/logout') ?>" class="text-gray-400 hover:text-red-400" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-900 p-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase">Receita Total</p>
                            <h3 class="text-2xl font-bold text-white mt-1">R$ <?= number_format($revenue, 2, ',', '.') ?></h3>
                        </div>
                        <div class="p-2 bg-green-500/10 rounded-lg text-green-500">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-green-400 mt-4 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i> 12% este mês (Exemplo)
                    </p>
                </div>

                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase">Modelos Ativas</p>
                            <h3 class="text-2xl font-bold text-white mt-1"><?= $totalModels ?></h3>
                        </div>
                        <div class="p-2 bg-pink-500/10 rounded-lg text-pink-500">
                            <i class="fas fa-female text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase">Total Usuários</p>
                            <h3 class="text-2xl font-bold text-white mt-1"><?= $totalUsers ?></h3>
                        </div>
                        <div class="p-2 bg-blue-500/10 rounded-lg text-blue-500">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6 border border-yellow-600 shadow-lg relative overflow-hidden">
                    <div class="flex justify-between items-start z-10 relative">
                        <div>
                            <p class="text-xs font-medium text-yellow-500 uppercase">Aprovação Pendente</p>
                            <h3 class="text-2xl font-bold text-white mt-1"><?= $pendingPhotos ?> Fotos</h3>
                        </div>
                        <div class="p-2 bg-yellow-500/10 rounded-lg text-yellow-500">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                    </div>
                    <?php if($pendingPhotos > 0): ?>
                        <a href="<?= url('/admin/photos') ?>" class="block mt-4 text-xs font-bold text-yellow-400 hover:text-white transition">
                            Revisar Agora &rarr;
                        </a>
                    <?php else: ?>
                        <p class="mt-4 text-xs text-gray-500">Tudo em dia!</p>
                    <?php endif; ?>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2 bg-gray-800 rounded-xl border border-gray-700 p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-100 mb-4">Crescimento de Acessos</h3>
                    <div class="h-64">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-100 mb-4">Novos Cadastros</h3>
                    <div class="space-y-4">
                        <?php foreach($latestUsers as $u): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-xs text-gray-400 font-bold mr-3">
                                    <?= strtoupper(substr($u['email'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white truncate w-32"><?= explode('@', $u['email'])[0] ?></p>
                                    <p class="text-xs text-gray-500"><?= date('d/m H:i', strtotime($u['created_at'])) ?></p>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded bg-gray-700 text-gray-300"><?= $u['role'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= url('/admin/users') ?>" class="block mt-4 text-center text-sm text-pink-500 hover:text-pink-400 font-bold">Ver todos</a>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
    const ctx = document.getElementById('trafficChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            datasets: [{
                label: 'Visitas',
                data: [120, 190, 300, 500, 200, 300, 450],
                borderColor: '#ec4899', // Pink-500
                backgroundColor: 'rgba(236, 72, 153, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: '#374151' }, ticks: { color: '#9ca3af' } },
                x: { grid: { display: false }, ticks: { color: '#9ca3af' } }
            }
        }
    });
</script>

</body>
</html>