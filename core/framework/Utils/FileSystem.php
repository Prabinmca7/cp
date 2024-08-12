<?php

namespace RightNow\Utils;

/**
 * Methods useful for checking or modifying items on the filesystem.
 */
final class FileSystem extends \RightNow\Internal\Utils\FileSystem
{
    /**
     * Determines if the provided path is a directory and can be written to
     * @param string $dir The path to the directory
     * @return bool Whether the directory is writable
     */
    public static function isWritableDirectory($dir) {
        return is_dir($dir) && is_writable($dir);
    }

    /**
     * Determines if the provided path is a file and can be read
     * @param string $filePath The path to the file
     * @return bool Whether the file is readable
     */
    public static function isReadableFile($filePath) {
        return @is_file($filePath) && is_readable($filePath);
    }

    /**
     * Determines if the provided path is a directory and can be read
     * @param string $dir The path to the directory
     * @return bool Whether the directory is readable
     */
    public static function isReadableDirectory($dir) {
        return is_dir($dir) && is_readable($dir);
    }

    /**
     * Attempts to copy $source to $target, optionally overriding the destination. Throws an exception
     * if the copy failed
     * @param string $source Absolute path to copy from
     * @param string $target Absolute path to copy to
     * @param bool $shouldOverwrite If the destination file exists, should it be overwritten?
     * @return void
     * @throws \Exception Something went wrong and the copy failed
     */
    public static function copyFileOrThrowExceptionOnFailure($source, $target, $shouldOverwrite=true) {
        if (!$shouldOverwrite && self::isReadableFile($target)) {
            return;
        }
        if (!@copy($source, $target)) {
            throw new \Exception(sprintf(Config::getMessage(COULD_NOT_COPY_FILE_PCT_S_PCT_S_MSG), $source, $target));
        }
    }

    /**
     * Attempts to write the $contents to $path
     * @param string $path The fully qualified path to write the file to
     * @param string $contents Content to write to the file
     * @return void
     * @throws \Exception If the file could not be written.
     */
    public static function filePutContentsOrThrowExceptionOnFailure($path, $contents) {
        parent::mkdirOrThrowExceptionOnFailure(dirname($path), true);
        if (false === @file_put_contents($path, $contents)) {
            throw new \Exception(sprintf(Config::getMessage(WRITE_FILE_CONTENTS_PCT_S_MSG), $path));
        }
    }

    /**
     * Returns the filesystem path to the core, versioned asset directory and optionally appends the relative path sent in.
     * @param string $path An optional relative path to append onto the base path
     * @return string Fully qualified path
     */
    public static function getCoreAssetFileSystemPath($path=null){
        return HTMLROOT . Url::getCoreAssetPath($path);
    }

    /**
     * Return relative path to '/euf/generated/optimized/{timestamp}/'
     * This is used by optimized pages.
     * @return string
     * @internal
     */
    public static function getOptimizedAssetsDir() {
        static $directory; // cache
        if (!isset($directory)) {
            $directory = null;
            if ($timestamp = parent::getLastDeployTimestampFromFile() ?: parent::getLastDeployTimestampFromDir()) {
                $directory = sprintf("/euf/generated/%soptimized/$timestamp/", IS_STAGING ? 'staging/' . STAGING_LOCATION . '/' : '');
            }
        }
        return $directory;
    }
}