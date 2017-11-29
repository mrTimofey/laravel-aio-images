<?php

namespace MrTimofey\LaravelAioImages;

use Illuminate\Support\ServiceProvider as Base;

class ServiceProvider extends Base
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'aio_images');
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/config.php' => config_path('aio_images.php')]);
        $this->loadMigrationsFrom(__DIR__ . '../migrations');
        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        $router = $this->app->make('router');
        $config = $this->app->make('config')->get('aio_images');
        $public = rtrim($config['public_path']);

        $router->get($public . '/{pipe}/{image_id}',
            'MrTimofey\LaravelAioImages\ImageController@pipe')
            ->middleware($config['pipe_middleware'])
            ->name('aio_images.pipe');

        $router->get($public . '/{image_id}',
            'MrTimofey\LaravelAioImages\ImageController@original')
            ->name('aio_images.original');

        if (!empty($config['upload_route'])) {
            $router->post($config['upload_route'], 'MrTimofey\LaravelAioImages\ImageController@upload')
                ->middleware($config['upload_middleware'])
                ->name('aio_images.upload');
        }
    }
}
