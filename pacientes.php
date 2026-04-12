<?php
include 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pacientes - AI Pharma Guard</title>
    <link rel="stylesheet" href="style_profissional.css">
</head>
<body>
    <h2>Cadastro de Paciente</h2>
    <form method="post" action="backend/api.php?action=cadastrar_paciente" accept-charset="UTF-8">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required maxlength="100">

        <label for="idade">Idade:</label>
        <input type="number" id="idade" name="idade" min="0" max="150" value="0">

        <label for="cpf">CPF:</label>
        <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="000.000.000-00" autocomplete="off">

        <label for="sexo">Sexo:</label>
        <select id="sexo" name="sexo">
            <option value="masculino">Masculino</option>
            <option value="feminino">Feminino</option>
        </select>

        <label for="doencas">Doenças:</label>
        <input type="text" id="doencas" name="doencas">

        <label for="medicamentos">Medicamentos em uso:</label>
        <input type="text" id="medicamentos" name="medicamentos">

        <button type="submit">Cadastrar Paciente</button>
    </form>
    <p><small>A resposta do servidor será exibida em JSON (sucesso ou erro detalhado).</small></p>

    <h3>Lista de Pacientes</h3>
    <table>
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
            <!-- Preencher via API listar_pacientes ou painel -->
        </tbody>
    </table>
</body>
</html>
