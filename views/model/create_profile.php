<?php
use App\Core\Database;

// 1. SEGURANÇA
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ' . url('/login')); exit; }

$userId = $_SESSION['user_id'];
$db = Database::getInstance();

// 2. VERIFICA SLOTS
$stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$currentProfiles = $stmt->fetchColumn();

$stmt = $db->getConnection()->prepare("SELECT max_profile_slots FROM users WHERE id = ?");
$stmt->execute([$userId]);
$maxSlots = $stmt->fetchColumn() ?: 1;

if ($currentProfiles >= $maxSlots) { header('Location: ' . url('/planos?msg=slots_full')); exit; }

// 3. BUSCA PAÍSES
try {
    $stmt = $db->getConnection()->query("SELECT id, name FROM countries ORDER BY name ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $countries = []; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .input-std { width: 100%; padding: 0.8rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; }
        .input-std:focus { outline: none; border-color: #db2777; ring: 2px solid #fce7f3; }
        /* Classes para feedback visual */
        .border-green-500 { border-color: #10b981 !important; }
        .border-red-500 { border-color: #ef4444 !important; }
        .text-green-600 { color: #059669 !important; }
        .text-red-600 { color: #dc2626 !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 border border-slate-100">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Novo Perfil</h1>
            <p class="text-sm text-slate-500">Crie seu anúncio e comece a faturar.</p>
        </div>

        <form id="createProfileForm" onsubmit="handleCreate(event)" class="space-y-5">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome Artístico</label>
                <input type="text" name="display_name" required class="input-std" placeholder="Ex: Ana Clara" onkeyup="generateSlug(this.value)">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Link Personalizado</label>
                <div class="relative">
                    <div class="flex items-center bg-slate-50 border border-slate-200 rounded-lg px-3" id="slug-container">
                        <span class="text-xs text-slate-400">topmodel.com/</span>
                        <input type="text" id="slug" name="slug" required class="w-full bg-transparent py-3 pl-1 text-sm font-bold text-pink-600 focus:outline-none" placeholder="ana-clara" onkeyup="manualSlugCheck(this.value)">
                        
                        <div id="slug-icon" class="hidden ml-2"></div>
                    </div>
                </div>
                <p id="slug-feedback" class="text-[10px] font-bold mt-1 min-h-[15px]"></p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">País</label>
                    <select id="country_id" class="input-std bg-white" onchange="loadCities(this.value)">
                        <option value="">Selecione...</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cidade</label>
                    <select name="city_id" id="city_id" class="input-std bg-white" disabled>
                        <option value="">...</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Eu sou</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="gender" value="woman" class="peer sr-only" checked>
                        <div class="h-10 flex items-center justify-center border rounded-lg peer-checked:bg-pink-50 peer-checked:border-pink-500 peer-checked:text-pink-600 hover:bg-slate-50 text-xs font-bold transition">Mulher</div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="gender" value="man" class="peer sr-only">
                        <div class="h-10 flex items-center justify-center border rounded-lg peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-600 hover:bg-slate-50 text-xs font-bold transition">Homem</div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="gender" value="trans" class="peer sr-only">
                        <div class="h-10 flex items-center justify-center border rounded-lg peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-600 hover:bg-slate-50 text-xs font-bold transition">Trans</div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="gender" value="couple" class="peer sr-only">
                        <div class="h-10 flex items-center justify-center border rounded-lg peer-checked:bg-orange-50 peer-checked:border-orange-500 peer-checked:text-orange-600 hover:bg-slate-50 text-xs font-bold transition">Casal</div>
                    </label>
                </div>
            </div>

            <button type="submit" id="btnSubmit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                Criar Perfil
            </button>
            <a href="<?= url('/perfil/editar') ?>" class="block text-center text-xs font-bold text-slate-400 hover:text-slate-600 mt-2">Cancelar</a>
        </form>
    </div>

    <script>
        const BASE_URL = "<?= rtrim(url('/'), '/') ?>";
        let checkTimeout = null;
        let isSlugValid = false;

        // Limpa e formata o slug
        function sanitizeSlug(text) {
            return text.toString().toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        }

        // Gera automaticamente ao digitar o nome
        function generateSlug(text) {
            const slug = sanitizeSlug(text);
            document.getElementById('slug').value = slug;
            debouncedCheck(slug);
        }

        // Verifica ao digitar manualmente no campo de link
        function manualSlugCheck(text) {
            const slug = sanitizeSlug(text);
            // Atualiza o valor sanitizado no campo (impede caracteres inválidos)
            // document.getElementById('slug').value = slug; // Opcional: força a limpeza enquanto digita
            debouncedCheck(slug);
        }

        // Delay para não chamar a API a cada letra (500ms)
        function debouncedCheck(slug) {
            const feedback = document.getElementById('slug-feedback');
            const container = document.getElementById('slug-container');
            const icon = document.getElementById('slug-icon');
            const btn = document.getElementById('btnSubmit');

            // Reset visual
            feedback.innerText = "Verificando...";
            feedback.className = "text-[10px] font-bold mt-1 text-slate-400";
            container.classList.remove('border-green-500', 'border-red-500');
            icon.classList.add('hidden');
            
            clearTimeout(checkTimeout);

            if (slug.length < 3) {
                feedback.innerText = "Mínimo 3 caracteres";
                isSlugValid = false;
                return;
            }

            checkTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(`${BASE_URL}/api/perfil/check-slug?slug=${slug}`);
                    const data = await res.json();
                    
                    icon.classList.remove('hidden');

                    if (data.available) {
                        // DISPONÍVEL
                        feedback.innerText = "✓ Link disponível!";
                        feedback.className = "text-[10px] font-bold mt-1 text-green-600";
                        container.classList.add('border-green-500');
                        icon.innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
                        isSlugValid = true;
                        btn.disabled = false;
                    } else {
                        // INDISPONÍVEL
                        feedback.innerText = "✕ Este link já está em uso. Tente outro.";
                        feedback.className = "text-[10px] font-bold mt-1 text-red-600";
                        container.classList.add('border-red-500');
                        icon.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
                        isSlugValid = false;
                        btn.disabled = true; // Bloqueia o botão
                    }
                } catch (e) {
                    console.error("Erro ao verificar slug");
                }
            }, 500);
        }

        async function loadCities(countryId) {
            const select = document.getElementById('city_id');
            select.innerHTML = '<option value="">Carregando...</option>';
            select.disabled = true;
            
            if(!countryId) { select.innerHTML = '<option value="">...</option>'; return; }

            try {
                const res = await fetch(`${BASE_URL}/api/locations/cities?country_id=${countryId}`);
                const data = await res.json();
                
                select.innerHTML = '<option value="">Selecione a cidade</option>';
                if(data.length) {
                    data.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id; opt.innerText = c.name;
                        select.appendChild(opt);
                    });
                    select.disabled = false;
                } else {
                    select.innerHTML = '<option value="">Nenhuma cidade</option>';
                }
            } catch(e) { console.error(e); select.innerHTML = '<option value="">Erro</option>'; }
        }

        async function handleCreate(e) {
            e.preventDefault();
            
            // Validação extra antes de enviar
            if (!isSlugValid) {
                alert('O link escolhido não está disponível. Por favor, escolha outro.');
                return;
            }

            const btn = document.getElementById('btnSubmit');
            const originalText = btn.innerText;
            
            if(!document.getElementById('city_id').value) { alert('Selecione uma cidade'); return; }

            btn.disabled = true; btn.innerText = "Criando...";

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(`${BASE_URL}/api/perfil/create`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch(err) { throw new Error(text); }

                if (json.success) {
                    window.location.href = `${BASE_URL}/perfil/editar?profile_id=${json.profile_id}`;
                } else {
                    alert('Atenção: ' + json.message);
                    btn.disabled = false; btn.innerText = originalText;
                }
            } catch (error) {
                console.error(error);
                alert('Erro no Servidor: ' + error.message.substring(0, 100) + '...');
                btn.disabled = false; btn.innerText = originalText;
            }
        }
    </script>
</body>
</html>