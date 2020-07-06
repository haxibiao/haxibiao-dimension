<?php

namespace Haxibiao\Dimension;

use Haxibiao\Dimension\Console\ArchiveAll;
use Haxibiao\Dimension\Console\ArchiveRetention;
use Haxibiao\Dimension\Console\ArchiveUser;
use Haxibiao\Dimension\Console\ArchiveWithdraw;
use Illuminate\Support\ServiceProvider;

class DimensionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        // Register Commands
        $this->commands([
            InstallCommand::class,
            ArchiveAll::class,
            ArchiveRetention::class,
            ArchiveUser::class,
            ArchiveWithdraw::class,
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
