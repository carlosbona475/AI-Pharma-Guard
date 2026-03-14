# AI Pharma Guard

Sistema web multi-farmácia (SaaS) para gestão farmacêutica: cadastro de farmácias, login, pacientes, medicamentos, análise de interações medicamentosas e relatórios.

---

## Estrutura do projeto

```
/
├── index.html              → Página inicial (Dashboard, Login, Criar conta)
├── layout_profissional.html → Layout com menu que aponta para frontend/pages/
├── README.md
├── ESTRUTURA_DO_PROJETO.md → Mapa detalhado do projeto
│
├── frontend/
│   ├── pages/
│   │   ├── dashboard.html   → Estatísticas e atalhos
│   │   ├── pacientes.html   → Cadastro e lista de pacientes
│   │   ├── medicamentos.html → Cadastro e lista de medicamentos
│   │   ├── interacoes.html  → Análise e cadastro de interações
│   │   ├── relatorios.html  → Relatórios
│   │   ├── login.html       → Login da farmácia
│   │   └── cadastro.html    → Cadastro de nova farmácia
│   ├── css/
│   │   └── app.css          → Estilos (menu, cards, tabelas, cores de risco)
│   ├── js/
│   │   └── app.js           → Chamadas à API e utilitários
│   └── components/
│       └── layout.html      → Referência de layout
│
├── backend/
│   ├── api.php              → API principal (estatísticas, pacientes, medicamentos, interações)
│   ├── login.php            → Login (email/senha, sessão PHP)
│   └── cadastro_farmacia.php → Cadastro de farmácia (password_hash)
│
└── database/
    └── database.sql         → Schema (farmacias, pacientes, medicamentos, interacoes)
```

---

## Como executar

1. **Banco de dados**  
   Importe `database/database.sql` no MySQL (cria o banco `farmacia` e as tabelas, incluindo `farmacias`).

2. **Servidor PHP**  
   Na **raiz do projeto** (pasta que contém `index.html`, `frontend`, `backend`):
   ```bash
   php -S localhost:8000
   ```

3. **Abrir no navegador**  
   Acesse: **http://localhost:8000/**  
   Na página inicial você verá:
   - **Abrir Dashboard** → entra no sistema
   - **Login** → login da farmácia
   - **Criar conta** → cadastro de nova farmácia

4. **Configuração do banco**  
   Em `backend/api.php`, `backend/login.php` e `backend/cadastro_farmacia.php` ajuste se necessário:
   - `$servername` (ex.: `localhost`)
   - `$username` (ex.: `root`)
   - `$password`
   - `$dbname` (ex.: `farmacia`)

---

## Navegação (menu lateral)

| Página              | Caminho                      | Função |
|---------------------|------------------------------|--------|
| Dashboard           | `frontend/pages/dashboard.html` | Totais (pacientes, medicamentos, interações) e atalhos |
| Pacientes          | `frontend/pages/pacientes.html`  | Cadastro e tabela de pacientes |
| Medicamentos       | `frontend/pages/medicamentos.html` | Cadastro e tabela de medicamentos |
| Interações         | `frontend/pages/interacoes.html`  | Analisar e cadastrar interações (cores: verde/amarelo/vermelho) |
| Relatórios         | `frontend/pages/relatorios.html`  | Relatórios |
| Login              | `frontend/pages/login.html`      | Login (email e senha) |
| Cadastrar Farmácia | `frontend/pages/cadastro.html`    | Criar conta da farmácia |

---

## API (backend)

Todas as respostas são **JSON**.

### Autenticação (sem sessão obrigatória para esses endpoints)

- **POST** `backend/cadastro_farmacia.php`  
  Corpo: `{ "nome", "email", "senha", "telefone" }`  
  Retorno: `{ "success": true/false, "message": "..." }`

- **POST** `backend/login.php`  
  Corpo: `{ "email", "senha" }`  
  Retorno: `{ "success": true }` e define `$_SESSION['farmacia_id']`

### API principal (`backend/api.php`)

- **GET** `api.php?action=estatisticas` → `{ pacientes, medicamentos, interacoes_cadastradas }`
- **GET** `api.php?action=listar_pacientes` → array de pacientes
- **POST** `api.php?action=cadastrar_paciente` → corpo: nome, idade, sexo, doencas, medicamentos
- **GET** `api.php?action=listar_medicamentos` → array de medicamentos
- **POST** `api.php?action=cadastrar_medicamento` → corpo: nome, classe, dose, indicacao, contraindicacoes
- **GET** `api.php?action=listar_interacoes` → lista de interações (nomeA, nomeB, nivel_risco, etc.)
- **POST** `api.php?action=cadastrar_interacao` → corpo: medicamentoA, medicamentoB, tipo_interacao, nivel_risco, recomendacao
- **POST** `api.php?action=verificar_interacoes` → corpo: `{ "medicamentos": [ { "id", "nome" }, ... ] }` → array de interações encontradas

---

## Segurança

- Senhas com **password_hash** (cadastro) e **password_verify** (login).
- Prepared statements no PHP para evitar SQL injection.
- APIs podem exigir sessão (`$_SESSION['farmacia_id']`) para multi-farmácia.
- Respostas sempre em JSON; erros não expõem HTML do PHP.

---

## Cores de risco (interações)

- **Verde** = baixo / sem interação
- **Amarelo** = moderada
- **Vermelho** = grave

---

## Objetivo final

Sistema **multi-farmácia** com autenticação: cada farmácia tem login, cadastro e (quando a API estiver protegida por sessão) seus próprios pacientes, medicamentos e interações no AI Pharma Guard.
