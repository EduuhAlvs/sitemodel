# 🤖 TOP Model Project - AI Context & Guidelines

Este arquivo é a **Fonte da Verdade** para o desenvolvimento deste projeto. Todo agente de IA (Jules/Gemini) DEVE ler e seguir estas diretrizes antes de gerar código.

---

## 1. Stack Tecnológico
* **Backend:** PHP Puro (Native). Sem frameworks (Laravel/Symfony).
* **Database:** MySQL/MariaDB via **PDO**.
* **Frontend:** HTML5 + **Tailwind CSS (via CDN)**.
* **Ícones:** FontAwesome 6.4.0 (CDN).
* **Fontes:** Google Fonts ('Plus Jakarta Sans' e 'Outfit').

---

## 2. Design System & UI (CRÍTICO)

### 🎨 Paleta de Cores (Tailwind Config)
O projeto usa uma configuração personalizada no Tailwind.
* **Primary (Pink):** `#db2777` (Classes: `text-pink-600`, `bg-pink-600`, `border-pink-600`).
* **Secondary (Indigo):** `#4f46e5` (Classes: `text-indigo-600`, `bg-indigo-600`).
* **Background:** `#f8fafc` (Slate-50) + Pattern.
* **Texto:** Títulos em `text-slate-900`, corpo em `text-slate-600`.

### 🧩 Componentes Reutilizáveis (Copie e Cole)

**Botão Primário (Ação Principal/CTA):**

    <button class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-4 rounded-xl font-bold text-sm transition shadow-lg transform hover:scale-105 active:scale-95">
        Texto do Botão
    </button>

**Botão Secundário (Outline):**

    <a href="#" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-pink-600 hover:border-pink-200 transition px-8 py-4 rounded-full border border-slate-200 bg-white shadow-lg shadow-slate-100/50 group">
        Texto Secundário
    </a>

**Card de Perfil/Conteúdo:**
Use sempre `rounded-2xl`, bordas sutis e sombra `shadow-card`.

    <div class="bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-card hover:shadow-2xl transition duration-500">
        </div>

**Inputs de Formulário:**

    <input type="text" class="flex-1 bg-transparent border-none focus:ring-0 text-slate-800 placeholder-slate-400 px-4 py-4 font-semibold text-base" placeholder="...">

---

## 3. CSS Global Obrigatório
Ao criar novas páginas, certifique-se de incluir estas classes CSS customizadas, pois elas definem a identidade do site.

    /* Fundo Padrão */
    .bg-pattern {
        background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
        background-size: 40px 40px;
    }

    /* Menu com efeito de vidro */
    .glass-nav { 
        background: rgba(255, 255, 255, 0.85); 
        backdrop-filter: blur(12px); 
        border-bottom: 1px solid rgba(226, 232, 240, 0.6); 
    }

    /* Badges de Planos (Gold/Silver/Bronze) */
    .tag-vip { background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%); color: #000; }
    .tag-plus { background: linear-gradient(135deg, #E2E8F0 0%, #94a3b8 100%); color: #0f172a; }
    .tag-premium { background: linear-gradient(135deg, #fdba74 0%, #ea580c 100%); color: #FFF; }

---

## 4. Estrutura de Arquivos e Código

### Estrutura Padrão de Página (Boilerplate)
Todas as views (`views/pages/`) devem seguir esta estrutura HTML:
1.  **Head:** Incluir meta tags, Fontes (Jakarta/Outfit), Tailwind CDN e FontAwesome.
2.  **Body:** Classes `text-slate-600 antialiased selection:bg-pink-500 selection:text-white bg-pattern`.
3.  **Nav:** Usar a estrutura `.glass-nav` fixa no topo.
4.  **Footer:** Manter o padrão do rodapé com links e copyright.

### Banco de Dados (Segurança)
* **Conexão:** Use SEMPRE `require 'config/database.php';`.
* **Queries:** Use **Prepared Statements** (`$stmt->prepare()`) para TODAS as consultas com variáveis. Nunca concatene strings em SQL.
* **XSS:** Ao exibir dados do usuário (nome, bio), use `htmlspecialchars($var)`.

---

## 5. Regras de Comportamento (Constraints)
1.  ⛔ **NÃO altere o layout visual:** Mantenha a consistência com `home.php`. Não invente cores ou estilos novos.
2.  ⛔ **NÃO use frameworks JS:** Use Vanilla JS ou Alpine.js (se necessário) inline. Não adicione passos de build (npm/webpack).
3.  ✅ **Mobile First:** Garanta que todas as classes Tailwind funcionem em mobile (`w-full`) e desktop (`max-w-7xl`).

4.  ✅ **Variáveis Definidas:** No início de cada arquivo PHP, defina valores padrão para evitar avisos de "Undefined Variable" (Ex: `$profiles = $profiles ?? [];`).

---

## 6. Idioma e Comunicação
* **Idioma:** Toda a comunicação, explicações, títulos de PR e mensagens de commit DEVEM ser em **Português do Brasil (pt-BR)**.
* **Código:** Mantenha nomes de variáveis e funções em Inglês (ex: `getProfile`, `$user_id`) para manter o padrão, mas comentários explicativos no código podem ser em Português.
