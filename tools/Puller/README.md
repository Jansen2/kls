# KLS Puller (.NET 8)

Console app to fetch encrypted submissions from pull_submission.php and decrypt them locally.

## Setup

1) Update appsettings.json with your BaseUrl and secrets.

## Run

```bash
dotnet run
```

## Notes

- When a record is fetched successfully, it is deleted on the server.
- Add your processing logic in Program.cs after decryption.
- Retry and DLQ behavior is controlled via Puller:RetryMaxAttempts, Puller:RetryDelaySeconds, and Puller:DeadLetterDir.
- Batch size is controlled via Puller:BatchMaxCount. Set to 0 to pull until empty, capped by Puller:BatchSafetyMax.
