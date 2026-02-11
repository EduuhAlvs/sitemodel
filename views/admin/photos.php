<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderar Fotos - Admin</title>
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
                <i class="fas fa-arrow-left w-6"></i> Voltar ao Dashboard
            </a>
            <div class="px-4 py-3 bg-gray-700 text-white rounded-lg">
                <i class="fas fa-camera w-6"></i> Moderar Fotos
            </div>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-16 bg-gray-800 border-b border-gray-700 flex items-center justify-between px-6">
            <h2 class="text-xl font-bold">Fila de Aprovação (<?= count($photos) ?>)</h2>
            <div class="text-sm text-gray-400">Revisando conteúdo antes de publicar</div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 bg-gray-900">
            <div class="max-w-7xl mx-auto w-full">

            <?php if(empty($photos)): ?>
                <div class="flex flex-col items-center justify-center h-full text-gray-500">
                    <div class="w-24 h-24 bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-check text-4xl text-green-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-300">Tudo Limpo!</h3>
                    <p>Nenhuma foto pendente de revisão.</p>
                </div>
            <?php else: ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach($photos as $photo): ?>
                    <div class="bg-gray-800 rounded-xl overflow-hidden shadow-lg border border-gray-700 flex flex-col group transition hover:border-gray-600" id="photo-<?= $photo['id'] ?>">

                        <div class="p-3 bg-gray-800 border-b border-gray-700 flex justify-between items-center">
                            <span class="font-bold text-sm text-pink-400 truncate max-w-[150px]">
                                <i class="fas fa-user-circle mr-1"></i> <?= $photo['display_name'] ?>
                            </span>
                            <span class="text-xs text-gray-500"><?= date('d/m H:i', strtotime($photo['created_at'])) ?></span>
                        </div>

                        <div class="relative aspect-[3/4] bg-gray-900 overflow-hidden">
                            <img src="<?= url('/' . $photo['file_path']) ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">

                            <a href="<?= url('/' . $photo['file_path']) ?>" target="_blank" class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300">
                                <span class="bg-black/50 text-white px-3 py-1 rounded-full text-sm border border-white/30 backdrop-blur-sm">
                                    <i class="fas fa-search-plus mr-1"></i> Ampliar
                                </span>
                            </a>
                        </div>

                        <div class="p-4 flex space-x-3 mt-auto bg-gray-800 border-t border-gray-700">
                            <button onclick="rejectPhoto(<?= $photo['id'] ?>)" class="flex-1 bg-red-500/10 hover:bg-red-600 text-red-500 hover:text-white py-2 rounded-lg font-bold transition flex items-center justify-center border border-red-500/20">
                                <i class="fas fa-times mr-2"></i> Rejeitar
                            </button>
                            <button onclick="approvePhoto(<?= $photo['id'] ?>)" class="flex-1 bg-green-500/10 hover:bg-green-600 text-green-500 hover:text-white py-2 rounded-lg font-bold transition flex items-center justify-center border border-green-500/20">
                                <i class="fas fa-check mr-2"></i> Aprovar
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

            </div>
        </main>
    </div>
</div>

<script>
    const BASE_URL = "<?= url('/') ?>";

    async function approvePhoto(id) {
        const card = document.getElementById('photo-' + id);
        // Feedback visual imediato
        card.style.opacity = '0.5';
        card.style.pointerEvents = 'none';

        try {
            const res = await fetch(`${BASE_URL}/api/admin/photos/approve`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id})
            });
            const data = await res.json();

            if(data.success) {
                // Animação de saída
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    updateCounter();
                }, 200);
            } else {
                alert('Erro ao aprovar foto.');
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
        } catch(e) { console.error(e); }
    }

    async function rejectPhoto(id) {
        if(!confirm('Tem certeza? A foto será apagada permanentemente.')) return;

        const card = document.getElementById('photo-' + id);
        card.style.opacity = '0.5';
        card.style.pointerEvents = 'none';

        try {
            const res = await fetch(`${BASE_URL}/api/admin/photos/reject`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id})
            });
            const data = await res.json();

            if(data.success) {
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    updateCounter();
                }, 200);
            } else {
                alert('Erro ao rejeitar foto.');
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
        } catch(e) { console.error(e); }
    }

    // Atualiza o contador lá em cima sem precisar recarregar
    function updateCounter() {
        let title = document.querySelector('h2');
        let current = parseInt(title.innerText.match(/\d+/)[0]);
        if(current > 0) {
            title.innerText = `Fila de Aprovação (${current - 1})`;
        }
        if (current - 1 === 0) {
            location.reload(); // Recarrega para mostrar a tela de "Tudo Limpo"
        }
    }
</script>
</body>
</html>
