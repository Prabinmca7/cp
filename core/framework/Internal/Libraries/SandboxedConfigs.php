<?php
namespace RightNow\Internal\Libraries;
use RightNow\Utils\FileSystem;

/**
 * A collection of static methods dealing with all of the sandboxed configurations.
 * Sandboxed means a change to the configuration does not immediately affect staging
 * or production, and needs to be staged and/or promoted for the change to appear.
 */
final class SandboxedConfigs{
    const FILE_NAME = 'sandboxedConfigs';
    const OLD_FILE_NAME = '_deprecated.php';

    private static $modeCache;
    private static $stagingAddedToModeCache = false;
    private static $configCache;
    private static $locationCache;
    private static $initialized = false;

    /**
     * Return an array with just the basic config information.
     * Use configValueFromMode method below to obtain config value settings.
     * @return array
     */
    public static function configurations() {
        // NOTE: doing strtoupper() below so configuration labels don't get turned into their defines.
        return array(
            'loginRequired' => array(
                'displayName' => \RightNow\Utils\Config::getMessage(LOGIN_REQD_LBL),
                'configName' => strtoupper('cp_contact_login_required'),
                'configSlot' => CP_CONTACT_LOGIN_REQUIRED,
            ),
        );
    }

    /**
     * Return an associative array containing the config value (e.g. 'js' => 0, 'loginRequired' => 1, ...).
     * @return array
     */
    public static function configValues() {
        $configs = array();
        foreach(array_keys(self::configurations()) as $config) {
            $configs[$config] = self::configValue($config);
        }
        return $configs;
    }

    /**
     * Return an array of environment names ('production', 'staging_01', 'development')
     * along with their display names and path to sandboxedConfigs where applicable.
     * @param bool $sortWithProductionFirst If True, return ('production, 'staging_01', 'development'), else reversed.
     * @return array
     */
    public static function modes($sortWithProductionFirst = true) {
        require_once CPCORE . 'Internal/Libraries/Staging.php';
        require_once CPCORE . 'Internal/Utils/Admin.php';
        $sortIndex = 0;
        foreach(\RightNow\Internal\Utils\Admin::getEnvironmentModes() as $mode => $displayName) {
            $directoryPath = null;
            if ($mode === 'reference') {
                continue;
            }
            else if ($mode === 'production') {
                $directoryPath = self::productionConfigPath();
            }
            else if (Staging::isValidStagingName($mode)) {
                $directoryPath = OPTIMIZED_FILES . "staging/$mode/optimized/config";
            }
            $modes[$mode] = array('displayName' => $displayName, 'directoryPath' => $directoryPath, 'sortIndex' => $sortIndex++);
        }
        if (!$sortWithProductionFirst) {
            uasort($modes, function($a, $b) {
                return ($a['sortIndex'] > $b['sortIndex']) ? -1 : 1;
            });
        }
        return $modes;
    }

    /**
     * Return the actual config value from config bases.
     * @param string $config Name of config
     * @return mixed Usually bool
     */
    public static function configValue($config) {
        self::validateConfig($config);
        self::initialize();
        $values = self::$configCache[$config];
        if (!isset($values['configValue'])) {
            $values['configValue'] = \RightNow\Utils\Config::getConfig($values['configSlot']);
        }
        return $values['configValue'];
    }

    /**
     * Determines if config name is valid
     * @param string $config The config name to check
     * @return bool Whether config is valid
     */
    public static function isValidConfig($config) {
        return array_key_exists($config, self::configurations());
    }

    public static function isValidMode($mode) {
        if (array_key_exists($mode, self::$modeCache)) {
            return true;
        }

        if (!self::$stagingAddedToModeCache) {
            foreach(self::modes() as $key => $values) {
                self::$modeCache[$key] = $values['directoryPath'];
            }
            self::$stagingAddedToModeCache = true;
            return array_key_exists($mode, self::$modeCache);
        }

        return false;
    }

    /**
     * Obtain config value from either the config itself, or the sandboxedConfigs file in the mode's location.
     * @param string $config One of the defined sandboxed config names ('loginRequired', 'js').
     * @param string|null $mode One of the defined environment modes ('production', 'staging_01', 'production').
     * @param bool $fetchFromCache If false, don't fetch from cache, instead fetch from disk and update cache.
     * @throws \Exception If invalid $config or $mode specified.
     * @return array Value of array element 0 [bool]: config value (either from config or sandboxedConfigs file).
     *               array element 1 [object|null]: Exception object, if one is encountered fetching config from file or configbase.
     */
    public static function configValueFromMode($config, $mode = null, $fetchFromCache = true) {
        self::validateConfig($config);
        self::initialize();
        if ($mode !== null && !self::isValidMode($mode)) {
            throw new \Exception("Invalid mode '$mode'");
        }

        $modeToCheck = null;
        if ($mode === null) {
            if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
                $modeToCheck = 'production';
            }
            else if (defined('IS_STAGING') && IS_STAGING) {
                $modeToCheck = STAGING_LOCATION;
            }
        }
        else if ($mode !== 'development') {
            $modeToCheck = $mode;
        }

        $exception = null;
        if ($modeToCheck) {
            try {
                $configs = self::configArrayFromMode($modeToCheck, $fetchFromCache);
                if (!array_key_exists($config, $configs)) {
                    throw new ConfigFileException(\RightNow\Utils\Config::getMessage(SANDBOXED_CONFIG_FILE_MISSING_KEY_LBL), $config);
                }
                return array($configs[$config], $exception);
            }
            catch (\Exception $e) {
                $exception = $e;
            }
        }

        try {
            return array(self::configValue($config), $exception);
        }
        catch (\Exception $e) {
            return array(null, $exception ?: $e);
        }
    }

    /**
     * Write $configs array to sandboxedConfigs file as a serialized array.
     *
     * @param array|null $configArray Array in the form array('js' => 0, 'loginRequired' => 0).
     * @param string $directoryPath Absolute path to sandboxedConfigs (minus the file name).
     * @throws \Exception if $configArray is not an array or is empty
     */
    public static function writeConfigArrayToFile($configArray, $directoryPath) {
        self::validateDirectoryPath($directoryPath);
        if (!is_array($configArray) || empty($configArray)) {
            throw new \Exception('Invalid configArray ' . var_export($configArray, true));
        }

        umask(IS_HOSTED ? 0002 : 0);
        FileSystem::filePutContentsOrThrowExceptionOnFailure(self::filePathFromDirectory($directoryPath), serialize($configArray));
    }

    /**
     * Return absolute path to sandboxedConfigs based on $mode.
     * @param string $mode One of the defined modes ('development', 'staging_01', 'production').
     * @return string Absolute path to sandboxedConfigs.
     * @throws \Exception If mode is invalid
     */
    public static function filePathFromMode($mode) {
        self::initialize();
        if (!self::isValidMode($mode) || !$directoryPath = self::$modeCache[$mode]) {
            throw new \Exception("Invalid mode '$mode'");
        }
        return self::filePathFromDirectory($directoryPath);
    }

    /**
     * Return config array from sandboxedConfigs file indicated by mode.
     * @param string $mode One of the defined modes ('development', 'staging_01', 'production').
     * @param bool $fetchFromCache If false, don't fetch from cache, instead fetch from disk and update cache.
     * @return array|null Config array if file exists, otherwise null;
     */
    public static function configArrayFromMode($mode, $fetchFromCache = true) {
        return self::configArrayFromFile(dirname(self::filePathFromMode($mode)), $fetchFromCache);
    }

    /**
     * Return config array from sandboxedConfigs file (or old _deprecated.php) file indicated by path.
     * @param string $directoryPath Absolute path to sandboxedConfigs file (minus the file name).
     * @param bool $fetchFromCache If false, don't fetch from cache, instead fetch from disk and update cache.
     * @return array|null Config array if file exists, otherwise null;
     * @throws ConfigFileException If file doesn't exist or contents are messed up somehow
     */
    public static function configArrayFromFile($directoryPath, $fetchFromCache = true) {
        $cache = self::$locationCache;
        if ($fetchFromCache && isset($cache[$directoryPath])) {
            return $cache[$directoryPath];
        }

        self::validateDirectoryPath($directoryPath);

        $filePath = self::filePathFromDirectory($directoryPath);
        if (!FileSystem::isReadableFile($filePath)) {
            $oldFilePath = "$directoryPath/" . self::OLD_FILE_NAME;
            if (FileSystem::isReadableFile($oldFilePath)) {
                return self::configArrayFromDeprecatedFile($directoryPath);
            }
            else {
                throw new ConfigFileException(\RightNow\Utils\Config::getMessage(SANDBOXED_CONFIG_FILE_MISSING_LBL), $filePath);
            }
        }

        try {
            $configs = $cache[$directoryPath] = unserialize(file_get_contents($filePath));
        }
        catch (\Exception $e) {
            $configs = null;
        }

        if (!is_array($configs) || empty($configs)) {
            throw new ConfigFileException(\RightNow\Utils\Config::getMessage(FILE_APPEAR_CONT_VALID_SERIALIZED_LBL), $filePath);
        }
        return $configs;
    }

    /**
     * Prior to 11.2, we used to store configs in a file named _deprecated.php as a function definition
     * that had to have require_once() run on it to determine the array contents. This became
     * prohibitive when we started having multiple environments and doing a require_once() more than once
     * would throw a php "function redeclare" error. In addition, the function name within could be one
     * of two things as the function name changed over the course of a few versions. Below we look for both
     * the old and new function names, and don't do an include on the file to avoid the "function redeclare" condition.
     *
     * The deployer should be converting the old _deprecated.php -> sandboxedConfigs
     *
     * @param string $directoryPath Absolute path to _deprecated.php, not including file name.
     * @return array
     * @throws ConfigFileException If file didn't contain expected function name
     */
    private static function configArrayFromDeprecatedFile($directoryPath) {
        $filePath = "$directoryPath/" . self::OLD_FILE_NAME;
        $contents = file_get_contents($filePath);
        if (!$functionName = self::getFunctionNameFromContents($contents)) {
            throw new ConfigFileException(\RightNow\Utils\Config::getMessage(FILE_CONT_EXPECTED_FUNCTION_NAME_LBL), $filePath);
        }

        $functionPrefix = function($nameOfFunction, $addPhpTag = true) {
            return (($addPhpTag) ? '<?' : '') . "function $nameOfFunction(){";
        };

        $counter = 0;
        $uniqueFunctionName = "{$functionName}_{$counter}";
        while(function_exists($uniqueFunctionName)) {
            $uniqueFunctionName = "{$functionName}_" . $counter++;
        }
        eval(str_replace($functionPrefix($functionName), $functionPrefix($uniqueFunctionName, false), $contents));
        return $uniqueFunctionName();

    }

    /**
     * Look for one of the two historical function names stored in _deprecated.php.
     * @param string $contents Content to search through
     */
    private static function getFunctionNameFromContents($contents) {
        foreach (array('_getDeployedDeprecatedConfigValues', 'getDeployedDeprecatedConfigValues') as $functionName) {
            if (\RightNow\Utils\Text::stringContains($contents, $functionName)) {
                return $functionName;
            }
        }
    }

    private static function validateConfig($config) {
        if (!self::isValidConfig($config)) {
            throw new \Exception("Invalid config: '$config'");
        }
    }

    private static function productionConfigPath() {
        return OPTIMIZED_FILES . 'production/optimized/config';
    }

    private static function initialize() {
        if (!self::$initialized) {
            if (!isset(self::$modeCache)) {
                self::$modeCache = array('production' => self::productionConfigPath(), 'development' => null);
            }

            if (!isset(self::$configCache)) {
                self::$configCache = self::configurations();
            }
            self::$initialized = true;
        }
    }

    private static function validateDirectoryPath($directoryPath) {
        if (!FileSystem::isReadableDirectory($directoryPath)) {
            throw new ConfigFileException('Invalid directory: ', $directoryPath);
        }
    }

    private static function filePathFromDirectory($directoryPath) {
        return "$directoryPath/" . self::FILE_NAME;
    }
}

final class ConfigFileException extends \RightNow\Internal\Exception {
    function __construct($message, $filePath) {
        parent::__construct("$message: '$filePath'");
    }
}