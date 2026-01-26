<?php

namespace Apo100l\Providers;

use Apo100l\Libraries\OpenSslEncrypter;
use Illuminate\Support\ServiceProvider;

class OpenSslEncryptionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['encrypter'] = $this->app->share(function ($app) {

            $key = $app['config']['app.key'];
            $cipher = $app['config']['app.cipher'];

            if (starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            return new OpenSslEncrypter($key, $cipher);
        });
    }
}