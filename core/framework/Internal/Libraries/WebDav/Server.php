<?php

namespace RightNow\Internal\Libraries\WebDav;

use RightNow\Utils\FileSystem,
    RightNow\Utils\Text,
    RightNow\Internal\Libraries\WebDav\ServerBase,
    RightNow\Internal\Libraries\WebDav\PathHandler;

require_once __DIR__ . '/ServerBase.php';
require_once(CPCORE . 'Internal/Libraries/WebDav/PathHandler.php');

/**
 * Filesystem access using WebDAV
 *
 * @access  public
 * @author  Hartmut Holzgraefe <hartmut@php.net>
 * @version @package-version@
 */
final class Server extends ServerBase
{
    const TYPE_FILE_UPLOAD = 0;
    const TYPE_FILE_COPY = 1;
    const TYPE_FILE_MOVE = 2;
    const TYPE_FILE_DELETE = 3;
    const TYPE_FOLDER_UPLOAD = 4;
    const TYPE_FILE_EDIT = 5;

    //The account that logging will be recorded against
    private $account;

    //The absolute path the log file
    private $logPath;

    private $mimeTypes = array(
        'html' => 'text/html',
        'php' =>  'text/plain',
        'yml' =>  'text/plain',
        'js' => 'text/javascript',
        'css' => 'text/css',
        'scss' => 'text/css',
        'gif' => 'image/gif',
        'png' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'tiff' => 'image/tiff'
    );

    /**
     * Construct a new WebDAV server
     * @param array $account The account associated with logging
     * @param string $logPath The path to log request to. Used for testing.
     */
    function __construct($account, $logPath = null) {
        $this->account = $account;
        $this->logPath = $logPath ?: \RightNow\Api::cfg_path() . "/log/webdav.log";
        parent::__construct();
    }

    /**
     * Serve a WebDAV request
     */
    function serve()
    {
        $this->serveRequest();
    }

    /**
     * PROPFIND method handler
     *
     * @param  array  general parameter passing array
     * @param  array  return array for file properties
     * @return bool   true on success
     */
    function PROPFIND(&$options, &$files)
    {
        $handler = $this->getPathHandler($options['path']);
        if(!$handler || (!$handler->isArtificialPath() && !$this->checkIfFileExistsAssiduously($handler->getFileSystemPath(), $handler->getBasePath()))) {
            return false;
        }
        //Add in the top level directory
        $files['files'] = array(
            $this->getHandlerInfo($handler, false)
        );

        if($options['depth'] && $handler->isDirectory()) {
            foreach($handler->getDirectoryContents() as $childHandler) {
                $files['files'][] = $this->getHandlerInfo($childHandler);
            }
        }

        return true;
    }

    // Dreamweaver has a nasty habbit of creating a directory then
    // immediately doing a PROPFIND on the directory.  In a load
    // balanced web server environment, that can cause the test
    // for the file's existance to report it's not there because NFS
    // hasn't caught up.
    //
    // Here's the story on NFS race conditions with load balanced web servers:
    // Randomly things would fail.  Eventually I figured out that the load
    // balanced web servers had differing views of NFS as the client requested
    // a series of changes.  Occasionally, that leads to problems.
    //
    // It looks like file locking won't help me because I can't lock a
    // directory.  (It just now occurs to me that I could stick a file in the
    // directory which would serve as a lock on the directory itself.)  I've
    // done various hacky things that have mostly fixed the problems.
    //
    // My solution at this point, which I feel dirty about and you will too, is
    // to recursively create the folder structure under any files or folders
    // that are requested, ignoring errors.  When a client asks if something
    // exists, I recursively touch parent directories, from root toward leaf,
    // and check if children exist.  Touching seems to force the NFS client to
    // refresh its view of the directory from the NFS server.  Not doing it
    // recursively led to a nasty situation in which a deleted folder would get
    // recreated.
    //
    // Some of the scenarios that have caused me problems include:
    //
    // 1.   mkdir foo/
    // 2.   touch foo/bar
    //
    // 1.   mkdir foo/
    // 2.   touch foo/bar
    // 3.   rm -r foo/
    // 4.   ls -d foo/ (or ls -R foo/ or ls foo/bar)
    //
    // Now that I've enumerated those, I can't think of any others.  That should probably scare me more.
    //
    // Assiduously: (adj) constant in application or effort; working diligently at a task; persevering; industrious; attentive
    private function checkIfFileExistsAssiduously($fileSystemPath, $basePath)
    {
        return $this->recursiveTouch($fileSystemPath, $basePath);
    }

    // Walking toward the root and touching parent folders seems to force NFS to get
    // updated information.  Yes, race conditions are still possible.
    private function recursiveTouch($path, $basePath)
    {
        $path = $this->_unslashify($path);
        if ($path === '.' || $path === '/' || strlen($basePath) >= strlen($path)) {
            return true;
        }

        $parent = dirname($path);
        if (!$this->recursiveTouch($parent, $basePath) || !FileSystem::isReadableDirectory($parent)) {
            return false;
        }

        @touch($parent);
        return is_readable($path);
    }

    /**
     * Get a PathHandler for the given DAV path
     * @param string $davPath The portions of the URL after `/dav/` (e.g. `cp/customer/generated/staging/`)
     * @return PathHandler|null
     */
    private function getPathHandler($davPath) {
        try {
            return new PathHandler($davPath);
        }
        catch(\Exception $e) {
            return null;
        }
    }

    /**
     * Get properties for a single file/resource
     *
     * @param PathHandler $handler The path handler
     * @param boolean $isChild Whether or not the requested handler is for the URI path or one of its children
     * @return array A list of resource properties
     */
    private function getHandlerInfo($handler, $isChild = true) {
        $fileSystemPath = $handler->getFileSystemPath();
        $additionalProperties = array();
        if($handler->isDirectory()) {
            $additionalProperties[] = $this->mkprop('resourcetype', 'collection');
            $additionalProperties[] = $this->mkprop('getcontenttype', 'httpd/unix-directory');
        }
        else {
            $additionalProperties[] = $this->mkprop('resourcetype', '');
            $additionalProperties[] = $this->mkprop('getcontenttype', $this->mimeType($fileSystemPath));
            $additionalProperties[] = $this->mkprop('getcontentlength', @filesize($fileSystemPath));
        }

        $name = $handler->getFileOrFolderName();
        return array(
            'self' => !$isChild,
            'path' => $this->_urlencode($name) . ($handler->isDirectory() ? '/' : ''),
            'props' => array_merge($additionalProperties, array(
                $this->mkprop('displayname', strtoupper($name)),
                $this->mkprop('creationdate', $handler->getCreationTime()),
                $this->mkprop('getlastmodified', $handler->getModifiedTime(false)),
            ))
        );
    }

    /**
     * try to detect the mime type of a file
     *
     * @param string $fileSystemPath file path
     * @param mixed $default default mime type if type could not be determined.
     * @return string guessed mime type
     */
    private function mimeType($fileSystemPath, $default = 'application/octet-stream')
    {
        if (@FileSystem::isReadableDirectory($fileSystemPath))
        {
            // directories are easy
            return "httpd/unix-directory";
        }
        else if (function_exists("mime_content_type"))
        {
            // use mime magic extension if available
            $mime_type = mime_content_type($fileSystemPath);
        }
        else
        {
            $mime_type = $this->mimeTypeByExtension(pathinfo($fileSystemPath, PATHINFO_EXTENSION), null);
        }

        if ($mime_type === null && $this->canExecute("file"))
        {
            // it looks like we have a 'file' command,
            // lets see it it does have mime support
            $fp    = popen("file -i '$fileSystemPath' 2>/dev/null", "r");
            $reply = fgets($fp);
            pclose($fp);

            // popen will not return an error if the binary was not found
            // and find may not have mime support using "-i"
            // so we test the format of the returned string

            // the reply begins with the requested filename
            if (!strncmp($reply, "$fileSystemPath: ", strlen($fileSystemPath)+2))
            {
                $reply = substr($reply, strlen($fileSystemPath)+2);
                // followed by the mime type (maybe including options)
                if (preg_match('|^[[:alnum:]_-]+/[[:alnum:]_-]+;?.*|', $reply, $matches))
                    $mime_type = $matches[0];

            }
        }

        if ($mime_type === null)
            $mime_type = $default;

        return $mime_type;
    }

    /**
     * detect if a given program is found in the search PATH
     *
     * helper function used by mimeType() to detect if the
     * external 'file' utility is available
     *
     * @param  string  program name
     * @param  string  optional search path, defaults to $PATH
     * @return bool    true if executable program found in path
     */
    private function canExecute($name, $path = false)
    {
        // path defaults to PATH from environment if not set
        if ($path === false)
            $path = getenv("PATH");

        // check method depends on operating system
        if (!strncmp(PHP_OS, "WIN", 3))
        {
            // on Windows an appropriate COM or EXE file needs to exist
            $exts     = array(".exe", ".com");
            $check_fn = "\\RightNow\\Utils\\FileSystem::isReadableFile";
        }
        else
        {
            // anywhere else we look for an executable file of that name
            $exts     = array("");
            $check_fn = "is_executable";
        }

        // now check the directories in the path for the program
        foreach (explode(PATH_SEPARATOR, $path) as $dir)
        {
            // skip invalid path entries
            if (!FileSystem::isReadableDirectory($dir)) {
                continue;
            }

            // and now look for the file
            foreach ($exts as $ext)
            {
                if ($check_fn("$dir/$name".$ext)) return true;
            }
        }
        return false;
    }

    /**
     * Return mime type for defined file types, else return $default.
     * @param string $extension The file extension to check
     * @param $default The fallback value if a mimetype is not found
     * @return string the mimetype
     */
    private function mimeTypeByExtension($extension, $default = 'application/octet-stream')
    {
        if (array_key_exists(strtolower($extension), $this->mimeTypes))
            return $this->mimeTypes[strtolower($extension)];

        return $default;
    }

    /**
     * GET method handler
     *
     * @param  array  parameter passing array
     * @return bool   true on success
     */
    function GET(&$options) {
        $handler = $this->getPathHandler($options['path']);
        if(!$handler || !$handler->isVisiblePath() || !$handler->fileExists()) {
            return false;
        }

        if(!$this->modifiedHeadRequest && $handler->isDirectory()) {
            echo $this->getIndexHtml($handler);
            exit;
        }

        if(!$handler->isArtificialPath()) {
            $fileSystemPath = $handler->getFileSystemPath();

            $options['mimetype'] = $this->mimeType($fileSystemPath);
            $options['mtime'] = filemtime($fileSystemPath);
            $options['size'] = filesize($fileSystemPath);

            if(!$handler->isDirectory()) {
                $options['stream'] = fopen($fileSystemPath, 'r');
            }
        }

        return true;
    }

    /**
     * Given a PathHandler generate the an HTML file listing.
     * @param PathHandler $handler The path to be displayed
     * @return string The generated HTML content
     */
    private function getIndexHtml($handler) {
        $directoryContents = $handler->getDirectoryContents();
        $format = "%15s  %-19s  %-s\n";
        $title = Text::escapeHtml(sprintf($format, "Size", "Last Modified", "FileName"));

        $content = '';
        foreach($directoryContents as $childHandler) {
            $class = $childHandler->isDirectory() ? "class='dir'" : '';
            $url = Text::escapeHtml($childHandler->getDavUrl());
            $fileName = Text::escapeHtml($childHandler->getFileOrFolderName());
            $content .= sprintf($format, $childHandler->getSize(), $childHandler->getModifiedTime(), "<a $class href='$url'>$fileName</a>");
        }

        $breadcrumb = $this->getBreadcrumbHtml($handler->getDavPath());

        return <<<OUTPUT
<html>
    <head>
        <title>Index of {$handler->getDavPath()}</title>
        <style>a.dir{ font-weight: bold }</style>
    </head>
    <body>
        <h3>Index of $breadcrumb</h3>
        <pre>$title<hr>$content</pre>
    </body>
</html>
OUTPUT;
    }

    /**
     * Generate a breadcrumb to be used at the top of the file index pages
     * @param string $davPath The WebDAV path
     * @return string HTML containing slash separated path segments
     */
    private function getBreadcrumbHtml($davPath) {
        if($davPath === '/') return '/';

        $html = '/';
        $href = '/dav';
        foreach(explode('/', $davPath) as $segment) {
            $segment = Text::escapeHtml($segment);
            $href .= "/$segment";
            $html .= "<a href='$href'>$segment</a>/";
        }
        return $html;
    }

    /**
     * Return true if we should silently ignore requests to write to $path.
     * This function also looks to see if $path is a sub-directory of one of the ignorable files/dirs below.
     *
     * @param string $davPath The DAV path of the file begin written
     * @return boolean
     */
    private function isIgnoredPutRequest($davPath) {
        if (\RightNow\Utils\Text::beginsWith(basename($davPath), '._')) {
            return true;
        }

        $pathElements = explode('/', $davPath);
        foreach(array('temp2342.htm', '.DS_Store', '.VolumeIcon.icns', '.fseventsd', '.Trashes') as $ignore) {
            if (in_array($ignore, $pathElements)) {
                return true;
            }
        }
        return false;
    }


    /**
     * PUT method handler
     *
     * @param  array  parameter passing array
     * @return string|object file pointer on success, error code on failure
     */
    function PUT(&$options)
    {
        $handler = $this->getPathHandler($options['path']);
        if($this->isIgnoredPutRequest($options['path'])) {
            return ($handler && $handler->fileExists()) ? '204 No Content' : '201 Created';
        }

        if(!$handler || !$this->pmdPermission('PUT', $handler->getDavSegments())) {
            return '403 Forbidden';
        }

        $fileSystemPath = $handler->getFileSystemPath();

        //If parent folder doesn't exist, try to create it. This
        //happens when running through the load balancer
        $parent = dirname($fileSystemPath);
        if (!@FileSystem::isReadableDirectory($parent)) {
            @mkdir($parent, 0777, TRUE);
        }

        $options['new'] = !is_readable($fileSystemPath);
        if(($fp = @fopen($fileSystemPath, 'w')) === false) {
            return '403 Forbidden';
        }

        $this->logInsert(($options['new']) ? self::TYPE_FILE_UPLOAD : self::TYPE_FILE_EDIT, $handler->getDavPath());
        return $fp;
    }


    /*
     * Dreamweaver's Synchronize function tries to create the following folders at the root
     * of the DAV share to test file creation on the server. If we let it think that it created
     * the folders but don't actually create the folders or ever give another indication that they
     * exist, Dreamweaver seems to march along merrily along doing the right thing.
     *
     * @param string $davPath The full DAV path
     * @return boolean True or false, whether the path is a Dreamweaver file
     */
    private function isIgnoredMkColRequest($davPath) {
        $davPath = explode('/', $this->_unslashify($davPath));
        $davPath = end($davPath);
        return $davPath === 'MM_CASETEST4291' || $davPath === 'mm_casetest4291' || $davPath === 'xyiznwsk';
    }

    /**
     * MKCOL method handler
     *
     * @param  array  general parameter passing array
     * @return string the status code for the request. 201 created on success.
     */
    function MKCOL($options)
    {
        if($this->isIgnoredMkColRequest($options['path'])) {
            return '201 Created';
        }

        if(!$handler = $this->getPathHandler($options['path'])) {
            return '403 Forbidden';
        }

        $status = $this->mkcolCore($handler, false);

        if (is_string($status) && Text::beginsWith($status, '20')) {
            $this->logInsert(self::TYPE_FOLDER_UPLOAD, $handler->getDavPath());
        }

        return $status;
    }

    /**
     * The core functionality for creating directories.
     * @param PathHandler $handler The handler for the directory to be created
     * @param boolean $allowOverwriteExistingDirectory Whether or not a pre-existing directory can be overwritten
     * @return string The status code for the request. 201 or 204 on success.
     */
    private function mkcolCore($handler, $allowOverwriteExistingDirectory)
    {
        if(!$this->mkcolPermission($handler->getDavSegments())) {
            return '403 Forbidden';
        }

        $fileSystemPath = $handler->getFileSystemPath();
        $parent = dirname($fileSystemPath);
        if(!$this->checkIfFileExistsAssiduously($parent, $handler->getBasePath()) || !FileSystem::isReadableDirectory($parent)) {
            return '409 Conflict';
        }

        if(FileSystem::isReadableDirectory($fileSystemPath)) {
            if($allowOverwriteExistingDirectory) {
                $didTargetExist = true;
            }
            else {
                return '405 Method Not Allowed';
            }
        }

        if(!empty($this->_SERVER["CONTENT_LENGTH"])) {
            return '415 Unsupported media type';
        }

        if(!$stat = @mkdir($fileSystemPath, 0777, true)) {
            return '403 Forbidden';
        }
        return ($didTargetExist) ? '204 No Content' : '201 Created';
    }

    /**
     * DELETE method handler
     *
     * @param  array  general parameter passing array
     * @return string The status code for the request. 204 on success.
     */
    function DELETE($options, $destHandler = null)
    {
        $handler = $destHandler ? $destHandler : $this->getPathHandler($options['path']);
        if(!$handler || !$this->pmdPermission('DELETE', $handler->getDavSegments())) {
            return '405 Method Not Allowed';
        }

        $fileSystemPath = $handler->getFileSystemPath();
        if(!is_readable($fileSystemPath)) {
            return '404 Not found';
        }

        if($handler->isDirectory()) {
            if(!$this->recursive_rmdir($fileSystemPath)) {
                return '405 Method Not Allowed';
            }
        }
        else {
            @unlink($fileSystemPath);
        }

        if(!$destHandler) {
            $this->logInsert(self::TYPE_FILE_DELETE, $handler->getDavPath());
        }

        return '204 No Content';
    }

    /**
     * MOVE method handler
     *
     * @param  array  general parameter passing array
     * @return string The status code for the request. 201 or 204 on success.
     */
    function MOVE($options)
    {
        $handler = $this->getPathHandler($options['path']);
        $destHandler = $this->getPathHandler($options['dest_path']);
        if(!$handler || !$destHandler || !$this->pmdPermission('COPY', $destHandler->getDavSegments()) || !$this->pmdPermission('DELETE', $handler->getDavSegments())) {
            return '405 Method Not Allowed';
        }

        list($error, $deletedDestination) = $this->initializeCopyOrMove($options, $handler, $destHandler);
        if ($error !== false)
            return $error;

        if (!rename($handler->getFileSystemPath(), $destHandler->getFileSystemPath())) {
            return '500 Internal server error';
        }

        $this->logInsert(self::TYPE_FILE_MOVE, $handler->getDavPath(), $destHandler->getDavPath());
        return ($deletedDestination) ? '204 No Content' : '201 Created';
    }

    /**
     * COPY method handler
     *
     * @param  array  options - general parameter passing array
     * @return string The status code for the request. 201 or 204 on success.
     */
    function COPY($options)
    {
        $handler = $this->getPathHandler($options['path']);
        $destHandler = $this->getPathHandler($options['dest_path']);
        if(!$handler || !$destHandler || !$this->pmdPermission('COPY', $destHandler->getDavSegments())) {
            return '405 Method Not Allowed';
        }

        list($error, $deletedDestination) = $this->initializeCopyOrMove($options, $handler, $destHandler);
        if ($error !== false)
            return $error;

        //If the depth is zero, just copy the directory and none of its contents.
        if ($handler->isDirectory() && $options['depth'] === '0') {
            $status = $this->mkcolCore($destHandler, $options['overwrite']);
            if (Text::beginsWith($status, '20')) {
                $this->logInsert(self::TYPE_FILE_COPY, $handler->getDavPath(), $destHandler->getDavPath());
            }
            return $status;
        }

        //If the depth is non-zero, copy the entire directory
        if (!$this->recursiveCopy($handler->getFileSystemPath(), $destHandler->getFileSystemPath())) {
            return '409 Conflict';
        }

        $this->logInsert(self::TYPE_FILE_COPY, $handler->getDavPath(), $destHandler->getDavPath());

        return ($deletedDestination) ? '204 No Content' : '201 Created';
    }

    /**
     * Common code from copy and move.
     * @param array $options A list of options provided by the base class
     * @param PathHandler $handler The handler for the source path
     * @param PathHandler $destHandler The Handler for the destination path
     * @return array An array containing an error (if one occurs) and whether or not the destination was deleted.
     */
    private function initializeCopyOrMove($options, $handler, $destHandler) {
        $source = $handler->getFileSystemPath();
        if(!self::checkIfFileExistsAssiduously($source, $handler->getBasePath())) {
            return array("404 Not found");
        }

        if($destinationExists = is_readable($destHandler->getFileSystemPath())) {
            if(!$options['overwrite']) {
                return array("412 precondition failed destination ({$destHandler->getDavPath()}) exists and overwrite not specified");
            }

            //Attempt to delete the file
            $status = $this->DELETE(array(), $destHandler);
            if($status[0] !== '2' && substr($status, 0, 3) !== '404') {
                return array($status);
            }
        }

        if($handler->isDirectory() && $options['depth'] !== 'infinity' && $options['depth'] !== '0') {
            // RFC 2518 Section 9.2, last paragraph
            return array("400 Bad request");
        }

        return array(false, $destinationExists);
    }

    /**
     * Recursively copy a folder (or just copy a file) from source to destination
     * @param string $source The absolute source path
     * @param string $destination The absolute destination path
     * @return boolean true on success, false on failure
     */
    private function recursiveCopy($source, $destination)
    {
        if (!FileSystem::isReadableDirectory($source))
        {
            // I added this gem to appease Litmus.  Basically, if the
            // destination looks like it's supposed to be a directory, treat it
            // as one.
            if (!FileSystem::isReadableDirectory($destination) && Text::endsWith($destination, '/')) {
                @mkdir($destination, 0777, true);
                $destination .= basename($source);
            }

            if (@copy($source, $destination))
            {
                @chmod($destination, 0666);
                return true;
            }
            return false;
        }

        $source = $this->_slashify($source);
        $destination = $this->_slashify($destination);

        // I don't check the result of mkdir because sometimes NFS fails when it shouldn't
        // in a load balanced web server environment.  If this really failed,
        // we'll know when we try to copy the files into the directory.
        @mkdir($destination, 0777, true);

        $directory = dir($source);
        while ($entry = $directory->read())
        {
            if ($entry === '.' || $entry === '..')
                continue;
            if (!$this->recursiveCopy($source . $entry, $destination . $entry))
                return false;
            }
        $directory->close();
        return true;
    }

    /**
     * PROPPATCH method handler. This method is NOT implemented and denies all requests to alter properties.
     *
     * @param  array  general parameter passing array
     * @return string The status code.
     */
    function PROPPATCH(&$options)
    {
        foreach ($options['props'] as $key => $prop)
        {
            if ($prop['ns'] === 'DAV:')
                $options['props'][$key]['status'] = '403 Forbidden';
        }

        return '';
    }

    /**
     * Function to recursively delete everything within a directory
     *
     * @param  directory  The directory path to delete
     * @return  boolean indicating if deletion was successful
     */
    function recursive_rmdir($directory)
    {
        if(substr($directory, -1) === '/')
            $directory = substr($directory, 0, -1);

        if(!is_readable($directory))
        {
            return FALSE;
        }
        else
        {
            $handle = opendir($directory);

            //scan through the items inside
            while (FALSE !== ($item = readdir($handle)))
            {
                if($item != '.' && $item != '..')
                {
                    $path = $directory.'/'.$item;
                    if(FileSystem::isReadableDirectory($path))
                        $this->recursive_rmdir($path);
                    else
                        unlink($path);
                }
            }
            closedir($handle);

            // try to delete the now empty directory
            if(!rmdir($directory))
            {
                return FALSE;
            }
            return TRUE;
        }
    }

    /**
     * This function determines permissions for the PUT, MOVE,
     * and DELETE requests (hence 'pmd')
     *
     * @param string $operation The operation type
     * @param array $pathSegments The exploded path segments of the full DAV path
     * @return bool Indication if permissions check has passed, i.e. the location can be written to
     */
    private function pmdPermission($operation, $pathSegments)
    {
        //Always skip the leading `cp/` on the path.
        list(, $phase, $firstSubItem, $secondSubItem, $thirdSubItem, $fourthSubItem) = $pathSegments;

        if($firstSubItem === "") {
            return false;
        }
        else if($phase === "logs") {
            if($operation === "DELETE" && \RightNow\Internal\Utils\Logs::isDebugLog($firstSubItem))
                return true;
        }
        else if ($phase === 'customer' && $firstSubItem === 'assets') {
            return true;
        }
        else if($phase === "customer" && $firstSubItem === 'error') {
            $allowedErrorPages = array("splash.html", "error500.html");

            switch($operation) {
                case "DELETE":
                    return parent::isClientMacOS() || self::isCyberduck();
                case "PUT":
                    return self::isAllowedFile($secondSubItem, $allowedErrorPages);
                break;
                default:
                    return false;
            }
        }
        else if($phase === "customer" && $firstSubItem === 'development')
        {
            if(self::isAllowedFile($secondSubItem, array("allowMixedFrameworkSpPatching"))){
                if($operation === "DELETE" || $operation === "PUT") {
                    return true;
                }
            }
            if($secondSubItem === "config") {
                $validConfigFiles = array("extensions.yml", "hooks.php", "mapping.php", "search_sources.yml");
                if(self::isAllowedFile($thirdSubItem, $validConfigFiles)) {
                    if($operation === "DELETE") {
                        return false;
                    }
                }
                if($operation === "DELETE" && (boolval($thirdSubItem) === false)) {
                    return false;
                }

                return true;
            }
            else if($secondSubItem === "errors") {
                $errorPages = array('error_general.php', 'error_php.php');

                if($operation === "DELETE") {
                    if(self::isAllowedFile($thirdSubItem, $errorPages + array(''))) {
                        return parent::isClientMacOS() || self::isCyberduck();
                    }
                    if(boolval($thirdSubItem) === false) {
                        return false;
                    }
                    return true;
                }
                return self::isAllowedFile($thirdSubItem, $errorPages);
            }
            else if($secondSubItem === "models" || $secondSubItem === "widgets") {
                if($thirdSubItem === "custom") {
                    if(($operation === "COPY" || $operation=="DELETE") && !$fourthSubItem)
                        return false;
                    return true;
                }
                return false;
            }
            else if($secondSubItem === "libraries" || $secondSubItem === "helpers" || $secondSubItem === "controllers") {
                return $thirdSubItem != "";
            }
            else if($secondSubItem === "javascript") {
                $allowedJS = array("autoload.js");

                if(self::isAllowedFile($thirdSubItem, $allowedJS)) {
                    if($operation === "DELETE")
                        return false;
                }
                if($operation === "DELETE" && boolval($thirdSubItem) === false) {
                    return false;
                }
                return true;
            }
            else if($secondSubItem === "views") {
                if($thirdSubItem === "") {
                    return false;
                }
                else if($thirdSubItem === "pages" || $thirdSubItem === "templates" || $thirdSubItem === "Partials") {
                    return $fourthSubItem != "";
                }
                else if($thirdSubItem === "admin") {
                    // Adding OKCS files answer.php and okcs_answer_full_preview.php as allowedAdminPages; OkcsFile.php and OkcsFattach.php load these pages
                    $allowedAdminPages = array('answer_full_preview.php', 'answer_quick_preview.php', 'answer.php', 'okcs_answer_full_preview.php');

                    switch($operation) {
                        case "DELETE":
                            if(boolval($fourthSubItem) === false) {
                                return false;
                            }
                            return parent::isClientMacOS() || self::isCyberduck();
                        break;
                        case "COPY":
                            return false;
                        break;
                        case "PUT":
                            return self::isAllowedFile($fourthSubItem, $allowedAdminPages);
                        break;
                        default:
                            return false;
                    }
                }
                else {
                    return false;
                }
            }
            else {
                return false;
            }
        }
        else if($phase === "generated" && $firstSubItem === 'temp_backups' && $secondSubItem && $operation === "DELETE") {
            return true;
        }

        return false;
    }

    /**
     * Check if the given file exists in the list of valid paths
     * @param  string  $file The file name to check
     * @param  array  $validPaths The list of valid paths
     * @return boolean Weather or not the file is allowed
     */
    private function isAllowedFile($file, array $validPaths) {
        return in_array($file, $validPaths) || (self::isCyberduck() && self::isCyberduckGuidFile($file, $validPaths));
    }

    /**
     * Weather or not the user agent is Cyberduck
     * @return boolean
     */
    private function isCyberduck() {
        return Text::stringContains($_SERVER['HTTP_USER_AGENT'], "Cyberduck");
    }

    /**
     * Determine if a file is a legal file with a Cyberduck GUID appended to the end. Cyberduck appends this GUID so that an uploaded file is not
     * automatically overwritten, allowing the UI to prompt the user to overwrite the file when it is later moved over the original file. This was
     * likely implemented by Cyberduck to make up for limitations in the WebDAV protocol, unfortunately CP's WebDAV implementation restricts
     * certain directories to only contain specific files. This opens those directories up to fix Cyberduck support.
     *
     * @param  string  $cyberduckPath The path containing the Cyberduck GUID
     * @param  array  $validPaths     The list of valid paths
     * @return boolean True or False if the path is a Cyberduck GUID path
     */
    private function isCyberduckGuidFile($cyberduckPath, array $validPaths) {
        $expression = "@^(" . implode('|', array_map("preg_quote", $validPaths)) . ")-[a-fA-F0-9]{8}(?:-[a-fA-F0-9]{4}){3}-[a-fA-F0-9]{12}$@";

        return preg_match($expression, $cyberduckPath) === 1;
    }

    /**
     * This function determines permissions for the MKCOL request
     *
     * @param array $pathSegments The exploded path segments of the full DAV path
     * @return bool Indication if permissions check has passed
     */
    private function mkcolPermission($pathSegments)
    {
        //Skip the `cp/` segment
        list(, $phase, $firstSubItem, $secondSubItem, $thirdSubItem) = $pathSegments;

        if($phase !== 'customer') {
            return false;
        }
        if($firstSubItem === 'assets') {
            return true;
        }
        if($firstSubItem === 'development') {
            if($secondSubItem === 'widgets' || $secondSubItem === 'models')
                return $thirdSubItem === 'custom';
            if($secondSubItem === 'views')
                return ($thirdSubItem === 'pages' || $thirdSubItem === 'templates' || $thirdSubItem === 'Partials');
            if($secondSubItem === 'libraries' || $secondSubItem === 'controllers' || $secondSubItem === 'helpers')
                return true;
        }
        return false;
    }

    /**
     * Inserts a log that keeps track of who was modifying files and when
     *
     * @param string $entry_type  The type of log to create (i.e. delete, edit)
     * @param string $file The file that was modified
     * @param string $destination If the request modified two files, destination is the additional path (move, copy)
     */
    private function logInsert($entry_type, $file, $destination=null)
    {
        $log = @fopen($this->logPath, "a");
        if($log)
        {
            if($destination)
                $file .= " -> " . urldecode($destination);

            //Encode any commas and special characters in the file name
            $file = str_replace(',', '&#44;', htmlspecialchars($file));
            $entry = sprintf("%s,%d,%s,%s,%d,%s\n",
                  $file,
                  htmlspecialchars($this->account->acct_id),
                  $this->_SERVER["REMOTE_ADDR"],
                  date("m/d/Y H:i:s"),
                  $entry_type,
                  \RightNow\Api::intf_name());

            fwrite($log, $entry);
            fclose($log);
        }
    }
}
