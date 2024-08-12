<?php

namespace RightNow\Internal\Utils;

use RightNow\Utils\Config as ConfigExternal,
    RightNow\Internal\Utils\Framework,
    RightNow\Api;

class VersionTracking {
    private static $accountID = false;
    private static $cache = null;
    private static $cached = array(
        'versions' => false,
        'objects' => false,
        'development' => false,
        'staging' => false,
        'production' => false,
    );

    private static $targets = array(
        'db' => 'logToDatabase',
        'acs' => 'logToAcs',
        'audit' => 'logToAudit',
    );

    /**
     * Report ID for CP objects and CP Object Versions
     * @var array Reports
     */
    public static $reports = array(
        'objects' => 10100,  // canned_rpts/ac10100_CPObjects.sql
        'versions' => 10102, // canned_rpts/ac10102_CPVersions.sql
    );

    /**
     * Record widget and framework version changes to the specified $targets.
     * @param array $data An array containing keys:
     *     'name' - Either 'framework' or the relative path to the widget being updated (beginning with 'standard/' or 'custom/').
     *     'to' - The version number being changed to.
     *     'from' (optional) - The version number being changed from. Defaults to null.
     *     'mode' (optional) - One of 'development' 'staging' or 'production'. Defaults to 'development'.
     * @param string|array $targets Specifies what targets to log to. Expects 'all' or an array of keys from self::$targets.
     * @throws \Exception If an error was encountered.
     */
    public static function log(array $data, $targets = 'all') {
        if ($targets === 'all') {
            $targets = array_keys(self::$targets);
        }
        else if (!is_array($targets)) {
            throw new \Exception(ConfigExternal::getMessage(TARGETS_STRING_ARRAY_MSG));
        }

        foreach($targets as $target) {
            if (!array_key_exists($target, self::$targets)) {
                throw new \Exception(sprintf(ConfigExternal::getMessage(RECOGNIZED_TARGET_PCT_S_COLON_LBL), $target));
            }
            $method = self::$targets[$target];
            self::$method($data);
        }
    }

    /**
     * Record widget and framework version changes to the version audit log.
     * @param array $data An array containing keys:
     *     'name' - Either 'framework' or the relative path to the widget being updated (beginning with 'standard/' or 'custom/').
     *     'to' - The version number being changed to.
     *     'from' (optional) - The version number being changed from. Defaults to null.
     * @throws \Exception If an error was encountered.
     * @return string The entry recorded to the version audit log.
     */
    public static function logToAudit(array $data) {
        $path = CUSTOMER_FILES . 'versionAuditLog';
        $log = file($path, FILE_SKIP_EMPTY_LINES);
        $entry = sprintf("%s,{$data['name']},{$data['to']},{$data['from']},%s\n", self::getAccountID(), time());
        array_unshift($log, $entry);
        $maxLines = 300;
        if(count($log) > $maxLines){
            $log = array_slice($log, 0, $maxLines);
        }
        umask(0);
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($path, implode('', $log));
        return $entry;
    }

    /**
     * Record widget and framework version changes to the Action Capture Service.
     * @param array $data An array containing the version change details.
     * @return array|null An array of the (subject, verb, object) arguments sent to ActionCapture::record(),
     *                    or null if the 'from' and 'to' versions are the same.
     * @throws \Exception if $data['name'] is not 'framework' and does not start with 'standard/' or 'custom/'
     */
    public static function logToAcs(array $data) {
        if (($delta = Version::compareVersionNumbers($data['from'], $data['to'])) !== 0) {
            $object = array('from' => $data['from'], 'to' => $data['to'], 'mode' => isset($data['mode']) ? $data['mode'] : 'development');
            list($subject, $name, $widgetType) = self::getTypeAndName($data['name']);
            if ($widgetType) {
                $object['type'] = $widgetType;
                $object['name'] = $name;
            }
            $args = array($subject, 'version' . ($delta === 1 ? 'Down' : 'Up'), json_encode($object));
            \RightNow\ActionCapture::record($args[0], $args[1], $args[2]);
            return $args;
        }
    }

    /**
     * Record widget and framework version changes to the cp_object* tables.
     * @param array $data An array containing keys:
     *     'name' - Either 'framework' or the relative path to the widget being updated (beginning with 'standard/' or 'custom/').
     *     'to' - The version number being changed to.
     *     'mode' (optional) - One of 'development', 'staging' or 'production'. Defaults to 'development'.
     * @throws \Exception If an error was encountered.
     * @return array|null Returns an array from the version cache if an update was made, else null.
     */
    public static function logToDatabase(array $data) {
        list($objectType, $objectName, $widgetType) = self::getTypeAndName($data['name']);
        $type = $widgetType ?: 'framework';
        $mode = isset($data['mode']) ? $data['mode'] : 'development';
        $newVersion = $data['to'] ?: '0.0';
        if (!IS_HOSTED && !IS_TARBALL_DEPLOY && $newVersion === 'current') {
            $newVersion = self::getNonHostedWidgetVersion($data['name'], $type);
        }
        $versions = self::getVersionsFromDatabase($type, $objectName);
        $objectID = $versions['id'];
        $oldVersion = $versions[$mode];
        if ($oldVersion !== $newVersion) {
            if (!$objectID) {
                $objectID = Api::cp_object_create(array('name' => $objectName, 'type' => self::typeDefines($type)));
                if (!\RightNow\Utils\Framework::isValidID($objectID)) {
                    throw new \Exception(ConfigExternal::getMessage(CP_OBJECT_CREATION_ERROR_ENC_MSG));
                }
            }

            $result = Api::cp_object_update(array(
                'cp_object_id' => $objectID,
                'version' => array(
                    'version_item' => array(
                        'mode' => self::modeDefines($mode),
                        'interface_id' => Api::intf_id(),
                        'version' => $newVersion,
                        'action' => self::actionDefines($oldVersion ? 'update' : 'add'),
                    ),
                ),
            ));

            if ($result !== 1) {
                throw new \Exception(ConfigExternal::getMessage(CP_OBJECT_UPDATE_ERROR_ENC_MSG));
            }
            return self::versionCache($type, $objectName, $objectID, $mode, $newVersion);
        }
    }

    /**
     * Return an array of framework, php version and widget version differences, based on the frameworkVersion and widgetVersions files.
     * @param string|null $fromPath The path to the version files indicating the previous versions.
     *                              If specified as null, or the version files do not exist, the 'from version' will be null.
     * @param string|null $toPath The path to the version files indicating the new versions.
     *                            If specified as null, or the version files do not exist, the 'to version' will be null.
     * @throws \Exception if both $fromPath and $toPath are null.
     * @return array An associative array having 'framework', php version and widget paths as the key, with values specifying the from and to version.
     */
    public static function getVersionChanges($fromPath = null, $toPath = null) {
        if ($fromPath === null && $toPath === null) {
            throw new \Exception(ConfigExternal::getMessage(FROMPATH_TOPATH_NULL_MSG));
        }
        $changes = $from = $to = array();
        $standardize = function($value) {return $value === '' ? null : $value;};
        $findDiffs = function($arrayA, $arrayB, &$diffs, $reverse = false) use ($standardize) {
            foreach($arrayA as $key => $value) {
                $valueA = $standardize($value);
                $valueB = $standardize($arrayB[$key]);
                if ($valueA !== $valueB && !array_key_exists($key, $diffs)) {
                    $diffs[$key] = $reverse ? array($valueB, $valueA) : array($valueA, $valueB);
                }
            }
        };

        // Get framework changes
        $fromFramework = $fromPath ? $standardize(Version::getVersionFile("{$fromPath}frameworkVersion")) : null;
        $toFramework = $toPath ? $standardize(Version::getVersionFile("{$toPath}frameworkVersion")) : null;
        if ($fromFramework !== $toFramework) {
            $changes['framework'] = array($fromFramework, $toFramework);
        }

        //Get PHP version changes
        $fromPhpVersion = $fromPath ? Framework::getPhpVersionLabel(Version::getVersionFile("{$fromPath}phpVersion"), true) : null;
        $toPhpVersion = $toPath ? Framework::getPhpVersionLabel(Version::getVersionFile("{$toPath}phpVersion"), true) : null;
        if ($fromPhpVersion !== $toPhpVersion) {
            $changes['phpVersion'] = array($fromPhpVersion, $toPhpVersion);
        }

        // Get widget changes
        if ($fromPath && !($fromFramework === null || $fromFramework === '2.0')) {
            $from = Widgets::getDeclaredWidgetVersions($fromPath);
        }
        if ($toPath) {
            $to = Widgets::getDeclaredWidgetVersions($toPath);
        }
        if ($from || $to) {
            $findDiffs($from, $to, $changes);
            $findDiffs($to, $from, $changes, true);
        }

        return $changes;
    }

    /**
     * Records widget and framework version changes between version files found in $fromPath and $toPath and records the specified $targets.
     * @param string|null $fromPath The path to the version files indicating the previous versions.
     *        If specified as null, or the version files do not exist, the 'from version' will be null.
     * @param string|null $toPath The path to the version files indicating the new versions.
     *        If specified as null, or the version files do not exist, the 'to version' will be null.
     * @param string $mode One of 'development', 'staging' or 'production'
     * @param string|array $targets Specifies what targets to log to. Expects 'all' or an array of keys from self::$targets.
     *
     * @throws \Exception if both $fromPath and $toPath are null.
     * @return array An array of the changes found.
     */
    public static function recordVersionChanges($fromPath = null, $toPath = null, $mode = 'development', $targets = 'all') {
        $changes = self::getVersionChanges($fromPath, $toPath);
        if ($changes && is_array($changes)) {
            foreach($changes as $name => $pair) {
                self::log(array('name' => $name, 'from' => $pair[0], 'to' => $pair[1], 'mode' => $mode), $targets);
            }
        }
        return $changes;
    }

    /**
     * Initializes the framework and widget version cache.
     */
    public static function initializeCache() {
        self::$cache = array(
            'framework' => array(),
            'standard' => array(),
            'custom' => array(),
        );
        foreach(array_keys(self::$cached) as $key) {
            self::$cached[$key] = false;
        }
    }

    /**
     * Returns true if there are entries in the cp_object_versions table for $mode.
     * @param string $mode One of 'development', 'staging' or 'production'
     * @return Boolean True if any entries exist for $mode in the cp_object_versions table.
     * @throws \Exception If mode specified isn't valid
     */
    public static function versionsPopulatedForMode($mode) {
        if (!array_key_exists($mode, self::modeDefines())) {
            throw new \Exception(sprintf(ConfigExternal::getMessage(INVALID_MODE_PCT_S_COLON_LBL), $mode));
        }
        if (!self::$cached['versions']) {
            self::getVersionsFromDatabase('framework', 'framework', false);
        }
        return self::$cached[$mode];
    }

    /**
     * Return 'type' mapping as an array, or the corresponding value if $key specified.
     * @param mixed $type The array key if the value is desired, else null.
     * @param boolean $flip If true, flip the mapping array so the integer value is the key
     * @return mixed Returns an array if $key is null, else the corresponding value.
     */
    private static function typeDefines($type = null, $flip = false) {
        return self::lookup($type, $flip, array(
            'framework' => CP_OBJECT_TYPE_FRAMEWORK,       // 1
            'standard'  => CP_OBJECT_TYPE_WIDGET_STANDARD, // 2
            'custom'    => CP_OBJECT_TYPE_WIDGET_CUSTOM,   // 3
            'phpVersion' => CP_OBJECT_TYPE_PHP_VERSION,    // 4
        ));
    }

    /**
     * Return 'mode' mapping as an array, or the corresponding value if $key specified.
     * @param mixed $mode The array key if the value is desired, else null.
     * @param boolean $flip If true, flip the mapping array so the integer value is the key
     * @return mixed Returns an array if $key is null, else the corresponding value.
     */
    private static function modeDefines($mode = null, $flip = false) {
        return self::lookup($mode, $flip, array(
            'production'  => CP_OBJECT_MODE_PRODUCTION,  // 1
            'development' => CP_OBJECT_MODE_DEVELOPMENT, // 2
            'staging'     => CP_OBJECT_MODE_STAGING,     // 3
        ));
    }

    /**
     * Return 'action' mapping as an array, or the corresponding value if $key specified.
     * @param mixed $action The array key if the value is desired, else null.
     * @param boolean $flip If true, flip the mapping array so the integer value is the key
     * @return mixed Returns an array if $key is null, else the corresponding value.
     */
    private static function actionDefines($action = null, $flip = false) {
        return self::lookup($action, $flip, array(
            'add'    => ACTION_ADD, // 1
            'update' => ACTION_UPD, // 2
            'delete' => ACTION_DEL, // 3
        ));
    }

    /**
     * Return $items as an array, or the corresponding value if $key specified.
     * @param mixed $key The array key if the value is desired, else null.
     * @param boolean $flip If true, flip the $items array
     * @param array $items The array upon which the lookup is made.
     * @return mixed Returns $items if $key is null, else the corresponding value.
     */
    private static function lookup($key = null, $flip = false, array $items = array()) {
        if ($flip) {
            $items = array_flip($items);
        }
        return ($key === null) ? $items : $items[$key];
    }

    /**
     * Returns an array of version information for the specified $type and $name.
     *
     * @param string  $type    One of 'framework', 'standard' or 'custom'
     * @param string  $name    Either 'framework' or a relative widget path.
     * @param boolean $fetchObjects If True, also fetch from the objects report, to retrieve the core object names.
     *
     * @return array An associative array containing keys:
     *     - id: The cp_object_id
     *     - development: The version used in dev mode.
     *     - staging: The version used in staging mode.
     *     - production: The version used in production mode.
     */
    private static function getVersionsFromDatabase($type, $name, $fetchObjects = true) {
        static $objectIDs = array();
        if (!self::$cached['versions']) {
            $types = self::typeDefines(null, true);
            $modes = self::modeDefines(null, true);

            $reportFilter = get_instance()->model('Report')->getFilterByName(self::$reports['versions'], 'type')->result;
            $filters = array();
            $filters['type'] = new \stdClass;
            $filters['type']->filters = new \stdClass;
            $filters['type']->filters->fltr_id = $reportFilter['fltr_id'];
            $filters['type']->filters->oper_id = $reportFilter['oper_id'];
            $filters['type']->filters->rnSearchType = 'searchType';
            $filters['type']->filters->report_id = self::$reports['versions'];
            $filters['type']->filters->data[] = "1,2,3,4";

            foreach(self::getReportEntries(self::$reports['versions'], $filters, true) as $entry) {
                list($objectID, $objectType, $objectMode, $objectName, $version) = $entry;
                self::versionCache($types[$objectType], $objectName, $objectID, $modes[$objectMode], $version);
                $objectIDs[] = $objectID;
            }
            self::$cached['versions'] = true;
        }

        if ($fetchObjects && !self::$cached['objects']) {
            $reportFilter = get_instance()->model('Report')->getFilterByName(self::$reports['objects'], 'type')->result;
            $filters = array();
            $filters['type'] = new \stdClass;
            $filters['type']->filters = new \stdClass;
            $filters['type']->filters->fltr_id = $reportFilter['fltr_id'];
            $filters['type']->filters->oper_id = $reportFilter['oper_id'];
            $filters['type']->filters->rnSearchType = 'searchType';
            $filters['type']->filters->report_id = self::$reports['objects'];
            $filters['type']->filters->data[] = "1,2,3,4";
            // Fill in any entries from the cp_objects table that might not have been recorded above.
            // This can happen if cp_objects was populated from another interface on the site.
            foreach(self::getReportEntries(self::$reports['objects'], $filters, true) as $entry) {
                list($objectID, $objectName, $objectType) = $entry;
                if (!in_array($objectID, $objectIDs)) {
                    self::versionCache(self::typeDefines($objectType, true), $objectName, $objectID);
                }
            }
            self::$cached['objects'] = true;
        }

        return self::versionCache($type, $name);
    }

    /**
     * Retrieves and sets widget and framework version data from cache.
     * If $id is specified, the cache is updated, otherwise the cached value is returned.
     *
     * @param string       $type    One of 'framework', 'standard' or 'custom'
     * @param string       $name    Either 'framework' or a relative widget path.
     * @param integer|null $id      The cp_object_id from the cp_object tables.
     * @param string|null  $mode    One of 'development', 'staging', 'production' or null.
     * @param string|null  $version The version string.
     *
     * @return array An associative array containing keys:
     *     - id: The cp_object_id
     *     - development: The version used in dev mode.
     *     - staging: The version used in staging mode.
     *     - production: The version used in production mode.
     * @throws \Exception If ID, mode, or version are invalid
     */
    private static function versionCache($type, $name, $id = null, $mode = null, $version = null) {
        static $types;
        if (!isset($types)) {
            $types = self::typeDefines();
        }
        if (!array_key_exists($type, $types)) {
            throw new \Exception(sprintf(ConfigExternal::getMessage(INVALID_TYPE_PCT_S_COLON_LBL), $type));
        }
        if (!self::$cache) {
            self::initializeCache();
        }
        if (!isset(self::$cache[$type][$name])) {
            self::$cache[$type][$name] = array('id' => null, 'development' => null, 'staging' => null, 'production' => null);
        }

        if ($id) {
            $id = intval($id);
            if (!\RightNow\Utils\Framework::isValidID($id)) {
                throw new \Exception(sprintf(ConfigExternal::getMessage(INVALID_ID_PCT_S_COLON_LBL), $id));
            }
            static $modes;
            if (!isset($modes)) {
                $modes = self::modeDefines();
            }
            if ($mode && !array_key_exists($mode, $modes)) {
                throw new \Exception(sprintf(ConfigExternal::getMessage(INVALID_MODE_PCT_S_COLON_LBL), $mode));
            }
            if (($version && !$mode) || ($mode && !$version)) {
                throw new \Exception(ConfigExternal::getMessage(MODE_VERSION_MSG));
            }
            self::$cache[$type][$name]['id'] = $id;
            if ($mode) {
                self::$cached[$mode] = true;
                self::$cache[$type][$name][$mode] = trim("$version");
            }
        }

        return self::$cache[$type][$name];
    }

    /**
     * Return entries from the hidden canned report indicated by $reportID
     * @param integer $reportID Report ID to retrieve
     * @param array $filters Filters to apply to report
     * @param bool $forceCacheBust Whether to willfully ignore any previously cached data
     * @return array Report data
     */
    private static function getReportEntries($reportID, array $filters = array(), $forceCacheBust = false) {
        return get_instance()->model('Report')->getDataHTML(
            $reportID,
            null,
            $filters,
            array(),
            true,
            $forceCacheBust
        )->result['data'] ?: array();
    }

    /**
     * Given a relative widget path or 'framework', return a 3 element array containing type, name and widgetType|null:
     *
     * Examples:
     *     INPUT                     OUTPUT
     *     ------------------------  ----------------------------------------------------
     *     framework                 array('framework', 'framework',         null)
     *     standard/input/TextInput  array('widget',    'input/TextInput',  'standard')
     *     custom/input/TextInput    array('widget',    'input/TextInput',  'custom')
     *
     * @param string $frameworkOrWidgetName A relative widget path or 'framework'
     * @throws \Exception if $frameworkOrWidgetName is not 'framework' and does not start with 'standard/' or 'custom/'
     * @return array
     */
    private static function getTypeAndName($frameworkOrWidgetName) {
        static $nameCache;
        if (!isset($nameCache[($name = trim($frameworkOrWidgetName))]) || !($cachedValue = $nameCache[($name = trim($frameworkOrWidgetName))])) {
            if ($name === 'framework') {
                $cachedValue = $nameCache[$name] = array('framework', 'framework', null);
            }
            else if ($name == 'phpVersion') {
                $cachedValue = $nameCache[$name] = array('phpVersion', 'phpVersion', 'phpVersion');
            }
            else {
                $parts = explode('/', $name);
                $widgetType = $parts[0];
                if ($widgetType !== 'standard' && $widgetType !== 'custom') {
                    throw new \Exception(ConfigExternal::getMessage(WIDGET_NAMES_BEG_STD_S_CUST_S_MSG));
                }
                $cachedValue = $nameCache[$name] = array('widget', implode('/', array_slice($parts, 1)), $widgetType);
            }
        }
        return $cachedValue;
    }

    /**
     * Returns the agent account ID, or null if not logged in.
     * @return integer|null
     */
    private static function getAccountID() {
        if (self::$accountID === false) {
            self::$accountID = ($account = get_instance()->_getAgentAccount()) ? $account->acct_id : null;
        }
        return self::$accountID;
    }

    /**
     * Returns the version from the widget's info.yml since we store 'current' in the widgetVersions file.
     * @param string $widgetName Name of widget
     * @param string $type One of 'standard' or 'custom'
     * @return string The version string.
     */
    private static function getNonHostedWidgetVersion($widgetName, $type) {
        $path = ($type === 'standard' ? CORE_FILES : CUSTOMER_FILES) . "widgets/$widgetName/info.yml";
        $contents = str_replace("\t", "  ", @file_get_contents($path));
        $widgetInfo = @yaml_parse($contents);
        return $widgetInfo['version'] ?: '1.2.3';
    }
}
