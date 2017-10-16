<?php

namespace command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;
use util\task_container;

class process_worker extends Command
{
    protected function configure()
    {
        $this
            ->setName('process:worker')
            ->setDescription('general eLAND background processes')
            ->setHelp('general eLAND background processes (send notifications etc.)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);

        $boot = $app['boot_count']->get('worker');

        echo 'worker started .. ' . $boot . "\n";

        $task = new task_container($app, 'task');
        $schema_task = new task_container($app, 'schema_task');

        $loop_count = 1;

        while (true)
        {
            $app['log_db']->update();

            sleep(5);

            if ($task->should_run())
            {
                $task->run();
            }
            else if ($schema_task->should_run())
            {
                $schema_task->run();
            }

            if ($loop_count % 1000 === 0)
            {
                error_log('..worker.. ' . $boot['count'] . ' .. ' . $loop_count);
            }

            if ($loop_count % 10 === 0)
            {
                $app['predis']->set('monitor_service_worker', '1');
                $app['predis']->expire('monitor_service_worker', 900);
            }

            $loop_count++;
        }

    }
}