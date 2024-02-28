<?php

namespace Mindshaker\ImageUpload;

use Illuminate\Support\ServiceProvider;


class ImageUploadServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $configPath = __DIR__ . '/../config/imageupload.php';
        $this->mergeConfigFrom($configPath, 'imageupload');
        $this->app->singleton('imageupload', function () {
            return new ImageUploadManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $configPath = __DIR__ . '/../config/imageupload.php';
        $this->publishes([$configPath => $this->getConfigPath()], 'config');
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path('imageupload.php');
    }
}
