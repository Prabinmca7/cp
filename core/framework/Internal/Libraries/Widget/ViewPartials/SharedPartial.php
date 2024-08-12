<?php

namespace RightNow\Internal\Libraries\Widget\ViewPartials;

use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget,
    RightNow\Internal\Utils\FileSystem as InternalFileSystem,
    RightNow\Internal\Utils\Framework as InternalFramework;

/**
 * Represents a partial view contained within
 * - standard Views/Partials directory (non-optimized)
 * - custom Views/Partials directory (non-optimized when customer
 *   registers custom view in extensions.yml)
 * - method on the widget OR static function on a global
 *   SharedViewPartials class in optimized includes (optimized) TBI
 */
class SharedPartial extends Partial {
    /**
     * This type of partial has the ability to render itself, since
     * we don't need to maintain instance state.
     */
    public static $canSelfRender = true;
    private $loader;

    /**
     * Constructor.
     * @param string $name                     Name of the view (e.g. MyView, Partials.Category.File)
     * @param string $relativePathToParentView Relative path to widget (e.g. standard/feedback/AnswerFeedback, custom/Foo)
     * @param string $environment              Whether to retrieve the view content in Optimized or NonOptimized mode;
     *                                         Optional for testing purposes
     */
    function __construct ($name, $relativePathToParentView, $environment = null) {
        parent::__construct($name, $relativePathToParentView, $environment);
        $this->loader = new Widget\ExtensionLoader('viewPartialExtensions', 'views');
    }

    /**
     * Renders the view.
     * @param string $viewContent View content
     * @param array  $dataForView Local scope variables
     *                             that the view expects
     * @return string|boolean              Rendered view content
     */
    function render ($viewContent, array $dataForView) {
        $method = "render{$this->environment}";
        return $this->$method($viewContent, $dataForView);
    }

    /**
     * Content getter for optimized mode, where a method
     * name for the method containing the view is returned
     * @return string method name
     * @see  #renderOptimized
     * @see  SharedViewPartialOptimization#getSharedViewPartialContentAsViewFunctions
     */
    protected function getOptimizedContents () {
        $name = Text::getSubstringAfter($this->name, 'Partials.');
        return str_replace('.', '_', "{$name}_view");
    }

    /**
     * Gets the view off disk.
     * @return string|boolean view or false if the file can't be retrieved
     */
    protected function getNonOptimizedContents () {
        return $this->loader->getExtensionContent($this->name, self::transformPathInFilename($this->name) . self::FILE_NAME_SUFFIX);
    }

    /**
     * Gets the custom content for the given partial name.
     * @throws \Exception If the given partial name also exists for a standard partial but
     *         the custom override hasn't been registered in the extension list
     * @return string|boolean view contents or false if no content
     */
    protected function getNonOptimizedCustomContents () {
        $name = self::transformPathInFilename($this->name) . self::FILE_NAME_SUFFIX;

        if ($this->loader->extensionIsRegistered($this->name)) {
            return $this->loader->getContentFromCustomerDirectory($name);
        }
        if ($standardContent = $this->loader->getContentFromCoreDirectory($name)) {
            throw new \Exception("Unregistered custom view with the same path as standard view");
        }
        return $this->loader->getContentFromCustomerDirectory($name);
    }

    /**
     * Renders the view by calling the given method name on
     * the CustomSharedViewPartial class, passing in the
     * runtime data.
     * @param string $methodContainingView Method name
     * @param array $dataForView          Local scope variables
     *                                     that the view expects
     * @return string                                rendered view
     * @see  SharedViewPartialOptimization
     */
    private function renderOptimized ($methodContainingView, array $dataForView) {
        if (!class_exists('\Custom\Libraries\Widgets\CustomSharedViewPartials')) {
            // On normal page renders, the class is already pre-loaded into the page.
            // But when a widget is rendering outside of the regular page rendering flow
            // (i.e. widget#render as a response to an ajax request) then the class needs to
            // be loaded.
            $this->loader->loadContentFromCustomerDirectory('Partials/' . InternalFileSystem::getLastDeployTimestampFromFile() . '.php');
        }
        ob_start();
        \Custom\Libraries\Widgets\CustomSharedViewPartials::$methodContainingView($dataForView);
        return ob_get_clean();
    }

    /**
     * Renders the view in non-optimized mode by evaling the content.
     * @param string $dontCollideVarNameViewContent View content
     * @param array  $dataForView                   Local scope variables
     *                                               that the view expects
     * @return string                                rendered view
     */
    private static function renderNonOptimized($dontCollideVarNameViewContent, array $dataForView) {
        // Ensure that $target (and any other expected variables) is included in $dataForView
        $dataForView['target'] = isset($dataForView['target']) ? $dataForView['target'] : null;        
        extract($dataForView);
        ob_start();
        eval("?>$dontCollideVarNameViewContent<?");
        return ob_get_clean();
    }
    
    /**
     * Replaces dots with forward slashes.
     * @param string $name File / view name
     * @return string       dots replaced with slashes
     */
    private static function transformPathInFilename ($name) {
        return str_replace('.', '/', $name);
    }
}
