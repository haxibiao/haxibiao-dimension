<?php

namespace Haxibiao\Dimension\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dimension:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '安装 haxibiao-dimension';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("复制 stubs ...");
        $this->copyStubs();
    }

    public function copyStubs()
    {
        //复制所有app stubs
        foreach (glob(__DIR__ . '/stubs/*.stub') as $filepath) {
            $filename = basename($filepath);
            copy($filepath, app_path(str_replace(".stub", ".php", $filename)));
        }
        //复制所有nova stubs
        if (!is_dir(app_path('Nova'))) {
            mkdir(app_path('Nova'));
        }
        foreach (glob(__DIR__ . '/stubs/Nova/*.stub') as $filepath) {
            $filename = basename($filepath);
            copy($filepath, app_path('Nova/' . str_replace(".stub", ".php", $filename)));
        }
    }
}
