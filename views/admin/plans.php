<?php
// views/admin/plans.php
use App\Core\Database;

// 1. SEGURANÇA (Adicione sua verificação de admin aqui)
if (session_status() === PHP_SESSION_NONE) session_start();
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: /login'); exit; }

$db = Database::getInstance();
$message = '';

// 2. PROCESSAR SALVAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. Salvar Preço do Slot
    if (isset($_POST['slot_price'])) {
        $slotPriceRaw = str_replace(',', '.', $_POST['slot_price']); 
        $stmt = $db->getConnection()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('slot_price', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$slotPriceRaw, $slotPriceRaw]);
    }

    // B. Salvar Opções dos Planos (Dias e Preços)
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        foreach ($_POST['options'] as $optId => $data) {
            $priceRaw = str_replace(',', '.', $data['price']);
            $days = (int)$data['days'];
            
            // Atualiza a tabela existente plan_options
            $stmt = $db->getConnection()->prepare("UPDATE plan_options SET days = ?, price = ? WHERE id = ?");
            $stmt->execute([$days, $priceRaw, $optId]);
        }
    }
    
    $message = "Preços e dias atualizados com sucesso!";
}

// 3. BUSCAR DADOS
// Preço do Slot
$stmt = $db->getConnection()->query("SELECT setting_value FROM settings WHERE setting_key = 'slot_price'");
$slotPrice = $stmt->fetchColumn() ?: '49.90';

// Planos e suas Opções (Usando suas tabelas plan_types e plan_options)
$stmt = $db->getConnection()->query("SELECT * FROM plan_types ORDER BY level ASC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($plans as &$plan) {
    // Busca as opções vinculadas a este plano (plan_type_id)
    $stmt = $db->getConnection()->prepare("SELECT * FROM plan_options WHERE plan_type_id = ? ORDER BY days ASC");
    $stmt->execute([$plan['id']]);
    $plan['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($plan);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Planos - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .input-admin { width: 100%; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 8px; font-weight: 600; color: #334155; }
        .input-admin:focus { outline: none; border-color: #db2777; ring: 2px solid #fce7f3; }
    </style>
</head>
<body class="text-slate-600">

    <div class="max-w-6xl mx-auto px-4 py-12">
        
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Gerenciar Financeiro</h1>
            <a href="/painel/admin" class="text-sm font-bold text-slate-500 hover:text-slate-800"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if($message): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="bg-slate-900 text-white rounded-2xl p-8 mb-8 shadow-xl">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <h2 class="text-xl font-bold flex items-center gap-2"><i class="fas fa-layer-group text-pink-400"></i> Preço do Slot Extra</h2>
                        <p class="text-slate-400 text-sm mt-1">Valor cobrado para a modelo adicionar um perfil extra (Vitalício).</p>
                    </div>
                    <div class="w-full md:w-auto bg-white/10 p-4 rounded-xl border border-white/20">
                        <label class="block text-xs uppercase font-bold text-slate-400 mb-1">Valor (R$)</label>
                        <input type="text" name="slot_price" value="<?= number_format((float)$slotPrice, 2, ',', '') ?>" class="bg-transparent text-3xl font-black text-white w-32 focus:outline-none text-center border-b border-white/30 focus:border-pink-500 transition">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <?php foreach($plans as $plan): ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col h-full">
                        
                        <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-4">
                            <h3 class="font-bold text-lg text-slate-900"><?= htmlspecialchars($plan['name']) ?></h3>
                            <span class="text-xs bg-<?= $plan['color_hex'] ?? 'gray' ?>-100 text-<?= $plan['color_hex'] ?? 'gray' ?>-600 px-2 py-0.5 rounded font-bold uppercase">Nível <?= $plan['level'] ?></span>
                        </div>

                        <div class="space-y-3 flex-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Opções de Venda</p>
                            
                            <?php foreach($plan['options'] as $option): ?>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 relative group hover:border-pink-200 transition">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Dias</label>
                                            <input type="number" name="options[<?= $option['id'] ?>][days]" value="<?= $option['days'] ?>" class="input-admin">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Preço (R$)</label>
                                            <input type="text" name="options[<?= $option['id'] ?>][price]" value="<?= number_format($option['price'], 2, ',', '') ?>" class="input-admin text-pink-600">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-slate-50 text-center">
                            <p class="text-xs text-slate-400">Total de opções: <?= count($plan['options']) ?></p>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <div class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur border-t border-slate-200 p-4 z-50">
                <div class="max-w-6xl mx-auto flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex items-center gap-2">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </div>
            
            <div class="h-24"></div> </form>
    </div>

</body>
</html>