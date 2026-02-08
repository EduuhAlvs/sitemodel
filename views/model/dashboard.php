<?php
use App\Models\Subscription;
use App\Models\Profile; // Mantido para métodos estáticos se existirem
use App\Core\Database;

// 1. SEGURANÇA E SESSÃO
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { 
    if (!headers_sent()) { header('Location: /login'); exit; }
    echo '<script>window.location.href="/login";</script>'; exit;
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance();

// 2. BUSCAR TODOS OS PERFIS DA USUÁRIA (PARA A SIDEBAR)
// Busca apenas colunas essenciais para evitar erros
$stmt = $db->getConnection()->prepare("SELECT id, display_name, slug FROM profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$myProfilesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tratamento de dados faltantes (Fallback)
$myProfiles = [];
foreach($myProfilesRaw as $p) {
    $p['status'] = $p['status'] ?? 'active'; 
    $p['profile_image'] = $p['profile_image'] ?? null; 
    $myProfiles[] = $p;
}

// Se não tiver perfil, redireciona para criar
if (empty($myProfiles)) { header('Location: /perfil/criar'); exit; }

// 3. SELEÇÃO DE PERFIL ATUAL
$currentProfileId = $_GET['profile_id'] ?? $myProfiles[0]['id'];

// Segurança: Garante que o perfil pertence ao usuário logado
$isOwner = false;
foreach ($myProfiles as $p) {
    if ($p['id'] == $currentProfileId) {
        $isOwner = true;
        break;
    }
}
if (!$isOwner) { $currentProfileId = $myProfiles[0]['id']; }

// 4. CARREGA DADOS DO PERFIL SELECIONADO (Query Direta)
$stmt = $db->getConnection()->prepare("SELECT * FROM profiles WHERE id = ?");
$stmt->execute([$currentProfileId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Garante campos padrão para não quebrar o HTML
if(!isset($profile['status'])) $profile['status'] = 'active';
if(!isset($profile['profile_image'])) $profile['profile_image'] = null;
if(!isset($profile['current_plan_level'])) $profile['current_plan_level'] = 0; 

// 5. DADOS AUXILIARES (Locais e Idiomas)
if (!isset($locations)) {
    try {
        $locations = \App\Models\Location::getByProfileId($profile['id']);
    } catch (Error $e) { $locations = []; }
}
if (!isset($languages)) {
    try {
        $languages = \App\Models\Language::getByProfileId($profile['id']);
    } catch (Error $e) { $languages = []; }
}

// 6. PLANOS E SOMA DE DIAS
$activePlans = [];
$calculatedTotalDays = 0;
if (method_exists(Subscription::class, 'getConsolidatedList')) {
    $subsData = Subscription::getConsolidatedList($profile['id']);
    $activePlans = $subsData['plans'];
    foreach($activePlans as $plan) { $calculatedTotalDays += (int)$plan['days_left']; }
}

// 7. LIMITES DE CONTA (Slots)
// Busca limite real do banco
$stmt = $db->getConnection()->prepare("SELECT max_profile_slots FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userSlots = $stmt->fetchColumn() ?: 1;

$usedSlots = count($myProfiles);
$canCreateMore = $usedSlots < $userSlots;

// 8. PREPARAÇÃO VISUAL
$profileImg = !empty($profile['profile_image']) ? url('/' . $profile['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($profile['display_name']).'&background=db2777&color=fff&size=256';
$workingHours = json_decode($profile['working_hours'] ?? '[]', true);
$serviceDetails = $profile['service_details'] ?? []; 
if(is_string($serviceDetails)) $serviceDetails = json_decode($serviceDetails, true);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel VIP - TOP Model</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'], display: ['Outfit', 'sans-serif'] },
                    colors: { primary: '#db2777', secondary: '#4f46e5', surface: '#ffffff' }
                }
            }
        }
        const BASE_URL = "<?= url('/') ?>";
        const CURRENT_PROFILE_ID = "<?= $profile['id'] ?>"; 
    </script>

    <style>
        body { background-color: #f8fafc; background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 24px 24px; }
        .panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); transition: all 0.2s; }
        .input-std { width: 100%; padding: 0.6rem 0.8rem; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; transition: 0.2s; color: #334155; }
        .input-std:focus { background: #fff; border-color: #db2777; outline: none; box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.1); }
        .btn-primary { background: #0f172a; color: white; font-weight: 600; padding: 0.75rem; border-radius: 0.5rem; width: 100%; transition: 0.2s; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-primary:hover { background: #1e293b; transform: translateY(-1px); }
        .chk-custom { accent-color: #db2777; width: 1.1rem; height: 1.1rem; cursor: pointer; }
        @media (min-width: 1024px) { .sidebar-sticky { position: sticky; top: 6rem; } }
        
        /* Estilo do Seletor de Perfil */
        .profile-selector-item { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; cursor: pointer; transition: 0.2s; border: 1px solid transparent; }
        .profile-selector-item:hover { background: #f1f5f9; }
        .profile-selector-item.active { background: #fdf2f8; border-color: #fbcfe8; }
        .profile-selector-item.active .name { color: #db2777; font-weight: 700; }
    </style>
</head>
<body class="text-slate-600 antialiased">

    <nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 h-16">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="font-display font-bold text-xl tracking-tight text-slate-900">TOP<span class="text-pink-600">Model</span></span>
                <span class="bg-slate-100 text-slate-500 text-[10px] font-bold px-2 py-0.5 rounded border border-slate-200 uppercase tracking-wide">Painel</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="<?= url('/perfil/' . ($profile['slug'] ?? '')) ?>" target="_blank" class="hidden sm:flex text-xs font-semibold text-slate-500 hover:text-pink-600 transition items-center gap-1.5 uppercase tracking-wide">
                    Ver Perfil Público <i class="fas fa-external-link-alt"></i>
                </a>
                <a href="<?= url('/logout') ?>" class="text-xs font-bold text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg transition uppercase tracking-wide">Sair</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 pt-24 pb-12 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <aside class="lg:col-span-3 space-y-5 sidebar-sticky">
            
            <div class="panel p-4 bg-white">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Meus Perfis (<?= $usedSlots ?>/<?= $userSlots ?>)</span>
                    
                    <?php if($canCreateMore): ?>
                        <a href="<?= url('/perfil/criar') ?>" class="text-[10px] bg-green-50 text-green-600 px-2 py-0.5 rounded font-bold hover:bg-green-100">+ Novo</a>
                    <?php else: ?>
                        <a href="<?= url('/planos?msg=slots_full') ?>" class="text-[10px] bg-pink-50 text-pink-600 px-2 py-0.5 rounded font-bold hover:bg-pink-100" title="Limite atingido. Comprar vaga extra.">+ Comprar Vaga</a>
                    <?php endif; ?>
                    
                </div>

                <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                    <?php foreach($myProfiles as $p): 
                        $isActive = ($p['id'] == $currentProfileId);
                        $pImg = !empty($p['profile_image']) ? url('/' . $p['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($p['display_name']).'&background=cbd5e1&color=fff&size=64';
                    ?>
                    <a href="?profile_id=<?= $p['id'] ?>" class="profile-selector-item <?= $isActive ? 'active' : '' ?>">
                        <img src="<?= $pImg ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium truncate name"><?= htmlspecialchars($p['display_name']) ?></p>
                            <p class="text-[10px] text-slate-400 truncate"><?= $p['status'] == 'active' ? '● Online' : '○ Offline' ?></p>
                        </div>
                        <?php if($isActive): ?>
                            <i class="fas fa-check text-pink-500 text-xs"></i>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel p-6 text-center group bg-white border-t-4 border-pink-500">
                <div class="relative inline-block mb-3">
                    <img src="<?= $profileImg ?>" class="w-20 h-20 rounded-full object-cover ring-2 ring-offset-2 ring-pink-500 group-hover:scale-105 transition duration-300">
                    <div class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                </div>
                <h2 class="font-display font-bold text-lg text-slate-900 leading-tight"><?= htmlspecialchars($profile['display_name']) ?></h2>
                <p class="text-xs text-slate-400 font-medium uppercase mt-1 tracking-wide">Editando Agora</p>
                
                <a href="<?= url('/planos?profile_id='.$profile['id']) ?>" class="block w-full py-2 mt-4 rounded-lg bg-slate-900 text-white font-bold text-xs hover:bg-slate-700 transition">
                    Impulsionar Este Perfil
                </a>
            </div>

            <div class="panel p-5 bg-gradient-to-br from-indigo-600 to-purple-700 text-white border-0 shadow-lg relative overflow-hidden">
                <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-100 mb-1 opacity-80">Cobertura (Este Perfil)</p>
                <div class="flex items-baseline gap-1 relative z-10">
                    <span class="text-3xl font-display font-bold"><?= $calculatedTotalDays ?></span>
                    <span class="text-sm opacity-80 font-medium">dias</span>
                </div>
                <i class="fas fa-calendar-alt absolute -right-3 -bottom-3 text-7xl text-white opacity-10 rotate-12"></i>
            </div>

            <div class="panel hidden lg:block overflow-hidden py-1">
                <nav class="flex flex-col text-sm font-medium text-slate-600">
                    <a href="#plans" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-layer-group w-4 text-center"></i> Planos</a>
                    <a href="#photos" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-camera w-4 text-center"></i> Fotos</a>
                    <a href="#personal" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-user w-4 text-center"></i> Dados</a>
                    <a href="#appearance" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-magic w-4 text-center"></i> Aparência</a>
                    <a href="#service" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-map-marker-alt w-4 text-center"></i> Serviços</a>
                    <a href="#schedule" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-clock w-4 text-center"></i> Horários</a>
                </nav>
            </div>
        </aside>

        <main class="lg:col-span-9 space-y-6">

            <section id="plans" class="scroll-mt-24">
                <div class="flex justify-between items-center mb-3 px-1">
                    <h3 class="font-display font-bold text-lg text-slate-800">Assinaturas deste Perfil</h3>
                    <a href="<?= url('/planos?profile_id='.$profile['id']) ?>" class="text-[10px] font-bold bg-pink-50 text-pink-600 px-3 py-1 rounded hover:bg-pink-100 transition uppercase tracking-wide">Comprar +</a>
                </div>
                
                <?php if (empty($activePlans)): ?>
                    <div class="panel p-6 text-center border-dashed border-slate-300">
                        <p class="text-slate-500 text-sm font-medium">Este perfil está no modo Gratuito.</p>
                        <a href="<?= url('/planos?profile_id='.$profile['id']) ?>" class="text-pink-600 text-xs font-bold hover:underline mt-1 inline-block">Ativar VIP</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach($activePlans as $index => $plan): $isMain = ($index === 0); ?>
                        <div class="panel p-4 flex flex-col justify-between h-full <?= $isMain ? 'ring-1 ring-pink-500 bg-pink-50/20' : 'bg-white' ?>">
                            <div>
                                <div class="flex justify-between items-start mb-2">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-600 font-bold text-xs">
                                        <?= substr($plan['plan_name'], 0, 1) ?>
                                    </span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-1 rounded <?= $isMain ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                                        <?= $isMain ? 'Vigente' : 'Reserva' ?>
                                    </span>
                                </div>
                                <h4 class="font-bold text-slate-800 text-sm"><?= $plan['plan_name'] ?></h4>
                                <p class="text-[10px] text-slate-400 mt-0.5">Expira: <?= date('d/m/y', strtotime($plan['expires_at'])) ?></p>
                            </div>
                            <div class="mt-3 pt-3 border-t border-slate-100/50">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xl font-bold text-slate-800"><?= $plan['days_left'] ?></span>
                                    <span class="text-[10px] text-slate-500 font-medium">dias restantes</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="photos" class="panel p-6 scroll-mt-24 relative bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-display font-bold text-lg text-slate-800">Galeria</h3>
                    <span id="upload-status-text" class="text-[10px] font-bold text-pink-600 opacity-0 transition uppercase">Enviando...</span>
                </div>

                <div id="drop-zone" class="border border-dashed border-slate-300 bg-slate-50 rounded-xl p-6 text-center cursor-pointer hover:bg-white hover:border-pink-400 hover:shadow-sm transition group" onclick="document.getElementById('photoInput').click()">
                    <input type="file" id="photoInput" multiple accept="image/*" class="hidden" onchange="handleFiles(this.files)">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-cloud-upload-alt text-2xl text-slate-300 group-hover:text-pink-500 transition"></i>
                        <div>
                            <p class="text-sm font-semibold text-slate-600">Clique ou arraste fotos</p>
                            <p class="text-[10px] text-slate-400">JPG, PNG, WebP (Max 5MB)</p>
                        </div>
                    </div>
                    <div id="loading-overlay" class="hidden mt-3">
                        <div class="h-1 w-full bg-slate-200 rounded-full overflow-hidden">
                            <div id="progress-bar" class="h-full bg-pink-500 w-0 transition-all duration-300"></div>
                        </div>
                    </div>
                </div>

                <div id="photoGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mt-5">
                    <?php if (!empty($photos)): foreach($photos as $photo): ?>
                        <div class="relative group aspect-[3/4] rounded-lg overflow-hidden bg-slate-100 shadow-sm border border-slate-200" id="photo-<?= $photo['id'] ?>">
                            <img src="<?= url('/' . $photo['file_path']) ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[8px] font-bold text-white rounded backdrop-blur-md <?= $photo['is_approved'] ? 'bg-green-500/80' : 'bg-yellow-500/80' ?>">
                                <?= $photo['is_approved'] ? 'OK' : 'ANÁLISE' ?>
                            </span>
                            <button onclick="deletePhoto(<?= $photo['id'] ?>)" class="absolute top-1.5 right-1.5 bg-white text-red-500 w-5 h-5 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow hover:scale-110">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <section id="personal" class="panel p-6 scroll-mt-24 relative bg-white">
                    <div class="flex justify-between items-center mb-5">
                        <h3 class="font-display font-bold text-lg text-slate-800">Dados Pessoais</h3>
                        <span id="msg-bio" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span>
                    </div>
                    <form onsubmit="saveCard(event, 'bio')" class="space-y-4">
                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Nome de Exibição</label>
                            <input type="text" name="display_name" value="<?= htmlspecialchars($profile['display_name']) ?>" class="input-std">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Gênero</label>
                                <select name="gender" class="input-std">
                                    <option value="woman" <?= $profile['gender']=='woman'?'selected':'' ?>>Mulher</option>
                                    <option value="man" <?= $profile['gender']=='man'?'selected':'' ?>>Homem</option>
                                    <option value="trans" <?= $profile['gender']=='trans'?'selected':'' ?>>Trans</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Orientação</label>
                                <select name="orientation" class="input-std">
                                    <option value="hetero" <?= $profile['orientation']=='hetero'?'selected':'' ?>>Hétero</option>
                                    <option value="bi" <?= $profile['orientation']=='bi'?'selected':'' ?>>Bi</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Nascimento</label>
                                <input type="date" name="birth_date" value="<?= $profile['birth_date'] ?>" class="input-std">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Nacionalidade</label>
                                <input type="text" name="nationality" value="<?= $profile['nationality'] ?>" class="input-std">
                            </div>
                        </div>
                        <button class="btn-primary mt-2">Salvar Dados</button>
                    </form>
                </section>

                <section id="appearance" class="panel p-6 scroll-mt-24 relative bg-white">
                     <div class="flex justify-between items-center mb-5">
                        <h3 class="font-display font-bold text-lg text-slate-800">Aparência</h3>
                        <span id="msg-appearance" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span>
                    </div>
                    <form onsubmit="saveCard(event, 'appearance')" class="grid grid-cols-2 gap-3">
                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Cabelos</label>
                            <select name="hair_color" class="input-std">
                                <option value="loira" <?= $profile['hair_color']=='loira'?'selected':'' ?>>Loira</option>
                                <option value="morena" <?= $profile['hair_color']=='morena'?'selected':'' ?>>Morena</option>
                                <option value="ruiva" <?= $profile['hair_color']=='ruiva'?'selected':'' ?>>Ruiva</option>
                                <option value="preto" <?= $profile['hair_color']=='preto'?'selected':'' ?>>Preto</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Olhos</label>
                            <select name="eye_color" class="input-std">
                                <option value="castanhos" <?= $profile['eye_color']=='castanhos'?'selected':'' ?>>Castanhos</option>
                                <option value="verdes" <?= $profile['eye_color']=='verdes'?'selected':'' ?>>Verdes</option>
                                <option value="azuis" <?= $profile['eye_color']=='azuis'?'selected':'' ?>>Azuis</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Altura (cm)</label>
                            <input type="number" name="height_cm" value="<?= $profile['height_cm'] ?>" class="input-std" placeholder="170">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Peso (kg)</label>
                            <input type="number" name="weight_kg" value="<?= $profile['weight_kg'] ?>" class="input-std" placeholder="60">
                        </div>
                        <div class="col-span-2 flex items-center justify-between border-t border-slate-100 pt-3 mt-1">
                            <span class="text-xs font-bold text-slate-500 uppercase">Tatuagens?</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="tattoos" value="1" <?= $profile['tattoos']?'checked':'' ?> class="chk-custom"> <span class="text-sm">Sim</span></label>
                                <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="tattoos" value="0" <?= !$profile['tattoos']?'checked':'' ?> class="chk-custom"> <span class="text-sm">Não</span></label>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <button class="btn-primary">Salvar Aparência</button>
                        </div>
                    </form>
                </section>
            </div>

            <section id="about" class="panel p-6 scroll-mt-24 relative bg-white">
                 <div class="flex justify-between items-center mb-5">
                    <h3 class="font-display font-bold text-lg text-slate-800">Sobre Mim</h3>
                    <span id="msg-about" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span>
                </div>
                <form onsubmit="saveCard(event, 'about')" class="space-y-5">
                    <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                    <div>
                        <textarea name="bio" rows="3" class="input-std" placeholder="Descreva você..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                    </div>
                    <div class="flex gap-8 border-t border-slate-100 pt-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Fuma?</label>
                            <div class="flex gap-3">
                                <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="smoker" value="no" <?= $profile['smoker']=='no'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Não</span></label>
                                <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="smoker" value="yes" <?= $profile['smoker']=='yes'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Sim</span></label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Bebe?</label>
                            <div class="flex gap-3">
                                <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="drinker" value="no" <?= $profile['drinker']=='no'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Não</span></label>
                                <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="drinker" value="yes" <?= $profile['drinker']=='yes'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Sim</span></label>
                                <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="drinker" value="occasionally" <?= $profile['drinker']=='occasionally'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Social</span></label>
                            </div>
                        </div>
                    </div>
                    <button class="btn-primary">Salvar Descrição</button>
                </form>
            </section>

            <section id="service" class="panel p-6 scroll-mt-24 relative bg-white">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <h3 class="font-display font-bold text-lg text-slate-800">Locais & Serviços</h3>
                    <span id="msg-service_details" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                         <div class="mb-4 relative">
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Adicionar Cidade</label>
                            <input type="text" placeholder="Buscar..." onkeyup="searchCity(this.value)" class="input-std">
                            <div id="cityResults" class="absolute z-20 w-full bg-white shadow-xl border rounded-lg mt-1 hidden max-h-40 overflow-y-auto"></div>
                        </div>
                        <div id="myCitiesList" class="space-y-2">
                            <?php if(!empty($locations)): foreach($locations as $loc): ?>
                                <div class="flex items-center justify-between bg-white p-2.5 rounded border border-slate-200" id="city-row-<?= $loc['id'] ?>">
                                    <span class="text-sm font-semibold text-slate-700"><?= $loc['name'] ?></span>
                                    <div class="flex items-center gap-2">
                                        <label class="flex items-center gap-1 text-[10px] uppercase font-bold cursor-pointer bg-slate-50 px-2 py-1 rounded hover:bg-slate-100">
                                            <input type="radio" name="base_city" onchange="setBaseCity(<?= $loc['id'] ?>)" <?= $loc['is_base_city']?'checked':'' ?> class="accent-pink-600"> Base
                                        </label>
                                        <button onclick="removeCity(<?= $loc['id'] ?>)" class="text-slate-400 hover:text-red-500 px-1"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <form onsubmit="saveCard(event, 'service_details')" class="space-y-5">
                        <input type="hidden" name="force_submit" value="1">
                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <label class="flex items-center gap-2 mb-2 font-bold text-slate-800 cursor-pointer">
                                <input type="checkbox" name="incall_available" value="1" <?= $profile['incall_available'] ? 'checked' : '' ?> class="chk-custom">
                                Incall (Local Próprio)
                            </label>
                            <div class="pl-6 space-y-1 text-sm text-slate-600">
                                <label class="block"><input type="checkbox" name="details[incall_private]" value="1" <?= ($serviceDetails['incall_private'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Apartamento</label>
                                <label class="block"><input type="checkbox" name="details[incall_hotel]" value="1" <?= ($serviceDetails['incall_hotel'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Hotel</label>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <label class="flex items-center gap-2 mb-2 font-bold text-slate-800 cursor-pointer">
                                <input type="checkbox" name="outcall_available" value="1" <?= $profile['outcall_available'] ? 'checked' : '' ?> class="chk-custom">
                                Outcall (Atende Fora)
                            </label>
                            <div class="pl-6 space-y-1 text-sm text-slate-600">
                                <label class="block"><input type="checkbox" name="details[outcall_hotel]" value="1" <?= ($serviceDetails['outcall_hotel'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Hotéis</label>
                                <label class="block"><input type="checkbox" name="details[outcall_home]" value="1" <?= ($serviceDetails['outcall_home'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Residência</label>
                                <label class="block"><input type="checkbox" name="details[outcall_events]" value="1" <?= ($serviceDetails['outcall_events'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Eventos</label>
                            </div>
                        </div>
                        <button class="btn-primary">Salvar Serviços</button>
                    </form>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <section id="schedule" class="panel p-6 relative bg-white">
                    <div class="flex justify-between items-center mb-5">
                        <h3 class="font-display font-bold text-lg text-slate-800">Horários</h3>
                        <span id="msg-schedule" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span>
                    </div>
                    <form onsubmit="saveSchedule(event)">
                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        <div class="space-y-2 mb-4">
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 <?= $profile['is_24_7']?'bg-pink-50 border-pink-200':'' ?>">
                                <input type="radio" name="mode" value="24_7" onchange="toggleSchedule(this.value)" <?= $profile['is_24_7']?'checked':'' ?> class="text-pink-600">
                                <span class="ml-2 text-sm font-bold">24 Horas / 7 Dias</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 <?= $profile['show_as_night']?'bg-pink-50 border-pink-200':'' ?>">
                                <input type="radio" name="mode" value="night" onchange="toggleSchedule(this.value)" <?= $profile['show_as_night']?'checked':'' ?> class="text-pink-600">
                                <span class="ml-2 text-sm font-bold">Plantão Noturno</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="mode" value="custom" onchange="toggleSchedule(this.value)" <?= (!$profile['is_24_7']&&!$profile['show_as_night'])?'checked':'' ?> class="text-pink-600">
                                <span class="ml-2 text-sm font-bold">Horário Personalizado</span>
                            </label>
                        </div>

                        <div id="schedule-table" class="<?= ($profile['is_24_7']||$profile['show_as_night'])?'hidden':'' ?> border-t pt-2 space-y-2">
                            <?php 
                            $days = ['seg'=>'Seg','ter'=>'Ter','qua'=>'Qua','qui'=>'Qui','sex'=>'Sex','sab'=>'Sáb','dom'=>'Dom'];
                            foreach($days as $key=>$label): 
                                $isActive = isset($workingHours[$key]['active']) && $workingHours[$key]['active']=='1';
                            ?>
                            <div class="flex items-center justify-between text-sm">
                                <label class="flex items-center w-20 cursor-pointer">
                                    <input type="checkbox" name="hours[<?= $key ?>][active]" value="1" <?= $isActive?'checked':'' ?> onchange="toggleDay('<?= $key ?>')" class="rounded chk-custom">
                                    <span class="ml-2 font-medium"><?= $label ?></span>
                                </label>
                                <div id="time-<?= $key ?>" class="flex gap-1 <?= $isActive?'':'opacity-30 pointer-events-none' ?>">
                                    <input type="time" name="hours[<?= $key ?>][start]" value="<?= $workingHours[$key]['start']??'09:00' ?>" class="border rounded px-1 w-20 text-xs">
                                    <input type="time" name="hours[<?= $key ?>][end]" value="<?= $workingHours[$key]['end']??'22:00' ?>" class="border rounded px-1 w-20 text-xs">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn-primary mt-4">Salvar Horários</button>
                    </form>
                </section>

                <div class="space-y-6">
                    <section class="panel p-6 relative bg-white">
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="font-display font-bold text-lg text-slate-800">Contato</h3>
                            <span id="msg-contact" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span>
                        </div>
                        <form onsubmit="saveCard(event, 'contact')" class="space-y-4">
                            <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1">WhatsApp</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone']) ?>" class="input-std">
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="whatsapp_enabled" value="1" <?= $profile['whatsapp_enabled']?'checked':'' ?> class="chk-custom">
                                    <span class="text-sm font-medium">Atender WhatsApp</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="viber_enabled" value="1" <?= $profile['viber_enabled']?'checked':'' ?> class="chk-custom">
                                    <span class="text-sm font-medium">Atender Viber</span>
                                </label>
                            </div>
                            <div class="pt-2 border-t border-slate-100">
                                <span class="block text-xs font-bold text-slate-500 uppercase mb-2">Preferência</span>
                                <div class="flex gap-2 text-sm">
                                    <label class="cursor-pointer bg-slate-50 px-3 py-1 rounded border hover:border-pink-300"><input type="radio" name="contact_preference" value="sms_only" <?= $profile['contact_preference']=='sms_only'?'checked':'' ?>> Chat</label>
                                    <label class="cursor-pointer bg-slate-50 px-3 py-1 rounded border hover:border-pink-300"><input type="radio" name="contact_preference" value="call_only" <?= $profile['contact_preference']=='call_only'?'checked':'' ?>> Voz</label>
                                    <label class="cursor-pointer bg-slate-50 px-3 py-1 rounded border hover:border-pink-300"><input type="radio" name="contact_preference" value="sms_call" <?= $profile['contact_preference']=='sms_call'?'checked':'' ?>> Ambos</label>
                                </div>
                            </div>
                            <button class="btn-primary">Salvar Contato</button>
                        </form>
                    </section>
                    
                    <section class="panel p-6 bg-white">
                        <h3 class="font-display font-bold text-lg text-slate-800 mb-4">Idiomas</h3>
                         <div class="flex gap-2 mb-3">
                             <select id="langSelect" class="flex-1 input-std p-1.5"><option>Inglês</option><option>Espanhol</option><option>Francês</option><option>Italiano</option></select>
                             <select id="langLevel" class="w-24 input-std p-1.5"><option value="medium">Médio</option><option value="native">Fluente</option></select>
                             <button onclick="addLanguage()" class="bg-pink-600 text-white px-3 rounded-lg hover:bg-pink-700"><i class="fas fa-plus"></i></button>
                         </div>
                         <div id="langList" class="space-y-2">
                             <?php if(!empty($languages)): foreach($languages as $lang): ?>
                                <div class="flex justify-between items-center bg-slate-50 p-2 rounded-lg text-sm border border-slate-100" id="lang-row-<?= $lang['id'] ?>">
                                    <span><?= $lang['language'] ?> <small class="text-slate-400 uppercase"><?= $lang['level'] ?></small></span>
                                    <button onclick="removeLanguage(<?= $lang['id'] ?>)" class="text-red-400 text-xs"><i class="fas fa-times"></i></button>
                                </div>
                             <?php endforeach; endif; ?>
                         </div>
                    </section>
                </div>
            </div>

        </main>
    </div>

    <script>
    async function saveCard(event, sectionName) {
        event.preventDefault();
        const form = event.target;
        const btn = form.querySelector('button');
        const oldText = btn.innerText; btn.innerText = "..."; btn.disabled = true;

        const formData = new FormData(form);
        const dataObj = {};
        formData.forEach((value, key) => dataObj[key] = value);

        if (!dataObj.profile_id) dataObj.profile_id = CURRENT_PROFILE_ID;

        if(sectionName === 'service_details') {
            const details = {};
            form.querySelectorAll('input[name^="details["]').forEach(cb => {
                const key = cb.name.match(/\[(.*?)\]/)[1]; 
                details[key] = cb.checked ? 1 : 0;
            });
            dataObj.details = details;
            dataObj.incall_available = form.querySelector('[name="incall_available"]').checked ? 1 : 0;
            dataObj.outcall_available = form.querySelector('[name="outcall_available"]').checked ? 1 : 0;
        }
        if(sectionName === 'contact') {
            dataObj.whatsapp_enabled = form.querySelector('[name="whatsapp_enabled"]')?.checked ? 1 : 0;
            dataObj.viber_enabled = form.querySelector('[name="viber_enabled"]')?.checked ? 1 : 0;
        }

        try {
            const res = await fetch(`${BASE_URL}/api/perfil/save`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ section: sectionName, data: dataObj })
            });
            const text = await res.text();
            try {
                const json = JSON.parse(text);
                if(json.success) {
                    const msg = document.getElementById('msg-' + sectionName);
                    if(msg) { msg.style.opacity = '1'; setTimeout(() => msg.style.opacity = '0', 2000); }
                } else alert('Erro: ' + json.message);
            } catch(e) { console.error("PHP Error:", text); }
        } catch(e) { alert('Erro conexão'); }
        finally { btn.innerText = oldText; btn.disabled = false; }
    }

    async function saveSchedule(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button');
        const oldText = btn.innerText; btn.innerText = "..."; btn.disabled = true;
        
        const fd = new FormData(form);
        const data = { 
            is_24_7: 0, 
            show_as_night: 0, 
            working_hours: '',
            profile_id: CURRENT_PROFILE_ID 
        };
        
        if(fd.get('mode') === '24_7') data.is_24_7 = 1;
        if(fd.get('mode') === 'night') data.show_as_night = 1;

        const workingHoursObj = {};
        ['seg','ter','qua','qui','sex','sab','dom'].forEach(d => {
            const chk = form.querySelector(`input[name="hours[${d}][active]"]`);
            if(chk && chk.checked) {
                workingHoursObj[d] = { 
                    active: 1, 
                    start: fd.get(`hours[${d}][start]`), 
                    end: fd.get(`hours[${d}][end]`) 
                };
            }
        });
        data.working_hours = JSON.stringify(workingHoursObj);
        
        try {
            await fetch(`${BASE_URL}/api/perfil/save`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ section: 'schedule', data: data }) });
            const msg = document.getElementById('msg-schedule');
            msg.style.opacity='1'; setTimeout(()=>msg.style.opacity='0', 2000);
        } catch(err) { alert('Erro ao salvar horários'); }
        finally { btn.innerText = oldText; btn.disabled = false; }
    }
    function toggleSchedule(mode){ document.getElementById('schedule-table').classList.toggle('hidden', mode!=='custom'); }
    function toggleDay(k){ 
        const d=document.getElementById('time-'+k); 
        const chk=document.querySelector(`input[name="hours[${k}][active]"]`);
        d.classList.toggle('opacity-30', !chk.checked); d.classList.toggle('pointer-events-none', !chk.checked);
    }

    const dropZone = document.getElementById('drop-zone');
    ['dragenter','dragover'].forEach(e=>dropZone.addEventListener(e,ev=>{ev.preventDefault();dropZone.classList.add('bg-pink-50','border-pink-400')}));
    ['dragleave','drop'].forEach(e=>dropZone.addEventListener(e,ev=>{ev.preventDefault();dropZone.classList.remove('bg-pink-50','border-pink-400')}));
    dropZone.addEventListener('drop',e=>handleFiles(e.dataTransfer.files));

    async function handleFiles(files) {
        if(!files.length) return;
        document.getElementById('loading-overlay').classList.remove('hidden');
        document.getElementById('upload-status-text').style.opacity = '1';
        const fd = new FormData();
        fd.append('profile_id', CURRENT_PROFILE_ID); 
        for(let i=0; i<files.length; i++) fd.append('photos[]', files[i]);
        try {
            const res = await fetch('<?= url('/api/photos/upload') ?>', {method:'POST', body:fd});
            const data = await res.json();
            if(data.success) setTimeout(()=>location.reload(),500);
            else alert(data.message);
        } catch(e) { alert('Erro upload'); }
        finally { document.getElementById('loading-overlay').classList.add('hidden'); }
    }
    
    async function deletePhoto(id) {
        if(!confirm('Excluir?')) return;
        document.getElementById('photo-'+id).style.opacity='0.5';
        try { await fetch('<?= url('/api/photos/delete') ?>', {method:'POST', body:JSON.stringify({id:id, profile_id: CURRENT_PROFILE_ID})}); document.getElementById('photo-'+id).remove(); }
        catch(e){alert('Erro ao excluir');}
    }

    async function searchCity(q) {
        const div = document.getElementById('cityResults');
        if(q.length<3) { div.classList.add('hidden'); return; }
        const res = await fetch(`${BASE_URL}/api/locations/search?q=${q}`);
        const list = await res.json();
        div.innerHTML='';
        if(list.length) {
            div.classList.remove('hidden');
            list.forEach(c => {
                const el = document.createElement('div');
                el.className='p-2 hover:bg-pink-50 cursor-pointer text-sm border-b text-slate-700';
                el.innerText = `${c.name} - ${c.country}`;
                el.onclick=()=>{addCity(c.id); div.classList.add('hidden');};
                div.appendChild(el);
            });
        } else div.classList.add('hidden');
    }
    async function addCity(id){ await manageLoc('add', id); location.reload(); }
    async function removeCity(id){ if(confirm('Remover?')){ await manageLoc('remove', id); location.reload(); } }
    async function setBaseCity(id){ await manageLoc('set_base', id); alert('Atualizada!'); }
    async function manageLoc(act, cid){ 
        await fetch(`${BASE_URL}/api/locations/manage`, {
            method:'POST', 
            body:JSON.stringify({action:act, city_id:cid, profile_id: CURRENT_PROFILE_ID})
        }); 
    }

    async function addLanguage(){
        const l=document.getElementById('langSelect').value; const v=document.getElementById('langLevel').value;
        await fetch(`${BASE_URL}/api/languages/manage`, {
            method:'POST', 
            body:JSON.stringify({action:'add', language:l, level:v, profile_id: CURRENT_PROFILE_ID})
        });
        location.reload();
    }
    async function removeLanguage(id){
        await fetch(`${BASE_URL}/api/languages/manage`, {
            method:'POST', 
            body:JSON.stringify({action:'remove', id:id, profile_id: CURRENT_PROFILE_ID})
        });
        location.reload();
    }
    </script>
</body>
</html>