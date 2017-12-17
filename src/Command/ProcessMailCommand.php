<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;

class ProcessMailCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app-process:mail')
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

            $record = $app['queue']->get('mail');

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

            $app['mail_message']->set_to($record['to'])
                ->set_from($app['mail_from']->get())
                ->set_text($app['mail_template']->get_text())
                ->set_html($app['mail_template']->get_html())
                ->set_subject($app['mail_template']->get_subject())
                ->send();

            $app['mail_from']->clear();
            $app['mail_template']->clear();
        }
    }
}