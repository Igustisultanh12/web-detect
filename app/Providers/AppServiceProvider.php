<?php

namespace App\Providers;

use App\Contracts\DnsProviderInterface;
use App\Contracts\DomainProviderInterface;
use App\Contracts\IpIntelligenceProviderInterface;
use App\Contracts\ReputationProviderInterface;
use App\Contracts\ScreenshotProviderInterface;
use App\Contracts\WhatsAppProviderInterface;
use App\Services\Providers\BrowserScreenshotProvider;
use App\Services\Providers\IpIntelligenceProvider;
use App\Services\Providers\MultiSourceReputationProvider;
use App\Services\Providers\OfficialWhatsAppProvider;
use App\Services\Providers\RdapDomainProvider;
use App\Services\Providers\SystemDnsProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DomainProviderInterface::class, RdapDomainProvider::class);
        $this->app->bind(DnsProviderInterface::class, SystemDnsProvider::class);
        $this->app->bind(IpIntelligenceProviderInterface::class, IpIntelligenceProvider::class);
        $this->app->bind(ReputationProviderInterface::class, MultiSourceReputationProvider::class);
        $this->app->bind(ScreenshotProviderInterface::class, BrowserScreenshotProvider::class);
        $this->app->bind(WhatsAppProviderInterface::class, OfficialWhatsAppProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
