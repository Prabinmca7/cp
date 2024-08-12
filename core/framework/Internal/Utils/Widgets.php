<?php

namespace RightNow\Internal\Utils;

use RightNow\Api,
    RightNow\Libraries\Widget\Base as BaseWidget,
    RightNow\Internal\Libraries\Widget\PathInfo,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\ClientLoader,
    RightNow\Internal\Libraries\Widget\DependencyInfo,
    RightNow\Utils\FileSystem as FileSystemExternal,
    RightNow\Utils\Framework as FrameworkExternal,
    RightNow\Utils\Tags as TagsExternal,
    RightNow\Utils\Config as ConfigExternal,
    RightNow\Utils\Text as TextExternal;

class Widgets{
    const DECLARED_WIDGET_VERSIONS_CACHE_KEY = 'declared-widget-versions';

    private static $allWidgets = array();
    private static $versionDirectories = array();
    private static $sourceBasePath = APPPATH;
    private static $verifyCache = array();
    private static $getManifestDataCache = array();
    private static $getWidgetInfoFromManifestCache = array();
    private static $declaredWidgetVersions = array();
    private static $containerID = 0;
    protected static $widgetAttributeStack = array();

    /**
     * List of rnContainer blocks for the current page
     * @internal
     */
    public static $rnContainers = array();

    public static function setSourceBasePath($sourceBasePath, $forceRefresh = false) {
        if($forceRefresh || $sourceBasePath !== self::$sourceBasePath){
            self::$sourceBasePath = $sourceBasePath;
            self::killCacheVariables();
        }
    }

    public static function killCacheVariables() {
        self::$allWidgets = array();
        self::$versionDirectories = array();
        self::$verifyCache = array();
        self::$getManifestDataCache = array();
        self::$getWidgetInfoFromManifestCache = array();
        self::$declaredWidgetVersions = array();
    }

    /**
     * Returns the list of widget versions (from the widgetVersions file) for the specified site mode.
     * @param string $pathPrefix Prefix of path to use for file. It not specified, the current mode file contents will be returned
     * @return array Keyed by standard widget paths, values are string "{major}.{minor}" versions
     */
    public static function getDeclaredWidgetVersions($pathPrefix = null){
        $cacheKey = self::DECLARED_WIDGET_VERSIONS_CACHE_KEY;
        if (($pathPrefix = $pathPrefix ?: self::$sourceBasePath) !== CUSTOMER_FILES) {
            $cacheKey .= "-$pathPrefix";
        }
        if (isset(self::$declaredWidgetVersions[$cacheKey]) && $widgetVersions = self::$declaredWidgetVersions[$cacheKey]) {
            return $widgetVersions;
        }

        $widgetVersions = Version::getVersionFile($pathPrefix . 'widgetVersions') ?: array();

        if(!IS_HOSTED && !IS_TARBALL_DEPLOY) {
            require_once CPCORE . "Internal/Libraries/Widget/DependencyInfo.php";
            $widgetVersions = DependencyInfo::overrideDeclaredWidgetVersions($widgetVersions);
        }
        return self::$declaredWidgetVersions[$cacheKey] = $widgetVersions;
    }

    /**
     * Write the list of widget versions for development mode to disk.
     * Versions must be of {major}.{minor} form (i.e. must not contain nano version numbers)
     * @param array $versions The versions to write out
     * @return bool True if the operation succeeded, false otherwise
     */
    public static function updateDeclaredWidgetVersions(array $versions){
        try{
            if(!IS_HOSTED && !IS_TARBALL_DEPLOY) {
                require_once CPCORE . "Internal/Libraries/Widget/DependencyInfo.php";
                if(DependencyInfo::isTesting())
                    return DependencyInfo::setCurrentWidgetVersions($versions);
            }

            Version::writeVersionFile(CUSTOMER_FILES . 'widgetVersions', $versions);
            self::$declaredWidgetVersions[self::DECLARED_WIDGET_VERSIONS_CACHE_KEY] = $versions;
            return true;
        }
        catch(\Exception $e){
            return false;
        }
    }

    /**
     * Updates all available widgets to the framework specified or the current framework version.
     * @param string|null $frameworkVersion Framework version
     * @param bool|null $saveChanges Whether to actually write out the changes
     * @return array|bool False if the operation failed or an array with all changes made;
     *  follows the following structure:
     *  keyed by widget relative paths whose values are arrays containing `oldVersion` and `newVersion`
     *  keys
     */
    public static function updateAllWidgetVersions($frameworkVersion = null, $saveChanges = true) {
        if ($updates = self::getAvailableWidgetsToUpdate($frameworkVersion)) {
            return self::modifyWidgetVersions($updates, $saveChanges);
        }
        return false;
    }

    /**
     * Updates the specified widget to the specified version.
     * @param string $widgetPath Relative widget path
     * @param string $version Version number or 'remove'
     * @param bool $saveChanges Whether to actually write out the changes
     * @return array|bool False if the operation failed or an array with all changes made;
     *  follows the following structure:
     *  keyed by widget relative paths whose values are arrays containing `oldVersion` and `newVersion`
     *  keys; if 'remove' was the desired action, the array won't contain a `newVersion` item
     */
    public static function updateWidgetVersion($widgetPath, $version, $saveChanges = true) {
        if ($version === 'remove')
            return self::modifyWidgetVersions(array($widgetPath => $version), $saveChanges);

        $checkRequires = function($widgetInfo) use($widgetPath, $version, $saveChanges) {
            if(!$widgetInfo || is_string($widgetInfo)){
                return false;
            }

            if (!isset($widgetInfo['requires']['framework']) || !is_array($widgetInfo['requires']['framework']) || in_array(CP_FRAMEWORK_VERSION, $widgetInfo['requires']['framework'])) {
                return Widgets::modifyWidgetVersions(array($widgetPath => $version), $saveChanges);
            }
            return false;
        };

        //Custom widgets need to be validated against their info.yml files
        if (TextExternal::beginsWith($widgetPath, 'custom/')) {
            $widget = new PathInfo('custom', $widgetPath, CUSTOMER_FILES . 'widgets/' . $widgetPath, $version);
            return $checkRequires($widget->meta);
        }

        //Standard widgets need to be checked against the cpHistory to ensure they are on a valid framework
        $versionHistory = Version::getVersionHistory();
        $widgetVersions = $versionHistory['widgetVersions'][$widgetPath];
        $finalVersion = "{$version}.0";

        if (!$widgetVersions)
            return false;

        foreach (array_keys($widgetVersions) as $fullVersion) {
            if (TextExternal::beginsWith($fullVersion, $version) && Version::compareVersionNumbers($fullVersion, $finalVersion) > 0) {
                $finalVersion = $fullVersion;
            }
        }

        return $checkRequires($widgetVersions[$finalVersion]);
    }

    /**
    * Returns a list of widgets with available updates.
    * Should be called without specifying parameters; parameters are optional
    * for testing purposes.
    * @param string|null $currentFramework Defaults to current version in development (major.minor)
    * @param array|null $versionHistory Defaults to results of Version#getVersionHistory
    * @param array|null $currentVersions Defaults to current versions in development
    * @return array Array keyed by relative widget paths whose values are the versions that
    * may be updated to within the current framework version
    * @private
    */
    private static function getAvailableWidgetsToUpdate($currentFramework = null, $versionHistory = null, $currentVersions = null) {
        $versionHistory || $versionHistory = Version::getVersionHistory();
        if (!$currentVersions || !$currentFramework) {
            $developmentVersions = Version::getVersionsInEnvironments(null, 'development');
            $currentVersions || $currentVersions = $developmentVersions['widgets'];
            $currentFramework || $currentFramework = $developmentVersions['framework'];
        }
        $toUpdate = array();

        // find standard widgets to update
        foreach ($versionHistory['widgetVersions'] as $widgetKey => $info) {
            if (!isset($currentVersions[$widgetKey]) || !$currentVersion = $currentVersions[$widgetKey]) continue; // Widget isn't activated

            uksort($info, "\RightNow\Internal\Utils\Version::compareVersionNumbers");
            foreach ($info as $version => $framework) {
                //Strip the nano versions for comparison, we don't want to report a nano version as needing an update
                $version = implode('.', array_slice(explode('.', $version), 0, 2));
                if($currentVersion !== 'current' && $currentVersion !== $version && isset($framework['requires']['framework']) && in_array($currentFramework, $framework['requires']['framework'])) {
                    $currentVersion = $version;
                    $toUpdate[$widgetKey] = $version;
                }
            }
        }
        // find custom widgets to update
        foreach ($currentVersions as $widgetKey => $currentVersion) {
            if (TextExternal::beginsWith($widgetKey, 'custom/') && Registry::isWidgetOnDisk($widgetKey)) {
                $widgetVersions = FileSystem::listDirectory(
                    CUSTOMER_FILES . 'widgets/' . $widgetKey, false, false,
                    array('match', '#^[0-9]+\.[0-9]+$#'));

                usort($widgetVersions, "\RightNow\Internal\Utils\Version::compareVersionNumbers");
                foreach ($widgetVersions as $version) {
                    $customVersionInfo = new PathInfo('custom', $widgetKey, CUSTOMER_FILES . 'widgets/' . $widgetKey, $version);
                    if(!is_array($customVersionInfo->meta)) {
                        continue;
                    }
                    $customFramework = isset($customVersionInfo->meta['requires']['framework']) ? $customVersionInfo->meta['requires']['framework'] : null;
                    if($currentVersion !== $version && (!$customFramework || in_array($currentFramework, $customFramework))) {
                        $currentVersion = $version;
                        $toUpdate[$widgetKey] = $version;
                    }
                }
            }
        }
        return $toUpdate;
    }

    /**
     * Saves all updates to widget versions.
     * @param array $changes All changes to be saved; must be keyed by widget relative paths
     *  whose values are string version numbers or 'remove' or 'current'
     * @param bool $saveChanges Whether to actually write out the changes
     * @return array|bool False if the operation failed or an array with all changes made;
     *  follows the following structure:
     *  keyed by widget relative paths whose values are arrays containing `oldVersion` and `newVersion`
     *  keys; if 'remove' was the desired action, the array won't contain a `newVersion` item
     */
    public static function modifyWidgetVersions(array $changes, $saveChanges = true) {
        $transactions = array();
        $existingWidgetVersions = self::getDeclaredWidgetVersions(CUSTOMER_FILES);

        foreach ($changes as $widgetKey => $change) {
            $transactions[$widgetKey] = array('previousVersion' => isset($existingWidgetVersions[$widgetKey]) ? $existingWidgetVersions[$widgetKey] : null);

            if ($change === 'remove') {
                unset($existingWidgetVersions[$widgetKey]);
                continue;
            }

            $existingWidgetVersions[$widgetKey] = $change;
            $transactions[$widgetKey]['newVersion'] = $change;
        }

        ksort($existingWidgetVersions);

        if ($saveChanges && self::updateDeclaredWidgetVersions($existingWidgetVersions)) {
            require_once CPCORE . 'Internal/Utils/VersionTracking.php';
            foreach($transactions as $key => $values) {
                VersionTracking::log(array('name' => $key, 'from' => $values['previousVersion'], 'to' => isset($values['newVersion']) ? $values['newVersion'] : null));
            }
            return $transactions;
        }
        else if (!$saveChanges) {
            return $transactions;
        }
        return false;
    }

    /**
     * Determines if the given widget has a name (e.g. TextInput) that matches another widget
     * @param string $widgetPath The widget to delete
     * @return bool True if another widget has the same name; false, otherwise
     */
    public static function hasNameConflict($widgetPath) {
        $widgetName = basename($widgetPath);
        $existingWidgets = array_keys(Registry::getAllWidgets());
        foreach ($existingWidgets as $existingWidgetPath) {
            if ($widgetPath === $existingWidgetPath)
                continue;

            $existingWidgetName = basename($existingWidgetPath);
            if ($existingWidgetName === $widgetName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Deletes the widget's entire folder and any presentation CSS files if no other widgets have the same name.
     * @param string $widgetPath The widget to delete
     * @return array Array with a `success` member indicating whether deletion was successful, possibly a `change` member
     * filled with information about deactivating the wdiget, and possibly a `files` member indicating what files were deleted
     */
    public static function deleteWidget($widgetPath) {
        $widgetPathParts = explode('/', $widgetPath);
        $widgetName = end($widgetPathParts);
        $absoluteWidgetPath = CUSTOMER_FILES . "widgets/$widgetPath";

        if ($widgetPathParts[0] !== 'custom')
            return array('success' => false);

        $changes = self::updateWidgetVersion($widgetPath, 'remove');
        if (!is_array($changes)) {
            return array('success' => false);
        }

        $deletedFiles = array();

        if (FileSystemExternal::isWritableDirectory($absoluteWidgetPath)) {
            FileSystemExternal::removeDirectory($absoluteWidgetPath, true);
            $deletedFiles[] = TextExternal::getSubstringAfter($absoluteWidgetPath, DOCROOT . '/cp') . '/*';
        }

        if (!self::hasNameConflict($widgetPath)) {
            $themes = FileSystemExternal::listDirectory(HTMLROOT . '/euf/assets/themes/', true, false, array('method', 'isDir'));
            if (is_array($themes)) {
                foreach ($themes as $theme) {
                    $widgetCssPath = "$theme/widgetCss/$widgetName.css";
                    if (FileSystemExternal::isReadableFile($widgetCssPath) && is_writable($widgetCssPath)) {
                        @unlink($widgetCssPath);
                        $deletedFiles[] = '/customer/' . TextExternal::getSubstringAfter($widgetCssPath, HTMLROOT . '/euf/');
                    }
                }
            }
        }

        return array('success' => true, 'change' => $changes, 'files' => $deletedFiles);
    }

    /**
     * Returns the version of the specified widget from
     * the widgetVersions file.
     * @param string $widgetPath The widget to look up
     * @param bool $majorMinorOnly Whether or not to limit result to major-minor
     * @return string|null String widget version or null if not found
     */
    public static function getCurrentWidgetVersion($widgetPath, $majorMinorOnly=true) {
        if (!self::$allWidgets) {
            self::$allWidgets = self::getDeclaredWidgetVersions();
        }

        $currentVersion = isset(self::$allWidgets[$widgetPath]) ? self::$allWidgets[$widgetPath] : null;
        if ($majorMinorOnly === true && ($versionParts = explode('.', $currentVersion ? $currentVersion : '')) && count($versionParts) === 3) {
            return implode('.', array_slice($versionParts, 0, 2));
        }
        return $currentVersion;
    }

    /**
     * Returns the full major-minor-nano version of the specified widget
     * according to what's listed in widgetVersions for the current site mode
     * and what's on the filesystem. Maintains an accumulated list of widgets
     * (added to as new widgets are requested) in memcache.
     * @param string $widgetPath The widget to look up
     * @return string The widget's full version directory (e.g. "1.1.2") or empty string if not found
     * @throws \Exception If $widgetPath is invalid
     */
    public static function getWidgetVersionDirectory($widgetPath) {
        static $cacheKey;
        $cacheKey || ($cacheKey = __CLASS__ . ':' . __FUNCTION__);

        if ($mainVersion = self::getCurrentWidgetVersion($widgetPath)) {
            // Because different site modes can have different widget versions,
            // there can be different main versions of the same widget being cached.
            // e.g. 'standard/foo/bar' => [10.9 => 10.9.1, 11.0 => 11.0.1]
            if (isset(self::$versionDirectories[$widgetPath]) && self::$versionDirectories[$widgetPath] && array_key_exists($mainVersion, self::$versionDirectories[$widgetPath])) {
                return self::$versionDirectories[$widgetPath][$mainVersion];
            }

            $cache = new \RightNow\Libraries\Cache\Memcache(600 /* 10 min. */);

            if ($cachedValues = $cache->get($cacheKey)) {
                // Populate static var with cached val.
                self::$versionDirectories = &$cachedValues;
                if (isset(self::$versionDirectories[$widgetPath]) && self::$versionDirectories[$widgetPath] && array_key_exists($mainVersion, self::$versionDirectories[$widgetPath])) {
                    return self::$versionDirectories[$widgetPath][$mainVersion];
                }
            }

            $fullVersion = self::getWidgetNanoVersion($widgetPath, $mainVersion);
            self::$versionDirectories[$widgetPath] = isset(self::$versionDirectories[$widgetPath]) ? self::$versionDirectories[$widgetPath] : array();
            self::$versionDirectories[$widgetPath][$mainVersion] = $fullVersion;
            try {
                // Store the entire (serialized) array of all widgets in memcache
                // for a single key rather than a different key per widget. Because...
                // * A `fetch` on a non-existent key is way slow with whatever the memcache API wrapper is doing.
                // * Doing it this way results in less overall fetches (though same number of sets for an empty cache).
                $cache->set($cacheKey, self::$versionDirectories);
            }
            catch (\Exception $e) {
                //No reason to show error for cache setting failure
            }

            return $fullVersion;
        }
        return '';
    }

    /**
     * Given a widgetKey and a {major}.{minor} version number, return the latest nano version from the
     * version history file.
     * @param string $widgetKey Formatted widget path (<standard/custom>/dir1/dir2/.../dirN)
     * @param string $majorMinor The {major}.{minor} version number or current
     * @return string Full {major}.{minor}.{nano} widget version
     * @throws \Exception If the widgetKey appears to be invalid
     */
    public static function getWidgetNanoVersion($widgetKey, $majorMinor)
    {
        if(!IS_HOSTED && !IS_TARBALL_DEPLOY && (strtolower($majorMinor) === 'current'))
            return ''; // indicates that widget's main directory is to be used

        // custom widgets only use major.minor versions
        if(TextExternal::beginsWith($widgetKey, 'custom/'))
            return $majorMinor;

        if(IS_TARBALL_DEPLOY)
            return self::getFullVersionFromManifest(Registry::getAbsoluteWidgetPathInternal($widgetKey));

        $versionHistory = Version::getVersionHistory();
        if(!$widgetVersions = $versionHistory['widgetVersions'][$widgetKey])
            throw new \Exception(sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_STARTING_VERSION_PCT_S_MSG), $widgetKey, $majorMinor));

        // use the major.minor.nano that was specified in widgetVersions
        if (($versionParts = explode('.', self::getCurrentWidgetVersion($widgetKey, false))) && count($versionParts) === 3) {
            return implode('.', $versionParts);
        }

        // otherwise, get the latest nano for the given major/minor version
        $maxNano = 0;
        foreach(array_keys($widgetVersions) as $fullVersion) {
            if($fullVersion){
                list($major, $minor, $nano) = array_pad(explode('.', $fullVersion), 3, null);
                if($majorMinor === "$major.$minor" && $nano > $maxNano)
                $maxNano = $nano;
            }
        }
        return "$majorMinor.$maxNano";
    }

    /**
     * Returns a widget version using the info.yml file.
     * Typically used in tarballDeploy.
     * @param string $absolutePath The absolute path to the widget info.yml file
     * @return string The {major}.{minor}.{nano} widget version
     * @throws \Exception If the absolutePath appears to be invalid or the function is called outside of tarball deploy or unit testing
     */
    public static function getFullVersionFromManifest($absolutePath)
    {
        if(!IS_TARBALL_DEPLOY && !IS_UNITTEST)
            throw new \Exception(ConfigExternal::getMessage(WIDGETS_GETFULLVERSIONFROMMANIFEST_MSG));
        $info = self::getManifestData($absolutePath);
        if(is_array($info))
            return $info['version'];
        throw new \Exception($info);
    }

    /**
     * Returns the widget's name, given its path.
     * @param string $widgetPath Absolute or relative path (as long as it's correct)
     * @throws \Exception If Widget path doesn't end in a slash or doesn't have at least two directories
     */
    public static function verifyWidgetPath($widgetPath) {
        $lastSlash = strrpos($widgetPath, '/');
        if ($lastSlash === strlen($widgetPath) - 1) {
            throw new \Exception("$widgetPath can't end with a slash");
        }
        if ($lastSlash === false) {
            throw new \Exception("$widgetPath must have at least two directory depths");
        }
    }

    /**
     * Returns the portion of the $widgetPath between the standard|custom/widgets/ directory and the widget version (if present).
     * @param string $widgetPath Absolute or relative path (as long as it's correct)
     * @return string|null Name of the widget e.g. 'standard/input/TextInput'
     */
    public static function getWidgetRelativePath($widgetPath) {
        if (!is_string($widgetPath)) return;

        if (preg_match("@^(" . dirname(CPCORE) . "/widgets/|" . CUSTOMER_FILES . "widgets/)?((?:(?!/\d+\.\d+).)+)(/\d+\.\d+(\.\d+)?)?$@", self::normalizeSlashesInWidgetPath($widgetPath, false), $matches)) {
            return $matches[2];
        }
    }

    /**
     * Returns the widget's class name (e.g. TextInput), given its path.
     * @param string $widgetPath Absolute or relative path (as long as it's correct)
     * @return string|null name of the widget
     */
    public static function getWidgetClassName($widgetPath) {
        self::verifyWidgetPath($widgetPath);
        if ($widgetName = self::getWidgetRelativePath($widgetPath)) {
            $elements = explode('/', $widgetName);
            return array_pop($elements);
        }
    }

    /**
     * Returns the widget's namespaced name, given its path.
     * @param string $widgetPath Relative path. Must start with 'standard' or 'custom'
     * @return string Namespaced name of the widget
     */
    public static function getWidgetNamespacedClassName($widgetPath) {
        self::verifyWidgetPath($widgetPath);
        if (preg_match('@/\d+\.\d+(\.\d+)?$@', $widgetPath) === 1) {
            $lastSlash = strrpos($widgetPath, '/');
            $widgetPath = substr($widgetPath, 0, $lastSlash);
        }
        if(Registry::getWidgetType($widgetPath) === 'standard') {
            $widgetPaths = explode("/", $widgetPath);
            return "\\RightNow\\Widgets\\" . end($widgetPaths);
        }
        return "\\Custom\\Widgets\\" . str_replace("/", "\\", TextExternal::getSubstringAfter($widgetPath, "custom/"));
    }

    /**
     * Returns the widget's namespaced name, given its path.
     * @param string $widgetPath Relative path. Must start with 'standard' or 'custom'
     * @return string Namespaced name of the widget
     */
    public static function getWidgetJSClassName($widgetPath) {
        return str_replace("\\", ".", substr(self::getWidgetNamespacedClassName($widgetPath), 1));
    }

    /**
     * Returns the widget's namespace, given its path.
     * @param string $widgetPath Relative path. Must start with 'standard' or 'custom'
     * @return string Namespace of the widget
     */
    public static function getWidgetNamespace($widgetPath) {
        $widgetPaths = explode("\\", self::getWidgetNamespacedClassName($widgetPath));
        array_pop($widgetPaths);
        return implode("\\", $widgetPaths);
    }

    /**
     * Adds provided attributes to the inherited attribute stack
     * @param array $attributes Attributes that should be added
     * @return array Full list of attributes
     */
    public static function addInheritedAttributes($attributes)
    {
        if (!count(self::$widgetAttributeStack))
            return $attributes;

        static $uninheritableAttributes = array('instance_id');

        // for a given attribute key, we only want to override it once, so that previous
        // stacks do not 'clobber' recent stacks
        $overriddenAttributes = array();

        // I want to iterate over the stack from the most recent to the oldest.  I
        // want a reverse foreach.  It doesn't exist.
        // I want to go that direction so that the a more recently set value beats
        // an older one.  Normally, I'd just iterate in the normal order and
        // overwrite the value, but I have the !isset check which stops that from
        // working.
        for ($i = count(self::$widgetAttributeStack) - 1; $i >= 0; --$i)
        {
            $stack = self::$widgetAttributeStack[$i];

            // 'sub' attributes are preferred over other attributes
            // 'sub' attributes with more levels are preferred over less
            // longer sub attributes are preferred over shorter
            uksort($stack, function($a, $b) {
                $aBeginsWithSub = TextExternal::beginsWith($a, 'sub:');
                $bBeginsWithSub = TextExternal::beginsWith($b, 'sub:');
                if ($aBeginsWithSub && !$bBeginsWithSub) {
                    return -1;
                }
                if (!$aBeginsWithSub && $bBeginsWithSub) {
                    return 1;
                }
                if ($aBeginsWithSub && $bBeginsWithSub) {
                    $aColonCount = count(explode(':', $a));
                    $bColonCount = count(explode(':', $b));
                    if ($aColonCount > $bColonCount)
                        return -1;
                    if ($aColonCount < $bColonCount)
                        return 1;
                    if (strlen($a) > strlen($b))
                        return -1;
                    if (strlen($a) < strlen($b))
                        return 1;
                }
                return 0;
            });
            foreach($stack as $key => $value)
            {
                $attrKeyToSet = null;
                $overrideAttribute = false;
                if (TextExternal::beginsWith($key, 'sub:'))
                {
                    list(, $idValue, $attrKey) = explode(':', $key, 3);

                    // if we don't have an id match, then we don't want to set the key
                    // since the SimpleHtmlDom parser lowercases attribute names during parsing,
                    // compare in a case-insensitive manner
                    if (strcasecmp($attributes['sub_id'], $idValue) !== 0)
                        continue;

                    // if we have nested sub-widgets, re-add 'sub:' to the attribute key
                    // and add back to the attributes for the sub-widgets to consume
                    if (TextExternal::stringContains($attrKey, ':'))
                        $attrKey = 'sub:' . $attrKey;

                    // use the new computed key
                    // force this attribute to override any set values, since the customer
                    // is obviously targeting this specifically, unless another, more-specific,
                    // sub-attribute has already been set
                    $attrKeyToSet = $attrKey;
                    $overrideAttribute = !isset($overriddenAttributes[$attrKey]);
                    $overriddenAttributes[$attrKey] = true;
                }
                else
                {
                    $attrKeyToSet = $key;
                }

                if ($attrKeyToSet !== null && !in_array($attrKeyToSet, $uninheritableAttributes, true) && ($overrideAttribute || !isset($attributes[$attrKeyToSet])))
                    $attributes[$attrKeyToSet] = $value;
            }
        }

        // If we don't have any containers, we're done
        if (!count(self::$rnContainers))
            return $attributes;

        // Add any attributes specified in the referenced container
        if (array_key_exists('rn_container_id', $attributes) && array_key_exists($attributes['rn_container_id'], self::$rnContainers))
        {
            $rnContainer = (is_array(self::$rnContainers[$attributes['rn_container_id']])) ? self::$rnContainers[$attributes['rn_container_id']] : json_decode(base64_decode(self::$rnContainers[$attributes['rn_container_id']]));
            foreach($rnContainer as $key => $value)
            {
                if (!array_key_exists($key, $attributes) && !in_array($key, $uninheritableAttributes, true))
                {
                    $attributes[$key] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * Creates a widget in development mode
     * @param PathInfo $widget The PathInfo object for the current widget.
     * @param bool $beParanoid Attempt to detect syntax errors in the controller before including it.  Validate the widget attributes.
     * constructor classes with the same name, should an error be reported or
     * should conflicts be magically resolved by renaming?
     * @param bool $attributes Attribute values to inherit from containing composite widgets.
     * @return string|object The requested widget instance or a string containing an error message.
     */
    public static function createWidgetInDevelopment(PathInfo $widget, $beParanoid, $attributes=false)
    {
        $attributes = $attributes ?: array();

        $meta = self::getWidgetInfo($widget, true);
        if (!is_array($meta)) {
            return BaseWidget::widgetError($widget, $meta);
        }

        // Get info on widget this widget relies on; require_once any of the controllers
        if (isset($meta['extends_info']))
        {
            if ($meta['extends_info']['logic'])
            {
                $meta['extends_js'] = $meta['extends_info']['logic'];
            }
            if ($meta['extends_info']['controller'])
            {
                $meta['extends_php'] = $meta['extends_info']['controller'];

                /*
                 if (!$meta['extends_info']['view'] && ($extendWidget = Registry::getWidgetPathInfo($meta['extends_info']['parent'])))
                {
                    // If the widget doesn't state that it wants to extend parent's view,
                    // then just use parent's view and ignore widget's own view
                    BaseWidget::widgetError($widget, ConfigExternal::getMessage(VIEW_FILE_FLDR_IGNORED_WIDGETS_INFO_CMD), false);
                    $widgetView = @file_get_contents($extendWidget->view);
                    unset($meta['view_path']);
                }
                */
            }
        }

        $widgetInstance = self::instantiateAndValidateWidgetController($meta, $widget, $beParanoid, $attributes);
        if (is_string($widgetInstance))
        {
            // error message
            return $widgetInstance;
        }
        $widgetInstance->setPath($widget->relativePath);
        $widgetInstance->setHelper();

        $jsName = null;
        if (array_key_exists('js_path', $meta))
        {
            $jsName = $widget->jsClassName;
        }

        $cachedWidgetInfoFromPageController = null;
        $CI = get_instance();
        if (property_exists($CI, 'widgetCallsOnPage')) {
            $cachedWidgetInfoFromPageController = $CI->widgetCallsOnPage;
        }
        if (is_null($cachedWidgetInfoFromPageController))
        {
            // No widgets were matched via the Page controller. But the page controller
            // isn't the only entrypoint for widget rendering. Retrieve the view.
            $widgetView = @file_get_contents($widget->view);
        }
        else if (is_array($cachedWidgetInfoFromPageController) && array_key_exists($widget->relativePath, $cachedWidgetInfoFromPageController))
        {
            $widgetView = $cachedWidgetInfoFromPageController[$widget->relativePath]['view'];
        }
        else
        {
            // The widget tag wasn't matched by Page's use of \RightNow\Internal\Libraries\Widget\Locator.
            // This can be caused by the caller making a manual call to #rnWidgetRenderCall

            return BaseWidget::widgetError($widget, ConfigExternal::getMessage(WIDGETS_PLACED_RN_BLOCKS_COLON_MSG));
        }
        $widgetView = WidgetViews::getExtendedWidgetPhpView($widgetView, $meta, $widget);
        $widgetInstance->setViewContent($widgetView);

        if(isset($meta['js_templates']) || isset($meta['extends_info']['js_templates']))
        {
            $meta['js_templates'] = WidgetViews::getExtendedWidgetJsViews($meta, $widget);
        }

        $widgetInstance->setInfo(array_merge($meta, array(
            'controller_name' => $widget->className,
            'js_name' => $jsName,
            'widget_name' => $widget->className,
            'widget_path' => $widget->relativePath,
        )));

        if(($areAttributesValid = $widgetInstance->setAttributes($attributes)) === true)
            return $widgetInstance;
        return $areAttributesValid;
    }

    /**
     * Gather statics for a widget
     * @param array $widgetInfo Contains 'meta' info about widget in terms of resources it needs
     * @param \RightNow\Internal\Libraries\ClientLoader $clientLoaderInstance An instance of RightNow\Internal\Libraries\ClientLoader
     * @return array Information about widget statics
     */
    public static function getWidgetStatics(array $widgetInfo, \RightNow\Internal\Libraries\ClientLoader $clientLoaderInstance) {
        $yuiModules = array();
        if (($requires = $widgetInfo['requires']) && isset($requires['yui'])) {
            $requires = $requires['yui'];
            if ($requires !== array_values($requires)) {
                // YUI requirements may be specified on a per-module basis.
                // e.g.
                // yui:
                // standard: [panel]
                // mobile:   [overlay]
                $module = $clientLoaderInstance->getJavaScriptModule();
                $requires = (isset($requires[$module]) && is_array($requires[$module]))
                    ? $requires[$module]
                    : array();
            }
            $yuiModules = $requires;
        }

        $statics = array();
        if (isset($widgetInfo['js_templates']) && $jsTemplates = $widgetInfo['js_templates']) {
            if (!IS_OPTIMIZED) {
                $clientLoaderInstance->parseJavaScript($jsTemplates);
            }
            $statics += array('templates' => json_encode($jsTemplates));
        }
        if ($yuiModules) {
            $statics += array('requires' => json_encode($yuiModules));
        }

        return $statics;
    }

    /**
     * Used in development mode only. Loads the controller file, parses the rn: attributes, instantiates the controller
     * class and ensures the validity of the extends relationship.
     * @param array $meta Widget meta data
     * @param PathInfo $widgetPathInfo Instance of widget PathInfo object
     * @param bool $beParanoid Whether to do additional validation checks on widget
     * @param array|null $attributes List of widget attributes
     * @return BaseWidget The widget instance
     * @throws \Exception If widget is invalid
     */
    private static function instantiateAndValidateWidgetController(array $meta, PathInfo $widgetPathInfo, $beParanoid, $attributes) {
        if(!array_key_exists('controller_path', $meta))
            return BaseWidget::widgetError($widgetPathInfo, ConfigExternal::getMessage(VIEW_PHP_FILES_META_INFO_CONT_CMD));

        if(!$controllerWidget = Registry::getWidgetPathInfo($meta['controller_path']))
            return BaseWidget::widgetError($widgetPathInfo, sprintf(ConfigExternal::getMessage(CONTROLLER_PATH_PCT_S_VALID_MSG), $meta['controller_path']));

        $controllerClass = $controllerWidget->namespacedClassName;

        if(!class_exists($controllerClass)) {
            if ($beParanoid && ($syntaxErrors = self::getSyntaxErrorsFor($controllerWidget->controller)))
                throw new \Exception($syntaxErrors);
            FrameworkExternal::installPathRestrictions();
            self::requireWidgetControllerWithPathInfo($widgetPathInfo);
        }

        if(!class_exists($controllerClass))
            return BaseWidget::widgetError($widgetPathInfo, $controllerClass . ' ' . ConfigExternal::getMessage(WIDGET_CTRLLER_CLASS_NAME_MSG));

        $meta = self::convertAttributeTagsToValues($meta, array('validate' => true, 'eval' => true, 'omit' => array('name', 'description')));
        if(is_string($meta))
            return BaseWidget::widgetError($widgetPathInfo, $meta);

        $widgetInstance = new $controllerClass(isset($meta['attributes']) && $meta['attributes'] ? $meta['attributes'] : array());

        if (!($widgetInstance instanceof BaseWidget))
            return BaseWidget::widgetError($widgetPathInfo, sprintf(ConfigExternal::getMessage(PCT_S_CLASS_CTRLLER_WIDGET_PCT_S_MSG), $controllerClass, $widgetPathInfo->relativePath));

        if ((isset($meta['extends']) && $extends = $meta['extends']) && ($errors = self::validateWidgetExtends($extends, $widgetPathInfo, $widgetInstance, $controllerClass))) {
            foreach ($errors as $errorArray) {
                list($errorMessage, $severe) = $errorArray;
                $error = BaseWidget::widgetError($widgetPathInfo, $errorMessage, $severe);
                if ($severe)
                    return $error;
            }
        }

        if ($beParanoid)
            self::validateWidgetAttributes($widgetInstance, "{$meta['controller_path']}/controller.php");

        $classMethods = get_class_methods($widgetInstance);
        if(!FrameworkExternal::inArrayCaseInsensitive($classMethods, $controllerClass) && !FrameworkExternal::inArrayCaseInsensitive($classMethods, '__construct'))
            return BaseWidget::widgetError($widgetPathInfo, ConfigExternal::getMessage(WIDGET_CONTROLLER_CONSTRUCTOR_FUNC_MSG));

            $extendsFrom = isset($meta['extends_php']) && $meta['extends_php'] ? $meta['extends_php'] : '';
        $contextData = self::getWidgetContextData($widgetInstance, $widgetPathInfo->relativePath, $extendsFrom, $attributes);

        // if $contextData is a string (from BaseWidget::widgetError), we know there was an error during the validation process
        if(is_string($contextData))
            return $contextData;
        $widgetInstance = self::addFormToken($widgetInstance, $widgetPathInfo);
        return self::setContextDataToWidgetInstance($widgetInstance, $contextData);
    }

    /**
     * Returns boolean to indicate whether or not a given widget has a parent view it should be extending from.
     * @param array $meta The meta data for a given widget
     * @return boolean True the parent view is being extended from, otherwise it's not.
     */
    private static function isViewAndLogicOverridden(array $meta)
    {
        return (isset($meta['extends']['overrideViewAndLogic']) && $meta['extends']['overrideViewAndLogic'] &&
            ($meta['extends']['overrideViewAndLogic'] === "true" ||
            $meta['extends']['overrideViewAndLogic'] === true));
    }

    private static function validateWidgetExtends(Array $extends, PathInfo $widget, $widgetInstance, $controllerClass) {
        $extendsPHP = array_key_exists('php', $extends['components']);
        $parentWidget = Registry::getWidgetPathInfo($extends['widget']);
        $errors = array();
        if ($extendsPHP && (!$parentWidget || !($widgetInstance instanceof $parentWidget->namespacedClassName))) {
            $errors[] = array(sprintf(ConfigExternal::getMessage(PCT_S_CLSS_CTRLLER_WIDGET_PCT_S_MSG), $controllerClass, $widget->relativePath, $extends['widget']), true);
            return $errors;
        }

        if (isset($extends['overrideViewAndLogic']) && $extends['overrideViewAndLogic']) {
            if (!FileSystemExternal::isReadableFile($widget->view) && FileSystemExternal::isReadableFile($parentWidget->view)) {
                $errors[] = array(sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_SPECIFIES_MSG), $widget->relativePath, 'view'), false);
            }
        }
        else {
            if (!array_key_exists('view', $extends['components']) && FileSystemExternal::isReadableFile($widget->absolutePath . '/view.php')) {
                $errors[] = array(sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_VIEW_FILE_INCLUDE_PCT_MSG), $widget->relativePath, "components"), false);
            }
            if (!array_key_exists('js', $extends['components']) && FileSystemExternal::isReadableFile($widget->absolutePath  . '/logic.js')) {
                $errors[] = array(sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_LOGIC_FILE_INCLUDE_PCT_MSG), $widget->relativePath, "components"), false);
            }
        }

        if (!$extendsPHP && FileSystemExternal::isReadableFile($widget->absolutePath . '/controller.php')) {
            $errors[] = array(sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_CONTROLLER_FILE_MSG), $widget->relativePath, "components"), false);
        }

        return $errors;
    }

    /**
     * Executes the given file to determine if it contains PHP errors
     * @param string $phpFilePath Path to file
     * @return bool|string Errors or false if no errors found
     * @throws \Exception If PHP binary could not be found
     */
    private static function getSyntaxErrorsFor($phpFilePath) {
        if (!FileSystemExternal::isReadableFile($phpFilePath)) {
            return false;
        }

        $executePhpAndGetOutput = function($arguments){
            $phpBinary = $_SERVER['SCRIPT_FILENAME'];
            if (IS_HOSTED) {
                $phpBinary = TextExternal::getSubstringStartingWith($phpBinary, '/cgi-bin/');
            }
            if (!$phpBinary || !is_executable($phpBinary)) {
                throw new \Exception('Could not find the PHP binary.');
            }
            $variablesWhichCausePhpToThinkApacheCalledIt = 'GATEWAY_INTERFACE PATH_TRANSLATED REQUEST_METHOD SCRIPT_FILENAME SERVER_NAME SERVER_SOFTWARE';
            $commandLine = "unset $variablesWhichCausePhpToThinkApacheCalledIt; $phpBinary $arguments";
            $output = array();
            exec($commandLine, $output, $exitCode);
            return $output;
        };

        $errors = $executePhpAndGetOutput("-q -l $phpFilePath 2>&1");
        $errors = array_map('strip_tags', $errors);
        $errors = array_filter($errors, function($errorMessage){return (TextExternal::stringContains($errorMessage, 'error:'));});
        return (count($errors) > 0) ? (implode("\n", $errors)) : (false);
    }

    /**
     * Adds the context data for a widget instance
     * @param BaseWidget $widgetInstance Widget instance for which to assign context data
     * @param array $contextData An array of context data for the widget, typically populated by $this->getWidgetContextData
     * @return BaseWidget Widget instance with added context data
     */
    private static function setContextDataToWidgetInstance(BaseWidget $widgetInstance, array $contextData) {
        $timestamp = time();
        $encodedContextData = base64_encode(json_encode($contextData));
        $contextDataHash = Api::ver_ske_encrypt_fast_urlsafe(sha1($encodedContextData . $timestamp));

        $widgetInstance->setInfo('contextData', $encodedContextData);
        $widgetInstance->setInfo('contextToken', $contextDataHash);
        $widgetInstance->setInfo('timestamp', $timestamp);

        return $widgetInstance;
    }

    /**
     * Gets a widget's context data
     * @param BaseWidget $widgetInstance A Widget object
     * @param string $widgetRelativePath The widget's relative path
     * @param string $extendsFrom Widget from which $widgetInstance is extended from
     * @param array $attributes An array of attributes for the widget's context data
     * @return null|string String error message if an error occurred
     */
    private static function getWidgetContextData($widgetInstance, $widgetRelativePath, $extendsFrom, array $attributes) {
        $contextData = array();
        if(count($ajaxHandlers = $widgetInstance->getAjaxHandlers())){
            $clickstreamActions = $loginRequirements = $tokenCheck = $isLoginRequired = array();

            if($extendsFrom)
                $contextData['extends'] = $extendsFrom;

            foreach($ajaxHandlers as $ajaxHandler){
                //QA 190723-000133 When on a hosted site in development mode only inspect widget ajax handlers
                //for customer created custom widgets. On non hosted sites in development mode check all
                //widget ajax handlers
                if(IS_HOSTED) {
                    if(IS_DEVELOPMENT && TextExternal::beginsWith($widgetRelativePath, 'custom')) {
                        if($error = self::inspectWidgetAjaxHandler($widgetInstance, $ajaxHandler, $widgetRelativePath)) {
                            return $error;
                        }
                    }
                }
                else {
                    if(IS_DEVELOPMENT) {
                        if($error = self::inspectWidgetAjaxHandler($widgetInstance, $ajaxHandler, $widgetRelativePath)) {
                            return $error;
                        }
                    }
                }
                self::accumulateWidgetContextData($widgetInstance, $ajaxHandler, $attributes, $contextData, $clickstreamActions, $loginRequirements, $tokenCheck, $isLoginRequired);
            }

            if ($contextData || $clickstreamActions || $loginRequirements || $tokenCheck || $isLoginRequired) {
                if ($clickstreamActions) {
                    $contextData['clickstream'] = $clickstreamActions;
                }
                if ($loginRequirements) {
                    $contextData['exempt_from_login_requirement'] = $loginRequirements;
                }
                if ($tokenCheck) {
                    $contextData['token_check'] = $tokenCheck;
                }
                if ($isLoginRequired) {
                    $contextData['login_required'] = $isLoginRequired;
                }
            }
        }
        return $contextData;
    }

    /**
     * Adds form token for a widget instance
     * @param BaseWidget $widgetInstance Widget instance for which to assign form token
     * @return BaseWidget Widget instance with added form token data
     */
    private static function addFormToken(BaseWidget $widgetInstance) {
        $formToken = FrameworkExternal::createTokenWithExpiration($widgetInstance->info['w_id'], false, false);
        $widgetInstance->setInfo('formToken', $formToken);
        return $widgetInstance;
    }

    /**
     * Validates a widget's ajaxHandler
     * @param BaseWidget $widgetInstance A Widget object
     * @param array|string $ajaxHandler The widget's ajax handler
     * @param string $widgetRelativePath The widget's relative path
     * @return null|string String error message if an error occurred
     */
    private static function inspectWidgetAjaxHandler(BaseWidget $widgetInstance, $ajaxHandler, $widgetRelativePath){
        $methodName = self::getMethodNameFromAjaxHandler($ajaxHandler);
        if($methodName === '')
            return BaseWidget::widgetError($widgetRelativePath, "Your ajax handler is improperly specified!");

        if(method_exists($widgetInstance, $methodName)){
            $reflection = new \ReflectionMethod($widgetInstance, $methodName);
            if($reflection->isStatic()){
                //the method is static: no need to persist anything across requests
                //grab the method's code to verify up-front that '$this' isn't being used
                $file = new \SplFileObject($reflection->getFileName());
                $file->seek($reflection->getStartLine() - 1);
                $code = '';
                while($file->key() < $reflection->getEndLine()){
                    $code .= $file->current();
                    $file->next();
                }
                if(TextExternal::stringContains($code, '$this')){
                    return BaseWidget::widgetError($widgetRelativePath, "The ajax handler method $methodName has an instance variable in it; the method must be static!");
                }
            }
        }
        else{
            return BaseWidget::widgetError($widgetRelativePath, "An ajax handler method you declared isn't a widget method ($methodName)!");
        }
    }

    /**
     * Returns the method name of an ajax handler
     * @param array|string $ajaxHandler An array or string which represents an ajax handler
     * @return string Method name of an ajax handler
     */
    private static function getMethodNameFromAjaxHandler($ajaxHandler) {
        if(is_string($ajaxHandler)){
            // Simply a method name
            return $ajaxHandler;
        }
        else if (is_array($ajaxHandler) && $ajaxHandler['method']){
            // Method name, clicksteam, login exemption, future needs...
            return $ajaxHandler['method'];
        }
        return '';
    }

    /**
     * Accumulates contextual data for a widget (including clickstream and login requirements)
     * @param BaseWidget $widgetInstance The widget for which to accumulate context data
     * @param array|string $ajaxHandler The ajax handler for which to accumulate context data
     * @param array|null $nonDefaultAttrValues An array of non-default attributes for the widget
     * @param array &$contextData An array of accumulated context data
     * @param array &$clickstreamActions An array of accumulated clickstream actions
     * @param array &$loginRequirements An array of accumulated login requirements
     * @param array &$tokenCheck An array of accumulated token check requirements
     * @param array &$isLoginRequired An array of accumulated login check requirements
     */
    private static function accumulateWidgetContextData(BaseWidget $widgetInstance, $ajaxHandler, $nonDefaultAttrValues, array &$contextData = array(), array &$clickstreamActions = array(), array &$loginRequirements = array(), array &$tokenCheck = array(), array &$isLoginRequired = array()) {
        $methodName = self::getMethodNameFromAjaxHandler($ajaxHandler);

        if (is_array($ajaxHandler) && $ajaxHandler['method']){
            if(isset($ajaxHandler['clickstream']) && $ajaxHandler['clickstream']){
                $clickstreamActions[$methodName] = $ajaxHandler['clickstream'];
            }
            if (isset($ajaxHandler['exempt_from_login_requirement']) && $ajaxHandler['exempt_from_login_requirement'] === true) {
                $loginRequirements[$methodName] = true;
            }
            // ajax requests which doesn't require token check are added to context data
            if (isset($ajaxHandler['token_check']) && $ajaxHandler['token_check'] === false) {
                $tokenCheck[$methodName] = false;
            }
            if (isset($ajaxHandler['login_required']) && $ajaxHandler['login_required'] === true) {
                $isLoginRequired[$methodName] = true;
            }
        }

        if(method_exists($widgetInstance, $methodName) && $nonDefaultAttrValues){
            $contextData['nonDefaultAttrValues'] = $nonDefaultAttrValues;
        }
    }

    /**
    * Returns the controller class name of the widget controller in the specified widget directory. Also conditionally
    * includes the widget and all of it's dependencies if needed.
    * @param string $widgetPath Widget path
    * @param array|null $loadDependencies List of parent widgets to load
    * @return string Class name of the widget
    */
    static function getWidgetController($widgetPath, $loadDependencies = array())
    {
        if ($loadDependencies)
        {
            foreach ($loadDependencies as $relativeControllerPath)
            {
                if(!$relativeControllerWidget = Registry::getWidgetPathInfo($relativeControllerPath))
                    continue;
                self::requireWidgetControllerWithPathInfo($relativeControllerWidget);
            }
        }

        if(!$widget = Registry::getWidgetPathInfo($widgetPath))
            return BaseWidget::widgetError($widgetPath, ConfigExternal::getMessage(CONTROLLER_PATH_IS_NOT_VALID_MSG));

        $controllerClass = $widget->namespacedClassName;
        if(class_exists($controllerClass)){
            return $controllerClass;
        }

        FrameworkExternal::installPathRestrictions();
        self::requireWidgetControllerWithPathInfo($widget);
        return (class_exists($controllerClass) ? $controllerClass :
            BaseWidget::widgetError($widget, $controllerClass . ' ' . ConfigExternal::getMessage(WIDGET_CONTROLLER_CLASS_NAME_MSG)));
    }

    /**
     * Retrieves info about the specified widget from its manifest file and files contained in the widget folder.
     * Will combine attribute values from the parent widgets as well. Note that widget attribute and URL parameter
     * values will not be expanded from their rn: values found in the info.yml file.
     * @param PathInfo $widget The PathInfo object for the current widget
     * @param bool $loadController Whether to load the widget's controller
     * @return string|array String error message or Array containing the widget's manifest data:
     *
     *  * controller_path: String relative path to folder containing controller.php (empty if not present)
     *  * view_path: String relative path to folder containing view.php (empty if not present)
     *  * js_path: String relative path to folder containing logic.js (key won't exist if not present)
     *  * js_templates: Array (key won't exist if not present) whose keys are template names and values are template contents
     *  * view_partials: Array (key won't exist if not present) whose values are file names of view partials
     *  * view_helper: String relative path helper file (key won't exist if not present)
     *  * version: String version number (major.minor.nano)
     *  * absolutePath: String abs. path up to and including the widget dir
     *  * relativePath: String relative path up to and including the widget dir
     *  * requires: Array
     *    * framework: Array containing major.minor versions
     *    * jsModule: Array containing names of js modules the widget is compatible with
     *  * attributes Array (if present)
     *  * info: Array (if present)
     *    * description: String
     *    * urlParameters: Array (if present)
     *  * extends: Array (if present)
     *    * widget: String relative path to widget
     *    * components: Array with keys {view,php,js,css} for each specified component
     *  * extends_info: Array (if present)
     *    * controller: Array containing relative paths to ancestor controller(s)
     *    * logic: Array containing relative paths to ancestor logic(s)
     *    * view: Array containing relative path to ancestor view
     *    * js_templates: Array whose keys are template names and values are template contents of ancestor template(s)
     *    * parent: String relative path to widget
     */
    public static function getWidgetInfo(PathInfo $widget, $loadController = false) {
        $widgetInfoFromManifest = $widget->meta;
        if(!is_array($widgetInfoFromManifest)) {
            return $widgetInfoFromManifest;
        }
        if(isset($widgetInfoFromManifest['extends']) && $widgetInfoFromManifest['extends']) {
            $widgetInfoFromManifest['extends_info'] = self::getWidgetToExtendFrom($widgetInfoFromManifest, $loadController);
        }
        $widgetInfo = self::getWidgetFiles($widget->absolutePath, $widget->relativePath, $widget->className) + $widgetInfoFromManifest;
        if(( isset($widgetInfo['extends']) && $extends = $widgetInfo['extends']) && ($parentWidget = Registry::getWidgetPathInfo($extends['widget']))) {
            if (!isset($extends['overrideViewAndLogic']) || !$extends['overrideViewAndLogic']) {
                if(!array_key_exists('view', $extends['components']) && FileSystemExternal::isReadableFile($widget->view)) {
                    unset($widgetInfo['view_path']);
                }
                if(!array_key_exists('js', $extends['components']) && FileSystemExternal::isReadableFile($widget->logic)) {
                    unset($widgetInfo['js_path']);
                }
            }
        }
        return $widgetInfo;
    }

    /**
     * Gets all of the widget's various files; defaults to Blank widget's files if controller or view are missing.
     * @param string $absolutePath The absolute filesystem path to the widget directory
     * @param string $relativePath A short path to the widget directory. e.g. standard/Foo/Bar
     * @param string $className The class name of the widget we are grabbing files for.
     * @return array Contains the following keys:
     *      -view_path
     *      -controller_path
     *      -js_path (optional)
     *      -js_templates (optional)
     *      -view_partials (optional)
     *      -view_helper (optional)
     * @throws \Exception if $absolutePath does not exist or $relativePath not specified.
     * @private
     */
    public static function getWidgetFiles($absolutePath, $relativePath, $className = null) {
        if (!is_string($relativePath) || strlen($relativePath) === 0) {
            throw new \Exception('Invalid relative path:' . $relativePath);
        }

        static $cached = array();
        if (isset($cached[$relativePath]) && $cached[$relativePath]) {
            return $cached[$relativePath];
        }

        if (!FileSystemExternal::isReadableDirectory($absolutePath)) {
            throw new \Exception('Invalid absolute path:' . $absolutePath);
        }

        // Controller
        // other functions will create a mock controller if the file doesn't exist
        $files = array('controller_path' => $relativePath);

        if (FileSystemExternal::isReadableFile($absolutePath . '/viewHelper.php')) {
            $files['view_helper'] = 'viewHelper.php';
        }

        // Registry::getWidgetPathInfo may return null if the user is in the process
        // of converting a widget
        if($widget = Registry::getWidgetPathInfo($relativePath)) {
            // View
            $files['view_path'] = FileSystemExternal::isReadableFile($widget->view) ? $relativePath : '';
            if ($partials = FileSystem::listDirectory($absolutePath, false, false, array('match', '/.*\.html\.php$/'))) {
                $files['view_partials'] = $partials;
            }
            // JS
            if(FileSystemExternal::isReadableFile($widget->logic))
                $files['js_path'] = $relativePath;
        }
        else {
            // View
            $files['view_path'] = FileSystemExternal::isReadableFile($absolutePath . '/view.php') ? $relativePath : '';
            // JS
            if(FileSystemExternal::isReadableFile($absolutePath . '/logic.js'))
                $files['js_path'] = $relativePath;
        }

        // CSS
        $cssFiles = self::getCssFiles($absolutePath, $relativePath, $className);
        if(count($cssFiles)) {
            $files = array_merge($files, $cssFiles);
        }

        // JS templates - widget w/o JS may still have JS template blocks defined
        if ($templateCollection = self::getJavaScriptTemplates($absolutePath)) {
            $files['js_templates'] = $templateCollection;
            $files['template_path'] = $relativePath;
        }

        $cached[$relativePath] = $files;

        return $files;
    }

    /**
     * Returns any JavaScript template files (*.ejs) in the widget's directory.
     * @param string $absoluteWidgetPath Absolute path to the widget directory
     * @return array Keyed by filename (without the file extension); values are minified template contents
     */
    public static function getJavaScriptTemplates($absoluteWidgetPath) {
        static $cacheResults = array();
        if (isset($cacheResults[$absoluteWidgetPath]) && $cacheResults[$absoluteWidgetPath]) {
            return $cacheResults[$absoluteWidgetPath];
        }

        $templateCollection = array();
        $widgetFiles = @scandir($absoluteWidgetPath);
        if (is_array($widgetFiles)) {
            foreach ($widgetFiles as $file) {
                if (TextExternal::endsWith($file, '.ejs')){
                    $fileName = str_replace('.', '_', TextExternal::getSubstringBefore($file, '.ejs'));
                    // Remove newlines and tabs; collapse multiple whitespaces into single space
                    $templateCollection[$fileName] = preg_replace('@\s{2,}@', ' ', preg_replace('@(\n|\t)@', '', file_get_contents("{$absoluteWidgetPath}/{$file}")));
                }
            }
        }

        $cacheResults[$absoluteWidgetPath] = $templateCollection;
        return $templateCollection;
    }

    /**
     * Returns css files for a given widget.
     * @param string $absolutePath Absolute path of the widget
     * @param string $relativePath Relative path of the widget
     * @param string $className Class name of the widget
     * @return array Array containing presentation and base css files
     */
    public static function getCssFiles($absolutePath, $relativePath, $className) {
        $files = array();
        // Presentation CSS
        $assetsPath = HTMLROOT . '/euf/assets/themes';
        if ($className) {
            $themes = FileSystemExternal::listDirectory($assetsPath);
            foreach ($themes as $theme) {
                if(FileSystemExternal::isReadableFile($assetsPath . "/{$theme}/widgetCss/{$className}.css")) {
                    $files['presentation_css'][] = "assets/themes/{$theme}/widgetCss/{$className}.css";
                }
            }
        }

        // Base CSS
        $baseCss = $absolutePath . '/base.css';
        if(FileSystemExternal::isReadableFile($baseCss)) {
            $files['base_css'][] = $relativePath . '/base.css';
        }

        return $files;
    }

    /**
     * Does a require_once on a widget controller given the widget path. The file will be checked that it
     * exists, but it will not check if the class defined in the controller is already defined on the page
     * @param PathInfo $widget The PathInfo object for the current widget
     */
    public static function requireWidgetControllerWithPathInfo(PathInfo $widget)
    {
        foreach(self::getParentControllers($widget) as $triple) {
            list($exists, $namespace, $code) = $triple;
            if (!class_exists($namespace)) {
                if ($exists) {
                    require_once $code;
                }
                else {
                    eval($code);
                }
            }
        }
    }


    /**
     * Returns an array containing controller info:
     *    - isReadableFile [bool]
     *    - namespacedClassName [string]
     *    - controller path or empty controller code [string]
     *
     * @param PathInfo $widget The PathInfo object for the current widget
     * @return array
     */
    private static function getControllerArray(PathInfo $widget) {
        if (FileSystemExternal::isReadableFile($widget->controller)) {
            return array(true, $widget->namespacedClassName, $widget->controller);
        }
        return array(false, $widget->namespacedClassName, self::getEmptyControllerCode($widget));
    }

    /**
     * Return an array of parent controller information starting with the original parent widget.
     *
     * @param PathInfo $widget The PathInfo object for the current widget.
     */
    public static function getParentControllers(PathInfo $widget) {
        $controllers = array(self::getControllerArray($widget));
        while ($parentWidget = self::getParentWidget($widget)) {
            array_unshift($controllers, self::getControllerArray($parentWidget));
            $widget = $parentWidget;
        }
        return $controllers;
    }

    /**
     * Given a PathInfo object return the parent PathInfo object, or null if no parent.
     *
     * @param PathInfo $widget The PathInfo object for the current widget.
     * @param bool $loadController Whether to load the widget's controller
     * @return object|null
     */
    public static function getParentWidget(PathInfo $widget, $loadController = true) {
        static $cache = array();
        $cacheKey = $widget->relativePath;
        if (!array_key_exists($cacheKey, $cache)) {
            if (is_array($meta = self::getWidgetInfo($widget, $loadController)) && isset( $meta['extends_info']['parent']) && $meta['extends_info']['parent']) {
                $cache[$cacheKey] = Registry::getWidgetPathInfo($meta['extends_info']['parent']);
            }
            else {
                $cache[$cacheKey] = null;
            }
        }
        return $cache[$cacheKey];
    }

    /**
     * Create an empty controller class for the specified widget. The controller class
     * will either extend from Widget or from the specified parent.
     * @param PathInfo $widget The PathInfo object for the current widget.
     * @param bool $loadController Whether to load the widget's controller
     * @return string Empty PHP controller code
     */
    public static function getEmptyControllerCode(PathInfo $widget, $loadController = true) {
        $parentWidget = self::getParentWidget($widget, $loadController);
        $parentWidgetNamespacedClassName = $parentWidget ? $parentWidget->namespacedClassName : '\RightNow\Libraries\Widget\Base';
        return "namespace $widget->namespace;\nclass $widget->className extends $parentWidgetNamespacedClassName { }";
    }

    /**
     * Looks for widget files to extend from; optionally loads the php controllers.
     * @param array $widgetManifest Info from the widget's manifest
     * @param bool $loadController Whether to load the widget's controller
     * @return mixed Array Contains info about the extended widget's pieces:
     *  -controller: array of controller(s) to be included ordered by ancestor to child
     *  -logic: array of logic file(s) to be included ordered by ancestor to child
     *  -view: array of view file(s) to be extended ordered by ancestor to child
     *  -js_templates: array of js view file(s) to be extended ordered by ancestor to child
     * OR Boolean false if the widget doesn't extend any widgets
     * @assert $widgetManifest has been validated by validateWidgetManifestInfo
     */
    public static function getWidgetToExtendFrom(array $widgetManifest, $loadController = false) {
        if (!$widgetManifest['extends']) {
            return false;
        }

        $extendInfo = array('controller' => array(), 'view' => array(), 'logic' => array(), 'js_templates' => array(), 'base_css' => array(), 'presentation_css' => array());

        $partsToExtend = $widgetManifest['extends']['components'];

        $parentWidget = $widgetManifest['extends']['widget'];
        $foundOverrideViewAndLogic = self::isViewAndLogicOverridden($widgetManifest);

        while ($widgetToExtend = Registry::getWidgetPathInfo($parentWidget)) {
            $extendInfo['parent'] = isset($extendInfo['parent'] ) && $extendInfo['parent'] ? $extendInfo['parent'] : $widgetToExtend->relativePath;

            if (FileSystemExternal::isReadableFile($widgetToExtend->controller)) {
                if ($loadController) {
                    // Load the controller to be extended from
                    self::requireWidgetControllerWithPathInfo($widgetToExtend);
                }
                $extendInfo['controller'][] = $widgetToExtend->relativePath;
            }

            if (!$foundOverrideViewAndLogic) {
                $foundOverrideViewAndLogic = self::isViewAndLogicOverridden($widgetToExtend->meta);

                foreach(array('view', 'logic') as $component) {
                    if (FileSystemExternal::isReadableFile($widgetToExtend->$component)) {
                        $extendInfo[$component][] = $widgetToExtend->relativePath;
                    }
                }
                if ($templateCollection = self::getJavaScriptTemplates($widgetToExtend->absolutePath)) {
                    // Grab all JS templates
                    $extendInfo['js_templates'][] = $templateCollection;
                }
            }

            if (isset($partsToExtend['css'])) {
                $cssFiles = self::getCssFiles($widgetToExtend->absolutePath, $widgetToExtend->relativePath, $widgetToExtend->className);

                if (isset($cssFiles['presentation_css']) && $cssFiles['presentation_css']) {
                    $extendInfo['presentation_css'] = array_merge($extendInfo['presentation_css'], $cssFiles['presentation_css']);
                }

                if (isset($cssFiles['base_css']) && $cssFiles['base_css']) {
                    $extendInfo['base_css'] = array_merge($extendInfo['base_css'], $cssFiles['base_css']);
                }
            }

            // get the next parent widget
            if (isset($widgetToExtend->meta['extends']) && is_array($widgetToExtend->meta['extends']) && $widgetToExtend->meta['extends']['widget']) {
                $parentWidget = $widgetToExtend->meta['extends']['widget'];
            }
            else {
                break;
            }
        }

        if (!$extendInfo || !($extendInfo['controller'] || $extendInfo['view'] || $extendInfo['logic'])) {
            return false;
        }
        return $extendInfo;
    }

    /**
     * Retrieves info about the specified widget from its manifest file without any processing.
     * @param string $absoluteWidgetPath The absolute file path to the widget directory
     * @param bool $forceRefresh Whether to ignore caching
     * @return string|array error message or widget's manifest data
     */
    private static function getManifestData($absoluteWidgetPath, $forceRefresh = false) {
        $absoluteWidgetPath = TextExternal::endsWith($absoluteWidgetPath, '/') ? substr($absoluteWidgetPath, 0, -1) : $absoluteWidgetPath;
        if (!$forceRefresh && isset(self::$getManifestDataCache[$absoluteWidgetPath]) && self::$getManifestDataCache[$absoluteWidgetPath]) {
            return self::$getManifestDataCache[$absoluteWidgetPath];
        }
        $manifest = "$absoluteWidgetPath/info.yml";
        $cpWidgetPath = TextExternal::getSubstringAfter($absoluteWidgetPath, '/scripts');
        if (FileSystemExternal::isReadableFile($manifest)) {
            // Replace tabs with two spaces
            $contents = str_replace("\t", "  ", @file_get_contents($manifest));
            $widgetInfo = @yaml_parse($contents);
            if(!is_array($widgetInfo)) {
                $widgetInfo = sprintf(ConfigExternal::getMessage(INFO_YML_FILE_PARSE_CORRECTLY_PCT_S_MSG), $cpWidgetPath);
            }
        }
        else {
            if(!IS_HOSTED && !IS_TARBALL_DEPLOY) {
                require_once CPCORE . "Internal/Libraries/Widget/DependencyInfo.php";
                if(DependencyInfo::isTesting()) {
                    //If we have a version number on the end, strip it off and attempt to load the version-less file
                    if(preg_match("#^[0-9]+\.[0-9]+(\.[0-9]+)?$#", end($pieces = explode('/', $absoluteWidgetPath)))) {
                        array_pop($pieces);
                        return self::getManifestData(implode('/', $pieces));
                    }
                }
            }

            $widgetInfo = sprintf(ConfigExternal::getMessage(INFO_YML_FILE_DOESNT_EX_PCT_S_MSG), $cpWidgetPath, "/ci/admin/versions/removeMissingActiveWidgets");
        }

        return (self::$getManifestDataCache[$absoluteWidgetPath] = $widgetInfo);
    }

    /**
     * Return widget info used to build a YAML manifest file based on provided $data.
     *
     * @param array $data An array of widget attributes to build the manifest.
     *        Relevant keys:
     *          version [string]
     *          frameworkVersion [string]
     *          attributes [array]
     *          info [array]
     *          extends [array]
     *          contains [array]
     *          requires [array]
     *
     * @param bool $returnAsYaml If true, return YAML string, else return an array.
     * @return array|string
     */
    public static function buildManifest(array $data, $returnAsYaml = false) {
        $manifest = array(
            'version' => isset($data['version']) ? $data['version'] : '1.0',
            'requires' => array(
                'jsModule' => $data['requires'] && $data['requires']['jsModule']
                    ? $data['requires']['jsModule']
                    : array(
                        ClientLoader::MODULE_STANDARD,
                        ClientLoader::MODULE_MOBILE,
                    ),
            ),
        );
        //Only add in dependency if specified
        if(isset($data['frameworkVersion']) && $data['frameworkVersion']){
            $manifest['requires']['framework'] = $data['frameworkVersion'];
        }

        if ($data['requires']) {
            $manifest['requires'] = $manifest['requires'] + $data['requires'];
        }

        if ($attributes = $data['attributes']) {
            $manifest['attributes'] = array();
            foreach ($attributes as $attribute => $values) {
                $manifest['attributes'][$attribute] = is_object($values) ? get_object_vars($values) : $values;
            }
        }

        foreach(array('info', 'extends', 'contains') as $parameter) {
            if (isset($data[$parameter]) && $data[$parameter]) {
                $manifest[$parameter] = $data[$parameter];
            }
        }

        // The substr call below strips the leading and trailing YAML separator characters.
        return ($returnAsYaml) ? substr(yaml_emit($manifest), 4, -4) : $manifest;
    }

    /**
     * Retrieves info about the specified widget from its manifest file. Will combine attribute values from the parent widgets as well. Note
     * that widget attribute and URL parameter values will not be expanded from their rn: values found in the info.yml file.
     * @param string $absoluteWidgetPath Fully qualified path to widget
     * @param string $widgetRelativePath Relative path to widget
     * @param array $examinedWidgets Array of already examined widget paths while trying to determine a particular widget's info
     * @param bool $forceRefresh Whether to ignore caching
     * @return mixed String error message or Array widget's manifest data
     */
    public static function getWidgetInfoFromManifest($absoluteWidgetPath, $widgetRelativePath, array $examinedWidgets = array(), $forceRefresh = false) {
        if (!$forceRefresh && isset(self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath]) && self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath]) {
            return self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath];
        }

        if(in_array($absoluteWidgetPath, $examinedWidgets))
            return (self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath] = sprintf(ConfigExternal::getMessage(APPEARS_DEPENDENCY_LOOP_EXAMINING_MSG), TextExternal::getSubstringAfter($absoluteWidgetPath, '/widgets/', $absoluteWidgetPath)));
        $examinedWidgets[] = $absoluteWidgetPath;

        //Load in the data array from the info.yml file
        $widgetInfo = self::getManifestData($absoluteWidgetPath, $forceRefresh);
        if(!is_array($widgetInfo)) {
            return (self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath] = $widgetInfo);
        }
        //If the widget extends another recursively call this function to load their info.yml and inherit the attributes
        if (isset($widgetInfo['extends']) && $widgetInfo['extends'] && ($extendsFrom = self::normalizeSlashesInWidgetPath($widgetInfo['extends']['widget'], true))) {
            $inheritedAttributes = array();
            if ($extendsFrom === $widgetRelativePath) {
                return (self::$getWidgetInfoFromManifestCache[$absolutePath] = ConfigExternal::getMessage(INFO_YML_FILE_REFS_INV_PARENT_MSG));
            }
            // Get all parent widget's attributes
            if(!$parentWidget = Registry::getWidgetPathInfo($extendsFrom)){
                return (self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath] = sprintf(ConfigExternal::getMessage(T_NTND_VL_NFYML_RF_NV_DCTVTD_RPT_CT_RPT_MSG), sprintf('<a href="/ci/admin/versions/manage#widget=%s">%s</a>', urlencode($extendsFrom), $extendsFrom)));
            }
            $parentWidgetInfo = self::getWidgetInfoFromManifest($parentWidget->absolutePath, $parentWidget->relativePath, $examinedWidgets, $forceRefresh);
            if(!is_array($parentWidgetInfo))
                return (self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath] = $parentWidgetInfo);
            if(is_array($parentWidgetInfo) && (isset($parentWidgetInfo['attributes']) && is_array($parentWidgetInfo['attributes']))){
                $inheritedAttributes = $parentWidgetInfo['attributes'];
            }
            $widgetInfo['attributes'] = self::mergeInheritedAttributes(isset($widgetInfo['attributes']) ? $widgetInfo['attributes'] : null, $inheritedAttributes);
        }

        $widgetInfo = self::validateWidgetManifestInfo($widgetInfo);

        if(!is_array($widgetInfo))
            return (self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath] = $widgetInfo);

        $widgetInfo['absolutePath'] = $absoluteWidgetPath;
        $widgetInfo['relativePath'] = $widgetRelativePath;

        if(!IS_HOSTED && !IS_TARBALL_DEPLOY) {
            require_once CPCORE . "Internal/Libraries/Widget/DependencyInfo.php";
            $widgetInfo = DependencyInfo::overrideWidgetInfo($widgetRelativePath, $widgetInfo);
        }
        return (self::$getWidgetInfoFromManifestCache[$absoluteWidgetPath] = $widgetInfo);
    }

    /**
     * Takes an array of attributes defined on a parent widget and merges it into the attribute list
     * of a child widget.
     * @param array|null $childAttributes Array of attributes defined on the child
     * @param array $parentAttributes Array of attributes defined on the parent
     * @return array Merged attribute list
     */
    private static function mergeInheritedAttributes($childAttributes, array $parentAttributes){
        $childAttributes = $childAttributes ?: array();
        foreach($parentAttributes as $name => $attribute){
            //If the parent attribute is unset, continue on and ignore it
            if($attribute === 'unset'){
                continue;
            }
            //If attribute is set on child but is the 'unset' keyword, remove it
            if (isset($childAttributes[$name] ) && $childAttributes[$name] === 'unset') {
                unset($childAttributes[$name]);
            }
            //If not defined on child (normal case) then add it to the child
            else if(!isset($childAttributes[$name]) || !$childAttributes[$name]){
                $childAttributes[$name] = $attribute;
                $childAttributes[$name]['inherited'] = true;
            }
        }
        return $childAttributes;
    }

    /**
     * Iterates over the attributes for a widget and converts them from the rn: tag to the actual value or the
     * calculation code depending on the $returnCalculationCode parameter.
     *
     * @param array $widgetInfo Widget info.yml data in array format
     * @param array $options Processing options; keys include:
     *          'validate': Boolean Whether to validate the attribute structure and values (defaults to false if unspecified)
     *          'eval':     Boolean Whether to eval the converted code from the special `rn:` values; if not evaled,
     *                      the literal code of the `rn:` conversion is returned (defaults to true if unspecified)
     *          'omit':     Array Attribute properties that should not be processed or set on the resulting Attribute object
     * @param bool $replaceConstants Whether or not to replace constant names with their value (defaults to true if unspecified)
     * @return array|string The $widgetInfo array with the 'attributes' member converted to an array of Attribute objects; String error message
     *          if an error is encountered
     */
    public static function convertAttributeTagsToValues(array $widgetInfo, array $options = array(), $replaceConstants = true){
        static $cache = array();
        $cacheKey = null;
        if(!isset($options['validate'])){
            $options['validate'] = false;
        }
        if($widgetInfo['absolutePath'])
            $cacheKey = $widgetInfo['absolutePath'];
        
        if (isset($options['omit']) && is_array($options['omit'])) {
            $propertiesToOmit = $options['omit'];
        }
        else {
            $propertiesToOmit = false;
        }
            
        if($cacheKey && isset($cache[$cacheKey])){
            $widgetInfo['attributes'] = $cache[$cacheKey];
            if(isset($widgetInfo['attributes']) && is_array($widgetInfo['attributes']))
            {
                foreach($widgetInfo['attributes'] as $key => &$value){
                    if ($propertiesToOmit) {
                        foreach ($propertiesToOmit as $property) {
                            if (is_object($value)) {
                                if(isset($value->$property)){
                                    unset($value->$property);
                                }                                
                            }
                            else {
                                return sprintf(\RightNow\Utils\Config::getMessage(ATTRIB_PCT_S_WIDGET_PCT_S_INVALID_MSG), $key, $widgetInfo['relativePath']);
                            }
                        }
                    }
                    $value = new \RightNow\Libraries\Widget\Attribute((array)$value);
                }
            }
            return $widgetInfo;
        }
        //Need to evaluate the attributes from the file
        if(isset($widgetInfo['attributes']) && is_array($widgetInfo['attributes']))
        {
            $evaledProperties = array('name', 'description', 'default', 'optlistId');
            $returnCalculationCode = $options['eval'] === false;

            foreach($widgetInfo['attributes'] as $key => &$value){
                foreach($evaledProperties as $property){
                    if(isset($value[$property])){
                        try{
                            $value[$property] = self::parseManifestRNField($value[$property], $returnCalculationCode, $replaceConstants);
                        }
                        catch(\Exception $e){
                            return $e->getMessage();
                        }
                    }
                }
                if(is_array($value) && (!isset($value['default']) || $value['default'] === null)){
                    switch(TextExternal::cStrToLower($value['type'])) {
                        case 'string':
                        case 'filepath':
                            $value['default'] = $returnCalculationCode ? "''" : '';
                            break;
                        case 'bool':
                        case 'boolean':
                            $value['default'] = $returnCalculationCode ? "false" : false;
                            break;
                        case 'menu':
                        case 'option':
                        case 'int':
                        case 'integer':
                            $value['default'] = $returnCalculationCode ? "null" : null;
                            break;
                        case 'multioption':
                            $value['default'] = $returnCalculationCode ? "array()" : array();
                            break;
                    }
                }                
                $value = new \RightNow\Libraries\Widget\Attribute($value);
            }

            if ($options['validate'] === true && ($validationError = self::validateWidgetAttributeStructure($widgetInfo, $propertiesToOmit ?: array()))) {
                return $validationError;
            }
        }

        if($cacheKey){
            $cache[$cacheKey] = isset($widgetInfo['attributes']) && $widgetInfo['attributes'] ? $widgetInfo['attributes'] : '';
        }
        return $widgetInfo;
    }

    /**
     * Converts URL parameter data from the info.yml file to literal values.
     * @param array $widgetInfo Widget info.yml data in array format
     */
    public static function convertUrlParameterTagsToValues(array $widgetInfo){
        if(isset($widgetInfo['info']['urlParameters']) && is_array($widgetInfo['info']['urlParameters']))
        {
            $evaledParameters = array('name', 'description', 'example');
            try{
                foreach($widgetInfo['info']['urlParameters'] as $name => $object){
                    foreach($evaledParameters as $parameter){
                        if(isset($object[$parameter])){
                            $widgetInfo['info']['urlParameters'][$name][$parameter] = self::parseManifestRNField($object[$parameter], false);
                        }
                    }
                }
            }
            catch(\Exception $e){
                return $e->getMessage();
            }
        }
        return $widgetInfo;
    }

    /**
     * Parses a field from the manifest file that uses the rn:<astr, php, msg, cfg>: syntax
     * @param string $code The value to be parsed
     * @param bool $returnCalculationCode Determines if literal value should be returned or if return value should be the PHP code to calculate the value
     * @param bool $replaceConstants Whether or not to replace constant names with their value (defaults to true if unspecified)
     * @return The parsed result
     * @throws \Exception If a rn:php value generates an exception
     */
    public static function parseManifestRNField($code, $returnCalculationCode, $replaceConstants = true)
    {
        //Check if we should explode it (could just be a POD value)
        if(!is_string($code) || !TextExternal::beginsWith(strtolower($code), 'rn:')){
            if($returnCalculationCode){
                if(is_bool($code))
                    return $code === true ? "true" : "false";
                if(is_array($code))
                    return var_export($code, true);
                if(!is_numeric($code))
                    return "'" . addslashes($code) . "'";
            }
            return $code;
        }

        //Determine what type of value we need to parse
        $parts = explode(':', $code);
        switch(strtolower($parts[1]))
        {
            case 'cfg':
                if ($replaceConstants === false && defined($parts[2]) && $cfg = constant($parts[2]))
                    return $returnCalculationCode ? "\RightNow\Utils\Config::getConfig($parts[2])" : ConfigExternal::getConfig($cfg);
                else if (defined($parts[2]) && $cfg = constant($parts[2]))
                    return $returnCalculationCode ? "\RightNow\Utils\Config::getConfig($cfg)" : ConfigExternal::getConfig($cfg);
            case 'msg':
                if ($replaceConstants === false && isset($parts[2]) && defined($parts[2]) && ($msg = constant($parts[2])))
                    return $returnCalculationCode ? "\RightNow\Utils\Config::getMessage($parts[2])" : ConfigExternal::getMessage($msg);
                else if (isset($parts[2]) && ($msg = defined($parts[2]) ? constant($parts[2]) : null) )
                    return $returnCalculationCode ? "\RightNow\Utils\Config::getMessage($msg)" : ConfigExternal::getMessage($msg);
            case 'msgjs':
                if ($replaceConstants === false && isset($parts[2]) && defined($parts[2]) && ($msg = constant($parts[2])))
                    return $returnCalculationCode ? "\RightNow\Utils\Config::getMessageJS($parts[2])" : ConfigExternal::getMessageJS($msg);
                else if (isset($parts[2]) && ($msg = defined($parts[2]) ? constant($parts[2]) : null) )
                    return $returnCalculationCode ? "\RightNow\Utils\Config::getMessageJS($msg)" : ConfigExternal::getMessageJS($msg);
            case 'astr':
                $fullCode = implode(':', array_slice($parts, 2));
                return $returnCalculationCode ? ("'" . addslashes($fullCode) . "'") : ConfigExternal::ASTRgetMessage($fullCode);
            case 'astrjs':
                $fullCode = implode(':', array_slice($parts, 2));
                return $returnCalculationCode ? ("'" . addslashes($fullCode) . "'") : ConfigExternal::ASTRgetMessageJS($fullCode);
            case 'def':
                if(isset($parts[2]) && defined($parts[2]) && ($cfg = constant($parts[2])))
                    return $returnCalculationCode ? $parts[2] : $cfg;
                break;
            case 'php':
                 //If we've gotten this far, we need to evaluate the value. Note: We must implode incase of ':' in the code.
                $fullCode = implode(':', array_slice($parts, 2));
                if($returnCalculationCode)
                    return $fullCode;
                $codeToEval = 'try{ $result = ' . $fullCode . ';} catch(\Exception $e){return $e;} return $result;';
                if(($result = eval($codeToEval)) === false)
                {
                    if($error = error_get_last())
                        throw new \Exception("Error when trying to eval() php code from manifest file. Error: {$error['message']}");
                    throw new \Exception('Error when trying to eval() loaded php code from the manifest file. Code:' . $codeToEval);
                }

                if($result instanceof \Exception)
                    throw $result;
                return $result;
        }
        throw new \Exception(sprintf(ConfigExternal::getMessage(PARSE_FLD_PCT_S_IMPROPERLY_MSG), $code));
    }

    /**
     * Validates the non-attribute data from a widget's manifest file.
     * @param array|null $widgetInfo Assumed to be the results from calling yaml_parse_file on a manifest file
     * @return string|array String error message or Array widget's manifest data
     */
    private static function validateWidgetManifestInfo($widgetInfo) {
        // Check for all the required fields
        if(!$widgetInfo)
            return ConfigExternal::getMessage(INFO_YML_IS_IMPROPERLY_FORMATTED_MSG);
        if(!$widgetInfo['version'])
            return ConfigExternal::getMessage(VERSION_KEY_REQD_WIDGET_MANIFESTS_MSG);
        if(!$widgetInfo['requires'] || !is_array($widgetInfo['requires']))
            return ConfigExternal::getMessage(REQUIRES_KEY_REQD_WIDGET_MANIFESTS_MSG);
        if(isset($widgetInfo['requires']['yui']) && $widgetInfo['requires']['yui'] && !is_array($widgetInfo['requires']['yui']))
            return ConfigExternal::getMessage(REQUIRED_YUI_COMPONENTS_ARRAY_FMT_MSG);
        if(!$widgetInfo['requires']['jsModule'] && !is_array($widgetInfo['requires']['jsModule']))
            return ConfigExternal::getMessage(REQS_KEY_SUB_KEY_JSMODULE_INFO_YML_MSG);

        // Check the jsModule attribute.
        $legalValues = array(ClientLoader::MODULE_STANDARD, ClientLoader::MODULE_NONE, ClientLoader::MODULE_MOBILE);
        foreach($widgetInfo['requires']['jsModule'] as $element)
        {
            if(!in_array(trim(strtolower($element)), $legalValues))
                return sprintf(ConfigExternal::getMessage(JSMODULE_KEY_CONT_ILLEGAL_VAL_LEGAL_MSG), implode(', ', $legalValues));
        }

        //Verify the framework version format
        if(array_key_exists('framework', $widgetInfo['requires'])) {
            $frameworkVersion = $widgetInfo['requires']['framework'];
            if(is_numeric($frameworkVersion)) {
                $widgetInfo['requires']['framework'] = array($frameworkVersion);
            }
            else if(!is_array($frameworkVersion)) {
                return ConfigExternal::getMessage(REQS_KEY_SUB_KEY_FRAMEWORK_INFO_YML_MSG);
            }
            else {
                foreach($frameworkVersion as $version) {
                    if(!Version::isValidVersionNumber($version))
                        return sprintf(ConfigExternal::getMessage(FRAMEWORK_ARRAY_CONT_INV_VERSION_LBL), $version);
                }
            }
        }

        foreach (array('extends', 'contains') as $parameter) {
            if (array_key_exists($parameter, $widgetInfo)) {
                $results = self::validateWidgetDependencies($widgetInfo[$parameter], $parameter);
                if (is_string($results)) {
                    return $results;
                }
                $widgetInfo[$parameter] = $results;
            }
        }

        return $widgetInfo;
    }

    /**
     * Validate the 'extends' and 'contains' attributes of a widget manifest.
     * Single 'versions' will be converted to a list.
     *
     * @param array|null $dependencies The 'extends' or 'contains' array.
     * @param string $relationship How to validate dependencies, either extends or contains
     * @return array|string Returns the $dependencies array upon success, else an error message is returned.
     */
    private static function validateWidgetDependencies($dependencies, $relationship = 'extends') {
        $validateWidgetAndVersionAttributes = function($entry) use ($relationship) {
            if (!is_array($entry) || !($widget = $entry['widget']) || !is_string($widget) || !preg_match('@^[A-Za-z_][A-Za-z0-9_/]*(/\d\.\d.\d)?$@', $widget)) {
                return sprintf(ConfigExternal::getMessage(VALID_WIDGET_PCT_S_ATTRIBUTE_MSG), $relationship);
            }

            if (array_key_exists('versions', $entry)) {
                if (!is_array($entry['versions'])) {
                    $entry['versions'] = array($entry['versions']);
                }
                foreach ($entry['versions'] as $version) {
                    if (!preg_match('/^\d{1,3}[.]\d{1,3}([.]\d{1,3})?$/', $version)) {
                        return sprintf(ConfigExternal::getMessage(VALID_VERSION_PCT_S_ATTRIBUTE_MSG), $relationship);
                    }
                }
            }
            return $entry;
        };

        if ($relationship === 'extends') {
            $components = is_array($dependencies) ? $dependencies['components'] : $dependencies;
            if (!($components) && (is_array($dependencies) && !$dependencies['overrideViewAndLogic'])) {
                return ConfigExternal::getMessage(WIDGET_COMPONENTS_WISH_EXTEND_E_G_MSG);
            }
            if (is_string($components)) {
                if (TextExternal::stringContains($components, ',')) {
                    return ConfigExternal::getMessage(COMPONENT_EXTENDED_COMPONENTS_LIST_MSG);
                }
                $components = array($components);
            }
            if (is_array($components)) {
                $pieces = array();
                foreach ($components as $piece) {
                    $key = '';
                    $piece = strtolower($piece);
                    if (in_array($piece, array('js', 'php', 'view', 'css'))) {
                        $key = $piece;
                    }
                    if ($key) {
                        $pieces[$key] = true;
                        if (($key === 'view' || $key === 'js') && (isset($dependencies['overrideViewAndLogic']) && $dependencies['overrideViewAndLogic'])) {
                            return sprintf(ConfigExternal::getMessage(EXTEND_PCT_S_COMPONENT_MSG), $piece);
                        }
                    }
                }
            }
            else {
                return ConfigExternal::getMessage(COMPONENTS_EXTENDED_IMPROPERLY_MSG);
            }
            $dependencies = !is_array($dependencies) ? array() : $dependencies;
            $dependencies['components'] = $pieces;
            if (!$pieces && !$dependencies['overrideViewAndLogic']) {
                return ConfigExternal::getMessage(COMPONENTS_SPECIFIED_ARENT_CORRECT_LBL);
            }

            $dependencies = $validateWidgetAndVersionAttributes($dependencies);
        }
        else if ($relationship === 'contains') {
            if (!is_array($dependencies)) {
                return ConfigExternal::getMessage(CONT_ATTRIB_ARRAY_WIDGETS_MSG);
            }
            $newContains = array();
            foreach($dependencies as $data) {
                $contains = $validateWidgetAndVersionAttributes($data);
                if (is_string($contains)) {
                    return $contains;
                }
                $newContains[] = $contains;
            }
            $dependencies = $newContains;
        }

        return $dependencies;
    }

    /**
     * Validates attribute values by checking their type and default.
     * @param array $widgetInfo Info from widget's info.yml
     * @param array $propertiesToOmit Containing names of properties to omit (basically can only contain 'name' and/or 'description')
     * @return string|null Error message if there's a problem, null if validation passes
     */
    public static function validateWidgetAttributeStructure(array $widgetInfo, array $propertiesToOmit = array()){
        if(is_array($widgetInfo['attributes'])){
            $attributes = $widgetInfo['attributes'];
            foreach ($attributes as $name => $value) {
                // skip validation if an attribute is being unset
                if (is_string($value) && strtolower($value) === 'unset')
                    continue;
                if (!$value->name && !in_array('name', $propertiesToOmit)) {
                    return sprintf(ConfigExternal::getMessage(ATTRIBUTE_PCT_S_NAME_MSG), $name);
                }
                if (!$value->description && !in_array('description', $propertiesToOmit)) {
                    return sprintf(ConfigExternal::getMessage(ATTRIBUTE_PCT_S_DESCRIPTION_MSG), $name);
                }
                $default = isset($value->default) ? $value->default : null;
                $type = TextExternal::cStrToLower($value->type);
                if ($type) {
                    switch($type) {
                        case 'string':
                        case 'filepath':
                            $isNumeric = $type === 'string' && is_numeric($default);
                            if ($default !== null && !is_string($default) && !$isNumeric) {
                                return sprintf(ConfigExternal::getMessage(DEFAULT_VALUE_ATTRIB_PCT_S_STRING_MSG), $name);
                            }
                            break;
                        case 'ajax':
                            if (!$default) {
                                return sprintf(ConfigExternal::getMessage(DEFAULT_VALUE_ATTRIBUTE_PCT_S_MSG), $name);
                            }
                            break;
                        case 'int':
                        case 'integer':
                            if ($default !== null && !is_numeric($default)) {
                                return sprintf(ConfigExternal::getMessage(DEF_VALUE_PCT_S_ATTRIB_PCT_S_INT_MSG), $default, $name);
                            }
                            break;
                        case 'bool':
                        case 'boolean':
                            if($default !== null){
                                if($default === 'true' || $default === 'false')
                                    $default = ($default === 'true');
                                if (!is_bool($default))
                                    return sprintf(ConfigExternal::getMessage(DEF_VAL_PCT_S_ATTRIB_PCT_S_BOOLEAN_MSG), $default, $name);
                            }
                            break;
                        case 'option':
                        case 'multioption':
                            if (!is_array($value->options) && count($value->options) === 0) {
                                return sprintf(ConfigExternal::getMessage(ATTRIBUTE_PCT_S_OPTIONS_LIST_MSG), $name);
                            }
                            break;
                        default:
                            return sprintf(ConfigExternal::getMessage(TYPE_ATTRIBUTE_PCT_S_INVALID_MSG), $name);
                    }
                }
                else {
                    return sprintf(ConfigExternal::getMessage(TYPE_ISNT_ATTRIBUTE_PCT_S_LBL), $name);
                }
            }
        }
    }

    private static function validateWidgetAttributes($widget, $widgetPath)
    {
        $attributes = $widget->getAttribute(null);
        if (!is_array($attributes))
            return;

        foreach ($attributes as $name => &$attribute)
        {
            if (is_array($attribute->options) && count($attribute->options) > 0 && strtolower($attribute->type) !== 'option')
            {
                if (TextExternal::beginsWith($widgetPath, 'standard/'))
                {
                    // This does not need to be internationalized because it should
                    // never happen outside of development or QA.  If this happens
                    // in the field we have a bigger problem.
                    throw new \Exception("$widgetPath has a string attribute, $name, with options.  The attribute's type should be OPTION.");
                }
            }

            switch (strtolower($attribute->type))
            {
                case 'int':
                case 'integer':
                case 'string':
                case 'bool':
                case 'boolean':
                case 'ajax':
                    continue 2;
                case 'option':
                    if (!is_array($attribute->options) || count($attribute->options) === 0)
                        throw new \Exception(sprintf(ConfigExternal::getMessage(PCT_S_PCT_S_ATTRIB_PCT_S_OPTION_MSG), $attribute->name, $name, $widgetPath));
                    continue 2;
                default:
                    throw new \Exception(sprintf(ConfigExternal::getMessage(PCT_S_PCT_S_ATTRIB_PCT_S_INV_DATA_MSG), $attribute->name, $name, $widgetPath, $attribute->type));
            }
        }
        return $widget;
    }

    /**
     * Ensure that a widget either:
     *  a) Extends from a valid widget and that extended widget has a legitimate version
     *  b) Contains a valid widget and that contained widget has a legitimate version
     *  c) Has a valid ['requires']['framework'] attribute that matches the current framework
     * @param PathInfo $widget PathInfo object of widget to verify
     * @return string|null String error message if something is invalid; null, otherwise
     */
    public static function verifyWidgetReferences(PathInfo $widget) {
        $cacheKey = "{$widget->relativePath}-{$widget->version}";
        if(array_key_exists($cacheKey, self::$verifyCache))
            return self::$verifyCache[$cacheKey];
        $versionHistory = Version::getVersionHistory();

        $getVersionToMatch = function(PathInfo $relatedWidget, $stripNano = true) {
            if($relatedWidget->type === 'standard') {
                if(IS_HOSTED || $relatedWidget->version !== '') {
                    $version = $relatedWidget->version;
                }
                else {
                    if(!is_array($relatedWidget->meta))
                        return '0.0';
                    $version = $relatedWidget->meta['version'];
                }
                if($stripNano)
                    return substr($version, 0, strrpos($version, '.'));
                return $version;
            }
            return $relatedWidget->version;
        };

        if($widget->type === 'standard') {
            $widgetHistory = $versionHistory['widgetVersions'][$widget->relativePath];
            if(IS_HOSTED || $widget->version !== '') {
                $version = $widget->version;
            }
            else {
                if(!is_array($widget->meta))
                    return sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_VALID_INFO_YML_FILE_LBL), $widget->relativePath, $widget->meta);
                $version = $widget->meta['version'];
            }
            $widgetInfo = $widgetHistory[$version];
        }
        else {
            $widgetInfo = $widget->meta;
            if(!is_array($widgetInfo))
                return sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_VALID_INFO_YML_FILE_LBL), $widget->relativePath, $widget->meta);
        }

        $returnValue = null;
        //If the current widget has a framework requirement, validate it.
        if(isset($widgetInfo['requires']['framework']) && $requiredFrameworks = $widgetInfo['requires']['framework']) {
            if(!in_array(CP_FRAMEWORK_VERSION, $requiredFrameworks)) {
                $returnValue = sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_SUPP_FRAMEWORK_VERSION_MSG), $widget->relativePath, CP_FRAMEWORK_VERSION, implode(', ', $requiredFrameworks));
            }
        }
        $parentWidget = $widgetInfo;
        while ($parentWidget && isset($parentWidget['extends']) && $parentWidget['extends']) {
            $extends = $parentWidget['extends'];
            if(!$extendedWidget = Registry::getWidgetPathInfo($extends['widget'])) {
                $returnValue = sprintf(ConfigExternal::getMessage(EXTENDED_ERR_CCT_CCT_TRGTBLNK_HRF_MSG), $extends['widget'], "/ci/admin/versions/manage#filters=both%3Anotinuse");
            }
            else if(isset($extends['versions']) && ($extendsVersions = $extends['versions']) && !in_array($getVersionToMatch($extendedWidget), $extendsVersions)) {
                $shortVersion = var_export($extendedWidget->shortVersion, true);
                $returnValue = sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_DECLARES_SUPPORTS_MSG),
                    $widget->relativePath, implode(', ', $extendsVersions), $extends['widget'], $shortVersion, $shortVersion, $widget->relativePath,
                    "<a target='_blank' href='/ci/admin/versions/manage#widget=" . urlencode($extends['widget']) . "'>" . ConfigExternal::getMessage(WIDGET_MANAGEMENT_LBL) . "</a>");
            }

            //Ensure extended widget has a valid framework requirement if it is specified
            $extendedFrameworks = array();
            if($extendedWidget->type === 'standard') {
                $extendedFrameworks = $versionHistory['widgetVersions'][$extendedWidget->relativePath][$getVersionToMatch($extendedWidget, false)]['requires']['framework'];
            }
            else if(is_array($extendedWidget->meta)) {
                $extendedFrameworks = $extendedWidget->meta['requires']['framework'];
            }
            if(!empty($extendedFrameworks) && !in_array(CP_FRAMEWORK_VERSION, $extendedFrameworks)) {
                $returnValue = sprintf(ConfigExternal::getMessage(WIDGET_PCT_S_EXTENDS_PCT_S_SUPP_MSG), $widget->relativePath, $extendedWidget->relativePath, CP_FRAMEWORK_VERSION, $extendedWidget->relativePath, implode(', ', $extendedFrameworks));
            }

            $parentWidget = $extendedWidget->meta ?: array();
        }
        if(!$returnValue && isset($widgetInfo['contains']) && ($contains = $widgetInfo['contains'])) {
            foreach($contains as $contain) {
                if(isset($contain['widget']) && !$containedWidget = Registry::getWidgetPathInfo($contain['widget']))
                    $returnValue = sprintf(ConfigExternal::getMessage(CONTAINED_WIDGET_PCT_S_EX_MSG), $contain['widget']);
                else if($contain['versions'] && !in_array($getVersionToMatch($containedWidget), $contain['versions']))
                    $returnValue = sprintf(ConfigExternal::getMessage(CONTAINED_WIDGET_PCT_S_DECLARED_MSG),
                        $contain['widget'], var_export($containedWidget->shortVersion, true), implode(', ', $contain['versions']), "<a target='_blank' href='/ci/admin/versions/manage#widget=" . urlencode($contain['widget']) . "'>" . ConfigExternal::getMessage(WIDGET_MANAGEMENT_LBL) . "</a>");
                if($returnValue)
                    break;
            }
        }
        return self::$verifyCache[$cacheKey] = $returnValue;
    }

    public static function widgetInDevelopment(PathInfo $widget, $attributes = array(), $widgetPosition = null)
    {
        $CI = get_instance();
        if($error = self::verifyWidgetReferences($widget)){
            return BaseWidget::widgetError($widgetPath, $error);
        }
        $widgetInstance = self::createWidgetInDevelopment($widget, false, $attributes);
        if(is_object($widgetInstance))
            $widgetInstance->setInfo("widgetPosition", $widgetPosition);
        $rendered = self::safelyRenderWidget($widgetInstance, $widget, 'renderDevelopment');
        WidgetViews::removeExtendingView($widget->relativePath);
        return $rendered;
    }

    private static function safelyRenderWidget($widgetInstance, PathInfo $widget, $renderMethodName)
    {
        if ($widgetInstance instanceof BaseWidget)
            return $widgetInstance->$renderMethodName();
        if (is_string($widgetInstance))
            return $widgetInstance; // which contains an error message
        return sprintf(ConfigExternal::getMessage(UNKNOWN_ERR_OCC_TRYING_RENDER_PCT_S_MSG), $widget->relativePath);
    }

    /**
     * Creates a widget in production mode given the saved widget meta information made available in the widget's
     * 'header' function dumped into the page during deployment.
     * @param PathInfo $widgetPathInfo The PathInfo object for the current widget.
     * @param array $attributes Attribute values to inherit from containing composite widgets.
     * @param string $libraryName Class name to use for the widget instead of what's normally resolved;
     * this is specified when a widget has been extended by a child and its customized view method actually
     * hangs off of the child's class.
     * @return Object|String The requested widget instance or a string containing an error message.
     * @see  RightNow\Internal\Libraries\Deployer#createWidgetHeaderFunction
     */
    private static function createWidgetInProduction(PathInfo $widgetPathInfo, $attributes = array(), $libraryName = '')
    {
        $widgetDetails = $widgetPathInfo->meta;
        $path = $widgetPathInfo->relativePath;
        $metaLibraryName = $widgetDetails['library_name'];
        $javaScriptClassName = $widgetDetails['js_name'];
        $viewFunctionName = $widgetDetails['view_func_name'];
        $meta = $widgetDetails['meta'];
        $widgetClass = $libraryName ?: "\\{$widgetPathInfo->namespace}\\{$metaLibraryName}";
        if(!class_exists($widgetClass))
            exit(ConfigExternal::getMessage(FATAL_ERR_WIDGET_CALLS_PHP_SUPP_MSG));

        $manifestAttributes = array();
        if ($meta['attributes']) {
            $manifestAttributes = $meta['attributes'];
            unset($meta['attributes']);
        }

        $widgetInstance = new $widgetClass($manifestAttributes);
        $contextData = self::getWidgetContextData($widgetInstance, $widgetPathInfo->relativePath, (isset($meta['extends_php']) ? $meta['extends_php'] : ''), $attributes);

        // if $contextData is a string (from BaseWidget::widgetError), we know there was an error during the validation process
        if(is_string($contextData))
            return $contextData;

        $widgetInstance = self::addFormToken($widgetInstance, $widgetPathInfo);
        $widgetInstance = self::setContextDataToWidgetInstance($widgetInstance, $contextData);
        $widgetInstance->setInfo(array_merge($meta, array(
            'controller_name' => $metaLibraryName,
            'js_name' => $javaScriptClassName,
            'widget_path' => isset($widgetPath) ? $widgetPath : '',
        )));
        $widgetInstance->setViewFunctionName($viewFunctionName);
        $widgetInstance->setPath($path);
        $widgetInstance->setHelper();

        $areAttributesValid = $widgetInstance->setAttributes($attributes);
        return ($areAttributesValid === true) ? $widgetInstance : $areAttributesValid;
    }

    public static function widgetInProduction(PathInfo $widget, $attributes = array(), $libraryName = '')
    {
        $widgetInstance = self::createWidgetInProduction($widget, $attributes, $libraryName);
        return self::safelyRenderWidget($widgetInstance, $widget, 'renderProduction');
    }

    /**
     * Converts rn widget calls to php widget calls
     *
     * @param string $matches The buffer to convert on
     * @param string $widgetPosition The character position detail of a widget in the source file
     * @return string The php function call for the widget
     */
    public static function widgetReplacer($matches, $widgetPosition = null)
    {
        $attributes = TagsExternal::getHtmlAttributes($matches[0]);
        $path = TagsExternal::getAttributeValueFromCollection($attributes, 'path');
        if (!$path)
            return ConfigExternal::getMessage(WIDGET_TAG_PATH_ATTRIB_DISPLAYED_MSG);

        if (Registry::getVersionInPath($path))
        {
            // Prevent widget paths from directly using a widget version directory
            return sprintf(ConfigExternal::getMessage(WIDGET_PATH_PCT_S_IS_INVALID_MSG), $path);
        }

        // If not found, just use what was passed in as the path (will generate an error later on)
        $functionCall = "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('$path', array(";

        // Dump all attributes beside path into the rnWidgetRenderCall() function call.
        foreach ($attributes as $attribute)
        {
            $parameterName = $attribute->attributeName;
            if (strcasecmp($parameterName, 'path') !== 0)
            {
                $parameterValue = TagsExternal::escapeForWithinPhp($attribute->attributeValue);
                $functionCall .= "'$parameterName' => '$parameterValue',";
            }
        }
        if(IS_DEVELOPMENT && $widgetPosition){
            $functionCall .= "), null, \"$widgetPosition\");\n?>";
        } else {
            $functionCall .= "));\n?>";
        }
        return $functionCall;
    }

    /**
     * Converts beginning rn container calls to php calls that modify the stack of attributes
     *
     * @param string $matches The buffer to convert on
     * @return string The php calls to modify the stack of attributes
     */
    public static function containerReplacerBegin($matches)
    {
        $containerIDName = "rn_container_id";
        $containerIDPrefix = "rnc_";
        $attributes = TagsExternal::getHtmlAttributes($matches[0]);

        $containerAttributes = array();
        $containerDeclaration = array();
        foreach($attributes as $attribute)
        {
            $attributeName = $attribute->attributeName;
            $attributeValue = $attribute->attributeValue;
            $containerAttributes[$attributeName] = $attributeValue;
            $containerDeclaration[] = "'$attributeName' => " . var_export($attributeValue, true);
            if ($attributeName === $containerIDName)
                $containerID = $attributeValue;
        }

        if (!isset($containerID) || !$containerID)
        {
            $containerID = $containerIDPrefix . ++self::$containerID;
            // add rn_container_id to defined attributes
            $containerAttributes[$containerIDName] = $containerID;
            $containerDeclaration[] = "'$containerIDName' => " . var_export($containerID, true);
        }
        else if (TextExternal::beginsWith($containerID, $containerIDPrefix))
        {
            FrameworkExternal::addErrorToPageAndHeader(sprintf(ConfigExternal::getMessage(PCT_S_ATTRIBUTE_START_PCT_S_MSG), $containerIDName, $containerIDPrefix), true);
            return "";
        }

        return '<?
            $currentAttributeArray = array("rn_container" => "true", ' .
                implode(",", $containerDeclaration) .
            ');
            \RightNow\Utils\Widgets::pushAttributesOntoStack($currentAttributeArray);
            \RightNow\Utils\Widgets::$rnContainers["' . $containerID . '"] = \'' . base64_encode(json_encode($containerAttributes)) . '\';
        ?>';
    }

    /**
     * Resets the static container ID incrementer variable. Called during the deploy process so that
     * static IDs are identical between each deploy.
     */
    public static function resetContainerID(){
        self::$containerID = 0;
    }

    /**
     * Converts ending rn container calls to php calls that modify the stack of attributes
     *
     * @return string The php calls to remove the attributes that the container previously pushed on
     */
    public static function containerReplacerEnd()
    {
        // if the popped set of attributes do not contain an 'rn_container' attribute (i.e.
        // the code has probably popped too many levels), the attributes should be pushed back on
        return '<?
            $attributes = \RightNow\Utils\Widgets::popAttributesFromStack();
            if($attributes !== null && !array_key_exists("rn_container", $attributes))
                \RightNow\Utils\Widgets::pushAttributesOntoStack($attributes);
        ?>';
    }

    /**
     * Replace \ by /, eliminate duplicate '/', and remove the initial or final '/'
     * We had problems with our deployment code that generated widget function
     * names getting messed up if the slashes weren't consistent within a file.
     * Additionally certain combinations of slashes would cause a widget to fail
     * to load in production.
     *
     * @param string $path The widget path
     * @param bool $removeLeadingSlash Boolean denoting if leading slash should be removed
     * @return string The normalized path
     */
    public static function normalizeSlashesInWidgetPath($path, $removeLeadingSlash) {
        $path = preg_replace('@/{2,}@', '/', preg_replace('@/+$@', '', preg_replace('@[\\\\]@', '/', $path)));
        if ($removeLeadingSlash) {
            $path = preg_replace('@^/+@', '', $path);
        }
        return $path;
    }

    /**
     * Generates widget information based on the given widget paths for the development
     * header.
     *
     *
     * @param array $widgetPaths A list of widgets keyed by the path
     * @return array An array of URL parameters based on the given paths
     */
    public static function getWidgetDetailsForHeader(array $widgetPaths)
    {
        $moduleSets = $urlParameters = array();
        $CI = get_instance();
        $pageMeta = $CI->_getMetaInformation();
        $declaredModule = isset($pageMeta['javascript_module']) ? $pageMeta['javascript_module'] : ClientLoader::MODULE_STANDARD;
        
        foreach ($widgetPaths as $widgetPath => $widgetData)
        {
            //Check that the page/template javascript_module matches the required jsModule of each widget.
            $widgetModules = $widgetData['meta']['requires']['jsModule'];
            if(!in_array($declaredModule, array_map('strtolower', array_map('trim', $widgetModules))))
                $moduleSets[$widgetPath] = implode(',', $widgetModules);

            if (isset($widgetData['meta']['info']) && $widgetData['meta']['info'])
            {
                $widgetData['meta'] = self::convertUrlParameterTagsToValues($widgetData['meta']);
                $urlParams = isset($widgetData['meta']['info']['urlParameters']) ? $widgetData['meta']['info']['urlParameters'] : array();
                foreach ($urlParams as $key => $param)
                {
                    if (!array_key_exists($key, $urlParameters))
                    {
                        $urlParameters[$key] = (object) $param;
                        $urlParameters[$key]->key = $key;
                        $urlParameters[$key]->widgetsUsedBy = array();
                    }
                    $urlParameters[$key]->widgetsUsedBy []= $widgetPath;
                }
            }
        }
        ksort($urlParameters);
        return array(
            'urlParameters' => $urlParameters,
            'javaScriptModuleProblems' => $moduleSets,
        );
    }

    /**
     * Given a widget and a theme, figures out which CSS paths with widget might use.
     * Also includes parent widget CSS if the widget extends another and specifies that
     * css should be included.
     * @param string $themePath Path of the current theme relative to the HTMLROOT.
     * @param PathInfo $widget The PathInfo object for the current widget.
     * @param object $resolver Figures out where to look for the widget presentation CSS.
     * @return array Each item has a type (base/presentation) and path (absolute path to the file); presentation css
     *      has additional info: class, absolutePath, relativePath
     */
    private static function determineWidgetCssFiles($themePath, PathInfo $widget, $resolver)
    {
        $widgetCssFiles = array(array('type' => 'base', 'path' => $widget->absolutePath . '/base.css'));
        if (is_array($widget->meta) && isset($widget->meta['extends']) && isset($widget->meta['extends']['components']['css']) &&
            ($parent = Registry::getWidgetPathInfo($widget->meta['extends']['widget']))) {
            // Include parent's base, presentation before child's base, presentation
            $widgetCssFiles = array_merge(self::determineWidgetCssFiles($themePath, $parent, $resolver), $widgetCssFiles);
        }

        // If we are in non-hosting and the widget's themed css file exists, reset presentation_css to widgetCss/.placeholder
        // because the file must exist for the resolver to work and we need it to execute within webfiles/assets/themes.
        if(!IS_HOSTED && ($themeName = basename($themePath)) && in_array($themeName, array('standard', 'mobile', 'basic'))
            && FileSystemExternal::isReadableFile($widget->absolutePath . "/themesPackageSource/$themeName/widgetCss/" . $widget->className . ".css"))
            $presentationCss = 'widgetCss/.placeholder';
        else
            $presentationCss = "widgetCss/{$widget->className}.css";

        if (!is_object($resolver)) {
            require_once CPCORE . 'Internal/Libraries/Resolvers.php';
            $resolver = new \RightNow\Internal\Libraries\NormalWidgetPresentationCssResolver();
        }

        $widgetCssFiles []= array(
            'type'         => 'presentation',
            'path'         => $resolver->resolve($themePath, $presentationCss),
            'class'        => $widget->className,
            'absolutePath' => $widget->absolutePath,
            'relativePath' => $widget->relativePath,
        );

        return $widgetCssFiles;
    }

    /**
     * Given a widget path, this function accumulates the widget CSS content
     *
     * @param PathInfo $widget The PathInfo object for the current widget
     * @param string $themePath Path to the theme
     * @param bool $shouldDelimitWithComments Whether CSS should be delimited with comments
     * @param bool $rebasePresentationToDirectory Whether to rebase CSS paths
     * @param object|bool $resolver Resolves a presentation_css value appropriately for the execution context/deployment mode.
     * @param array|null $replacePatterns Array of arrays of search and replace patterns to send to CSSUrlRewriter.
     * @return string The combined CSS content for the widget
     */
    public static function accumulateWidgetCss(PathInfo $widget, $themePath, $shouldDelimitWithComments, $rebasePresentationToDirectory = false, $resolver = false, $replacePatterns = array())
    {
        $widgetCssContent = '';
        foreach(self::determineWidgetCssFiles($themePath, $widget, $resolver) as $css) {
            $type = $css['type'];
            $path = $css['path'];

            // If we are in non-hosting and the widget's themed css file exists, use that file instead of the widgetCss equivalent.
            if(!IS_HOSTED && $type === 'presentation'
                && ($themeName = basename($themePath)) && in_array($themeName, array('standard', 'mobile', 'basic'))
                && ($nonHostedPresentationCssFile = $css['absolutePath'] . "/themesPackageSource/$themeName/widgetCss/" . $css['class'] . ".css")
                && FileSystemExternal::isReadableFile($nonHostedPresentationCssFile))
            {
                $content = file_get_contents($nonHostedPresentationCssFile);
            }
            else if (!FileSystemExternal::isReadableFile($path)){
                continue;
            }
            else{
                $content = file_get_contents($path);
            }

            if ($type === 'presentation') {
                if ($rebasePresentationToDirectory) {
                    $rewriteFromDirectory = dirname(str_replace(HTMLROOT . $themePath, $rebasePresentationToDirectory, $path));
                }
                else {
                    $rewriteFromDirectory = dirname($path);
                }
                $content = \RightNow\Internal\Libraries\CssUrlRewriter::rewrite($content, $rewriteFromDirectory, HTMLROOT, $replacePatterns);
            }
            $widgetCssContent .= $content;
        }

        $widgetCssContent = TextExternal::minifyCss($widgetCssContent);
        if (strlen($widgetCssContent) === 0)
            return '';
        if ($shouldDelimitWithComments)
            return "/*Begin CSS for $widget->relativePath */\n$widgetCssContent\n/*End CSS for $widget->relativePath */\n\n";
        return "$widgetCssContent\n";
    }

    /**
     * Converts a widget name into the corresponding function call used in production.
     * @param object|string $widget The PathInfo object for the current widget or its relative path
     * @param string $suffix Suffix to add onto the function name
     * @return string The function name for the widget
     */
    public static function generateWidgetFunctionName($widget, $suffix = '')
    {
        //first start the function name with an underscore, and replace slashes with underscores
        $functionName = '_' . strtr(is_object($widget) ? $widget->relativePath : $widget, '/', '_') . $suffix;
        //next replace all non alphanumeric/underscore characters to their ASCII value
        $functionName = preg_replace_callback('@[^a-zA-Z0-9_]@', function($matches){return ord($matches[0]);}, $functionName);
        return $functionName;
    }

    /**
     * Returns an array of themes supported by the widget.  An empty array is returned
     * if the widget does not contain any theme directories.  A theme may be returned
     * even if there are not any assets within it.
     *
     * @param string $widgetPath Relative path of widget (E.g. "standard/input/TextInput")
     * @return array Returns array of themes (E.g. ["standard", "mobile"])
     */
    public static function getThemes($widgetPath)
    {
        if(!$widget = Registry::getWidgetPathInfo($widgetPath))
            return array();
        return FileSystemExternal::getSortedListOfDirectoryEntries($widget->absolutePath . "/themesPackageSource");
    }

    /**
     * Build up a dependencies array of 'extends', 'contains' and 'children'.
     *
     * @param string $widget A widget specifier (e.g. 'standard/input/DateInput')
     * @param string $version A {major}.{minor} version string.
     * @param string $relationship One of 'extends', 'contains' or 'children'.
     * @param array $dependents A list of associative arrays having keys: 'widget' and (optionally) 'versions'.
     * @param array &$tracker The dependencies array to build.
     * @throws \Exception If $relationship is invalid
     */
    public static function buildDependencies($widget, $version, $relationship, array $dependents, array &$tracker) {
        $relationships = array('extends' => array(), 'contains' => array(), 'children' => array());
        if (!array_key_exists($relationship, $relationships)) {
            throw new \Exception(sprintf(ConfigExternal::getMessage(VALID_RELATIONSHIP_PCT_S_COLON_LBL), $relationship));
        }
        if (!array_key_exists($widget, $tracker)) {
            $tracker[$widget] = array();
        }
        if (!array_key_exists($version, $tracker[$widget])) {
            $tracker[$widget][$version] = $relationships;
        }
        foreach ($dependents as $dependent) {
            if ($dependentWidget = $dependent['widget']) {
                // substitute in a fake 'N/A' to indicate 'all' versions
                $dependentVersions = isset($dependent['versions']) ? $dependent['versions'] : array('N/A');
                foreach ($dependentVersions as $dependentVersion) {
                    if ($relationship === 'extends') {
                        self::buildDependencies(
                            $dependentWidget, $dependentVersion, 'children',
                            array(array('widget' => $widget, 'versions' => array($version))),
                            $tracker
                        );
                    }
                    if (!array_key_exists($dependentWidget, $tracker[$widget][$version][$relationship])) {
                        $tracker[$widget][$version][$relationship][$dependentWidget] = array();
                    }
                    $tracker[$widget][$version][$relationship][$dependentWidget][] = $dependentVersion;
                    // don't duplicate data
                    $tracker[$widget][$version][$relationship][$dependentWidget] = array_values(array_unique($tracker[$widget][$version][$relationship][$dependentWidget]));
                }
            }
        }
    }

    /**
     * Retrieves widget version and relationship information
     *
     * @param array|null $allWidgets Only passed in for unit testing, defaults to `Registry::getAllWidgets()`
     * @param array|null $allVersions Only passed in for unit testing, defaults to `Version::getVersionHistory()`
     * @return array The returned array is formatted as follows:
     *  array(
     *       ['widgets'] => array(
     *           ['custom/sample/SampleWidget'] => array( //Relative widget path
     *               ['type'] => 'custom'
     *               ['absolutePath'] => ...
     *               ['relativePath'] => 'custom/sample/SampleWidget'
     *               // Major.Minor version information
     *               ['versions'] => array (
     *                   array(
     *                       'version' => '1.0'
     *                       'framework' => array('3.0', '3.1')
     *                       'extends' => array(
     *                           'standard/input/SelectionInput' => array('1.0')
     *                       )
     *                       'contains' => array(
     *                           'standard/search/KeywordText' => array('1.0')
     *                       )
     *                       'children' => array(
     *                           'standard/search/SampleWidgetExtended' => array('1.0')
     *                       )
     *                   )
     *               )
     *               ...)
     *       ['errors'] => false // if any errors were found while retrieving custom widgets
     *   )
     */
    public static function getWidgetRelationships($allWidgets = null, $allVersions = null) {
        $allWidgets = $allWidgets ?: Registry::getAllWidgets();
        $allVersions = $allVersions ?: Version::getVersionHistory();

        // Remove deprecated widget names from allWidgets list to avoid showing them in versions/manage page for non hosted site because there entry(ies) is/are still
        // there in cpHistory file and it'll give file not found error when clicked on its documentation button.
        if(!IS_HOSTED) {
            foreach($allWidgets as $widgetKey => $widgetKeyValue) {
                if(!self::getCurrentWidgetVersion($widgetKeyValue['relativePath'])) {
                    // Check if widget entry exists in widgetVersions file
                    if(!FileSystemExternal::isReadableFile($widgetKeyValue['absolutePath'].'/info.yml')) {
                        // In case widget was deactivated, check to see if it exists on disk.
                        unset($allWidgets[$widgetKey]);
                    }
                }
            }
        }

        $dependencies = array();
        $errorsFound = false;

        $addEntry = function($widgetKey, $version, $versionInfo, &$availableVersions, &$dependencies) {
            $entry = array('version' => $version, 'framework' => isset($versionInfo['requires']['framework']) ? $versionInfo['requires']['framework'] : null);
            foreach(array('extends', 'contains') as $attribute) {
                if(isset($versionInfo[$attribute]) && $dependents = $versionInfo[$attribute]) {
                    Widgets::buildDependencies(
                        $widgetKey, $version, $attribute,
                        ($attribute === 'extends') ? array($dependents) : $dependents,
                        $dependencies
                    );
                    $entry[$attribute] = $dependents;
                }
            }
            $availableVersions[$version] = $entry;
        };

        foreach($allWidgets as $widgetKey => $widgetInfo) {
            unset($allVersions['widgetVersions'][$widgetKey]['category']);
            $availableVersions = array();
            switch(strtolower($widgetInfo['type'])) {
                case 'custom':
                    $widgetVersions = \RightNow\Utils\FileSystem::listDirectory(
                        CUSTOMER_FILES . 'widgets/' . $widgetKey, false, false,
                        array('match', '#^[0-9]+\.[0-9]+$#'));

                    usort($widgetVersions, "\RightNow\Internal\Utils\Version::compareVersionNumbers");
                    $metaInfo = null;
                    foreach ($widgetVersions as $version) {
                        $customVersionInfo = new PathInfo('custom', $widgetKey, CUSTOMER_FILES . 'widgets/' . $widgetKey, $version);
                        $metaInfo = $customVersionInfo->meta;
                        if(!is_array($metaInfo)) {
                            $errorsFound = true;
                            $availableVersions[$version] = array('error' => sprintf(ConfigExternal::getMessage(B_ERROR_PCT_S_WIDGET_SLASH_B_PCT_S_LBL), $widgetKey . "/$version", $metaInfo));
                            break;
                        }
                        $addEntry($widgetKey, $version, $metaInfo, $availableVersions, $dependencies);
                    }
                    if(!is_null($metaInfo) && isset($metaInfo['info']) && is_array($metaInfo['info']) && isset($metaInfo['info']['category']) && is_array($metaInfo['info']['category']))
                        $allWidgets[$widgetKey]['category'] = $metaInfo['info']['category'];
                    break;
                case 'standard':
                    foreach ($allVersions['widgetVersions'][$widgetKey] as $widgetVersion => $versionInfo) {
                        $version = substr($widgetVersion, 0, strrpos($widgetVersion, '.'));
                        $addEntry($widgetKey, $version, $versionInfo, $availableVersions, $dependencies);
                    }
                    break;
            }
            if(is_array($allWidgets[$widgetKey]['category']))
                sort($allWidgets[$widgetKey]['category']);
            $allWidgets[$widgetKey]['versions'] = array_values($availableVersions);
        }

        foreach($allWidgets as $widgetKey => &$widgetInfo) {
            foreach($widgetInfo['versions'] as &$versionInfo) {
                // merge dependency info (extends, contains, children) into widget information
                // note that `$versionInfo` already contains an extends and contains key, but we are
                // intentionally overwriting them, which does remove the extends component information
                if(isset($dependencies[$widgetKey][$versionInfo['version']]) && $dependencies[$widgetKey][$versionInfo['version']]) {
                    $versionInfo = array_merge($versionInfo, $dependencies[$widgetKey][$versionInfo['version']]);
                }
                // if this widget has some depedency that applies to 'all' versions, then merge in all versions of
                // the other widget so that the result is complete
                if (isset($dependencies[$widgetKey]['N/A']) && $allVersionsDependencies = $dependencies[$widgetKey]['N/A']) {
                    foreach (array('children', 'contains', 'extends') as $attribute) {
                        if ($allVersionsDependencies[$attribute]) {
                            foreach ($allVersionsDependencies[$attribute] as $subWidget => $versions) {
                                $versionInfo[$attribute][$subWidget] = array_merge((isset($versionInfo[$attribute][$subWidget]) ? $versionInfo[$attribute][$subWidget] : array()), $allVersionsDependencies[$attribute][$subWidget]);
                                usort($versionInfo[$attribute][$subWidget], "\RightNow\Internal\Utils\Version::compareVersionNumbers");
                            }
                        }
                    }
                }
                ksort($versionInfo);
            }
        }

        ksort($allWidgets);

        return array('widgets' => $allWidgets, 'errors' => $errorsFound);
    }
}
