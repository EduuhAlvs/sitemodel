<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - TOP Model</title>
    
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
        body { 
            background-color: #f8fafc; 
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px); 
            background-size: 32px 32px; 
        }
        
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.5;
            animation: float 10s infinite ease-in-out;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(10px, -20px); }
        }

        .glass-nav { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-bottom: 1px solid #e2e8f0; }
        
        .type-radio:checked + div {
            border-color: #db2777;
            background-color: #fff1f2;
            color: #db2777;
            box-shadow: 0 4px 6px -1px rgba(219, 39, 119, 0.1);
        }
        .type-radio:checked + div .check-icon { opacity: 1; }
        .input-error { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
    </style>
</head>
<body class="text-slate-600 antialiased selection:bg-pink-500 selection:text-white flex flex-col min-h-screen relative overflow-x-hidden">

    <div class="blob bg-pink-300 w-96 h-96 rounded-full top-[-100px] left-[-100px] mix-blend-multiply"></div>
    <div class="blob bg-purple-300 w-96 h-96 rounded-full bottom-[-100px] right-[-100px] mix-blend-multiply animation-delay-2000"></div>

    <nav class="fixed top-0 w-full z-50 glass-nav h-16 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 h-full flex justify-between items-center">
            <a href="<?= url('/') ?>" class="flex items-center gap-2 group">
                <div class="bg-slate-900 text-white w-8 h-8 rounded-lg flex items-center justify-center font-display font-bold text-lg group-hover:bg-pink-600 transition">T</div>
                <span class="font-display font-bold text-xl tracking-tight text-slate-900">TOP<span class="text-pink-600">Model</span></span>
            </a>
            
            <div class="hidden md:flex items-center gap-6">
                <a href="<?= url('/') ?>" class="text-sm font-semibold text-slate-900 hover:text-pink-600 transition">Home</a>
                <a href="<?= url('/') ?>?gender=woman" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Mulheres</a>
                <a href="<?= url('/') ?>?gender=trans" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Trans</a>
                <a href="<?= url('/') ?>?gender=couple" class="text-sm font-medium text-slate-500 hover:text-pink-600 transition">Casais</a>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?= url('/login') ?>" class="text-xs font-bold text-slate-500 hover:text-pink-600 transition uppercase tracking-wide mr-2">Entrar</a>
                <a href="<?= url('/register') ?>" class="text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 px-5 py-2.5 rounded-xl transition shadow-lg shadow-slate-200">Criar Perfil</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow pt-32 pb-20 px-4">
        <div class="max-w-lg mx-auto">
            
            <div class="text-center mb-10">
                <span class="inline-block py-1 px-3 rounded-full bg-pink-50 text-pink-600 text-[10px] font-bold uppercase tracking-wider border border-pink-100 mb-3">
                    Junte-se a nós
                </span>
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-slate-900 tracking-tight">
                    Crie sua conta
                </h1>
                <p class="text-slate-500 mt-2 text-sm">Faça parte da plataforma mais exclusiva do Brasil.</p>
            </div>

            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500"></div>

                <div id="error-message" class="hidden bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl mb-6 text-xs font-bold flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-lg"></i> <span id="error-text"></span>
                </div>

                <form id="registerForm" onsubmit="submitRegister(event)" class="space-y-5">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <label class="cursor-pointer relative group">
                            <input type="radio" name="account_type" value="member" class="type-radio sr-only" checked>
                            <div class="border border-slate-200 rounded-2xl p-4 text-center hover:border-pink-300 transition bg-slate-50/50 group-hover:bg-white">
                                <i class="fas fa-user text-xl mb-2 text-slate-400"></i>
                                <span class="block font-display font-bold text-sm text-slate-700">Membro</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Ver Perfis</span>
                                <i class="fas fa-check-circle absolute top-2 right-2 text-pink-500 opacity-0 transition check-icon"></i>
                            </div>
                        </label>

                        <label class="cursor-pointer relative group">
                            <input type="radio" name="account_type" value="model" class="type-radio sr-only">
                            <div class="border border-slate-200 rounded-2xl p-4 text-center hover:border-pink-300 transition bg-slate-50/50 group-hover:bg-white">
                                <i class="fas fa-camera text-xl mb-2 text-slate-400"></i>
                                <span class="block font-display font-bold text-sm text-slate-700">Modelo</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Anunciar</span>
                                <i class="fas fa-check-circle absolute top-2 right-2 text-pink-500 opacity-0 transition check-icon"></i>
                            </div>
                        </label>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Nome Completo</label>
                            <input type="text" id="name" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition" placeholder="Seu nome" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">E-mail</label>
                            <input type="email" id="email" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition" placeholder="seu@email.com" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Nascimento</label>
                            <input type="date" id="birth_date" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition" required>
                            <p id="age-feedback" class="text-xs text-red-500 mt-2 font-bold hidden items-center gap-1 bg-red-50 p-2 rounded-lg border border-red-100">
                                <i class="fas fa-ban"></i> É necessário ser maior de 18 anos.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Senha</label>
                                <input type="password" id="password" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition" placeholder="******" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">Confirmar</label>
                                <input type="password" id="confirm_password" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition" placeholder="******" required>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start pt-2">
                        <div class="flex items-center h-5">
                            <input type="checkbox" id="terms" class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500" required>
                        </div>
                        <div class="ml-3 text-xs">
                            <label for="terms" class="font-medium text-slate-600">
                                Li e concordo com os <a href="#" class="text-pink-600 hover:underline">Termos de Uso</a>.
                            </label>
                            <p class="text-slate-400 mt-0.5">Confirmo ser maior de 18 anos.</p>
                        </div>
                    </div>

                    <button type="submit" id="btn-register" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-4 rounded-xl shadow-lg shadow-slate-200 transition transform hover:-translate-y-0.5 active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span>Criar Conta</span> <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                </form>
            </div>

            <div class="mt-8 text-center">
                <p class="text-sm text-slate-500">
                    Já tem uma conta? 
                    <a href="<?= url('/login') ?>" class="font-bold text-slate-900 hover:text-pink-600 hover:underline transition">Fazer Login</a>
                </p>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-12 mt-auto">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="bg-slate-900 text-white w-6 h-6 rounded flex items-center justify-center font-bold text-xs">T</span>
                <p class="font-display font-bold text-lg text-slate-900">TOP<span class="text-pink-600">Model</span></p>
            </div>
            
            <div class="flex gap-6 text-sm font-medium text-slate-500">
                <a href="#" class="hover:text-pink-600 transition">Termos</a>
                <a href="#" class="hover:text-pink-600 transition">Privacidade</a>
                <a href="#" class="hover:text-pink-600 transition">Contato</a>
            </div>
            
            <p class="text-slate-400 text-xs">© <?= date('Y') ?> TopModel. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const birthDateInput = document.getElementById('birth_date');
        const ageFeedback = document.getElementById('age-feedback');
        const submitBtn = document.getElementById('btn-register');

        function checkAgeValidity() {
            const dateValue = birthDateInput.value;
            if (!dateValue) return;

            const dob = new Date(dateValue);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) { age--; }

            if (age < 18) {
                birthDateInput.classList.add('input-error');
                birthDateInput.classList.remove('focus:border-pink-500', 'focus:ring-pink-500');
                ageFeedback.classList.remove('hidden');
                ageFeedback.classList.add('flex');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> Idade Insuficiente';
                submitBtn.classList.add('bg-slate-300', 'text-slate-500');
                submitBtn.classList.remove('bg-slate-900', 'text-white', 'hover:bg-slate-800');
            } else {
                birthDateInput.classList.remove('input-error');
                birthDateInput.classList.add('focus:border-pink-500', 'focus:ring-pink-500');
                ageFeedback.classList.add('hidden');
                ageFeedback.classList.remove('flex');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Criar Conta</span> <i class="fas fa-arrow-right text-xs"></i>';
                submitBtn.classList.remove('bg-slate-300', 'text-slate-500');
                submitBtn.classList.add('bg-slate-900', 'text-white', 'hover:bg-slate-800');
            }
        }

        birthDateInput.addEventListener('input', checkAgeValidity);
        birthDateInput.addEventListener('change', checkAgeValidity);
        birthDateInput.addEventListener('blur', checkAgeValidity);

        async function submitRegister(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-register');
            const errorDiv = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            
            // Dados
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            const birth_date = document.getElementById('birth_date').value;
            const accountType = document.querySelector('input[name="account_type"]:checked').value;

            // Reset
            errorDiv.classList.add('hidden');
            btn.disabled = true;
            const originalBtnContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

            // Validação final de idade
            const dob = new Date(birth_date);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) { age--; }

            if (age < 18) {
                errorText.innerText = "Idade mínima de 18 anos não atingida.";
                errorDiv.classList.remove('hidden');
                btn.disabled = true;
                btn.innerHTML = originalBtnContent;
                return;
            }

            try {
                const response = await fetch('<?= url('/register') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, password, confirm_password, account_type: accountType, birth_date })
                });
                
                const text = await response.text();
                try {
                    const result = JSON.parse(text);
                    if (result.success) {
                        window.location.href = result.redirect;
                    } else {
                        throw new Error(result.message || "Erro desconhecido");
                    }
                } catch (err) {
                    errorText.innerText = err.message || "Erro no servidor.";
                    errorDiv.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = originalBtnContent;
                }
            } catch (error) {
                errorText.innerText = "Erro de conexão.";
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = originalBtnContent;
            }
        }
    </script>
</body>
</html>