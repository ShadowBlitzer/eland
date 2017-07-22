<?php

namespace command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class migrate_status extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrate:status')
            ->setDescription('Show status of current migration')
            ->setHelp('Show status of current migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
    }
}