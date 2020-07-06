<?php

namespace Haxibiao\Dimension\Console;

use Illuminate\Console\Command;

class ArchiveAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:all {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天凌晨归档所有维度(新增，留存，广告，提现，变现)';

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
        $date = $this->option('date') ?? today()->toDateString();

        //每日归档新用户数据
        $this->call("archive:user", ['--date' => $date, '--newuser' => true]);

        //每日归档留存率
        $this->call("archive:retention", ['--date' => $date]);

        //每日归档提现数据
        // $this->call("archive:withdraw", ['--date' => $date]);
        $this->call("archive:withdraw", ['--date' => $date, 'type' => 'like']);
        $this->call("archive:withdraw", ['--date' => $date, 'type' => 'post']);

    }

}
