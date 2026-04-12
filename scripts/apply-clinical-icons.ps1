$root = Split-Path -Parent $PSScriptRoot
Get-ChildItem -Path $root -Include *.html, *.php -Recurse -File | ForEach-Object {
    $p = $_.FullName
    $c = [IO.File]::ReadAllText($p)
    $o = $c
    $c = $c.Replace('<span class="nav-icon">&#128202;</span>', '<span class="nav-icon nav-icon--chart" aria-hidden="true"></span>')
    $c = $c.Replace('<span class="nav-icon">&#128100;</span>', '<span class="nav-icon nav-icon--users" aria-hidden="true"></span>')
    $c = $c.Replace('<span class="nav-icon">&#128138;</span>', '<span class="nav-icon nav-icon--pill" aria-hidden="true"></span>')
    $c = $c.Replace('<span class="nav-icon">&#9888;</span>', '<span class="nav-icon nav-icon--alert" aria-hidden="true"></span>')
    $c = $c.Replace('<span class="nav-icon">&#128274;</span>', '<span class="nav-icon nav-icon--lock" aria-hidden="true"></span>')
    $c = $c.Replace('<span class="nav-icon">&#127970;</span>', '<span class="nav-icon nav-icon--hospital" aria-hidden="true"></span>')
    $c = $c.Replace('<span>&#128100;</span> ', '<span class="user-pill__ico" aria-hidden="true"></span> ')
    $c = $c.Replace('<a href="../../pacientes.php"><span class="nav-icon">&#128203;</span>', '<a href="../../pacientes.php"><span class="nav-icon nav-icon--clipboard" aria-hidden="true"></span>')
    $c = $c.Replace('<a href="relatorios.html"><span class="nav-icon">&#128203;</span>', '<a href="relatorios.html"><span class="nav-icon nav-icon--report" aria-hidden="true"></span>')
    $c = $c.Replace('<a href="frontend/pages/relatorios.html"><span class="nav-icon">&#128203;</span>', '<a href="frontend/pages/relatorios.html"><span class="nav-icon nav-icon--report" aria-hidden="true"></span>')
    $c = $c.Replace('<a href="pacientes.php" class="active"><span class="nav-icon">&#128203;</span>', '<a href="pacientes.php" class="active"><span class="nav-icon nav-icon--clipboard" aria-hidden="true"></span>')
    if ($c -ne $o) {
        $utf8 = New-Object System.Text.UTF8Encoding $false
        [IO.File]::WriteAllText($p, $c, $utf8)
        Write-Host $p
    }
}
