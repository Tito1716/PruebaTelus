<?php

namespace App\Command;

use App\Handler\FetchDataHandler;
use App\Handler\SaveSftpHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'FetchAndSaveUserCommand',
    description: 'Execute a fecth users and save it in sftp server',
)]
class FetchAndSaveUserCommand extends Command
{
    protected static $defaultName = 'app:fetch-users';

    //Set the credentials
    private $httpClient;
    private $sftpUsername;
    private $sftpPassword;
    public function __construct(HttpClientInterface $httpClient, string $sftpUsername, string $sftpPassword)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->sftpUsername = $sftpUsername;
        $this->sftpPassword = $sftpPassword;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fetchDataHandler = new FetchDataHandler($this->httpClient);
        $saveToSftpHandler = new SaveSftpHandler($this->sftpUsername, $this->sftpPassword);

        $fetchDataHandler->setNext($saveToSftpHandler);

        $fetchDataHandler->handle([]);

        $output->writeln('Data fetched and saved to SFTP server');

        return Command::SUCCESS;
    }
}
