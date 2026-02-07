<?php require __DIR__ . '/../partials/header.php'; ?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-6xl mx-auto px-4">
        
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-heart text-red-500 mr-2"></i>Meus Favoritos</h1>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                 <a href="<?= url('/logout') ?>" class="text-red-500 hover:underline">Sair</a>
            <?php endif; ?>
        </div>

        <?php if (empty($favorites)): ?>
            <div class="text-center py-20 bg-white rounded-lg shadow-sm">
                <i class="far fa-heart text-6xl text-gray-200 mb-4"></i>
                <h2 class="text-xl font-bold text-gray-600">Sua lista está vazia</h2>
                <p class="text-gray-500 mb-6">Explore nossas modelos e salve as que você mais gostar.</p>
                <a href="<?= url('/') ?>" class="bg-pink-600 text-white px-6 py-2 rounded-full hover:bg-pink-700 transition">
                    Ver Modelos
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach($favorites as $profile): 
                    $photoUrl = $profile['cover_photo'] ? url('/' . $profile['cover_photo']) : 'https://via.placeholder.com/400x550';
                ?>
                <div class="relative group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition">
                    <a href="<?= url('/perfil/' . $profile['slug']) ?>">
                        <div class="aspect-[3/4] overflow-hidden bg-gray-200">
                            <img src="<?= $photoUrl ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        </div>
                        <div class="p-3">
                            <h3 class="font-bold text-gray-800 truncate"><?= $profile['display_name'] ?></h3>
                            <p class="text-xs text-gray-500"><?= $profile['city_name'] ?? 'Brasil' ?></p>
                        </div>
                    </a>
                    
                    <button onclick="toggleFavorite(<?= $profile['id'] ?>, this.parentElement)" class="absolute top-2 right-2 bg-white/90 text-red-500 p-2 rounded-full shadow hover:bg-white transition z-10">
                        <i class="fas fa-trash-alt text-sm"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function toggleFavorite(profileId, cardElement) {
    if(!confirm('Remover dos favoritos?')) return;
    
    // Mesma lógica do controller toggle
    const response = await fetch('<?= url('/api/favorites/toggle') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ profile_id: profileId })
    });
    
    const result = await response.json();
    if(result.success) {
        // Remove o card da tela
        cardElement.remove();
        // Se ficou vazio, recarrega para mostrar a msg de "vazio"
        if(document.querySelectorAll('.grid > div').length === 0) location.reload();
    }
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>