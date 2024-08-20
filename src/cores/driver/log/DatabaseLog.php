<?php

namespace cores\driver\log;

use cores\driver\LogDatabaseSave;
use think\contract\LogHandlerInterface;
use think\exception\ValidateException;

class DatabaseLog implements LogHandlerInterface
{
    public function save(array $log): bool
    {
        $message = [];
        foreach ($log as $type => $val) {
            foreach ($val as $msg) {
                if (is_string($msg)) {
                    $msg = ['msg' => $msg];
                } elseif (!is_array($msg)) {
                    $msg = ['msg' => var_export($msg, true)];
                }

                $message[] = array_merge($msg, ['type' => $type]);
            }
        }

        app(LogDatabaseSave::class)->saveLog($message);
        return true;
    }
}