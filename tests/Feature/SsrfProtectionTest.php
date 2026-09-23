<?php

namespace Tests\Feature;

use App\Exceptions\SsrfBlockedException;
use App\Services\Security\SsrfProtectionService;
use Tests\TestCase;

class SsrfProtectionTest extends TestCase
{
    protected SsrfProtectionService $ssrfService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ssrfService = new SsrfProtectionService();
    }

    /**
     * Test loopback IPs (127.0.0.1, localhost) are blocked.
     */
    public function test_loopback_addresses_are_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('http://127.0.0.1');
    }

    public function test_localhost_hostname_is_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('http://localhost:8080');
    }

    /**
     * Test private RFC 1918 networks are blocked.
     */
    public function test_class_a_private_ip_is_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('http://10.0.0.1');
    }

    public function test_class_b_private_ip_is_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('http://172.16.0.1');
    }

    public function test_class_c_private_ip_is_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('http://192.168.1.1');
    }

    /**
     * Test cloud metadata endpoint (169.254.169.254) is blocked.
     */
    public function test_aws_cloud_metadata_ip_is_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('http://169.254.169.254/latest/meta-data/');
    }

    /**
     * Test IPv6 loopback is blocked.
     */
    public function test_ipv6_loopback_is_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('http://[::1]');
    }

    /**
     * Test decimal IP encoding tricks are blocked.
     */
    public function test_decimal_ip_encoding_is_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('http://2130706433');
    }

    /**
     * Test disallow unsupported protocols.
     */
    public function test_unsupported_protocol_is_blocked(): void
    {
        $this->expectException(SsrfBlockedException::class);
        $this->ssrfService->validateUrl('file:///etc/passwd');
    }

    /**
     * Test public IP validation passes without throwing exception.
     */
    public function test_public_ip_passes_validation(): void
    {
        // 1.1.1.1 is Cloudflare public DNS
        $result = $this->ssrfService->validateUrl('https://1.1.1.1');
        $this->assertEquals('1.1.1.1', $result['host']);
        $this->assertContains('1.1.1.1', $result['resolved_ips']);
    }
}
