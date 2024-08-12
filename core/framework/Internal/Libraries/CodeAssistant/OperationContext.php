<?php

namespace RightNow\Internal\Libraries\CodeAssistant;

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Config,
    RightNow\Internal\Utils\CodeAssistant as CodeAssistantUtils;

/**
 * A base class used as the context when performing operations for the Code Assistant tool. It is used to provide
 * helper methods and a file system abstraction.
 */
class OperationContext {
    protected $fileCache = array();
    protected $writableFileCache = array();
    protected $removedFiles = array();
    protected $directoryCache = array();
    protected $absolutePath = CUSTOMER_FILES;

    protected $instructionSet = array();
    protected $messages = array();
    protected $errors = array();

    /**
     * Insert an instruction into the list of instructions
     * @param string $name The name of the instruction being inserted
     * @param array $data An array of data associated to the instruction
     */
    protected function addInstruction($name, array $data) {
        $this->instructionSet[] = array_merge(array('type' => $name), $data);
    }

    /**
     * Given an absolute path, create an object which can be passed down to the client and re-combined later during the
     * operation confirmation stage to create an absolute path. The object contains the following keys:
     *  * `key` => A key that maps into a path in CodeAssistantUtils::getReadablePaths, used to prevent exposing fully qualified paths to JS
     *  * `hiddenPath` => A segment of the path that is not visible to the user, determined based upon the classes absolute path from setAbsolutePath
     *  * `visiblePath` => The segment which is actually shown to the user, anything not in `$this->absolutePath`
     * @param string $path The path being decomposed
     * @return An array containing the above mentioned keys
     * @throws \Exception If $path is not readable
     */
    protected function createPathObject($path) {
        if($key = CodeAssistantUtils::getReadablePathKey($path)) {
            $keyPath = Text::getSubstringAfter($path, CodeAssistantUtils::getReadablePaths($key));
            $visiblePath = Text::getSubstringAfter($path, $this->absolutePath, $keyPath);
            $hiddenPath = Text::getSubstringBefore($keyPath, $visiblePath, '');
            return array('key' => $key, 'hiddenPath' => $hiddenPath, 'visiblePath' => $visiblePath);
        }
        throw new \Exception(sprintf(Config::getMessage(PATH_PCT_S_ACC_CODE_ASSISTANT_MSG), $path));
    }

    /**
     * Given a relative or absolute path remove duplicate slashes, make the path absolute, and verify that the complete path is
     * either readable or writable depending on whether it impacts the file system
     * @param string $path The path
     * @param boolean $impactsFileSystem True if the path must be writable, false if readable
     * @return string The absolute path
     * @throws \Exception If path provided is not writable
     */
    protected function normalizePath($path, $impactsFileSystem = true) {
        if($path[0] !== '/') {
            $path = $this->absolutePath . $path;
        }
        $path = FileSystem::normalizePath(rtrim($path, '/'));

        //Check if the paths are accessible, we don't want to allow access to paths here, but prevent them during the confirmation stage.
        if($impactsFileSystem && !CodeAssistantUtils::isWritablePath($path)) {
            throw new \Exception(sprintf(Config::getMessage(PATH_PCT_S_WRITABLE_PATH_MODIFIED_MSG), $path));
        }
        if(!$impactsFileSystem && !CodeAssistantUtils::isReadablePath($path)) {
            throw new \Exception(sprintf(Config::getMessage(PATH_PCT_S_ACC_CODE_ASSISTANT_MSG), $path));
        }

        return $path;
    }

    /**
     * Determine if the given path is a file based on the cache or disk. The path must be absolute.
     * @param string $path The absolute path to the file
     * @return boolean True or false whether the path points to a file
     */
    protected function isFile($path) {
        return (!$this->removedFiles[$path] && ($this->writableFileCache[$path] || $this->fileCache[$path] || @is_file($path)));
    }

    /**
     * Determine if the given path is a directory based on the cache or disk. The path must be absolute.
     * @param string $path The absolute path to the directory
     * @return boolean True or false whether the path points to a directory
     */
    protected function isDir($path) {
        return ($this->directoryCache[$path] || @is_dir($path));
    }

    /**
     * Determine if the given path already exists
     * @param string $path The absolute path to the file or directory
     * @return boolean True or false whether the path exists
     */
    public function fileExists($path) {
        $path = $this->normalizePath($path, false);
        return $this->isFile($path) || $this->isDir($path);
    }

    /**
     * Check if a file is writable in the virtual file system or the underlying file system. Abstract away
     * the fact that we are pretending to be the FS. If the file or directory does not exist, this will always
     * return false. To check if a file can be created, we must first check if the parent directory is writable.
     * @param string $path The path to the directory or file being checked
     * @return boolean True or false whether the path is writable.
     */
    protected function isWritable($path) {
        return !$this->removedFiles[$path] && ($this->directoryCache[$path] || $this->writableFileCache[$path] || (CodeAssistantUtils::isWritablePath($path) && @is_readable($path) && @is_writable($path)));
    }

    /**
     * Get the cached file for the given absolute path
     * @param string $path The path
     * @return boolean|string False if the file is not cached or the file content
     */
    protected function getCachedFile($path) {
        if(isset($this->writableFileCache[$path])) return $this->writableFileCache[$path];
        if(isset($this->fileCache[$path])) return $this->fileCache[$path];
        return false;
    }

    /**
     * Add an error instruction
     * @param string $error The error message
     */
    public function addError($error) {
        $this->errors[] = $error;
    }

    /**
     * Add an information message instruction
     * @param string $message The information message
     */
    public function addMessage($message) {
        $this->messages[] = $message;
    }

    /**
     * Get a list of all of the errors which have been added by this operation
     * @return array The list of errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get a list of all of the messages which have been added by this operation
     * @return array The list of messages
     */
    public function getMessages() {
        return (count($this->errors)) ? array() : $this->messages;
    }

    /**
     * Retrieve the list of instructions for this instance. If an error occurred, don't return instructions.
     * @return array The list of instructions
     */
    public function getInstructions() {
        return (count($this->errors)) ? array() : $this->instructionSet;
    }

    /**
     * Set the absolute path which is used for all file operation functions. Defaults to `CUSTOMER_FILES`. If
     * a path is specified, all paths displayed in the user interface will be relative to this path
     * for example, if the absolute path is CUSTOMER_FILES and the custom widget TestInput view.php file is modified
     * the user will only see the path from CUSTOMER_FILES to the view.php file, not the full path
     * @param string|array $path The Path
     * @throws \Exception If path isn't a string or path array doesn't have / in the 0 index
     */
    public function setAbsolutePath($path) {
        if(!is_string($path) || $path[0] !== '/') {
            throw new \Exception(Config::getMessage(PATH_MUST_BE_STRING_AND_ABSOLUTE_MSG));
        }
        $this->absolutePath = $path;
    }

    /**
     * Retrieve the currently set absolute path.
     * @return The absolute path
     */
    public function getAbsolutePath() {
        return $this->absolutePath;
    }

    /**
     * Retrieve the contents of a file and cache it in case the file is retrieved later.
     * @param string $path The Path
     * @return string|boolean The file content or false
     */
    public function getFile($path) {
        $path = $this->normalizePath($path, false);
        if($cached = $this->getCachedFile($path)) return $cached;
        if(!$this->isFile($path) || (($fileContent = @file_get_contents($path)) === false)) return false;

        if($this->isWritable($path)) {
            return $this->writableFileCache[$path] = $fileContent;
        }
        return $this->fileCache[$path] = $fileContent;
    }
}