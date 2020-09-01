<?php

namespace Haxibiao\Dimension\Console;

use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Haxibiao\Dimension\Dimension;
use Illuminate\Support\Facades\DB;

class ArchiveUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:user {--date=} {--hour=} {--newuser : 新用户首日数据} {--categoryuser : 新老用户分类数据}';

    /**
     * 接受短信手机号
     */
    protected $phones = [13327347331, 17692625821, 17872635502, 13825294765, 15575464607];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '按日，小时统计归档用户新增留存信息';

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
        $date = $this->option('date') ?? today()->toDateString();
        $hour = $this->option('hour') ?? now()->format('H');

        //必须凌晨执行，新用户首日行为数据才准确，避免次日留存用户又活跃了，更新了counts
        if ($this->option('newuser')) {
            $this->info("维度归档统计: 新用户首日行为数据 ..." . $date);
            return $this->firstDayUser($date);
        }

        if ($this->option('categoryuser')) {
            $this->info("维度归档统计: 新老用户数据 ..." . $date);
            return $this->userCategoriesByDay($date);
        }

        //默认归档当前小时新增
        if (is_null($this->option('date'))) {
            $this->info("统计${hour}时新增:" . $date);
            return $this->newUsersByHour($date, $hour);
        }

        //归档以前某天的每小时新增
        if (is_null($this->option('hour')) && $this->option('date')) {
            if ($this->option('date') >= today()->toDateString()) {
                $this->error('必须指定过去的日期才能统计整天每小时新增数据');
                return;
            }
            $this->info("统计整天的每小时新增:" . $date);
            for ($i = 1; $i <= 24; $i++) {
                $this->newUsersByHour($date, $i);
            }
        }

    }

    /**
     * 统计每小时的新增用户数
     */
    public function newUsersByHour($date, $hour)
    {
        $timeTo = $date . " " . $hour . ":00:00";
        if ($hour == 24) {
            $timeTo = $date . " 23:59:59";
        }
        $timeFrom = $date . " " . ($hour - 1) . ":00:00";

        $newUserCount = User::whereBetween('created_at', [$timeFrom, $timeTo])
            ->count('id');

        $dimension = Dimension::firstOrCreate([
            'group' => '新增',
            'name'  => '每小时新增用户',
            'hour'  => $hour,
            'date'  => $date,
        ]);
        $dimension->value = $newUserCount;
        $dimension->save();
        $this->info($timeFrom . " " . $timeTo . "," . $dimension->name . " " . $dimension->value);
    }

    /**
     * 计算 新用户首日 信息，如 平均智慧点 平均答题数 最高答题数 (每日凌晨统计)
     *
     * @return void
     */
    public function firstDayUser($date)
    {
        $day   = today();
        $dates = [(clone $day)->subDay(), $day];

        $qb_first_day = DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
        //开启下面留存条件就是次日流失用户了...
        // ->join('user_retentions', 'users.id', '=', 'user_retentions.user_id')
        // ->whereNull('user_retentions.day2_at')
            ->whereBetween('users.created_at', $dates);

        $avgGold   = $qb_first_day->avg('gold') ?? 0;
        $dimension = Dimension::firstOrNew([
            'group' => '新用户首日',
            'name'  => '平均智慧点',
            'date'  => $date,
        ]);
        $dimension->value = $avgGold;
        $dimension->save();
        echo '新用户首日 - 平均智慧点:' . $avgGold . ' 日期:' . $date . "\n";

        $avg_answers_count = $qb_first_day->avg('user_profiles.answers_count') ?? 0;
        $dimension         = Dimension::firstOrNew([
            'group' => '新用户首日',
            'name'  => '平均答题数',
            'date'  => $date,
        ]);
        $dimension->value = $avg_answers_count;
        $dimension->save();
        echo '新用户首日 - 平均答题数:' . $avg_answers_count . ' 日期:' . $date . "\n";

        $max_answers_count = $qb_first_day->max('user_profiles.answers_count') ?? 0;
        $dimension         = Dimension::firstOrNew([
            'group' => '新用户首日',
            'name'  => '最高答题数',
            'date'  => $date,
        ]);
        $dimension->value = $max_answers_count;
        $dimension->save();
        echo '新用户首日 - 最高答题数:' . $max_answers_count . ' 日期:' . $date . "\n";

        $zero_answers_count = $qb_first_day->where('user_profiles.answers_count', 0)->count();
        $dimension          = Dimension::firstOrNew([
            'group' => '新用户首日',
            'name'  => '零答题行为人数',
            'date'  => $date,
        ]);
        $dimension->value = $zero_answers_count;
        $dimension->save();
        echo '新用户首日 - 零答题行为人数:' . $zero_answers_count . ' 日期:' . $date . "\n";

        $zero_gold_count = DB::table('users')
            ->where('gold', 300)
            ->whereBetween('users.created_at', $dates)->count();
        $dimension = Dimension::firstOrNew([
            'group' => '新用户首日',
            'name'  => '零账单变动人数',
            'date'  => $date,
        ]);
        $dimension->value = $zero_gold_count;
        $dimension->save();
        echo '新用户首日 - 零账单变动人数:' . $zero_gold_count . ' 日期:' . $date . "\n";

    }

    /**
     * 每天根据提现次数分类用户
     */
    public function userCategoriesByDay($date){

        // 归档昨天的数据
        $date = Carbon::parse($date)->subDay(1)->toDateString();

        // 没有提现过的用户
        $pureNewUserQuery = DB::select('SELECT count(1) as pureNewUserCount FROM user_profiles where transaction_sum_amount =  0 and created_at >= "2018-12-12";');
        $pureNewUserCount = current($pureNewUserQuery)->pureNewUserCount;

        $dimension = Dimension::firstOrNew([
            'group' => '新老用户分类活跃数',
            'name'  => '纯新用户',
            'date'  => $date,
        ]);
        $dimension->value = $pureNewUserCount;
        $dimension->save();
        echo '新老用户分类活跃数 - 纯新用户:' . $pureNewUserCount . ' 日期:' . $date . "\n";


        // 提现一次用户
        $NewUserQuery = DB::select('SELECT count(1) as newUserCount FROM ( SELECT count(1) as num, wallet_id FROM withdraws WHERE created_at >= "2018-12-12" GROUP BY wallet_id HAVING num = 1) AS b;');
        $newUserCount = current($NewUserQuery)->newUserCount;

        $dimension = Dimension::firstOrNew([
            'group' => '新老用户分类活跃数',
            'name'  => '新用户',
            'date'  => $date,
        ]);
        $dimension->value = $newUserCount;
        $dimension->save();
        echo '新老用户分类活跃数 - 新用户:' . $newUserCount . ' 日期:' . $date . "\n";

        // 提现2-7次用户
        $OldUserQuery = DB::select('SELECT count(1) as oldUserCount FROM ( SELECT count(1) as num, wallet_id FROM withdraws WHERE created_at >= "2018-12-12" GROUP BY wallet_id HAVING num BETWEEN 2 and 8) AS b;');
        $oldUserCount = current($OldUserQuery)->oldUserCount;

        $dimension = Dimension::firstOrNew([
            'group' => '新老用户分类活跃数',
            'name'  => '老用户',
            'date'  => $date,
        ]);
        $dimension->value = $oldUserCount;
        $dimension->save();
        echo '新老用户分类活跃数 - 老用户:' . $oldUserCount . ' 日期:' . $date . "\n";

        // 提现7次以上
        $pureOldUserQuery = DB::select('SELECT count(1) as pureOldUserCount FROM ( SELECT count(1) as num, wallet_id FROM withdraws WHERE created_at >= "2018-12-12" GROUP BY wallet_id HAVING num > 7) AS b;');
        $pureOldUserCount = current($pureOldUserQuery)->pureOldUserCount;

        $dimension = Dimension::firstOrNew([
            'group' => '新老用户分类活跃数',
            'name'  => '纯老用户',
            'date'  => $date,
        ]);
        $dimension->value = $pureOldUserCount;
        $dimension->save();
        echo '新老用户分类活跃数 - 纯老用户:' . $pureOldUserCount . ' 日期:' . $date . "\n";

    }


    // 每三小时发一次新增和提现统计短信 - 暂时停用
    public function smsAlert()
    {
        // if (!is_night()) {
        //         $withdrawAmountSum = Withdraw::whereBetween('created_at', [now()->subHours($intervalHours), now()])
        // ->where('status', Withdraw::SUCCESS_WITHDRAW)
        // ->sum('amount');
        //     $currentHour   = (int) now()->format('H');
        //     $intervalHours = 3;
        //     // 每三小时发一次新增和提现统计短信
        //     if ($currentHour / $intervalHours == 0) {
        //         $newUserCount      = User::whereBetween('created_at', [now()->subHours($intervalHours), now()])->count('id');
        //         $withdrawAmountSum = Withdraw::whereBetween('created_at', [now()->subHours($intervalHours), now()])
        //             ->where('status', Withdraw::SUCCESS_WITHDRAW)
        //             ->sum('amount');
        //         $sendData = [
        //             '1' => '3小时',
        //             '2' => $newUserCount . '位',
        //             '3' => $withdrawAmountSum,
        //         ];
        //         foreach ($this->phones as $phone) {
        //             try {
        //                 SMSUtils::sendNovaMessage($phone, 'NOVA_NEW_USER_WITHDRAW', $sendData);
        //             } catch (\Exception $exception) {
        //                 $this->error($exception->getMessage());
        //                 info("sms发送失败: {$phone}");
        //             }
        //         }
        //     }
        // }

    }
}
