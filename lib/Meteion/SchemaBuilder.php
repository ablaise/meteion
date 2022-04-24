<?php

namespace Meteion;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;

class SchemaBuilder
{
    /**
     * @var AbstractSchemaManager
     */
    private $manager;

    /**
     * @var Connection
     */
    private $doctrine;

    /**
     * @var array
     */
    private $insert = [];

    public function __construct(array $connection)
    {
        $this->doctrine = DriverManager::getConnection($connection);
        $this->manager = $this->doctrine->createSchemaManager();
    }

    /**
     * Executes a raw query.
     */
    public function execute(string $query)
    {
        return $this->doctrine->executeStatement($query);
    }

    /**
     * Persists a table.
     */
    public function create(Table $table, bool $dropIfExists = false): bool
    {
        try {
            if ($dropIfExists) {
                $this->dropTableIfExists($table);
            }

            $this->manager->createTable($table);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Gets a table by its name.
     */
    public function getTable(string $name): ?Table
    {
        try {
            return $this->manager->listTableDetails($name);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Applies a diff between two tables and persists the result.
     */
    public function diffTable(Table $fromTable, Table $toTable): bool
    {
        try {
            $comparator = new Comparator();
            $diff = $comparator->diffTable($fromTable, $toTable);
            if (false !== $diff) {
                $this->manager->alterTable($diff);

                return true;
            }
        } catch (\Exception $ex) {
            return false;
        }

        return false;
    }

    /**
     * Drops a given table.
     */
    public function dropTableIfExists(Table $table): bool
    {
        try {
            $name = $table->getName();
            if ($this->tableExist($name)) {
                $this->manager->dropTable($name);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Inserts data via a dynamic query.
     */
    public function insert(Table $table, array $row): int
    {
        $statement = $this->doctrine->prepare($this->getSQLInsert($table));

        foreach ($row as $index => $data) {
            $statement->bindValue($index + 1, $data->value, $data->type);
        }

        return $statement->executeStatement();
    }

    /**
     * Checks if a table exists.
     */
    public function tableExist(string $table): bool
    {
        return $this->manager->tablesExist([$table]);
    }

    /**
     * Returns an insert query with prepared fields.
     */
    private function getSQLInsert(Table $table): string
    {
        if (!isset($this->insert[$table->getName()])) {
            $columns = $this->getColumns($table);

            $this->insert[$table->getName()] =
                sprintf('INSERT INTO %s (%s) VALUES (%s)',
                    $table->getName(),
                    implode(',', $columns),
                    implode(',', array_fill(0, count($columns), '?'))
                );
        }

        return $this->insert[$table->getName()];
    }

    /**
     * Returns an array of column names.
     */
    private function getColumns(Table $table): array
    {
        return array_values(array_map(function (Column $column) {
            return sprintf('"%s"', $column->getName());
        }, $table->getColumns()));
    }
}
