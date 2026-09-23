<?php

namespace App\Services\Analysis;

class TechnologyDetectionService
{
    /**
     * Passively fingerprints technologies from response headers, cookies, and HTML markup.
     */
    public function detect(array $headers, array $cookies, string $html): array
    {
        $technologies = [];
        $headersLower = array_change_key_case($headers, CASE_LOWER);
        $cookieNames = array_map(fn($c) => strtolower($c['name'] ?? ''), $cookies);
        $serverHeader = strtolower($headersLower['server'] ?? '');
        $poweredBy = strtolower($headersLower['x-powered-by'] ?? '');

        // 1. Web Server
        if (str_contains($serverHeader, 'nginx')) {
            $version = preg_match('#nginx/([\d.]+)#i', $serverHeader, $m) ? $m[1] : null;
            $technologies[] = ['category' => 'Web Server', 'name' => 'Nginx', 'version' => $version, 'confidence' => 100, 'matched_pattern' => 'Server: nginx', 'icon' => 'server'];
        } elseif (str_contains($serverHeader, 'apache')) {
            $version = preg_match('#apache/([\d.]+)#i', $serverHeader, $m) ? $m[1] : null;
            $technologies[] = ['category' => 'Web Server', 'name' => 'Apache HTTP Server', 'version' => $version, 'confidence' => 100, 'matched_pattern' => 'Server: apache', 'icon' => 'server'];
        } elseif (str_contains($serverHeader, 'caddy')) {
            $technologies[] = ['category' => 'Web Server', 'name' => 'Caddy', 'version' => null, 'confidence' => 100, 'matched_pattern' => 'Server: caddy', 'icon' => 'server'];
        } elseif (str_contains($serverHeader, 'microsoft-iis') || str_contains($serverHeader, 'iis')) {
            $version = preg_match('#microsoft-iis/([\d.]+)#i', $serverHeader, $m) ? $m[1] : null;
            $technologies[] = ['category' => 'Web Server', 'name' => 'Microsoft IIS', 'version' => $version, 'confidence' => 100, 'matched_pattern' => 'Server: microsoft-iis', 'icon' => 'server'];
        } elseif (str_contains($serverHeader, 'cloudflare')) {
            $technologies[] = ['category' => 'CDN / Reverse Proxy', 'name' => 'Cloudflare', 'version' => null, 'confidence' => 100, 'matched_pattern' => 'Server: cloudflare', 'icon' => 'cloud'];
        }

        // 2. Backend Language
        if (str_contains($poweredBy, 'php') || in_array('phpsessid', $cookieNames, true)) {
            $version = preg_match('#php/([\d.]+)#i', $poweredBy, $m) ? $m[1] : null;
            $technologies[] = ['category' => 'Backend', 'name' => 'PHP', 'version' => $version, 'confidence' => 95, 'matched_pattern' => 'PHP header/session cookie', 'icon' => 'code'];
        } elseif (str_contains($poweredBy, 'asp.net') || in_array('asp.net_sessionid', $cookieNames, true)) {
            $technologies[] = ['category' => 'Backend', 'name' => 'ASP.NET', 'version' => null, 'confidence' => 95, 'matched_pattern' => 'ASP.NET header/cookie', 'icon' => 'code'];
        } elseif (str_contains($poweredBy, 'express') || str_contains($poweredBy, 'next.js')) {
            $technologies[] = ['category' => 'Backend', 'name' => 'Node.js', 'version' => null, 'confidence' => 90, 'matched_pattern' => 'X-Powered-By: Express/Next', 'icon' => 'code'];
        }

        // 3. Framework
        $hasLaravelCookie = false;
        foreach ($cookieNames as $cn) {
            if (str_contains($cn, 'laravel') || str_contains($cn, 'xsrf-token')) {
                $hasLaravelCookie = true;
            }
        }
        if ($hasLaravelCookie || str_contains($html, 'laravel') || str_contains($html, 'csrf-token')) {
            $technologies[] = ['category' => 'Framework', 'name' => 'Laravel', 'version' => null, 'confidence' => 90, 'matched_pattern' => 'CSRF token / session cookie structure', 'icon' => 'layers'];
        }

        if (str_contains($html, '__NEXT_DATA__') || str_contains($poweredBy, 'next.js')) {
            $technologies[] = ['category' => 'Framework', 'name' => 'Next.js', 'version' => null, 'confidence' => 95, 'matched_pattern' => '__NEXT_DATA__ marker', 'icon' => 'layers'];
        }

        if (str_contains($html, '__NUXT__')) {
            $technologies[] = ['category' => 'Framework', 'name' => 'Nuxt.js', 'version' => null, 'confidence' => 95, 'matched_pattern' => '__NUXT__ state hydration', 'icon' => 'layers'];
        }

        // 4. CMS (Content Management System)
        if (str_contains($html, '/wp-content/') || str_contains($html, '/wp-includes/') || in_array('wordpress_test_cookie', $cookieNames, true)) {
            $version = null;
            if (preg_match('#content="WordPress ([\d.]+)"#i', $html, $m)) {
                $version = $m[1];
            }
            $technologies[] = ['category' => 'CMS', 'name' => 'WordPress', 'version' => $version, 'confidence' => 100, 'matched_pattern' => '/wp-content/ /wp-includes/ path structure', 'icon' => 'file-text'];
        } elseif (str_contains($html, 'Joomla!') || str_contains($html, '/media/jui/')) {
            $technologies[] = ['category' => 'CMS', 'name' => 'Joomla', 'version' => null, 'confidence' => 90, 'matched_pattern' => 'Joomla marker in source', 'icon' => 'file-text'];
        } elseif (str_contains($html, 'Drupal.settings') || str_contains($html, 'drupal.js')) {
            $technologies[] = ['category' => 'CMS', 'name' => 'Drupal', 'version' => null, 'confidence' => 90, 'matched_pattern' => 'Drupal JS globals', 'icon' => 'file-text'];
        }

        // 5. Frontend Libraries
        if (str_contains($html, 'vue') || str_contains($html, 'data-v-') || str_contains($html, '__vue_app__')) {
            $technologies[] = ['category' => 'Frontend', 'name' => 'Vue.js', 'version' => null, 'confidence' => 85, 'matched_pattern' => 'Vue scoped CSS / app attribute', 'icon' => 'layout'];
        }

        if (str_contains($html, 'react') || str_contains($html, 'data-reactroot')) {
            $technologies[] = ['category' => 'Frontend', 'name' => 'React', 'version' => null, 'confidence' => 85, 'matched_pattern' => 'React root identifier in DOM', 'icon' => 'layout'];
        }

        if (str_contains($html, 'tailwindcss') || str_contains($html, 'tailwind') || preg_match('#class="[^"]*(flex|grid|p-\d|m-\d|text-\w+-\d+)#', $html)) {
            $technologies[] = ['category' => 'UI Framework', 'name' => 'Tailwind CSS', 'version' => null, 'confidence' => 80, 'matched_pattern' => 'Tailwind utility classes', 'icon' => 'feather'];
        }

        if (str_contains($html, 'bootstrap.min.css') || str_contains($html, 'bootstrap.bundle')) {
            $technologies[] = ['category' => 'UI Framework', 'name' => 'Bootstrap', 'version' => null, 'confidence' => 90, 'matched_pattern' => 'Bootstrap CSS/bundle link', 'icon' => 'feather'];
        }

        if (str_contains($html, 'jquery.min.js') || str_contains($html, 'jquery-')) {
            $technologies[] = ['category' => 'JavaScript Library', 'name' => 'jQuery', 'version' => null, 'confidence' => 90, 'matched_pattern' => 'jQuery script include', 'icon' => 'code'];
        }

        // 6. Analytics
        if (str_contains($html, 'googletagmanager.com') || str_contains($html, 'gtag(')) {
            $technologies[] = ['category' => 'Analytics', 'name' => 'Google Tag Manager / Analytics', 'version' => null, 'confidence' => 95, 'matched_pattern' => 'GTM script tag or gtag() call', 'icon' => 'bar-chart'];
        }

        if (str_contains($html, 'connect.facebook.net') || str_contains($html, 'fbq(')) {
            $technologies[] = ['category' => 'Analytics', 'name' => 'Meta (Facebook) Pixel', 'version' => null, 'confidence' => 95, 'matched_pattern' => 'fbq script invocation', 'icon' => 'bar-chart'];
        }

        return $technologies;
    }
}
