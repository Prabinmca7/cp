<?

namespace RightNow\Internal\Utils;

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem as FileSystemExternal;

class FileSystem{
    /**
     * Returns an array of widget manifest files found under the specified base paths.
     * @param mixed $basePaths An array of one or more base paths to search for widget manifest files
     * or a string if searching a single base path
     * @return array An array of widget view files found under the specified base paths.
     */
    public static function getListOfWidgetManifests($basePaths)
    {
        return self::getListOfFiles($basePaths, '@[/]info[.]yml$@');
    }

    /**
     * Get all templates in directory
     * @return array List of template names
     */
    public static function getListOfTemplates()
    {
        $templates = array();
        $basePath = APPPATH . '/views/templates/';
        self::getListOfTemplatesImpl($basePath, '', $templates);
        return $templates;
    }

    /**
     * License: MIT License
     * Date Added:  2/14/2008
     * First RNT Product Shipped:  CRM 8.5
     * RNT Developer: Devin Gray
     *
     * The MIT License for dircopy() and getDirectoryTree() functions located at http://us3.php.net/manual/en/function.copy.php#78500
     *
     * Copyright (c) <2007> <Max Zheng [mzheng@ariba.com]>
     *
     * Permission is hereby granted, free of charge, to any person obtaining a copy
     * of this software and associated documentation files (the "Software"), to deal
     * in the Software without restriction, including without limitation the rights
     * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
     * copies of the Software, and to permit persons to whom the Software is
     * furnished to do so, subject to the following conditions:
     *
     * The above copyright notice and this permission notice shall be included in
     * all copies or substantial portions of the Software.
     *
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
     * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
     * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
     * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
     * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
     * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
     * THE SOFTWARE.
     *
     * Copies a dir to another. Recursively.
     *
     * @param string $sourceDir Source directory to copy.
     * @param string $destDir Destination directory to copy to.
     * @param bool $overrideSourceTree If not false, expected to be an array of relative paths in the source directory to copy.
     * @param bool $shouldOverwrite If the destination file exists, should it be overwritten?
     * @return void
     */
    public static function copyDirectory($sourceDir, $destDir, $overrideSourceTree=false, $shouldOverwrite=true) {
        $sourceDir = Text::removeTrailingSlash($sourceDir);
        $destDir = Text::removeTrailingSlash($destDir);

        self::mkdirOrThrowExceptionOnFailure($destDir, true);
        $sourceTree = $overrideSourceTree ?: self::getDirectoryTree($sourceDir);
        foreach ($sourceTree as $file => $sourceModTime) {
            if ($sourceModTime === false) {
                // It's a directory.
                self::mkdirOrThrowExceptionOnFailure("$destDir/$file");
            }
            else {
                FileSystemExternal::copyFileOrThrowExceptionOnFailure("$sourceDir/$file", "$destDir/$file", $shouldOverwrite);
                touch("$destDir/$file", $sourceModTime);
            }
        }
    }

    /**
     * Moves a directory. Recursively. Maintaining structure.
     * @param string $source The directory to move
     * @param string $destination Where the directory is to be moved
     * @param string|array|bool $filter Filter to apply to subfiles
     * @see #getDirectoryTree for format
     */
    public static function moveDirectory($source, $destination, $filter=false) {
        if (!is_dir($destination)) {
            self::mkdirOrThrowExceptionOnFailure($destination);
        }
        foreach (self::getDirectoryTree($source, $filter) as $path => $isFile) {
            if ($isFile === false) {
                self::mkdirOrThrowExceptionOnFailure($destination . $path);
            }
            else {
                self::renameOrThrowExceptionOnFailure($source . $path, $destination . $path);
            }
        }
    }

    /**
     * Deletes all files and folders within a directory. Recursively.
     * @param string $dir File system directory to remove
     * @param bool $removeTopLevel Flag to indicate if top level folder should also be deleted
     */
    public static function removeDirectory($dir, $removeTopLevel) {
        if (!$dh = @opendir($dir)) {
            return;
        }

        while (false !== ($obj = readdir($dh))) {
            if ($obj === '.' || $obj === '..') {
                continue;
            }
            $path = $dir . '/' . $obj;
            if(is_dir($path)){
                self::removeDirectory($path, true);
            }
            else{
                @unlink($path);
            }
        }

        closedir($dh);
        if ($removeTopLevel) {
            @rmdir($dir);
        }
    }

    /**
     * Gets a recursive listing of a directory.
     * @param string $dir Directory or file without ending slash
     * @param string|array|bool $filter If set to a string, the only files with that extension will be returned. If an array, only files with those extensions will be returned.
     * If the array contains a 'regex' key, the value is expected to be a regular expression which will filter files. If the array contains a 'not' key, it will invert the meaning of
     * the filter. If false, all files will be returned.
     * @param mixed &$tree Used for recursion; should not be set by external callers
     * @param int $baseDirectoryLength Amount of base directory to truncate in results
     * @return array Directory and file in an associative array with file modified time as value
     * for files and false as the value for directories.
     */
    public static function getDirectoryTree($dir, $filter = false, &$tree = null, $baseDirectoryLength = 0) {
        if ($tree === null) {
            $tree = array();
            $rootDirectory = true;
            $baseDirectoryLength = strlen($dir) + 1;
        }
        else{
            $rootDirectory = false;
        }

        if (is_file($dir)) {
            $ext = pathinfo($dir, PATHINFO_EXTENSION);
            if (Text::endsWith($dir, '.swp') && Text::beginsWith(basename($dir), '.')) {
                $includeFile = false; // Always ignore vi swap files.  Nobody wants those.
            }
            else if (is_array($filter) && array_key_exists('regex', $filter)) {
                $includeFile = preg_match($filter['regex'], $dir) ^ array_key_exists('not', $filter);
            }
            else if (is_array($filter)) {
                // If it's in the filter list and the magic 'not' key doesn't exist or
                // it's not in the filter list and the magic 'not' key does exist.
                $includeFile = (in_array($ext, $filter, true) != (array_key_exists('not', $filter)));
            }
            else if (is_string($filter)) {
                $includeFile = ($ext === $filter);
            }
            else {
                $includeFile = true;
            }
            if ($includeFile) {
                $tree[substr($dir, $baseDirectoryLength)] = filemtime($dir);
            }
        }
        else if (is_dir($dir) && substr($dir, -4) != '/CVS' && $di = dir($dir)) {
            if (!$rootDirectory) {
                $tree[substr($dir, $baseDirectoryLength)] = false;
            }
            while (($file = $di->read()) !== false) {
                if ($file !== '.' && $file !== '..') {
                    self::getDirectoryTree("$dir/$file", $filter, $tree, $baseDirectoryLength);
                }
            }
            $di->close();
        }
        return $tree;
    }

    /**
     * The list that #getDirectoryTree returns includes the directories as keys with
     * false for their value. This function removes those entries, leaving only the keys
     * which are files.
     * @param array $tree A result for #getDirectoryTree
     * @return array The $tree minus the directories.
     */
    public static function removeDirectoriesFromGetDirTreeResult(array $tree) {
        return array_filter($tree);
    }

    /**
     * Returns a list of files with a .php extension given a folder
     * @param string $directory Fully qualified path to directory
     * @return array List of PHP files in the directory
     */
    public static function getDirectoryPhpFiles($directory)
    {
        return array_keys(self::removeDirectoriesFromGetDirTreeResult(self::getDirectoryTree($directory, 'php')));
    }

    /**
     * Retrieves a list of files/directories within the path specified and sorts them by the method specified.
     * @param string $directory The directory to search within
     * @param string|null $sortMethod Sort method. Defaults to case-insensitive string comparison
     * @param array|null $filter Filter to apply. See #listDirectory for details
     * @return array Array containing directory contents
     */
    public static function getSortedListOfDirectoryEntries($directory, $sortMethod = null, $filter = null)
    {
        $entries = array();
        if (is_dir($directory)) {
            $entries = self::listDirectory($directory, false, false, $filter);
            usort($entries, $sortMethod ?: 'strcasecmp');
        }
        return $entries;
    }

    /**
     * Gets all files and subfiles within a directory. Does not include the subdirectories themselves.
     * @param string $startDir The directory from which to search
     * @param int $maxDepth Limits the number of recursions; default is no limit
     * @param int $currentDepth Used by recursive calls; shouldn't be set by external callers
     * @return array|bool Array of listing or false if $startDir is invalid
     */
    public static function listDirectoryRecursively($startDir='.', $maxDepth = 0, $currentDepth = 1) {
        $files = array();
        if ($fileHandle = @opendir($startDir)) {
            while (($file = readdir($fileHandle)) !== false) {
                if ($file === '.' || $file === '..' || $file === 'CVS') {
                    continue;
                }
                $filePath = "$startDir/$file";
                $isDir = is_dir($filePath);
                if ((!$maxDepth || $maxDepth > $currentDepth) && $isDir ) {
                    $files = array_merge($files, self::listDirectoryRecursively($filePath, $maxDepth, $currentDepth + 1));
                }
                else if (!$isDir ) {
                    array_push($files, $filePath);
                }
            }
            closedir($fileHandle);
        }
        else {
            $files = false;
        }
        return $files;
    }

    /**
     * Return directory/file listing as an array.
     * @param string $path Absolute path to a valid directory.
     * @param bool $addFullPath Specify true to return absolute paths to files and directories.
     * @param bool $recursive Specify true to return recursive directory results.
     * @param array|null $filter A two element array used to filter results.
     *                          Examples:
     *                            array('equals',     'view.php')
     *                            array('not equals', 'view.php')
     *                            array('match',      '/^.+\.php|html$/')
     *                            array('not match',  '/^.+\.php|html$/')
     *                            array('method',     'isDir') - any DirectoryIterator method that returns true/false.
     *                            array('function',   function($f) {return $f->getSize() > 1000000;})
     * @param array $extraData Additional columns added to results array.
     *                          Examples:
     *                            array('getType') - adds a column specifying 'dir', 'file' or 'link'.
     *                            array(function($f) {return $f->isReadable();}) - adds a column specifying true or false.
     * @param string $originalPath Should only be called from within listDirectory().
     * @return array
     * @throws \Exception If $path is not a valid directory
     */
    public static function listDirectory($path, $addFullPath = false, $recursive = false, $filter = null, array $extraData = array(), $originalPath = null) {
        try {
            $directoryIterator = new \DirectoryIterator($path);
        }
        catch (\UnexpectedValueException $e) {
            throw new \Exception(\RightNow\Utils\Config::getMessage(INVALID_PATH_LBL) . $path);
        }

        if ($originalPath === null) {
            $originalPath = $path;
        }

        if ($filter !== null) {
            list($filterMethod, $filterValue) = $filter;
            switch ($filterMethod) {
                case 'equals':
                    $filterFunction = function($f) use ($filterValue) {return $f->getFilename() === "$filterValue";};
                    break;
                case 'not equals':
                    $filterFunction = function($f) use ($filterValue) {return $f->getFilename() !== "$filterValue";};
                    break;
                case 'match':
                    $filterFunction = function($f) use ($filterValue) {return preg_match("$filterValue", $f->getFilename()) === 1;};
                    break;
                case 'not match':
                    $filterFunction = function($f) use ($filterValue) {return preg_match("$filterValue", $f->getFilename()) !== 1;};
                    break;
                case 'method':
                    $filterFunction = function($f) use ($filterValue) {return $f->$filterValue();};
                    break;
                case 'function':
                    $filterFunction = $filterValue;
                    break;
                default:
                    throw new \Exception("Not a valid filter method: $filterMethod");
            }
        }

        $listing = array();
        foreach ($directoryIterator as $fileInfo) {
            $fullPath = $fileInfo->getPathname();
            if ($fileInfo->isDot() || $fileInfo->getFilename() === 'CVS') {
                continue;
            }

            if ($filter === null || $filterFunction($fileInfo) === true) {
                $pathName = $addFullPath ? $fullPath : ltrim(Text::getSubstringAfter($fullPath, $originalPath), '/');
                $data = empty($extraData) ? $pathName : array($pathName);
                foreach ($extraData as $func) {
                    if (is_string($func) && method_exists($fileInfo, $func)) {
                        array_push($data, $fileInfo->$func());
                    }
                    else if (is_callable($func)) {
                        array_push($data, $func($fileInfo));
                    }
                    else {
                        array_push($data, "__ERROR__ - invalid function: '$func'");
                    }
                }
                array_push($listing, $data);
            }
            if ($recursive && $fileInfo->isDir()) {
                $listing = array_merge($listing, self::listDirectory($fullPath, $addFullPath, $recursive, $filter, $extraData, $originalPath));
            }
        }
        return $listing;
    }

    /**
     * Determines if the provided path is a directory and can be written to
     * @param string $target The path to the directory
     * @param bool $recursive Whether to create parent directories if they don't exist
     * @throws \Exception If directory could not be created
     */
    public static function mkdirOrThrowExceptionOnFailure($target, $recursive = false) {
        if (!is_dir($target)) {
            if (!@mkdir($target, 0777, $recursive)) {
                throw new \Exception(sprintf(\RightNow\Utils\Config::getMessage(COULD_NOT_CREATE_DIRECTORY_PCT_S_MSG), $target));
            }
        }
    }

    public static function renameOrThrowExceptionOnFailure($source, $target) {
        if (!@rename($source, $target)) {
            throw new \Exception(sprintf(\RightNow\Utils\Config::getMessage(COULD_NOT_MOVE_PCT_S_TO_PCT_S_MSG), $source, $target));
        }
    }

    public static function removeDirectoryRecursivelyOrThrowExceptionOnFailure($dir, $removeTopLevel) {
        self::removeDirectory($dir, $removeTopLevel);
        if (($removeTopLevel === true && FileSystemExternal::isReadableDirectory($dir)) || ($removeTopLevel === false && FileSystemExternal::isReadableDirectory($dir) && self::listDirectory($dir) !== array())) {
            throw new \Exception(sprintf(\RightNow\Utils\Config::getMessage(COULD_NOT_REMOVE_DIRECTORY_PCT_S_LBL), $dir));
        }
    }

    /**
     * Return contents of $filePath, which should contain 10-digit epoch time from last deploy.
     * This is used by optimized pages.
     * @param string $filePath Defaults to DEPLOY_TIMESTAMP_FILE define (euf/application/production/deployTimestamp or
     *                             euf/application/staging/staging_XX/deployTimestamp)
     * @return mixed First 10 digits of specified file contents or null if file does not exist.
     */
    public static function getLastDeployTimestampFromFile($filePath = DEPLOY_TIMESTAMP_FILE) {
        if (FileSystemExternal::isReadableFile($filePath)) {
            return file_get_contents($filePath, false, null, 0, 10);
        }
    }

    /**
     * Returns the most recent timestamp directory from the base directory provided
     * @param string $basePath Defaults to OPTIMIZED_ASSETS_PATH define (HTMLROOT/euf/generated/optimized)
     * @return mixed A 10 digit epoch time from $basePath/[timestamp] directory or null if no matching directories found.
     */
    public static function getLastDeployTimestampFromDir($basePath = OPTIMIZED_ASSETS_PATH) {
        // @codingStandardsIgnoreStart
        if ($timestamps = self::listDirectory($basePath, false, false, array('function', function($f) {return $f->isDir() && preg_match('/^[0-9]{10}$/', $f->getFilename());}))) {
            rsort($timestamps);
            return $timestamps[0];
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * Write epoch time of last deploy to deployTimestamp file.
     *
     * @param string $mode Production or staging_XX.
     * @param string $timestamp A10 digit epoch time. If null, the epoch time will be determined by what is on disk in the mode's optimized/<timestamp> directory.
     * @throws \Exception If $mode is not a valid value
     */
    public static function writeDeployTimestampToFile($mode = 'production', $timestamp = null) {
        require_once CPCORE . 'Internal/Libraries/Staging.php';
        if ($mode === 'production') {
            $optimizedDir = OPTIMIZED_ASSETS_PATH;
            $filePath = DEPLOY_TIMESTAMP_FILE;
        }
        else if (\RightNow\Internal\Libraries\Staging::isValidStagingName($mode)) {
            $optimizedDir = HTMLROOT . "/euf/generated/staging/$mode/optimized";
            $filePath = OPTIMIZED_FILES . "staging/$mode/" . basename(DEPLOY_TIMESTAMP_FILE);
        }
        else {
            throw new \Exception("Not a valid mode: '$mode'");
        }

        if ($timestamp === null) {
            $timestamp = self::getLastDeployTimestampFromDir($optimizedDir);
        }
        else if (!preg_match('/^\d{10}$/', $timestamp)) {
            throw new \Exception("Not a valid timestamp: '$timestamp'");
        }

        if ($timestamp) {
            FileSystemExternal::filePutContentsOrThrowExceptionOnFailure($filePath, $timestamp);
        }
    }

    /**
     * Like realpath() in that it fixes '..', '.', and '//' but doesn't actually look at the filesystem,
     * so it doesn't tell you if the file exists.  Also it can't help you if the path tries to '../' out of
     * the root of the path passed in.
     *
     * @param string $path Relative or absolute path to normalize.
     * @return string Normalized path or false if the path couldn't be normalized.
     */
    public static function normalizePath($path) {
        $leadingSlash = Text::beginsWith($path, '/') ? '/' : '';
        $segments = array();
        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                if (count($segments) === 0) {
                    return false;
                }
                array_pop($segments);
            }
            else if($segment !== '' && $segment !== '.') {
                array_push($segments, $segment);
            }
        }
        return $leadingSlash . implode('/', $segments);
    }

    /**
     * Returns an array of files matching the given regex found under the specified base paths.
     * @param mixed $basePaths An array of one or more base paths to search for widget view files
     * or a string if searching a single base path
     * @param string $regex File regex
     * @return array An array of widget view files found under the specified base paths.
     */
    private static function getListOfFiles($basePaths, $regex) {
        if (is_string($basePaths))
        {
            $basePaths = array($basePaths);
        }
        $views = array();
        foreach ($basePaths as $basePath)
        {
            $views = array_merge($views, self::removeDirectoriesFromGetDirTreeResult(self::getDirectoryTree($basePath, array('regex' => $regex))));
        }
        return array_keys($views);
    }

    /**
     * Gets an array of templates
     * @param string $basePath Base path to directory
     * @param string $intermediatePath Path to append
     * @param array &$templates Array of templates which is populated
     */
    private static function getListOfTemplatesImpl($basePath, $intermediatePath, array &$templates)
    {
        foreach (self::getSortedListOfDirectoryEntries($basePath . $intermediatePath) as $filename)
        {
            $path = $intermediatePath . $filename;
            if (is_file($basePath . $path) && (strlen($filename) > 4) && (strcasecmp(substr($filename, strlen($filename) - 4), '.php') === 0))
            {
                array_push($templates, $path);
            }
            else if (is_dir($basePath . $path))
            {
                self::getListOfTemplatesImpl($basePath, "$path/", $templates);
            }
        }
    }
}
