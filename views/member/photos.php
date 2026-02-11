<?php require __DIR__ . '/../partials/header.php'; ?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-4">

        <div class="flex flex-col md:flex-row justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Minha Galeria</h1>
                <p class="text-gray-500">Gerencie as fotos que aparecem no seu perfil público.</p>
            </div>

            <label class="mt-4 md:mt-0 cursor-pointer bg-pink-600 hover:bg-pink-700 text-white font-bold py-3 px-6 rounded-full shadow-lg transition transform hover:scale-105 flex items-center">
                <i class="fas fa-cloud-upload-alt mr-2"></i> Adicionar Fotos
                <input type="file" id="file-upload" multiple accept="image/*" class="hidden" onchange="uploadPhotos(this)">
            </label>
        </div>

        <div id="upload-status" class="hidden mb-6 bg-white p-4 rounded-lg shadow border border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-bold text-gray-700">Enviando fotos...</span>
                <span id="percent" class="text-sm text-pink-600 font-bold">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div id="progress-bar" class="bg-pink-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
        </div>

        <?php if (empty($photos)): ?>
            <div class="text-center py-20 bg-white rounded-xl border-2 border-dashed border-gray-300">
                <i class="far fa-images text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-400">Nenhuma foto ainda</h3>
                <p class="text-gray-400">Clique no botão acima para começar sua galeria.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach($photos as $photo): ?>
                <div class="relative group bg-white rounded-lg shadow-md overflow-hidden" id="photo-<?= $photo['id'] ?>">

                    <div class="aspect-[3/4] overflow-hidden bg-gray-100">
                        <img src="<?= url('/' . $photo['file_path']) ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                    </div>

                    <div class="absolute top-2 left-2">
                        <?php if($photo['is_approved']): ?>
                            <span class="bg-green-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                                <i class="fas fa-check-circle"></i> APROVADA
                            </span>
                        <?php else: ?>
                            <span class="bg-yellow-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                                <i class="fas fa-clock"></i> EM ANÁLISE
                            </span>
                        <?php endif; ?>
                    </div>

                    <button onclick="deletePhoto(<?= $photo['id'] ?>)"
                        class="absolute top-2 right-2 bg-red-500 text-white w-8 h-8 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center hover:bg-red-600">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    const API_UPLOAD = '<?= url('/api/photos/upload') ?>';
    const API_DELETE = '<?= url('/api/photos/delete') ?>';

    // Função de Upload
    async function uploadPhotos(input) {
        if (!input.files || input.files.length === 0) return;

        const formData = new FormData();
        for (let i = 0; i < input.files.length; i++) {
            formData.append('photos[]', input.files[i]);
        }

        // UI Feedback
        const statusBox = document.getElementById('upload-status');
        const progressBar = document.getElementById('progress-bar');
        const percent = document.getElementById('percent');

        statusBox.classList.remove('hidden');
        progressBar.style.width = '30%';
        percent.innerText = '30%';

        try {
            const response = await fetch(API_UPLOAD, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            progressBar.style.width = '100%';
            percent.innerText = '100%';

            if (result.success) {
                setTimeout(() => location.reload(), 500); // Recarrega para mostrar as novas
            } else {
                alert('Erro: ' + (result.message || 'Falha no envio'));
                statusBox.classList.add('hidden');
            }
        } catch (e) {
            console.error(e);
            alert('Erro de conexão.');
            statusBox.classList.add('hidden');
        }
    }

    // Função de Delete
    async function deletePhoto(id) {
        if(!confirm('Tem certeza que deseja apagar esta foto?')) return;

        try {
            const card = document.getElementById('photo-' + id);
            card.style.opacity = '0.5'; // Feedback imediato

            const response = await fetch(API_DELETE, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            });
            const result = await response.json();

            if (result.success) {
                card.remove();
            } else {
                alert('Erro ao apagar foto.');
                card.style.opacity = '1';
            }
        } catch(e) { console.error(e); }
    }
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>