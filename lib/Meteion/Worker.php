<?php

declare(strict_types=1);

namespace Meteion;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Meteion\Model\Data;
use Meteion\Utils\Business\Client;

class Worker
{
    /**
     * @var SchemaBuilder
     */
    private $builder;

    /**
     * @var array
     */
    private $columnNames;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var array
     */
    private $rows;

    public function __construct(array $connection)
    {
        $this->builder = new SchemaBuilder($connection);
    }

    /**
     * Creates a "Table" object built based to the processed data and persists it.
     */
    public function persist(string $file): void
    {
        $table = new Table($file);

        $maxLength = Client::getMaxLength($this->rows);

        foreach ($this->columns as $column) {
            if ($maxLength > 255) {
                $column->options['length'] = $maxLength + 1;
            }

            $table->addColumn($column->name, $column->type, $column->options);
            if ($column->pk) {
                $table->setPrimaryKey([$column->name]);
            }
        }

        if ($this->builder->create($table, true)) {
            foreach ($this->rows as $row) {
                $this->builder->insert($table, $row);
            }
        }

        $this->reset();
    }

    /**
     * Links the foreign keys of two given tables.
     */
    public function linkForeignKeys($tableFromName, $tableToName, $index): bool
    {
        $vanilla = $this->builder->getTable($tableFromName);
        $tableFrom = $this->builder->getTable($tableFromName);
        $tableTo = $this->builder->getTable($tableToName);

        if (null === $tableFrom) {
            return false;
        }

        if (null === $tableTo) {
            return false;
        }

        // gets the column's name based on its index
        $columns = array_values($tableFrom->getColumns());
        if (!isset($columns[$index])) {
            return false;
        }
        $column = $columns[$index]->getName();
        if (false === $tableFrom->hasColumn($column)) {
            return false;
        }

        // both columns must have the same type
        $typeFrom = $tableFrom->getColumn($column)->getType();
        $typeTo = $tableTo->getColumn('pk')->getType();
        if ($typeFrom !== $typeTo) {
            return false;
        }

        // does the table already have the foreign key?
        $foreignKeyColumns = $tableFrom->getForeignKeyColumns();
        if (isset($foreignKeyColumns[$column])) {
            return false;
        }

        $tableFrom->addForeignKeyConstraint($tableTo, [$column], ['pk']);

        // at this point inconsistent indexes can safely be removed
        $this->removeInvalidColumnId($tableFrom->getName(), $tableTo->getName(), $column);

        return $this->builder->diffTable($vanilla, $tableFrom);
    }

    /**
     * Creates a special table based on the files contained in one of the client's subfolders.
     * e.g. All CSV files in the "quest" folder will be inserted in a single table and linked by an internal identifier.
     * Doing this avoids having thousands of tables.
     */
    public function createSubTable(string $name, array $files): bool
    {
        $data = [];

        foreach ($files as $file) {
            $content = Client::getContent($file->path);
            if (false === $content) {
                return false;
            }

            // setting file name
            $data[$file->name] = [];

            // skipping headers
            $data[$file->name] = array_merge($data[$file->name], array_slice($content, 2));
        }

        if (!empty($data)) {
            $table = new Table($name.'_'.Client::EXTRA_TABLE_NAME);
            $table->addColumn('pk', Types::INTEGER);
            $table->addColumn('reference_id', Types::INTEGER);
            $table->addColumn('reference_table', Types::STRING);
            $table->addColumn('column1', Types::STRING);
            $table->addColumn('column2', Types::STRING, ['length' => pow(2, 12)]);
            $table->setPrimaryKey(['pk']);

            $this->builder->create($table, true);

            $pk = 0;
            foreach ($data as $fileName => $columns) {
                foreach ($columns as $column) {
                    $row = [
                        new Data(ParameterType::INTEGER, ++$pk),
                        new Data(ParameterType::INTEGER, $column['key']),
                        new Data(ParameterType::STRING, $fileName),
                        new Data(ParameterType::STRING, $column['0']),
                        new Data(ParameterType::STRING, $column['1']),
                    ];

                    $this->builder->insert($table, $row);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Returns all the valid column names of a CSV file.
     */
    public function setColumnNames(array $values): void
    {
        $this->columnNames = array_map(function ($value, $index) {
            return Client::getColumnName($index, $value);
        }, $values, array_keys($values));
    }

    /**
     * Gets all the "Column" objects of a CSV file.
     */
    public function setColumns(array $values): void
    {
        $this->columns = array_map(function ($value, $index) {
            return Client::getColumn($this->columnNames, $index, $value);
        }, $values, array_keys($values));
    }

    /**
     * Processes all the data in a row and creates the associated "Data" objects.
     */
    public function setRows(array $values): void
    {
        $this->rows[] = array_map(function ($value, $index) use (&$toString) {
            // some primary keys in the client are not integers, but strings
            if (0 === $index && Client::isFloat($value)) {
                if (count($this->columns) > 1) {
                    $this->columns[0]->type = Types::STRING;
                }
            }

            return Client::getData($index, $value);
        }, $values, array_keys($values));
    }

    /**
     * Removes inconsistent indexes from a table so that the associated foreign key can be created.
     */
    private function removeInvalidColumnId(string $tableFrom, string $tableTo, string $column): int
    {
        return $this->builder->execute(
            sprintf('UPDATE "%s" SET "%s" = NULL WHERE "%s" IN (SELECT DISTINCT "%s" FROM "%s" WHERE "%s" NOT IN (SELECT pk FROM "%s"))',
                $tableFrom,
                $column,
                $column,
                $column,
                $tableFrom,
                $column,
                $tableTo
            )
        );
    }

    /**
     * Resets the worker data.
     */
    public function reset(): void
    {
        $this->columnNames = [];
        $this->columns = [];
        $this->rows = [];
    }
}
