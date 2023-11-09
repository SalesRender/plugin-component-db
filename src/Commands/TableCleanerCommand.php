<?php
/**
 * Created for plugin-core
 * Date: 20.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Components\Db\Commands;



use SalesRender\Plugin\Components\Db\Components\Connector;
use SalesRender\Plugin\Components\Db\Exceptions\DatabaseException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TableCleanerCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('db:cleaner')
            ->setDescription('Remove outdated records from DB')
            ->addArgument('table', InputArgument::REQUIRED, 'Table name')
            ->addArgument('by', InputArgument::REQUIRED, "timestamp (int) field name")
            ->addArgument('hours', InputArgument::OPTIONAL, 'Timeout in hours', 24)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = Connector::db();

        $table = $input->getArgument('table');
        $by = $input->getArgument('by');
        $hours = (int) $input->getArgument('hours');

        $data = $db->delete($table, [
            "{$by}[<]" => time() - ($hours * 60 * 60),
        ]);

        DatabaseException::guard($db);

        $output->writeln("Deleted {$data->rowCount()} records from table '{$table}'");

        return 0;
    }

}