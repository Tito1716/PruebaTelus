<?php
// src/Handler/HandlerInterface.php
namespace App\Handler;

interface HandlerInterface
{
    public function setNext(HandlerInterface $handler): HandlerInterface;
    public function handle(array $data): ?array;
}
