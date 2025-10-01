<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class FileSystemServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override the MIME type detector to avoid finfo dependency
        $this->app->singleton('mime-type-detector', function () {
            return new ExtensionMimeTypeDetector();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
