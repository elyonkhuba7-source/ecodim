$ErrorActionPreference = 'Stop'

$edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
$base = 'http://127.0.0.1/ecodim/capture_bootstrap.php?token=ecodim-capture-2026'
$outDir = 'C:\wamp64\www\ecodim\exports\captures'

if (-not (Test-Path $edge)) {
    throw 'Microsoft Edge est introuvable.'
}

if (-not (Test-Path $outDir)) {
    New-Item -ItemType Directory -Path $outDir | Out-Null
}

$captures = @(
    @{ Name = '01_login.png'; Url = 'http://127.0.0.1/ecodim/login.php' },
    @{ Name = '02_dashboard_admin.png'; Url = "$base&as=admin&page=index.php" },
    @{ Name = '03_inscriptions.png'; Url = "$base&as=admin&page=inscriptions.php" },
    @{ Name = '04_classes.png'; Url = "$base&as=admin&page=parametres.php" },
    @{ Name = '05_moniteurs.png'; Url = "$base&as=admin&page=moniteurs.php" },
    @{ Name = '06_securite.png'; Url = "$base&as=admin&page=securite.php" },
    @{ Name = '07_presences.png'; Url = "$base&as=admin&page=presences.php" },
    @{ Name = '08_dashboard_moniteur.png'; Url = "$base&as=moniteur&page=index.php" },
    @{ Name = '09_lecons.png'; Url = "$base&as=moniteur&page=lecons.php" },
    @{ Name = '10_evaluations.png'; Url = "$base&as=moniteur&page=evaluations.php" },
    @{ Name = '11_rapports.png'; Url = "$base&as=admin&page=rapports.php&type=lecons" },
    @{ Name = '12_finances.png'; Url = "$base&as=admin&page=finances.php" }
)

foreach ($capture in $captures) {
    $output = Join-Path $outDir $capture.Name
    if (Test-Path $output) {
        Remove-Item $output -Force
    }

    & $edge `
        --headless `
        --disable-gpu `
        --hide-scrollbars `
        --window-size=1440,1080 `
        --virtual-time-budget=5000 `
        "--screenshot=$output" `
        $capture.Url | Out-Null

    Start-Sleep -Seconds 1

    if (-not (Test-Path $output)) {
        throw "Capture introuvable: $($capture.Name)"
    }

    Write-Output $output
}
