<?php

namespace RightNow\Internal\Libraries\Widget;

use RightNow\Utils\Widgets,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Utils\Version;

/**
 * A collection of static methods for retrieving and caching info for all widgets.
 * The intent is to only have to hit the file system on the initial call, with
 * all subsequent calls accessing the cache.
 */
final class Registry{
    /**
     * List of widgets, key : '<standard|custom>/<type>/<widget_name>'
     */
    private static $widgets = array();

    /**
     * List of widget objects, key : '<absolute path>'
     */
    private static $widgetObjects = array();
    private static $widgetTypes = null;
    private static $deprecationBaseline = null;
    private static $lastBaselineVersion = null;
    private static $targetPages = 'development';
    private static $sourceBasePath = APPPATH;
    private static $lookup = null;
    private static $hasInitialized = false;

    public static function getAllWidgets($forceRefresh = false) {
        if (empty(self::$widgets) || $forceRefresh === true) {
            self::$widgets = array(); //Array of 'type', 'absolutePath', 'relativePath'
            self::$widgetObjects = array(); // Array of PathInfo objects

            foreach(self::getWidgetTypes($forceRefresh) as $widgetType => $basePath) {
                foreach(self::getWidgetPaths($widgetType, $forceRefresh) as $fullPath => $category) {
                    //Generate a widgetKey for the widget, <type>/<paths>/<name> with versions stripped
                    $widgetKey = Text::getSubstringAfter($fullPath, $basePath);
                    $widgetKey = preg_replace("#/[0-9]+\.[0-9]+$#", "", $widgetKey);

                    self::$widgets[$widgetKey] = array(
                        'type' => $widgetType,
                        'absolutePath' => $fullPath,
                        'relativePath' => $widgetKey,
                        'category' => $category
                    );
                }
            }
        }

        return self::$widgets;
    }

    public static function initialize($forceRefresh = false) {
        self::$hasInitialized = true;
        self::getWidgetTypes($forceRefresh);
        self::getAllWidgets($forceRefresh);
        self::$widgetObjects = array();
        Widgets::setSourceBasePath(self::$sourceBasePath, $forceRefresh);
        self::$lookup = Widgets::getDeclaredWidgetVersions(self::$sourceBasePath);
    }

    public static function setSourceBasePath($sourceBasePath, $forceRefresh = false) {
        if ($forceRefresh || $sourceBasePath !== self::$sourceBasePath) {
            self::$sourceBasePath = $sourceBasePath;
            self::initialize(true);
        }
    }

    /**
     * Set self::$targetPages to specified mode and re-cache widgets.
     *
     * @param string $mode One of development, production, reference or staging_XX
     * @param string $sourceDirectory One of 'source' or 'temp_source'.
     *        Currently temp_source only used during staging where custom widgets are temporarily in temp_source.
     */
    public static function setTargetPages($mode = 'development', $sourceDirectory = 'source') {
        $sourceBasePath = self::validateTargetPageMode($mode, $sourceDirectory);
        self::$targetPages = $mode;
        self::setSourceBasePath($sourceBasePath, true);
    }

    public static function getStandardWidgets($addFullPath = false) {
        return self::getWidgetsByType('standard', $addFullPath);
    }

    public static function getCustomWidgets($addFullPath = false) {
        return self::getWidgetsByType('custom', $addFullPath);
    }

    /**
     * Returns the absolute path of the specified relative path.
     * @param string $widgetPath Relative path
     *  e.g.
     *    'standard/foo/Bar'
     *    'custom/foo/Bar'
     * @return string Absolute path to the widget directory
     * @throws \Exception If the absolute path can't be determined
     */
    public static function getAbsoluteWidgetPathInternal($widgetPath) {
        foreach(array('standard', 'custom') as $widgetType) {
            if (Text::beginsWith($widgetPath, "$widgetType/")) {
                $basePath = 'getBase' . ucfirst($widgetType) . 'WidgetPath';
                $basePath = self::$basePath();
                if (Text::endsWith($basePath, "$widgetType/")) {
                    $basePath = str_replace("$widgetType/", '', $basePath);
                }
                return $basePath . $widgetPath;
            }
        }
        throw new \Exception("Unknown widget path '$widgetPath'");
    }

    /**
     * Returns a PathInfo instance for the specified widget path.
     *     Note: This function only looks for widgets which are already activated
     * @param string $widgetPath Relative widget path e.g. 'standard/foo/bar'
     * @param string|null $version Version to retrieve the path info for (optional);
     *      defaults to the currently activated version
     * @return PathInfo|null PathInfo instance if the path and/or version
     *      are valid, null otherwise
     */
    public static function getWidgetPathInfo($widgetPath, $version = null) {
        $cache = self::$widgetObjects;
        $fetchFromCache = function($key) use ($version, $cache) {
            if (isset($cache[$key]) && ($object = $cache[$key]) && (!$version || substr($object->version, 0, strrpos($object->version, '.')) === $version)) {
                return $object;
            }
        };
        if ($widgetObject = $fetchFromCache($widgetPath)) {
            return $widgetObject;
        }

        //Resolve the widget path and verify it exists/is valid. If we're in an optimized mode, however, there
        //really isn't any need to do this since we can be sure that a) the path has already been resolved
        //and b) the widget actually exists. Otherwise, the deploy operation would have failed.
        if(IS_TARBALL_DEPLOY || !IS_OPTIMIZED || !self::containsPrefix($widgetPath)){
            $widgetKey = self::getWidgetKey($widgetPath);
            if($widgetKey === null) {
                //Widget not activated
                return self::$widgetObjects[$widgetPath] = null;
            }
        }
        else{
            $widgetKey = $widgetPath;
        }
        if ($widgetObject = $fetchFromCache($widgetKey)) {
            return $widgetObject;
        }

        $widgetType = current(explode("/", $widgetKey));
        try {
            $version = ($version)
                ? Widgets::getWidgetNanoVersion($widgetKey, $version)
                : Widgets::getWidgetVersionDirectory($widgetKey);
        }
        catch (\Exception $e) {
            \RightNow\Libraries\Widget\Base::widgetError($widgetPath, $e->getMessage());
            return self::$widgetObjects[$widgetPath] = self::$widgetObjects[$widgetKey] = null;
        }

        return self::$widgetObjects[$widgetPath] = self::$widgetObjects[$widgetKey] =
            new PathInfo(
                $widgetType,
                $widgetKey,
                self::getBasePath($widgetType) . $widgetKey,
                $version
            );
    }

    public static function getWidgetType($widgetPath) {
        $widgetKey = self::getWidgetKey($widgetPath);
        return self::$widgets[$widgetKey]['type'];
    }

    /**
     * Given a widgetPath determines if it is an activated widget
     * @param string $widgetPath Any widget path
     * @return bool
     */
    public static function isWidget($widgetPath) {
        return self::getWidgetKey($widgetPath) !== null;
    }

    /**
     * Given a widgetPath check if the widget exists on disk even if
     * it is deactivated.
     * @param string $widgetPath Any widget path
     * @return bool
     */
    public static function isWidgetOnDisk($widgetPath) {
        self::initialize();
        return self::getWidgetKey($widgetPath, self::$widgets) !== null;
    }

    /**
     * Returns the version number in a widget path.
     * @param string $widgetPath The relative or absolute path of widget
     * @return mixed String Major.Minor[.Nano] version string or false if not found
     */
    public static function getVersionInPath($widgetPath) {
        if (preg_match('@^.*/(\d+\.\d+(?:\.\d+)?){1}(/)?$@', $widgetPath, $matches) === 1) {
            return $matches[1];
        }
        return false;
    }

    public static function getDeprecationBaseline($baselineVersion = null) {
        if (self::$deprecationBaseline === null || ($baselineVersion !== self::$lastBaselineVersion)) {
            if ($baselineVersion === null)
                $baseline = Version::getVersionNumber(\RightNow\Utils\Config::getConfig(CP_DEPRECATION_BASELINE));
            else
                $baseline = Version::getVersionNumber($baselineVersion);

            self::$deprecationBaseline = ($baseline === null) ? Version::getVersionNumber(MOD_BUILD_VER) : $baseline;

            if ($baselineVersion !== self::$lastBaselineVersion) {
                self::$lastBaselineVersion = $baselineVersion;
                self::initialize(true);
            }
        }
        return self::$deprecationBaseline;
    }

    /**
     * Get base path for standard widgets.
     * @return /home/httpd/cgi-bin/<site>.cfg/scripts/cp/core/widgets/standard
     */
    public static function getBaseStandardWidgetPath() {
        return CORE_WIDGET_FILES;
    }

    /**
     * Get base path for custom widgets
     * @return /home/httpd/cgi-bin/<site>.cfg/scripts/cp/customer/development/widgets
     */
    public static function getBaseCustomWidgetPath() {
        $basePath = (self::$targetPages === 'reference') ? CUSTOMER_FILES : self::$sourceBasePath;
        return $basePath . 'widgets/';
    }

    public static function getWidgetTypes($forceRefresh = false) {
        if (self::$widgetTypes === null || $forceRefresh === true) {
            self::$widgetTypes = array(
              'custom' => self::getBaseCustomWidgetPath(),
              'standard' => self::getBaseStandardWidgetPath(),
            );
            if (self::$targetPages === 'reference') {
                // Do not try to load the custom widgets.
                // Instead, only try to realize the truth: there are no custom widgets.
                unset(self::$widgetTypes['custom']);
            }
        }
        return self::$widgetTypes;
    }

    /**
     * Return base path for specified $widgetType.
     *
     * @param string $widgetType Either standard|custom
     *
     * Examples:
     *     getBasePath('standard') -> /.../scripts/cp/core/widgets/
     *
     *     getBasePath('custom')   -> /.../scripts/cp/customer/development/widgets/
     */
    public static function getBasePath($widgetType) {
        $widgetTypes = self::getWidgetTypes();
        return $widgetTypes[$widgetType];
    }

    private static function validateTargetPageMode($mode, $sourceDirectory = 'source') {
        if (!in_array($sourceDirectory, array('source', 'temp_source'))) {
            throw new \Exception("Invalid sourceDirectory: '$sourceDirectory'");
        }

        $modes = array(
            'development' => CUSTOMER_FILES,
            'reference' => (IS_HOSTED) ? CPCORESRC : CUSTOMER_FILES,
            'production' => OPTIMIZED_FILES . "production/$sourceDirectory/",
        );

        if (array_key_exists($mode, $modes)) {
            return $modes[$mode];
        }

        require_once CPCORE . 'Internal/Libraries/Staging.php';
        if (\RightNow\Internal\Libraries\Staging::isValidStagingName($mode)) {
            return OPTIMIZED_FILES . "staging/{$mode}/$sourceDirectory/";
        }
        $validModes = array_merge(array_keys($modes), array(STAGING_PREFIX . 'XX'));
        throw new \Exception(sprintf("Invalid mode '$mode'. Must be one of '%s'", var_export($validModes, true)));
    }

    /**
     * Given a widgetPath attempt to normalize it and match it against a key.
     * @param string $widgetPath Poorly formatted widgetPath
     *     e.g.
     *       'sample/SampleWidget  ',
     *       'custom/sample/samplewidget',
     *       'absPath/sample/samplewidget'
     * @param array|null $lookupArray The array of widget keys we intend to search
     * @return string|null Properly formatted widgetKey - 'custom/sample/SampleWidget' or null
     */
    private static function getWidgetKey($widgetPath, $lookupArray = null) {
        if(!self::$hasInitialized) {
            self::initialize();
        }
        if(!$lookupArray) {
            $lookupArray = self::$lookup;
        }
        $widgetPath = trim($widgetPath ? $widgetPath : '');
        $normalized = Widgets::normalizeSlashesInWidgetPath($widgetPath, true);
        $widgetTypes = array_merge(array(null), array_keys(self::getWidgetTypes()));

        foreach (array(true, false) as $caseSensitive) {
            $attemptedPaths = array();
            foreach (array($widgetPath, $normalized) as $initPath) {
                foreach ($widgetTypes as $widgetType) {
                    $path = ($widgetType === null) ? $initPath : "$widgetType/$initPath";

                    //Strip off widget versions for the CP2 -> CP3 migration
                    $separatedPath = explode('/', $path);
                    if(strtolower(reset($separatedPath)) === 'standard' && intval(substr($path, -1)) > 0)
                        $path = substr($path, 0, -1);

                    if (!in_array($path, $attemptedPaths)) {
                        if ($widgetKey = self::getKey($path, $lookupArray, $caseSensitive)) {
                            return $widgetKey;
                        }
                        array_push($attemptedPaths, $path);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if the given path is contained in the given lookup array.
     * @param string $path The widget key we are looking for
     * @param array $lookupArray The array we intend to look in
     * @param bool $caseSensitive Whether or not to perform case sensitive lookup
     * @return string|null The located widget key or null
     */
    private static function getKey($path, array $lookupArray, $caseSensitive = true) {
        $widgetKey = null;
        if ($caseSensitive && array_key_exists($path, $lookupArray)) {
            $widgetKey = $path;
        }
        else if ($caseSensitive === false) {
            // perform slower case-insensitive search as first iteration found no matches.
            foreach (array_keys($lookupArray) as $key) {
                if (strtolower($path) === strtolower($key)) {
                    $widgetKey = $key;
                    break;
                }
            }
        }

        return $widgetKey;
    }

    private static function getWidgetsByType($widgetType, $addFullPath = false) {
        $widgets = array();
        foreach(self::getAllWidgets() as $key => $values) {
            if ($values['type'] === $widgetType) {
                if ($addFullPath) {
                    $key = self::getBasePath($widgetType) . $key;
                }
                $widgets[$key] = $values;
            }
        }
        return $widgets;
    }

    /**
     * Checks the cpHistory (standard) or the directory structure (custom)
     * to find ALL of the different widgets available to CP.
     * @param string $widgetType The widget type: standard|custom
     * @param bool $forceRefresh Return fresh, non-cached results
     * @return array Widget paths
     */
    private static function getWidgetPaths($widgetType, $forceRefresh = false) {
        static $cached;
        if (!isset($cached)) {
            $cached = array('standard' => array(), 'custom' => array());
        }
        if (!$forceRefresh && $cached[$widgetType]) {
            return $cached[$widgetType];
        }

        $supportsCurrentFramework = function($widgetVersions) {
            foreach ($widgetVersions as $version => $versionInfo) {
                if (isset($versionInfo['requires']['framework']) && is_array($versionInfo['requires']['framework']) && in_array(CP_FRAMEWORK_VERSION, $versionInfo['requires']['framework']))
                    return true;
            }
            return false;
        };

        $listing = array();
        $path = self::getBasePath($widgetType);
        if ($widgetType === 'standard') {
            $versionHistory = Version::getVersionHistory();
            foreach(array_keys($versionHistory['widgetVersions']) as $widgetKey) {
                $category = isset($versionHistory['widgetVersions'][$widgetKey]['category']) ? $versionHistory['widgetVersions'][$widgetKey]['category'] : array();
                if ($supportsCurrentFramework($versionHistory['widgetVersions'][$widgetKey]))
                    $listing["$path$widgetKey"] = $category;
            }
        }
        else {
            $customWidgets = FileSystem::listDirectoryRecursively($path);
            if (is_array($customWidgets)) {
                foreach($customWidgets as $pathToFile) {
                    if(($fullPath = Text::getSubstringBefore($pathToFile, '/info.yml')) && preg_match("#/[0-9]+\.[0-9]+$#", $fullPath)) {
                        $category = array();
                        $infoYmlContents = file_get_contents($pathToFile);
                        if($infoYmlContents && Text::stringContains($infoYmlContents, 'category')) {
                            $parsedYml = yaml_parse($infoYmlContents);
                            if(isset($parsedYml['info']) && $parsedYml['info'] && isset($parsedYml['info']['category']) && $parsedYml['info']['category'])
                                $category = $parsedYml['info']['category'];
                        }

                        $listing[preg_replace("#//#", "/", $fullPath)] = $category;
                    }
                }
            }
        }

        // remove duplicate keys
        $uniqueListing = array();
        foreach($listing as $listingPath => $listingCategories) {
            if(!array_key_exists($listingPath, $uniqueListing)) {
                $uniqueListing[$listingPath] = $listingCategories;
            }
        }

        return $cached[$widgetType] = $uniqueListing;
    }

    /**
     * Checks to see if given widgetPath is a resolved path
     * @param  string $widgetPath Widget path to check
     * @return bool   Whether $widgetPath is resolved or relative
     */
    private static function containsPrefix($widgetPath) {
        return (Text::beginsWithCaseInsensitive($widgetPath, 'standard/') || Text::beginsWithCaseInsensitive($widgetPath, 'custom/'));
    }
}
