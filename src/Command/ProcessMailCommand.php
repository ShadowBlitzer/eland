<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Predis\Client as Predis;

use App\Service\BootCount;
use App\Service\Queue;

use App\Repository\ConfigRepository;

class ProcessMailCommand extends Command
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
            ->setName('app-process:mail')
            ->setDescription('Send mail background process')
            ->setHelp('Send mail from queue background process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);
 
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

            $record = $this->queue->get('mail');

            if (!count($record))
            {
                continue;
            }

            if (isset($record['schema']))
            {
                if (!$app['config']->get('mailenabled', $record['schema']))
                {
                    $app['monolog']->info(sprintf(
                        'mail functionality not enabled in 
                        configuration, mail from queue not send: %s', json_encode($data)), [
                            'schema'	=> $record['schema'],
                        ]
                    );
    
                    continue;
                }

                $app['mail_message']->init()
                    ->set_schema($record['schema']);

                $app['mail_from']->set_schema($record['schema']);
            }
            else
            {
                $app['mail_message']->init();
            }

            $app['mail_template']
                ->set_template($record['template'])
                ->set_vars($record['vars']);

            if (isset($record['reply_to']))
            {
                $app['mail_from']->set_reply_possible(true);
                $app['mail_message']->set_reply_to($record['reply_to']);
            }

            if (isset($record['cc']))
            {
                $app['mail_message']->set_cc($record['cc']);
            }

            echo json_encode($app['mail_from']->get());

            $app['mail_message']->setTo($record['to'])
                ->setFrom($app['mail_from']->get())
                ->setNext($app['mail_template']->getText())
                ->setHtml($app['mail_template']->getHtml())
                ->setSubject($app['mail_template']->getSubject())
                ->send();

            $app['mail_from']->clear();
            $app['mail_template']->clear();
        }
    }
}