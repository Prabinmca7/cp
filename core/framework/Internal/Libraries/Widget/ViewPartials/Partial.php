<?php

namespace RightNow\Internal\Libraries\Widget\ViewPartials;

use RightNow\Utils\Framework;

/**
 * Provides widget view partial retrieval and caching. Acts as an interface
 * for subclasses. Subclasses should implement #getOptimizedContents and
 * #getNonOptimizedContents.
 */
abstract class Partial {
    const CACHE_KEY_PREFIX = 'View_Partial_';
    const FILE_NAME_SUFFIX = '.html.php';

    protected $name = '';
    protected $environment;
    protected $relativePathToParentView = '';

    /**
     * Constructor.
     * @param string $name                     Name of the view (e.g. MyView, Partials.Category.File)
     * @param string $relativePathToParentView Relative path to widget (e.g. standard/feedback/AnswerFeedback, custom/Foo)
     * @param string $environment              Whether to retrieve the view content in Optimized or NonOptimized mode;
     *                                         Optional for testing purposes
     */
    function __construct($name, $relativePathToParentView, $environment = null) {
        $this->name = $name;
        $this->relativePathToParentView = $relativePathToParentView;
        $this->environment = $environment ?: (IS_OPTIMIZED ? 'Optimized' : 'NonOptimized');
    }

    /**
     * Gets the contents either from cache or by caching the call to
     * #getContentsForEnvironment
     * @param string $forEnvironment Whether to retrieve the view content in Optimized or NonOptimized mode;
     * @return string|boolean view content or False if there's a problem retrieving it
     */
    function getContents ($forEnvironment = null) {
        $content = $this->getCachedContent();

        return (is_null($content))
            ? $this->cacheContent($this->getContentsForEnvironment($forEnvironment))
            : $content;
    }

    /**
     * Validates the data array containing local variables that the view expects.
     * Non-optimized renderer functions evaling content should use `dontCollideVarNameViewContent`
     * for the view content parameter name.
     * @param array $dataForView Contains local variables that view expects.
     * @return string|boolean              Invalid keys or false if no invalid
     *                                             keys are found
     */
    static function invalidPartialDataFound ($dataForView) {
        static $badKeys = array('this', 'dontCollideVarNameViewContent');

        if ($badKeysFound = array_intersect($badKeys, array_keys($dataForView))) {
            return implode(', ', array_values($badKeysFound));
        }

        return false;
    }

    /**
     * Subclass must implement.
     */
    protected abstract function getOptimizedContents();

    /**
     * Subclass must implement.
     */
    protected abstract function getNonOptimizedContents();

    /**
     * Calls the optimized / non optimized content getter
     * depending on the value of `$this->environment`
     * @param string $environment Whether to retrieve the view content in Optimized or NonOptimized mode;
     * @return string|boolean view content or False if there's a problem retrieving it
     */
    private function getContentsForEnvironment ($environment) {
        $environment || ($environment = $this->environment);
        $environment = "get{$environment}Contents";
        return $this->{$environment}();
    }

    /**
     * Caches the content.
     * @param string $content View content
     * @return string          $content as a convenience to callers
     */
    private function cacheContent ($content) {
        Framework::setCache($this->getCacheKey(), $content);

        return $content;
    }

    /**
     * Checks the cache for a cached view.
     * @return string|boolean cached content or F if none
     */
    private function getCachedContent () {
        return Framework::checkCache($this->getCacheKey());
    }

    /**
     * Builds the cache key for the instance.
     * @return string cache key
     */
    private function getCacheKey () {
        return self::CACHE_KEY_PREFIX . $this->name . $this->relativePathToParentView;
    }
}
