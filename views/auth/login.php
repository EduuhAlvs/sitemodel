<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TOP Model</title>
    
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
                <a href="<?= url('/register') ?>" class="text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 px-5 py-2.5 rounded-xl transition shadow-lg shadow-slate-200">Criar Conta</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center pt-20 px-4">
        <div class="w-full max-w-md">
            
            <div class="text-center mb-8">
                <span class="inline-block py-1 px-3 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wider border border-slate-200 mb-3">
                    Área Restrita
                </span>
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-slate-900 tracking-tight">
                    Bem-vindo de volta
                </h1>
                <p class="text-slate-500 mt-2 text-sm">Acesse sua conta para continuar.</p>
            </div>

            <div class="bg-white/80 backdrop-blur-xl p-8 rounded-3xl shadow-2xl border border-white/50 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-pink-50 to-transparent rounded-bl-full -mr-4 -mt-4"></div>

                <div id="error-message" class="hidden bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl mb-6 text-xs font-bold flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-lg"></i> <span id="error-text"></span>
                </div>
                
                <div id="success-message" class="hidden bg-green-50 border border-green-100 text-green-600 px-4 py-3 rounded-xl mb-6 text-xs font-bold flex items-center gap-2">
                    <i class="fas fa-check-circle text-lg"></i> <span>Login realizado! Redirecionando...</span>
                </div>

                <form onsubmit="submitLogin(event)" class="space-y-5 relative z-10">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5 ml-1">E-mail</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="far fa-envelope"></i></div>
                            <input type="email" id="email" class="w-full pl-10 pr-4 py-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition" placeholder="seu@email.com" required>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1.5 ml-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Senha</label>
                            <a href="#" class="text-xs text-pink-600 hover:text-pink-700 font-bold hover:underline" tabindex="-1">Esqueceu a senha?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><i class="fas fa-lock"></i></div>
                            <input type="password" id="password" class="w-full pl-10 pr-4 py-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition" placeholder="******" required>
                        </div>
                    </div>

                    <button type="submit" id="btn-login" class="w-full bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white font-bold py-4 rounded-xl shadow-xl shadow-pink-500/20 transition transform hover:-translate-y-0.5 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-2">
                        <span>Entrar no Sistema</span> <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                </form>

                <div class="mt-8 text-center pt-6 border-t border-slate-100">
                    <p class="text-sm text-slate-500 font-medium">
                        Não tem uma conta? 
                        <a href="<?= url('/register') ?>" class="text-slate-900 font-bold hover:text-pink-600 hover:underline transition">Criar Grátis</a>
                    </p>
                </div>
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
        async function submitLogin(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btn-login');
            const errorDiv = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            const successDiv = document.getElementById('success-message');
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');
            btn.disabled = true;
            const originalBtnContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Autenticando...';

            try {
                // CORREÇÃO: Usando a URL '/login' que corresponde à sua rota POST
                const response = await fetch('<?= url('/login') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                
                const text = await response.text();
                
                try {
                    const result = JSON.parse(text);
                    if (result.success) {
                        successDiv.classList.remove('hidden');
                        successDiv.classList.add('flex');
                        setTimeout(() => { window.location.href = result.redirect; }, 1000);
                    } else {
                        throw new Error(result.message || "Credenciais inválidas");
                    }
                } catch (err) {
                    // Se der erro de JSON, mostra a mensagem crua no console e alerta amigável
                    console.error("Resposta não-JSON:", text);
                    errorText.innerText = err.message || "Erro inesperado do servidor.";
                    errorDiv.classList.remove('hidden');
                    errorDiv.classList.add('flex');
                    btn.disabled = false;
                    btn.innerHTML = originalBtnContent;
                }

            } catch (error) {
                console.error(error);
                errorText.innerText = "Erro de conexão. Verifique sua internet.";
                errorDiv.classList.remove('hidden');
                errorDiv.classList.add('flex');
                btn.disabled = false;
                btn.innerHTML = originalBtnContent;
            }
        }
    </script>
</body>
</html>