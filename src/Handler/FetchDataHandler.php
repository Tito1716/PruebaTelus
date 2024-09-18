<?php
// src/Handler/FetchDataHandler.php
namespace App\Handler;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FetchDataHandler implements HandlerInterface
{
    private $httpClient;
    private $next;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setNext(HandlerInterface $handler): HandlerInterface
    {
        $this->next = $handler;
        return $handler;
    }

    public function handle(array $data): ?array
    {
        $response = $this->httpClient->request('GET', 'https://dummyjson.com/users');
        $users = $response->toArray();

        if ($this->next) {
            return $this->next->handle($users);
        }

        return $users;
    }
}
