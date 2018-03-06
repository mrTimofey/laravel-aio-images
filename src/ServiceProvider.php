<?php

namespace MrTimofey\LaravelAioImages;

use Illuminate\Support\ServiceProvider as Base;
use Laravel\Lumen\Application as LumenApplication;
use Spatie\ImageOptimizer\OptimizerChain as ImageOptimizer;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ServiceProvider extends Base
{
    public function register(): void
    {
        $this->app->singleton(ImageOptimizer::class, function () {
            return OptimizerChainFactory::create();
        });
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'aio_images');
        $this->publishes([__DIR__ . '/../config.php' => base_path('config/aio_images.php')], 'config');
        $this->publishes([__DIR__ . '/../migrations' => database_path('migrations')], 'migrations');
        if ($this->app instanceof LumenApplication) {
            $this->app->configure('aio_images');
        }
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $config = $this->app->make('config')->get('aio_images');
        if ($config) {
            $router = $this->app->make('router');
            $public = rtrim($config['public_path']);

            $router->get($public . '/{pipe}/{image_id}', [
                'as' => 'aio_images.pipe',
                'middleware' => $config['pipe_middleware'],
                'uses' => 'MrTimofey\LaravelAioImages\ImageController@pipe'
            ]);

            $router->get($public . '/{image_id}', [
                'as' => 'aio_images.original',
                'uses' => 'MrTimofey\LaravelAioImages\ImageController@original'
            ]);

            if (!empty($config['upload_route'])) {
                $router->post($config['upload_route'], [
                    'as' => 'aio_images.upload',
                    'middleware' => $config['upload_middleware'],
                    'uses' => 'MrTimofey\LaravelAioImages\ImageController@upload'
                ]);
            }
        }
    }
}
