<?php

namespace App\Services\Analysis;

use App\Services\Security\SafeHttpClientService;
use Exception;

class HttpAnalysisService
{
    protected SafeHttpClientService $httpClient;

    public function __construct(SafeHttpClientService $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Conducts safe, non-invasive HTTP/HTTPS metadata inspection.
     */
    public function analyze(string $targetUrl): array
    {
        $normalizedUrl = trim($targetUrl);
        if (!preg_match('#^https?://#i', $normalizedUrl)) {
            $normalizedUrl = 'https://' . $normalizedUrl;
        }

        $httpResult = [
            'http_status' => null,
            'https_available' => false,
            'final_url' => $normalizedUrl,
            'redirect_chain' => [],
            'server_header' => null,
            'content_type' => null,
            'content_length' => null,
            'compression' => null,
            'hsts_header' => null,
            'csp_header' => null,
            'x_frame_options' => null,
            'x_content_type_options' => null,
            'referrer_policy' => null,
            'permissions_policy' => null,
            'cookies_data' => [],
            'security_score' => 0,
            'security_notes' => [],
            'raw_headers' => [],
            'html_body' => '',
        ];

        try {
            $resp = $this->httpClient->get($normalizedUrl, [
                'timeout' => 8,
                'max_redirects' => 4,
            ]);

            $headers = $resp['headers'] ?? [];
            $httpResult['http_status'] = $resp['status'] ?? null;
            $httpResult['final_url'] = $resp['final_url'] ?? $normalizedUrl;
            $httpResult['redirect_chain'] = $resp['redirect_chain'] ?? [];
            $httpResult['raw_headers'] = $headers;
            $httpResult['html_body'] = substr($resp['body'] ?? '', 0, 500000); // 500KB max for technology analysis

            $httpResult['https_available'] = str_starts_with(strtolower($httpResult['final_url']), 'https://');
            $httpResult['server_header'] = $headers['server'] ?? null;
            $httpResult['content_type'] = $headers['content-type'] ?? null;
            $httpResult['content_length'] = isset($headers['content-length']) ? (int) $headers['content-length'] : null;
            $httpResult['compression'] = $headers['content-encoding'] ?? null;

            // Security headers inspection
            $httpResult['hsts_header'] = $headers['strict-transport-security'] ?? null;
            $httpResult['csp_header'] = $headers['content-security-policy'] ?? null;
            $httpResult['x_frame_options'] = $headers['x-frame-options'] ?? null;
            $httpResult['x_content_type_options'] = $headers['x-content-type-options'] ?? null;
            $httpResult['referrer_policy'] = $headers['referrer-policy'] ?? null;
            $httpResult['permissions_policy'] = $headers['permissions-policy'] ?? ($headers['feature-policy'] ?? null);

            // Cookies inspection
            if (!empty($headers['set-cookie'])) {
                $rawCookies = is_array($headers['set-cookie']) ? $headers['set-cookie'] : [$headers['set-cookie']];
                foreach ($rawCookies as $cookieStr) {
                    $parts = explode(';', $cookieStr);
                    $nameVal = explode('=', trim($parts[0]), 2);
                    $cName = $nameVal[0] ?? 'unknown';
                    $hasSecure = false;
                    $hasHttpOnly = false;
                    $sameSite = null;

                    foreach ($parts as $p) {
                        $pTrim = strtolower(trim($p));
                        if ($pTrim === 'secure') $hasSecure = true;
                        if ($pTrim === 'httponly') $hasHttpOnly = true;
                        if (str_starts_with($pTrim, 'samesite=')) {
                            $sameSite = substr($pTrim, 9);
                        }
                    }

                    $httpResult['cookies_data'][] = [
                        'name' => $cName,
                        'secure' => $hasSecure,
                        'httponly' => $hasHttpOnly,
                        'samesite' => $sameSite,
                    ];
                }
            }

            // Calculate Security Score (0 to 100)
            $score = 0;
            $notes = [];

            if ($httpResult['https_available']) {
                $score += 25;
                $notes[] = ['header' => 'HTTPS', 'status' => 'PASS', 'detail' => 'Koneksi diamankan dengan enkripsi HTTPS.'];
            } else {
                $notes[] = ['header' => 'HTTPS', 'status' => 'FAIL', 'detail' => 'Situs tidak menggunakan HTTPS secara default, lalu lintas data tidak terenkripsi.'];
            }

            if (!empty($httpResult['hsts_header'])) {
                $score += 20;
                $notes[] = ['header' => 'HSTS', 'status' => 'PASS', 'detail' => 'Strict-Transport-Security aktif. Mencegah downgrade SSL stripping.'];
            } else {
                $notes[] = ['header' => 'HSTS', 'status' => 'WARN', 'detail' => 'Header HSTS tidak ditemukan. Rekomendasikan penambahan Strict-Transport-Security.'];
            }

            if (!empty($httpResult['csp_header'])) {
                $score += 20;
                $notes[] = ['header' => 'CSP', 'status' => 'PASS', 'detail' => 'Content-Security-Policy terpasang untuk memitigasi serangan Cross-Site Scripting (XSS).'];
            } else {
                $notes[] = ['header' => 'CSP', 'status' => 'WARN', 'detail' => 'Header Content-Security-Policy tidak terpasang. Browser tidak memiliki pembatasan sumber script.'];
            }

            if (!empty($httpResult['x_frame_options'])) {
                $score += 15;
                $notes[] = ['header' => 'X-Frame-Options', 'status' => 'PASS', 'detail' => 'Proteksi clickjacking aktif (' . $httpResult['x_frame_options'] . ').'];
            } else {
                $notes[] = ['header' => 'X-Frame-Options', 'status' => 'WARN', 'detail' => 'X-Frame-Options tidak ditemukan. Berpotensi rentan terhadap penyematan iframe/clickjacking.'];
            }

            if (!empty($httpResult['x_content_type_options'])) {
                $score += 10;
                $notes[] = ['header' => 'X-Content-Type-Options', 'status' => 'PASS', 'detail' => 'MIME-sniffing dinonaktifkan (nosniff).'];
            } else {
                $notes[] = ['header' => 'X-Content-Type-Options', 'status' => 'WARN', 'detail' => 'Header X-Content-Type-Options tidak ditemukan.'];
            }

            if (!empty($httpResult['referrer_policy'])) {
                $score += 10;
                $notes[] = ['header' => 'Referrer-Policy', 'status' => 'PASS', 'detail' => 'Kebijakan perujuk (' . $httpResult['referrer_policy'] . ') terkonfigurasi.'];
            } else {
                $notes[] = ['header' => 'Referrer-Policy', 'status' => 'WARN', 'detail' => 'Referrer-Policy default browser digunakan.'];
            }

            $httpResult['security_score'] = min(100, $score);
            $httpResult['security_notes'] = $notes;

        } catch (Exception $e) {
            $httpResult['security_notes'][] = [
                'header' => 'Error',
                'status' => 'ERROR',
                'detail' => 'Pemeriksaan HTTP mengalami kendala: ' . $e->getMessage(),
            ];
        }

        return $httpResult;
    }
}
