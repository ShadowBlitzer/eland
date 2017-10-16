<?php

namespace command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;
use util\queue_container;
use util\task_container;

class process_geo extends Command
{
    protected function configure()
    {
        $this
            ->setName('process:geo')
            ->setDescription('Find  background process')
            ->setHelp(' from queue background process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);
 
        $boot = $app['boot_count']->get('geo');

        error_log('... geo service started ... ' . $boot);

        $loop_count = 1;

        while (true)
        {
            sleep(120);

            if ($loop_count % 1800 === 0)
            {
                error_log('..geo.. ' . $boot . ' .. ' . $loop_count);
            }

            $loop_count++;

            $record = $app['queue']->get('geo');

            if (!count($record))
            {
                continue;
            }

 //           $app['mail_send']->send($record); to do: process geo
        }
    }
}