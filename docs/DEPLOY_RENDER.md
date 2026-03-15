# Deploy AI Pharma Guard no Render

## Estrutura final do projeto

```
/
├── backend/
│   ├── config/
│   │   └── database.php      # Conexão PDO Supabase (getConnection)
│   ├── api/
│   │   ├── medicamentos.php  # GET → lista medicamentos (Supabase)
│   │   ├── interacoes.php    # GET → lista interações (Supabase)
│   │   └── dashboard.php     # GET → totais pacientes, medicamentos, interações
│   ├── api.php               # API legada (cadastros, pacientes, etc.)
│   ├── login.php
│   └── cadastro_farmacia.php
├── frontend/
│   ├── pages/
│   │   ├── dashboard.html
│   │   ├── medicamentos.html
│   │   ├── interacoes.html
│   │   └── ...
│   ├── js/
│   │   └── app.js            # API_BASE + chamadas a backend/api/*.php
│   └── css/
│       └── app.css
├── database/
│   ├── database_supabase.sql
│   └── rls_supabase.sql
├── .env.example
└── docs/
    └── DEPLOY_RENDER.md
```

## Variáveis de ambiente no Render

No painel do serviço: **Environment** → adicione:

| Variável          | Valor (exemplo) |
|-------------------|------------------|
| `SUPABASE_HOST`   | `aws-0-us-east-1.pooler.supabase.com` (ou o host do seu projeto) |
| `SUPABASE_DB`     | `postgres` |
| `SUPABASE_USER`   | `postgres.xxxxxxxx` (do Supabase → Settings → Database) |
| `SUPABASE_PASS`   | Senha do banco (Database password) |
| `SUPABASE_PORT`   | `5432` (direto) ou `6543` (pooler) |
| `SUPABASE_SSLMODE`| `require` |

No Supabase: **Project Settings → Database** → use **Connection string** (URI) ou **Host, Database name, User, Port, Password**. Para pooler (recomendado): **Connection pooling** → modo Session ou Transaction, porta 6543.

## Configurar API_BASE no frontend (produção)

Se o frontend for servido em outro domínio ou em path diferente, defina a URL do backend antes de carregar o `app.js`:

```html
<script>
  window.API_BASE = 'https://SEU-BACKEND.onrender.com/backend';
</script>
<script src="../js/app.js?v=4"></script>
```

Substitua `SEU-BACKEND` pelo nome do serviço no Render. Assim as chamadas serão:

- `https://SEU-BACKEND.onrender.com/backend/api/dashboard.php`
- `https://SEU-BACKEND.onrender.com/backend/api/medicamentos.php`
- `https://SEU-BACKEND.onrender.com/backend/api/interacoes.php`

## Como testar local

1. **Variáveis de ambiente**
   - Crie `.env` a partir de `.env.example` (ou exporte no terminal).
   - PHP não lê `.env` por padrão; use `getenv()` após carregar com algo como `vlucas/phpdotenv` ou defina as variáveis no sistema/shell.

2. **Servir o projeto**
   ```bash
   php -S localhost:8000 -t .
   ```

3. **Abrir no navegador**
   - `http://localhost:8000/frontend/pages/dashboard.html`
   - `http://localhost:8000/frontend/pages/medicamentos.html`
   - `http://localhost:8000/frontend/pages/interacoes.html`

4. **API_BASE local**
   - Com origem `http://localhost:8000`, o `app.js` usa `http://localhost:8000/backend`.
   - As requisições vão para `http://localhost:8000/backend/api/dashboard.php` etc.

## Como testar produção (Render)

1. Faça o deploy e confira se as env vars estão preenchidas.
2. Abra a URL do serviço (ex.: `https://SEU-BACKEND.onrender.com`).
3. Se o site estiver na raiz: `https://SEU-BACKEND.onrender.com/frontend/pages/dashboard.html`.
4. Abra o DevTools (F12) → **Network**: as requisições para `.../backend/api/dashboard.php`, `medicamentos.php`, `interacoes.php` devem retornar **200** e JSON.
5. **Console**: não deve haver erro de CORS nem "Unexpected token '<'".

## Logs e erros comuns

| Sintoma | Causa provável | O que fazer |
|--------|-----------------|-------------|
| Dashboard 0 / tabela vazia | Backend não conecta ao Supabase | Verificar SUPABASE_* no Render; testar conexão (ver abaixo). |
| "Tabela medicamentos não existe" | Schema não aplicado no Supabase | Rodar `database/database_supabase.sql` no SQL Editor do Supabase. |
| "Erro de conexão" no PHP | Host, porta, user ou senha errados | Conferir Host (pooler ou direto), Port (5432 ou 6543), User (com projeto), Password. |
| CORS / bloqueio no navegador | Backend sem header Access-Control | Os arquivos em `backend/api/*.php` já enviam `Access-Control-Allow-Origin: *`. |
| Resposta HTML em vez de JSON | PHP com erro ou 404 | Ver logs do Render; garantir que a URL seja `.../backend/api/medicamentos.php` (com `api` no path). |
| "Unexpected token '<'" | Servidor retornou HTML (erro PHP ou 404) | Checar URL da API no frontend (API_BASE) e logs do backend. |

## Validar conexão com o Supabase

No Render, em **Shell** (ou local com as mesmas env):

```bash
php -r "
require 'backend/config/database.php';
\$pdo = getConnection();
\$n = \$pdo->query('SELECT COUNT(*) FROM medicamentos')->fetchColumn();
echo 'Medicamentos: ' . \$n . PHP_EOL;
"
```

Se aparecer "Medicamentos: N" (número), a conexão e a tabela estão ok.
