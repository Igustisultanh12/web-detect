<?php

namespace App\Services\Security;

use App\Exceptions\SsrfBlockedException;
use Exception;
use Illuminate\Support\Facades\Log;

class SafeHttpClientService
{
    protected SsrfProtectionService $ssrfProtection;

    public function __construct(SsrfProtectionService $ssrfProtection)
    {
        $this->ssrfProtection = $ssrfProtection;
    }

    /**
     * Safely executes an HTTP GET request with low-intensity, strict SSRF inspection,
     * redirect validation, and memory/size bounds.
     */
    public function get(string $url, array $options = []): array
    {
        $validated = $this->ssrfProtection->validateUrl($url);
        $currentUrl = $validated['normalized_url'];

        $maxRedirects = $options['max_redirects'] ?? 3;
        $timeout = $options['timeout'] ?? 10;
        $maxBytes = $options['max_bytes'] ?? 2097152; // 2MB max
        $redirectChain = [];

        $ch = curl_init();
        $redirectCount = 0;

        try {
            while (true) {
                // Validate URL for every hop
                $this->ssrfProtection->validateUrl($currentUrl);
                $redirectChain[] = $currentUrl;

                $headers = [];
                $body = '';

                curl_setopt_array($ch, [
                    CURLOPT_URL => $currentUrl,
                    CURLOPT_RETURNTRANSFER => false,
                    CURLOPT_HEADER => false,
                    CURLOPT_FOLLOWLOCATION => false, // We manually follow and inspect redirects!
                    CURLOPT_AUTOREFERER => false,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 WebGuard/1.0',
                    CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$headers) {
                        $len = strlen($header);
                        $parts = explode(':', $header, 2);
                        if (count($parts) === 2) {
                            $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                        }
                        return $len;
                    },
                    CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$body, $maxBytes) {
                        $chunkLen = strlen($chunk);
                        if (strlen($body) + $chunkLen > $maxBytes) {
                            return 0; // Abort transfer if oversized
                        }
                        $body .= $chunk;
                        return $chunkLen;
                    },
                ]);

                $success = curl_exec($ch);
                $curlError = curl_error($ch);
                $curlErrno = curl_errno($ch);
                $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

                if ($curlErrno === CURLE_WRITE_ERROR && strlen($body) >= $maxBytes) {
                    Log::warning("Response size limit reached for {$currentUrl}");
                } elseif ($curlErrno !== CURLE_OK && empty($body) && $statusCode === 0) {
                    throw new Exception("Koneksi gagal atau waktu habis: {$curlError} ({$curlErrno})");
                }

                // Check for HTTP redirects (301, 302, 303, 307, 308)
                if (in_array($statusCode, [301, 302, 303, 307, 308], true) && !empty($headers['location'])) {
                    $redirectCount++;
                    if ($redirectCount > $maxRedirects) {
                        throw new Exception("Terlalu banyak pengalihan (maksimum {$maxRedirects} redirect).");
                    }

                    $nextLocation = $headers['location'];
                    // Resolve relative URLs
                    if (!preg_match('#^https?://#i', $nextLocation)) {
                        $baseParts = parse_url($currentUrl);
                        $base = ($baseParts['scheme'] ?? 'http') . '://' . ($baseParts['host'] ?? '');
                        if (!empty($baseParts['port'])) {
                            $base .= ':' . $baseParts['port'];
                        }
                        $nextLocation = rtrim($base, '/') . '/' . ltrim($nextLocation, '/');
                    }

                    $currentUrl = $nextLocation;
                    continue;
                }

                // Request completed
                return [
                    'status' => $statusCode,
                    'final_url' => $currentUrl,
                    'redirect_chain' => $redirectChain,
                    'headers' => $headers,
                    'body' => $body,
                    'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: ($headers['content-type'] ?? null),
                    'total_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
                    'ssl_verify_result' => curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT),
                ];
            }
        } finally {
            curl_close($ch);
        }
    }
}
