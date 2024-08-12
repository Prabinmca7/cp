<?

namespace RightNow\Internal\Libraries\Widget\Helpers;

use RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Widget\PathInfo;

/**
 * Performs loading and retrieval of widget helper class names
 * that are to be instantiated and assigned onto a widget instance.
 */
class Loader {
    /**
     * Base widget helper
     * @var string
     */
    public static $baseHelper = 'RightNow\Libraries\Widget\Helper';

    /**
     * Namespace prefix of core helpers
     * @var string
     */
    public static $coreHelperNamespacePrefix = 'RightNow\Helpers';

    /**
     * Namespace prefix of custom helpers
     * @var string
     */
    public static $customHelperNamespacePrefix = 'Custom\Helpers';

    /**
     * Loader class to instantiate in order to load helpers.
     * @var string
     */
    private static $loaderClass = 'RightNow\Internal\Libraries\Widget\ExtensionLoader';

    /**
     * Cache of loaded helper classes.
     * @var array
     */
    private static $loadedClasses = array();

    /**
     * Loads and returns the shared helper class names to
     * attach onto the widget.
     * @param  array $helperNames String helper names
     * @return array              Loaded helper classnames
     *                                   ready for instantiation
     */
    static function fetchSharedHelpers (array $helperNames) {
        $helperNames = array_unique($helperNames);
        return (IS_OPTIMIZED)
            ? self::fetchOptimizedSharedHelpers($helperNames)
            : self::fetchNonOptimizedSharedHelpers($helperNames);
    }

    /**
     * Loads the widget's own `viewHelper.php` helper.
     * @param  string $widgetPath Widget's relative path
     * @return string             Widget helper classname or
     *                                   `$baseHelper` if the
     *                                   widget doesn't have
     *                                   a helper
     */
    static function fetchWidgetHelper ($widgetPath) {
        // In an optimized mode, the helper class is already loaded.
        $className = self::loadedClassNameForWidgetHelper($widgetPath);
        if ($className || ($className = self::includeWidgetHelperClass($widgetPath))) return $className;

        return self::$baseHelper;
    }

    /**
     * Constructs the classname of the widget helper, given
     * its relative path.
     * @param  string $helperName Helper's file name
     * @return string             Class name
     */
    static function coreHelperClassName ($helperName) {
        return self::$coreHelperNamespacePrefix . "\\{$helperName}Helper";
    }

    /**
     * Constructs the classname of the custom widget helper, given
     * its relative path.
     * @param  string $helperName Helper's file name
     * @return string             Class name
     */
    static function customHelperClassName ($helperName) {
        return self::$customHelperNamespacePrefix . "\\{$helperName}Helper";
    }

    /**
     * Returns the helper class names to attach onto the widget.
     * @param  array $helperNames String helper names
     * @return array            Helper classnames ready for instantiation
     */
    private static function fetchOptimizedSharedHelpers (array $helperNames) {
        $loaded = array();
        foreach ($helperNames as $name) {
            if ($className = self::optimizedSharedHelper($name)) {
                $loaded[$name] = $className;
            }
        }

        return $loaded;
    }

    /**
     * Loads shared helper classes.
     * @param  array $helperNames String helper names
     * @return array              Loaded helper classnames
     */
    private static function fetchNonOptimizedSharedHelpers (array $helperNames) {
        $loaded = array();

        foreach ($helperNames as $helperName) {
            if ($className = self::nonOptimizedSharedHelper($helperName)) {
                $loaded[$helperName] = $className;
            }
            else {
                echo $errorMessage = sprintf(Config::getMessage(COULD_NOT_FIND_VIEW_HELPER_NAMED_S_MSG), "$helperName.php");
                Framework::addDevelopmentHeaderError($errorMessage);
            }
        }

        return $loaded;
    }

    /**
     * Includes the widget's own helper class.
     * This operation is needed only in dev mode.
     * @param  string $widgetPath Relative path to widget
     * @return string|null             Loaded class name
     */
    private static function includeWidgetHelperClass ($widgetPath) {
        if (!$pathInfo = Registry::getWidgetPathInfo($widgetPath)) return;

        $className = self::helperClassName($pathInfo);
        $file = "{$pathInfo->absolutePath}/viewHelper.php";

        if ($parent = self::getWidgetParent($pathInfo)) {
            $parentClass = self::fetchWidgetHelper($parent);
        }

        if (FileSystem::isReadableFile($file)) {
            $included = include_once $file;
            if (!$included || !class_exists($className)) {
                echo $errorMessage = sprintf(Config::getMessage(THE_VIEW_HELPER_CLASS_FOR_PCT_S_MSG), $pathInfo->relativePath, $className);
                return Framework::addDevelopmentHeaderError($errorMessage);
            }
            return self::$loadedClasses[$widgetPath] = $className;
        }
        else if (isset($parentClass) && $parentClass) {
            return $parentClass;
        }
    }

    /**
     * Loads the optimized helper. On normal page renders,
     * the class will already be included within the page's
     * php content. For other rendering flows (like view partial
     * rendering on the server side in response to ajax requests),
     * it needs to be included from a rollup helper file.
     * @param  string $helperName Helper file name
     * @return string|null             Loaded classname if loaded
     */
    private static function optimizedSharedHelper($helperName) {
        static $includedCustomSharedHelpers = false;

        if ($className = self::loadedClassNameForHelper($helperName, true)) return $className;

        if (!$includedCustomSharedHelpers) {
            $loader = self::getLoader();
            $loader->loadContentFromCustomerDirectory(FileSystem::getLastDeployTimestampFromFile() . '.php');
            $includedCustomSharedHelpers = true;
            return self::loadedClassNameForHelper($helperName, true);
        }
    }

    /**
     * Loads the non-optimized helper. Caches results
     * so that source files aren't needlessly re-included.
     * @param  string $helperName Helper file name
     * @return string|null             Loaded classname if loaded
     */
    private static function nonOptimizedSharedHelper ($helperName) {
        static $includedHelpers = array();

        if (array_key_exists($helperName, $includedHelpers) && !is_null($includedHelpers[$helperName])) {
            return $includedHelpers[$helperName];
        }

        $loader = self::getLoader();
        if ($contentLoaded = $loader->loadExtension($helperName, "{$helperName}.php")){
            return $includedHelpers[$helperName] = self::loadedClassNameForHelper($helperName, isset($contentLoaded['custom']) && $contentLoaded['custom'] ? $contentLoaded['custom'] : false);
        }
    }

    /**
     * Creates a loader that loads the source files.
     * Uses the static `$loaderClass` variable as
     * the loader's classname so that it can be mocked
     * out for testing.
     * @return RightNow\Internal\Libraries\Widget\ExtensionLoader loader instance
     */
    private static function getLoader () {
        static $loaderInstance;

        if (is_null($loaderInstance)) {
            $loaderClass = self::$loaderClass;
            $loaderInstance = new $loaderClass('viewHelperExtensions', 'helpers');
        }

        return $loaderInstance;
    }

    /**
     * Returns the already-loaded classname for the helper.
     * @param  string  $helperName Helper file name
     * @param  boolean $preferCustom Whether or not we should prefer using the custom helper.
     * @return string|null             Helper's fully-namespaced class name
     *                                          or null if not loaded
     */
    private static function loadedClassNameForHelper ($helperName, $preferCustom = false) {
        if (isset(self::$loadedClasses[$helperName]) && $alreadyLoaded = self::$loadedClasses[$helperName]) return $alreadyLoaded;

        $classNames = array(self::coreHelperClassName($helperName), self::customHelperClassName($helperName));

        if ($preferCustom) {
            $classNames = array_reverse($classNames);
        }

        foreach ($classNames as $className) {
            if (class_exists($className)) return self::$loadedClasses[$helperName] = $className;
        }
    }

    /**
     * Returns the already-loaded classname for the widget helper class.
     * @param  string $widgetRelativePath Widget's relative path
     * @return string|null                     Helper's fully-namespaced class name
     *                                                  or null if not loaded
     */
    private static function loadedClassNameForWidgetHelper ($widgetRelativePath) {
        return self::loadedClassNameForHelper(basename($widgetRelativePath));
    }

    /**
     * Returns the relative path name of the widget's
     * immediate parent.
     * @param  string|object $widget Either relative widget path
     *                               or a PathInfo instance
     * @return string|null         Parent's relative path
     */
    private static function getWidgetParent ($widget) {
        if (is_string($widget)) {
            $widget = Registry::getWidgetPathInfo($widget);
        }

        if ($widget) {
            // The structure of the data is different depending on the site mode :(
            $meta = array_key_exists('meta', $widget->meta) ? $widget->meta['meta'] : $widget->meta;

            if (array_key_exists('extends', $meta)) {
                return $meta['extends']['widget'];
            }
        }
    }

    /**
     * Determines the helper's class name based on
     * the widget's type (custom|standard)
     * @param  PathInfo $widget Widget pathinfo instance
     * @return string           Helper's expected class name
     */
    private static function helperClassName (PathInfo $widget) {
        return $widget->type === 'custom'
            ? self::customHelperClassName($widget->className)
            : self::coreHelperClassName($widget->className);
    }
}
