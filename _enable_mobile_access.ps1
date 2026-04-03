$files = @(
    'C:\wamp64\bin\apache\apache2.4.62.1\conf\httpd.conf',
    'C:\wamp64\bin\apache\apache2.4.62.1\conf\extra\httpd-vhosts.conf'
)

foreach ($file in $files) {
    $content = Get-Content -Path $file -Raw
    $updated = $content -replace 'Require local', 'Require all granted'
    Set-Content -Path $file -Value $updated
}
