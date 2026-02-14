# Test API Script (PowerShell)

This script calls the pull_submission.php endpoint and optionally decrypts the payload.

## Requirements

- PowerShell 7+ for decryption (AesGcm).
- API key for the webapp endpoint.

## Usage

```powershell
./test_api.ps1 -BaseUrl "https://dummy.com/pull_submission.php" -ApiKey "YOUR_KEY"
```

With decryption:

```powershell
./test_api.ps1 -BaseUrl "https://dummy.com/pull_submission.php" -ApiKey "YOUR_KEY" -EncryptionKey "BASE64_KEY"
```

Fetch a specific token:

```powershell
./test_api.ps1 -BaseUrl "https://dummy.com/pull_submission.php" -ApiKey "YOUR_KEY" -Token "TOKEN"
```

## Notes

- The server deletes the record after a successful fetch.
- If you skip EncryptionKey, the script only verifies that the API is reachable.
