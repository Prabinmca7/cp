<?php
namespace RightNow\Internal\Utils;

use RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Widget\DependencyInfo,
    RightNow\Utils\FileSystem as FileSystemExternal,
    RightNow\Utils\Text as TextExternal;

final class Version
{
    private static $versionHistoryCache = null;
    private static $versionHistoryCacheWithoutExtras = null;

    /**
     * Compares two CP version numbers in the format x, x.y, or x.y.z. Useful as a callback to PHP's myriad
     * of sort functions.
     *
     * @param string $versionA The first version number
     * @param string $versionB The second version number
     * @return int The value 0 if the versions are the same, 1 if versionA is greater, -1 if versionB is greater
     */
    static function compareVersionNumbers($versionA, $versionB){
        $versionA = (int) vsprintf("%03d%03d%03d", array_pad(explode('.', ($versionA) ?: "", 3), 3, 0));
        $versionB = (int) vsprintf("%03d%03d%03d", array_pad(explode('.', ($versionB) ?: "", 3), 3, 0));

        if ($versionA > $versionB) return 1;
        if ($versionA < $versionB) return -1;
        return 0;
    }

    /**
     * Removes the version folder from a given path.
     *
     * @param string $path Path that possibly contains a version folder
     * @return string Modified path with version folder removed
     */
    static function removeVersionPath($path){
        return preg_replace('#/\d+\.\d+(\.\d+)?#', '', $path);
    }

    static function clearCacheVariables() {
        self::$versionHistoryCache = null;
        self::$versionHistoryCacheWithoutExtras = null;
    }

    static function getMonths() {
        return array(null, 'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December');
    }

    static function getVersionHistoryPath() {
        return CORE_FILES . 'cpHistory';
    }

    /**
     * Returns the contents of the specified path that leads to either the
     * cpHistory, widgetVersions, or frameworkVersion file.
     * @param string $path File path to a cpHistory, widgetVersions or frameworkVersion file
     * @return null|string|array Contents of the file as a string (or array depending on
     *   how it is to be serialized) or null if the file isn't readable.
     */
    static function getVersionFile($path) {
        if (FileSystemExternal::isReadableFile($path)) {
            $contents = file_get_contents($path);
            if ($serialization = self::getSerializationStrategy($path)) {
                return ($serialization === 'yaml')
                    ? yaml_parse($contents)
                    : unserialize($contents);
            }

            return trim($contents);
        }
    }

    /**
     * Writes the contents to the specified file path.
     * Serializes the contents if the specified file is
     * cpHistory or widgetVersions.
     * @param string $path File path to a cpHistory, widgetVersions or frameworkVersion file
     * @param string|array $contents Content to write; serialized for cpHistory and widgetVersions
     * @param string|null $serialization Serialization strategy to use (php or yaml) defaults
     *                                   to using #getSerializationStrategy if not specified (shouldn't be specified unless
     *                                   deliberately attempting to convert the file during deploy)
     * @return bool Whether the operation succeeded
     */
    static function writeVersionFile($path, $contents, $serialization = '') {
        $serialization || ($serialization = self::getSerializationStrategy($path));
        if ($serialization) {
            if ($serialization === 'yaml') {
                $contents = yaml_emit($contents);
            }
            else if ($serialization === 'php') {
                $contents = serialize($contents);
            }
        }

        try {
            umask(0);
            FileSystemExternal::filePutContentsOrThrowExceptionOnFailure($path, $contents);
        }
        catch(\Exception $e){
            return false;
        }
        return true;
    }

    /**
     * Get the cpHistory file from disk
     * @param bool $mergeNewVersions Set true to merge in the 'current' versions. Note: mergeNewVersions is only used in non-hosted.
     * @param bool $includeWidgetInfo Set false to return widget history without additional widget information, such as categories.
     * @return array An array of all widget/framework versions and their associated dependencies.
     * @throws \Exception If path to a widget couldn't be parsed
     */
    static function getVersionHistory($mergeNewVersions = false, $includeWidgetInfo = true) {
        if(!$mergeNewVersions) {
            if(isset(self::$versionHistoryCache) && $includeWidgetInfo)
                return self::$versionHistoryCache;

            if(isset(self::$versionHistoryCacheWithoutExtras) && !$includeWidgetInfo)
                return self::$versionHistoryCacheWithoutExtras;
        }

        $versions = self::getVersionFile(self::getVersionHistoryPath()) ?: array();

        if($includeWidgetInfo) {
            foreach($versions['widgetInfo'] as $widgetPath => $widgetDetails) {
                if(isset($widgetDetails['category']))
                    $versions['widgetVersions'][$widgetPath] = array('category' => $widgetDetails['category']) + $versions['widgetVersions'][$widgetPath];
            }
        }

        if(!IS_HOSTED && $mergeNewVersions) {
            //Load all the standard versions from the info.yml files.
            foreach(FileSystemExternal::listDirectory(CORE_WIDGET_FILES, false, true, array('equals', 'info.yml')) as $manifestPath) {
                $widgetKey = dirname($manifestPath);

                if(!is_array($meta = \RightNow\Utils\Widgets::getWidgetInfoFromManifest(CORE_WIDGET_FILES . $widgetKey, $widgetKey)))
                    throw new \Exception("The widget $relativePath could not be parsed. The following error occurred in the info.yml file: {$meta}");

                $infoToSave = array(
                    'requires' => array(
                        'framework' => $meta['requires']['framework']
                    )
                );

                if ($extends = $meta['extends'])
                    $infoToSave['extends'] = array('widget' => $extends['widget'], 'versions' => $extends['versions']);

                if ($contains = $meta['contains']) {
                    foreach($contains as &$resultsInfo) {
                        if (array_key_exists('description', $resultsInfo)) {
                            unset($resultsInfo['description']);
                        }
                    }
                    $infoToSave['contains'] = $contains;
                }

                $versions['widgetVersions'][$widgetKey][$meta['version']] = $infoToSave;

                if($includeWidgetInfo && $meta['info']['category'])
                    $versions['widgetVersions'][$widgetKey] = array('category' => $meta['info']['category']) + $versions['widgetVersions'][$widgetKey];

                uksort($versions['widgetVersions'][$widgetKey], "\RightNow\Internal\Utils\Version::compareVersionNumbers");
            }

            //Add the current CX version and its associated framework version
            $cxVersion = self::getCXVersionNumber();
            $versions['frameworkVersions'][$cxVersion] = \RightNow\Utils\Framework::getFrameworkVersion();
            uksort($versions['frameworkVersions'], "\RightNow\Internal\Utils\Version::compareVersionNumbers");
        }

        if(self::isTesting())
            $versions = DependencyInfo::overrideVersionHistory($versions);

        if($includeWidgetInfo)
            return self::$versionHistoryCache = $versions;

        return self::$versionHistoryCacheWithoutExtras = $versions;
    }

    /**
     * Write out an array of version history data to the cpHistory file
     * @param array $versionHistory An array of data containing a history of all
     * the widget and framework versions shipped in CP along with their dependencies.
     * The array is formatted as follows:
     * array(
     *      ['widgetVersions'] => array(
     *          ['standard/chat/ChatAgentStatus'] => array( //Relative widget path
     *              //Widget version => Framework Version Dependencies and an optional extends/contains widget dependency
     *              ['1.0.1'] => array (
     *                  'requires' => array(
     *                      'framework' => ['3.0'],
     *                      'extends' => array('widget' => 'a/relative/Path', 'versions' => ['3.0'])
     *                      'contains' => array(
     *                          array('widget' => 'a/relative/Path', 'versions' => ['1.0']),
     *                       )
     *                  ),
     *              )
     *              ...)
     *          ...)
     *      ['frameworkVersions'] => array(
     *          ['12.5'] => ['3.0.1'] //CX Version => Framework Version released with that version
     *          ...)
     *      ...)
     * )
     */
    static function writeVersionHistory(array $versionHistory) {
        return self::writeVersionFile(self::getVersionHistoryPath(), $versionHistory);
    }

    /**
     * Writes the specified version string out to the frameworkVersion file.
     * Verifies that the specified version number is actually available.
     * @param string $version The version to write
     * @param string $oldVersion The previous framework version
     * @return bool True if the operation succeeds, false otherwise
     */
    static function updateDeclaredFrameworkVersion($version, $oldVersion = null) {
        if (self::isTesting()) {
            return DependencyInfo::setCurrentFrameworkVersion($version);
        }

        if (self::writeVersionFile(CUSTOMER_FILES . 'frameworkVersion', $version)) {
            require_once CPCORE . 'Internal/Utils/VersionTracking.php';
            VersionTracking::log(array('name' => 'framework', 'from' => $oldVersion ?: CP_FRAMEWORK_VERSION, 'to' => $version));
            return true;
        }

        return false;
    }

    /**
     * Returns the declared version info for widgets and the framework
     * in each site environment.
     * @param string|null $type The value 'framework' or 'widgets' or phpversion; defaults to all if unspecified
     * @param string|null $mode String|Null The value 'development', 'production', or 'staging'; defaults to all if unspecified
     * @return array|string Dependent on the specified parameters:
     *  - if both parameters omitted:
     *  array:
     *    'framework': ['Development': '3.0', 'Staging': '3.0', 'Production': '3.0'],
     *    'widgets': ['Development' / 'Staging' / 'Production': array keyed by widget names whose values are version numbers];
     *              uninstalled widgets are excluded
     *  - if `$type` is specified:
     *  array:
     *    ['Development' / 'Staging' / 'Production': values (strings if `framework` or `phpVersion`, arrays if `widgets`)]
     *  - if `$mode` is specified:
     *  array:
     *    'framework': string version in `$mode`
     *    'phpVersion': string version in `$mode`
     *    'widgets': array widget versions in `$mode`
     *  - if both `$type` and `$mode` are specified:
     *  string: version if `$type` is `framework`
     *  array: widget versions if `$type` is `widgets`
     */
    static function getVersionsInEnvironments($type = null, $mode = null) {
        static $results = array('widgets' => array(), 'framework' => array());
        static $versionFiles;

        if (!isset($versionFiles)) {
            $versionFiles = array(
                'Staging'       => OPTIMIZED_FILES . 'staging/staging_01/optimized/',
                'Production'    => OPTIMIZED_FILES . 'production/optimized/',
                'Development'   => CUSTOMER_FILES,
            );
        }

        $types = array('widgets' => 'widgetVersions', 'framework' => 'frameworkVersion', 'phpversion' => 'phpVersion');
        $files = $versionFiles;
        $singleMode = false;

        if ($type) {
            $type = strtolower($type);
            $types = array($type => $types[$type]);
            $singleType = true;
        }
        if ($mode) {
            $mode = ucfirst(strtolower($mode));
            $files = array($mode => $files[$mode]);
            $singleMode = true;
        }

        foreach ($files as $mode => $location) {
            foreach ($types as $type => $fileName) {
                $file = $location . $fileName;
                if (!isset($results[$type][$mode])) {
                    $results[$type][$mode] = self::getVersionFile($file);
                }
            }
        }

        if (self::isTesting()) {
            if ($results['widgets']) {
                $results['widgets'] = DependencyInfo::overrideAllDeclaredWidgetVersions($results['widgets']);
            }
            if ($results['framework']) {
                $results['framework'] = DependencyInfo::overrideAllDeclaredFrameworkVersions($results['framework']);
            }
        }

        if (isset($singleMode) && $singleMode && isset($singleType) && $singleType) {
            $declaredVersions = $results[$type][$mode];
        }
        else if (isset($singleType) && $singleType) {
            $declaredVersions = $results[$type];
        }
        else if ($singleMode) {
            $declaredVersions = array();
            foreach (array_keys($results) as $type) {
                $declaredVersions[$type] = $results[$type][$mode];
            }
        }
        else {
            // All environments + types are requested
            return $results;
        }

        return $declaredVersions;
    }

    static function getCXVersionNumber() {
        return implode('.', array_slice(explode('.', MOD_BUILD_VER), 0, 2));
    }

    /**
     * Converts product marketing name to the internal version number.
     *
     * @param string $versionName Name of version (e.g. 'February 10')
     * @return string|null Version number (e.g. '10.2') or null if not a recognized marketing name.
     */
    static function versionNameToNumber($versionName) {
        $elements = explode(' ', $versionName, 2);
        $monthName = ucfirst(strtolower($elements[0]));
        $month = array_search($monthName, self::getMonths());
        $year = ltrim(preg_replace('/\D/', '', $elements[1]), '0');

        if (strlen($year) === 4)
            $year = ($year[2] === '0') ? $year[3] : $year[2].$year[3];
        else if (!in_array(strlen($year), array(1, 2)))
            $year = null;

        if ($month && $year)
            return "$year.$month";

        return null;
    }

    /**
     * Converts internal version number to product marketing name.
     *
     * @param string $version Numerical version (e.g. '10.8)
     * @return string|null Marketing name (e.g. 'August 2010') or null if not a recognized version.
     */
    static function versionNumberToName($version) {
        if (self::isValidVersionNumber($version)) {
            $elements = explode('.', $version);
            $months = self::getMonths();
            if (count($elements) > 1 &&
                in_array($elements[0], range(0, 99)) &&
                in_array($elements[1], range(1, 12)))
            {
                return sprintf("%s 20%'02s", $months[$elements[1]], $elements[0]);
            }
        }
        return null;
    }


    /**
     * Given a version number (e.g. '11.2', '11.2.0.1', '11.2.0.1-b32h-mysql') return 1 if $version is considered valid.
     * @param string $version Version number to check
     * @return bool Return True if $version is valid
     */
    static function isValidVersionNumber($version) {
        return preg_match('/^\d{1,2}\.\d{1,2}(\.\d+\.\d+|\.\d+\.\d+-b\d+h-mysql)?$/', $version) === 1;
    }

    /**
     * Return an array of (<version number>, <version name>).
     * Nulls will be returned if invalid version.
     * @param string $version Version number (E.g. 10.2) or version name (E.g. February 10).
     * @return array In the form [<version number>, <version name>]
     */
    static function getVersionNumberAndName($version) {
        if (self::isValidVersionNumber($version)) {
            $name = self::versionNumberToName($version);
            $number = self::versionNameToNumber($name);
        }
        else {
            $number = self::versionNameToNumber($version);
            $name = self::versionNumberToName($number);
        }

        if (($number != null) && ($name != null))
            return array($number, $name);

        return array(null, null);
    }

    /**
     * Return version number (E.g. 10.2) from version name or number.
     * @param string $version Version number (E.g. 10.2) or version name (E.g. February 10).
     * @return string Version number
     */
    static function getVersionNumber($version) {
        $elements = self::getVersionNumberAndName($version);
        return $elements[0];
    }

    /**
     * Return version name (E.g. February 10) from version name or number.
     * @param string $version Version number (E.g. 10.2) or version name (E.g. February 10).
     * @return string Version name
     */
    static function getVersionName($version) {
        $elements = self::getVersionNumberAndName($version);
        return $elements[1];
    }

    /**
     * Return an array of integers of specified $arrayLength for the specified $version.
     * Used for version comparison (E.G 10.11 > 10.9).
     * @param string $version Examples: 10.2 or 10.2.0.1 or 10.2.0.1-b123h-mysql
     * @param int $arrayLength Length of array to return
     * @return array Array in the format (10, 2, 0, 1, 123)
     */
    static function versionToDigits($version, $arrayLength = 5) {
        $version = preg_replace('/\D/', ' ', "$version");
        $elements = array();
        foreach (explode(' ', $version) as $element) {
            if ($element || $element === '0') {
                array_push($elements, intval(trim($element)));
            }
        }
        if (count($elements) > $arrayLength) {
            $elements = array_slice($elements, 0, $arrayLength);
        }
        else if (count($elements) < $arrayLength) {
            $elements = array_pad($elements, $arrayLength, 0);
        }
        return $elements;
    }

    /**
     * Returns a version range given the min and max range values
     * @param mixed $minVersion Version number (E.g. 10.2), version name (February 10) or Version instance.
     * @param mixed $maxVersion Version number (E.g. 10.2), version name (February 10) or Version instance.
     * @return array In the form array('8.2', '8.5', '8.8', '8.11', '9.2', '9.5', '9.8', '9.11', '10.2');
     */
    static function getVersionRange($minVersion = null, $maxVersion = null) {
        require_once CPCORE . 'Internal/Libraries/Version.php';
        if ($minVersion === null)
            $minVersion = '8.2';
        if ($maxVersion === null)
            $maxVersion = MOD_BUILD_VER;
        $minVersion = \RightNow\Internal\Libraries\Version::toVersion($minVersion);
        $maxVersion = \RightNow\Internal\Libraries\Version::toVersion($maxVersion);
        $years = range($minVersion->year, $maxVersion->year);
        $months = array(2, 5, 8, 11);
        $versions = array();
        foreach ($years as $year) {
            foreach ($months as $month) {
                $version = new \RightNow\Internal\Libraries\Version("$year.$month");

                if ($version->lessThan($minVersion))
                    continue;

                if ($version->greaterThan($maxVersion))
                    break;

                array_push($versions, $version->version);
            }
        }
        return $versions;
    }

    /**
     * Return a future or past version number relative to $version.
     * @param mixed $version Version number (E.g. 10.2), version name (February 10) or Version instance.
     * @param int $offset A positive offset will return a version number in the future, a negative offset will return a version number in the past.
     * @return string Version number.
     */
    static function getAdjacentVersionNumber($version, $offset=1) {
        require_once CPCORE . 'Internal/Libraries/Version.php';
        $version = \RightNow\Internal\Libraries\Version::toVersion($version);
        $future = ($offset < 0) ? false : true;
        $year = $version->year;
        $month = $version->month;
        for ($i = abs($offset); $i > 0; $i--) {
            switch ($month) {
                case 2:
                    $month = $future ? 5 : 11;
                    if (!$future)
                        $year--;
                    break;
                case 5:
                    $month = $future ? 8 : 2;
                    break;
                case 8:
                    $month = $future ? 11 : 5;
                    break;
                case 11:
                    $month = $future ? 2 : 8;
                    if ($future)
                        $year++;
                    break;
            }
        }
        return "$year.$month";
    }

    /**
     * Writes the specified version string out to the phpVersion file.
     * @param string $version The version to write
     * @param string $shortHandVersion Major.minor version to be storied in logs and DB
     * @param string $oldVersion The previous framework version
     * @return bool True if the operation succeeds, false otherwise
     */
    static function updateDeclaredPhpVersion($version, $shortHandVersion, $oldVersion = null) {
        if (self::isTesting()) {
            return true;
        }
        if (self::writeVersionFile(CUSTOMER_FILES . 'phpVersion', $version)) {
            preg_match('/^[0-9]{1,3}\.[0-9]{1,3}(\.[0-9]{1,3})?/', $shortHandVersion, $match);
            $versionLabel = (isset($match[0]) && !empty($match[0])) ? $match[0] : trim(str_ireplace('Deprecated', '', $shortHandVersion));
            require_once CPCORE . 'Internal/Utils/VersionTracking.php';
            VersionTracking::log(array('name' => 'phpVersion', 'from' => $oldVersion ?: CP_PHP_VERSION, 'to' => (string) $versionLabel));
            return true;
        }
        return false;
    }

    /**
     * Returns the type of serializer to use for the given file.
     * @param string $path Path to the file
     * @return string Either empty string (for frameworkVersion or some other file)
     * or yaml or php (for serialized php object)
     */
    private static function getSerializationStrategy($path) {
        $file = basename($path);

        if ($file === 'cpHistory') {
            return IS_HOSTED ? 'php' : 'yaml';
        }
        if ($file === 'widgetVersions') {
            return (TextExternal::stringContains($path, CUSTOMER_FILES) || TextExternal::stringContains($path, CPCORESRC)) ? 'yaml' : 'php';
        }

        return '';
    }

    /**
     * Indicates whether the given version is a valid major.minor CP version
     * @param string $version Major.minor CP version (e.g. 3.8)
     * @return bool True if the version is valid for the current CP installation, false otherwise
     */
    static function isValidFrameworkVersion($version) {
        // get a list of framework versions (this will contain all major.minor.nano versions even if not the most current)
        $allFrameworkVersions = self::getVersionHistory(false, false)['frameworkVersions']; 

        // convert framework versions to simply major.minor (duplicates allowed)
        $frameworkVersions = array_map(function($v) { return preg_replace('/^(\d+\.\d+).*$/', '$1', $v); }, $allFrameworkVersions);

        // check for $version, using strict type checking so it will gracefully fail if $version is not a string
        return in_array($version, $frameworkVersions, true);
    }

    private static function isTesting() {
        static $returnVal;

        if (isset($returnVal)) return $returnVal;

        if(!IS_HOSTED && !IS_TARBALL_DEPLOY) {
            require_once CPCORE . 'Internal/Libraries/Widget/DependencyInfo.php';
            return ($returnVal = DependencyInfo::isTesting());
        }

        return ($returnVal = false);
    }
}
