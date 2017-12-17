<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;
use util\queue_container;
use util\task_container;

class ProcessSyncCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app-process:sync-elas')
            ->setDescription('Sync to eLAS db background process')
            ->setHelp('Sync to eLAS db background process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);

        $boot = $app['boot_count']->get('sync');

        echo 'sync elas started .. ' . $boot . "\n";

        $loop_count = 1;

        while (true)
        {
            sleep(1);

            if ($app['sync_elas']->should_run())
            {
                $app['sync_elas']->run();
            }

            if ($loop_count % 1000 === 0)
            {
                error_log('..sync elas .. ' . $boot . ' .. ' . $loop_count);
            }

            $loop_count++;
        }

    }
}