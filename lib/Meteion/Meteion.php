<?php

declare(strict_types=1);

namespace Meteion;

use Meteion\Model\File;
use Meteion\Utils\Business\Client;
use Meteion\Utils\Common;

class Meteion
{
    public const CSV_NAME = 0;
    public const CSV_TYPE = 1;

    /**
     * @var string
     */
    private $clientPath;

    /**
     * @var Worker
     */
    private $worker;

    /**
     * @var array
     */
    private $errors = [];

    public function __construct(string $clientPath, array $connection)
    {
        $this->clientPath = ltrim($clientPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $this->worker = new Worker($connection);
    }

    /**
     * Starts creating tables from CSV files.
     */
    public function run(): void
    {
        $files = Client::getFiles($this->clientPath);

        foreach ($files as $file) {
            $result = $this->process($file);
            if (!$result) {
                $this->errors[] = $files;
            }
        }

        foreach ($files as $file) {
            $result = $this->linkForeignKeys($file);
            if (!$result) {
                $this->errors[] = $files;
            }
        }

        $this->processSubfolders();
    }

    /**
     * Processes each CSV file and creates the associated table.
     */
    public function process(File $file): bool
    {
        $rows = Client::getContent($file->path);
        if (false === $rows) {
            return false;
        }

        foreach ($rows as $index => $row) {
            $row = array_values($row);
            switch ($index) {
                case self::CSV_NAME:
                    $this->worker->setColumnNames($row);
                    break;
                case self::CSV_TYPE:
                    $this->worker->setColumns($row);
                    break;
                default:
                    $this->worker->setRows($row);
                    break;
            }
        }

        $this->worker->persist($file->snake);

        return true;
    }

    /**
     * Tries to create foreign keys based on CSV files.
     */
    public function linkForeignKeys(File $file): bool
    {
        $rows = Client::getContent($file->path);
        if (false === $rows) {
            return false;
        }

        foreach ($rows as $index => $row) {
            $row = array_values($row);
            if (self::CSV_TYPE === $index) {
                foreach ($row as $csvIndex => $csvColumn) {
                    $name = Client::getFileName($csvColumn);
                    if (null === $name) {
                        continue;
                    }

                    if (Common::isFirstLetterUppercase($name) && file_exists($this->clientPath.$name.'.csv')) {
                        $tableFromName = $file->snake;
                        $tableToName = Common::toSnakeCase($name);
                        $this->worker->linkForeignKeys($tableFromName, $tableToName, $csvIndex);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Creates all the tables defined by the subfolders. Each table share the same format (key, 0, 1).
     */
    public function processSubfolders()
    {
        $directories = Common::getDirectories($this->clientPath);
        foreach ($directories as $directory) {
            $files = Client::getFiles($directory, true);

            $this->worker->createSubTable(basename($directory), $files);
        }
    }

    /**
     * Returns a list of tables that caused errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
