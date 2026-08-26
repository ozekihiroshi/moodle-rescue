param(
    [string]$MoodleEnv = (Join-Path $PSScriptRoot '..\.env'),
    [string]$PythonLabEnv = 'D:\workspace\python-lab-rescue\.env'
)

$bytes = New-Object byte[] 32
$generator = New-Object System.Security.Cryptography.RNGCryptoServiceProvider
try {
    $generator.GetBytes($bytes)
} finally {
    $generator.Dispose()
}
$secret = ([BitConverter]::ToString($bytes)).Replace('-', '').ToLowerInvariant()

function Set-EnvValue([string]$Path, [string]$Name, [string]$Value) {
    if (-not (Test-Path -LiteralPath $Path)) { throw "Environment file not found: $Path" }
    $lines = [System.Collections.Generic.List[string]](Get-Content -LiteralPath $Path)
    $prefix = "$Name="
    $found = $false
    for ($index = 0; $index -lt $lines.Count; $index++) {
        if ($lines[$index].StartsWith($prefix, [StringComparison]::Ordinal)) {
            $lines[$index] = "$prefix$Value"
            $found = $true
        }
    }
    if (-not $found) { $lines.Add(''); $lines.Add("$prefix$Value") }
    [IO.File]::WriteAllLines((Resolve-Path -LiteralPath $Path), $lines, [Text.UTF8Encoding]::new($false))
}

Set-EnvValue $MoodleEnv 'PYTHON_LAB_SUBMIT_SECRET' $secret
Set-EnvValue $MoodleEnv 'PYTHON_LAB_SUBMIT_COURSES' 'PYAI-INTRO,PYAI-INTRO-JA'
Set-EnvValue $PythonLabEnv 'PYTHON_LAB_SUBMIT_SECRET' $secret
Set-EnvValue $PythonLabEnv 'PYTHON_LAB_MOODLE_SUBMIT_URL' 'http://moodle-rescue-local/local/pythonlabsubmit/submit.php'
Write-Output 'Python Lab submission secret configured in both local .env files.'
