<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class QueryListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {
        // 只在测试环境下输出 log 日志
        if (!app()->environment(['testing', 'local'])) {
            return;
        }
        $sql = $event->sql;
        $bindings = $event->bindings;
        $time = $event->time; // 毫秒
        $bindings = array_map(function ($binding) {
            if (is_string($binding)) {
                return (string)$binding;
            }
            if ($binding instanceof \DateTime) {
                return $binding->format("'Y-m-d H:i:s'");
            }
            return $binding;
        }, $bindings);
        $sql = str_replace('?', '%s', $sql);
        $sql = sprintf($sql, ...$bindings);
        Log::channel('sqlLog')->info('sql', ['sql' => $sql, 'time' => $time . 'ms']);
    }
}
