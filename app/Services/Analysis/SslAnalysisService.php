<?php

namespace App\Services\Analysis;

use Exception;
use Illuminate\Support\Facades\Log;

class SslAnalysisService
{
    /**
     * Connect to host via SSL/TLS socket and parse certificate metadata.
     */
    public function analyze(string $host, int $port = 443): array
    {
        $result = [
            'subject_cn' => null,
            'subject_org' => null,
            'issuer_cn' => null,
            'issuer_org' => null,
            'san_list' => [],
            'valid_from' => null,
            'valid_until' => null,
            'is_valid' => false,
            'days_remaining' => null,
            'tls_version' => null,
            'cipher' => null,
            'signature_algorithm' => null,
            'public_key_bits' => null,
            'cert_chain' => [],
            'ct_status' => 'Unknown',
            'error' => null,
        ];

        $g = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ],
        ]);

        $timeout = 8;
        $client = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $g
        );

        if (!$client) {
            $result['error'] = "Gagal terhubung ke port SSL ({$port}): {$errstr} ({$errno})";
            return $result;
        }

        try {
            $params = stream_context_get_params($client);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;
            $chain = $params['options']['ssl']['peer_certificate_chain'] ?? [];

            // Read crypto metadata
            $meta = stream_get_meta_data($client);
            $crypto = $meta['crypto'] ?? [];
            $result['tls_version'] = $crypto['protocol'] ?? 'TLS';
            $result['cipher'] = $crypto['cipher_name'] ?? null;

            if ($cert) {
                $parsed = openssl_x509_parse($cert);

                if ($parsed) {
                    $result['subject_cn'] = $parsed['subject']['CN'] ?? null;
                    $result['subject_org'] = $parsed['subject']['O'] ?? null;
                    $result['issuer_cn'] = $parsed['issuer']['CN'] ?? null;
                    $result['issuer_org'] = $parsed['issuer']['O'] ?? null;
                    $result['signature_algorithm'] = $parsed['signatureTypeSN'] ?? null;

                    if (!empty($parsed['validFrom_time_t'])) {
                        $result['valid_from'] = date('Y-m-d H:i:s', $parsed['validFrom_time_t']);
                    }
                    if (!empty($parsed['validTo_time_t'])) {
                        $result['valid_until'] = date('Y-m-d H:i:s', $parsed['validTo_time_t']);
                        $now = time();
                        $result['is_valid'] = ($now >= $parsed['validFrom_time_t'] && $now <= $parsed['validTo_time_t']);
                        $result['days_remaining'] = (int) round(($parsed['validTo_time_t'] - $now) / 86400);
                    }

                    // Extract SAN (Subject Alternative Names)
                    if (!empty($parsed['extensions']['subjectAltName'])) {
                        $sanParts = explode(',', $parsed['extensions']['subjectAltName']);
                        foreach ($sanParts as $san) {
                            $san = trim($san);
                            if (str_starts_with($san, 'DNS:')) {
                                $result['san_list'][] = substr($san, 4);
                            } else {
                                $result['san_list'][] = $san;
                            }
                        }
                    }

                    // Public key info
                    $pubKey = openssl_pkey_get_public($cert);
                    if ($pubKey) {
                        $keyDetails = openssl_pkey_get_details($pubKey);
                        $result['public_key_bits'] = $keyDetails['bits'] ?? null;
                    }

                    // Certificate Transparency SCT extension presence
                    if (isset($parsed['extensions']['sctList']) || isset($parsed['extensions']['1.3.6.1.4.1.11129.2.4.2'])) {
                        $result['ct_status'] = 'Embedded SCTs Present (RFC 6962)';
                    } else {
                        $result['ct_status'] = 'Standard Public Certificate';
                    }
                }
            }

            // Parse chain
            if (!empty($chain)) {
                foreach ($chain as $chainCert) {
                    $chainParsed = openssl_x509_parse($chainCert);
                    if ($chainParsed) {
                        $result['cert_chain'][] = [
                            'subject' => $chainParsed['subject']['CN'] ?? ($chainParsed['name'] ?? 'Unknown'),
                            'issuer' => $chainParsed['issuer']['CN'] ?? ($chainParsed['issuer']['O'] ?? 'Unknown'),
                            'valid_to' => date('Y-m-d H:i:s', $chainParsed['validTo_time_t'] ?? 0),
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            Log::info("SSL analysis error on {$host}: " . $e->getMessage());
            $result['error'] = $e->getMessage();
        } finally {
            fclose($client);
        }

        return $result;
    }
}
