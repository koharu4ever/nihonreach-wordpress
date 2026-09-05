$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
$project = 'nrc_release_' + [Guid]::NewGuid().ToString('N').Substring(0,10)
$listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback,0)
$listener.Start()
$port = $listener.LocalEndpoint.Port
$listener.Stop()
$url = "http://127.0.0.1:$port"
$dbSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
$rootSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
$authSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(48))
$adminSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
$config = ".local/$project.env"
@("DB_PASSWORD=$dbSecret","DB_ROOT_PASSWORD=$rootSecret","NRC_AUTH_SECRET=$authSecret","NRC_SITE_URL=$url","NRC_SMOKE_PORT=$port",'NRC_ADMIN_USER=release_admin',"NRC_ADMIN_PASSWORD=$adminSecret",'NRC_ADMIN_EMAIL=release@example.test') | Set-Content $config
function Invoke-Release {
    docker compose --env-file $config -p $project -f compose.production.yaml -f infra/production/compose.smoke.yaml @args
    if($LASTEXITCODE) { throw 'Production smoke Docker command failed.' }
}
function Assert-Release([bool]$Condition,[string]$Message) {
    if(-not $Condition) { throw "FAIL: $Message" }
    Write-Output "PASS: $Message"
}
try {
    Invoke-Release up -d --no-build --wait --wait-timeout 180
    $setup=Invoke-WebRequest "$url/wp-admin/install.php" -SkipHttpErrorCheck
    Assert-Release ($setup.StatusCode -eq 403) 'Public WordPress installer is blocked'
    Invoke-Release exec -T --user 33:33 web sh /opt/nrc/initialize.sh
    $homepageResponse=Invoke-WebRequest "$url/"
    Assert-Release ($homepageResponse.Content.Contains('Portfolio Demo')) 'Production homepage serves demo'
    $catalog=Invoke-WebRequest "$url/products/"
    Assert-Release (([regex]::Matches($catalog.Content,'class="nrc-card"')).Count -eq 9) 'Catalogue shows 9 public products'
    $page2=Invoke-WebRequest "$url/products/page/2/"
    Assert-Release (([regex]::Matches($page2.Content,'class="nrc-card"')).Count -eq 3) 'Second page shows 3 public products'
    $secret=Invoke-WebRequest "$url/product/restricted-publish/"
    Assert-Release (-not $secret.Content.Contains('HIDDEN-PUBLISH')) 'Password-protected custom model is hidden'
    $login=Invoke-WebRequest "$url/wp-login.php" -SessionVariable loginSession
    $loginResult=Invoke-WebRequest "$url/wp-login.php" -Method Post -WebSession $loginSession -Body @{log='release_admin';pwd=$adminSecret;'wp-submit'='Log In';redirect_to="$url/wp-admin/";testcookie='1'}
    Assert-Release ($loginResult.Content.Contains('wp-admin-bar-my-account')) 'Production admin login works'
    $id=(Invoke-Release exec -T --user 33:33 web wp post list --post_type=nrc_product --name=em-06-4f --field=ID).Trim()
    Invoke-Release exec -T --user 33:33 web wp post meta update $id _nrc_model RELEASE-OK
    Invoke-Release exec -T --user 33:33 web sh -ec 'test ! -w /opt/nrc/site/wp-content/plugins/nrc-catalog/nrc-catalog.php; test -w /opt/nrc/site/wp-content/uploads; test ! -e /opt/nrc/site/.env; test ! -e /opt/nrc/site/.local'
    Write-Output 'PASS: Code read-only for PHP user; uploads writable; local secrets absent'
    $detail=Invoke-WebRequest "$url/product/em-06-4f/"
    $imageMatch=[regex]::Match($detail.Content,'src="([^"]*/wp-content/uploads/[^"]+)"')
    Assert-Release ($imageMatch.Success) 'Detail contains uploaded media URL'
    $mediaUrl=[Net.WebUtility]::HtmlDecode($imageMatch.Groups[1].Value)
    $media=Invoke-WebRequest $mediaUrl
    Assert-Release ($media.StatusCode -eq 200) 'Uploaded media is accessible'
    Invoke-Release up -d --no-build --force-recreate --wait --wait-timeout 180
    $again=Invoke-WebRequest "$url/product/em-06-4f/"
    Assert-Release ($again.Content.Contains('RELEASE-OK')) 'Saved product survives container recreation'
    $media=Invoke-WebRequest $mediaUrl
    Assert-Release ($media.StatusCode -eq 200) 'Uploaded media survives container recreation'
    $secondInit=Invoke-Release exec -T --user 33:33 web sh /opt/nrc/initialize.sh
    Assert-Release (($secondInit -join '').Contains('no accounts, content or settings changed')) 'Initialization is non-destructive on an existing site'
    Write-Output 'Production smoke checks completed.'
} finally {
    # This generated project was created solely above; never target a saved site.
    if($project -notmatch '^nrc_release_[a-f0-9]{10}$') { throw 'Refusing unexpected cleanup target' }
    Invoke-Release down --volumes
    Remove-Item -LiteralPath $config
}
