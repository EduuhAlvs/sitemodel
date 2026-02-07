<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - TOP Model</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        /* Estilo para esconder o radio button padrão e estilizar o label */
        .type-radio:checked + div {
            border-color: #db2777; /* pink-600 */
            background-color: #fce7f3; /* pink-100 */
            color: #db2777;
        }
        .type-radio:checked + div .check-icon {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen py-10 px-4">

    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-lg">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Crie sua Conta</h1>
            <p class="text-gray-500 text-sm mt-2">Escolha seu perfil para começar</p>
        </div>
        
        <div id="error-msg" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 text-sm"></div>

        <form onsubmit="handleRegister(event)">
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <label class="cursor-pointer relative">
                    <input type="radio" name="account_type" value="client" class="type-radio sr-only" checked>
                    <div class="border-2 border-gray-200 rounded-xl p-4 text-center hover:border-gray-400 transition duration-200 h-full flex flex-col justify-center items-center">
                        <i class="fas fa-search text-3xl mb-2"></i>
                        <span class="font-bold text-sm block">Sou Membro</span>
                        <span class="text-xs text-gray-500 mt-1">Quero encontrar modelos</span>
                        <i class="fas fa-check-circle absolute top-2 right-2 text-pink-600 opacity-0 check-icon transition-opacity"></i>
                    </div>
                </label>

                <label class="cursor-pointer relative">
                    <input type="radio" name="account_type" value="model" class="type-radio sr-only">
                    <div class="border-2 border-gray-200 rounded-xl p-4 text-center hover:border-gray-400 transition duration-200 h-full flex flex-col justify-center items-center">
                        <i class="fas fa-star text-3xl mb-2"></i>
                        <span class="font-bold text-sm block">Sou Acompanhante</span>
                        <span class="text-xs text-gray-500 mt-1">Quero anunciar meu perfil</span>
                        <i class="fas fa-check-circle absolute top-2 right-2 text-pink-600 opacity-0 check-icon transition-opacity"></i>
                    </div>
                </label>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 ml-1">E-mail</label>
                    <input type="email" id="email" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-pink-500 focus:bg-white focus:outline-none transition" required placeholder="seu@email.com">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 ml-1">Senha</label>
                    <input type="password" id="password" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-pink-500 focus:bg-white focus:outline-none transition" required placeholder="Mínimo 6 caracteres">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 ml-1">Confirmar Senha</label>
                    <input type="password" id="confirm_password" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-pink-500 focus:bg-white focus:outline-none transition" required placeholder="Repita a senha">
                </div>
            </div>
            
            <button type="submit" id="btn-submit" class="mt-8 w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transform transition hover:scale-[1.02] active:scale-95">
                Cadastrar Agora
            </button>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">Já tem conta? <a href="<?= url('/login') ?>" class="text-pink-600 font-bold hover:underline">Faça Login</a></p>
            </div>
        </form>
    </div>

    <script>
        async function handleRegister(e) {
            e.preventDefault();
            
            // Pega o valor do Radio selecionado
            const accountType = document.querySelector('input[name="account_type"]:checked').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            
            const errorDiv = document.getElementById('error-msg');
            const btn = document.getElementById('btn-submit');
            
            // UI Feedback
            errorDiv.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Criando conta...';

            try {
                const response = await fetch('<?= url('/register') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        email: email, 
                        password: password,
                        confirm_password: confirm_password,
                        account_type: accountType // Enviando o tipo escolhido
                    })
                });
                
                const text = await response.text();
                
                try {
                    const result = JSON.parse(text);
                    
                    if (result.success) {
                        // Sucesso: Redireciona
                        // Se for modelo, podemos mandar um parametro extra para o login saber
                        window.location.href = result.redirect;
                    } else {
                        throw new Error(result.message || "Erro desconhecido");
                    }
                } catch (err) {
                    errorDiv.innerText = err.message || "Erro no servidor.";
                    errorDiv.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerText = "Cadastrar Agora";
                }

            } catch (error) {
                console.error(error);
                errorDiv.innerText = "Erro de conexão.";
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.innerText = "Cadastrar Agora";
            }
        }
    </script>
</body>
</html>