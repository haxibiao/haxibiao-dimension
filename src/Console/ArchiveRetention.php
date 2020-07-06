<?php

namespace Haxibiao\Dimension\Console;

use App\User;
use App\UserRetention;
use Carbon\Carbon;
use Haxibiao\Dimension\Dimension;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveRetention extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:retention {--date=} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '按日归档用户留存信息';

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
     * @param $intervalHours 统计时间
     *
     * @return mixed
     */
    public function handle()
    {
        //注意：留存都是看前一天的
        $date = $this->option('date') ?? today()->toDateString();
        $type = $this->option('type');
        if ($type == 'retention') {
            return $this->calculateRetention($date);
        }
        if ($type == 'lost') {
            return $this->secondDayLostUser($date);
        }
        if ($type == 'keep') {
            return $this->secondDayKeepUser($date);
        }

        $this->info("维度归档统计: 统计留存数据 ..." . $date);
        $this->calculateRetention($date);

        $this->info("维度归档统计: 次日流失用户 ..." . $date);
        $this->secondDayLostUser($date);

        $this->info("维度归档统计: 次日留存用户 ..." . $date);
        $this->secondDayKeepUser($date);
    }

    /**
     * 缓存留存率统计信息
     */
    public function calculateRetention(String $date)
    {
        //注意：留存都是看前一天的
        echo "\n缓存留存率统计信息";
        $endOfDay = Carbon::parse($date)->subDay(1);

        //次日留存率
        echo "\n - 次日留存率 ";
        $date         = clone $endOfDay;
        $next_day_key = 'day2_at_' . $date->toDateString();
        if (!cache()->store('database')->get($next_day_key)) {
            $next_day_key_registed_at = $date->subDay(1);
            $newRegistedNum           = User::whereDate('created_at', $next_day_key_registed_at)->count();
            $userRetentionNum         = UserRetention::whereDate('day2_at', $next_day_key_registed_at)->count();
            $next_day_result          = sprintf('%.2f', ($userRetentionNum / $newRegistedNum) * 100);
            cache()->store('database')->forever($next_day_key, $next_day_result);
            echo $next_day_result;
        }

        //三日留存率
        echo "\n - 三日留存率 ";

        $date          = clone $endOfDay;
        $third_day_key = 'day3_at_' . $date->toDateString();
        if (!cache()->store('database')->get($third_day_key)) {
            $third_day_registed_at = $date->subDay(3);
            $newRegistedNum        = User::whereDate('created_at', $third_day_registed_at)->count();
            $userRetentionNum      = UserRetention::whereDate('day3_at', $third_day_registed_at)->count();
            $third_day_result      = sprintf('%.2f', ($userRetentionNum / $newRegistedNum) * 100);
            cache()->store('database')->forever($third_day_key, $third_day_result);
            echo $third_day_result;
        }

        //五日留存率
        echo "\n - 五日留存率 ";

        $date          = clone $endOfDay;
        $fifth_day_key = 'day5_at_' . $date->toDateString();
        if (!cache()->store('database')->get($fifth_day_key)) {
            $fifth_day_registed_at = $date->subDay(5);
            $newRegistedNum        = User::whereDate('created_at', $fifth_day_registed_at)->count();
            $userRetentionNum      = UserRetention::whereDate('day5_at', $fifth_day_registed_at)->count();
            $fifth_day_result      = sprintf('%.2f', ($userRetentionNum / $newRegistedNum) * 100);
            cache()->store('database')->forever($fifth_day_key, $fifth_day_result);
            echo $fifth_day_result;
        }

        // 七日留存率
        echo "\n - 七日留存率 ";

        $date          = clone $endOfDay;
        $sixth_day_key = 'day7_at_' . $date->toDateString();
        if (!cache()->store('database')->get($sixth_day_key)) {
            $sixth_day_registed_at = $date->subDay(7);
            $newRegistedNum        = User::whereDate('created_at', $sixth_day_registed_at)->count();
            $userRetentionNum      = UserRetention::whereDate('day7_at', $sixth_day_registed_at)->count();
            $sixth_day_result      = sprintf('%.2f', ($userRetentionNum / $newRegistedNum) * 100);
            cache()->store('database')->forever($sixth_day_key, $sixth_day_result);
            echo $sixth_day_result;
        }

        //三十日留存率
        echo "\n - 三十日留存率 ";
        $date      = clone $endOfDay;
        $month_key = 'day30_at_' . $date->toDateString();
        if (!cache()->store('database')->get($month_key)) {
            $month_registed_at = $date->subDay(30);
            $newRegistedNum    = User::whereDate('created_at', $month_registed_at)->count();
            $userRetentionNum  = UserRetention::whereDate('day30_at', $month_registed_at)->count();
            $month_result      = sprintf('%.2f', ($userRetentionNum / $newRegistedNum) * 100);
            cache()->store('database')->forever($month_key, $month_result);
            echo $month_result;
        }
    }

    /**
     * 计算次日流失用户信息，如平均智慧点  (每日凌晨统计，前一日的)
     *
     * @return void
     */
    public function secondDayLostUser(String $date)
    {
        //注意：留存都是看前一天的
        $day   = Carbon::parse($date);
        $day1  = (clone $day)->subDay(1)->toDateString();
        $day2  = (clone $day)->subDay(2)->toDateString();
        $dates = [$day2, $day1];

        $qb_new_users = DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('user_retentions', 'users.id', '=', 'user_retentions.user_id')
            ->whereBetween('users.created_at', $dates);

        //次日流失的逻辑应该基于留存记录
        $qb_second_day = $qb_new_users->whereNull('user_retentions.day2_at');

        $partitions = $qb_second_day->groupBy('user_profiles.source')->selectRaw('count(*) as num, source');
        foreach ($partitions->get() as $part) {
            $dimension = Dimension::firstOrNew([
                'group' => '次日流失用户分布',
                'name'  => $part->source,
                'date'  => $date,
            ]);
            $dimension->value = $part->num;
            $dimension->save();
            echo '次日流失用户分布 - :' . $part->source . '  ' . $part->num . "\n";
        }

        $avgGold   = $qb_second_day->avg('gold') ?? 0;
        $dimension = Dimension::firstOrNew([
            'group' => '次日流失用户',
            'name'  => '平均智慧点',
            'date'  => $date,
        ]);
        $dimension->value = $avgGold;
        $dimension->save();
        echo '次日流失用户 - 平均智慧点:' . $avgGold . ' 日期:' . $date . "\n";

        $avg_answers_count = $qb_second_day->avg('user_profiles.answers_count') ?? 0;
        $dimension         = Dimension::firstOrNew([
            'group' => '次日流失用户',
            'name'  => '平均答题数',
            'date'  => $date,
        ]);
        $dimension->value = $avg_answers_count;
        $dimension->save();
        echo '次日流失用户 - 平均答题数:' . $avg_answers_count . ' 日期:' . $date . "\n";

        $max_answers_count = $qb_second_day->max('user_profiles.answers_count') ?? 0;
        $dimension         = Dimension::firstOrNew([
            'group' => '次日流失用户',
            'name'  => '最高答题数',
            'date'  => $date,
        ]);
        $dimension->value = $max_answers_count;
        $dimension->save();
        echo '次日流失用户 - 最高答题数:' . $max_answers_count . ' 日期:' . $date . "\n";

    }

    /**
     * 计算次日流失用户信息，如平均智慧点 (每日凌晨统计，前一日的)
     *
     * @return void
     */
    public function secondDayKeepUser(String $date)
    {
        //注意：留存都是看前一天的
        $day   = Carbon::parse($date);
        $day1  = (clone $day)->subDay(1)->toDateString();
        $day2  = (clone $day)->subDay(2)->toDateString();
        $dates = [$day2, $day1];

        $qb_new_users = DB::table('users')
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->leftJoin('user_retentions', 'users.id', '=', 'user_retentions.user_id')
            ->whereBetween('users.created_at', $dates);

        //次日留存的逻辑应该基于留存记录
        $qb_second_day = $qb_new_users
            ->whereNotNull('user_retentions.day2_at');

        $partitions = $qb_second_day->groupBy('user_profiles.source')
            ->selectRaw('count(*) as num, source');
        foreach ($partitions->get() as $part) {
            $dimension = Dimension::firstOrNew([
                'group' => '次日留存用户分布',
                'name'  => $part->source,
                'date'  => $date,
            ]);
            $dimension->value = $part->num;
            $dimension->save();
            echo '次日留存用户分布 - :' . $part->source . '  ' . $part->num . "\n";
        }

        $avgGold   = $qb_second_day->avg('gold') ?? 0;
        $dimension = Dimension::firstOrNew([
            'group' => '次日留存用户',
            'name'  => '平均智慧点',
            'date'  => $date,
        ]);
        $dimension->value = $avgGold;
        $dimension->save();
        echo '次日留存用户 - 平均智慧点:' . $avgGold . ' 日期:' . $date . "\n";

        $avg_answers_count = $qb_second_day->avg('user_profiles.answers_count') ?? 0;
        $dimension         = Dimension::firstOrNew([
            'group' => '次日留存用户',
            'name'  => '平均答题数',
            'date'  => $date,
        ]);
        $dimension->value = $avg_answers_count;
        $dimension->save();
        echo '次日留存用户 - 平均答题数:' . $avg_answers_count . ' 日期:' . $date . "\n";

        $max_answers_count = $qb_second_day->max('user_profiles.answers_count') ?? 0;
        $dimension         = Dimension::firstOrNew([
            'group' => '次日留存用户',
            'name'  => '最高答题数',
            'date'  => $date,
        ]);
        $dimension->value = $max_answers_count;
        $dimension->save();
        echo '次日留存用户 - 最高答题数:' . $max_answers_count . ' 日期:' . $date . "\n";

    }

}
