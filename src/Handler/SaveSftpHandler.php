<?php

namespace App\Handler;

class SaveSftpHandler implements HandlerInterface
{
    private $sftpUsername;
    private $sftpPassword;
    private $next;

    public function __construct(string $sftpUsername, string $sftpPassword)
    {
        $this->sftpUsername = $sftpUsername;
        $this->sftpPassword = $sftpPassword;
    }

    public function setNext(HandlerInterface $handler): HandlerInterface
    {
        $this->next = $handler;
        return $handler;
    }

    public function handle(array $data): ?array
    {
        $ssh = new Ssh('sftp.example.com', $this->sftpUsername, $this->sftpPassword);
        $session = $ssh->getSession();

        foreach ($data as $file) {
            try {
                $session->upload($file, '/remote/path/' . $file);
                echo "Successfully uploaded $file to SFTP server.\n";
            } catch (\Exception $e) {
                echo "Failed to upload $file: " . $e->getMessage() . "\n";
            }
        }
        if ($this->next) {
            return $this->next->handle($data);
        }

        return null;
    }
}