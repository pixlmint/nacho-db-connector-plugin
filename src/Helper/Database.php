<?php

namespace Nacho\DbConnector\Helper;

use PDO;
use Exception;
use PDOStatement;

class Database
{
    /**
     * hold database connection
     */
    protected PDO $db;

    /**
     * Array of connection arguments
     * 
     * @param array $args
     */
    public function __construct(array $args)
    {
        if (!isset($args['database'])) {
            throw new Exception('&args[\'database\'] is required');
        }

        if (!isset($args['username'])) {
            throw new Exception('&args[\'username\']  is required');
        }

        $type     = isset($args['type']) ? $args['type'] : 'mysql';
        $host     = isset($args['host']) ? $args['host'] : 'localhost';
        $charset  = isset($args['charset']) ? $args['charset'] : 'utf8';
        $port     = isset($args['port']) ? 'port=' . $args['port'] . ';' : '';
        $password = isset($args['password']) ? $args['password'] : '';
        $database = $args['database'];
        $username = $args['username'];

        $this->db = new PDO("$type:host=$host;$port" . "dbname=$database;charset=$charset", $username, $password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * get PDO instance
     * 
     * @return $db PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->db;
    }

    /**
     * Run raw sql query 
     * 
     * @param  string $sql       sql query
     * @return void
     */
    public function raw($sql): void
    {
        $this->db->query($sql);
    }

    /**
     * Run sql query
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return PDOStatement      returns a PDO object
     */
    public function run(string $sql, array $args = []): PDOStatement
    {
        if (empty($args)) {
            return $this->db->query($sql);
        }

        $stmt = $this->db->prepare($sql);

        //check if args is associative or sequential?
        $is_assoc = (array() === $args) ? false : array_keys($args) !== range(0, count($args) - 1);
        if ($is_assoc) {
            foreach ($args as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":$key", $value);
                }
            }
            $stmt->execute();
        } else {
            $stmt->execute($args);
        }

        return $stmt;
    }

    /**
     * Get arrays of records
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @param  int    $fetchMode set return mode ie object or array
     * @return array             returns multiple records
     */
    public function rows(string $sql, array $args = [], int $fetchMode = PDO::FETCH_OBJ): array
    {
        return $this->run($sql, $args)->fetchAll($fetchMode);
    }

    /**
     * Get array of records
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @param  int    $fetchMode set return mode ie object or array
     * @return mixed             returns single record
     */
    public function row(string $sql, array $args = [], int $fetchMode = PDO::FETCH_OBJ)
    {
        return $this->run($sql, $args)->fetch($fetchMode);
    }

    /**
     * Get record by id
     * 
     * @param  string  $table      name of table
     * @param  integer $id         id of record
     * @param  int     $fetchMode  set return mode ie object or array
     * @return mixed               returns single record
     */
    public function getById(string $table, int $id, int $fetchMode = PDO::FETCH_OBJ)
    {
        return $this->run("SELECT * FROM $table WHERE id = ?", [$id])->fetch($fetchMode);
    }

    /**
     * Get number of records
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @param  int    $fetchMode set return mode ie object or array
     * @return int               returns number of records
     */
    public function count(string $sql, array $args = []): int
    {
        return $this->run($sql, $args)->rowCount();
    }

    /**
     * Get primary key of last inserted record
     */
    public function lastInsertId(): int
    {
        return $this->db->lastInsertId();
    }

    /**
     * insert record
     * 
     * @param  string $table table name
     * @param  array $data   array of columns and values
     * @return int           lastInsertId
     */
    public function insert(string $table, array $data): int
    {
        // enclose columns in backticks
        $columns = array_map(function($column) {
            return '`' . trim($column, '`') . '`';
        }, array_keys($data));

        //add columns into comma separated string
        $columns = implode(',', $columns);

        //get values
        $values = array_values($data);

        $placeholders = array_map(function ($val) {
            return '?';
        }, array_keys($data));

        //convert array into comma separated string
        $placeholders = implode(',', array_values($placeholders));

        $this->run("INSERT INTO $table ($columns) VALUES ($placeholders)", $values);

        return $this->lastInsertId();
    }

    /**
     * update record
     * 
     * @param  string $table table name
     * @param  array $data  array of columns and values
     * @param  array $where array of columns and values
     * @return int          number of rows affected
     */
    public function update(string $table, array $data, array $where): int
    {
        //collect the values from data and where
        $values = [];

        //setup fields
        $fieldDetails = null;
        foreach ($data as $key => $value) {
            $key = '`' . trim($key, '`') . '`';
            $fieldDetails .= "$key = ?,";
            $values[] = $value;
        }
        $fieldDetails = rtrim($fieldDetails, ',');

        //setup where 
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            $key = '`' . trim($key, '`') . '`';
            $whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $values[] = $value;
            $i++;
        }

        $stmt = $this->run("UPDATE $table SET $fieldDetails WHERE $whereDetails", $values);

        return $stmt->rowCount();
    }

    /**
     * Delete records
     * 
     * @param  string $table table name
     * @param  array  $where array of columns and values
     * @param  int    $limit limit number of records
     * @param int     Number of deleted rows
     */
    public function delete(string $table, array $where, int $limit = 1): int
    {
        //collect the values from collection
        $values = array_values($where);

        //setup where 
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            $key = '`' . trim($key, '`') . '`';
            $whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $i++;
        }

        //if limit is a number use a limit on the query
        if (is_numeric($limit)) {
            $limit = "LIMIT $limit";
        }

        $stmt = $this->run("DELETE FROM $table WHERE $whereDetails $limit", $values);

        return $stmt->rowCount();
    }

    /**
     * Delete all records
     * 
     * @param  string $table table name
     * @return int    Number of deleted rows
     */
    public function deleteAll(string $table): int
    {
        $stmt = $this->run("DELETE FROM $table");

        return $stmt->rowCount();
    }

    /**
     * Delete record by id
     * 
     * @param  string $table table name
     * @param  int    $id id of record
     * @return int    Number of deleted rows
     */
    public function deleteById(string $table, int $id): int
    {
        $stmt = $this->run("DELETE FROM $table WHERE id = ?", [$id]);

        return $stmt->rowCount();
    }

    /**
     * Delete record by ids
     * 
     * @param  string $table table name
     * @param  string $column name of column
     * @param  string $ids ids of records
     * @return int    Number of deleted rows
     */
    public function deleteByIds(string $table, string $column, string $ids): int
    {
        $stmt = $this->run("DELETE FROM $table WHERE $column IN ($ids)");

        return $stmt->rowCount();
    }

    /**
     * truncate table
     * 
     * @param  string $table table name
     * @return int    Number of deleted rows
     */
    public function truncate($table): int
    {
        $stmt = $this->run("TRUNCATE TABLE $table");

        return $stmt->rowCount();
    }
}

