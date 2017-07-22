<?php

namespace command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class migrate_elas extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrate:elas')
            ->setDescription('Migrate from a eLAS database')
            ->setHelp('The eLAS installation needs to be imported first in the same database in a different schema')
            ->addArgument('schema', InputArgument::REQUIRED, 'The schema of the eLAS installation in the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $schema = $input->getArgument('schema');



        $output->writeln('Migration of ' . $schema);

        $cid = $app['unique_id']->get();

        $output->writeln('New id: ' . $cid);
        
    }
}