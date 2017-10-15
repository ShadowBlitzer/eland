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

class process_mail extends Command
{
    protected function configure()
    {
        $this
            ->setName('process:mail')
            ->setDescription('Send mail background process')
            ->setHelp('Send mail from queue background process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);
 
        $boot = $app['boot_count']->get('mail');

        error_log('... mail service started ... ' . $boot);

        $loop_count = 1;

        while (true)
        {
            sleep(2);

            if ($loop_count % 1800 === 0)
            {
                error_log('..mail.. ' . $boot . ' .. ' . $loop_count);
            }

            $loop_count++;

            $record = $app['queue']->get(['mail']);

            if (!count($record))
            {
                continue;
            }

            $app['mail_send']->send($record['data']);
        }
    }
}