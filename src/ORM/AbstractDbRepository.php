<?php

namespace Nacho\DbConnector\ORM;

use Nacho\DbConnector\Helper\Database;
use Nacho\ORM\AbstractRepository;
use Nacho\ORM\RepositoryInterface;
use Nacho\Nacho;


abstract class AbstractDbRepository extends AbstractRepository implements RepositoryInterface {
    private Database $db;

    public function __construct()
    {
        $this->db = Nacho::$container->get(Database::class);
    }

    protected function execSQLFile(string $sqlFile, array $params = []): mixed
    {

    }

    protected function execQuery(string $query, array $params = []): mixed
    {

    }
}

