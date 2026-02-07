<?php require __DIR__ . '/../partials/header.php'; ?>

<div class="bg-gray-900 relative overflow-hidden lg:h-[400px] flex items-center justify-center py-12 lg:py-0">
    <div class="absolute inset-0 opacity-30 bg-[url('https://source.unsplash.com/random/1920x600/?fashion,model,dark')] bg-cover bg-center"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent"></div>

    <div class="relative z-10 max-w-4xl mx-auto text-center px-4">
        <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">
            Encontre sua <span class="text-gold">Musa</span>
        </h1>
        <p class="text-gray-300 text-sm md:text-lg mb-8 max-w-2xl mx-auto hidden md:block">
            A seleção mais exclusiva de acompanhantes de luxo. Discrição, elegância e experiências inesquecíveis.
        </p>
        
        <div class="flex flex-wrap justify-center gap-3">
            <a href="<?= url('/?genero=woman') ?>" class="px-6 py-2 rounded-full border border-gray-600 bg-gray-900/80 text-white hover:bg-gold hover:text-dark transition font-medium backdrop-blur-sm">Mulheres</a>
            <a href="<?= url('/?genero=man') ?>" class="px-6 py-2 rounded-full border border-gray-600 bg-gray-900/80 text-white hover:bg-gold hover:text-dark transition font-medium backdrop-blur-sm">Homens</a>
            <a href="<?= url('/?genero=trans') ?>" class="px-6 py-2 rounded-full border border-gray-600 bg-gray-900/80 text-white hover:bg-gold hover:text-dark transition font-medium backdrop-blur-sm">Trans</a>
        </div>
    </div>
</div>
<div class="bg-gray-800 border-b border-gray-700 py-8 mb-8 relative overflow-hidden shadow-md">
    <div class="max-w-6xl mx-auto px-4 relative z-10">
        <h2 class="text-2xl md:text-3xl font-bold text-white mb-6 text-center">
            Encontre sua <span class="text-pink-500">companhia ideal</span>
        </h2>

        <form action="<?= url('/') ?>" method="GET" class="bg-gray-900 p-4 rounded-xl shadow-2xl border border-gray-700 flex flex-col md:flex-row gap-4 items-center">
            
            <div class="w-full md:w-1/3 relative">
                <i class="fas fa-map-marker-alt absolute left-4 top-3.5 text-gray-500"></i>
                <select name="city" class="w-full pl-10 pr-4 py-3 bg-gray-800 text-white border border-gray-600 rounded-lg focus:border-pink-500 focus:outline-none appearance-none">
                    <option value="">Todas as Cidades</option>
                    <?php foreach($cities as $city): ?>
                        <option value="<?= $city['slug'] ?>" <?= ($filters['city'] == $city['slug']) ? 'selected' : '' ?>>
                            <?= $city['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                    <i class="fas fa-chevron-down text-xs"></i>
                </div>
            </div>

            <div class="w-full md:w-1/4 relative">
                <i class="fas fa-venus-mars absolute left-4 top-3.5 text-gray-500"></i>
                <select name="gender" class="w-full pl-10 pr-4 py-3 bg-gray-800 text-white border border-gray-600 rounded-lg focus:border-pink-500 focus:outline-none appearance-none">
                    <option value="">Qualquer Gênero</option>
                    <option value="F" <?= ($filters['gender'] == 'F') ? 'selected' : '' ?>>Mulheres</option>
                    <option value="M" <?= ($filters['gender'] == 'M') ? 'selected' : '' ?>>Homens</option>
                    <option value="T" <?= ($filters['gender'] == 'T') ? 'selected' : '' ?>>Trans</option>
                </select>
            </div>

            <div class="w-full md:w-1/3 relative">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-500"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                       placeholder="Buscar por nome..." 
                       class="w-full pl-10 pr-4 py-3 bg-gray-800 text-white border border-gray-600 rounded-lg focus:border-pink-500 focus:outline-none placeholder-gray-500">
            </div>

            <div class="w-full md:w-auto">
                <button type="submit" class="w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-lg flex items-center justify-center">
                    Buscar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-8 md:py-12">
    
    <div class="flex flex-col md:flex-row justify-between items-end mb-8 border-b border-gray-200 pb-4 gap-4">
        <div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">
                <?php if($filters['gender']): ?>
                    Exibindo: <?= ucfirst($filters['gender']) ?>
                <?php else: ?>
                    Destaques <span class="text-gold">VIP</span>
                <?php endif; ?>
            </h2>
            <p class="text-gray-500 text-sm mt-1">Os perfis mais visualizados da semana.</p>
        </div>
        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded text-xs font-bold uppercase tracking-wide border">
            <?= count($profiles) ?> perfis encontrados
        </span>
    </div>

    <?php if (empty($profiles)): ?>
        <div class="text-center py-32 bg-gray-50 rounded-lg border border-dashed border-gray-300">
            <p class="text-gray-500 text-lg">Nenhum perfil encontrado.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach($profiles as $profile): 
                $photoUrl = $profile['cover_photo'] ? url('/' . $profile['cover_photo']) : 'https://via.placeholder.com/400x550?text=Sem+Foto';
                
                // Verifica se é VIP (nível > 1) para borda dourada
                $isVip = ($profile['ranking_score'] ?? 0) >= 2000; // Ajuste conforme sua lógica de score
            ?>
            
            <div class="relative group rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300 bg-gray-900 border <?= $isVip ? 'border-yellow-500' : 'border-gray-800' ?>">
                
                <button onclick="toggleFavorite(<?= $profile['id'] ?>, this)" 
                    class="absolute top-3 right-3 z-20 w-10 h-10 rounded-full bg-black/40 hover:bg-pink-600 text-white backdrop-blur-sm border border-white/10 flex items-center justify-center transition-all duration-200 transform hover:scale-110 shadow-lg">
                    <i class="far fa-heart text-lg"></i>
                </button>

                <a href="<?= url('/perfil/' . $profile['slug']) ?>" class="block w-full h-full">
                    
                    <div class="aspect-[3/4] w-full relative overflow-hidden">
                        <img src="<?= $photoUrl ?>" alt="<?= $profile['display_name'] ?>" 
                             class="w-full h-full object-cover transition duration-700 group-hover:scale-110 group-hover:rotate-1">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-80 group-hover:opacity-90 transition-opacity"></div>
                        
                        <?php if($isVip): ?>
                        <div class="absolute top-3 left-3 bg-yellow-500 text-black text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider shadow-sm z-10">
                            <i class="fas fa-crown mr-1"></i> VIP
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="absolute bottom-0 left-0 w-full p-4 z-10">
                        <div class="flex justify-between items-end">
                            <div>
                                <h3 class="text-white font-bold text-lg leading-tight truncate w-32 md:w-40 drop-shadow-md">
                                    <?= $profile['display_name'] ?>
                                </h3>
                                <p class="text-gray-300 text-xs flex items-center mt-1">
                                    <i class="fas fa-map-marker-alt mr-1 text-pink-500"></i>
                                    <?= $profile['city_name'] ?? 'Localização não def.' ?>
                                </p>
                            </div>
                            
                            <div class="text-pink-500 opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0 duration-300">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </a>

            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
async function toggleFavorite(profileId, btn) {
    try {
        const response = await fetch('<?= url('/api/favorites/toggle') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ profile_id: profileId })
        });

        if (response.status === 401) {
            alert('Você precisa entrar para favoritar!');
            window.location.href = '<?= url('/login') ?>';
            return;
        }

        const result = await response.json();
        if (result.success) {
            // Muda a cor do coração visualmente
            if (result.is_favorited) {
                btn.classList.remove('text-gray-400');
                btn.classList.add('text-red-500');
            } else {
                btn.classList.remove('text-red-500');
                btn.classList.add('text-gray-400');
            }
        }
    } catch (e) {
        console.error(e);
    }
}
</script>


<?php require __DIR__ . '/../partials/footer.php'; ?>