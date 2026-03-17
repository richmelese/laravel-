<?php

namespace Pro;

use Pro\Database\Updaters\Updater101;

class Updater extends \Illuminate\Support\ServiceProvider
{

    public function boot()
    {
        if (!$this->app->runningInConsole() and is_installed()) {
            Updater101::run();
        }
    }
}
