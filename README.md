# AI Pharma Guard

Sistema web profissional para gestão farmacêutica: cadastro de pacientes e medicamentos, análise de interações medicamentosas e relatórios.

## Estrutura do projeto

```
/frontend
  /pages          → Páginas da aplicação
    dashboard.html
    pacientes.html
    medicamentos.html
    interacoes.html
    relatorios.html
  /components      → Referência de layout
  /css
    app.css       → Estilos globais (menu, cards, tabelas, cores de risco)
  /js
    app.js        → API e utilitários
/backend
  api.php         → API REST (PHP)
/database
  database.sql    → Script de criação do banco
index.html        → Redireciona para o Dashboard
```

## Como executar

1. **Banco de dados**: importe `database/database.sql` no MySQL (cria o banco `farmacia` e as tabelas).

2. **Backend**: configure o PHP com MySQL (XAMPP, WAMP ou `php -S localhost:8000` na raiz do projeto). A API fica em `http://localhost:8000/backend/api.php` (ajuste a porta se necessário).

3. **Frontend**: abra pelo mesmo servidor para evitar CORS:
   - **Opção A**: Servir a raiz do projeto (ex.: `php -S localhost:8000` na pasta do projeto). Acesse `http://localhost:8000/` ou `http://localhost:8000/frontend/pages/dashboard.html`.
   - **Opção B**: Se o frontend estiver em outro domínio/porta, ajuste `API_BASE` em `frontend/js/app.js` para a URL completa do backend (ex.: `http://localhost:8000/backend`).

4. **Configuração do banco**: em `backend/api.php` altere `$servername`, `$username`, `$password` e `$dbname` conforme seu ambiente.

## Navegação

- **Dashboard** (`/frontend/pages/dashboard.html`): estatísticas (total de pacientes, medicamentos, interações) e atalhos.
- **Pacientes** (`/frontend/pages/pacientes.html`): formulário de cadastro e tabela de pacientes.
- **Pacientes** (`/frontend/pages/medicamentos.html`): formulário de cadastro e tabela de medicamentos.
- **Interações** (`/frontend/pages/interacoes.html`): seleção de medicamentos e botão "Analisar interações"; resultados com cores de risco (verde = sem/baixo, amarelo = moderado, vermelho = grave).
- **Relatórios** (`/frontend/pages/relatorios.html`): página reservada para relatórios.

## API (backend)

- **GET** `api.php?action=estatisticas` → `{ pacientes, medicamentos, interacoes_cadastradas }`
- **GET** `api.php?action=listar_pacientes` → array de pacientes
- **POST** `api.php?action=cadastrar_paciente` → corpo JSON: nome, idade, sexo, doencas, medicamentos
- **GET** `api.php?action=listar_medicamentos` → array de medicamentos
- **POST** `api.php?action=cadastrar_medicamento` → corpo JSON: nome, classe, dose, indicacao, contraindicacoes
- **POST** `api.php?action=verificar_interacoes` → corpo JSON: `{ "medicamentos": [ { "id": 1, "nome": "..." }, ... ] }` → array de interações (com nomeA, nomeB, tipo_interacao, nivel_risco, recomendacao)
- **GET** `api.php?action=listar_interacoes` → lista todas as interações cadastradas (com nomeA, nomeB, etc.)
- **POST** `api.php?action=cadastrar_interacao` → corpo JSON: `medicamentoA`, `medicamentoB` (IDs), `tipo_interacao`, `nivel_risco` (baixo|medio|alto), `recomendacao`

Na página **Interações** é possível cadastrar novas interações (dois medicamentos, tipo, nível de risco e recomendação) e ver a tabela de interações cadastradas. O arquivo `database/database.sql` inclui dados de exemplo (medicamentos e interações) para teste.
