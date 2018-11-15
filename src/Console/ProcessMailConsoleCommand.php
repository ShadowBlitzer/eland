<?php declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Predis\Client as Predis;
use App\Service\BootCount;
use App\Mail\MailProcessQueue;

class ProcessMailConsoleCommand extends Command
{
    private $bootCount;
    private $predis;
    private $mailProcessQueue;

    public function __construct(BootCount $bootCount, Predis $predis, MailProcessQueue $mailProcessQueue)
    {
        $this->bootCount = $bootCount;
        $this->predis = $predis;
        $this->mailProcessQueue = $mailProcessQueue;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app-process:mail')
            ->setDescription('Send mail background process')
            ->setHelp('Send mail from queue background process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    { 
        $boot = $this->bootCount->get('mail');

        error_log('... mail service started ... ' . $boot);

        $loopCount = 1;

        while (true)
        {
            sleep(2);

            if ($loopCount % 1800 === 0)
            {
                error_log('..mail.. ' . $boot . ' .. ' . $loopCount);
            }

            if ($loopCount % 10 === 0)
            {
                $this->predis->set('monitor_process_mail', '1');
                $this->predis->expire('monitor_process_mail', 900);              
            }

            $loopCount++;

            $this->mailProcessQueue->run();
        }
    }
}