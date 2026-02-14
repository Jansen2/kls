param(
    [Parameter(Mandatory = $true)]
    [string]$BaseUrl,

    [Parameter(Mandatory = $true)]
    [string]$ApiKey,

    [string]$Token = "",

    [string]$EncryptionKey = ""
)

$uri = $BaseUrl
if ($Token) {
    if ($uri.Contains("?")) {
        $uri = "$uri&token=$Token"
    } else {
        $uri = "$uri?token=$Token"
    }
}

try {
    $headers = @{ "X-Api-Key" = $ApiKey }
    $response = Invoke-RestMethod -Method Get -Uri $uri -Headers $headers -TimeoutSec 15
} catch {
    Write-Error $_.Exception.Message
    exit 1
}

if (-not $response.success) {
    Write-Error ($response.message | Out-String)
    exit 1
}

Write-Output ("Token: {0}" -f $response.token)

if (-not $response.record -or -not $response.record.payload) {
    Write-Error "Missing payload in response."
    exit 1
}

$payload = $response.record.payload
Write-Output "Encrypted payload received."

if (-not $EncryptionKey) {
    Write-Output "No EncryptionKey provided. Skipping decryption."
    exit 0
}

$aesGcmType = [System.Type]::GetType("System.Security.Cryptography.AesGcm")
if (-not $aesGcmType) {
    Write-Output "AesGcm not available in this PowerShell version. Use PowerShell 7+ to decrypt."
    exit 0
}

try {
    $key = [Convert]::FromBase64String($EncryptionKey)
    $nonce = [Convert]::FromBase64String($payload.nonce)
    $tag = [Convert]::FromBase64String($payload.tag)
    $ciphertext = [Convert]::FromBase64String($payload.ciphertext)
} catch {
    Write-Error "Invalid base64 in key or payload."
    exit 1
}

$plaintext = New-Object byte[] ($ciphertext.Length)
try {
    $aes = New-Object System.Security.Cryptography.AesGcm($key)
    $aes.Decrypt($nonce, $ciphertext, $tag, $plaintext)
    $aes.Dispose()
} catch {
    Write-Error "Decryption failed."
    exit 1
}

$json = [Text.Encoding]::UTF8.GetString($plaintext)
Write-Output "Decrypted payload:"
Write-Output $json
