<?php
include 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes - AI Pharma Guard</title>
    <link rel="stylesheet" href="frontend/css/app.css">
</head>
<body>
    <header class="app-topbar">
        <button type="button" class="sidebar-toggle" aria-label="Abrir menu">&#9776;</button>
        <div class="app-topbar__brand">
            <h1>AI Pharma Guard</h1>
            <span class="app-topbar__tagline">Cadastro de pacientes</span>
        </div>
        <span class="app-topbar__spacer"></span>
        <div class="app-topbar__actions">
            <span class="user-pill" title="Formulário servidor"><span>&#128203;</span> PHP + MySQL</span>
        </div>
    </header>
    <div class="app-overlay" aria-hidden="true"></div>
    <aside class="app-sidebar">
        <nav>
            <div class="nav-title">Navegação</div>
            <ul>
                <li><a href="frontend/pages/dashboard.html"><span class="nav-icon">&#128202;</span><span>Dashboard</span></a></li>
                <li><a href="frontend/pages/pacientes.html"><span class="nav-icon">&#128100;</span><span>Pacientes</span></a></li>
                <li><a href="pacientes.php" class="active"><span class="nav-icon">&#128203;</span><span>Cadastro (PHP)</span></a></li>
                <li><a href="frontend/pages/medicamentos.html"><span class="nav-icon">&#128138;</span><span>Medicamentos</span></a></li>
                <li><a href="frontend/pages/interacoes.html"><span class="nav-icon">&#9888;</span><span>Interações</span></a></li>
                <li><a href="frontend/pages/relatorios.html"><span class="nav-icon">&#128203;</span><span>Relatórios</span></a></li>
                <li><a href="frontend/pages/login.html"><span class="nav-icon">&#128274;</span><span>Login</span></a></li>
                <li><a href="frontend/pages/cadastro.html"><span class="nav-icon">&#127970;</span><span>Cadastrar Farmácia</span></a></li>
            </ul>
        </nav>
    </aside>
    <main class="app-main">
        <div class="container">
            <div class="page-head">
                <h1 class="page-title">Cadastro de paciente</h1>
                <p class="page-subtitle">Envio direto ao backend — a resposta do servidor aparece em JSON (sucesso ou erro detalhado).</p>
            </div>

            <div class="card">
                <h3>Dados do paciente</h3>
                <form method="post" action="backend/api.php?action=cadastrar_paciente" accept-charset="UTF-8" class="form-stack">
                    <div class="input-group">
                        <label for="nome">Nome completo *</label>
                        <input type="text" id="nome" name="nome" required maxlength="100" placeholder="Nome do paciente" autocomplete="name">
                    </div>
                    <div class="input-group">
                        <label for="idade">Idade</label>
                        <input type="number" id="idade" name="idade" min="0" max="150" value="0">
                    </div>
                    <div class="input-group">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="000.000.000-00" autocomplete="off">
                    </div>
                    <div class="input-group">
                        <label for="sexo">Sexo</label>
                        <select id="sexo" name="sexo">
                            <option value="masculino">Masculino</option>
                            <option value="feminino">Feminino</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="doencas">Doenças</label>
                        <input type="text" id="doencas" name="doencas" placeholder="Separe por vírgula, se houver">
                    </div>
                    <div class="input-group">
                        <label for="medicamentos">Medicamentos em uso</label>
                        <input type="text" id="medicamentos" name="medicamentos" placeholder="Separe por vírgula, se houver">
                    </div>
                    <div style="margin-top: 8px;">
                        <button type="submit" class="btn btn-primary">Cadastrar paciente</button>
                    </div>
                </form>
                <div class="alert alert--info mt-2" style="margin-top: 20px;">
                    Após enviar, o navegador exibirá a resposta JSON da API (incluindo mensagens de erro do banco, se houver).
                </div>
            </div>

            <div class="card">
                <h3>Lista de pacientes</h3>
                <p style="color: var(--text-muted); margin: 0 0 16px;">Esta tabela pode ser preenchida via API <code style="background: var(--primary-soft); padding: 2px 8px; border-radius: 6px;">listar_pacientes</code> ou painel futuro.</p>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Idade</th>
                                <th>CPF</th>
                                <th>Sexo</th>
                                <th>Doenças</th>
                                <th>Medicamentos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 28px;">Nenhum dado carregado nesta página estática.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script src="frontend/js/shell.js"></script>
</body>
</html>
