<?php
// Rota de pacientes
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
    <form>
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
        <label for="idade">Idade:</label>
        <input type="number" id="idade" name="idade" required>
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
    <h3>Lista de Pacientes</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Idade</th>
                <th>Sexo</th>
                <th>Doenças</th>
                <th>Medicamentos</th>
            </tr>
        </thead>
        <tbody>
            <!-- Os dados dos pacientes serão inseridos aqui -->
        </tbody>
    </table>
</body>
</html>