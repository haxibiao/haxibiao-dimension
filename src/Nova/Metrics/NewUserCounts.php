<?php

namespace Haxibiao\Dimension\Nova\Metrics;

use Haxibiao\Dimension\Dimension;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;

class NewUserCounts extends Trend
{
    public $name = '新用户首日';

    public $range = 7;

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $name = $request->range;
        // $request->range = 7; // 先固定看7天的
        $qb     = Dimension::whereGroup('新用户首日')->whereName($name);
        $result = $this->averageByDays($request, $qb, 'value', 'date')->showLatestValue();
        $arr    = $result->trend;
        array_pop($arr);
        $yesterday = last($arr);
        $max       = max($arr);

        return $result->showLatestValue()->suffix("昨日: $yesterday  最大: $max");

    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            $this->range => '平均智慧点',
            $this->range => '平均答题数',
            $this->range => '最高答题数',
            $this->range => '零答题行为人数',
            $this->range => '零账单变动人数',
        ];
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        // return 5;
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'new-user-answers-count-trend';
    }
}
