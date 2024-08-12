<?
namespace RightNow\Internal\Utils\Deployment;

use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\PathInfo,
    RightNow\Internal\Libraries\Widget\Helpers\Loader,
    RightNow\Internal\Libraries\Widget\ExtensionLoader;

class WidgetHelperOptimization {
    /**
     * Retrieves all standard shared view helpers.
     * @return string helper content with leading opening php
     *                       removed and namespace statements
     *                       transformed into bracket syntax
     */
    static function buildStandardSharedHelpers () {
        static $phpContents;
        if (is_null($phpContents)) {
            $phpContents = self::getSharedHelperContent(CPCORE . 'Helpers/', false);
        }

        return $phpContents;
    }

    /**
     * Retrieves all custom shared view helpers. Custom helpers
     * must be registered in extensions.yml or not share the same
     * path as a standard view helper.
     * @return string helper content with leading opening php
     *                       removed and namespace statements
     *                       transformed into bracket syntax
     */
    static function buildCustomSharedHelpers () {
        static $phpContents;
        if (is_null($phpContents)) {
            $phpContents = self::getSharedHelperContent(APPPATH . 'helpers/', true);
        }

        return $phpContents;
    }

    /**
     * Validates a widget helper's php helper contents.
     * @param  PathInfo $widget  Widget to examine
     * @param  string   $content Helper content
     * @return string|boolean            If invalid, string expected namespace + classname
     *                                      If valid, true
     */
    static function validateWidgetHelperContents (PathInfo $widget, $content) {
        $isCustom = $widget->type === 'custom';
        if (!self::validateHelperContents($content, $widget->className, $isCustom)) {
            return self::helperNamespace($isCustom) . "\\" . self::helperClassname($widget->className);
        }
        return true;
    }

    /**
     * Validates a shared helper's php helper contents.
     * @param  string $content       PHP helper code
     * @param  string $pathToContent File path to helper
     * @param  boolean $isCustom     Whether the helper is custom or core
     * @return boolean True If valid, False otherwise
     */
    private static function validateSharedHelperContents ($content, $pathToContent, $isCustom) {
        return self::validateHelperContents($content, basename($pathToContent, '.php'), $isCustom);
    }

    /**
     * Retrieves all php files in the specified directory, verifies them as valid helper code, and combines their content.
     * @param  string $dir    Parent dir to look within
     * @param  boolean $custom Whether the path being searched is custom. If it is,
     *                         the extension rules are checked.
     * @return string         Contents of all php files combined with leading opening
     *                                 php removed and namespace statements transformed
     *                                 into bracket syntax
     */
    private static function getSharedHelperContent ($dir, $custom) {
        $helpers = array();

        if (!FileSystem::isReadableDirectory($dir)) return $helpers;

        $files = FileSystem::listDirectory($dir, false, true, array('match', '/.*\.php$/'));
        $extensionLoader = new ExtensionLoader('viewHelperExtensions', 'helpers');

        foreach ($files as $filePath) {
            $content = null;
            if (Text::endsWith($filePath, '.test.php')) continue;

            if ($custom) {
                // Helper must either be registered or not have the same path as a core helper.
                if ($extensionLoader->extensionIsRegistered(basename($filePath, '.php')) || !$extensionLoader->coreFileExists($filePath)) {
                    $content = $extensionLoader->getContentFromCustomerDirectory($filePath);
                }
            }
            else {
                $content = $extensionLoader->getContentFromCoreDirectory($filePath);
            }

            if ($content && self::validateSharedHelperContents($content, $filePath, $custom)) {
                $helpers []= $content;
            }
        }
        return self::joinHelperContent($helpers);
    }

    /**
     * Joins the content in the specified array.
     * @param array $helpers Array containing php content
     * @return string         Contents of all php files combined with leading opening
     *                                 php removed and namespace statements transformed
     *                                 into bracket syntax
     */
    private static function joinHelperContent (array $helpers) {
        array_walk($helpers, function (&$helperCode) {
            $helperCode = CodeWriter::deleteOpeningPHP(CodeWriter::modifyPhpToAllowForCombination($helperCode));
        });

        return implode("\n", array_values($helpers));
    }

    /**
     * Validates that the helper php code has the
     * correct namespace and class name.
     * @param  string $content  PHP code
     * @param  string $class    Widget's folder / classname
     * @param  boolean $isCustom Whether the widget is custom
     * @return boolean           Whether $content has the correct
     *                                   namespace and class within it
     */
    private static function validateHelperContents ($content, $class, $isCustom) {
        $class = self::helperClassname($class);
        $classRegex = "/class\s+{$class}/";
        $namespaceRegex = sprintf("@namespace\s+%s@", str_replace('\\', '\\\\', self::helperNamespace($isCustom)));

        return preg_match($namespaceRegex, $content) && preg_match($classRegex, $content);
    }

    /**
     * Returns the namespace of the helper class.
     * @param  boolean $isCustom Whether the helper is
     *                         custom or core
     * @return string         Helper namespace
     */
    private static function helperNamespace ($isCustom) {
        return $isCustom
            ? Loader::$customHelperNamespacePrefix
            : Loader::$coreHelperNamespacePrefix;
    }

    /**
     * Returns the helper class name.
     * @param  string $class Widget's folder / classname (if widget helper)
     *                       filename (if shared helper)
     * @return string        The $class with Helper appended
     */
    private static function helperClassname ($class) {
        return "{$class}Helper";
    }
}
