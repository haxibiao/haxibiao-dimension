<?php

namespace Haxibiao\Dimension;

use Haxibiao\Base\Model;

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
}
