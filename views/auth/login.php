<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TOP Model</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen px-4">

    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md transform transition-all hover:scale-[1.01]">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-pink-100 mb-4 text-pink-600">
                <i class="fas fa-user-lock text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Bem-vindo de volta!</h1>
            <p class="text-gray-500 text-sm mt-2">Acesse sua conta para continuar</p>
        </div>

        <div id="success-msg" class="hidden flex items-center bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded shadow-sm mb-6 relative" role="alert">
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <div>
                <p class="font-bold">Sucesso!</p>
                <p class="text-sm">Conta criada. Faça login para começar.</p>
            </div>
        </div>
        
        <div id="error-msg" class="hidden flex items-center bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-sm mb-6 relative">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span id="error-text" class="text-sm font-medium"></span>
        </div>

        <form onsubmit="handleLogin(event)">
            <div class="space-y-5">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 ml-1">E-mail</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" class="w-full pl-10 pr-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-pink-500 focus:bg-white focus:outline-none transition shadow-sm" required placeholder="seu@email.com">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 ml-1">Senha</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" class="w-full pl-10 pr-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-pink-500 focus:bg-white focus:outline-none transition shadow-sm" required placeholder="Sua senha">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-between mt-6 mb-6">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="form-checkbox h-4 w-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                    <span class="ml-2 text-sm text-gray-600">Lembrar de mim</span>
                </label>
                <a href="#" class="text-sm text-pink-600 hover:text-pink-800 font-semibold">Esqueceu a senha?</a>
            </div>
            
            <button type="submit" id="btn-submit" class="w-full bg-gradient-to-r from-pink-600 to-pink-500 hover:from-pink-700 hover:to-pink-600 text-white font-bold py-3 px-4 rounded-lg shadow-lg transform transition hover:scale-[1.02] active:scale-95 flex justify-center items-center">
                <span>Entrar no Sistema</span>
                <i class="fas fa-arrow-right ml-2"></i>
            </button>
            
            <div class="mt-8 text-center border-t border-gray-100 pt-6">
                <p class="text-sm text-gray-500">Ainda não tem uma conta?</p>
                <a href="<?= url('/register') ?>" class="inline-block mt-2 text-pink-600 font-bold hover:underline hover:text-pink-800 transition">
                    Criar conta gratuitamente
                </a>
            </div>
        </form>
    </div>

    <script>
        // 1. Verifica se veio do registro (URL contém ?registered=1)
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('registered')) {
                const successMsg = document.getElementById('success-msg');
                successMsg.classList.remove('hidden');
                successMsg.classList.add('flex');
                
                // Remove o parametro da URL para não ficar aparecendo se der refresh
                // window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        // 2. Lógica de Login
        async function handleLogin(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error-msg');
            const errorText = document.getElementById('error-text');
            const btn = document.getElementById('btn-submit');
            const successMsg = document.getElementById('success-msg');
            
            // Reseta estados
            errorDiv.classList.add('hidden');
            successMsg.classList.add('hidden'); // Esconde msg de sucesso se tentar logar e errar
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Autenticando...';
            
            try {
                const response = await fetch('<?= url('/login') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                
                const text = await response.text();
                
                try {
                    const result = JSON.parse(text);
                    if (result.success) {
                        window.location.href = result.redirect;
                    } else {
                        errorText.innerText = result.message;
                        errorDiv.classList.remove('hidden');
                        errorDiv.classList.add('flex');
                        btn.disabled = false;
                        btn.innerHTML = '<span>Entrar no Sistema</span><i class="fas fa-arrow-right ml-2"></i>';
                    }
                } catch (err) {
                    errorText.innerText = "Erro no servidor. Tente novamente.";
                    errorDiv.classList.remove('hidden');
                    errorDiv.classList.add('flex');
                    btn.disabled = false;
                    btn.innerHTML = '<span>Entrar no Sistema</span><i class="fas fa-arrow-right ml-2"></i>';
                }

            } catch (error) {
                console.error(error);
                errorText.innerText = "Erro de conexão.";
                errorDiv.classList.remove('hidden');
                errorDiv.classList.add('flex');
                btn.disabled = false;
                btn.innerHTML = '<span>Entrar no Sistema</span><i class="fas fa-arrow-right ml-2"></i>';
            }
        }
    </script>
</body>
</html>