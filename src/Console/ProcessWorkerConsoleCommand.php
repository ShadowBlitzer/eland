<?php

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;
use util\task_container;

use Predis\Client as Predis;

use App\Service\BootCount;
use App\Service\Queue;

class ProcessWorkerConsoleCommand extends Command
{
    private $bootCount;
    private $queue;
    private $predis;


    public function __construct(BootCount $bootCount, Queue $queue, Predis $predis)
    {
        $this->bootCount = $bootCount;
        $this->queue = $queue;
        $this->predis = $predis;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('app-process:worker')
            ->setDescription('general background tasks')
            ->setHelp('general background tasks (send notifications etc.)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);

        $boot = $bootCount->get('worker');

        echo 'worker started .. ' . $boot . "\n";

        $task = new task_container($app, 'task');
        $schema_task = new task_container($app, 'schema_task');

        $loopCount = 1;

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

            if ($loopCount % 1000 === 0)
            {
                error_log('..worker.. ' . $boot . ' .. ' . $loopCount);
            }

            if ($loopCount % 10 === 0)
            {
                $this->predis->set('monitor_process_worker', '1');
                $this->predis->expire('monitor_process_worker', 900);
            }

            $loopCount++;
        }
    }
}