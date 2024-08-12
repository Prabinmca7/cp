<?
namespace RightNow\Internal\Utils;

use RightNow\Utils\FileSystem as FileSystemExternal,
    RightNow\Utils\Config as ConfigExternal,
    RightNow\Utils\Text as TextExternal;

require_once CPCORE . 'Internal/Libraries/WebDav/PathHandler.php';
require_once CPCORE . 'Internal/Libraries/CodeAssistant/Suggestion.php';
require_once CPCORE . 'Internal/Libraries/CodeAssistant/Conversion.php';

class CodeAssistantException extends \Exception {
    /**
     * Override the exception class to allow internal only exceptions to be created. Internal exceptions
     * are never displayed to the user and will instead return a generic error.
     * @param string $message The message associated to the exception.
     * @param bool $isInternal Whether or not the message is internal only.
     */
    public function __construct($message, $isInternal = false) {
        if($isInternal && IS_HOSTED) {
            Api::phpoutlog($message);
            $message = ConfigExternal::getMessage(ERROR_REQUEST_PLEASE_TRY_MSG);
        }
        parent::__construct($message);
    }
}

class CodeAssistant {
    private static $operationsFile = 'Operations.yml';
    private static $dryrun = false; //Whether or not changes should be committed

    //Filtering constants
    const ASSETS = 1;
    const CONTROLLERS = 2;
    const HELPERS = 4;
    const JAVASCRIPT = 8;
    const LIBRARIES = 16;
    const MODELS = 32;
    const VIEWS = 64;
    const WIDGETS = 128;
    const ALL_NO_ASSETS = 254;
    const ALL = 255;

    //FileType Constants
    const FILETYPE_JS = 1;
    const FILETYPE_CSS = 2;
    const FILETYPE_PHP = 4;
    const FILETYPE_YML = 8;
    const FILETYPE_ALL = 15;

    /**
     * This function is useful for filtering customer files down to a more manageable list.
     * @param int $chosenTypes One of this classes filtering constants
     * @param int $chosenFileTypes One of this classes file type constants
     * @param string $regex An arbitrary regular expression to apply to every file name
     * @return array The list of filtered files
     */
    public static function getFiles($chosenTypes = self::ALL, $chosenFileTypes = self::FILETYPE_ALL, $regex = null) {
        $fileTypes = array(
            self::FILETYPE_PHP => 'php',
            self::FILETYPE_JS => 'js',
            self::FILETYPE_CSS => 'css',
            self::FILETYPE_YML => 'yml'
        );

        $extensions = array();
        foreach($fileTypes as $fileType => $extension) {
            if($chosenFileTypes & $fileType) {
                $extensions []= $extension;
            }
        }

        if(count($extensions) > 0) {
            $fileTypeExpression = "@\.(" . implode('|', $extensions) . ")$@";
        }

        if($regex && $fileTypeExpression) {
            $filter = function($f) use ($fileTypeExpression, $regex) { return $f->isFile() && preg_match("$regex", $f->getFilename()) && preg_match($fileTypeExpression, $f->getFilename()); };
        }
        else if($regex) {
            $filter = function($f) use ($regex) { return $f->isFile() && preg_match("$regex", $f->getFilename()); };
        }
        else if($fileTypeExpression) {
            $filter = function($f) use ($fileTypeExpression) { return $f->isFile() && preg_match($fileTypeExpression, $f->getFilename()); };
        }
        else {
            $filter = function($f) { return $f->isFile(); };
        }

        $scriptTypes = array(
            self::CONTROLLERS => 'controllers',
            self::HELPERS => 'helpers',
            self::JAVASCRIPT => 'javascript',
            self::LIBRARIES => 'libraries',
            self::MODELS => 'models',
            self::VIEWS => 'views',
            self::WIDGETS => 'widgets'
        );

        $files = array();
        foreach(self::getWritablePaths() as $type => $editablePath) {
            if($type === 'assets' && $chosenTypes & self::ASSETS) {
                $files = array_merge($files, FileSystemExternal::listDirectory($editablePath, true, true, $filter ? array('function', $filter) : null));
                continue;
            }

            if($type === 'scripts') {
                foreach($scriptTypes as $scriptType => $directory) {
                    if($chosenTypes & $scriptType) {
                        $files = array_merge($files, FileSystemExternal::listDirectory($editablePath . $directory, true, true, $filter ? array('function', $filter) : null));
                    }
                }
                continue;
            }
        }
        return $files;
    }

    /**
     * Retrieve the list of valid operations from the `operations.yml` file based on a from and to version number
     * @param string $current The current production version (where the site is currently)
     * @param string $next The development version (where the site is headed next)
     * @return array List of operations
     * @throws CodeAssistantException If no operations are found
     */
    public static function getOperations($current = null, $next = null) {
        $data = self::getOperationsData();

        $versionRange = array();
        foreach(self::getAllFrameworkVersions() as $version) {
            //Include all versions in the range ($current, $next] (exclusive of current and inclusive of next)
            if((!$current || Version::compareVersionNumbers($current, $version) === -1) && (!$next || Version::compareVersionNumbers($next, $version) !== -1)) {
                $versionRange[] = $version;
            }
        }

        $operations = array();
        foreach($data['operations'] as $id => $operation) {
            $validOperation = self::validateOperation($id, $operation);
            if(in_array($validOperation['destinationVersion'], $versionRange) && (!$validOperation['deprecatedVersion'] || !in_array($validOperation['deprecatedVersion'], $versionRange))) {
                $operations[] = $validOperation;
            }
        }

        if(!count($operations)) {
            throw new CodeAssistantException(ConfigExternal::getMessage(OPS_AVAIL_SEL_DEVELOPMENT_VERSION_MSG));
        }
        return $operations;
    }

    /**
     * Retrieve a single operation based on the ID. IDs are dependent upon the ordering of the `operations.yml` file, but only
     * matter between AJAX requests on the same page.
     * @param int $id The index of the operation in the `operations.yml` file
     * @return array A dictionary of operation data
     * @throws CodeAssistantException If no operation could be found for the given ID
     */
    public static function getOperationById($id) {
        $operations = self::getOperations();
        if(!$operation = $operations[$id]) {
            throw new CodeAssistantException(ConfigExternal::getMessage(RETRIEVE_ITEMS_OP_PLS_REFRESH_PG_MSG));
        }
        return $operation;
    }


    /**
     * Include the correct utility file for the given operation and verify that the expected methods are
     * defined on the class.
     * @param array $operation A dictionary of operation data, generated with `getOperations` from the `operations.yml` file.
     * @return array The class that was loaded and the name of the method on that class
     * @throws CodeAssistantException If the values in the operations array are invalid
     */
    private static function getOperationClassName(array $operation) {
        if(!$operation['file']) {
            throw new CodeAssistantException("Every operation must define a `file` key which specifies the name of the operation's PHP file.", true);
        }

        $path = self::getDefaultPath() . $operation['file'];
        if(!FileSystemExternal::isReadableFile($path)) {
            throw new CodeAssistantException("The file '$path' could not be found.", true);
        }
        require_once $path;

        $class = 'RightNow\Internal\Utils\CodeAssistant\\' . substr($operation['file'], 0, strrpos($operation['file'], '.'));
        if(!class_exists($class)) {
            throw new CodeAssistantException("The class '$class' does not exist. The class name of an operation must match the filename", true);
        }

        return $class;
    }

    /**
     * Check if the given method exists on the given class. If the method doesn't exist, throws an exception.
     * @param string $class The class name
     * @param string $method The method name
     * @return boolean True on success
     * @throws CodeAssistantException If the method doesn't exist on the class
     */
    private static function checkMethodExists($class, $method) {
        if(!method_exists($class, $method)) {
            throw new CodeAssistantException("The method '$method' does not exist on class '$class'.", true);
        }
        return true;
    }

    /**
     * Validate an operation dictionary that has been loaded from the `Operations.yml` file. Also, convert
     * any strings using the `rn:astr` or `rn:msg` syntax to their actual values.
     * @param int $id The id of the operation
     * @param array $operation The list of data from the yml file.
     * @return array A list of validated data
     * @throws CodeAssistantException If operation isn't properly formed
     */
    private static function validateOperation($id, array $operation) {
        $requiredKeys = array('title', 'description', 'instructions', 'destinationVersion', 'file');
        foreach($requiredKeys as $key) {
            if(!$operation[$key]) {
                throw new CodeAssistantException("The key `$key` is required. Please include it in the definitions file.", true);
            }
        }

        $versionKeys = array('destinationVersion', 'deprecatedVersion');
        foreach($versionKeys as $version) {
            if(!$operation[$version]) continue;
            if(!Version::isValidVersionNumber($operation[$version])) {
                throw new CodeAssistantException("The version '{$operation[$version]}' is not a valid version.", true);
            }
        }

        if($operation['deprecatedVersion'] && Version::compareVersionNumbers($operation['deprecatedVersion'], $operation['destinationVersion']) !== 1) {
            throw new CodeAssistantException("The deprecated version '{$operation['deprecatedVersion']}' is less than or equal to the destination version '{$operation['destinationVersion']}'. It must be larger than the destination version.", true);
        }

        $stringKeys = array('title', 'description', 'instructions', 'success', 'failure', 'postExecuteMessage');
        foreach($stringKeys as $key) {
            if(!$operation[$key]) continue;
            $parts = explode(':', $operation[$key]);
            if(count($parts) < 3) {
                throw new CodeAssistantException("The key `$key` must be internationalized. Use `rn:astr` or `rn:msg`.", true);
            }
            array_map('strtolower', $parts);

            $firstSegment = array_shift($parts);
            $secondSegment = array_shift($parts);
            $thirdSegment = implode(':', $parts);

            if($firstSegment === 'rn') {
                if($secondSegment === 'msg' && defined($thirdSegment) && $thirdSegment = constant($thirdSegment)) {
                    $operation[$key] = ConfigExternal::getMessage($thirdSegment);
                    continue;
                }
                if($secondSegment === 'astr') {
                    $operation[$key] = ConfigExternal::ASTRgetMessage($thirdSegment);
                    continue;
                }
            }

            throw new CodeAssistantException("The key `$key` has an invalid `rn` type: '$secondSegment'. Only `rn:astr` and `rn:msg` are supported", true);
        }

        $allowedTypes = array('conversion', 'suggestion');
        if(!$operation['type']) $operation['type'] = 'conversion';
        if(!in_array($operation['type'], $allowedTypes)) {
            throw new CodeAssistantException(sprintf("The operation type `{$operation['type']}` is not supported. Allowed types are '%s'.", implode(', ', $allowedTypes)), true);
        }

        $path = self::getDefaultPath() . $operation['file'];
        if(!FileSystemExternal::isReadableFile($path)) {
            throw new CodeAssistantException("Cannot read the following file: '$path'", true);
        }

        $operation['id'] = $id;
        return $operation;
    }

    /**
     * Get all of the available units for a given operation. The units are determined using the `getUnits` methods
     * of the operation class.
     * @param array $operation The dictionary of operation data
     * @return array A list of unit strings
     * @throws CodeAssistantException If units for the specific operation aren't an array
     */
    public static function getUnits(array $operation) {
        static $getUnitsMethod = 'getUnits';
        $className = self::getOperationClassName($operation);
        self::checkMethodExists($className, $getUnitsMethod);
        $units = $className::$getUnitsMethod();

        if(!is_array($units) || !count($units) || count(array_filter($units, 'is_string')) !== count($units)) {
            throw new CodeAssistantException(ConfigExternal::getMessage(ITEMS_AVAIL_OP_PLEASE_SELECT_OP_MSG));
        }

        return $units;
    }

    /**
     * Get all of the instructions for an operation and a set of units. These instructions are determined
     * by calling the `executeUnit` method of the operation class foreach unit.
     * @param array $operation The dictionary of operation data
     * @param array $units An array of string unit names
     * @return array A list of units which were successfully completed and their associated instructions.
     */
    public static function getInstructions(array $operation, array $units) {
        static $executeUnitMethod = 'executeUnit';
        $className = self::getOperationClassName($operation);
        self::checkMethodExists($className, $executeUnitMethod);
        $contextType = '\RightNow\Internal\Libraries\CodeAssistant\\' . ucfirst($operation['type']);

        $performedUnits = array();
        foreach($units as $unit) {
            $context = new $contextType();

            try {
                $className::$executeUnitMethod($unit, $context);
            }
            catch(\Exception $e) {
                $context->addError($e->getMessage());
            }

            $performedUnits[$unit] = array(
                'messages' => $context->getMessages(),
                'instructions' => $context->getInstructions(),
                'errors' => $context->getErrors()
            );
        }

        return $performedUnits;
    }

    /**
     * Return the default path containing the `operations.yml` file and the operation libraries.
     * @return string The path
     */
    private static function getDefaultPath() {
        return CPCORE . 'Internal/Utils/CodeAssistant/';
    }

    /**
     * Get the Operations data from the `Operations.yml` file
     * @return array The data
     * @throws CodeAssistantException If yaml file couldn't be parsed
     */
    private static function getOperationsData() {
        if(!$data = yaml_parse_file(self::getDefaultPath() . self::$operationsFile)) {
            throw new CodeAssistantException("Unable to parse manifest file. Please try again later.", true);
        }
        return $data;
    }

    /**
     * Return the default backup directory location accessible through WebDAV.
     * @return string The path
     */
    public static function getBackupPath() {
        return OPTIMIZED_FILES . 'temp_backups/';
    }

    /**
     * Return the default temporary file location
     * @return string The path
     */
    private static function getTemporaryPath() {
        return get_cfg_var('upload_tmp_dir') . '/';
    }

    /**
     * Determine if the given path is writable by the Code Assistant tool
     * @param string $path The path
     * @return boolean True or false whether or not the path is writable
     */
    public static function isWritablePath($path) {
        return self::pathHasPermission($path, 'writable');
    }

    /**
     * Determine if the given path is readable by the Code Assistant tool
     * @param string $path The path
     * @return boolean True or false whether or not the path is readable
     */
    public static function isReadablePath($path) {
        return self::pathHasPermission($path, 'readable');
    }

    /**
     * Determine if the given path has a permission
     * @param string $path The path
     * @param string $permission Either `readable` or `writable`
     * @return boolean True or false if the path has the permission
     */
    private static function pathHasPermission($path, $permission) {
        $paths = ($permission === 'readable') ? self::getReadablePaths() : self::getWritablePaths();
        foreach($paths as $availablePath) {
            if(TextExternal::beginsWith($path, rtrim($availablePath, '/'))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the path "key" for the given path used for the `getWritablePaths` function
     * @param string $path The path
     * @return string|null The key that matches the given path or null if none are found
     */
    public static function getWritablePathKey($path) {
        return self::getPathKey($path, 'writable');
    }

    /**
     * Get the path "key" for the given path used for the `getReadablePaths` function
     * @param string $path The path
     * @return string|null The key that matches the given path or null if none are found
     */
    public static function getReadablePathKey($path) {
        return self::getPathKey($path, 'readable');
    }

    /**
     * Returns the path key for the given type if one exists, otherwise returns null
     * @param string $path The path
     * @param string $type The type of path key either `readable` or `writable`
     * @return null|string Null if no path was found, key otherwise
     */
    private static function getPathKey($path, $type) {
        $paths = ($type === 'readable') ? self::getReadablePaths() : self::getWritablePaths();
        foreach($paths as $key => $operablePath) {
            if(TextExternal::beginsWith($path, $operablePath)) {
                return $key;
            }
        }
    }

    /**
     * Returns the list of paths which can be edited by the Code Assistant tool. Any file under these directories
     * can be edited by the tool and will be backed up during the operation processing phase.
     * Note: Writable paths should be a subset of readable paths below
     * @param string|null $key Specific key to retrieve
     * @return array|string All editable paths or a specific path if key is provided
     */
    public static function getWritablePaths($key = null) {
        $paths = array(
            'scripts' => CUSTOMER_FILES,
            'assets' => HTMLROOT . '/euf/assets/'
        );
        return ($key) ? $paths[$key] : $paths;
    }

    /**
     * Returns a list of all paths that are reachable by the Code Assistant tool. Any file under these directories
     * can be read from with the tool and could potentially feed into other parts of the tool.
     * @param null|string $key Specific key to retrieve
     * @return array|string All accessible paths or a specific path if key is provided
     */
    public static function getReadablePaths($key = null) {
        $paths = array(
            'cp' => DOCROOT . '/cp/',
            'euf' => DOCROOT . '/euf/',
            'assets' => HTMLROOT . '/euf/'
        );
        return ($key) ? $paths[$key] : $paths;
    }

    /**
     * The list of WebDAV paths for which links are displayed
     * @return array List of paths
     */
    public static function getDAVPathSegments() {
        return array(
            'cp/customer/development/',
            'cp/customer/assets/'
        );
    }

    /**
     * Get the WebDAV path for the given backup path location. Split the resultant path into components
     * `visiblePath` and `davPath` which are used to build up the URL and display to the user
     * @param string $path The path
     * @param string $backupDirectory The directory containing the backup
     * @return array An object describing the WebDAV path
     */
    private static function getWebDAVBackupPath($path, $backupDirectory) {
        if(!$path) return false;

        $head = TextExternal::getSubstringAfter(self::getBackupPath(), 'cp/');
        $keyedPath = TextExternal::getSubstringAfter($path, $backupDirectory);
        foreach(array_keys(self::getWritablePaths()) as $pathKey) {
            if(TextExternal::beginsWith($keyedPath, $pathKey)) {
                $tail = TextExternal::getSubstringAfter($keyedPath, $pathKey);
                break;
            }
        }

        return array(
            'isDAV' => true,
            'visiblePath' => $head . '...' . $tail,
            'davPath' => self::getWebDAVPath($path)
        );
    }

    /**
     * Get the WebDAV path for the given PathObject.
     * @param array|null $pathObject An object containing the path data generated for an operation
     * @return array An object describing the WebDAV path
     */
    private static function getWebDAVPathObject($pathObject) {
        if(!$pathObject) return false;

        if($absolutePath = self::getWebDAVPath(self::getAbsolutePathAndCheckPermissions($pathObject))) {
            foreach(self::getDAVPathSegments() as $davPath) {
                if(TextExternal::beginsWith($absolutePath, $davPath)) {
                    return array(
                        'isDAV' => true,
                        'visiblePath' => TextExternal::getSubstringAfter($absolutePath, $davPath),
                        'davPath' => $absolutePath
                    );
                }
            }
        }

        return array(
            'isDAV' => false,
            'visiblePath' => $pathObject['visiblePath']
        );
    }

    /**
     * Given an absolute path on the file system get the equivalent path that can be used through the `/dav` endpoint.
     * @param string $absolutePath The file system path
     * @return string The WebDAV path
     */
    private static function getWebDAVPath($absolutePath) {
        try {
            $handler = new \RightNow\Internal\Libraries\WebDav\PathHandler($absolutePath, true);
        }
        catch(\Exception $e) {
            return false;
        }

        if(!$handler->isVisiblePath() || !$handler->fileExists()) return false;
        return $handler->getDavPath();
    }

    /**
     * Given a WebDavPathObject transform it into a usable link
     * @param array $pathObject The path object
     * @return string The link
     */
    private static function getWebDAVLink(array $pathObject) {
        return '<a target="_blank" href="/dav/' . $pathObject['davPath'] . '">' . $pathObject['visiblePath'] . '</a>';
    }

    /**
     * For the given array of units and associated instructions, perform all of the instructions.
     * @param array $operation The operation metadata object
     * @param array $units A dictionary keyed by units with associated instructions. Generated above with `getInstructions`
     * @return array A list of results to be displayed to the user including all moved files and backup paths
     */
    public static function processInstructions(array $operation, array $units) {
        //Create a backup directory. Uses periods to separate hours, minutes and seconds since Windows WebDAV
        //acts bonkers when we use colons.
        $backupDirectory = self::getBackupPath() . 'ca' . strftime("%m-%d-%Y %H.%M.%S") . '/';

        $successfulUnits = $failedUnits = array();
        foreach($units as $unit => $unitInformation) {
            //If this unit encountered an error while retrieving the instructions, mark it as failed.
            if(count($unitInformation['errors'])) {
                $failedUnits[$unit] = $unitInformation;
                continue;
            }

            try {
                //Process all of the instructions before getting the WebDAV paths since a delete instruction late
                //in the process could alter what's visible through DAV
                $backupPaths = array();
                foreach($unitInformation['instructions'] as $instruction) {
                    $backupPaths[] = self::processInstruction($instruction, $backupDirectory);
                }

                //Now retrieve the DAV paths
                $processedInstructions = array();
                foreach($unitInformation['instructions'] as $index => $instruction) {
                    $processedInstructions[] = array(
                        'type' => $instruction['type'],
                        'source' => self::getWebDAVPathObject($instruction['source']),
                        'destination' => self::getWebDAVPathObject($instruction['destination']),
                        'backup' => self::getWebDAVBackupPath($backupPaths[$index], $backupDirectory)
                    );
                }

                $successfulUnits[$unit] = array(
                    'instructions' => $processedInstructions,
                    'messages' => $unitInformation['messages']
                );
            }
            catch(\Exception $e) {
                //If an error is encountered, we need to work back through the already processed instructions
                //and let the user know that they'll need to revert any changes which have been made.
                $errors = array();
                foreach($unitInformation['instructions'] as $index => $instruction) {
                    if(!$backupPaths[$index]) continue;
                    $linkText = self::getWebDAVLink(self::getWebDAVBackupPath($backupPaths[$index], $backupDirectory));
                    $errors[] = sprintf(ConfigExternal::getMessage(BACKUP_PCT_S_CR_CONV_ITEM_COMP_MSG), $linkText);
                }

                $errors[] = $e->getMessage();
                $failedUnits[$unit] = array(
                    'errors' => $errors
                );
            }
        }

        return array(
            'successfulUnits' => $successfulUnits,
            'failedUnits' => $failedUnits,
            'postExecuteMessage' => self::runPostExecute($operation, $successfulUnits)
        );
    }

    /**
     * Run the postExecute method of the operation if one exists. This stage is intended to run after all of the units
     * have been converted.
     * @param array $operation The array of operation metadata
     * @param array $units The list of units that were successfully converted
     * @return string|null Error message or null on success
     */
    private static function runPostExecute(array $operation, array $units) {
        static $postExecuteMethod = 'postExecute';

        $units = array_keys($units);
        $className = self::getOperationClassName($operation);
        if(method_exists($className, $postExecuteMethod) && count($units)) {
            try {
                if(!self::$dryrun) $className::$postExecuteMethod($units);
                return $operation['postExecuteMessage'] ?: ConfigExternal::getMessage(POST_EXECUTE_STEP_WAS_PERFORMED_MSG);
            }
            catch(\Exception $e) {
                return $e->getMessage();
            }
        }
    }

    /**
     * Process the given instruction. This typically involves modifying the file system in some way (defined within the instruction).
     * any instructions which remove already existing files on the file system will have a backup created.
     * @param array $instruction The instruction to be processed
     * @param string $backupDirectory The path to store backups
     * @return string The path to the backup file if applicable
     * @throws CodeAssistantException If instructions cannot be performed
     */
    private static function processInstruction(array $instruction, $backupDirectory) {
        if(self::$dryrun) return false; //Pretend that everything went fine, but no backups were created

        $source = self::getAbsolutePathAndCheckPermissions($instruction['source'], true);

        if(!IS_HOSTED) {
            $oldMask = umask(0);
        }

        switch($instruction['type']) {
            case 'createDirectory':
                try {
                    FileSystemExternal::mkdirOrThrowExceptionOnFailure($source);
                }
                catch(\Exception $e) {
                    throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_CREATE_DIRECTORY_PCT_S_MSG), $instruction['source']['visiblePath']));
                }
                break;
            case 'createFile':
                $tempSource = self::getTemporaryPath() . $instruction['tempSource'];
                try {
                    FileSystemExternal::copyFileOrThrowExceptionOnFailure($tempSource, $source, false);
                }
                catch(\Exception $e) {
                    throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_TO_CREATE_FILE_AT_PCT_S_MSG), $instruction['source']['visiblePath']));
                }
                break;
            case 'deleteFile':
                $backup = self::backupFile($source, $backupDirectory);
                if(!FileSystemExternal::isReadableFile($source) || !@unlink($source)) {
                    throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_TO_DELETE_FILE_AT_PCT_S_MSG), $instruction['source']['visiblePath']));
                }
                break;
            case 'modifyFile':
                $backup = self::backupFile($source, $backupDirectory);
                $tempSource = self::getTemporaryPath() . $instruction['tempSource'];
                //The file being modified doesn't exist. It should, otherwise this isn't truly a modify operation.
                if(!FileSystemExternal::isReadableFile($source)) {
                    throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_TO_MODIFY_FILE_AT_PCT_S_MSG), $instruction['source']['visiblePath']));
                }
                try {
                    FileSystemExternal::copyFileOrThrowExceptionOnFailure($tempSource, $source);
                }
                catch(\Exception $e) {
                    throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_TO_MODIFY_FILE_AT_PCT_S_MSG), $instruction['source']['visiblePath']));
                }
                break;
            case 'moveFile':
                $backup = self::backupFile($source, $backupDirectory);
                $tempSource = self::getTemporaryPath() . $instruction['tempSource'];
                if(!FileSystemExternal::isReadableFile($source) || !unlink($source)) {
                    throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_TO_DELETE_FILE_AT_PCT_S_MSG), $instruction['source']['visiblePath']));
                }
                $destination = self::getAbsolutePathAndCheckPermissions($instruction['destination'], true);
                try {
                    FileSystemExternal::copyFileOrThrowExceptionOnFailure($tempSource, $destination, false);
                }
                catch(\Exception $e) {
                    throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_TO_CREATE_FILE_AT_PCT_S_MSG), $instruction['destination']['visiblePath']));
                }
                break;
            case 'moveDirectory':
                $backup = self::backupDirectory($source, $backupDirectory);
                try {
                    FileSystemExternal::moveDirectory("$source/", self::getAbsolutePathAndCheckPermissions($instruction['destination'], true) . '/');
                    FileSystemExternal::removeDirectory($source, true);
                }
                catch(\Exception $e) {
                    throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(MOVE_DIRECTORY_PCT_S_PCT_S_MSG), $instruction['source']['visiblePath'], $instruction['destination']['visiblePath']));
                }
                break;
            default:
                throw new CodeAssistantException("Unrecognized instruction type '{$instruction['type']}'", true);
        }

        if(!IS_HOSTED) {
            umask($oldMask);
        }

        return $backup ?: false;
    }

    /**
     * Given an instruction's path object, create the absolute path. Always check the path
     * to ensure that it's accessible by the Code Assistant. Optionally check to see if it's writable.
     * @param array $pathObject Array of paths to check
     * @param bool $checkWritable Whether to check if path is writable
     * @return string The absolute path
     * @throws CodeAssistantException If paths provided in pathObject are invalid
     */
    private static function getAbsolutePathAndCheckPermissions(array $pathObject, $checkWritable = false) {
        $readablePaths = self::getReadablePaths();
        $absolutePath = $readablePaths[$pathObject['key']];
        $path = $absolutePath . $pathObject['hiddenPath'] . $pathObject['visiblePath'];

        if(!$absolutePath) {
            throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(PATH_PCT_S_ACC_CODE_ASSISTANT_MSG), $pathObject['visiblePath']));
        }
        if($checkWritable && !self::isWritablePath($path)) {
            throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(PATH_PCT_S_WRITABLE_CODE_ASSISTANT_MSG), $pathObject['visiblePath']));
        }

        return $path;
    }

    /**
     * Make a copy of the given file in the given backup directory
     * @param string $absolutePath The path to the file being backed up
     * @param string $backupDirectory The directory to store the backup
     * @return string The absolute path to the backup file
     * @throws CodeAssistantException If paths provided are invalid
     */
    private static function backupFile($absolutePath, $backupDirectory) {
        if(!$writableKey = self::getWritablePathKey($absolutePath)) {
            throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(PATH_PCT_S_WRITABLE_CODE_ASST_MSG), $absolutePath));
        }
        $writablePath = self::getWritablePaths($writableKey);
        $backupFile = $backupDirectory . $writableKey . '/' . TextExternal::getSubstringAfter($absolutePath, $writablePath);

        $backupParent = dirname($backupFile);
        try {
            FileSystemExternal::mkdirOrThrowExceptionOnFailure($backupParent, true);
        }
        catch(\Exception $e) {
            throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(CREATE_BACKUP_DIRECTORY_PCT_S_MSG), $backupParent));
        }

        try {
            FileSystemExternal::copyFileOrThrowExceptionOnFailure($absolutePath, $backupFile, false);
        }
        catch(\Exception $e) {
            throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_CREATE_BACKUP_PCT_S_MSG), $backupFile));
        }
        return $backupFile;
    }

    /**
     * Make a recursive copy of the given directory in the given backup directory
     * @param string $absolutePath The path to the directory being backed up
     * @param string $backupDirectory The directory to store the backup
     * @return string The absolute path to the backup
     * @throws CodeAssistantException If paths provided are invalid
     */
    private static function backupDirectory($absolutePath, $backupDirectory) {
        if(!$writableKey = self::getWritablePathKey($absolutePath)) {
            throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(PATH_PCT_S_WRITABLE_CODE_ASST_MSG), $absolutePath));
        }
        $backupPath = $backupDirectory . $writableKey . '/' . TextExternal::getSubstringAfter($absolutePath, self::getWritablePaths($writableKey));

        try {
            FileSystemExternal::copyDirectory($absolutePath, $backupPath);
        }
        catch(\Exception $e) {
            throw new CodeAssistantException(sprintf(ConfigExternal::getMessage(UNABLE_CREATE_BACKUP_PCT_S_MSG), $backupPath));
        }
        return $backupPath;
    }

    /**
     * Return a list of all framework versions of CP.
     * @return array Sorted list of framework versions
     */
    public static function getAllFrameworkVersions() {
        $allVersions = array('2.0');
        $history = Version::getVersionHistory();
        uasort($history['frameworkVersions'], "\RightNow\Internal\Utils\Version::compareVersionNumbers");
        foreach($history['frameworkVersions'] as $cxRelease => $frameworkVersion) {
            $allVersions[] = substr($frameworkVersion, 0, strrpos($frameworkVersion, '.'));
        }
        return array_values(array_unique($allVersions));
    }
}
