<?php

namespace Haxibiao\Dimension;

use App\User;
use Haxibiao\Base\Model;
use Haxibiao\Base\UserRetention;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Dimension extends Model
{
    protected $fillable = [
        'group',
        'name',
        'value',
        'count',
        'created_at',
        'hour',
        'date',
    ];

    //repo

    //维度统计（广告）
    public static function trackAdView($name = "激励视频", $group = "广告展示")
    {
        Dimension::track($name, 1, $group);
    }

    public static function trackAdClick($name = "激励视频", $group = "广告点击")
    {
        Dimension::track($name, 1, $group);
    }

    //更新维度统计
    public static function track($name, int $value = 1, $group = null)
    {
        $date = today()->toDateString();
        //每天一个维度统计一到一个记录里
        $dimension = Dimension::whereGroup($group)
            ->whereName($name)
            ->where('date', $date)
            ->first();
        if (!$dimension) {
            $dimension = Dimension::create([
                'date'  => $date,
                'group' => $group,
                'name'  => $name,
                'value' => $value,
            ]);
        } else {
            //更新数值和统计次数
            $dimension->value = $dimension->value + $value;
            $dimension->count = ++$dimension->count;
            $dimension->save();
        }

        return $dimension;
    }

    public static function calculateRetention($date, $subDay, $column, $isSave = true)
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $next_day_key = $column . '_' . $date->toDateString();
        $startDay     = $date->subDay($subDay);
        $endDay       = $startDay->copy()->addDay();
        $dateRange    = [$startDay, $endDay];

        $userModel          = new User;
        $userRetentionModel = new UserRetention;
        $newRegistedNum     = User::whereBetween('created_at', $dateRange)->count(DB::raw(1));
        $userRetentionNum   = User::whereBetween($userModel->getTable() . '.created_at', $dateRange)
            ->join($userRetentionModel->getTable(), function ($join) use ($userModel, $userRetentionModel) {
                $join->on($userModel->getTable() . '.id', $userRetentionModel->getTable() . '.user_id');
            })->whereBetween($userRetentionModel->getTable() . '.' . $column, [$endDay, $endDay->copy()->addDay()])
            ->count(DB::raw(1));
        if (0 != $userRetentionNum) {
            $next_day_result = sprintf('%.2f', ($userRetentionNum / $newRegistedNum) * 100);
            if ($isSave) {
                cache()->store('database')->forever($next_day_key, $next_day_result);
            }
            return $next_day_result;
        }
    }
}
