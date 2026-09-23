<?php

namespace App\Contracts;

interface ScreenshotProviderInterface
{
    /**
     * Capture high fidelity screenshot of target website.
     * Returns: file_path, sha256, width, height, file_size.
     */
    public function capture(string $url, string $investigationCode): array;
}
