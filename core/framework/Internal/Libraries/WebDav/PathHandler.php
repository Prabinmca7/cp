<?php
namespace RightNow\Internal\Libraries\WebDav;

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Config,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Utils\Framework;

require_once CPCORE . 'Internal/Utils/Logs.php';

/**
 * A class which wraps a WebDAV path and provides useful functionality
 */
class PathHandler {

    /**
     * Indicates if its a root directory
     */
    protected $isRoot = false;

    /**
     * The DAV path including the CP segment (e.g. `cp/core/framework/Models/Field.php`)
     */
    private $davPath;

    /**
     * The absolute file system path that begins an associated DAV Path
     */
    private $basePath;

    /**
     * The segment after the basePath leading to the file on disk. The combination of basePath and relativePath forms a complete path.
     */
    private $relativePath;

    /**
     * The complete path to the file on disk. This is modified slightly to handle versions.
     */
    private $fileSystemPath;

    /**
     * The full path to the default core scripts
     */
    private $corePath;

    /**
     * Whether or not the DAV path points to an artificial directory (e.g. a directory that doesn't exist on disk)
     */
    private $isArtificial = false;

    /**
     * Whether or not the class is being run under test. Tests versioning.
     */
    private $isTesting = false;

    /**
     * Regular expressions used to match paths which should be hidden from WebDAV
     */
    private static $hiddenDavPaths = array(
        //Directories
        "@^cp/core/compatibility(?:/|$)@",
        "@^cp/core/util(?:/|$)@",
        "@^cp/core/framework/CodeIgniter(*:/|$)@",
        "@^cp/core/framework/Controllers/Admin(?:/|$)@",
        "@^cp/core/framework/Internal(?:/|$)@",
        "@^cp/core/framework/Views/Admin(?:/|$)@",
        "@^cp/core/framework/Hooks(?:/|$)@",
        "@^cp/core/widgets/[^/]+/[^/]+/[^/]+/optimized@",
        "@^cp/generated/production/optimized(?:/|$)@",
        "@^cp/generated/staging/optimized(?:/|$)@",
        "@^cp/generated/production/assets/pages(?:/|$)@",
        "@^cp/generated/production/assets/templates(?:/|$)@",

        "@^cp/core/assets/css(?:/|$)@",
        "@^cp/core/assets/admin(?:/|$)@",
        "@^cp/core/assets/js(?:/|$)@",

        //Specific Files
        "@^cp/core/compatibility/ActionCapture[.]php$@",
        "@^cp/core/compatibility/optimized_includes[.]php$@",
        "@^cp/core/cpHistory$@",
        "@^cp/core/framework/Controllers/AnswerPreview[.]php$@",
        "@^cp/core/framework/Controllers/Dqa[.]php$@",
        "@^cp/core/framework/Controllers/InlineImage[.]php$@",
        "@^cp/core/compatibility/Controllers/InlineImg[.]php$@",
        "@^cp/core/framework/Models/Pageset[.]php$@",
        "@^cp/core/framework/environment@",
        "@^cp/core/framework/init[.]php@",
        "@^cp/core/framework/optimized_includes[.]php$@",
        "@^cp/generated/production/deployTimestamp$@",
        "@^cp/generated/staging/deployTimestamp$@",
        "@^cp/logs/dqa$@",
        "@^cp/customer/development/cacheControl.json@",
        "@^cp/customer/development/cpConfig.json@",

        // General files
        "@widgetVersions$@",
        "@frameworkVersion$@",
        "@phpVersion$@",
        "@versionAuditLog$@",
        "@cp/generated/.+?/pageSetMapping[.]php$@",
        "@/[.]cvsignore$@",
        "@^cp/core/.+?/changelog[.]yml$@",
        "@/[.]#[^/]+(?:[.][0-9]+){2,}$@", // cvs hidden files
    );

    /**
     * Create a path handler.
     * @param string $davPath The absolute DAV path or an absolute file system path is the second parameter is true
     * @param string $isFileSystemPath A convenience flag to create a DAV handler from a file system path
     * @throws \Exception when the given path does not have a valid base path in WebDAV.
     */
    public function __construct($davPath, $isFileSystemPath = false) {
        if($isFileSystemPath && !($davPath = $this->transformToDavPath($davPath))) {
            throw new \Exception(Config::getMessage(PATH_VALID_WEBDAV_PATH_MSG));
        }

        //Map empty paths to the root directory
        $davPath = Text::endsWith($davPath, '/') ? substr($davPath, 0, -1) : $davPath;
        if($davPath === '' || $davPath === '.') {
            $this->isRoot = true;
            $davPath = '/';
        }

        $this->davPath = FileSystem::normalizePath($davPath);

        //Decide if the path is artificial, if it is, other parts of the path won't be valid so exit early
        foreach(array_keys(self::getArtificialPaths()) as $artificialPath) {
            if($this->davPath === $artificialPath) {
                $this->isArtificial = true;
                return;
            }
        }

        //Find the appropriate base path for the given DAV path. Choose the most specific one.
        $lastInsertedPath = $this->relativePath = '';
        foreach(self::getInsertedPaths() as $insertedPath => $basePath) {
            if(Text::beginsWith($this->davPath, $insertedPath) && strlen($lastInsertedPath) < strlen($insertedPath)) {
                $lastInsertedPath = $insertedPath;
                $this->basePath = $basePath;
                $this->relativePath = Text::getSubstringAfter($this->davPath, $insertedPath);
            }
        }

        if(!$this->basePath) {
            throw new \Exception(Config::getMessage(PATH_VALID_WEBDAV_PATH_MSG));
        }
    }

    /**
     * Given an absolute file system path return a fully qualified DAV path including the `cp` segment.
     * @param string $absolutePath The absolute file system path
     * @return string|boolean The WebDAV path or false if the path doesn't exist
     */
    public static function transformToDavPath($absolutePath) {
        $davPath = $chosenInsertedPath = '';
        foreach(self::getInsertedPaths() as $insertedPath => $basePath) {
            if(Text::beginsWith($absolutePath, $basePath) && strlen($chosenInsertedPath) < strlen($insertedPath)) {
                $davPath = $insertedPath . Text::getSubstringAfter($absolutePath, $basePath);
            }
        }

        if(!$davPath) return false;

        //Check if the path is a standard widget / framework path
        $matches = array();
        foreach(array(self::getCorePath() . '/widgets/standard', self::getCorePath() . '/framework', HTMLROOT . '/euf/core') as $versionPath) {
            if(Text::beginsWith($absolutePath, $versionPath) && preg_match('@[/](\d{1}\.\d{1}(?:\.\d{1})?)@', $davPath, $matches)) {
                return Text::getSubstringBefore($davPath, $matches[0]) . Text::getSubstringAfter($davPath, $matches[0]);
            }
        }

        return $davPath;
    }

    /**
     * If the given DAV path is a directory, returns that directory contents.
     * @return Array The array of next level PathHandlers
     */
    public function getDirectoryContents() {
        $directoryContents = $this->getChildren();
        //Sort the contents in alphabetical order with directories on top
        usort($directoryContents, function($a, $b) {
            return strcmp("{$a[1]}{$a[0]}", "{$b[1]}{$b[0]}");
        });

        //Get the visible handlers
        $results = $encounteredPaths = array();
        foreach($directoryContents as $pair) {
            $path = ($this->isRoot) ? $pair[0] : ($this->davPath . '/' . $pair[0]);
            if((isset($encounteredPaths[$path]) && $encounteredPaths[$path]) || !($handler = self::getVisibleHandler($path))) continue;

            $encounteredPaths[$path] = true;
            $results[] = $handler;
        }

        return $results;
    }

    /**
     * Given a DAV path get a valid PathHandler if one exists.
     * @param string $davPath Path to file in WebDAV
     * @return PathHandler A valid visible PathHandler
     */
    public static function getVisibleHandler($davPath) {
        try {
            $handler = new PathHandler($davPath);
        }
        catch(\Exception $e) {
            $handler = null;
        }

        if(!$handler || !$handler->fileExists() || !$handler->isVisiblePath()) {
            return false;
        }

        return $handler;
    }

    /**
     * Get a file system path for this object's DAV path.
     * @return boolean|string The file system path or false if the DAV path is artificial and doesn't have a file system path
     */
    public function getFileSystemPath() {
        if($this->isArtificial) {
            return false;
        }

        $fileSystemPath = "{$this->basePath}{$this->relativePath}";

        //Add version directories
        if(($versionedPath = $this->insertWidgetVersion($fileSystemPath)) || ($versionedPath = $this->insertFrameworkVersion($fileSystemPath))) {
            return $versionedPath;
        }

        return $fileSystemPath;
    }

    /**
     * Get the fully qualified URL for this DAV path
     * @return string The URL
     */
    public function getDavUrl() {
        return '/dav/' . (!$this->isRoot ? $this->davPath : '');
    }

    /**
     * Get the file or folder name for this DAV path
     * @return string The name
     */
    public function getFileOrFolderName() {
        $davSegments = explode('/', $this->davPath);
        return isset($this->isRoot) && $this->isRoot ? '/' : end($davSegments);
    }

    /**
     * Get the DAV path used when constructing this object
     * @return string The DAV path
     */
    public function getDavPath() {
        return $this->davPath;
    }

    /**
     * Get an array of segments forming the DAV path
     * @return array The DAV segments
     */
    public function getDavSegments() {
        return $this->isRoot ? array() : explode('/', $this->davPath);
    }

    /**
     * If the path doesn't have a representation on disk, it is an artificial path.
     * @return boolean Whether or not the path is artificial
     */
    public function isArtificialPath() {
        return $this->isArtificial;
    }

    /**
     * The file system base path for the DAV path
     * @return string The base path
     */
    public function getBasePath() {
        return $this->isArtificial ? '' : $this->basePath;
    }

    /**
     * Whether or not the given DAV path is a directory
     * @return boolean true or false
     */
    public function isDirectory() {
        return $this->isArtificial ?: FileSystem::isReadableDirectory($this->getFileSystemPath());
    }

    /**
     * The size of the given DAV path
     * @return string The file size. Defaults to 4096 for artificial paths.
     */
    public function getSize() {
        return ($this->isArtificial) ? '4096' : number_format(@filesize($this->getFileSystemPath()));
    }

    /**
     * The creation time for the DAV path
     * @return integer The creation time. Defaults to current time for artificial paths.
     */
    public function getCreationTime() {
        return ($this->isArtificial) ? time() : @filectime($this->getFileSystemPath());
    }

    /**
     * The modification time for DAV path
     * @param boolean $formatted Whether or not the return time should be formatted
     * @return integer|string The modification time. Defaults to current time for artificial paths.
     */
    public function getModifiedTime($formatted = true) {
        $result = ($this->isArtificial) ? time() : @filemtime($this->getFileSystemPath());
        return $formatted ? get_instance()->cpwrapper->cpPhpWrapper->strftime('Y-m-d H:i:s', $result) : $result;
    }

    /**
     * Whether or not the given DAV path exists on disk.
     * @return boolean true or false
     */
    public function fileExists() {
        if($this->isArtificial) {
            return true;
        }
        return FileSystem::isReadableDirectory($this->getFileSystemPath()) || FileSystem::isReadableFile($this->getFileSystemPath());
    }

    /**
     * Whether or not the DAV path should be displayed.
     * @return boolean true or false
     */
    public function isVisiblePath() {
        return $this->isArtificial || !($this->isHiddenLogFile() || $this->isServicePackBackupFile() || $this->isHiddenPath());
    }

    /**
     * Check the static list of expressions to determine if the DAV path is visible.
     * @return boolean true or false
     */
    private function isHiddenPath() {
        foreach(self::$hiddenDavPaths as $pattern) {
            if(preg_match($pattern, $this->davPath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the current core path. In hosted, point to the un-munged files.
     * @return string The path
     */
    private static function getCorePath() {
        return IS_HOSTED ? DOCROOT . '/cp/src/core' : DOCROOT . '/cp/core';
    }

    /**
     * This function returns the list of paths which do not exist anywhere on disk and are being emulated by
     * the WebDAV wrapper layer. They use artificial file statistics and are represented by a list of children.
     * Since these paths are artificial, all of the listed children must exist either in this array, with another
     * list of artificial children, or in the `getInsertedPaths` array below, mapping to an actual path on disk.
     * @return array A list of artificial paths
     */
    private static function getArtificialPaths() {
        static $artificialPaths = array(
            '/' => array(
                'cp',
            ),
            'cp' => array(
                'core',
                'customer',
                'generated',
                'logs'
            )
        );

        return $artificialPaths;
    }

    /**
     * Inserted paths are paths which actually exist on disk, but are being relocated to improve the organization
     * of the WebDAV directory structure. This list maps a path on WebDAV to a path on disk. The WebDAV paths
     * are based upon the array defined in `getArtificialPaths`.
     * @return array A list of inserted paths backed by real directories
     */
    private static function getInsertedPaths() {
        static $insertedPaths;
        $productionTimestamp = FileSystem::getLastDeployTimestampFromFile() ?: FileSystem::getLastDeployTimestampFromDir();
        $cp = DOCROOT . '/cp';
        $stagingTimestamp = FileSystem::getLastDeployTimestampFromDir(HTMLROOT . "/euf/generated/staging/staging_01/optimized");
        $insertedPaths = $insertedPaths ?: array(
            'cp/core' => self::getCorePath(),
            'cp/customer' => "$cp/customer",
            'cp/generated' => "$cp/generated",
            'cp/logs' => \RightNow\Api::cfg_path() . '/log',
            'cp/core/assets' => HTMLROOT . '/euf/core',
            'cp/customer/error' => "$cp/customer/development/errors",
            'cp/customer/assets' => HTMLROOT . '/euf/assets',
            'cp/generated/staging/assets' => HTMLROOT . '/euf/generated/staging/staging_01/optimized/' . $stagingTimestamp,
            'cp/generated/staging' => "$cp/generated/staging/staging_01",
            'cp/generated/production/assets' => HTMLROOT . '/euf/generated/optimized/' . $productionTimestamp
        );

        return $insertedPaths;
    }

    /**
     * Given an invalid absolute file system path, this function attempts to transform it into a valid versioned widget path.
     * @param string $fileSystemPath The absolute path
     * @param string $testVersion An optional version to use in place of the widget version (used for testing)
     * @return string|boolean The transformed path or false, if the given path is not a widget path
     */
    private function insertWidgetVersion($fileSystemPath, $testVersion = null) {
        $widgetDirectory = self::getCorePath() . '/widgets/standard/';
        $pathSegments = explode('/', Text::getSubstringAfter($fileSystemPath, $widgetDirectory));

        $widgetSegments = array();
        foreach($pathSegments as $index => $segment) {
            $widgetSegments[] = $segment;
            $widgetKey = implode('/', $widgetSegments);
            if(($widget = Registry::getWidgetPathInfo('standard/' . $widgetKey)) && ($version = $widget->version ? $widget->version : $testVersion)) {
                if($remainingSegments = implode('/', array_slice($pathSegments, $index + 1)))    {
                    $remainingSegments = '/' . $remainingSegments;
                }
                return $widgetDirectory . $widgetKey . '/' . $version . $remainingSegments;
            }
        }
        return false; //The path is not a valid widget path.
    }

    /**
     * Given an invalid absolute file system path, this function attempts to transform it into a valid versioned framework path.
     * @param string $fileSystemPath The absolute path
     * @return string|boolean The transformed path or false, if the given path is not a framework path
     */
    private function insertFrameworkVersion($fileSystemPath) {
        $paths = array(
            'framework' => array(
                'path' => self::getCorePath() . '/framework',
                'version' => Framework::getFrameworkVersion(),
            ),
            'core' => array(
                'path' => HTMLROOT . '/euf/core',
                'version' => CP_FRAMEWORK_VERSION,
            ),
        );

        foreach($paths as $name => $info) {
            $directory = $info['path'];
            if((IS_HOSTED || $this->isTesting) && Text::beginsWith($fileSystemPath, $directory)) {
                return "{$directory}/{$info['version']}" . Text::getSubstringAfter($fileSystemPath, $directory);
            }
        }

        return false;
    }

    /**
     * Get all of the children of this DAV Path
     * @return array A list of the children formatted as pairs of (filename, type) where type is `dir` or `file`
     */
    private function getChildren() {
        if(!$this->isDirectory()) {
            return array();
        }

        $artificialPaths = self::getArtificialPaths();

        $directoryContents = (!$this->isArtificial)
            ? FileSystem::listDirectory($this->getFileSystemPath(), false, false, null, array('getType'))
            : array_map(function($path) { return array($path, 'dir'); }, $artificialPaths[$this->davPath]);

        //Add in any files or folders which have been manually inserted into the file tree
        foreach(array_keys(self::getInsertedPaths()) as $insertedPath) {
            if(($insertedChildren = Text::getSubstringAfter($insertedPath, $this->davPath)) && $insertedChildren[0] === '/') {
                //If the path is directly below the directory we are listing, add the inserted path
                $childSegments = explode('/', substr($insertedChildren, 1));
                if(count($childSegments) === 1) {
                    $directoryContents[] = array($childSegments[0], 'dir');
                }
            }
        }

        return $directoryContents;
    }

    /**
     * Whether or not the given DAV path log should be hidden
     * @return boolean True or false
     */
    private function isHiddenLogFile() {
        $baseName = $this->getFileOrFolderName();
        return !$this->isDirectory() && Text::endsWith($this->getBasePath(), 'log') && $baseName !== 'webdav.log' && !\RightNow\Internal\Utils\Logs::isDebugLog($baseName) && !\RightNow\Internal\Utils\Logs::isDeployLog($baseName);
    }

    /**
     * Whether or not the given DAV path is a service pack backup file
     * @return boolean True or false
     */
    private function isServicePackBackupFile() {
        return preg_match('@-\d+-\d+-\d{8}(-[A-Z]+)?$@', $this->getFileOrFolderName());
    }
}
