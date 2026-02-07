<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos - TOP Model</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        /* Estilo customizado para os Radios ficarem bonitos (Botões selecionáveis) */
        .plan-radio:checked + div {
            border-color: currentColor;
            background-color: rgba(255, 255, 255, 0.1);
            font-weight: bold;
        }
        .plan-radio:checked + div .check-icon {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans pb-20">

    <header class="bg-gray-800 shadow-lg border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-white"><i class="fas fa-rocket mr-2 text-pink-500"></i>Upgrade</h1>
            <a href="<?= url('/perfil/editar') ?>" class="text-sm text-gray-400 hover:text-white"><i class="fas fa-times"></i> Fechar</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-10">
        <h2 class="text-3xl text-center font-bold mb-2">Escolha seu nível de destaque</h2>
        <p class="text-center text-gray-400 mb-10">Selecione o plano e a quantidade de dias.</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <?php foreach($plans as $type): 
                // Define cores baseadas no tipo (hardcoded para visual bonito)
                $colorClass = match($type['name']) {
                    'Premium' => 'text-blue-400 border-blue-500',
                    'Plus' => 'text-purple-400 border-purple-500',
                    'VIP' => 'text-yellow-400 border-yellow-500',
                    default => 'text-gray-400 border-gray-500'
                };
                $btnClass = match($type['name']) {
                    'Premium' => 'bg-blue-600 hover:bg-blue-700',
                    'Plus' => 'bg-purple-600 hover:bg-purple-700',
                    'VIP' => 'bg-yellow-500 hover:bg-yellow-600 text-black',
                    default => 'bg-gray-600'
                };
            ?>

            <div class="bg-gray-800 rounded-2xl border border-gray-700 shadow-xl overflow-hidden flex flex-col h-full relative">
                
                <div class="p-6 text-center border-b border-gray-700 bg-gray-800/50">
                    <h3 class="text-2xl font-black uppercase tracking-wider <?= $colorClass ?> border-0">
                        <?= $type['name'] ?>
                    </h3>
                    <p class="text-gray-400 text-sm mt-2 min-h-[40px]"><?= $type['description'] ?></p>
                    
                    <ul class="text-left text-sm space-y-2 mt-4 text-gray-300">
                        <?php foreach($type['benefits'] as $benefit): ?>
                            <li class="flex items-start"><i class="fas fa-check <?= $colorClass ?> border-0 mr-2 mt-1"></i> <?= $benefit ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="p-4 bg-gray-900 flex-grow">
                    <p class="text-xs font-bold text-gray-500 uppercase mb-3 ml-1">Selecione os dias:</p>
                    
                    <form id="form-<?= $type['id'] ?>" class="space-y-2">
                        <?php foreach($type['options'] as $index => $option): ?>
                            <label class="block cursor-pointer relative group">
                                <input type="radio" name="option_id" value="<?= $option['id'] ?>" class="peer sr-only plan-radio" <?= $index === 0 ? 'checked' : '' ?>>
                                
                                <div class="flex justify-between items-center p-3 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700 transition <?= $colorClass ?>">
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 rounded-full border border-gray-500 mr-3 peer-checked:bg-current flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-current opacity-0 peer-checked:opacity-100"></div>
                                        </div>
                                        <span class="font-medium text-white"><?= $option['days'] ?> Dias</span>
                                    </div>
                                    <span class="font-bold">R$ <?= number_format($option['price'], 2, ',', '.') ?></span>
                                    
                                    <i class="fas fa-check-circle absolute right-3 opacity-0 transition-opacity check-icon"></i>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </form>
                </div>

                <div class="p-4 bg-gray-800 border-t border-gray-700">
                    <button onclick="submitPlan(<?= $type['id'] ?>)" class="w-full py-3 rounded-xl font-bold uppercase tracking-wider transition shadow-lg <?= $btnClass ?>">
                        Contratar <?= $type['name'] ?>
                    </button>
                </div>

            </div>
            <?php endforeach; ?>

        </div>
    </main>

    <script>
        const BASE_URL = "<?= url('/') ?>";

        async function submitPlan(typeId) {
            // Pega o radio selecionado DENTRO do form específico desse card
            const form = document.getElementById('form-' + typeId);
            const selectedRadio = form.querySelector('input[name="option_id"]:checked');

            if (!selectedRadio) {
                alert('Por favor, selecione uma quantidade de dias.');
                return;
            }

            const optionId = selectedRadio.value;

            if(!confirm('Ir para pagamento seguro?')) return;

            try {
                const response = await fetch(`${BASE_URL}/api/payment/checkout`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ option_id: optionId }) // Envia ID da opção
                });

                const result = await response.json();

                if (result.success && result.redirect_url) {
                    window.location.href = result.redirect_url;
                } else {
                    alert('Erro: ' + (result.message || 'Tente novamente.'));
                }
            } catch (error) {
                console.error(error);
                alert('Erro de conexão.');
            }
        }
    </script>
</body>
</html>