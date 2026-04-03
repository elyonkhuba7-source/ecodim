<#
Usage: .\push_to_github.ps1 -RemoteUrl "https://github.com/username/repo.git" [-Force]

This script will:
 - check that git is installed
 - initialize a local git repo if none exists
 - add all files, commit, and push to the provided remote URL (main branch)

If your GitHub requires a personal access token for HTTPS pushes, use a credential helper
or create a remote with the token embedded (less secure):
 $remote = "https://<TOKEN>@github.com/username/repo.git"
#>

param(
    [Parameter(Mandatory=$true)]
    [string]$RemoteUrl,
    [string]$Token,
    [switch]$Force
)

function Check-Git {
    $git = Get-Command git -ErrorAction SilentlyContinue
    if (-not $git) {
        Write-Error "Git n'est pas installé ou n'est pas dans le PATH. Installez Git pour Windows: https://git-scm.com/download/win"
        exit 1
    }
}

Set-Location -Path $PSScriptRoot
Check-Git

if (-not (Test-Path -Path .git)) {
    Write-Host "Initialisation d'un dépôt git local..."
    git init
} else {
    Write-Host "Dépôt git local déjà présent."
}

git add .

if ($Force) {
    git commit -m "Initial import" --allow-empty
} else {
    try {
        git commit -m "Initial import"
    } catch {
        Write-Host "Aucun changement à committer ou commit déjà présent."
    }
}

if (-not (git remote)) {
    git remote add origin $RemoteUrl
} else {
    Write-Host "Remote(s) existant(s) :"; git remote -v
    Write-Host "Mise à jour de 'origin' vers $RemoteUrl"
    git remote remove origin; git remote add origin $RemoteUrl
}

git branch -M main

if ($Token) {
    # Create a temporary remote that embeds the token for the push, then remove it so the token is not stored
    $tempRemote = "temp-origin-with-token"
    try {
        if ((git remote) -match $tempRemote) { git remote remove $tempRemote }
    } catch { }

    # Safely insert the token after https:// (do not print it)
    $authUrl = $RemoteUrl -replace '^https://', "https://$($Token)@"

    Write-Host "Poussée (via remote temporaire) vers $RemoteUrl ..."
    try {
        git remote add $tempRemote $authUrl
        git push -u $tempRemote main
        Write-Host "Push terminé. Suppression du remote temporaire."
    } catch {
        Write-Error "La push a échoué. Vérifiez que le token et l'URL sont corrects."
    } finally {
        try { git remote remove $tempRemote } catch { }
    }
} else {
    Write-Host "Poussée vers $RemoteUrl ..."
    try {
        git push -u origin main
        Write-Host "Push terminé."
    } catch {
        Write-Error "La push a échoué. Vérifiez que vous avez accès au dépôt distant et que les identifiants sont configurés."
    }
}
