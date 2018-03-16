<?php

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Predis\Client as Predis;

use App\Service\BootCount;
use App\Service\Queue;

use util\queue_container;
use util\task_container;

class ProcessGeoConsoleCommand extends Command
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
            ->setName('app-process:geo')
            ->setDescription('Geocode background process')
            ->setHelp('Find geo coordinates from address queue background process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);
 
        $boot = $this->bootCount->get('geo');

        error_log('... geo service started ... ' . $boot);

        $loopCount = 1;

        while (true)
        {
            sleep(120);

            if ($loopCount % 1800 === 0)
            {
                error_log('..geo.. ' . $boot . ' .. ' . $loopCount);
            }

            $loopCount++;

            $record = $this->queue->get('geo');

            if (!count($record))
            {
                continue;
            }

 //           $app['mail_send']->send($record); to do: process geo
        }
    }
}