<?php

namespace App\Providers;

use App\Services\PdamSoapService;
use App\Services\WhatsAppService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Daftarkan kedua service sebagai singleton agar tidak di-instantiate ulang
        // per request; SoapClient tetap dibuat baru setiap call di dalam service.
        $this->app->singleton(PdamSoapService::class);
        $this->app->singleton(WhatsAppService::class);
    }

    public function boot(): void
    {
        //
    }
}
