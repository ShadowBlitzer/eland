<?php

namespace command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class migrate_elas extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrate:elas')
            ->setDescription('Migrate from a eLAS database')
            ->setHelp('The eLAS database needs to be imported first 
                in the same database in a different schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    // ...
    }
}