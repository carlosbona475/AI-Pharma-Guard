# AI Pharma Guard

Sistema web multi-farmácia (SaaS) para gestão farmacêutica: cadastro de farmácias, login, pacientes (com prontuário completo), medicamentos, análise de interações medicamentosas e relatórios.

---

## Estrutura do projeto

```

├── index.html              → Página inicial (Dashboard, Login, Criar conta)
├── layout_profissional.html → Layout com menu que aponta para frontend/pages/
├── README.md
├── ESTRUTURA_DO_PROJETO.md → Mapa detalhado do projeto
│
├── frontend/
│   ├── pages/
│   │   ├── dashboard.html   → Estatísticas e atalhos
│   │   ├── pacientes.html   → Cadastro e lista de pacientes (prontuário: alergias, histórico, observações)
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
    ├── database.sql         → Schema completo (farmacias, pacientes com prontuário, medicamentos, interacoes)
    └── migration_pacientes_prontuario.sql → ALTER TABLE para adicionar alergias, historico_clinico, observacoes
```

---

## Como executar

1. **Banco de dados**
   - **Instalação nova:** importe `database/database.sql` no MySQL (cria o banco `farmacia` e todas as tabelas já com os campos de prontuário).
   - **Tabela pacientes já existente:** execute `database/migration_pacientes_prontuario.sql` para adicionar as colunas `alergias`, `historico_clinico` e `observacoes` sem recriar a tabela.

2. **Servidor PHP**  
   Na **raiz do projeto** (pasta que contém `index.html`, `frontend`, `backend`):
   ```bash
   php -S localhost:8000
   ```

3. **Abrir no navegador**  
   Acesse: **http://localhost:8000/**  
   Na página inicial:
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

| Página              | Caminho                         | Função |
|---------------------|---------------------------------|--------|
| Dashboard           | `frontend/pages/dashboard.html` | Totais (pacientes, medicamentos, interações) e atalhos |
| Pacientes           | `frontend/pages/pacientes.html` | Cadastro com prontuário (alergias, histórico clínico, observações) e tabela |
| Medicamentos        | `frontend/pages/medicamentos.html` | Cadastro e tabela de medicamentos |
| Interações          | `frontend/pages/interacoes.html` | Analisar e cadastrar interações (cores: verde/amarelo/vermelho) |
| Relatórios          | `frontend/pages/relatorios.html` | Relatórios |
| Login               | `frontend/pages/login.html`     | Login (email e senha) |
| Cadastrar Farmácia  | `frontend/pages/cadastro.html`  | Criar conta da farmácia |

---

## API (backend)

Todas as respostas são **JSON** (nunca HTML).

### Autenticação (sem sessão obrigatória para esses endpoints)

- **POST** `backend/cadastro_farmacia.php`  
  Corpo: `{ "nome", "email", "senha", "telefone" }`  
  Retorno: `{ "success": true/false, "message": "..." }`

- **POST** `backend/login.php`  
  Corpo: `{ "email", "senha" }`  
  Retorno: `{ "success": true }` e define `$_SESSION['farmacia_id']`

### API principal (`backend/api.php`)

- **GET** `api.php?action=estatisticas` → `{ pacientes, medicamentos, interacoes_cadastradas }`
- **GET** `api.php?action=listar_pacientes` → array de pacientes (inclui `alergias`, `historico_clinico`, `observacoes`)
- **POST** `api.php?action=cadastrar_paciente`  
  Corpo (JSON): `nome`, `idade`, `sexo`, `doencas`, `medicamentos`, `alergias`, `historico_clinico`, `observacoes`  
  Sucesso: `{ "success": true, "id": <id> }`  
  Erro: `{ "success": false, "message": "..." }`
- **GET** `api.php?action=listar_medicamentos` → array de medicamentos
- **POST** `api.php?action=cadastrar_medicamento` → corpo: nome, classe, dose, indicacao, contraindicacoes
- **GET** `api.php?action=listar_interacoes` → lista de interações (nomeA, nomeB, nivel_risco, etc.)
- **POST** `api.php?action=cadastrar_interacao` → corpo: medicamentoA, medicamentoB, tipo_interacao, nivel_risco, recomendacao
- **POST** `api.php?action=verificar_interacoes` → corpo: `{ "medicamentos": [ { "id", "nome" }, ... ] }` → array de interações encontradas

---

## Banco de dados (schema relevante)

### Tabela `pacientes`

| Coluna               | Tipo        | Descrição |
|----------------------|-------------|-----------|
| id                   | INT PK      | Chave primária |
| farmacia_id          | INT         | FK para farmacias (multi-farmácia) |
| nome                 | VARCHAR(100)| Nome do paciente |
| idade                | INT         | Idade |
| sexo                 | ENUM        | masculino, feminino |
| doencas              | TEXT        | Doenças / condições |
| medicamentos_usados  | TEXT        | Medicamentos em uso |
| alergias             | TEXT        | Alergias conhecidas (prontuário) |
| historico_clinico    | TEXT        | Histórico clínico (prontuário) |
| observacoes          | TEXT        | Observações farmacêuticas (prontuário) |
| created_at           | TIMESTAMP   | Data de cadastro |

Inserções usam **prepared statements**; respostas da API são sempre JSON.

---

## Evolução do prontuário (o que foi feito)

Resumo objetivo do que foi implementado no sistema em relação ao prontuário do paciente.

### 1) Banco de dados

- **Schema (`database/database.sql`):** na tabela `pacientes` foram adicionados os campos:
  - `alergias` (TEXT)
  - `historico_clinico` (TEXT)
  - `observacoes` (TEXT)
- **Migração (`database/migration_pacientes_prontuario.sql`):** script de ALTER TABLE para quem já tem a tabela `pacientes` criada, adicionando essas três colunas de forma segura (execute uma vez).

### 2) Frontend – formulário de paciente

- Em **`frontend/pages/pacientes.html`** foram adicionados ao formulário de cadastro:
  - **Textarea “Alergias”** – alergias conhecidas (medicamentos, substâncias).
  - **Textarea “Histórico clínico”** – histórico clínico relevante.
  - **Textarea “Observações farmacêuticas”** – observações do farmacêutico.
- Layout e estilo seguem o padrão já existente da página.

### 3) JavaScript – cadastro de paciente

- Em **`frontend/js/app.js`** a função `api.cadastrarPaciente(data)` continua enviando o body em JSON; não foi alterada a assinatura.
- Em **`frontend/pages/pacientes.html`** o handler do submit do formulário envia no body do fetch os campos:
  - `alergias`
  - `historico_clinico`
  - `observacoes`
  além dos já existentes (nome, idade, sexo, doencas, medicamentos), garantindo JSON válido.

### 4) Backend PHP

- Em **`backend/api.php`**, na action **`cadastrar_paciente`**:
  - Os novos campos são lidos do corpo da requisição via `json_decode`.
  - O INSERT em `pacientes` inclui `alergias`, `historico_clinico` e `observacoes`.
  - Uso de **prepared statements** para todos os valores.
  - Resposta sempre em JSON:
    - Sucesso: `{ "success": true, "id": <id> }`
    - Erro: `{ "success": false, "message": "mensagem de erro" }`
  - Não é retornado HTML em nenhum caso.

### 5) Listagem de pacientes

- **Backend:** `listar_pacientes` utiliza `SELECT * FROM pacientes`, portanto os novos campos (`alergias`, `historico_clinico`, `observacoes`) já vêm na listagem.
- **Frontend:** a tabela em `pacientes.html` exibe os dados listados; os dados completos do prontuário ficam disponíveis para uso em modal ou tela de detalhe (por exemplo, “Ver prontuário”) quando essa tela for implementada, usando os dados já retornados pela API.

### 6) Alerta de alergia (futuro)

- Fica preparado para evoluir com uma função (por exemplo no frontend ou em endpoint dedicado) que:
  - Receba o paciente (ou suas alergias) e o nome/ID do medicamento.
  - Verifique se nas alergias do paciente consta aquele medicamento (ou substância) e retorne se há ou não risco.
- Nenhuma rota ou funcionalidade atual foi removida; a inclusão do alerta pode ser feita depois sem quebrar o que já existe.

### 7) Cuidados adotados

- Rotas e actions existentes foram mantidas; apenas foram adicionados parâmetros e colunas.
- Nenhuma funcionalidade atual foi removida.
- Compatibilidade SaaS multi-farmácia preservada (estrutura com `farmacia_id` e sessão mantidas).
- Código mantido limpo: prepared statements, respostas em JSON e organização atual do projeto respeitadas.

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

## Objetivo do sistema

Sistema **multi-farmácia** com autenticação: cada farmácia tem login e cadastro e (quando a API estiver protegida por sessão) seus próprios pacientes, medicamentos e interações. O prontuário do paciente inclui doenças, medicamentos em uso, **alergias**, **histórico clínico** e **observações farmacêuticas**, evoluindo o AI Pharma Guard para um registro clínico mais completo.
