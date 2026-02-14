using System.Net;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using Microsoft.Extensions.Configuration;

var config = new ConfigurationBuilder()
    .SetBasePath(AppContext.BaseDirectory)
    .AddJsonFile("appsettings.json", optional: true)
    .AddEnvironmentVariables()
    .Build();

var options = new PullerOptions
{
    BaseUrl = config["Puller:BaseUrl"] ?? string.Empty,
    ApiKey = config["Puller:ApiKey"] ?? string.Empty,
    EncryptionKey = config["Puller:EncryptionKey"] ?? string.Empty,
    PollIntervalSeconds = GetInt(config, "Puller:PollIntervalSeconds", 15),
    TimeoutSeconds = GetInt(config, "Puller:TimeoutSeconds", 15),
    UsePolling = GetBool(config, "Puller:UsePolling", true),
    BatchMaxCount = GetInt(config, "Puller:BatchMaxCount", 20),
    BatchSafetyMax = GetInt(config, "Puller:BatchSafetyMax", 200),
    RetryMaxAttempts = GetInt(config, "Puller:RetryMaxAttempts", 3),
    RetryDelaySeconds = GetInt(config, "Puller:RetryDelaySeconds", 5),
    DeadLetterDir = config["Puller:DeadLetterDir"] ?? "./dlq"
};
if (string.IsNullOrWhiteSpace(options.BaseUrl))
{
    Console.Error.WriteLine("Missing Puller:BaseUrl");
    return 1;
}

if (string.IsNullOrWhiteSpace(options.ApiKey))
{
    Console.Error.WriteLine("Missing Puller:ApiKey");
    return 1;
}

if (string.IsNullOrWhiteSpace(options.EncryptionKey))
{
    Console.Error.WriteLine("Missing Puller:EncryptionKey");
    return 1;
}

byte[] key;
try
{
    key = Convert.FromBase64String(options.EncryptionKey);
}
catch (FormatException)
{
    Console.Error.WriteLine("Puller:EncryptionKey is not valid base64");
    return 1;
}

if (key.Length != 32)
{
    Console.Error.WriteLine("Puller:EncryptionKey must be 32 bytes (base64-encoded)");
    return 1;
}

using var httpClient = new HttpClient
{
    Timeout = TimeSpan.FromSeconds(options.TimeoutSeconds)
};

EnsureDeadLetterDir(options.DeadLetterDir);

var cts = new CancellationTokenSource();
Console.CancelKeyPress += (_, e) =>
{
    e.Cancel = true;
    cts.Cancel();
};

if (options.UsePolling)
{
    Console.WriteLine("Polling enabled. Press Ctrl+C to stop.");
    while (!cts.IsCancellationRequested)
    {
        var result = await RunBatchAsync(httpClient, options, key, cts.Token);
        if (result == FetchResult.NoData)
        {
            await Task.Delay(TimeSpan.FromSeconds(options.PollIntervalSeconds), cts.Token);
        }
    }
}
else
{
    await RunBatchAsync(httpClient, options, key, cts.Token);
}

return 0;

static int GetInt(IConfiguration config, string key, int defaultValue)
{
    return int.TryParse(config[key], out var value) ? value : defaultValue;
}

static bool GetBool(IConfiguration config, string key, bool defaultValue)
{
    return bool.TryParse(config[key], out var value) ? value : defaultValue;
}

static async Task<FetchResult> FetchWithRetryAsync(HttpClient httpClient, PullerOptions options, byte[] key, CancellationToken ct)
{
    var attempts = 0;
    while (true)
    {
        attempts++;
        var (result, retryable) = await FetchOnceAsync(httpClient, options, key, ct);
        if (result != FetchResult.Error || !retryable)
        {
            return result;
        }

        if (attempts >= options.RetryMaxAttempts)
        {
            Console.Error.WriteLine($"Retry limit reached ({options.RetryMaxAttempts}).");
            return FetchResult.Error;
        }

        Console.WriteLine($"Retrying in {options.RetryDelaySeconds}s (attempt {attempts}/{options.RetryMaxAttempts})...");
        await Task.Delay(TimeSpan.FromSeconds(options.RetryDelaySeconds), ct);
    }
}

static async Task<FetchResult> RunBatchAsync(HttpClient httpClient, PullerOptions options, byte[] key, CancellationToken ct)
{
    var processed = 0;
    var safetyMax = options.BatchSafetyMax <= 0 ? 1 : options.BatchSafetyMax;
    var maxCount = options.BatchMaxCount <= 0 ? safetyMax : options.BatchMaxCount;

    if (options.BatchMaxCount <= 0)
    {
        Console.WriteLine($"Batch mode: run until empty (safety max {maxCount}).");
    }
    else
    {
        Console.WriteLine($"Batch mode: max {maxCount} records.");
    }

    while (processed < maxCount)
    {
        var result = await FetchWithRetryAsync(httpClient, options, key, ct);
        if (result == FetchResult.NoData)
        {
            Console.WriteLine($"Batch completed: {processed} of {maxCount} processed (no more data).");
            break;
        }

        if (result == FetchResult.Error)
        {
            return FetchResult.Error;
        }

        processed++;
        Console.WriteLine($"Progress: {processed} von {maxCount} verarbeitet.");
    }

    return processed == 0 ? FetchResult.NoData : FetchResult.Ok;
}

static async Task<(FetchResult Result, bool Retryable)> FetchOnceAsync(HttpClient httpClient, PullerOptions options, byte[] key, CancellationToken ct)
{
    using var request = new HttpRequestMessage(HttpMethod.Get, options.BaseUrl);
    request.Headers.Add("X-Api-Key", options.ApiKey);

    using var response = await httpClient.SendAsync(request, ct);

    if (response.StatusCode == HttpStatusCode.NotFound)
    {
        Console.WriteLine("No data available.");
        return (FetchResult.NoData, false);
    }

    if (response.StatusCode == HttpStatusCode.Forbidden)
    {
        Console.Error.WriteLine("Forbidden: check API key.");
        return (FetchResult.Error, false);
    }

    if (!response.IsSuccessStatusCode)
    {
        Console.Error.WriteLine($"Request failed: {(int)response.StatusCode} {response.ReasonPhrase}");
        return (FetchResult.Error, true);
    }

    var content = await response.Content.ReadAsStringAsync(ct);
    var apiResponse = JsonSerializer.Deserialize<ApiResponse>(content, new JsonSerializerOptions
    {
        PropertyNameCaseInsensitive = true
    });

    if (apiResponse?.Success != true || apiResponse.Record?.Payload == null)
    {
        Console.Error.WriteLine("Invalid response payload.");
        return (FetchResult.Error, false);
    }

    var payload = apiResponse.Record.Payload;

    byte[] nonce;
    byte[] tag;
    byte[] ciphertext;

    try
    {
        nonce = Convert.FromBase64String(payload.Nonce ?? string.Empty);
        tag = Convert.FromBase64String(payload.Tag ?? string.Empty);
        ciphertext = Convert.FromBase64String(payload.Ciphertext ?? string.Empty);
    }
    catch (FormatException)
    {
        Console.Error.WriteLine("Invalid base64 in payload.");
        return (FetchResult.Error, false);
    }

    byte[] plaintext = new byte[ciphertext.Length];
    try
    {
        using var aes = new AesGcm(key, 16);
        aes.Decrypt(nonce, ciphertext, tag, plaintext);
    }
    catch (CryptographicException)
    {
        Console.Error.WriteLine("Decryption failed.");
        return (FetchResult.Error, false);
    }

    var json = Encoding.UTF8.GetString(plaintext);
    using var doc = JsonDocument.Parse(json);
    var root = doc.RootElement;
    var waitingNumber = root.TryGetProperty("waiting_number", out var wn) ? wn.GetString() : "";

    Console.WriteLine($"Received record token={apiResponse.Token} waiting_number={waitingNumber}");

    try
    {
        var processed = await ProcessRecordAsync(json, apiResponse.Token ?? string.Empty, ct);
        if (!processed)
        {
            WriteDeadLetter(options.DeadLetterDir, apiResponse.Token, json, "Processing returned false");
        }
    }
    catch (Exception ex)
    {
        WriteDeadLetter(options.DeadLetterDir, apiResponse.Token, json, ex.Message);
        return (FetchResult.Error, false);
    }

    return (FetchResult.Ok, false);
}

static Task<bool> ProcessRecordAsync(string jsonPayload, string token, CancellationToken ct)
{
    _ = jsonPayload;
    _ = token;
    _ = ct;
    return Task.FromResult(true);
}

static void EnsureDeadLetterDir(string dir)
{
    if (string.IsNullOrWhiteSpace(dir))
    {
        return;
    }

    if (!Directory.Exists(dir))
    {
        Directory.CreateDirectory(dir);
    }
}

static void WriteDeadLetter(string dir, string? token, string jsonPayload, string reason)
{
    if (string.IsNullOrWhiteSpace(dir))
    {
        return;
    }

    try
    {
        EnsureDeadLetterDir(dir);
        var record = new
        {
            token = token ?? string.Empty,
            reason,
            payload = JsonDocument.Parse(jsonPayload).RootElement,
            createdAtUtc = DateTime.UtcNow
        };

        var fileName = $"dlq-{DateTime.UtcNow:yyyyMMdd-HHmmss}-{Guid.NewGuid():N}.json";
        var path = Path.Combine(dir, fileName);
        var json = JsonSerializer.Serialize(record, new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText(path, json);
        Console.Error.WriteLine($"Dead-lettered record to {path}");
    }
    catch (Exception ex)
    {
        Console.Error.WriteLine($"Failed to write dead-letter: {ex.Message}");
    }
}

sealed class PullerOptions
{
    public string BaseUrl { get; set; } = string.Empty;
    public string ApiKey { get; set; } = string.Empty;
    public string EncryptionKey { get; set; } = string.Empty;
    public int PollIntervalSeconds { get; set; } = 15;
    public int TimeoutSeconds { get; set; } = 15;
    public bool UsePolling { get; set; } = true;
    public int BatchMaxCount { get; set; } = 20;
    public int BatchSafetyMax { get; set; } = 200;
    public int RetryMaxAttempts { get; set; } = 3;
    public int RetryDelaySeconds { get; set; } = 5;
    public string DeadLetterDir { get; set; } = "./dlq";
}

sealed class ApiResponse
{
    public bool Success { get; set; }
    public string? Token { get; set; }
    public ExportRecord? Record { get; set; }
}

sealed class ExportRecord
{
    public string? Token { get; set; }
    public long Created_At { get; set; }
    public long Expires_At { get; set; }
    public ExportPayload? Payload { get; set; }
}

sealed class ExportPayload
{
    public string? Alg { get; set; }
    public string? Nonce { get; set; }
    public string? Tag { get; set; }
    public string? Ciphertext { get; set; }
}

enum FetchResult
{
    Ok,
    NoData,
    Error
}
