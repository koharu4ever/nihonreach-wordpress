param([ValidateSet('demo','test')][string]$Environment = 'demo')
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if (-not (Test-Path '.env')) {
    New-Item -ItemType Directory -Force '.local','backups' | Out-Null
    $dbSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
    $rootSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
    $adminSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(18))
    @('COMPOSE_PROJECT_NAME=nihonreach_wp_demo','HTTP_PORT=8081',"DB_PASSWORD=$dbSecret","DB_ROOT_PASSWORD=$rootSecret",'ADMIN_USER=portfolio_admin',"ADMIN_PASSWORD=$adminSecret") | Set-Content '.env'
    @('后台：http://127.0.0.1:8081/wp-admin/','用户名：portfolio_admin',"密码：$adminSecret") | Set-Content '.local/credentials.txt'
}
$project = "nihonreach_wp_$Environment"
$port = if ($Environment -eq 'demo') { 8081 } else { 8083 }
if ($Environment -eq 'test') {
    if (-not (Test-Path '.local/test.env')) {
        (Get-Content '.env') -replace 'nihonreach_wp_demo','nihonreach_wp_test' -replace 'HTTP_PORT=8081','HTTP_PORT=8083' | Set-Content '.local/test.env'
    }
    $config = '.local/test.env'
} else { $config = '.env' }
docker compose --env-file $config -p $project up -d --wait
if ($LASTEXITCODE) { throw 'Docker 启动失败，请查看上面的错误。' }
docker compose --env-file $config -p $project exec -T web chown -R www-data:www-data /var/www/html/wp-content/uploads
if ($LASTEXITCODE) { throw '上传卷权限初始化失败。' }
docker compose --env-file $config -p $project run --rm --entrypoint sh cli /work/scripts/bootstrap.sh "http://127.0.0.1:$port"
if ($LASTEXITCODE) { throw '初始化失败；可修复后重跑，不会覆盖已有演示内容。' }
