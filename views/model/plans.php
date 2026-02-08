<?php
use App\Core\Database;

// 1. SEGURANÇA E CONTEXTO
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /login'); exit; }

$userId = $_SESSION['user_id'];
$db = Database::getInstance();

// 2. IDENTIFICAR PERFIL ALVO
$stmt = $db->getConnection()->prepare("SELECT id, display_name, profile_image FROM profiles WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$defaultProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não tem perfil, manda criar (mas se já tiver cheio, o create_profile manda de volta pra cá com msg=slots_full)
if (!$defaultProfile && !isset($_GET['msg'])) { header('Location: /perfil/criar'); exit; }

if (!$defaultProfile) {
    $targetProfile = ['id' => 0, 'display_name' => 'Nova Modelo', 'profile_image' => null];
    $targetProfileId = 0;
} else {
    $targetProfileId = $_GET['profile_id'] ?? $defaultProfile['id'];
    $stmt = $db->getConnection()->prepare("SELECT id, display_name, profile_image FROM profiles WHERE id = ? AND user_id = ?");
    $stmt->execute([$targetProfileId, $userId]);
    $targetProfile = $stmt->fetch(PDO::FETCH_ASSOC) ?: $defaultProfile;
}

$profileImg = !empty($targetProfile['profile_image']) ? url('/' . $targetProfile['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($targetProfile['display_name']).'&background=db2777&color=fff&size=128';

// 3. BUSCAR DADOS DO BANCO
// A. Preço do Slot
$stmt = $db->getConnection()->query("SELECT setting_value FROM settings WHERE setting_key = 'slot_price'");
$slotPrice = $stmt->fetchColumn() ?: 49.90;

// B. Planos
$stmt = $db->getConnection()->query("SELECT * FROM plan_types ORDER BY level ASC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($plans as &$plan) {
    $plan['benefits'] = !empty($plan['benefits_json']) ? json_decode($plan['benefits_json'], true) : [];
    $stmt = $db->getConnection()->prepare("SELECT * FROM plan_options WHERE plan_type_id = ? ORDER BY days ASC");
    $stmt->execute([$plan['id']]);
    $plan['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($plan);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos & Vip - TOP Model</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;700;800&display=swap" rel="stylesheet">
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
        
        .panel { 
            background: white; border: 1px solid #e2e8f0; border-radius: 1rem; 
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); transition: all 0.2s ease; 
        }
        .panel:hover { border-color: #fbcfe8; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        
        .radio-option-input { display: none; }
        .radio-option {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; 
            cursor: pointer; transition: all 0.15s; background: #fff;
        }
        .radio-option:hover { background: #f8fafc; border-color: #cbd5e1; }
        
        .radio-option-input:checked + .radio-option {
            border-color: #db2777; background: #fdf2f8; 
            box-shadow: 0 0 0 1px #db2777 inset;
        }
        .radio-circle {
            width: 16px; height: 16px; border: 2px solid #cbd5e1; border-radius: 50%; margin-right: 10px;
            position: relative; transition: 0.2s;
        }
        .radio-option-input:checked + .radio-option .radio-circle {
            border-color: #db2777; background: #db2777;
        }
        .radio-option-input:checked + .radio-option .radio-circle::after {
            content: ''; position: absolute; top: 4px; left: 4px; width: 4px; height: 4px;
            background: white; border-radius: 50%;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-down { animation: slideDown 0.5s ease-out forwards; }
    </style>
</head>
<body class="text-slate-600 antialiased">

    <nav class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-md border-b border-slate-200 h-14">
        <div class="max-w-6xl mx-auto px-4 h-full flex justify-between items-center">
            <a href="<?= url('/perfil/editar') ?>" class="flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition uppercase tracking-wide">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
            </a>
            <span class="font-display font-bold text-lg tracking-tight text-slate-900">TOP<span class="text-pink-600">Model</span></span>
            <div class="w-10"></div> 
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 pt-24 pb-16">
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'slots_full'): ?>
            <div class="max-w-3xl mx-auto mb-10 bg-amber-50 border border-amber-200 rounded-2xl p-6 flex flex-col sm:flex-row items-start gap-4 shadow-sm animate-slide-down relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-amber-400/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="bg-amber-100 p-3 rounded-full text-amber-600 shrink-0"><i class="fas fa-exclamation-triangle text-xl"></i></div>
                <div class="flex-1 relative z-10">
                    <h3 class="font-display font-bold text-amber-900 text-lg">Limite de Perfis Atingido</h3>
                    <p class="text-amber-800 text-sm mt-1 leading-relaxed">
                        Sua conta atingiu o número máximo de perfis. Adquira uma <strong>vaga extra</strong> abaixo.
                    </p>
                    <button onclick="scrollToSlot()" class="mt-4 text-xs font-bold uppercase tracking-wide text-white bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded-lg transition shadow-sm inline-flex items-center gap-2">
                        Ir para Compra de Vaga <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="font-display font-bold text-2xl text-slate-900">Turbinar Perfil</h1>
                <p class="text-sm text-slate-500 mt-1">Destaque-se nas buscas e atraia mais clientes.</p>
            </div>
            
            <?php if($targetProfileId > 0): ?>
            <div class="flex items-center gap-3 bg-white pl-2 pr-4 py-1.5 rounded-full border border-slate-200 shadow-sm">
                <img src="<?= $profileImg ?>" class="w-8 h-8 rounded-full object-cover border border-slate-100">
                <div class="text-left leading-none">
                    <p class="text-[9px] uppercase font-bold text-slate-400 mb-0.5">Aplicando em:</p>
                    <p class="font-bold text-xs text-slate-800"><?= htmlspecialchars($targetProfile['display_name']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($plans)): ?>
            <div class="text-center py-12 bg-white rounded-2xl border border-dashed border-slate-200">
                <p class="text-slate-500 text-sm">Nenhum plano disponível.</p>
            </div>
        <?php else: ?>
            
            <div class="grid gap-5 md:grid-cols-3 mb-12">
                <?php foreach ($plans as $plan): ?>
                    <div class="panel p-5 flex flex-col h-full relative">
                        <div class="text-center mb-4">
                            <h3 class="font-display font-bold text-lg text-slate-900"><?= htmlspecialchars($plan['name']) ?></h3>
                            <?php if(!empty($plan['description'])): ?>
                                <p class="text-xs text-slate-400 mt-1 leading-snug"><?= htmlspecialchars($plan['description']) ?></p>
                            <?php endif; ?>
                        </div>

                        <ul class="space-y-2 mb-5 flex-1 border-t border-b border-slate-50 py-3">
                            <?php if(!empty($plan['benefits'])): ?>
                                <?php foreach ($plan['benefits'] as $benefit): ?>
                                    <li class="flex items-start gap-2 text-xs text-slate-600">
                                        <i class="fas fa-check text-[10px] text-pink-500 mt-0.5"></i>
                                        <span><?= htmlspecialchars(trim($benefit)) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="text-xs text-slate-400 text-center">Benefícios padrão inclusos.</li>
                            <?php endif; ?>
                        </ul>

                        <form action="<?= url('/payment/checkout') ?>" method="POST" class="mt-auto">
                            <input type="hidden" name="profile_id" value="<?= $targetProfileId ?>">
                            <input type="hidden" name="type" value="vip">
                            <input type="hidden" name="plan_type_id" value="<?= $plan['id'] ?>">

                            <?php if(empty($plan['options'])): ?>
                                <p class="text-xs text-red-400 text-center">Indisponível.</p>
                            <?php else: ?>
                                <div class="space-y-2 mb-4">
                                    <?php foreach ($plan['options'] as $i => $option): ?>
                                        <label class="relative block">
                                            <input type="radio" name="plan_option_id" value="<?= $option['id'] ?>" class="radio-option-input" <?= $i===0?'checked':'' ?>>
                                            <div class="radio-option">
                                                <div class="flex items-center">
                                                    <div class="radio-circle"></div>
                                                    <span class="font-bold text-sm text-slate-700"><?= $option['days'] ?> Dias</span>
                                                </div>
                                                <span class="text-sm font-bold text-pink-600">R$ <?= number_format($option['price'], 2, ',', '.') ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-lg text-sm transition shadow-md flex items-center justify-center gap-2">
                                    Contratar
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div id="buy-slot-section" class="bg-slate-900 rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-lg scroll-mt-24">
            <div class="flex items-start gap-4">
                <div class="bg-white/10 p-3 rounded-full hidden sm:block"><i class="fas fa-layer-group text-pink-400 text-xl"></i></div>
                <div>
                    <h2 class="font-display font-bold text-xl text-white">Novo Slot de Perfil</h2>
                    <p class="text-slate-400 text-sm mt-1 max-w-lg leading-relaxed">Adquira uma vaga extra vitalícia.</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-4 bg-white/5 p-4 rounded-xl border border-white/10">
                <div class="text-center sm:text-right">
                    <p class="text-[10px] text-slate-400 uppercase font-bold">Valor Único</p>
                    <p class="text-2xl font-black text-white">R$ <?= number_format((float)$slotPrice, 2, ',', '.') ?></p>
                </div>
                <form action="<?= url('/payment/checkout') ?>" method="POST">
                    <input type="hidden" name="type" value="slot">
                    <button type="submit" class="bg-white hover:bg-pink-50 text-slate-900 font-bold py-2.5 px-6 rounded-lg text-sm transition shadow flex items-center gap-2 whitespace-nowrap">
                        <i class="fas fa-plus-circle text-pink-600"></i> Comprar Vaga
                    </button>
                </form>
            </div>
        </div>

    </div>

    <script>
        function scrollToSlot() {
            const el = document.getElementById('buy-slot-section');
            if(el) el.scrollIntoView({behavior: 'smooth'});
        }
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'slots_full'): ?>
            document.addEventListener('DOMContentLoaded', () => { setTimeout(scrollToSlot, 800); });
        <?php endif; ?>
    </script>

</body>
</html>