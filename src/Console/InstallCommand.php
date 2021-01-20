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
    protected $signature = 'dimension:install {--force}';

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
        copyStubs(__DIR__, false);
    }
}
