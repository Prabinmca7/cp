<?php

namespace RightNow\Internal\Libraries\CodeAssistant;


require_once CPCORE . 'Internal/Libraries/CodeAssistant/OperationContext.php';

/**
 * Used for any Code Assistant operations which are type `conversion`. This class provides an abstraction
 * of the file system so that the Code Assistant can display the file changes to the user before actually
 * modifying the files on disk. All of the actual file operations are performed in the CodeAssistant Utility.
 */
class Conversion extends OperationContext {
    protected $tempDir;
    public function __construct() {
        $this->tempDir = get_cfg_var('upload_tmp_dir') . '/';
    }

    /**
     * Determine if the given absolute path can be created
     * @param string $path The path
     * @return boolean True or false if the file or directory can be created
     */
    private function isCreatable($path) {
        $parent = substr($path, 0, strrpos($path, '/'));
        return !$this->fileExists($path) && $this->isDir($parent) && $this->isWritable($parent);
    }

    /**
     * Create a temporary file that can be referenced in subsequent requests to the Code Assistant.
     * @param string $content The content to be written to a temporary file
     * @return string The path of the newly created file
     * @throws \Exception If content is not readable
     */
    private function createTempFile($content) {
        $tmpFile = 'cp-admin-file-' . sha1($content);
        $tmpPath = $this->tempDir . $tmpFile;

        if(!@is_readable($tmpPath) && !file_put_contents($tmpPath, $content)) {
            throw new \Exception(sprintf("A temporary file could not be created at '%s'. Please try again later.", $tmpPath));
        }
        return $tmpFile;
    }

    /**
     * Emulate the creation of a new directory on the file system. Store an instruction that specifies
     * where the directory is created so that we can show the user before actually creating the directory
     * @param string $path The path relative to the path set with `$this->setAbsolutePath`
     * @return boolean True if the directory can be created, false if not
     */
    public function createDirectory($path) {
        $absPath = $this->normalizePath($path);
        if(!$this->isCreatable($absPath)) return false;

        $this->addInstruction('createDirectory', array(
            'source' => $this->createPathObject($absPath),
        ));

        return $this->directoryCache[$absPath] = true;
    }

    /**
     * Emulate the removal of a file. Store an instruction that can be displayed to the user.
     * @param string $path The path relative to the path set with `$this->setAbsolutePath`
     * @return boolean True if the file can be deleted, false if not
     */
    public function deleteFile($path) {
        $absPath = $this->normalizePath($path);
        if(!$this->isFile($absPath) || !$this->isWritable($absPath)) return false;

        $this->addInstruction('deleteFile', array(
            'source' => $this->createPathObject($absPath)
        ));

        unset($this->writableFileCache[$absPath]);
        return $this->removedFiles[$absPath] = true;
    }

    /**
     * Emulate the creation of a file. If the file already exists it will not be overwritten. Use `deleteFile` then `createFile` if
     * that is the case.
     * @param string $path The path relative to the path set with `$this->setAbsolutePath`
     * @param string $content The content for the newly created file
     * @return boolean True if the file can be created, false if not
     */
    public function createFile($path, $content) {
        $absPath = $this->normalizePath($path);
        if(!$this->isCreatable($absPath)) return false;

        //Create a temporary file and store off the new operation
        $this->addInstruction('createFile', array(
            'source' => $this->createPathObject($absPath),
            'tempSource' => $this->createTempFile($content),
        ));

        $this->writableFileCache[$absPath] = $content;
        unset($this->removedFiles[$absPath]);
        return true;
    }

    /**
     * This is functionally the same as calling delete then create on the same file, but we store a modify operation instead so that
     * the UI can correctly display the action that occurred.
     * @param string $path The file to be modified
     * @param string $content The complete file content
     * @return boolean True on success, false on failure
     */
    public function modifyFile($path, $content) {
        $absPath = $this->normalizePath($path);
        if((($oldContent = $this->getFile($absPath)) === false) || !$this->isWritable($absPath)) return false;

        //Create a temporary file and store off the new operation
        $this->addInstruction('modifyFile', array(
            'source' => $this->createPathObject($absPath),
            'tempSource' => $this->createTempFile($content),
        ));

        $this->writableFileCache[$absPath] = $content;
        return true;
    }

    /**
     * This is functionally the same as calling delete on one file then create with the same content in a different location. The
     * only difference is that we convey this operation to the user as a single step. This function will NOT overwrite a
     * destination file that already exists.
     * @param string $source The source file
     * @param string $destination The destination file
     * @return boolean True on success, false on failure
     */
    public function moveFile($source, $destination) {
        $absSource = $this->normalizePath($source);
        $absDestination = $this->normalizePath($destination);

        if((($content = $this->getFile($absSource)) === false) || !$this->isCreatable($absDestination)) return false;
        if(!$this->isWritable($absSource)) return false; //Make sure that the source file can actually be deleted.

        $this->addInstruction('moveFile', array(
            'source' => $this->createPathObject($absSource),
            'destination' => $this->createPathObject($absDestination),
            'tempSource' => $this->createTempFile($content),
        ));

        $this->writableFileCache[$absDestination] = $content;

        unset($this->writableFileCache[$absSource]);
        $this->removedFiles[$absSource] = true;
        return true;
    }

    /**
     * Emulates moving a directory from $source to $destination.
     * This function will NOT overwrite an existing destination directory.
     * @param string $source The source directory.
     * @param string $destination The destination directory.
     * @return boolean True on success, false on failure
     */
    public function moveDirectory($source, $destination) {
        $absSource = $this->normalizePath($source);
        $absDestination = $this->normalizePath($destination);

        if ($this->isDir($absDestination) || !$this->isCreatable($absDestination) || !$this->isWritable($absSource)) {
            return false;
        }

        $this->addInstruction('moveDirectory', array(
            'source' => $this->createPathObject($absSource),
            'destination' => $this->createPathObject($absDestination),
        ));

        $this->directoryCache[$absSource] = false;
        return $this->directoryCache[$absDestination] = true;
    }
}