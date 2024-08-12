<?

$cpPhpVersion = substr(PHP_VERSION_ID, 0, -2) . '00';
define('CP_PHP_VERSION', $cpPhpVersion);
define('IS_HOSTED', (get_cfg_var('rnt.hosted') == 0) ? false : true);

dl('yaml-'.CP_PHP_VERSION.'.so');

//Path to /scripts
$documentRoot = get_cfg_var('doc_root');

$getFrameworkVersionToUse = function() use($documentRoot){
    list($mode) = explode("~", isset($_COOKIE['location']) && $_COOKIE['location'] ? $_COOKIE['location'] : '');
    $mode = strtolower($mode) ?: 'production';

    $frameworkDeclarationFile = \RightNow\Routes\CustomerPortal::getCurrentFrameworkVersionFilePath($documentRoot, $mode);
    if(strncmp('staging', $mode, 7) === 0 && !is_file($frameworkDeclarationFile)) {
        setcookie('location', '', time() - 604800, '/');
        $responseBody = "<html><body>The $mode environment is in an unusable state.<br/><br/> Click <a href='//{$_SERVER['SERVER_NAME']}/ci/admin/deploy/selectFiles'>here</a> to re-stage.</body></html>";
        header('Content-Length: ' . strval(strlen($responseBody)));
        header('Content-Type: text/html');
        echo $responseBody;
        exit();
    }

    if(is_readable($frameworkDeclarationFile) && is_file($frameworkDeclarationFile)){
        $version = trim(file_get_contents($frameworkDeclarationFile));
        $nanoVersionSpecified = (($versionParts = explode('.', $version)) && count($versionParts) === 3);
        $directoryIterator = new DirectoryIterator("$documentRoot/cp/core/framework");
        $possibleVersions = array();
        foreach ($directoryIterator as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if (0 !== strncmp($version, $filename, strlen($version))) {
                continue;
            }
            // if we found a version that matches the nano version specified, return it
            if ($nanoVersionSpecified) {
                return $version;
            }
            $possibleVersions[] = substr($filename, strlen($version) + 1);
        }
        rsort($possibleVersions, SORT_NUMERIC);
        return count($possibleVersions) ? $version . '.' . $possibleVersions[0] : null;
    }
    return null;
};

$parseFrameworkManifest = function($version) use($documentRoot){
    $manifestPath = "$documentRoot/cp/core/framework" . (IS_HOSTED && $version !== null ? "/$version" : "") . '/manifest';
    if(is_readable($manifestPath) && is_file($manifestPath))
    {
        $frameworkDetails = yaml_parse_file($manifestPath);
        if(is_array($frameworkDetails))
            return $frameworkDetails;
        throw new Exception("Couldn't parse the frameworks manifest file. Sorry.");
    }
    throw new Exception("Couldn't find or read the framework manifest file for the version of the framework you're attempting to access ($version). Sorry.");
};

$version = $getFrameworkVersionToUse();
try{
    $frameworkManifest = $parseFrameworkManifest($version);
}
catch(Exception $e){
    if(!IS_HOSTED){
        echo $e->getMessage() . "\n";
    }
    exit("This interface has not been successfully deployed");
}
$frameworkDependencies = $frameworkManifest['dependencies'];

if (array_key_exists('yui', $frameworkDependencies)) {
    //Define the prefix for where YUI files should be loaded.
    define('YUI_SOURCE_DIR', $frameworkDependencies['yui']['location']);
    define('CP_YUI_VERSION', $frameworkDependencies['yui']['version']);
}

sscanf($frameworkManifest['version'], "%d.%d.%d", $major, $minor, $nano);
define("CP_FRAMEWORK_VERSION", "$major.$minor");
define("CP_FRAMEWORK_NANO_VERSION", $nano);

//Remove these functions now that they are no longer needed
unset($getFrameworkVersionToUse);
unset($parseFrameworkManifest);
unset($frameworkDependencies);
//Start up the framework version specified
require_once "$documentRoot/cp/core/framework" . (IS_HOSTED ? "/{$version}" : "") . '/init.php';
