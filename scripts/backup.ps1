$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$folder = "backups/$stamp"
New-Item -ItemType Directory -Path $folder | Out-Null
function Invoke-Demo { docker compose -p nihonreach_wp_demo @args; if ($LASTEXITCODE) { throw 'Backup command failed.' } }
Invoke-Demo stop web
try {
    $dumpCommand = 'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump -u nrc --single-transaction --skip-lock-tables --no-tablespaces --set-gtid-purged=OFF nrc > /backups/' + $stamp + '/database.sql'
    Invoke-Demo exec -T db sh -c $dumpCommand
    Invoke-Demo run --rm --entrypoint sh cli -c "tar -czf /backups/$stamp/wordpress.tar.gz --exclude=./wp-content/uploads -C /var/www/html ."
    Invoke-Demo run --rm --entrypoint sh cli -c "tar -czf /backups/$stamp/uploads.tar.gz -C /var/www/html/wp-content/uploads ."
    Compress-Archive -Path plugins,themes,infra,scripts,tests,assets,docs,.devcontainer,wp-cli.yml,README.md,LICENSE,AGENTS.md,compose.yaml,compose.restore.yaml,.env.example,.gitignore,.dockerignore -DestinationPath "$folder/source.zip"
    Copy-Item '.env' "$folder/local.env"
    Get-ChildItem $folder -File | Get-FileHash -Algorithm SHA256 | Select-Object @{n='File';e={Split-Path $_.Path -Leaf}},Hash | ConvertTo-Json | Set-Content "$folder/checksums.json"
    Set-Content '.local/latest-backup.txt' $stamp
    Write-Output "Backup complete: $folder (private, Git ignored)."
} finally { Invoke-Demo start web }
