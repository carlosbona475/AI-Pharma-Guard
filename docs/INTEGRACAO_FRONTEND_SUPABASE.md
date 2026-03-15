# Diagnóstico: Integração Frontend → Supabase (AI Pharma Guard)

## Arquitetura

O sistema **não usa o cliente JavaScript do Supabase** no frontend. O fluxo é:

```
Frontend (HTML/JS)  →  fetch()  →  Backend (PHP api.php)  →  PDO  →  PostgreSQL (Supabase)
```

- **Inicialização do client**: Não há `createClient` do Supabase no frontend. O "client" é o módulo `app.js`, que define `API_BASE` e o objeto `api` com métodos que fazem `fetch` para `backend/api.php`.
- **RLS (Row Level Security)**: Aplicam-se ao PostgreSQL. O backend conecta com usuário/senha do pooler (Supabase). Se RLS estiver ativo nas tabelas e não houver política para o role usado pelo backend, os SELECT/INSERT podem falhar. Use o script `database/rls_supabase.sql` para desabilitar RLS ou criar políticas para o role do backend.
- **Query SELECT**: O backend usa `SELECT * FROM medicamentos WHERE farmacia_id = ?` (e equivalentes para outras tabelas). Nome da tabela: `medicamentos` (compatível com o schema Supabase).
- **Await/async**: O frontend usa Promises (`.then()`/`.catch()`). Não há `async/await`; o uso de Promises está correto.
- **Chamada no load**: Em `medicamentos.html` e `pacientes.html`, a lista é carregada ao abrir a página (`loadMedicamentos()` / `loadPacientes()`).
- **Renderização DOM**: Conteúdo da tabela é preenchido via `innerHTML` com os dados retornados. Foi adicionada verificação `Array.isArray()` para evitar erro quando a API não retorna array.
- **Permissões**: O backend usa variáveis de ambiente (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS). O usuário do Supabase precisa de permissão SELECT/INSERT/UPDATE nas tabelas. RLS pode restringir; ver `database/rls_supabase.sql`.
- **Console errors**: Erros de API são logados com `console.error` e, onde aplicável, exibidos na UI (ex.: bloco de erro + "Tentar novamente" em medicamentos).
- **Network request**: Todas as chamadas passam por `app.js` → `request()` → `fetch(API_BASE + '/api.php?action=...')`. `API_BASE` é definido em `resolveApiBase()`: se a URL da página contiver `/frontend/`, usa `origin + '/backend'`; senão usa `'../../backend'` como fallback.

## Ajustes realizados

1. **app.js**
   - `API_BASE` passou a ser definido por `resolveApiBase()` (suporte a diferentes origens/caminhos).
   - Inclusão de `api.analisarRiscoPaciente(pacienteId)` para uso na tela de pacientes.

2. **medicamentos.html**
   - Carregamento robusto: estado de loading, tratamento de erro com mensagem e botão "Tentar novamente".
   - Verificação `Array.isArray(medicamentos)` antes de montar as linhas da tabela.
   - Uso de `api.listarMedicamentos()` no load da página.

3. **pacientes.html**
   - Uso de `api.analisarRiscoPaciente(id)` em vez de `fetch` direto, garantindo uso do mesmo `API_BASE`.
   - Verificação `Array.isArray(pacientes)` em `loadPacientes()`.

4. **RLS Supabase**
   - Criado `database/rls_supabase.sql` com opção para desabilitar RLS nas tabelas ou criar políticas para o role do backend.

## Como testar

1. Abrir a aplicação (ex.: `http://localhost:8000/frontend/pages/medicamentos.html`).
2. Aba Network: conferir se as requisições vão para `.../backend/api.php?action=listar_medicamentos` (e demais actions) e se a resposta é JSON com array.
3. Aba Console: não devem aparecer erros de "Unexpected token" ou "Resposta inválida"; em caso de falha de rede/servidor, a mensagem de erro deve ser exibida na tela (medicamentos) ou no alert (pacientes/dashboard).
