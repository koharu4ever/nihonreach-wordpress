param([string]$Backup)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if (-not $Backup) { $Backup = (Get-Content '.local/latest-backup.txt' -Raw).Trim() }
if ($Backup -notmatch '^\d{8}-\d{6}$') { throw 'Backup 必须是 backups 下的时间戳目录名。' }
if (-not (Test-Path "backups/$Backup/checksums.json")) { throw '没有找到完整备份。' }
foreach ($entry in (Get-Content "backups/$Backup/checksums.json" -Raw | ConvertFrom-Json)) {
    if ((Get-FileHash "backups/$Backup/$($entry.File)" -Algorithm SHA256).Hash -ne $entry.Hash) { throw '备份校验失败。' }
}
$existing = docker volume ls --filter name=nihonreach_wp_restore_ --format '{{.Name}}'
if ($LASTEXITCODE) { throw '无法检查 Docker 卷。' }
if ($existing) { throw '恢复实例已有数据卷，拒绝覆盖。请保留现有演练，或人工确认其归属和数据后处理。' }
$restoreDb = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
$restoreRoot = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
@("DB_PASSWORD=$restoreDb","DB_ROOT_PASSWORD=$restoreRoot") | Set-Content '.local/restore.env'
function Invoke-Restore { docker compose --env-file .local/restore.env -f compose.restore.yaml -p nihonreach_wp_restore @args; if ($LASTEXITCODE) { throw 'Restore command failed.' } }
Invoke-Restore up -d --wait
Invoke-Restore stop web
Invoke-Restore run --rm --user 0:0 --entrypoint sh cli -ec "tar -xzf /backups/$Backup/wordpress.tar.gz -C /var/www/html; tar -xzf /backups/$Backup/uploads.tar.gz -C /var/www/html/wp-content/uploads; chown -R 33:33 /var/www/html"
$importCommand = 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -u nrc nrc < /backups/' + $Backup + '/database.sql'
Invoke-Restore exec -T db sh -c $importCommand
Invoke-Restore run --rm cli search-replace 'http://127.0.0.1:8081' 'http://127.0.0.1:8082' --all-tables-with-prefix --skip-columns=guid --precise --report-changed-only
Invoke-Restore run --rm cli rewrite flush
Invoke-Restore start web
Write-Output 'Restore ready: http://127.0.0.1:8082/ ; login uses the account saved in the backup.'
