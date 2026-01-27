<?php
namespace Apo100l\Providers;

use Apo100l\Cms;
use Illuminate\Support\ServiceProvider;

class SdkServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app['cms.sdk'] = $this->app->share(function () {
            return new Cms();
        });

        $this->app->alias('cms.sdk', 'Apo100l\Cms');
    }

    public function boot()
    {
        $this->package('apo100l/cms-kit');
    }
}