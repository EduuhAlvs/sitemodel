<?php
// Busca assinatura ativa para exibir
use App\Models\Subscription;
$activeSub = Subscription::getActiveByProfile($profile['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Modelo - TOP Model</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script>
    const BASE_URL = "<?= url('/') ?>"; // O PHP imprime a URL base correta aqui
    </script>
</head>
<body class="bg-gray-100 text-gray-800">

    <header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
        <h1 class="text-xl font-bold text-pink-600">Meu Painel</h1>
        <div class="flex items-center space-x-4">
            <a href="<?= url('/planos') ?>" class="bg-gold text-dark px-3 py-1 rounded text-sm font-bold hover:bg-yellow-500 animate-pulse">
                <i class="fas fa-crown mr-1"></i> Ser VIP
            </a>
            <a href="<?= url('/logout') ?>" class="text-sm text-gray-500 hover:text-red-500">Sair</a>
        </div>
    </div>
</header>
<script>
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('payment') === 'success'){
            alert("PARABÉNS! 🚀\n\nSeu plano foi ativado com sucesso.\nVocê agora tem destaque VIP!");
            // Limpa a URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>

    <div class="max-w-4xl mx-auto px-4 py-8">
        
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-lg shadow-lg p-6 mb-8 text-white flex justify-between items-center border border-gray-700">
            <div>
                <p class="text-gray-400 text-sm uppercase tracking-wider font-bold">Plano Atual</p>
                <?php if($activeSub): ?>
                    <h2 class="text-3xl font-bold text-gold mt-1">
                        <i class="fas fa-crown mr-2"></i> <?= $activeSub['plan_name'] ?>
                    </h2>
                    <p class="text-sm text-gray-300 mt-2">
                        Expira em: <span class="font-bold text-white"><?= date('d/m/Y', strtotime($activeSub['expires_at'])) ?></span>
                    </p>
                <?php else: ?>
                    <h2 class="text-3xl font-bold text-gray-500 mt-1">Gratuito</h2>
                    <p class="text-sm text-gray-400 mt-2">Seu perfil tem visibilidade limitada.</p>
                <?php endif; ?>
            </div>
            
            <div>
                <?php if(!$activeSub): ?>
                    <a href="<?= url('/planos') ?>" class="bg-gold hover:bg-yellow-500 text-gray-900 font-bold py-3 px-6 rounded-full shadow-lg transform transition hover:scale-105">
                        VIRAR VIP AGORA
                    </a>
                <?php else: ?>
                    <div class="text-right">
                        <span class="bg-green-500 text-white text-xs font-bold px-2 py-1 rounded">ATIVO</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <main class="max-w-4xl mx-auto px-4 py-6 space-y-6">

        <div class="bg-white rounded-lg shadow p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-user mr-2 text-pink-500"></i>Biografia</h2>
                <span id="msg-bio" class="text-xs font-bold transition-opacity opacity-0 text-green-600">Salvo!</span>
            </div>
            
            <form id="form-bio" onsubmit="saveCard(event, 'bio')" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600 mb-1">Nome de Exibição</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($profile['display_name']) ?>" 
                        class="w-full border border-gray-300 rounded p-2 focus:border-pink-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Eu sou</label>
                    <select name="gender" class="w-full border border-gray-300 rounded p-2 bg-white">
                        <option value="woman" <?= $profile['gender'] == 'woman' ? 'selected' : '' ?>>Mulher</option>
                        <option value="man" <?= $profile['gender'] == 'man' ? 'selected' : '' ?>>Homem</option>
                        <option value="trans" <?= $profile['gender'] == 'trans' ? 'selected' : '' ?>>Transsexual</option>
                        <option value="couple" <?= $profile['gender'] == 'couple' ? 'selected' : '' ?>>Casal</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Orientação Sexual</label>
                    <select name="orientation" class="w-full border border-gray-300 rounded p-2 bg-white">
                        <option value="hetero" <?= $profile['orientation'] == 'hetero' ? 'selected' : '' ?>>Heterossexual</option>
                        <option value="bi" <?= $profile['orientation'] == 'bi' ? 'selected' : '' ?>>Bissexual</option>
                        <option value="homo" <?= $profile['orientation'] == 'homo' ? 'selected' : '' ?>>Homossexual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Data de Nascimento</label>
                    <input type="date" name="birth_date" value="<?= $profile['birth_date'] ?>"
                        class="w-full border border-gray-300 rounded p-2">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Nacionalidade</label>
                    <input type="text" name="nationality" value="<?= $profile['nationality'] ?>" placeholder="Ex: Brasileira"
                        class="w-full border border-gray-300 rounded p-2">
                </div>

                <div class="md:col-span-2 flex justify-end mt-4">
                    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition">
                        Salvar Biografia
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-magic mr-2 text-pink-500"></i>Aparência</h2>
                <span id="msg-appearance" class="text-xs font-bold transition-opacity opacity-0 text-green-600">Salvo!</span>
            </div>
            
            <form id="form-appearance" onsubmit="saveCard(event, 'appearance')" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                
                <div class="col-span-1">
                    <label class="block text-xs text-gray-600 mb-1">Cabelos</label>
                    <select name="hair_color" class="w-full border border-gray-300 rounded p-2 text-sm bg-white">
                        <option value="loira" <?= $profile['hair_color'] == 'loira' ? 'selected' : '' ?>>Loira</option>
                        <option value="morena" <?= $profile['hair_color'] == 'morena' ? 'selected' : '' ?>>Morena</option>
                        <option value="ruiva" <?= $profile['hair_color'] == 'ruiva' ? 'selected' : '' ?>>Ruiva</option>
                        <option value="preto" <?= $profile['hair_color'] == 'preto' ? 'selected' : '' ?>>Preto</option>
                    </select>
                </div>

                <div class="col-span-1">
                    <label class="block text-xs text-gray-600 mb-1">Olhos</label>
                    <select name="eye_color" class="w-full border border-gray-300 rounded p-2 text-sm bg-white">
                        <option value="castanhos" <?= $profile['eye_color'] == 'castanhos' ? 'selected' : '' ?>>Castanhos</option>
                        <option value="verdes" <?= $profile['eye_color'] == 'verdes' ? 'selected' : '' ?>>Verdes</option>
                        <option value="azuis" <?= $profile['eye_color'] == 'azuis' ? 'selected' : '' ?>>Azuis</option>
                    </select>
                </div>

                <div class="col-span-1">
                    <label class="block text-xs text-gray-600 mb-1">Altura (cm)</label>
                    <input type="number" name="height_cm" value="<?= $profile['height_cm'] ?>" placeholder="170"
                        class="w-full border border-gray-300 rounded p-2 text-sm">
                </div>

                <div class="col-span-1">
                    <label class="block text-xs text-gray-600 mb-1">Peso (kg)</label>
                    <input type="number" name="weight_kg" value="<?= $profile['weight_kg'] ?>" placeholder="60"
                        class="w-full border border-gray-300 rounded p-2 text-sm">
                </div>

                <div class="col-span-2 mt-2">
                    <span class="block text-xs text-gray-600 mb-1">Tem Tatuagem?</span>
                    <div class="flex space-x-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="tattoos" value="1" <?= $profile['tattoos'] ? 'checked' : '' ?> class="mr-2 text-pink-600 focus:ring-pink-500">
                            <span class="text-sm">Sim</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="tattoos" value="0" <?= !$profile['tattoos'] ? 'checked' : '' ?> class="mr-2 text-pink-600 focus:ring-pink-500">
                            <span class="text-sm">Não</span>
                        </label>
                    </div>
                </div>

                <div class="col-span-2 md:col-span-4 flex justify-end mt-4">
                    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition">
                        Salvar Aparência
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-camera mr-2 text-pink-500"></i>Minhas Fotos</h2>
            </div>

            <div class="mb-6">
                <label class="flex flex-col items-center px-4 py-6 bg-white text-pink-500 rounded-lg shadow-lg tracking-wide uppercase border border-blue cursor-pointer hover:bg-pink-50 transition">
                    <i class="fas fa-cloud-upload-alt text-3xl"></i>
                    <span class="mt-2 text-base leading-normal">Selecione uma foto</span>
                    <input type='file' class="hidden" id="photoInput" accept="image/*" onchange="uploadPhoto(this)" />
                </label>
                <p id="uploadStatus" class="text-center text-sm text-gray-500 mt-2"></p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="photoGrid">
                <?php if (!empty($photos)): ?>
                    <?php foreach($photos as $photo): ?>
                        <div class="relative group" id="photo-<?= $photo['id'] ?>">
                            <img src="<?= url('/' . $photo['file_path']) ?>" class="w-full h-32 object-cover rounded shadow">
                            <button onclick="deletePhoto(<?= $photo['id'] ?>)" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow">
                                &times;
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="col-span-4 text-center text-gray-400" id="noPhotosMsg">Nenhuma foto adicionada ainda.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-heart mr-2 text-pink-500"></i>Sobre Mim</h2>
                <span id="msg-about" class="text-xs font-bold transition-opacity opacity-0 text-green-600">Salvo!</span>
            </div>
            
            <form id="form-about" onsubmit="saveCard(event, 'about')" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600 mb-1">Descrição do Perfil</label>
                    <textarea name="bio" rows="4" placeholder="Escreva sobre você..." 
                        class="w-full border border-gray-300 rounded p-2 focus:border-pink-500 focus:outline-none"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">Emojis permitidos 💋. HTML não permitido.</p>
                </div>

                <div>
                    <span class="block text-sm text-gray-600 mb-1">Fuma?</span>
                    <div class="flex space-x-3">
                        <label class="inline-flex items-center">
                            <input type="radio" name="smoker" value="no" <?= ($profile['smoker'] == 'no') ? 'checked' : '' ?> class="text-pink-600 focus:ring-pink-500">
                            <span class="ml-2 text-sm">Não</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="smoker" value="yes" <?= ($profile['smoker'] == 'yes') ? 'checked' : '' ?> class="text-pink-600 focus:ring-pink-500">
                            <span class="ml-2 text-sm">Sim</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="smoker" value="occasionally" <?= ($profile['smoker'] == 'occasionally') ? 'checked' : '' ?> class="text-pink-600 focus:ring-pink-500">
                            <span class="ml-2 text-sm">Às vezes</span>
                        </label>
                    </div>
                </div>

                <div>
                    <span class="block text-sm text-gray-600 mb-1">Bebe Álcool?</span>
                    <div class="flex space-x-3">
                        <label class="inline-flex items-center">
                            <input type="radio" name="drinker" value="no" <?= ($profile['drinker'] == 'no') ? 'checked' : '' ?> class="text-pink-600 focus:ring-pink-500">
                            <span class="ml-2 text-sm">Não</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="drinker" value="yes" <?= ($profile['drinker'] == 'yes') ? 'checked' : '' ?> class="text-pink-600 focus:ring-pink-500">
                            <span class="ml-2 text-sm">Sim</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="drinker" value="occasionally" <?= ($profile['drinker'] == 'occasionally') ? 'checked' : '' ?> class="text-pink-600 focus:ring-pink-500">
                            <span class="ml-2 text-sm">Socialmente</span>
                        </label>
                    </div>
                </div>

                <div class="md:col-span-2 flex justify-end mt-4">
                    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition">
                        Salvar Sobre Mim
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-phone mr-2 text-pink-500"></i>Contato</h2>
                <span id="msg-contact" class="text-xs font-bold transition-opacity opacity-0 text-green-600">Salvo!</span>
            </div>
            
            <form id="form-contact" onsubmit="saveCard(event, 'contact')" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600 mb-1">Número de Telefone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone']) ?>" placeholder="+55 (11) 99999-9999"
                        class="w-full border border-gray-300 rounded p-2 focus:border-pink-500 focus:outline-none">
                </div>

                <div>
                    <span class="block text-sm text-gray-600 mb-2">Apps Disponíveis</span>
                    <div class="flex flex-col space-y-2">
                        <label class="inline-flex items-center">
                            <input type="hidden" name="whatsapp_enabled" value="0">
                            <input type="checkbox" name="whatsapp_enabled" value="1" <?= $profile['whatsapp_enabled'] ? 'checked' : '' ?> class="form-checkbox text-pink-600 h-5 w-5">
                            <span class="ml-2 text-sm"><i class="fab fa-whatsapp text-green-500 mr-1"></i> Atender via WhatsApp</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="hidden" name="viber_enabled" value="0">
                            <input type="checkbox" name="viber_enabled" value="1" <?= $profile['viber_enabled'] ? 'checked' : '' ?> class="form-checkbox text-purple-600 h-5 w-5">
                            <span class="ml-2 text-sm"><i class="fab fa-viber text-purple-500 mr-1"></i> Atender via Viber</span>
                        </label>
                    </div>
                </div>

                <div>
                    <span class="block text-sm text-gray-600 mb-2">Preferência</span>
                    <div class="flex flex-col space-y-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="contact_preference" value="sms_only" <?= ($profile['contact_preference'] == 'sms_only') ? 'checked' : '' ?> class="text-pink-600">
                            <span class="ml-2 text-sm">Apenas SMS/Zap</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="contact_preference" value="call_only" <?= ($profile['contact_preference'] == 'call_only') ? 'checked' : '' ?> class="text-pink-600">
                            <span class="ml-2 text-sm">Apenas Ligações</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="contact_preference" value="sms_call" <?= ($profile['contact_preference'] == 'sms_call') ? 'checked' : '' ?> class="text-pink-600">
                            <span class="ml-2 text-sm">Ambos</span>
                        </label>
                    </div>
                </div>

                <div class="md:col-span-2 flex justify-end mt-4">
                    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition">
                        Salvar Contato
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-map-marker-alt mr-2 text-pink-500"></i>Locais de Trabalho</h2>
                <span id="msg-service" class="text-xs font-bold transition-opacity opacity-0 text-green-600">Salvo!</span>
            </div>

            <div class="mb-6">
                <label class="block text-sm text-gray-600 mb-1">Adicionar Cidade</label>
                <div class="relative">
                    <input type="text" id="citySearch" placeholder="Digite o nome da cidade (min 3 letras)..." 
                           class="w-full border border-gray-300 rounded p-2" onkeyup="searchCity(this.value)">
                    <div id="cityResults" class="absolute z-10 w-full bg-white shadow-lg border rounded mt-1 hidden max-h-40 overflow-y-auto"></div>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-sm font-bold text-gray-600 mb-2">Minhas Cidades</p>
                <div id="myCitiesList" class="space-y-2">
                    <?php foreach($locations as $loc): ?>
                        <div class="flex items-center justify-between bg-gray-50 p-2 rounded border" id="city-row-<?= $loc['id'] ?>">
                            <span class="text-gray-700"><?= $loc['name'] ?></span>
                            <div class="flex items-center space-x-3">
                                <label class="text-xs flex items-center cursor-pointer">
                                    <input type="radio" name="base_city" onchange="setBaseCity(<?= $loc['id'] ?>)" <?= $loc['is_base_city'] ? 'checked' : '' ?> class="text-pink-600 mr-1">
                                    Cidade Base
                                </label>
                                <button onclick="removeCity(<?= $loc['id'] ?>)" class="text-red-500 hover:text-red-700 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <form id="form-service" onsubmit="saveCard(event, 'service_details')" class="border-t pt-4">
                 <input type="hidden" name="force_submit" value="1">

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="inline-flex items-center mb-2">
                            <input type="checkbox" name="incall_available" value="1" <?= $profile['incall_available'] ? 'checked' : '' ?> class="form-checkbox text-pink-600 h-5 w-5">
                            <span class="ml-2 font-bold text-gray-700">Disponível para Incall</span>
                        </label>
                        
                        <div class="ml-6 space-y-1 text-sm text-gray-600">
                            <label class="block"><input type="checkbox" name="details[incall_private]" value="1" <?= ($profile['service_details']['incall_private'] ?? 0) ? 'checked' : '' ?>> Apartamento Privativo</label>
                            <label class="block"><input type="checkbox" name="details[incall_hotel]" value="1" <?= ($profile['service_details']['incall_hotel'] ?? 0) ? 'checked' : '' ?>> Quarto de Hotel</label>
                            
                            <div class="pl-4 mt-1">
                                <span class="text-xs block mb-1">Categoria do Hotel:</span>
                                <select name="details[hotel_stars]" class="border rounded p-1 text-xs">
                                    <option value="1" <?= ($profile['service_details']['hotel_stars'] ?? '') == '1' ? 'selected' : '' ?>>⭐ 1 Estrela</option>
                                    <option value="3" <?= ($profile['service_details']['hotel_stars'] ?? '') == '3' ? 'selected' : '' ?>>⭐⭐⭐ 3 Estrelas</option>
                                    <option value="5" <?= ($profile['service_details']['hotel_stars'] ?? '') == '5' ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ 5 Estrelas</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="inline-flex items-center mb-2">
                            <input type="checkbox" name="outcall_available" value="1" <?= $profile['outcall_available'] ? 'checked' : '' ?> class="form-checkbox text-pink-600 h-5 w-5">
                            <span class="ml-2 font-bold text-gray-700">Disponível para Outcall</span>
                        </label>

                        <div class="ml-6 space-y-1 text-sm text-gray-600">
                            <label class="block"><input type="checkbox" name="details[outcall_hotel]" value="1" <?= ($profile['service_details']['outcall_hotel'] ?? 0) ? 'checked' : '' ?>> Visitas a Hotéis</label>
                            <label class="block"><input type="checkbox" name="details[outcall_home]" value="1" <?= ($profile['service_details']['outcall_home'] ?? 0) ? 'checked' : '' ?>> Visitas Domiciliares</label>
                            <label class="block"><input type="checkbox" name="details[outcall_events]" value="1" <?= ($profile['service_details']['outcall_events'] ?? 0) ? 'checked' : '' ?>> Eventos / Jantares</label>
                        </div>
                    </div>
                 </div>

                 <div class="flex justify-end mt-4">
                    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition">
                        Salvar Locais
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-clock mr-2 text-pink-500"></i>Horário de Atendimento</h2>
                <span id="msg-schedule" class="text-xs font-bold transition-opacity opacity-0 text-green-600">Salvo!</span>
            </div>

            <form id="form-schedule" onsubmit="saveSchedule(event)">
                
                <div class="mb-6 space-y-3">
                    <label class="flex items-center p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="mode" value="24_7" onchange="toggleSchedule(this.value)" 
                            <?= $profile['is_24_7'] ? 'checked' : '' ?> class="text-pink-600 h-5 w-5">
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-700">Disponível 24h / 7 dias</span>
                            <span class="text-xs text-gray-500">Seu perfil aparecerá sempre como "Aberto".</span>
                        </div>
                    </label>

                    <label class="flex items-center p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="mode" value="night" onchange="toggleSchedule(this.value)"
                            <?= $profile['show_as_night'] ? 'checked' : '' ?> class="text-pink-600 h-5 w-5">
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-700">Acompanhante Noturna</span>
                            <span class="text-xs text-gray-500">Mostre-me disponível apenas à noite (Ícone de Lua).</span>
                        </div>
                    </label>

                    <label class="flex items-center p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="mode" value="custom" onchange="toggleSchedule(this.value)"
                            <?= (!$profile['is_24_7'] && !$profile['show_as_night']) ? 'checked' : '' ?> class="text-pink-600 h-5 w-5">
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-700">Definir Horários Específicos</span>
                            <span class="text-xs text-gray-500">Configure os dias e horas exatas abaixo.</span>
                        </div>
                    </label>
                </div>

                <?php 
                    // Prepara os horários padrão caso venha nulo do banco
                    $hours = json_decode($profile['working_hours'] ?? '[]', true);
                    $days = ['seg' => 'Segunda', 'ter' => 'Terça', 'qua' => 'Quarta', 'qui' => 'Quinta', 'sex' => 'Sexta', 'sab' => 'Sábado', 'dom' => 'Domingo'];
                ?>
                <div id="schedule-table" class="<?= ($profile['is_24_7'] || $profile['show_as_night']) ? 'hidden' : '' ?> border-t pt-4">
                    <div class="grid gap-2">
                        <?php foreach($days as $key => $label): 
                            $isActive = isset($hours[$key]['active']) && $hours[$key]['active'] == '1';
                            $start = $hours[$key]['start'] ?? '09:00';
                            $end = $hours[$key]['end'] ?? '22:00';
                        ?>
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                            <label class="flex items-center cursor-pointer w-32">
                                <input type="checkbox" name="hours[<?= $key ?>][active]" value="1" <?= $isActive ? 'checked' : '' ?> 
                                    class="text-pink-600 h-4 w-4" onchange="toggleDay('<?= $key ?>')">
                                <span class="ml-2 text-sm font-medium text-gray-700"><?= $label ?></span>
                            </label>
                            
                            <div class="flex space-x-2" id="time-<?= $key ?>" style="<?= $isActive ? '' : 'opacity: 0.5; pointer-events: none;' ?>">
                                <input type="time" name="hours[<?= $key ?>][start]" value="<?= $start ?>" class="border rounded p-1 text-sm">
                                <span class="text-gray-400">-</span>
                                <input type="time" name="hours[<?= $key ?>][end]" value="<?= $end ?>" class="border rounded p-1 text-sm">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 transition">
                        Salvar Horários
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-language mr-2 text-pink-500"></i>Línguas</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 items-end">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Idioma</label>
                    <select id="langSelect" class="w-full border border-gray-300 rounded p-2">
                        <option value="Português">Português</option>
                        <option value="Inglês">Inglês</option>
                        <option value="Francês">Francês</option>
                        <option value="Espanhol">Espanhol</option>
                        <option value="Italiano">Italiano</option>
                        <option value="Alemão">Alemão</option>
                        <option value="Russo">Russo</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Nível</label>
                    <select id="langLevel" class="w-full border border-gray-300 rounded p-2">
                        <option value="basic">Básico</option>
                        <option value="medium" selected>Médio</option>
                        <option value="good">Bom</option>
                        <option value="native">Língua Materna</option>
                    </select>
                </div>

                <div>
                    <button type="button" onclick="addLanguage()" class="w-full bg-pink-600 text-white p-2 rounded hover:bg-pink-700">
                        <i class="fas fa-plus mr-1"></i> Adicionar
                    </button>
                </div>
            </div>

            <div id="langList" class="space-y-2">
                <?php if(isset($languages)): foreach($languages as $lang): ?>
                    <div class="flex items-center justify-between bg-gray-50 p-2 rounded border" id="lang-row-<?= $lang['id'] ?>">
                        <div>
                            <span class="font-bold text-gray-700"><?= $lang['language'] ?></span>
                            <span class="text-xs text-gray-500 ml-2 uppercase border px-1 rounded"><?= $lang['level'] ?></span>
                        </div>
                        <button onclick="removeLanguage(<?= $lang['id'] ?>)" class="text-red-500 hover:text-red-700 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    </main>

    <script>
    // 1. Configuração da URL Base (Evita erros de caminho)
    

    // ------------------------------------------------------------------
    // FUNÇÃO 1: SALVAR CARDS (Bio, Aparência, Contato, etc)
    // ------------------------------------------------------------------
    async function saveCard(event, sectionName) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const jsonData = {};
        
        // Converte FormData para JSON (trata checkboxes e radios)
        formData.forEach((value, key) => { 
            // Lógica especial para arrays (ex: details[incall])
            if(key.includes('[')) {
                // Simplificação: apenas mantém o valor cru para envio, o PHP processa
            }
            jsonData[key] = value 
        });
        
        // Tratamento especial para checkboxes do formulário de locais
        // Se for o form de serviço, precisamos garantir a estrutura do JSON
        if (sectionName === 'service_details') {
            // Recria o objeto json para garantir a estrutura correta dos detalhes
            // O PHP espera 'details' como array, mas aqui simplificamos enviando tudo
            // O backend já filtra, então mandamos o form data puro no body é mais seguro
        }

        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerText;
        btn.innerText = "Salvando...";
        btn.disabled = true;

        try {
            const response = await fetch(`${BASE_URL}/api/perfil/save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    section: sectionName,
                    data: Object.fromEntries(formData) // Conversão moderna para JSON
                })
            });

            const text = await response.text();
            try {
                const result = JSON.parse(text);
                if (result.success) {
                    const msg = document.getElementById('msg-' + sectionName);
                    if(msg) {
                        msg.classList.remove('opacity-0');
                        setTimeout(() => msg.classList.add('opacity-0'), 3000);
                    }
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (e) {
                console.error("Erro PHP:", text);
                alert("Erro no servidor. Verifique o console.");
            }
        } catch (error) {
            console.error(error);
            alert('Erro de conexão.');
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }

    // ------------------------------------------------------------------
    // FUNÇÃO 2: UPLOAD E DELETE DE FOTOS
    // ------------------------------------------------------------------
    async function uploadPhoto(input) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('photo', input.files[0]);
            
            const status = document.getElementById('uploadStatus');
            status.innerText = "Enviando...";

            try {
                const response = await fetch(`${BASE_URL}/api/photos/upload`, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    status.innerText = "Sucesso!";
                    addPhotoToGrid(result.photo);
                    document.getElementById('noPhotosMsg')?.remove();
                } else {
                    status.innerText = "Erro: " + result.message;
                }
            } catch (error) {
                console.error(error);
                status.innerText = "Erro ao enviar.";
            }
            input.value = ''; 
        }
    }

    function addPhotoToGrid(photo) {
        const grid = document.getElementById('photoGrid');
        const div = document.createElement('div');
        div.className = 'relative group';
        div.id = 'photo-' + photo.id;
        div.innerHTML = `
            <img src="${photo.url}" class="w-full h-32 object-cover rounded shadow">
            <button onclick="deletePhoto(${photo.id})" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow">
                &times;
            </button>
        `;
        grid.prepend(div);
    }

    async function deletePhoto(id) {
        if(!confirm('Apagar esta foto?')) return;
        try {
            await fetch(`${BASE_URL}/api/photos/delete`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            });
            document.getElementById('photo-' + id).remove();
        } catch(e) { console.error(e); }
    }

    // ------------------------------------------------------------------
    // FUNÇÃO 3: CIDADES E LOCAIS (A QUE ESTAVA FALTANDO)
    // ------------------------------------------------------------------
    async function searchCity(term) {
        const resultsDiv = document.getElementById('cityResults');
        
        if (term.length < 3) {
            resultsDiv.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`${BASE_URL}/api/locations/search?q=${term}`);
            const cities = await response.json();

            resultsDiv.innerHTML = '';
            if (cities.length > 0) {
                resultsDiv.classList.remove('hidden');
                cities.forEach(city => {
                    const div = document.createElement('div');
                    div.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b text-sm';
                    div.innerText = `${city.name} - ${city.country}`;
                    div.onclick = () => addCity(city.id, city.name);
                    resultsDiv.appendChild(div);
                });
            } else {
                resultsDiv.classList.add('hidden');
            }
        } catch (e) {
            console.error("Erro na busca de cidades:", e);
        }
    }

    async function addCity(id, name) {
        // Esconde o menu
        document.getElementById('cityResults').classList.add('hidden');
        document.getElementById('citySearch').value = '';

        // Salva no banco
        await manageLocation('add', id);
        
        // Adiciona visualmente na lista
        const list = document.getElementById('myCitiesList');
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between bg-gray-50 p-2 rounded border';
        row.id = `city-row-${id}`;
        row.innerHTML = `
            <span class="text-gray-700">${name}</span>
            <div class="flex items-center space-x-3">
                <label class="text-xs flex items-center cursor-pointer">
                    <input type="radio" name="base_city" onchange="setBaseCity(${id})" class="text-pink-600 mr-1">
                    Cidade Base
                </label>
                <button onclick="removeCity(${id})" class="text-red-500 hover:text-red-700 text-sm"><i class="fas fa-trash"></i></button>
            </div>
        `;
        list.appendChild(row);
    }

    async function removeCity(id) {
        if(!confirm('Remover cidade?')) return;
        await manageLocation('remove', id);
        document.getElementById(`city-row-${id}`).remove();
    }

    async function setBaseCity(id) {
        await manageLocation('set_base', id);
        alert('Cidade base atualizada!');
    }

    async function manageLocation(action, cityId) {
        await fetch(`${BASE_URL}/api/locations/manage`, {
            method: 'POST',
            body: JSON.stringify({ action, city_id: cityId })
        });
    }

    async function addLanguage() {
        const lang = document.getElementById('langSelect').value;
        const level = document.getElementById('langLevel').value;
        
        const response = await fetch(`${BASE_URL}/api/languages/manage`, {
            method: 'POST',
            body: JSON.stringify({ action: 'add', language: lang, level: level })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Recarrega a página para atualizar a lista (mais simples que recriar o DOM na mão com ID)
            // Ou se quiser fazer dinâmico igual Cidades, precisaria retornar o ID inserido do PHP
            location.reload(); 
        } else {
            alert('Não foi possível adicionar (talvez já exista na lista).');
        }
    }

    async function removeLanguage(id) {
        if(!confirm('Remover idioma?')) return;
        
        await fetch(`${BASE_URL}/api/languages/manage`, {
            method: 'POST',
            body: JSON.stringify({ action: 'remove', id: id })
        });
        
        document.getElementById(`lang-row-${id}`).remove();
    }

    // --- LÓGICA DE HORÁRIOS ---

    function toggleSchedule(mode) {
        const table = document.getElementById('schedule-table');
        if (mode === 'custom') {
            table.classList.remove('hidden');
        } else {
            table.classList.add('hidden');
        }
    }

    function toggleDay(key) {
        const timeDiv = document.getElementById('time-' + key);
        const checkbox = document.querySelector(`input[name="hours[${key}][active]"]`);
        
        if (checkbox.checked) {
            timeDiv.style.opacity = '1';
            timeDiv.style.pointerEvents = 'auto';
        } else {
            timeDiv.style.opacity = '0.5';
            timeDiv.style.pointerEvents = 'none';
        }
    }

    async function saveSchedule(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const jsonData = {
            is_24_7: 0,
            show_as_night: 0,
            working_hours: {}
        };

        // 1. Processa o Modo Selecionado
        const mode = formData.get('mode');
        if (mode === '24_7') jsonData.is_24_7 = 1;
        if (mode === 'night') jsonData.show_as_night = 1;

        // 2. Processa a Tabela de Horas
        // Transforma hours[seg][start] em objeto JSON estruturado
        const days = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
        days.forEach(day => {
            if (formData.get(`hours[${day}][active]`)) {
                jsonData.working_hours[day] = {
                    active: 1,
                    start: formData.get(`hours[${day}][start]`),
                    end: formData.get(`hours[${day}][end]`)
                };
            }
        });
        
        // Converte o objeto working_hours para string JSON antes de enviar (para o PHP salvar no banco)
        // Mas como nosso ProfileController.update espera um array, vamos passar o JSON stringificado
        // OU melhor: Passamos o array e o Controller/Model deveria tratar.
        // Dado nosso Model genérico, o ideal é enviar working_hours como string JSON.
        jsonData.working_hours = JSON.stringify(jsonData.working_hours);

        // Reutiliza a função saveCard genérica? Não, porque a estrutura aqui é customizada.
        // Vamos fazer o fetch manual aqui para garantir.
        
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerText;
        btn.innerText = "Salvando...";
        btn.disabled = true;

        try {
            const response = await fetch(`${BASE_URL}/api/perfil/save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    section: 'schedule',
                    data: jsonData
                })
            });
            
            const result = await response.json();
            if (result.success) {
                const msg = document.getElementById('msg-schedule');
                msg.classList.remove('opacity-0');
                setTimeout(() => msg.classList.add('opacity-0'), 3000);
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (e) {
            console.error(e);
            alert('Erro ao salvar horários.');
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }
</script>
</body>
</html>
</body>
</html>