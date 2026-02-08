<?php
// views/model/create_profile.php
use App\Core\Database;

// 1. SEGURANÇA
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { 
    header('Location: ' . url('/login')); 
    exit; 
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance();

// 2. VERIFICAR SLOTS DISPONÍVEIS
$stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$currentProfiles = $stmt->fetchColumn();

$stmt = $db->getConnection()->prepare("SELECT max_profile_slots FROM users WHERE id = ?");
$stmt->execute([$userId]);
$maxSlots = $stmt->fetchColumn() ?: 1;

if ($currentProfiles >= $maxSlots) {
    header('Location: ' . url('/planos?msg=slots_full')); 
    exit;
}

// 3. BUSCAR PAÍSES (Da tabela countries existente)
try {
    $stmt = $db->getConnection()->query("SELECT id, name FROM countries ORDER BY name ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $countries = []; // Fallback seguro
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Perfil - TOP Model</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'], display: ['Outfit', 'sans-serif'] },
                    colors: { primary: '#db2777', secondary: '#4f46e5' }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 24px 24px; }
        .input-std { width: 100%; padding: 0.8rem 1rem; background-color: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-weight: 500; transition: 0.2s; color: #334155; }
        .input-std:focus { outline: none; border-color: #db2777; box-shadow: 0 0 0 4px rgba(219, 39, 119, 0.1); }
        .input-std:disabled { background-color: #f1f5f9; cursor: not-allowed; color: #94a3b8; }
    </style>
</head>
<body class="text-slate-600 antialiased min-h-screen flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center mb-8">
        <span class="font-display font-black text-3xl tracking-tight text-slate-900">TOP<span class="text-pink-600">Model</span></span>
        <h2 class="mt-6 text-2xl font-bold text-slate-900">Criar Novo Perfil</h2>
        <p class="mt-2 text-sm text-slate-500">
            Você tem <span class="font-bold text-green-600"><?= $maxSlots - $currentProfiles ?> vaga(s)</span> disponível(is).
        </p>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-slate-100">
            
            <form id="createProfileForm" onsubmit="handleCreate(event)" class="space-y-6">
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nome Artístico</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-400"></i>
                        </div>
                        <input type="text" id="display_name" name="display_name" required 
                               class="input-std pl-10" 
                               placeholder="Ex: Ana Clara"
                               onkeyup="generateSlug(this.value)">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Link do Perfil</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-slate-400 text-xs">topmodel.com/</span>
                        </div>
                        <input type="text" id="slug" name="slug" required 
                               class="input-std pl-28 text-sm font-mono text-pink-600" 
                               placeholder="ana-clara">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">País</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-globe text-slate-400"></i>
                        </div>
                        <select id="country_id" name="country_id" class="input-std pl-10 appearance-none bg-white" onchange="loadCities(this.value)">
                            <option value="">Selecione um país...</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Cidade Principal</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-map-marker-alt text-slate-400"></i>
                        </div>
                        <select id="city_id" name="city_id" class="input-std pl-10 appearance-none bg-white" disabled>
                            <option value="">Selecione o país primeiro...</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                        </div>
                    </div>
                    <p id="loading-cities" class="hidden text-xs text-pink-500 font-bold mt-1 ml-1"><i class="fas fa-spinner fa-spin"></i> Carregando cidades...</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Eu sou</label>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="woman" class="peer sr-only" checked>
                            <div class="text-center py-2 border rounded-lg peer-checked:bg-pink-50 peer-checked:border-pink-500 peer-checked:text-pink-600 hover:bg-slate-50 transition h-full flex items-center justify-center">
                                <span class="text-sm font-bold">Mulher</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="man" class="peer sr-only">
                            <div class="text-center py-2 border rounded-lg peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-600 hover:bg-slate-50 transition h-full flex items-center justify-center">
                                <span class="text-sm font-bold">Homem</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="trans" class="peer sr-only">
                            <div class="text-center py-2 border rounded-lg peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-600 hover:bg-slate-50 transition h-full flex items-center justify-center">
                                <span class="text-sm font-bold">Trans</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="gender" value="couple" class="peer sr-only">
                            <div class="text-center py-2 border rounded-lg peer-checked:bg-orange-50 peer-checked:border-orange-500 peer-checked:text-orange-600 hover:bg-slate-50 transition h-full flex items-center justify-center">
                                <span class="text-sm font-bold">Casal</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnSubmit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                        Criar Perfil e Começar <i class="fas fa-arrow-right"></i>
                    </button>
                    <a href="<?= url('/painel/dashboard') ?>" class="block text-center text-sm font-bold text-slate-400 mt-4 hover:text-slate-600">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

    <script>
        const BASE_URL = "<?= url('/') ?>";

        function generateSlug(text) {
            const slug = text.toString().toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '') 
                .replace(/\s+/g, '-')           
                .replace(/[^\w\-]+/g, '')       
                .replace(/\-\-+/g, '-')         
                .replace(/^-+/, '')             
                .replace(/-+$/, '');            
            document.getElementById('slug').value = slug;
        }

        async function loadCities(countryId) {
            const citySelect = document.getElementById('city_id');
            const loader = document.getElementById('loading-cities');
            
            citySelect.innerHTML = '<option value="">Selecione uma cidade...</option>';
            citySelect.disabled = true;

            if (!countryId) return;

            loader.classList.remove('hidden');

            try {
                const res = await fetch(`${BASE_URL}/api/locations/cities?country_id=${countryId}`);
                const cities = await res.json();
                
                if (cities.length > 0) {
                    cities.forEach(city => {
                        const opt = document.createElement('option');
                        opt.value = city.id;
                        opt.textContent = city.name;
                        citySelect.appendChild(opt);
                    });
                    citySelect.disabled = false;
                } else {
                    citySelect.innerHTML = '<option value="">Nenhuma cidade encontrada.</option>';
                }

            } catch (error) {
                console.error(error);
                alert('Erro ao carregar cidades.');
            } finally {
                loader.classList.add('hidden');
            }
        }

        async function handleCreate(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const cityVal = document.getElementById('city_id').value;
            
            if(!cityVal) {
                alert('Por favor, selecione uma cidade.');
                return;
            }

            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
            btn.disabled = true;

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(`${BASE_URL}/api/perfil/create`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await res.json();
                
                if (result.success) {
                    window.location.href = `${BASE_URL}/painel/dashboard?profile_id=${result.profile_id}`;
                } else {
                    alert('Erro: ' + (result.message || 'Erro desconhecido'));
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                alert('Erro de conexão.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>

</body>
</html>