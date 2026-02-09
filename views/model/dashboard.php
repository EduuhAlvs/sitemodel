<?php
// views/model/dashboard.php

// --- 0. HELPER FUNCTIONS (API) ---
function getPlanName($price, $id = null) {
    if ($price >= 100) return 'Plano VIP'; 
    if ($price >= 50) return 'Plano Plus';
    if ($price > 0) return 'Plano Premium';
    return 'Plano #' . ($id ?? '?');
}

// --- 1. HANDLER AJAX PARA HISTÓRICO ---
if (isset($_GET['action']) && $_GET['action'] === 'load_history') {
    if (!isset($_SESSION['user_id'])) { http_response_code(403); exit; }
    
    header('Content-Type: application/json');
    $db = \App\Core\Database::getInstance();
    
    $profileId = $_GET['profile_id'] ?? 0;
    $page = (int)($_GET['page'] ?? 1);
    $limit = 5;
    $offset = ($page - 1) * $limit;
    
    try {
        $stmtHist = $db->getConnection()->prepare("
            SELECT s.*
            FROM subscriptions s
            WHERE s.profile_id = ?
            ORDER BY s.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmtHist->execute([$profileId]);
        $rows = $stmtHist->fetchAll(\PDO::FETCH_ASSOC);
        
        $data = [];
        foreach($rows as $row) {
            $status = $row['payment_status'] ?? 'pending';
            $statusLabel = 'Pendente';
            $statusClass = 'bg-slate-100 text-slate-500';
            
            if($status === 'paid') { $statusLabel = 'Pago'; $statusClass = 'bg-green-50 text-green-700'; }
            elseif($status === 'refunded') { $statusLabel = 'Reembolsado'; $statusClass = 'bg-red-50 text-red-700'; }
            elseif($status === 'failed') { $statusLabel = 'Falhou'; $statusClass = 'bg-red-50 text-red-700'; }
            
            $expires = strtotime($row['expires_at']);
            $validity = ($expires > time() && $status === 'paid') 
                ? '<span class="text-green-600 font-bold">Ativo até '.date('d/m/y', $expires).'</span>' 
                : '<span class="text-slate-400">Expirou em '.date('d/m/y', $expires).'</span>';

            $data[] = [
                'date' => date('d/m/Y', strtotime($row['created_at'])),
                'time' => date('H:i', strtotime($row['created_at'])),
                'plan_name' => getPlanName($row['price_paid'], $row['plan_id']),
                'price' => number_format($row['price_paid'], 2, ',', '.'),
                'status_html' => "<span class='inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {$statusClass}'>{$statusLabel}</span>",
                'validity_html' => $validity
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $data, 'has_more' => count($rows) === $limit]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// --- 2. SEGURANÇA E INÍCIO ---
if (!isset($_SESSION['user_id'])) { header('Location: ' . url('/login')); exit; }
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'member') { header('Location: ' . url('/minha-conta')); exit; }

$db = \App\Core\Database::getInstance();
$conn = $db->getConnection();

// --- 3. DADOS GERAIS ---
$stmt = $conn->prepare("SELECT max_profile_slots FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userSlots = $stmt->fetchColumn() ?: 1;
$usedSlots = count($myProfiles);
$canCreateMore = ($usedSlots < $userSlots);

// Dados do Perfil
$profileImg = !empty($profile['profile_image']) ? url('/' . $profile['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($profile['display_name']).'&background=db2777&color=fff&size=256';
$workingHours = json_decode((string)($profile['working_hours'] ?? '[]'), true);
$serviceDetails = is_string($profile['service_details'] ?? '') ? json_decode((string)$profile['service_details'], true) : [];

// Status Online
$stmtActive = $conn->prepare("SELECT COUNT(*) FROM subscriptions WHERE profile_id = ? AND payment_status = 'paid' AND expires_at > NOW()");
$stmtActive->execute([$profile['id']]);
$hasActivePlan = $stmtActive->fetchColumn() > 0;
if (!$hasActivePlan) $hasActivePlan = (int)($profile['current_plan_level'] ?? 0) > 0;
$isVisuallyOnline = $hasActivePlan; 

// --- 4. ASSINATURAS ATIVAS ---
$activePlans = [];
try {
    $stmtPlans = $conn->prepare("
        SELECT s.*, DATEDIFF(s.expires_at, NOW()) as days_left 
        FROM subscriptions s 
        WHERE s.profile_id = ? AND s.payment_status = 'paid' AND s.expires_at > NOW()
    ");
    $stmtPlans->execute([$profile['id']]);
    $rawPlans = $stmtPlans->fetchAll(\PDO::FETCH_ASSOC);

    $groupedPlans = [];
    $hierarchyWeights = ['Plano VIP' => 3, 'Plano Plus' => 2, 'Plano Premium' => 1];

    foreach ($rawPlans as $p) {
        $price = $p['price_paid'] ?? 0;
        $name = getPlanName($price, $p['plan_id']);
        if (isset($groupedPlans[$name])) {
            if (strtotime($p['expires_at']) > strtotime($groupedPlans[$name]['expires_at'])) {
                $groupedPlans[$name]['expires_at'] = $p['expires_at'];
            }
        } else {
            $groupedPlans[$name] = [
                'plan_name' => $name,
                'expires_at' => $p['expires_at'],
                'weight' => $hierarchyWeights[$name] ?? 0
            ];
        }
    }
    foreach ($groupedPlans as &$gp) {
        $diff = strtotime($gp['expires_at']) - time();
        $gp['days_left'] = max(0, ceil($diff / (60 * 60 * 24)));
    }
    unset($gp);
    usort($groupedPlans, function($a, $b) { return $b['weight'] <=> $a['weight']; });
    $activePlans = array_values($groupedPlans);
} catch (\Exception $e) { $activePlans = []; }

// --- 5. AVALIAÇÕES ---
$reviews = [];
try {
    $stmtReview = $conn->prepare("SELECT * FROM profile_reviews WHERE profile_id = ? ORDER BY created_at DESC");
    $stmtReview->execute([$profile['id']]);
    $reviews = $stmtReview->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Exception $e) { $reviews = []; }
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel VIP - <?= htmlspecialchars($profile['display_name']) ?></title>
    
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
        const BASE_URL = "<?= rtrim(url('/'), '/') ?>";
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
        .profile-selector-item { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; cursor: pointer; transition: 0.2s; border: 1px solid transparent; }
        .profile-selector-item:hover { background: #f1f5f9; }
        .profile-selector-item.active { background: #fdf2f8; border-color: #fbcfe8; }
        .profile-selector-item.active .name { color: #db2777; font-weight: 700; }
        .modal-animate { animation: popIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes popIn { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body class="text-slate-600 antialiased">

    <nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 h-16">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="font-display font-bold text-xl tracking-tight text-slate-900">TOP<span class="text-pink-600">Model</span></span>
                <span class="bg-slate-100 text-slate-500 text-[10px] font-bold px-2 py-0.5 rounded border border-slate-200 uppercase tracking-wide">Painel Modelo</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="<?= url('/perfil/' . ($profile['slug'] ?? '')) ?>" target="_blank" class="hidden sm:flex text-xs font-semibold text-slate-500 hover:text-pink-600 transition items-center gap-1.5 uppercase tracking-wide">Ver Perfil Público <i class="fas fa-external-link-alt"></i></a>
                <a href="<?= url('/logout') ?>" class="text-xs font-bold text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg transition uppercase tracking-wide">Sair</a>
            </div>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 pt-24 pb-12 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <aside class="lg:col-span-3 space-y-5 sidebar-sticky">
            
            <div class="panel p-4 bg-white">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Meus Perfis (<?= count($myProfiles) ?>)</span>
                    <?php if($canCreateMore): ?>
                        <a href="<?= url('/perfil/criar') ?>" class="text-[10px] bg-green-50 text-green-600 px-2 py-0.5 rounded font-bold hover:bg-green-100">+ Novo</a>
                    <?php else: ?>
                        <a href="<?= url('/planos?msg=slots_full') ?>" class="text-[10px] bg-pink-50 text-pink-600 px-2 py-0.5 rounded font-bold hover:bg-pink-100">+ Comprar Vaga</a>
                    <?php endif; ?>
                </div>
                <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                    <?php 
                    foreach($myProfiles as $p): 
                        $isActive = ($p['id'] == $profile['id']); 
                        $pImg = !empty($p['profile_image']) ? url('/' . $p['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($p['display_name']).'&background=cbd5e1&color=fff&size=64'; 
                        
                        $isOnline = false;
                        if ($isActive) {
                            $isOnline = $isVisuallyOnline;
                        } else {
                            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM subscriptions WHERE profile_id = ? AND payment_status = 'paid' AND expires_at > NOW()");
                            $stmtCheck->execute([$p['id']]);
                            $isOnline = ($stmtCheck->fetchColumn() > 0);
                            if(!$isOnline) $isOnline = (int)($p['current_plan_level'] ?? 0) > 0;
                        }
                    ?>
                    <a href="?profile_id=<?= $p['id'] ?>" class="profile-selector-item <?= $isActive ? 'active' : '' ?>">
                        <img src="<?= $pImg ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium truncate name"><?= htmlspecialchars($p['display_name']) ?></p>
                            <?php if($isOnline): ?>
                                <p class="text-[10px] truncate text-green-600 font-bold">● Online</p>
                            <?php else: ?>
                                <p class="text-[10px] truncate text-slate-400">○ Offline</p>
                            <?php endif; ?>
                        </div>
                        <?php if($isActive): ?><i class="fas fa-check text-pink-500 text-xs"></i><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="panel p-6 text-center group bg-white border-t-4 border-pink-500">
                <div class="relative inline-block mb-3">
                    <img src="<?= $profileImg ?>" class="w-20 h-20 rounded-full object-cover ring-2 ring-offset-2 ring-pink-500 group-hover:scale-105 transition duration-300">
                    <?php if($isVisuallyOnline): ?>
                        <div class="absolute bottom-0 right-0 w-5 h-5 bg-green-500 border-4 border-white rounded-full" title="Online"></div>
                    <?php else: ?>
                        <div class="absolute bottom-0 right-0 w-5 h-5 bg-slate-400 border-4 border-white rounded-full" title="Offline"></div>
                    <?php endif; ?>
                </div>
                
                <h2 class="font-display font-bold text-lg text-slate-900 leading-tight"><?= htmlspecialchars($profile['display_name']) ?></h2>
                <p class="text-xs text-slate-400 font-medium uppercase mt-1 tracking-wide">Editando Agora</p>
                <a href="<?= url('/planos?profile_id='.$profile['id']) ?>" class="block w-full py-2 mt-4 rounded-lg bg-slate-900 text-white font-bold text-xs hover:bg-slate-700 transition">Impulsionar Este Perfil</a>
            </div>
            
            <div class="panel hidden lg:block overflow-hidden py-1">
                <nav class="flex flex-col text-sm font-medium text-slate-600">
                    <a href="#plans" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-layer-group w-4 text-center"></i> Planos</a>
                    <a href="#photos" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-camera w-4 text-center"></i> Fotos</a>
                    <a href="#personal" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-user w-4 text-center"></i> Dados</a>
                    <a href="#appearance" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-magic w-4 text-center"></i> Aparência</a>
                    <a href="#reviews" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-star w-4 text-center"></i> Avaliações</a>
                    <a href="#service" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-map-marker-alt w-4 text-center"></i> Serviços</a>
                    <a href="#schedule" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-clock w-4 text-center"></i> Horários</a>
                    <a href="#history" class="px-5 py-2.5 hover:bg-slate-50 hover:text-pink-600 transition flex items-center gap-3"><i class="fas fa-history w-4 text-center"></i> Histórico</a>
                </nav>
            </div>
        </aside>
        
        <main class="lg:col-span-9 space-y-6">
            
            <section id="plans" class="scroll-mt-24">
                <div class="flex justify-between items-center mb-3 px-1">
                    <h3 class="font-display font-bold text-lg text-slate-800">Assinaturas Ativas</h3>
                    <a href="<?= url('/planos?profile_id='.$profile['id']) ?>" class="text-[10px] font-bold bg-pink-50 text-pink-600 px-3 py-1 rounded hover:bg-pink-100 transition uppercase tracking-wide">Comprar +</a>
                </div>
                <?php if (empty($activePlans)): ?>
                    <div class="panel p-6 text-center border-dashed border-slate-300">
                        <p class="text-slate-500 text-sm font-medium">Este perfil está no modo Gratuito (Offline).</p>
                        <a href="<?= url('/planos?profile_id='.$profile['id']) ?>" class="text-pink-600 text-xs font-bold hover:underline mt-1 inline-block">Ativar VIP para ficar Online</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php 
                        foreach($activePlans as $index => $plan): 
                            $isMain = ($index === 0); 
                            $statusLabel = $isMain ? 'VIGENTE' : 'RESERVA';
                            $statusColor = $isMain ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500';
                            $cardBorder = $isMain ? 'ring-1 ring-pink-500 bg-pink-50/20' : 'bg-white';
                        ?>
                        <div class="panel p-4 flex flex-col justify-between h-full <?= $cardBorder ?>">
                            <div>
                                <div class="flex justify-between items-start mb-2">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-600 font-bold text-xs"><?= strtoupper(substr($plan['plan_name'], 6, 1)) ?></span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-1 rounded <?= $statusColor ?>"><?= $statusLabel ?></span>
                                </div>
                                <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($plan['plan_name']) ?></h4>
                                <p class="text-[10px] text-slate-400 mt-0.5">Válido até: <?= date('d/m/Y', strtotime($plan['expires_at'])) ?></p>
                            </div>
                            <div class="mt-3 pt-3 border-t border-slate-100/50">
                                <div class="flex items-baseline gap-1"><span class="text-xl font-bold text-slate-800"><?= $plan['days_left'] ?></span><span class="text-[10px] text-slate-500 font-medium">dias restantes</span></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <section id="photos" class="panel p-6 scroll-mt-24 relative bg-white">
                <div class="flex justify-between items-center mb-4"><h3 class="font-display font-bold text-lg text-slate-800">Galeria</h3><span id="upload-status-text" class="text-[10px] font-bold text-pink-600 opacity-0 transition uppercase">Enviando...</span></div>
                <div id="drop-zone" class="border border-dashed border-slate-300 bg-slate-50 rounded-xl p-6 text-center cursor-pointer hover:bg-white hover:border-pink-400 hover:shadow-sm transition group" onclick="document.getElementById('photoInput').click()">
                    <input type="file" id="photoInput" multiple accept="image/*" class="hidden" onchange="handleFiles(this.files)">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fas fa-cloud-upload-alt text-2xl text-slate-300 group-hover:text-pink-500 transition"></i>
                        <div><p class="text-sm font-semibold text-slate-600">Clique ou arraste fotos</p><p class="text-[10px] text-slate-400">JPG, PNG, WebP (Max 5MB)</p></div>
                    </div>
                    <div id="loading-overlay" class="hidden mt-3"><div class="h-1 w-full bg-slate-200 rounded-full overflow-hidden"><div id="progress-bar" class="h-full bg-pink-500 w-0 transition-all duration-300"></div></div></div>
                </div>
                <div id="photoGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mt-5">
                    <?php if (!empty($photos)): foreach($photos as $photo): ?>
                        <div class="relative group aspect-[3/4] rounded-lg overflow-hidden bg-slate-100 shadow-sm border border-slate-200" id="photo-<?= $photo['id'] ?>">
                            <img src="<?= url('/' . $photo['file_path']) ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[8px] font-bold text-white rounded backdrop-blur-md <?= $photo['is_approved'] ? 'bg-green-500/80' : 'bg-yellow-500/80' ?>"><?= $photo['is_approved'] ? 'OK' : 'ANÁLISE' ?></span>
                            <button onclick="deletePhoto(<?= $photo['id'] ?>)" class="absolute top-1.5 right-1.5 bg-white text-red-500 w-5 h-5 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow hover:scale-110"><i class="fas fa-times text-[10px]"></i></button>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </section>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <section id="personal" class="panel p-6 scroll-mt-24 relative bg-white">
                    <div class="flex justify-between items-center mb-5"><h3 class="font-display font-bold text-lg text-slate-800">Dados Pessoais</h3><span id="msg-bio" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span></div>
                    <form onsubmit="saveCard(event, 'bio')" class="space-y-4">
                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Nome de Exibição</label><input type="text" name="display_name" value="<?= htmlspecialchars($profile['display_name']) ?>" class="input-std"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Gênero</label><select name="gender" class="input-std"><option value="woman" <?= $profile['gender']=='woman'?'selected':'' ?>>Mulher</option><option value="man" <?= $profile['gender']=='man'?'selected':'' ?>>Homem</option><option value="trans" <?= $profile['gender']=='trans'?'selected':'' ?>>Trans</option><option value="couple" <?= $profile['gender']=='couple'?'selected':'' ?>>Casal</option></select></div>
                            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Orientação</label><select name="orientation" class="input-std"><option value="hetero" <?= $profile['orientation']=='hetero'?'selected':'' ?>>Hétero</option><option value="bi" <?= $profile['orientation']=='bi'?'selected':'' ?>>Bi</option></select></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Nascimento</label><input type="date" name="birth_date" value="<?= $profile['birth_date'] ?>" class="input-std"></div>
                            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Etnia</label><select name="ethnicity" class="input-std"><option value="">Selecione...</option><option value="asian" <?= ($profile['ethnicity']??'')=='asian'?'selected':'' ?>>Asiática</option><option value="black" <?= ($profile['ethnicity']??'')=='black'?'selected':'' ?>>Negra</option><option value="caucasian" <?= ($profile['ethnicity']??'')=='caucasian'?'selected':'' ?>>Caucasiana</option><option value="latin" <?= ($profile['ethnicity']??'')=='latin'?'selected':'' ?>>Latina</option><option value="mixed" <?= ($profile['ethnicity']??'')=='mixed'?'selected':'' ?>>Parda</option></select></div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Nacionalidade</label>
                            <select name="nationality" class="input-std">
                                <option value="">Selecione...</option>
                                <?php
                                $nacionalidades = ['Afegã', 'Albanesa', 'Alemã', 'Andorrana', 'Angolana', 'Antiguana', 'Argentina', 'Armênia', 'Australiana', 'Austríaca', 'Azeri', 'Baamense', 'Barbadiana', 'Bareinita', 'Belga', 'Belizenha', 'Beninense', 'Bielorrussa', 'Boliviana', 'Bósnia', 'Botsuanesa', 'Brasileira', 'Britânica', 'Bruneana', 'Búlgara', 'Burquinense', 'Burundiana', 'Cabo-verdiana', 'Camaronesa', 'Cambojana', 'Canadense', 'Catariana', 'Cazaque', 'Centro-africana', 'Chadiana', 'Chilena', 'Chinesa', 'Cipriota', 'Colombiana', 'Comorense', 'Congolesa', 'Costarriquenha', 'Croata', 'Cubana', 'Dinamarquesa', 'Dominicana', 'Egípcia', 'Equatoriana', 'Eritreia', 'Eslovaca', 'Eslovena', 'Espanhola', 'Estadunidense', 'Estoniana', 'Etíope', 'Fijiana', 'Filipina', 'Finlandesa', 'Francesa', 'Gabonesa', 'Gambiana', 'Ganesa', 'Georgiana', 'Granadina', 'Grega', 'Guatemalteca', 'Guianesa', 'Guineense', 'Guinéu-equatoriana', 'Haitiana', 'Hondurenha', 'Húngara', 'Iemenita', 'Indiana', 'Indonésia', 'Iraniana', 'Iraquiana', 'Irlandesa', 'Islandesa', 'Israelense', 'Italiana', 'Jamaicana', 'Japonesa', 'Jordaniana', 'Kiribatiana', 'Kuwaitiana', 'Laosiana', 'Lesotiana', 'Letã', 'Libanesa', 'Liberiana', 'Líbia', 'Liechtensteinense', 'Lituana', 'Luxemburguesa', 'Macedônia', 'Malaia', 'Malauiana', 'Maldiva', 'Malinesa', 'Maltesa', 'Marfinense', 'Marroquina', 'Marshallina', 'Mauriciana', 'Mauritana', 'Mexicana', 'Mianmarense', 'Micronésia', 'Moçambicana', 'Moldava', 'Monegasca', 'Mongol', 'Montenegrina', 'Namibiana', 'Nauruana', 'Neozelandesa', 'Nepalesa', 'Nicaraguense', 'Nigeriana', 'Nigerina', 'Norueguesa', 'Omanense', 'Panamenha', 'Papua-nova-guineense', 'Paquistanesa', 'Paraguaia', 'Peruana', 'Polonesa', 'Portuguesa', 'Queniana', 'Quirguiz', 'Romena', 'Ruandesa', 'Russa', 'Salomonense', 'Salvadorenha', 'Samoana', 'Santa-lucense', 'São-cristovense', 'São-tomense', 'Saudita', 'Seichelense', 'Senegalesa', 'Serra-leonesa', 'Sérvia', 'Singapurense', 'Síria', 'Somali', 'Sri-lankesa', 'Sudanesa', 'Sueca', 'Suíça', 'Sul-africana', 'Surinamesa', 'Tailandesa', 'Tanzaniana', 'Tcheca', 'Timorense', 'Togolesa', 'Tonganesa', 'Trinitária', 'Tunisiana', 'Turca', 'Turcomena', 'Tuvaluana', 'Ucraniana', 'Ugandense', 'Uruguaia', 'Uzbeque', 'Venezuelana', 'Vietnamita', 'Zambiana', 'Zimbabuense'];
                                sort($nacionalidades);
                                foreach ($nacionalidades as $nac) {
                                    $selected = ($profile['nationality'] === $nac) ? 'selected' : '';
                                    echo "<option value=\"{$nac}\" {$selected}>{$nac}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button class="btn-primary mt-2">Salvar Dados</button>
                    </form>
                </section>

                <section id="appearance" class="panel p-6 scroll-mt-24 relative bg-white">
                     <div class="flex justify-between items-center mb-5"><h3 class="font-display font-bold text-lg text-slate-800">Aparência</h3><span id="msg-appearance" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span></div>
                    <form onsubmit="saveCard(event, 'appearance')" class="grid grid-cols-2 gap-3">
                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Cabelos</label><select name="hair_color" class="input-std"><option value="loira" <?= ($profile['hair_color']??'')=='loira'?'selected':'' ?>>Loira</option><option value="morena" <?= ($profile['hair_color']??'')=='morena'?'selected':'' ?>>Morena</option><option value="ruiva" <?= ($profile['hair_color']??'')=='ruiva'?'selected':'' ?>>Ruiva</option><option value="preto" <?= ($profile['hair_color']??'')=='preto'?'selected':'' ?>>Preto</option></select></div>
                        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Olhos</label><select name="eye_color" class="input-std"><option value="castanhos" <?= ($profile['eye_color']??'')=='castanhos'?'selected':'' ?>>Castanhos</option><option value="verdes" <?= ($profile['eye_color']??'')=='verdes'?'selected':'' ?>>Verdes</option><option value="azuis" <?= ($profile['eye_color']??'')=='azuis'?'selected':'' ?>>Azuis</option></select></div>
                        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Altura (cm)</label><input type="number" name="height_cm" value="<?= $profile['height_cm'] ?>" class="input-std" placeholder="170"></div>
                        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Peso (kg)</label><input type="number" name="weight_kg" value="<?= $profile['weight_kg'] ?>" class="input-std" placeholder="60"></div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Tamanho Seios</label>
                            <select name="cup_size" class="input-std">
                                <option value="">Selecione...</option>
                                <option value="Pequeno" <?= ($profile['cup_size'] ?? '') === 'Pequeno' ? 'selected' : '' ?>>Pequeno</option>
                                <option value="Médio" <?= ($profile['cup_size'] ?? '') === 'Médio' ? 'selected' : '' ?>>Médio</option>
                                <option value="Médio Grande" <?= ($profile['cup_size'] ?? '') === 'Médio Grande' ? 'selected' : '' ?>>Médio Grande</option>
                                <option value="Grande" <?= ($profile['cup_size'] ?? '') === 'Grande' ? 'selected' : '' ?>>Grande</option>
                                <option value="Muito Grande" <?= ($profile['cup_size'] ?? '') === 'Muito Grande' ? 'selected' : '' ?>>Muito Grande</option>
                            </select>
                        </div>
                        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Silicone</label><select name="silicone" class="input-std"><option value="0" <?= ($profile['silicone']??0) == '0' ? 'selected' : '' ?>>Não</option><option value="1" <?= ($profile['silicone']??0) == '1' ? 'selected' : '' ?>>Sim</option></select></div>
                        <div class="col-span-2"><label class="block text-xs font-semibold text-slate-500 mb-1">Depilação</label><select name="shaving" class="input-std"><option value="full" <?= ($profile['shaving']??'') == 'full' ? 'selected' : '' ?>>Completamente Depilada</option><option value="partial" <?= ($profile['shaving']??'') == 'partial' ? 'selected' : '' ?>>Parcialmente Depilada</option><option value="natural" <?= ($profile['shaving']??'') == 'natural' ? 'selected' : '' ?>>Completamente Natural</option></select></div>

                        <div class="col-span-2 mt-2 pt-2 border-t border-slate-100"><label class="block text-xs font-bold text-slate-400 uppercase mb-2">Medidas (cm)</label></div>
                        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Busto</label><input type="number" name="bust_cm" value="<?= $profile['bust_cm'] ?? '' ?>" class="input-std" placeholder="90"></div>
                        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Cintura</label><input type="number" name="waist_cm" value="<?= $profile['waist_cm'] ?? '' ?>" class="input-std" placeholder="60"></div>
                        <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-semibold text-slate-500 mb-1">Quadril</label><input type="number" name="hips_cm" value="<?= $profile['hips_cm'] ?? '' ?>" class="input-std" placeholder="90"></div>

                        <div class="col-span-2 flex items-center justify-between border-t border-slate-100 pt-3 mt-1">
                            <span class="text-xs font-bold text-slate-500 uppercase">Tatuagens?</span>
                            <div class="flex gap-4"><label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="tattoos" value="1" <?= ($profile['tattoos']??0)?'checked':'' ?> class="chk-custom"> <span class="text-sm">Sim</span></label><label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="tattoos" value="0" <?= !($profile['tattoos']??0)?'checked':'' ?> class="chk-custom"> <span class="text-sm">Não</span></label></div>
                        </div>
                        <div class="col-span-2"><button class="btn-primary">Salvar Aparência</button></div>
                    </form>
                </section>
            </div>
            
            <section id="reviews" class="panel p-6 scroll-mt-24 relative bg-white">
                <div class="flex justify-between items-center mb-5"><h3 class="font-display font-bold text-lg text-slate-800 flex items-center gap-2"><i class="fas fa-star text-yellow-400"></i> Gestão de Avaliações</h3><span class="text-xs font-bold bg-slate-100 text-slate-500 px-2 py-1 rounded"><?= count($reviews) ?> total</span></div>
                <?php if (empty($reviews)): ?><div class="text-center py-10 border border-dashed border-slate-200 rounded-xl bg-slate-50"><i class="far fa-comment-dots text-2xl text-slate-300 mb-2"></i><p class="text-sm text-slate-500">Ainda não há avaliações.</p></div>
                <?php else: ?><div class="space-y-4"><?php foreach($reviews as $rev): ?><div class="bg-slate-50 border border-slate-200 rounded-xl p-4"><div class="flex justify-between items-start mb-2"><div><span class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($rev['reviewer_name'] ?? 'Anônimo') ?></span><div class="flex text-yellow-400 text-xs mt-0.5"><?php for($i=0; $i<5; $i++): ?><i class="<?= $i < $rev['rating'] ? 'fas' : 'far' ?> fa-star"></i><?php endfor; ?></div></div><div class="flex gap-2"><?php if($hasActivePlan): ?><button onclick="deleteReview(<?= $rev['id'] ?>)" class="text-slate-400 hover:text-red-500 transition text-xs flex items-center gap-1"><i class="fas fa-trash-alt"></i> Apagar</button><?php else: ?><button class="text-slate-300 cursor-not-allowed text-xs flex items-center gap-1"><i class="fas fa-lock"></i> Apagar</button><?php endif; ?></div></div><p class="text-sm text-slate-600 italic mb-3">"<?= htmlspecialchars($rev['comment']) ?>"</p><form onsubmit="replyReview(event, <?= $rev['id'] ?>)" class="border-t border-slate-200 pt-2 mt-2"><?php if(!empty($rev['reply'])): ?><div class="bg-white p-3 rounded-lg border border-pink-100 text-xs text-slate-600"><strong class="text-pink-600 block mb-1">Sua Resposta:</strong><?= htmlspecialchars($rev['reply']) ?></div><?php else: ?><div class="flex gap-2"><input type="text" name="reply_text" placeholder="Escreva uma resposta..." class="flex-1 bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs focus:border-pink-500 outline-none"><button class="bg-slate-800 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-700">Enviar</button></div><?php endif; ?></form></div><?php endforeach; ?></div><?php endif; ?>
            </section>
            
            <section id="about" class="panel p-6 scroll-mt-24 relative bg-white">
                 <div class="flex justify-between items-center mb-5"><h3 class="font-display font-bold text-lg text-slate-800">Sobre Mim</h3><span id="msg-about" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span></div>
                <form onsubmit="saveCard(event, 'about')" class="space-y-5">
                    <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                    <div><textarea name="bio" rows="3" class="input-std" placeholder="Descreva você..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea></div>
                    <div class="flex gap-8 border-t border-slate-100 pt-4">
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Fuma?</label><div class="flex gap-3"><label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="smoker" value="no" <?= $profile['smoker']=='no'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Não</span></label><label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="smoker" value="yes" <?= $profile['smoker']=='yes'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Sim</span></label></div></div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-2">Bebe?</label><div class="flex gap-3"><label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="drinker" value="no" <?= $profile['drinker']=='no'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Não</span></label><label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="drinker" value="yes" <?= $profile['drinker']=='yes'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Sim</span></label><label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="drinker" value="occasionally" <?= $profile['drinker']=='occasionally'?'checked':'' ?> class="chk-custom"> <span class="text-sm">Social</span></label></div></div>
                    </div>
                    <button class="btn-primary">Salvar Descrição</button>
                </form>
            </section>
            
            <section id="service" class="panel p-6 scroll-mt-24 relative bg-white">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4"><h3 class="font-display font-bold text-lg text-slate-800">Locais & Serviços</h3><span id="msg-service_details" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                         <div class="mb-4 relative"><label class="block text-xs font-semibold text-slate-500 mb-1">Adicionar Cidade</label><input type="text" placeholder="Buscar..." onkeyup="searchCity(this.value)" class="input-std"><div id="cityResults" class="absolute z-20 w-full bg-white shadow-xl border rounded-lg mt-1 hidden max-h-40 overflow-y-auto"></div></div>
                        <div id="myCitiesList" class="space-y-2"><?php if(!empty($locations)): foreach($locations as $loc): ?><div class="flex items-center justify-between bg-white p-2.5 rounded border border-slate-200" id="city-row-<?= $loc['id'] ?>"><span class="text-sm font-semibold text-slate-700"><?= $loc['name'] ?></span><div class="flex items-center gap-2"><label class="flex items-center gap-1 text-[10px] uppercase font-bold cursor-pointer bg-slate-50 px-2 py-1 rounded hover:bg-slate-100"><input type="radio" name="base_city" onchange="setBaseCity(<?= $loc['id'] ?>)" <?= $loc['is_base_city']?'checked':'' ?> class="accent-pink-600"> Base</label><button onclick="removeCity(<?= $loc['id'] ?>)" class="text-slate-400 hover:text-red-500 px-1"><i class="fas fa-times"></i></button></div></div><?php endforeach; endif; ?></div>
                    </div>
                    <form onsubmit="saveCard(event, 'service_details')" class="space-y-5">
                        <input type="hidden" name="force_submit" value="1"><input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200"><label class="flex items-center gap-2 mb-2 font-bold text-slate-800 cursor-pointer"><input type="checkbox" name="incall_available" value="1" <?= $profile['incall_available'] ? 'checked' : '' ?> class="chk-custom"> Incall (Local Próprio)</label><div class="pl-6 space-y-1 text-sm text-slate-600"><label class="block"><input type="checkbox" name="details[incall_private]" value="1" <?= ($serviceDetails['incall_private'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Apartamento</label><label class="block"><input type="checkbox" name="details[incall_hotel]" value="1" <?= ($serviceDetails['incall_hotel'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Hotel</label></div></div>
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200"><label class="flex items-center gap-2 mb-2 font-bold text-slate-800 cursor-pointer"><input type="checkbox" name="outcall_available" value="1" <?= $profile['outcall_available'] ? 'checked' : '' ?> class="chk-custom"> Outcall (Atende Fora)</label><div class="pl-6 space-y-1 text-sm text-slate-600"><label class="block"><input type="checkbox" name="details[outcall_hotel]" value="1" <?= ($serviceDetails['outcall_hotel'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Hotéis</label><label class="block"><input type="checkbox" name="details[outcall_home]" value="1" <?= ($serviceDetails['outcall_home'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Residência</label><label class="block"><input type="checkbox" name="details[outcall_events]" value="1" <?= ($serviceDetails['outcall_events'] ?? 0) ? 'checked' : '' ?> class="mr-2 chk-custom"> Eventos</label></div></div>
                        <button class="btn-primary">Salvar Serviços</button>
                    </form>
                </div>
            </section>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <section id="schedule" class="panel p-6 relative bg-white">
                    <div class="flex justify-between items-center mb-5"><h3 class="font-display font-bold text-lg text-slate-800">Horários</h3><span id="msg-schedule" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span></div>
                    <form onsubmit="saveSchedule(event)">
                        <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                        <div class="space-y-2 mb-4"><label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 <?= $profile['is_24_7']?'bg-pink-50 border-pink-200':'' ?>"><input type="radio" name="mode" value="24_7" onchange="toggleSchedule(this.value)" <?= $profile['is_24_7']?'checked':'' ?> class="text-pink-600"><span class="ml-2 text-sm font-bold">24 Horas / 7 Dias</span></label><label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 <?= $profile['show_as_night']?'bg-pink-50 border-pink-200':'' ?>"><input type="radio" name="mode" value="night" onchange="toggleSchedule(this.value)" <?= $profile['show_as_night']?'checked':'' ?> class="text-pink-600"><span class="ml-2 text-sm font-bold">Plantão Noturno</span></label><label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50"><input type="radio" name="mode" value="custom" onchange="toggleSchedule(this.value)" <?= (!$profile['is_24_7']&&!$profile['show_as_night'])?'checked':'' ?> class="text-pink-600"><span class="ml-2 text-sm font-bold">Horário Personalizado</span></label></div>
                        <div id="schedule-table" class="<?= ($profile['is_24_7']||$profile['show_as_night'])?'hidden':'' ?> border-t pt-2 space-y-2"><?php $days = ['seg'=>'Seg','ter'=>'Ter','qua'=>'Qua','qui'=>'Qui','sex'=>'Sex','sab'=>'Sáb','dom'=>'Dom']; foreach($days as $key=>$label): $isActive = isset($workingHours[$key]['active']) && $workingHours[$key]['active']=='1'; ?><div class="flex items-center justify-between text-sm"><label class="flex items-center w-20 cursor-pointer"><input type="checkbox" name="hours[<?= $key ?>][active]" value="1" <?= $isActive?'checked':'' ?> onchange="toggleDay('<?= $key ?>')" class="rounded chk-custom"><span class="ml-2 font-medium"><?= $label ?></span></label><div id="time-<?= $key ?>" class="flex gap-1 <?= $isActive?'':'opacity-30 pointer-events-none' ?>"><input type="time" name="hours[<?= $key ?>][start]" value="<?= $workingHours[$key]['start']??'09:00' ?>" class="border rounded px-1 w-20 text-xs"><input type="time" name="hours[<?= $key ?>][end]" value="<?= $workingHours[$key]['end']??'22:00' ?>" class="border rounded px-1 w-20 text-xs"></div></div><?php endforeach; ?></div>
                        <button class="btn-primary mt-4">Salvar Horários</button>
                    </form>
                </section>
                
                <div class="space-y-6">
                    <section class="panel p-6 relative bg-white">
                        <div class="flex justify-between items-center mb-5"><h3 class="font-display font-bold text-lg text-slate-800">Contato</h3><span id="msg-contact" class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded opacity-0 transition uppercase">Salvo</span></div>
                        <form onsubmit="saveCard(event, 'contact')" class="space-y-4">
                            <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                            <div><label class="block text-xs font-semibold text-slate-500 mb-1">WhatsApp</label><input type="text" name="phone" value="<?= htmlspecialchars($profile['phone']) ?>" class="input-std"></div>
                            <div class="space-y-2"><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="whatsapp_enabled" value="1" <?= $profile['whatsapp_enabled']?'checked':'' ?> class="chk-custom"><span class="text-sm font-medium">Atender WhatsApp</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="viber_enabled" value="1" <?= $profile['viber_enabled']?'checked':'' ?> class="chk-custom"><span class="text-sm font-medium">Atender Viber</span></label></div>
                            <div class="pt-2 border-t border-slate-100"><span class="block text-xs font-bold text-slate-500 uppercase mb-2">Preferência</span><div class="flex gap-2 text-sm"><label class="cursor-pointer bg-slate-50 px-3 py-1 rounded border hover:border-pink-300"><input type="radio" name="contact_preference" value="sms_only" <?= $profile['contact_preference']=='sms_only'?'checked':'' ?>> Chat</label><label class="cursor-pointer bg-slate-50 px-3 py-1 rounded border hover:border-pink-300"><input type="radio" name="contact_preference" value="call_only" <?= $profile['contact_preference']=='call_only'?'checked':'' ?>> Voz</label><label class="cursor-pointer bg-slate-50 px-3 py-1 rounded border hover:border-pink-300"><input type="radio" name="contact_preference" value="sms_call" <?= $profile['contact_preference']=='sms_call'?'checked':'' ?>> Ambos</label></div></div>
                            <button class="btn-primary">Salvar Contato</button>
                        </form>
                    </section>
                    
                    <section class="panel p-6 bg-white">
                        <h3 class="font-display font-bold text-lg text-slate-800 mb-4">Idiomas</h3>
                         <div class="flex gap-2 mb-3"><select id="langSelect" class="flex-1 input-std p-1.5"><option>Inglês</option><option>Espanhol</option><option>Francês</option><option>Italiano</option></select><select id="langLevel" class="w-24 input-std p-1.5"><option value="medium">Médio</option><option value="native">Fluente</option></select><button onclick="addLanguage()" class="bg-pink-600 text-white px-3 rounded-lg hover:bg-pink-700"><i class="fas fa-plus"></i></button></div>
                         <div id="langList" class="space-y-2"><?php if(!empty($languages)): foreach($languages as $lang): ?><div class="flex justify-between items-center bg-slate-50 p-2 rounded-lg text-sm border border-slate-100" id="lang-row-<?= $lang['id'] ?>"><span><?= $lang['language'] ?> <small class="text-slate-400 uppercase"><?= $lang['level'] ?></small></span><button onclick="removeLanguage(<?= $lang['id'] ?>)" class="text-red-400 text-xs"><i class="fas fa-times"></i></button></div><?php endforeach; endif; ?></div>
                    </section>
                </div>
            </div>
            
            <section id="history" class="panel overflow-hidden scroll-mt-24">
                <div class="p-6 border-b border-slate-100 bg-white">
                    <h3 class="font-display font-bold text-lg text-slate-800 flex items-center gap-2">
                        <i class="fas fa-history text-pink-500"></i> Histórico Detalhado
                    </h3>
                </div>
                
                <div class="w-full">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="hidden md:table-header-group bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 border-b border-slate-200">Data</th>
                                <th class="px-6 py-3 border-b border-slate-200">Plano</th>
                                <th class="px-6 py-3 border-b border-slate-200">Valor</th>
                                <th class="px-6 py-3 border-b border-slate-200">Status</th>
                                <th class="px-6 py-3 border-b border-slate-200 text-right">Validade</th>
                            </tr>
                        </thead>
                        <tbody id="history-body" class="divide-y divide-slate-100 bg-white">
                            </tbody>
                    </table>
                </div>
                
                <div id="history-loading" class="p-4 text-center text-xs text-slate-400 hidden">Carregando...</div>
                <div class="p-4 text-center bg-slate-50 border-t border-slate-100">
                    <button id="btn-load-history" onclick="loadHistory()" class="text-xs font-bold text-pink-600 hover:text-pink-700 uppercase tracking-wide">
                        Carregar Mais
                    </button>
                </div>
            </section>

        </main>
    </div>

    <div id="vip-success-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeVipModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center modal-animate border-2 border-green-500">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-sm"><i class="fas fa-check"></i></div>
            <h2 class="text-2xl font-display font-bold text-slate-800 mb-2">Parabéns!</h2>
            <p class="text-slate-500 text-sm mb-6">Você adquiriu o plano <strong id="modal-plan-name" class="text-slate-900">VIP</strong> com sucesso.<br>Seu perfil está destacado por <strong id="modal-days" class="text-green-600">30</strong> dias!</p>
            <button onclick="closeVipModal()" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-xl transition shadow-lg transform active:scale-95">Incrível, vamos lá!</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'vip_success') {
            document.getElementById('modal-plan-name').textContent = decodeURIComponent(urlParams.get('plan_name') || 'VIP');
            document.getElementById('modal-days').textContent = urlParams.get('days') || '30';
            document.getElementById('vip-success-modal').classList.remove('hidden');
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?profile_id=" + CURRENT_PROFILE_ID;
            window.history.replaceState({path: newUrl}, '', newUrl);
        }
        
        loadHistory();
    });
    
    // --- LÓGICA DE HISTÓRICO AJAX ---
    let historyPage = 1;
    let isLoadingHistory = false;
    
    async function loadHistory() {
        if (isLoadingHistory) return;
        
        const btn = document.getElementById('btn-load-history');
        const loader = document.getElementById('history-loading');
        const tableBody = document.getElementById('history-body');
        
        isLoadingHistory = true;
        btn.disabled = true;
        btn.classList.add('opacity-50');
        loader.classList.remove('hidden');
        
        try {
            const res = await fetch(`?action=load_history&page=${historyPage}&profile_id=${CURRENT_PROFILE_ID}`);
            const json = await res.json();
            
            if (json.success) {
                if (json.data.length === 0 && historyPage === 1) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="p-6 text-center text-slate-400 text-xs">Nenhum histórico encontrado.</td></tr>';
                    btn.classList.add('hidden');
                } else {
                    json.data.forEach(item => {
                        const tr = document.createElement('tr');
                        // No Desktop é ROW, no Mobile é COL (Card Layout)
                        tr.className = 'flex flex-col md:table-row border-b border-slate-100 md:border-0 hover:bg-slate-50 transition p-4 md:p-0';
                        
                        tr.innerHTML = `
                            <td class="flex justify-between md:table-cell md:px-6 md:py-4 md:whitespace-nowrap">
                                <span class="md:hidden text-xs font-bold text-slate-500 uppercase w-20">Data</span>
                                <div class="text-right md:text-left">
                                    <span class="font-bold text-slate-700 block text-sm md:text-base">${item.date}</span>
                                    <span class="text-[10px] text-slate-400">${item.time}</span>
                                </div>
                            </td>
                            <td class="flex justify-between items-center md:table-cell md:px-6 md:py-4">
                                <span class="md:hidden text-xs font-bold text-slate-500 uppercase w-20">Plano</span>
                                <span class="font-medium text-slate-800 text-sm md:text-xs text-right md:text-left">${item.plan_name}</span>
                            </td>
                            <td class="flex justify-between items-center md:table-cell md:px-6 md:py-4">
                                <span class="md:hidden text-xs font-bold text-slate-500 uppercase w-20">Valor</span>
                                <span class="text-sm md:text-xs font-bold text-slate-600 text-right md:text-left">R$ ${item.price}</span>
                            </td>
                            <td class="flex justify-between items-center md:table-cell md:px-6 md:py-4">
                                <span class="md:hidden text-xs font-bold text-slate-500 uppercase w-20">Status</span>
                                <div class="text-right md:text-left">${item.status_html}</div>
                            </td>
                            <td class="flex justify-between items-center md:table-cell md:px-6 md:py-4">
                                <span class="md:hidden text-xs font-bold text-slate-500 uppercase w-20">Validade</span>
                                <div class="text-sm md:text-xs text-right md:text-left">${item.validity_html}</div>
                            </td>
                        `;
                        tableBody.appendChild(tr);
                    });
                    
                    historyPage++;
                    
                    if (!json.has_more) {
                        btn.classList.add('hidden');
                    }
                }
            } else {
                console.error(json.message);
            }
        } catch (e) {
            console.error('Erro ao carregar histórico');
        } finally {
            isLoadingHistory = false;
            btn.disabled = false;
            btn.classList.remove('opacity-50');
            loader.classList.add('hidden');
        }
    }

    function closeVipModal() { document.getElementById('vip-success-modal').classList.add('hidden'); }

    async function replyReview(e, id) {
        e.preventDefault();
        const text = e.target.querySelector('input').value;
        if(!text) return;
        try {
            const res = await fetch(`${BASE_URL}/api/reviews/reply`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({review_id: id, reply: text, profile_id: CURRENT_PROFILE_ID})
            });
            if((await res.json()).success) location.reload();
        } catch(err) { alert('Erro ao responder.'); }
    }

    async function deleteReview(id) {
        if(!confirm('Apagar avaliação?')) return;
        try {
            const res = await fetch(`${BASE_URL}/api/reviews/delete`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({review_id: id, profile_id: CURRENT_PROFILE_ID})
            });
            if((await res.json()).success) location.reload();
        } catch(err) { alert('Erro ao apagar.'); }
    }

    async function saveCard(event, sectionName) {
        event.preventDefault();
        const form = event.target;
        const btn = form.querySelector('button');
        const oldText = btn.innerText; btn.innerText = "..."; btn.disabled = true;
        
        const formData = new FormData(form);
        const dataObj = {};
        
        // CORREÇÃO PONTUAL: Tratamento de strings vazias para INT (Medidas)
        formData.forEach((value, key) => {
            if (value === '' && (key.endsWith('_cm') || key.endsWith('_kg'))) {
                dataObj[key] = null; // Envia null se estiver vazio
            } else {
                dataObj[key] = value;
            }
        });

        if (!dataObj.profile_id) dataObj.profile_id = CURRENT_PROFILE_ID;
        
        if(sectionName === 'service_details') {
            const details = {};
            form.querySelectorAll('input[name^="details["]').forEach(cb => { const key = cb.name.match(/\[(.*?)\]/)[1]; details[key] = cb.checked ? 1 : 0; });
            dataObj.details = details;
            dataObj.incall_available = form.querySelector('[name="incall_available"]').checked ? 1 : 0;
            dataObj.outcall_available = form.querySelector('[name="outcall_available"]').checked ? 1 : 0;
        }
        if(sectionName === 'contact') {
            dataObj.whatsapp_enabled = form.querySelector('[name="whatsapp_enabled"]')?.checked ? 1 : 0;
            dataObj.viber_enabled = form.querySelector('[name="viber_enabled"]')?.checked ? 1 : 0;
        }
        try {
            const res = await fetch(`${BASE_URL}/api/perfil/save`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ section: sectionName, data: dataObj }) });
            const json = await res.json();
            if(json.success) { 
                const msg = document.getElementById('msg-' + sectionName); 
                if(msg) { msg.style.opacity = '1'; setTimeout(() => msg.style.opacity = '0', 2000); } 
            } else alert('Erro: ' + json.message);
        } catch(e) { console.error(e); alert('Erro conexão'); }
        finally { btn.innerText = oldText; btn.disabled = false; }
    }
    
    async function saveSchedule(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button');
        const oldText = btn.innerText; btn.innerText = "..."; btn.disabled = true;
        const fd = new FormData(form);
        const data = { is_24_7: 0, show_as_night: 0, working_hours: '', profile_id: CURRENT_PROFILE_ID };
        if(fd.get('mode') === '24_7') data.is_24_7 = 1;
        if(fd.get('mode') === 'night') data.show_as_night = 1;
        const workingHoursObj = {};
        ['seg','ter','qua','qui','sex','sab','dom'].forEach(d => {
            const chk = form.querySelector(`input[name="hours[${d}][active]"]`);
            if(chk && chk.checked) { workingHoursObj[d] = { active: 1, start: fd.get(`hours[${d}][start]`), end: fd.get(`hours[${d}][end]`) }; }
        });
        data.working_hours = JSON.stringify(workingHoursObj);
        try { await fetch(`${BASE_URL}/api/perfil/save`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ section: 'schedule', data: data }) }); const msg = document.getElementById('msg-schedule'); msg.style.opacity='1'; setTimeout(()=>msg.style.opacity='0', 2000); } catch(err) { alert('Erro ao salvar horários'); } finally { btn.innerText = oldText; btn.disabled = false; }
    }
    function toggleSchedule(mode){ document.getElementById('schedule-table').classList.toggle('hidden', mode!=='custom'); }
    function toggleDay(k){ const d=document.getElementById('time-'+k); const chk=document.querySelector(`input[name="hours[${k}][active]"]`); d.classList.toggle('opacity-30', !chk.checked); d.classList.toggle('pointer-events-none', !chk.checked); }
    const dropZone = document.getElementById('drop-zone');
    ['dragenter','dragover'].forEach(e=>dropZone.addEventListener(e,ev=>{ev.preventDefault();dropZone.classList.add('bg-pink-50','border-pink-400')}));
    ['dragleave','drop'].forEach(e=>dropZone.addEventListener(e,ev=>{ev.preventDefault();dropZone.classList.remove('bg-pink-50','border-pink-400')}));
    dropZone.addEventListener('drop',e=>handleFiles(e.dataTransfer.files));
    async function handleFiles(files) { if(!files.length) return; document.getElementById('loading-overlay').classList.remove('hidden'); document.getElementById('upload-status-text').style.opacity = '1'; const fd = new FormData(); fd.append('profile_id', CURRENT_PROFILE_ID); for(let i=0; i<files.length; i++) fd.append('photos[]', files[i]); try { const res = await fetch('<?= url('/api/photos/upload') ?>', {method:'POST', body:fd}); const data = await res.json(); if(data.success) setTimeout(()=>location.reload(),500); else alert(data.message); } catch(e) { alert('Erro upload'); } finally { document.getElementById('loading-overlay').classList.add('hidden'); } }
    async function deletePhoto(id) { if(!confirm('Excluir?')) return; document.getElementById('photo-'+id).style.opacity='0.5'; try { await fetch('<?= url('/api/photos/delete') ?>', {method:'POST', body:JSON.stringify({id:id, profile_id: CURRENT_PROFILE_ID})}); document.getElementById('photo-'+id).remove(); } catch(e){alert('Erro ao excluir');} }
    async function searchCity(q) { const div = document.getElementById('cityResults'); if(q.length<3) { div.classList.add('hidden'); return; } const res = await fetch(`${BASE_URL}/api/locations/search?q=${q}`); const list = await res.json(); div.innerHTML=''; if(list.length) { div.classList.remove('hidden'); list.forEach(c => { const el = document.createElement('div'); el.className='p-2 hover:bg-pink-50 cursor-pointer text-sm border-b text-slate-700'; el.innerText = `${c.name} - ${c.country}`; el.onclick=()=>{addCity(c.id); div.classList.add('hidden');}; div.appendChild(el); }); } else div.classList.add('hidden'); }
    async function addCity(id){ await manageLoc('add', id); location.reload(); }
    async function removeCity(id){ if(confirm('Remover?')){ await manageLoc('remove', id); location.reload(); } }
    async function setBaseCity(id){ await manageLoc('set_base', id); alert('Atualizada!'); }
    async function manageLoc(act, cid){ await fetch(`${BASE_URL}/api/locations/manage`, { method:'POST', body:JSON.stringify({action:act, city_id:cid, profile_id: CURRENT_PROFILE_ID}) }); }
    async function addLanguage(){ const l=document.getElementById('langSelect').value; const v=document.getElementById('langLevel').value; await fetch(`${BASE_URL}/api/languages/manage`, { method:'POST', body:JSON.stringify({action:'add', language:l, level:v, profile_id: CURRENT_PROFILE_ID}) }); location.reload(); }
    async function removeLanguage(id){ await fetch(`${BASE_URL}/api/languages/manage`, { method:'POST', body:JSON.stringify({action:'remove', id:id, profile_id: CURRENT_PROFILE_ID}) }); location.reload(); }
    </script>
</body>
</html>