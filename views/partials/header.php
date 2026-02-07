<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'TOP Model' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script>
        // Configuração do Tailwind para cores personalizadas
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#d4af37',
                        dark: '#1a1a1a'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased">

<nav class="bg-dark text-white shadow-lg sticky top-0 z-50 border-b border-gold/30">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="<?= url('/') ?>" class="flex-shrink-0 flex items-center">
                    <span class="font-bold text-2xl tracking-wider text-white">TOP<span class="text-gold">Model</span></span>
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-8">
                <a href="<?= url('/') ?>" class="hover:text-gold transition">Home</a>
                <a href="<?= url('/?genero=woman') ?>" class="hover:text-gold transition">Mulheres</a>
                <a href="<?= url('/?genero=man') ?>" class="hover:text-gold transition">Homens</a>
                <a href="<?= url('/?genero=trans') ?>" class="hover:text-gold transition">Trans</a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?= url('/perfil/editar') ?>" class="bg-gold text-dark px-4 py-2 rounded font-bold hover:bg-yellow-500 transition">
                        Meu Painel
                    </a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="hover:text-gold">Entrar</a>
                    <a href="<?= url('/register') ?>" class="border border-gold text-gold px-4 py-2 rounded hover:bg-gold hover:text-dark transition">
                        Anunciar
                    </a>
                <?php endif; ?>
            </div>

            <div class="flex items-center md:hidden">
                <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-white hover:text-gold focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </div>
    </nav>