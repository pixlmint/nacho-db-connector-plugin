<?php

namespace Nacho\DbConnector\ORM;

use Nacho\ORM\RepositoryInterface;
use Nacho\ORM\RepositoryManagerInterface;

class DbRepositoryManager implements RepositoryManagerInterface
{
    public function trackRepository(RepositoryInterface $repository): void
    {
    }

    public function getRepository(string $repositoryClass): RepositoryInterface
    {
    }

    public function close(): void
    {
    }
}
