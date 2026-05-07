param(
    [int] $Port = 8000,
    [string] $HttpdBin = 'C:\xampp\apache\bin\httpd.exe',
    [switch] $StopExisting,
    [switch] $Foreground
)

$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$public = Resolve-Path (Join-Path $root 'public')
$storage = Join-Path $root 'storage'
$logs = Join-Path $storage 'logs'
$confPath = Join-Path $storage 'apache-dev.conf'

New-Item -ItemType Directory -Force -Path $logs | Out-Null

function Convert-ToApachePath([string] $PathValue) {
    return ($PathValue -replace '\\', '/')
}

if (!(Test-Path -LiteralPath $HttpdBin)) {
    throw "Apache httpd was not found at '$HttpdBin'. Install XAMPP or pass -HttpdBin with the Apache httpd.exe path."
}

if ($StopExisting) {
    $listeners = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue

    foreach ($listener in $listeners) {
        if ($listener.OwningProcess -and $listener.OwningProcess -ne 0) {
            Stop-Process -Id $listener.OwningProcess -Force -ErrorAction SilentlyContinue
        }
    }

    Get-CimInstance Win32_Process |
        Where-Object { $_.CommandLine -match 'scripts[\\/]dev-server\.js|php\.exe -S 127\.0\.0\.1:810' } |
        ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
}

$apacheRoot = 'C:/xampp/apache'
$phpRoot = 'C:/xampp/php'
$publicPath = Convert-ToApachePath $public.Path
$errorLog = Convert-ToApachePath (Join-Path $logs 'apache-dev-error.log')
$accessLog = Convert-ToApachePath (Join-Path $logs 'apache-dev-access.log')
$pidFile = Convert-ToApachePath (Join-Path $logs 'apache-dev.pid')

$config = @"
Define SRVROOT "$apacheRoot"
ServerRoot "$apacheRoot"
Listen $Port
ServerName localhost:$Port
PidFile "$pidFile"
EnableMMAP Off
EnableSendfile Off

<IfModule mpm_winnt_module>
    ThreadsPerChild 64
    MaxConnectionsPerChild 0
</IfModule>

LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule dir_module modules/mod_dir.so
LoadModule env_module modules/mod_env.so
LoadModule log_config_module modules/mod_log_config.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadFile "$phpRoot/php8ts.dll"
LoadFile "$phpRoot/libpq.dll"
LoadFile "$phpRoot/libsqlite3.dll"
LoadModule php_module "$phpRoot/php8apache2_4.dll"

PHPINIDir "$phpRoot"
TypesConfig "conf/mime.types"
DirectoryIndex index.php index.html

<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>

<Directory />
    AllowOverride None
    Require all denied
</Directory>

DocumentRoot "$publicPath"
<Directory "$publicPath">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

ErrorLog "$errorLog"
LogFormat "%h %l %u %t \"%r\" %>s %b" common
CustomLog "$accessLog" common
"@

Set-Content -LiteralPath $confPath -Value $config -Encoding ASCII

Set-Location $root

if ($Foreground) {
    & $HttpdBin -f $confPath -DFOREGROUND
    exit $LASTEXITCODE
}

$process = Start-Process -FilePath $HttpdBin -ArgumentList @('-f', $confPath) -WorkingDirectory $root -WindowStyle Hidden -PassThru
Start-Sleep -Milliseconds 800
Write-Output "Apache dev server started at http://localhost:$Port (PID $($process.Id))."
