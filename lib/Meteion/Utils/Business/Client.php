<?php

namespace Meteion\Utils\Business;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Meteion\Model\Column;
use Meteion\Model\Data;
use Meteion\Model\File;
use Meteion\Utils\Common;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Client
{
    public const PK = 'pk';

    public const EXTRA_TABLE_NAME = 'metadata';

    public const TYPE_INT64 = 'int64';
    public const TYPE_UINT = 'uint';
    public const TYPE_INT = 'int';
    public const TYPE_BYTE = 'byte';
    public const TYPE_BIT = 'bit';
    public const TYPE_STR = 'str';
    public const TYPE_BOOL = 'bool';
    public const TYPE_SINGLE = 'single';

    public const TYPES = [
        self::TYPE_INT64 => Types::STRING,
        self::TYPE_UINT => Types::BIGINT,
        self::TYPE_INT => Types::INTEGER,
        self::TYPE_BYTE => Types::INTEGER,
        self::TYPE_BIT => Types::BOOLEAN,
        self::TYPE_STR => Types::STRING,
        self::TYPE_BOOL => Types::BOOLEAN,
        self::TYPE_SINGLE => Types::STRING,
    ];

    public const SPECIAL_CHARS_MAPPING = [
        '][' => '_',
        '}[' => '_',
        ']{' => '_',
        '[' => '_',
        ']' => '_',
        '{' => '_',
        '}' => '_',
        '<' => '_',
        '>' => '_',
        '/' => '_',
        '(' => '_',
        ')' => '_',
        '-' => '_',
        '\'' => '',
        '%' => '',
        ' ' => '',
    ];

    public const EXCEPTIONS = [
        'HousingPlacement' => Types::BOOLEAN,
    ];

    /**
     * Returns a database-compliant name.
     */
    public static function getColumnName(int $index, string $name): string
    {
        if ('#' === $name) {
            return self::PK;
        }

        if (empty($name)) {
            return 'column_'.$index;
        }

        $letter = $name[0];
        if (ctype_digit($letter)) {
            $name = ltrim($name, $letter).$letter;
        }

        $name = trim(rtrim(strtr($name, self::SPECIAL_CHARS_MAPPING), '_'));

        return Common::toSnakeCase($name);
    }

    public static function getFileName(string $name)
    {
        foreach (array_keys(self::SPECIAL_CHARS_MAPPING) as $filter) {
            $parts = explode($filter, $name);

            if (count($parts) > 1) {
                return $parts[0];
            }
        }

        return $name;
    }

    public static function getColumn(array $columnNames, int $index, string $value): Column
    {
        $columnName = $columnNames[$index];

        // handles the primary key, assuming they are all integers
        if (0 === $index) {
            return new Column('pk', Types::INTEGER, [], true);
        }

        //  handles foreign keys
        if (Common::isFirstLetterUppercase($value)) {
            // handling special cases
            if (array_key_exists($value, self::EXCEPTIONS)) {
                return new Column($columnName, self::EXCEPTIONS[$value], ['notnull' => false]);
            }

            return new Column($columnName, Types::INTEGER, ['notnull' => false]);
        }

        // handles types mapping
        foreach (Client::TYPES as $xivType => $dbalType) {
            if (Common::contains($value, $xivType)) {
                return new Column($columnName, $dbalType, ['notnull' => false]);
            }
        }

        throw new \LogicException('Type not found.');
    }

    public static function getData(int $index, string $value): Data
    {
        switch ($value) {
            case '':
                return new Data(ParameterType::STRING, '');
            case '0':
                if (self::isFloat($value)) {
                    return new Data(ParameterType::STRING, $value);
                }

                if (0 === $index) {
                    return new Data(ParameterType::INTEGER, $value);
                }

                return new Data(ParameterType::NULL, 'null');
            case 'True':
                return new Data(ParameterType::BOOLEAN, 'true');
            case 'False':
                return new Data(ParameterType::BOOLEAN, 'false');
            default:
                if (self::isFloat($value)) {
                    return new Data(ParameterType::STRING, $value);
                }

                if (is_numeric($value)) {
                    return new Data(ParameterType::INTEGER, (int) $value);
                }

                return new Data(ParameterType::STRING, $value);
        }
    }

    /**
     * Gets the CSV files into an array of File object.
     */
    public static function getFiles(string $path, bool $recursive = false): array
    {
        return array_map(function (string $input) {
            $file = new File();
            $file->path = $input;
            $file->name = basename($input, '.csv');
            $file->snake = Common::toSnakeCase($file->name);

            return $file;
        }, Common::getFiles($path, $recursive));
    }

    /**
     * Handles client's float types.
     */
    public static function isFloat($input): bool
    {
        if (is_string($input) && Common::contains($input, '.')) {
            $float = implode('', explode('.', $input));

            return is_numeric($float);
        }

        return is_float($input);
    }

    /**
     * Gets the max length of all given rows.
     */
    public static function getMaxLength(array $rows): int
    {
        return max(array_map(function (array $row) {
            return max(array_map(function ($item) {
                return strlen($item->value);
            }, $row));
        }, $rows));
    }

    /**
     * Returns the parsed content of a client CSV file.
     */
    public static function getContent(string $path)
    {
        $data = @file_get_contents($path);
        if (false === $data) {
            return false;
        }

        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        return $serializer->decode($data, CsvEncoder::FORMAT, [CsvEncoder::DELIMITER_KEY => ',']);
    }
}
