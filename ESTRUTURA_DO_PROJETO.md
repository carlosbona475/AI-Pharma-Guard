# Estrutura completa do projeto AI Pharma Guard

Use este arquivo para enxergar todo o projeto de uma vez. No Cursor/VS Code, abra a **pasta raiz** do projeto (a que contém `index.html`, `backend`, `frontend`) em **Arquivo > Abrir Pasta**.

---

## Raiz do projeto

| Arquivo / Pasta   | Descrição |
|-------------------|-----------|
| `index.html`      | Página inicial; redireciona para o dashboard (ou pode apontar para login). |
| `README.md`       | Documentação do projeto. |
| `ESTRUTURA_DO_PROJETO.md` | Este arquivo – mapa do projeto. |
| `backend/`        | APIs PHP (api, login, cadastro de farmácia). |
| `frontend/`        | Interface: páginas, CSS, JS. |
| `database/`       | Script SQL do banco (database.sql). |
| `api.php`         | API legada na raiz (opcional). |
| Outros `.html`, `.php`, `.css` na raiz | Versões antigas ou alternativas; o fluxo principal está em `frontend/` e `backend/`. |

---

## backend/

| Arquivo | Função |
|---------|--------|
| `api.php` | API principal: estatísticas, pacientes, medicamentos, interações. Deve exigir sessão (`farmacia_id`). |
| `login.php` | Login: recebe email/senha, valida com `password_verify`, grava `$_SESSION['farmacia_id']`. |
| `cadastro_farmacia.php` | Cadastro de farmácia: nome, email, senha (com `password_hash`), telefone; retorna JSON. |

---

## frontend/

| Pasta / Arquivo | Conteúdo |
|-----------------|----------|
| `css/app.css`   | Estilos globais (layout, menu, cards, tabelas, cores de risco). |
| `js/app.js`     | Chamadas à API (`api.getEstatisticas`, `api.listarPacientes`, etc.) e `escapeHtml`. |
| `components/layout.html` | Referência de layout (menu lateral, etc.). |
| `pages/`        | Páginas HTML da aplicação. |

---

## frontend/pages/

| Página | Função |
|--------|--------|
| `dashboard.html` | Dashboard com totais (pacientes, medicamentos, interações) e atalhos. |
| `pacientes.html` | Cadastro e listagem de pacientes. |
| `medicamentos.html` | Cadastro e listagem de medicamentos. |
| `interacoes.html` | Análise de interações e cadastro de interações. |
| `relatorios.html` | Página de relatórios (placeholder). |
| `login.html`      | Formulário de login (email, senha) → chama `backend/login.php`; se sucesso, redireciona para `dashboard.html`. |
| `cadastro.html`   | Formulário de cadastro de farmácia (nome, email, senha, telefone) → chama `backend/cadastro_farmacia.php`. |

---

## database/

| Arquivo | Função |
|---------|--------|
| `database.sql` | Script para criar banco `farmacia`, tabelas `farmacias`, `pacientes`, `medicamentos`, `interacoes` (com `farmacia_id` onde aplicável). |

---

## Como ver “o projeto completo” no editor

1. **Abrir a pasta raiz**  
   Em **Arquivo > Abrir Pasta**, escolha a pasta que contém `backend`, `frontend`, `index.html` e `ESTRUTURA_DO_PROJETO.md`. Não abra só `frontend` ou só `backend`.

2. **Explorador (sidebar esquerda)**  
   Expanda as pastas `backend`, `frontend`, `frontend/pages`, `frontend/css`, `frontend/js`, `database`. Todos os arquivos listados acima devem aparecer aí.

3. **Busca**  
   Use **Ctrl+P** (ou Cmd+P no Mac) e digite o nome do arquivo (ex.: `login.html`, `api.php`) para abrir direto.

4. **Este arquivo**  
   Abra `ESTRUTURA_DO_PROJETO.md` na raiz para ter este mapa sempre à mão.

Se ainda não vir algo (por exemplo `login.html` ou `cadastro.html`), confira se está na pasta raiz correta e se os arquivos foram salvos (podem estar em outro disco ou workspace).
