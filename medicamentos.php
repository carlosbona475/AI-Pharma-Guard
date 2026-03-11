<?php
// Rota de medicamentos
include 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Medicamentos - AI Pharma Guard</title>
    <link rel="stylesheet" href="style_profissional.css">
</head>
<body>
    <h2>Cadastro de Medicamento</h2>
    <form>
        <label for="nome-medicamento">Nome do Medicamento:</label>
        <input type="text" id="nome-medicamento" name="nome-medicamento" required>
        <label for="classe">Classe Farmacológica:</label>
        <input type="text" id="classe" name="classe">
        <label for="dose">Dose:</label>
        <input type="text" id="dose" name="dose">
        <label for="indicacao">Indicação:</label>
        <input type="text" id="indicacao" name="indicacao">
        <label for="contraindicacoes">Contraindicações:</label>
        <input type="text" id="contraindicacoes" name="contraindicacoes">
        <button type="submit">Cadastrar Medicamento</button>
    </form>
    <h3>Lista de Medicamentos</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Classe</th>
                <th>Dose</th>
                <th>Indicação</th>
                <th>Contraindicações</th>
            </tr>
        </thead>
        <tbody>
            <!-- Os dados dos medicamentos serão inseridos aqui -->
        </tbody>
    </table>
</body>
</html>