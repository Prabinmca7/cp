<?
//Utility script to return template and page JavaScript

define('HTMLROOT', get_cfg_var('rnt.html_root'));
define('IS_HOSTED', get_cfg_var('rnt.hosted') === 1);
$documentRoot = get_cfg_var('doc_root') . "/cp";

function getLatestVersion($path, $versionMajorMinor) {
    if(!is_readable($path) || !is_dir($path)) {
        echo "Invalid path $path";
        return null;
    }
    if(!$versionMajorMinor) {
        echo 'Invalid $versionMajorMinor argument sent to getLatestVersion (' . var_export($versionMajorMinor, true) . ')';
        return null;
    }
    $directoryIterator = new DirectoryIterator($path);
    $possibleVersions = array();
    foreach ($directoryIterator as $fileInfo) {
        $filename = $fileInfo->getFilename();
        if (0 !== strncmp($versionMajorMinor, $filename, strlen($versionMajorMinor))) {
            continue;
        }
        $possibleVersions[] = substr($filename, strlen($versionMajorMinor) + 1);
    }
    rsort($possibleVersions, SORT_NUMERIC);
    return count($possibleVersions) ? $versionMajorMinor . '.' . $possibleVersions[0] : null;
}

function getLatestTimestamp($path) {
    if(!is_readable($path) || !is_dir($path)) {
        echo "Invalid path $path";
        return null;
    }
    $directoryIterator = new DirectoryIterator($path);
    $possibleTimestamps = array();
    foreach ($directoryIterator as $fileInfo) {
        if ($fileInfo->isDot())
            continue;
        $possibleTimestamps[] = $fileInfo->getFilename();
    }
    rsort($possibleTimestamps, SORT_NUMERIC);
    return count($possibleTimestamps) ? $possibleTimestamps[0] : null;
}

if(IS_HOSTED) {
    $frameworkVersionProduction = trim(@file_get_contents("$documentRoot/generated/production/optimized/frameworkVersion"));
    $frameworkVersionProduction = getLatestVersion("$documentRoot/core/framework/", $frameworkVersionProduction);
    if(!$frameworkVersionProduction)
        return;
}
else {
    $manifestPath = "$documentRoot/core/framework/manifest";
    if(!is_readable($manifestPath) || !is_file($manifestPath)) {
        echo "Invalid manifest path $manifestPath";
        return;
    }
    $frameworkDetails = @yaml_parse_file($manifestPath);
    if(!$frameworkDetails['version']) {
        echo "Manifest file $manifestPath appears to be invalid";
        return;
    }
    $frameworkVersionProduction = $frameworkDetails['version'];
}

$assetLocationProduction = HTMLROOT . '/euf/generated/optimized/';
$timestampProduction = getLatestTimestamp($assetLocationProduction);
$assetLocationProduction = $assetLocationProduction . $timestampProduction . '/';

$generatedProduction = "$documentRoot/generated/production/optimized/";

// set production mode
$modes = array(
    'production' => array('frameworkVersion' => $frameworkVersionProduction, 'assetLocation' => $assetLocationProduction, 'generated' => $generatedProduction, 'timestamp' => $timestampProduction),
);

// add staging modes (e.g. staging_01)
$assetLocationStagingPrefix = HTMLROOT . '/euf/generated/staging/';
$generatedStagingPrefix = "$documentRoot/generated/staging/";
$stagingIterator = new DirectoryIterator($generatedStagingPrefix);
foreach ($stagingIterator as $fileInfo) {
    if ($fileInfo->isDot())
        continue;
    $stagingName = $fileInfo->getFilename();
    if(IS_HOSTED) {
        $frameworkVersionStaging = trim(@file_get_contents("$documentRoot/generated/staging/$stagingName/optimized/frameworkVersion"));
        $frameworkVersionStaging = getLatestVersion("$documentRoot/core/framework/", $frameworkVersionStaging);
        if(!$frameworkVersionStaging)
            return;
    }
    else {
        $frameworkVersionStaging = $frameworkDetails['version'];
    }
    $assetLocationStaging = "$assetLocationStagingPrefix$stagingName/optimized/";
    $timestampStaging = getLatestTimestamp($assetLocationStaging);
    $assetLocationStaging = $assetLocationStaging . $timestampStaging . '/';
    $generatedStaging = "$generatedStagingPrefix$stagingName/optimized/";
    $modes[$stagingName] = array('frameworkVersion' => $frameworkVersionStaging, 'assetLocation' => $assetLocationStaging, 'generated' => $generatedStaging, 'timestamp' => $timestampStaging);
}

$widgetLocationStandard = "$documentRoot/core/widgets/";

echo "Starting script\n";
foreach ($modes as $mode) {
    $frameworkVersion = $mode['frameworkVersion'];
    $assetLocation = $mode['assetLocation'];
    $generated = $mode['generated'];
    $widgetLocationCustom = "$generated/widgets/";
    $timestamp = $mode['timestamp'];
    $path = $generated . 'javascript/';
    $jsonFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    while ($jsonFiles->valid()) {
        if (substr($jsonFiles->getFilename(), -5) === '.json') {
            $javaScriptData = '';
            $jsonFile = $jsonFiles->getSubPathName();
            $originalFilePath = $assetLocation . substr_replace($jsonFile, '.js', -5);
            $javaScriptInfo = json_decode(file_get_contents("$path$jsonFile"), true);
            $updatedJavaScriptPaths = array();
            foreach($javaScriptInfo as $javaScriptFile)
            {
                if(!array_key_exists('version', $javaScriptFile))
                {
                    // getting file directly
                    list($major, $minor) = explode('.', $frameworkVersion);
                    $javaScriptContents = @file_get_contents(HTMLROOT . (!IS_HOSTED ? $javaScriptFile['path'] : str_replace('/euf/core/', "/euf/core/{$major}.{$minor}/", $javaScriptFile['path'])));
                    $updatedJavaScriptPaths[] = $javaScriptFile['path'];
                }
                else if(!$javaScriptFile['version'])
                {
                    // getting widget file with no version
                    $javaScriptFilePath = (($javaScriptFile['type'] === 'standard') ? $widgetLocationStandard : $widgetLocationCustom)
                        . $javaScriptFile['path'] . "/optimized/optimizedWidget.js";
                    $javaScriptContents = @file_get_contents($javaScriptFilePath);
                    $updatedJavaScriptPaths[] = $javaScriptFile['path'] . '/';
                }
                else
                {
                    // getting widget file with a version (going to find correct nano number)
                    $javaScriptFilePath = (($javaScriptFile['type'] === 'standard') ? $widgetLocationStandard : $widgetLocationCustom)
                        . $javaScriptFile['path'];
                    $versionMajorMinor = substr($javaScriptFile['version'], 0, strrpos($javaScriptFile['version'], '.'));
                    $latestVersion = getLatestVersion($javaScriptFilePath, $versionMajorMinor);
                    if(!$latestVersion)
                        return;
                    $javaScriptFilePath .= "/$latestVersion/optimized/optimizedWidget.js";
                    $javaScriptContents = @file_get_contents($javaScriptFilePath);
                    $updatedJavaScriptPaths[] = $javaScriptFile['path'] . ($latestVersion ? "/$latestVersion" : "");
                }
                $javaScriptData .= "\n" . $javaScriptContents;
            }

            $newHash = md5($timestamp . '.' . $frameworkVersion . '.' . json_encode($updatedJavaScriptPaths));
            $newFilename = substr_replace($originalFilePath, '.' . $newHash, -3, 0);

            if(!is_writable($newFilename)) {
                echo "creating new file $newFilename\n";
                @file_put_contents($newFilename, $javaScriptData);
            }
        }
        $jsonFiles->next();
    }
}
echo "Finished script\n";
