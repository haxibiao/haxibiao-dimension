<?php

namespace Haxibiao\Dimension\Nova\Metrics;

use Haxibiao\Dimension\Dimension;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class RewardVideoPartition extends Partition
{
    public $name = '激励视频分布 (今日)';
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $qb = Dimension::whereGroup('激励视频')
            ->where('date', '>', today()->subDay()->toDateString());
        return $this->sum($request, $qb, 'value', 'name');
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
        return 'reward-video-partition';
    }
}
