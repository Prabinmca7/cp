<?php

namespace RightNow\Internal\Libraries\Widget;

use RightNow\Utils\Tags,
    RightNow\Internal\Utils\Widgets;

/**
 * Provides the ability to find widgets in any view content.
 * All sub-widgets contained inside views and view partials
 * are also examined.
 */
class Locator {
    /**
     * Widgets found during processing.
     * @var array
     */
    private $widgets = array();

    /**
     * Views to continue looking for widgets within
     * (e.g. widget views, widget view partials, extended widget views).
     * @var array
     */
    private $contentToProcess = array();

    /**
     * Paths of widgets that are matched in the `rn:widget` call
     * but the widgets do not actually exist.
     * @var array
     */
    public $errors = array();

    /**
     * Whether to process widgets' extended view chain.
     * The implication is that the result data returned from #getWidgets has
     * widget data whose "referencedBy" member includes widgets that extend
     * a given widget's view but do not _directly_ reference the widget with an rn:widget tag.
     * Setting this to false results in "referencedBy" members containing only view paths
     * to views and widgets that _directly_ reference the widget with an rn:widget tag.
     * @var boolean
     */
    public $includeParents = true;

    /**
     * Constructor.
     * @param string $contentPath  Path to the view content
     *                             (used for reporting purposes)
     * @param string $content      View content to examine
     * @param bool $includeParents Include parent widgets
     */
    function __construct ($contentPath = '', $content = '', $includeParents = true) {
        $this->includeParents = $includeParents;
        $this->addContentToProcess($contentPath, $content);
    }

    /**
     * Returns information about all of the widget calls in the content provided to
     * the constructor or supplied via #addContentToProcess.
     * @return Array Details about all of the widget calls within the given content:
     *                       Keys are all of the widget relative paths
     *                       Values are associative arrays containing the following keys:
     *                       - referencedBy: (array) list of view paths that reference the widget
     *                       - meta: (array) result of `Widgets#getWidgetInfo`
     *                       - view: (string) widget's view content
     */
    function getWidgets() {
        while ($this->contentToProcess) {
            $view = $this->removeContentToProcess();
            $this->findWidgetReferences($view->path, $view->content);
        }

        return $this->widgets;
    }

    /**
     * Adds more view content to look at next.
     * @param string $contentPath Path to the view content
     * @param string $content     View content
     */
    function addContentToProcess ($contentPath, $content) {
        if ($content && $contentPath) {
            $this->contentToProcess []= (object) array('path' => $contentPath, 'content' => $content);
        }
    }

    /**
     * Processes a widget.
     * Looks at the widget's view, view partials, and any inherited views, queuing up
     * their content for processing, as needed.
     * Takes care to ensure that if a widget's already been processed, then it's not
     * re-processed.
     * @param  object $widgetPathInfo PathInfo instance
     * @param  string $path           Path of the view content referencing the widget
     */
    function processWidget ($widgetPathInfo, $path) {
        // info.yml error that's reported later on when the widget is attempted to be rendered.
        if (!is_array($widgetFileInfo = Widgets::getWidgetInfo($widgetPathInfo, true))) return;

        if ($this->widgetWasAlreadyProcessed($widgetPathInfo)) {
            $this->addViewReferenceToProcessedWidget($widgetPathInfo, $path);
        }
        else {
            $widgetView = $this->examineWidgetView($widgetPathInfo, $widgetFileInfo);
            $this->examineAdditionalWidgetViews($widgetPathInfo, $widgetFileInfo);
            $this->addProcessedWidget($widgetView, $widgetPathInfo, $widgetFileInfo, $path);
        }
    }

    /**
     * Returns an array of widgets by keeping the top-level widgets and removing
     * their parent widgets that weren't actually referenced.
     * @param array $widgets Widgets array
     * @return array $widgets Widgets array with unwanted parent widgets removed.
     */
    public static function removeNonReferencedParentWidgets(array $widgets) {
        foreach($widgets as $widgetRelativePath => $widget) {
            $widgetsInList = 0;
            foreach ($widget['referencedBy'] as $widgetPath) {
                if(array_key_exists($widgetPath, $widgets)) {
                    $widgetsInList++;
                }
            }

            if($widgetsInList === count($widget['referencedBy'])) {
                //If here then it means widget referenced by $widgetRelativePath is not actually used on the page and can be removed.
                unset($widgets[$widgetRelativePath]);
            }
        }
        return $widgets;
    }

    /**
     * Pulls out the first item to process next, FIFO-style.
     * @return object Object added via #addContentToProcess
     */
    private function removeContentToProcess () {
        return array_shift($this->contentToProcess);
    }

    /**
     * Keeps track of widgets that have been encountered.
     * @param string $content View content
     * @param object $widgetPathInfo       PathInfo instance
     * @param array $widgetFileInfo       WidgetInfo array
     * @param string $pathOfContainingView Path of view the widget is within
     */
    private function addProcessedWidget ($content, $widgetPathInfo, $widgetFileInfo, $pathOfContainingView) {
        $this->widgets[$widgetPathInfo->relativePath] = array(
            'view'         => $content,
            'meta'         => $widgetFileInfo,
            'referencedBy' => array($pathOfContainingView),
        );
    }

    /**
     * Determines if the widget was already to the list of processed widgets.
     * @param object $widgetPathInfo PathInfo instance
     * @return boolean                 True if the widget has already been processed
     */
    private function widgetWasAlreadyProcessed ($widgetPathInfo) {
        return array_key_exists($widgetPathInfo->relativePath, $this->widgets);
    }

    /**
     * Adds the given view path to the processed widget's 'referencedBy' array.
     * @param object $widgetPathInfo       PathInfo instance
     * @param string $pathOfContainingView Path of the view the widget is within
     */
    private function addViewReferenceToProcessedWidget ($widgetPathInfo, $pathOfContainingView) {
        $this->widgets[$widgetPathInfo->relativePath]['referencedBy'] []= $pathOfContainingView;
    }

    /**
     * Locates all widget references in the view content.
     * @param string $path    Path of view content
     * @param string $content View content
     */
    private function findWidgetReferences ($path, $content) {
        foreach ($this->getWidgetRenderMatches($content) as $widgetPathInfo) {
            $this->processWidget($widgetPathInfo, $path);
        }
    }

    /**
     * Looks at and queues up widget views, partials, and
     * extended views for the given widget.
     * @param object $widgetPathInfo PathInfo instance
     * @param array $widgetFileInfo WidgetInfo array
     */
    private function examineAdditionalWidgetViews ($widgetPathInfo, $widgetFileInfo) {
        $this->examineWidgetViewPartials($widgetPathInfo, $widgetFileInfo);

        if ($this->includeParents) {
            $this->examineExtendedViewChain($widgetPathInfo, $widgetFileInfo);
        }
    }

    /**
     * Looks at and queues up the widget's view.
     * @param object $widgetPathInfo PathInfo instance
     * @param array  $widgetFileInfo WidgetInfo array
     * @return string widget view content
     */
    private function examineWidgetView ($widgetPathInfo, $widgetFileInfo) {
        $viewPath = $widgetPathInfo->view;
        if($viewPath)
        $viewContent = @file_get_contents($viewPath);

        if (!$viewContent && $widgetFileInfo['view_path'] &&
            ($widgetViewPathInfo = Registry::getWidgetPathInfo($widgetFileInfo['view_path']))) {
            $viewPath = $widgetViewPathInfo->view;
            $viewContent = @file_get_contents($viewPath);
        }

        $this->addContentToProcess($viewPath, $viewContent);

        return $viewContent;
    }

    /**
     * Looks up and queues up the widget's view partials, if it has any.
     * @param object $widgetPathInfo PathInfo instance
     * @param array $widgetFileInfo WidgetInfo array
     */
    private function examineWidgetViewPartials ($widgetPathInfo, $widgetFileInfo) {
        if (array_key_exists('view_partials', $widgetFileInfo)) {
            foreach ($widgetFileInfo['view_partials'] as $relativeFilePath) {
                $this->addContentToProcess("{$widgetPathInfo->relativePath}/$relativeFilePath",
                    @file_get_contents("{$widgetPathInfo->absolutePath}/$relativeFilePath"));
            }
        }
    }

    /**
     * Looks at and queues up the widget's extended views, if it has any.
     * @param object $widgetPathInfo PathInfo instance
     * @param array $widgetFileInfo WidgetInfo array
     */
    private function examineExtendedViewChain ($widgetPathInfo, $widgetFileInfo) {
        if (isset($widgetFileInfo['extends_info']) && $widgetFileInfo['extends_info'] &&
            $widgetFileInfo['extends_info']['view'] &&
            ($parentWidgetPathInfo = Registry::getWidgetPathInfo($widgetFileInfo['extends_info']['view'][0]))) {
            // If the widget extends another widget's view, widgets cannot be used in the extending view;
            // use the parent view to continue looking for widget calls
            $this->processWidget($parentWidgetPathInfo, $widgetPathInfo->relativePath);
        }
    }

    /**
     * Returns an array of Widget PathInfo instances for
     * every legit widget found in the given view content.
     * @param string $content View content
     * @return array          Contains PathInfo instances for
     *                                 every legit widget found
     */
    private function getWidgetRenderMatches ($content) {
        $matches = $errors = array();

        if (preg_match_all(Tags::getRightNowTagPattern(), $content, $matches, PREG_SET_ORDER)) {
            $matches = array_filter(array_map(function ($match) use (&$errors) {
                if (!isset($match[1]) || ($match[1] !== 'widget' && $match[1] !== 'widgets')) return;
                $attributes = Tags::getHtmlAttributes($match[0]);
                $path = Tags::getAttributeValueFromCollection($attributes, 'path');

                if (($pathInfo = Registry::getWidgetPathInfo($path)) && !Widgets::verifyWidgetReferences($pathInfo)) {
                    return $pathInfo;
                }

                $errors []= $path;
            }, $matches));

            if ($errors) {
                $this->errors += $errors;
            }
        }

        return $matches;
    }
}
