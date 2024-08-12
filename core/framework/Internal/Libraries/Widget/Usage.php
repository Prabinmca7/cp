<?

namespace RightNow\Internal\Libraries\Widget;

use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Utils\Version,
    RightNow\Internal\Libraries\Widget\Locator,
    RightNow\Internal\Libraries\Widget\Registry;

/**
 * Locates widgets within files and produces the location within files of
 * the found widgets.
 */
final class Usage {
    /**
     * Unix timestamp of when an item in the cache was last saved.
     */
    public $lastCheckTime = 0;

    /**
     * Seconds to cache for.
     */
    protected $cacheTime = 600; // 10 minutes

    /**
     * Cache key prefix.
     */
    protected $cacheKeyPrefix;

    /**
     * Cache object that the constructor will instantiate with
     * the injected cache class name. Expects to conform to the
     * Memcache protocol.
     */
    private $cache = null;

    /**
     * Supplied via the constructor.
     * @var string
     */
    private $widgetPath = '';

    /**
     * Types of views.
     */
    const VIEW = 'view';
    const CUSTOM_WIDGET = 'custom';
    const STANDARD_WIDGET = 'standard';

    /**
     * Markers used when producing code snippets of widget matches.
     */
    public static $snippetBreak = "CPREPLACECP\n";
    public static $startWidgetPathMatch = "[startwidgetmatch]";
    public static $endWidgetPathMatch = "[endwidgetmatch]";

    /**
     * @param string $widgetPath Relative path to widget
     * @param string $cacheClass Class name of cache class to use
     */
    function __construct ($widgetPath, $cacheClass) {
        $this->widgetPath = $widgetPath;
        $this->cache = new $cacheClass($this->cacheTime);
        $this->cacheKeyPrefix = basename(str_replace('\\', '/', get_class($this)));
    }

    /**
     * Returns all found references to the widget on the filesystem.
     * @return array|false False if no references found; Array otherwise:
     *                           keys are view paths; values are types (view|custom|standard)
     */
    function getReferences () {
        return $this->cachedMapTypesOfResults($this->cachedReferencesInViews());
    }

    /**
     * Produces the code in the specified file that the widget is contained within.
     * @param  string $filePath Relative path to file
     * @param  string $fileType One of view|custom|standard
     * @param  boolean $includeDeactivated Include deactivated widget
     * @return string Code snippet
     * @throws \Exception If An invalid type or widget path is given
     */
    function getReferenceInFile ($filePath, $fileType, $includeDeactivated = false) {
        return $this->cachedReferenceInFile($filePath, $fileType, $includeDeactivated);
    }

    /**
     * Gets the widget reference and caches the result.
     * @param  string $filePath Relative path to file
     * @param  string $fileType One of view|custom|standard
     * @param  boolean $includeDeactivated Include deactivated widget
     * @return string Code snippet
     */
    function cachedReferenceInFile ($filePath, $fileType, $includeDeactivated = false) {
        $key = $this->cacheKeyPrefix . "_{$this->widgetPath}_{$fileType}_{$filePath}";

        if ($cached = $this->getFromCache($key)) return $cached;
        Registry::setSourceBasePath(CUSTOMER_FILES);
        $matches = $this->findMatchesInFile($this->getAbsolutePathFromFileType($fileType, $filePath), array(
            $this->widgetPath,
            self::getShortWidgetPath($this->widgetPath, $includeDeactivated)
        ));

        return $this->cache($key, $matches);
    }

    /**
     * Gets and caches widget references.
     * @return array|false False if no references found; Array otherwise:
     *                           keys are view paths; values are types (view|custom|standard)
     */
    private function cachedReferencesInViews () {
        $cacheKey = $this->cacheKeyPrefix . "_WidgetReferences_{$this->widgetPath}";
        $cachedResult = $this->getFromCache($cacheKey);

        return is_null($cachedResult) ?
            $this->cache($cacheKey, $this->findReferencesInViews()) :
            $cachedResult;
    }

    /**
     * Finds references to the widget in view files.
     * @return array|false False if no references found; Array otherwise:
     *                           keys are view paths; values are types (view|custom|standard)
     */
    private function findReferencesInViews () {
        Registry::setSourceBasePath(CUSTOMER_FILES);

        $customerViewsPrefix = CUSTOMER_FILES . 'views/';
        $widgetLocator = new Locator();
        $widgetLocator->includeParents = false;

        foreach (FileSystem::getDirectoryPhpFiles($customerViewsPrefix) as $viewFile) {
            $widgetLocator->addContentToProcess($viewFile, @file_get_contents("{$customerViewsPrefix}{$viewFile}"));
        }

        $widgetReferences = array_map(function($item) { return $item['referencedBy']; }, $widgetLocator->getWidgets());

        return array_key_exists($this->widgetPath, $widgetReferences) ? $widgetReferences : false;
    }

    /**
     * Caches the results of mapping found references in files to
     * file paths and the type of file the widget was found in.
     * @param  bool|array $widgetReferences Found widget references or false if none
     *                                      were found
     * @return bool|array                   $widgetReferences if falsey otherwise the result
     *                                                        of #mapTypesOfViews
     */
    private function cachedMapTypesOfResults ($widgetReferences) {
        if (!$widgetReferences) return $widgetReferences;

        $cacheKey = $this->cacheKeyPrefix . "_Views_{$this->widgetPath}";

        return $this->getFromCache($cacheKey) ?: $this->cache($cacheKey, $this->mapTypesOfViews($widgetReferences));
    }

    /**
     * Produces an array keyed by file path whose values are the
     * type of view the widget match occurred in.
     * @param array $widgetReferences Results of #findReferencesInViews
     * @return array File path keys, values of the view type
     */
    private function mapTypesOfViews (array $widgetReferences) {
        $views = array();

        foreach ($widgetReferences[$this->widgetPath] as $file) {
            if (Text::beginsWith($file, '/')) {
                // Absolute path to widget view.
                $file = Text::getSubstringBefore($file, '/view.php');
                if ($partialFilePath = Text::getSubstringAfter($file, CUSTOMER_FILES . 'widgets/')) {
                    $fileType = self::CUSTOM_WIDGET;
                }
                else {
                    $partialFilePath = Text::getSubstringAfter($file, CORE_WIDGET_FILES);
                    $fileType = self::STANDARD_WIDGET;
                }
                $partialFilePath = Version::removeVersionPath($partialFilePath);
            }
            else if (array_key_exists(dirname($file), $widgetReferences)) {
                // Relative path to widget view partial.
                $partialFilePath = $file;
                $fileType = Text::beginsWith($file, 'standard/') ? self::STANDARD_WIDGET : self::CUSTOM_WIDGET;
            }
            else {
                // Relative path to page.
                $partialFilePath = $file;
                $fileType = self::VIEW;
            }

            $views[$partialFilePath] = $fileType;
        }
        // Sort order is type then path.
        array_multisort(array_values($views), array_keys($views), $views);

        return $views;
    }

    /**
     * Locates the widget references in the specified file.
     * @param  string $path    Absolute file path
     * @param  array  $toMatch Widget path(s) to match
     * @return string Code
     */
    private function findMatchesInFile ($path, array $toMatch) {
        $fileContents = file($path, FILE_SKIP_EMPTY_LINES);
        $widgetsToCheck = "#(" . implode('|', array_filter($toMatch)) . ")#";
        $matches = preg_grep($widgetsToCheck, $fileContents);

        if (!$matches) return '';

        // Map of line number => line contents
        $linesToInclude = array();

        foreach (array_keys($matches) as $index) {
            // Include a buffer of two lines around each match.
            foreach (range($index - 2, $index + 2) as $i) {
                $linesToInclude = self::includeLineAtIndex($fileContents, $i, $linesToInclude);
            }
        }

        // Collapse map into a string and add markers around widget name matches.

        $snippet = implode('', array_values($linesToInclude));

        return preg_replace($widgetsToCheck,
            self::$startWidgetPathMatch . "$1" . self::$endWidgetPathMatch, $snippet);
    }

    /**
     * Adds the specified line from $fileContents to $existingLines, if it isn't already populated.
     * Also populates the previous line (if there is one) with `$snippetBreak` to indicate that a
     * new area of text from $fileContents is being included.
     * @param array $fileContents Each line of a file, keyed by 0-based line number
     * @param int $index Line number to include
     * @param array $existingLines Array to populate
     * @return array Populated lines in $existingLines
     */
    private static function includeLineAtIndex (array $fileContents, $index, array $existingLines) {
        if (!array_key_exists($index, $existingLines) && $fileContents[$index]) {
            if ($existingLines && !array_key_exists($index - 1, $existingLines)) {
                // If there are several matching snippets within a single file, indicate that a
                // break happens so that the UI can handle it appropriately.
                $existingLines[$index - 1] = self::$snippetBreak;
            }

            $existingLines[$index] = $fileContents[$index];
        }

        return $existingLines;
    }

    /**
     * Returns the absolute file path to the widget.
     * @param  string $type One of view|custom|standard
     * @param  string $path Relative path to file
     * @return string Absolute path
     * @throws \Exception If An invalid type or widget path is given
     */
    private function getAbsolutePathFromFileType ($type, $path) {
        switch ($type) {
            case self::CUSTOM_WIDGET:
            case self::STANDARD_WIDGET:
                // Given path is either a relative path to the widget dir or a relative path to
                // a widget view partial.
                $pathInfo = pathinfo($path);
                $fileSpecified = $pathInfo['basename'] !== $pathInfo['filename'];

                if (!$fileWidgetPath = Registry::getWidgetPathInfo($fileSpecified ? dirname($path) : $path)) {
                    throw new \Exception(sprintf(Config::getMessage(INVALID_WIDGET_PATH_PCT_S_MSG), $path));
                }

                if ($fileSpecified) {
                    return $fileWidgetPath->absolutePath . '/' . $pathInfo['basename'];
                }
                return $fileWidgetPath->view;
            case self::VIEW:
                return CUSTOMER_FILES . "views/{$path}";
            default:
                throw new \Exception(sprintf(Config::getMessage(INVALID_FILE_TYPE_PCT_S_USED_MSG), $type));
        }
    }

    /**
    * Returns either the full widget path if it cannot be shortened or the shortened widget path (e.g.
    * input/FormInput). A widget path cannot be shortened if it is a standard widget and a custom
    * widget uses that same shortened path.
    * @param string $widgetPath Full relative widget path
    * @param boolean $includeDeactivated Include deactivated widget
    * @return string Widget path, shortened if possible
    */
    private static function getShortWidgetPath ($widgetPath, $includeDeactivated = false) {
        if (Text::beginsWith($widgetPath, 'standard/')) {
            $shortWidgetPath = Text::getSubstringAfter($widgetPath, 'standard/');
            $widget = Registry::getWidgetPathInfo($shortWidgetPath);
            if ((!$widget && !$includeDeactivated)
                || ($widget && $widget->relativePath !== $widgetPath)) {
                // standard widget is overridden by custom widget if 'standard' isn't specified
                $shortWidgetPath = $widgetPath;
            }
        }
        else {
            $shortWidgetPath = Text::getSubstringAfter($widgetPath, 'custom/');
        }

        return $shortWidgetPath;
    }

    /**
     * Caches the value and returns it. Updates the
     * `lastCheckTime` instance variable.
     * @param  string $key   Cache key
     * @param  array|bool $value Value to cache
     * @return array|bool        Value cached
     */
    private function cache ($key, $value) {
        try {
            $this->lastCheckTime = time();
            $this->cache->set($key, array($value, $this->lastCheckTime));
        }
        catch (\Exception $e) {
            // No need to warn about failing to set cache data.
        }

        return $value;
    }

    /**
     * Retrieves the cached value and restores the
     * `lastCheckTime` instance variable.
     * @param  string $key Cache key
     * @return array|bool|null Retrieved value or null if not found
     */
    private function getFromCache ($key) {
        if ($result = $this->cache->get($key)) {
            $this->lastCheckTime = $result[1];

            return $result[0];
        }
    }
}
