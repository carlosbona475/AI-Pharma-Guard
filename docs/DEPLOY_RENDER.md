# Deploy AI Pharma Guard (backend MySQL / PDO)

O backend usa **PHP + PDO + MySQL**. Credenciais ficam em `backend/config/database.php` (ajuste para o seu ambiente).

## Estrutura relevante

- `backend/config/database.php` — `getConnection()` e `ensurePacientesTable()`
- `backend/api.php` — cadastro de pacientes, medicamentos, interações, etc.
- `database/mysql_schema.sql` — schema MySQL (importar no phpMyAdmin ou cliente SQL)

## Variáveis

Use `.env.example` como referência. O PHP deste projeto lê credenciais principalmente do `database.php` (Hostinger).

## Testar API local

```bash
php -S localhost:8000 -t .
```

Abra `http://localhost:8000/frontend/pages/dashboard.html`. O `app.js` resolve `API_BASE` para `.../backend`.

## Erros comuns

| Sintoma | O que verificar |
|--------|------------------|
| Erro 500 no cadastro | Resposta JSON com campo `error`; conferir tabela `pacientes` e coluna `cpf` |
| Tabela inexistente | Importar `database/mysql_schema.sql` |
| PDO mysql não encontrado | Habilitar `pdo_mysql` no PHP |
