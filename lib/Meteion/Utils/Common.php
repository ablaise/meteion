<?php

namespace Meteion\Utils;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Common
{
    /**
     * Converts a string to Snake case.
     */
    public static function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }

    /**
     * Checks if a string contains a given substring.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return false !== strpos($haystack, $needle);
    }

    /**
     * Checks if a string starts with a capital letter.
     */
    public static function isFirstLetterUppercase(string $input): bool
    {
        if (empty($input)) {
            return false;
        }

        return ctype_upper($input[0]);
    }

    /**
     * Gets direct directories from a given path.
     */
    public static function getDirectories(string $path): array
    {
        return array_map('realpath', glob($path.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR));
    }

    /**
     * Gets files from a given path.
     */
    public static function getFiles(string $path, bool $recursive = true): array
    {
        if (!$recursive) {
            return array_map('realpath', array_filter(glob($path.DIRECTORY_SEPARATOR.'*'), 'is_file'));
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $files[] = realpath($file->getPathname());
            }
        }

        return $files;
    }
}
