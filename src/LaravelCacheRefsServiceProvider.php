<?php

namespace Aprivette\LaravelCacheRefs;

use Illuminate\Support\ServiceProvider;

class LaravelCacheRefsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PurgeCacheRefsCommand::class,
            ]);
        }
    }
}
