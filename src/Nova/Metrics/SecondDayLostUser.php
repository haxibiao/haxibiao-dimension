<?php

namespace Haxibiao\Dimension\Nova\Metrics;

use Haxibiao\Dimension\Dimension;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;

class SecondDayLostUser extends Trend
{

    public $name = '次日流失用户';

    public $range = 7;

    public $flip = false;

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $this->flip     = true;
        $name           = $request->range;
        $request->range = $this->range; // 先固定看7天的
        $qb = Dimension::whereGroup('次日流失用户')->whereName($name);

        $result = $this->averageByDays($request, $qb, 'value', 'date')->showLatestValue();
        $arr    = $result->trend;
        array_pop($arr);
        $yesterday = last($arr);
        $max       = max($arr);

        return $result->showLatestValue()
            ->suffix("昨日: $yesterday  最大: $max");

    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        $ranges = [
            '平均智慧点' => $this->range,
            '平均答题数' => $this->range,
            '最高答题数' => $this->range,
        ];

        if ($this->flip) {
            $ranges = array_flip($ranges);
        }

        return $ranges;
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'second-day-lost-user-avg-gold';
    }
}
