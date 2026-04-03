# Ecodim

Petit projet PHP pour la gestion d'une application interne (inscriptions, présences, évaluations...).

Comment initialiser le dépôt et pousser sur GitHub (Windows PowerShell) :

1. Installer Git pour Windows : https://git-scm.com/download/win
2. Ouvrir PowerShell, naviguer vers le dossier :
   cd C:\wamp64\www\ecodim
3. Initialiser, committer et pousser (remplacez <GITHUB_REPO_URL> par l'URL du dépôt distant) :

   git init
   git add .
   git commit -m "Initial import"
   git remote add origin <GITHUB_REPO_URL>
   git branch -M main
   git push -u origin main

Option: utiliser `push_to_github.ps1` pour automatiser (voir fichier dans le repo).
