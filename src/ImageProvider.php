<?php

namespace MrTimofey\LaravelAioImages;

use Illuminate\Support\ServiceProvider as Base;

class ImageProvider extends Base
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'aio_images');
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/config.php' => config_path('aio_images.php')]);
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        $router = $this->app->make('router');
        $config = $this->app->make('config')->get('aio_images');

        $router->get(rtrim($config['public_path']) .'/{pipe}/{path}', 'MrTimofey\LaravelAioImages\ImageController@generate')
            ->middleware($config['generate_middleware'])
            ->name('aio_images.generate');
        if (!empty($config['upload_route'])) {
            $router->post($config['upload_route'], 'MrTimofey\LaravelAioImages\ImageController@upload')
                ->middleware($config['upload_middleware'])
                ->name('aio_images.upload');
        }
    }
}
