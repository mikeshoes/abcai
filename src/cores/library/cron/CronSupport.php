<?php

namespace cores\library\cron;

use Cron\CronExpression;
use think\console\Command;

class CronSupport extends Command
{
    protected string $cron = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function cron(string $cron)
    {
        $this->cron = $cron;
        return $this;
    }

    public function daily()
    {
        $this->cron = '0 0 * * *';
        return $this;
    }

    public function weekly()
    {
        $this->cron = '0 0 * * 0';
        return $this;
    }

    public function monthly()
    {
        $this->cron = '0 0 1 * *';
        return $this;
    }

    public function hourly()
    {
        $this->cron = '0 * * * *';
        return $this;
    }

    public function yearly()
    {
        $this->cron = '0 0 1 1 *';
        return $this;
    }

    public function everyMinute()
    {
        $this->cron = '*/1 * * * *';
        return $this;
    }

    public function isDue(): bool
    {
        if (empty($this->cron)) {
            return false;
        }
        return CronExpression::factory($this->cron)->isDue();
    }
}