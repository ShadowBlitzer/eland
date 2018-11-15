<?php declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Finder\Finder;

use Predis\Client as Predis;

use App\Service\BootCount;
use App\Service\Queue;

use util\queue_container;
use util\task_container;

class ProcessSyncConsoleCommand extends Command
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
            ->setName('app-process:sync-elas')
            ->setDescription('Sync to eLAS db background process')
            ->setHelp('Sync to eLAS db background process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);

        $boot = $bootCount->get('sync');

        echo 'sync elas started .. ' . $boot . "\n";

        $loopCount = 1;

        while (true)
        {
            sleep(1);

            if ($app['sync_elas']->should_run())
            {
                $app['sync_elas']->run();
            }

            if ($loopCount % 1000 === 0)
            {
                error_log('..sync elas .. ' . $boot . ' .. ' . $loopCount);
            }

            $loopCount++;
        }

    }
}