<?php

namespace command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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
        $io = new SymfonyStyle($input, $output);
        $magenta = new OutputFormatterStyle('magenta'); 
        $output->getFormatter()->setStyle('magenta', $magenta);
        $cyan = new OutputFormatterStyle('cyan'); 
        $output->getFormatter()->setStyle('cyan', $cyan);
        $schema = $input->getArgument('schema');
        $io->title('Migration from eLAS of schema ' . $schema);
        $cid = $app['unique_id']->get(); 
        $io->text('<info>New id of currency: </>' . $cid);

        $io->text('<cyan>Get users</>');

       // $users = $app['db']->fetchAll('select * from ' . $schema . '.users');

        $type_contact = $app['db']->fetchColumn('select id 
            from ' . $schema . '.type_contact 
            where abbrev = \'mail\'');

        $mail = $app['db']->fetchAll('select value, id, id_user 
            from ' . $schema . '.contact 
            where id_type_contact = ?', [$type_contact]);

        $io->table([], $mail);

$io->listing(array(
    'Element #1 Lorem ipsum dolor sit amet',
    'Element #2 Lorem ipsum dolor sit amet',
    'Element #3 Lorem ipsum dolor sit amet',
));
        


    }
}