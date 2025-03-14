<?php

namespace cores\library\cron;

use think\console\Command;
use think\Exception;

class Schedule extends Command
{
    public function configure()
    {
        $this->setName("schedule")
            ->setDescription("执行定时任务");
    }

    private function loadCronSupport(): array
    {
        // 处理command
        $command = $this->getConsole()->all();
        $runCommands = [];
        foreach ($command as $key => $value) {
            if ($value instanceof CronSupport) {
                if ($value->isDue()) {
                    $runCommands[] = $value;
                }
            }
        }
        return $runCommands;
    }

    /**
     * @throws Exception
     */
    public function execute($input, $output)
    {
        $commands = $this->loadCronSupport();
        foreach ($commands as $command) {
            try {
                $command->run($input, $output);
            } catch (Exception $e) {
                $output->writeln($e->getMessage());
            }
        }
    }
}