<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    <aside class="w-64 bg-gray-800 border-r border-gray-700 hidden md:flex flex-col">
        <div class="h-16 flex items-center justify-center border-b border-gray-700">
            <h1 class="text-2xl font-bold tracking-wider">TOP<span class="text-pink-500">ADMIN</span></h1>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="<?= url('/admin') ?>" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                <i class="fas fa-chart-pie w-6"></i> Dashboard
            </a>
            <div class="flex items-center px-4 py-3 bg-gray-700 text-white rounded-lg">
                <i class="fas fa-users w-6"></i> Usuários
            </div>
            <a href="<?= url('/admin/photos') ?>" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                <i class="fas fa-camera w-6"></i> Moderar Fotos
            </a>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-16 bg-gray-800 border-b border-gray-700 flex items-center justify-between px-6">
            <h2 class="text-xl font-bold">Gestão de Usuários (<?= count($users) ?>)</h2>
            <a href="<?= url('/logout') ?>" class="text-red-400 hover:text-red-300 text-sm"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </header>

        <main class="flex-1 overflow-y-auto p-6 bg-gray-900">
            
            <div class="bg-gray-800 rounded-xl border border-gray-700 shadow-lg overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-700/50 text-gray-400 text-xs uppercase tracking-wider">
                            <th class="p-4 border-b border-gray-700">Usuário / Email</th>
                            <th class="p-4 border-b border-gray-700">Tipo</th>
                            <th class="p-4 border-b border-gray-700">Cadastro</th>
                            <th class="p-4 border-b border-gray-700">Status</th>
                            <th class="p-4 border-b border-gray-700 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 text-sm">
                        <?php foreach($users as $u): 
                            $isBanned = $u['status'] === 'banned';
                            $isAdmin = $u['role'] === 'admin';
                        ?>
                        <tr class="hover:bg-gray-700/30 transition">
                            
                            <td class="p-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-xs font-bold mr-3">
                                        <?= strtoupper(substr($u['email'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white"><?= $u['email'] ?></p>
                                        <?php if($u['has_profile']): ?>
                                            <p class="text-xs text-pink-400">Nome: <?= $u['model_name'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4">
                                <?php if($isAdmin): ?>
                                    <span class="bg-purple-500/20 text-purple-400 px-2 py-1 rounded text-xs font-bold border border-purple-500/30">ADMIN</span>
                                <?php elseif($u['has_profile']): ?>
                                    <span class="bg-pink-500/20 text-pink-400 px-2 py-1 rounded text-xs font-bold border border-pink-500/30">MODELO</span>
                                <?php else: ?>
                                    <span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded text-xs font-bold border border-blue-500/30">CLIENTE</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 text-gray-400">
                                <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                            </td>

                            <td class="p-4">
                                <span id="status-badge-<?= $u['id'] ?>" class="px-2 py-1 rounded-full text-xs font-bold flex items-center w-max 
                                    <?= $isBanned ? 'bg-red-500/20 text-red-500' : 'bg-green-500/20 text-green-500' ?>">
                                    <span class="w-2 h-2 rounded-full mr-2 <?= $isBanned ? 'bg-red-500' : 'bg-green-500' ?>"></span>
                                    <?= $isBanned ? 'Banido' : 'Ativo' ?>
                                </span>
                            </td>

                            <td class="p-4 text-right">
                                <?php if(!$isAdmin): // Não pode banir admin ?>
                                    <button onclick="toggleBan(<?= $u['id'] ?>)" id="btn-ban-<?= $u['id'] ?>" 
                                        class="text-xs font-bold px-3 py-1 rounded transition border 
                                        <?= $isBanned ? 'border-green-500 text-green-500 hover:bg-green-500 hover:text-white' : 'border-red-500 text-red-500 hover:bg-red-500 hover:text-white' ?>">
                                        <?= $isBanned ? 'Desbanir' : 'Banir' ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</div>

<script>
    const BASE_URL = "<?= url('/') ?>";

    async function toggleBan(userId) {
        if(!confirm('Tem certeza que deseja alterar o status deste usuário?')) return;

        try {
            const res = await fetch(`${BASE_URL}/api/admin/users/ban`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: userId})
            });
            const data = await res.json();

            if(data.success) {
                const badge = document.getElementById(`status-badge-${userId}`);
                const btn = document.getElementById(`btn-ban-${userId}`);
                const dot = badge.querySelector('span');

                if (data.new_status === 'banned') {
                    // Ficou Banido
                    badge.className = "px-2 py-1 rounded-full text-xs font-bold flex items-center w-max bg-red-500/20 text-red-500";
                    dot.className = "w-2 h-2 rounded-full mr-2 bg-red-500";
                    badge.innerHTML = ''; badge.appendChild(dot); badge.append('Banido');

                    btn.innerText = "Desbanir";
                    btn.className = "text-xs font-bold px-3 py-1 rounded transition border border-green-500 text-green-500 hover:bg-green-500 hover:text-white";
                } else {
                    // Ficou Ativo
                    badge.className = "px-2 py-1 rounded-full text-xs font-bold flex items-center w-max bg-green-500/20 text-green-500";
                    dot.className = "w-2 h-2 rounded-full mr-2 bg-green-500";
                    badge.innerHTML = ''; badge.appendChild(dot); badge.append('Ativo');

                    btn.innerText = "Banir";
                    btn.className = "text-xs font-bold px-3 py-1 rounded transition border border-red-500 text-red-500 hover:bg-red-500 hover:text-white";
                }
            } else {
                alert('Erro ao alterar status.');
            }
        } catch(e) { console.error(e); alert('Erro de conexão'); }
    }
</script>
</body>
</html>