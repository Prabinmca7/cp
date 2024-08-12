<?php

namespace RightNow\Internal\Utils;

use RightNow\Internal\Libraries\Widget\PathInfo,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Libraries\ThirdParty\SimpleHtmlDomExtension;

/**
 * Provides functionality for processing widget views.
 * TK - Exists as a class until PHP 5.4.x provides traits,
 * whereupon the Utils\Widgets class can simply be composed of
 * various Views, Versions, Assets, Manifest, Helpers trait files,
 * providing for cleaner, more maintainable code.
 */
final class WidgetViews{
    /**
     * Unsets any stashed extended views for the widget that were
     * needed for sub-widget view processing.
     * @param string $widgetPath Relative widget path
     * @return bool True if there was a view that had been stored
     * and unset, false otherwise
     */
    public static function removeExtendingView($widgetPath) {
        return self::extendingViewCache('clear', $widgetPath);
    }

    /**
     * Processes the widget JS templates and combines / collapses `rn:block`s
     * @param array $meta The widget's meta info
     * @param PathInfo $widget The PathInfo object for the current widget
     * @return array Converted JS templates
     */
    public static function getExtendedWidgetJsViews(array $meta, PathInfo $widget) {
        $combined = $templateNames = $converted = array();
        $jsTemplates = isset($meta['js_templates']) && is_array($meta['js_templates']) ? array($meta['js_templates']) : array();
        $widgetName = $widget->className;

        $getBaseViewAncestors = function($ancestorViews) {
            $result = array();
            for ($i = count($ancestorViews) - 1; $i >= 0; --$i) {
                foreach ($ancestorViews[$i] as $name => $content) {
                    if (!array_key_exists($name, $result)) {
                        $result[$name] = $content;
                    }
                }
            }
            return $result;
        };

        // merge rn:block tags, if there are ancestors
        if (isset($meta['extends_info']) && is_array($meta['extends_info']) && $meta['extends_info']['js_templates']) {
            $jsTemplates = array_merge($jsTemplates, $meta['extends_info']['js_templates']);
            $meta['js_templates'] = array();

            if (isset($meta['extends_info']['parent']) && !$parentWidget = Registry::getWidgetPathInfo($meta['extends_info']['parent']))
                return $converted;

            $widgetName = isset($parentWidget->className) && $parentWidget->className ? $parentWidget->className : '';
            $baseTemplate = $getBaseViewAncestors($jsTemplates);
            $templateNames = array_keys($baseTemplate);

            // extend the ancestor views until the base ancestor is reached, where ever that
            // might be in the extension chain
            if (count($jsTemplates) > 1) {
                $combined = array_shift($jsTemplates);
                $reachedAncestor = array();

                foreach ($jsTemplates as $currentTemplate) {
                    foreach ($templateNames as $name) {
                        if (!in_array($name, $reachedAncestor) && (isset($currentTemplate[$name]) && isset($baseTemplate[$name]) && $currentTemplate[$name] === $baseTemplate[$name])) {
                            $reachedAncestor[] = $name;
                            continue;
                        }

                        // merge the current view with the view provided by the last child
                        $combined[$name] = self::mergeExtendedWidgetViewBlocks(isset($currentTemplate[$name]) ? $currentTemplate[$name] : "", isset($combined[$name]) ? $combined[$name] : "");
                    }
                }
            }
            else {
                $combined = array_shift($jsTemplates);
            }
        }
        else {
            $templateNames = array();
            // if the widget does not have any extensions, use the views provided
            if (isset($meta['js_templates']) && is_array($meta['js_templates'])) {
                $baseTemplate = $meta['js_templates'];
                $templateNames = array_keys($baseTemplate);
            }
        }

        foreach ($templateNames as $name) {
            $converted[$name] = ($baseTemplate && $baseTemplate[$name]) ?
                self::combineWidgetViewBlocks($widgetName, $baseTemplate[$name], isset($combined[$name]) && $combined[$name] ? $combined[$name] : "") :
                self::combineWidgetViewBlocks($widgetName, isset($combined[$name]) && $combined[$name] ? $combined[$name] : "");
        }

        return $converted;
    }

    /**
     * Returns the view content for the widget.
     * @param string $widgetView The widget's view
     * @param array $meta Array The widget's meta info
     * @param PathInfo $widget The PathInfo object for the current widget
     * @return string View content with all `rn:block`s removed
     */
    public static function getExtendedWidgetPhpView($widgetView, array $meta, PathInfo $widget) {
        $baseView = null;
        $requireSubWidgetName = false;
        $widgetName = $widget->className;
        $phpViews = isset($meta['extends_info']) && is_array($meta['extends_info']['view']) ? $meta['extends_info']['view'] : array();

        if ($meta['view_path']) {
            self::extendingViewCache('set', $widget->relativePath, $widgetView);
        }

        $getMergedParentViews = function($widgetView) use (&$phpViews) {
            while ($nextWidget = Registry::getWidgetPathInfo(array_shift($phpViews))) {
                $widgetView = WidgetViews::mergeExtendedWidgetViewBlocks(
                    Tags::transformTags(@file_get_contents($nextWidget->view)),
                    $widgetView);
            }
            return $widgetView;
        };

        // If this widget extends any views, combine them. The meta data will only contain valid
        // view extensions here; if any widget in the extension chain overrides view and logic
        // that will be the last view extension listed.
        if (isset($meta['extends_info']) && is_array($meta['extends_info']) && $meta['extends_info']['view']) {
            // the base ancestor will contain all of the rn:block tags that are available to children
            if ($baseAncestorWidget = Registry::getWidgetPathInfo(array_pop($phpViews))) {
                $widgetName = $baseAncestorWidget->className;
                $baseView = Tags::transformTags(@file_get_contents($baseAncestorWidget->view));

                // The meta data lists views from last child -> base ancestor. Merge all of the
                // view extensions including the current widget via $widgetView.
                $widgetView = $getMergedParentViews($widgetView);
            }
        }

        if ($baseView === null) {
            // if the widget isn't extending another view, the rn:blocks still need to be removed
            $baseView = $widgetView;
            // Widget view ends up being either null or a view that's extending a widget that contains this current widget.
            // Note that the 'get' call to extendingViewCache below will only return a value if there is exactly one view in the cache.
            $widgetView = (IS_OPTIMIZED) ? null : self::extendingViewCache('get');

            while ($baseView && !$widgetView && count($phpViews) > 0) {
                $widgetView = $getMergedParentViews($widgetView);
            }

            if ($widgetView) {
                $requireSubWidgetName = true;
            }
        }

        $combined = self::combinePHPViews($widgetName, $baseView, $widgetView, $requireSubWidgetName);
        return $combined['result'];
    }

    /**
     * Removes the rn:blocks in $baseView; replaces blocks with matching ids in $viewWithBlocksToInsert.
     * @param string $widgetName Name of the widget; used as an optional selector prefix in extending views
     * @param string $baseView The widget view containing `rn:block`s to replace / remove
     * @param string $viewWithBlocksToInsert Extending view containing `rn:block`s to place into $baseView
     * @param bool $requireWidgetNamePrefix Whether to only look for block selectors beginning with widgetName-id
     *  when scanning the extendingView for block replacements
     * @return array Array containing 'result' and 'replacementsMade'
     */
    public static function combinePHPViews($widgetName, $baseView, $viewWithBlocksToInsert, $requireWidgetNamePrefix = false) {
        $combined = self::combineWidgetViewBlocks($widgetName, $baseView, $viewWithBlocksToInsert, $requireWidgetNamePrefix, $replacementsMade);
        return array(
            'result' => $combined,
            'replacementsMade' => $replacementsMade ?: 0,
        );
    }

    /**
     * Reduces rn:blocks in view partials.
     * @param string $partialName  Name of view partial file
     * @param string $relativePath Relative path to widget (e.g. custom/WidgtName or standard/feedback/AnswerFeedback)
     * @return string|boolean       Partial content with reduced rn:blocks or false if $relativePath doesn't point to
     *                                      a legit widget or $partialName doesn't point to a legit partial view.
     */
    public static function combinePartials($partialName, $relativePath) {
        $relativePath = Widgets::getWidgetRelativePath($relativePath);
        $widgetInfo = Registry::getWidgetPathInfo($relativePath);

        if (!$widgetInfo) return false;

        $widgetMetaInfo = Widgets::getWidgetInfo($widgetInfo);

        if (isset($widgetMetaInfo['extends_info']) && $widgetMetaInfo['extends_info'] && ($viewHierarchy = $widgetMetaInfo['extends_info']['view'])) {
            // If the widget is extending another widget's view, then its partial view file
            // is eligible for the same block-subbing functionality that the view.php file has.

            // The view hierarchy info in the widget's metadata is ordered by most immediate parent first.
            array_unshift($viewHierarchy, $relativePath);
            return self::combinePartialExtensionHierarchy($partialName, $viewHierarchy);
        }
        else if (!$widgetMetaInfo['view_partials'] || !in_array($partialName, $widgetMetaInfo['view_partials'])) {
            // Widget isn't extending any views and doesn't have a partial by this name. Error.
            return false;
        }

        // Remove any blocks in the given partial.
        return self::combineWidgetViewBlocks(basename($relativePath), self::getTransformedViewContent("{$widgetMetaInfo['absolutePath']}/{$partialName}"));
    }

    /**
     * Goes thru the supplied widget view hierarchy and
     * combines up the partial views.
     * @param string $partialName        Partial file name
     * @param array $viewHierarchy       View hierarchy ordered parent → child
     * @param string $currentViewContent Used for recursion to build up the view content;
     *                                    defaults to bool to differentiate between
     *                                    an empty-content partial and not-found partial
     * @return string|boolean             combined view content or false if no view was
     *                                    ever processed
     */
    private static function combinePartialExtensionHierarchy($partialName, $viewHierarchy, $currentViewContent = false) {
        if (!$viewHierarchy) return $currentViewContent;

        $relativePath = array_shift($viewHierarchy);
        $widgetMetaInfo = Widgets::getWidgetInfo(Registry::getWidgetPathInfo($relativePath));

        if (isset($widgetMetaInfo['view_partials']) && $widgetMetaInfo['view_partials'] && in_array($partialName, $widgetMetaInfo['view_partials'])) {
            $content = self::getTransformedViewContent("{$widgetMetaInfo['absolutePath']}/{$partialName}");
            $currentViewContent = ($viewHierarchy)
                // Merge the rn:blocks in the extending views.
                ? self::mergeExtendedWidgetViewBlocks($content, $currentViewContent)
                // At the parent view: sub in the rn:blocks from the extended view content.
                : self::combineWidgetViewBlocks(basename($relativePath), $content, $currentViewContent);
        }

        return self::combinePartialExtensionHierarchy($partialName, $viewHierarchy, $currentViewContent);
    }

    /**
     * A cache of extending views.
     *
     * @param string $action One of:
     *     'set'      - Add a cache entry where $widgetPath is the key and $view the value.
     *     'get'      - Get $view from cache where $widgetPath is the key.
     *                  If $widgetPath not specified AND there is exactly one entry in the cache, return that value.
     *     'clear'    - Remove record specified by $widgetPath. Returns true if $widgetPath existed, else false.
     *     'clearAll' - Remove all records from the cache. Intended use is for testing.
     * @param string $widgetPath The widget path which acts as the cache key.
     * @param string|null $view The widget view which is stored as the cache value.
     * @return mixed
     * @throws \Exception If action isn't one of the supported options
     */
    private static function extendingViewCache($action, $widgetPath = null, $view = null) {
        // The preferred technique of keeping the cache in a static var causes opcode cache problems.
        // Instead, use a global var assigned by reference to $cache - it will persist between 
        // method calls and will not be unset when $cache goes out of scope
        $cache =& $GLOBALS['viewCache']; 

        if (!isset($cache)) {
            $cache = array();
        }
        if ($action === 'set') {
            return $cache[$widgetPath] = $view;
        }
        if ($action === 'get') {
            if ($widgetPath === null && count($cache) === 1) {
                return end($cache);
            }
            return isset($cache[$widgetPath]) && $cache[$widgetPath] ? $cache[$widgetPath] : false;
        }
        if ($action === 'clear') {
            if (array_key_exists($widgetPath, $cache)) {
                unset($cache[$widgetPath]);
                return true;
            }
            return false;
        }
        if ($action === 'clearAll') {
            $cache = array();
            return true;
        }
        throw new \Exception("Action must be one of 'set', 'get', 'clear' or 'clearAll'");
    }

    /**
     * Parses $baseView and replaces `rn:block`s with `rn:block`s in $extendingView that
     * have the same id. If $extendingView is null then all `rn:block`s in $baseView are simply removed.
     * @param string $widgetName Name of the widget; used as an optional selector prefix in extending views
     * @param string $baseView The widget view containing `rn:block`s to replace / remove
     * @param string $extendingView Extending view containing `rn:block`s to place into $baseView
     * @param bool $requireWidgetNamePrefix Whether to only look for block selectors beginning with widgetName-id
     *  when scanning the extendingView for block replacements
     * @param int &$replacementsMade If $extendingView is passed, this parameter value (pass-by-reference) is populated
     *  with the number of block replacements made
     * @return string The value of $baseView with all `rn:block`s replaced or removed
     */
    private static function combineWidgetViewBlocks($widgetName, $baseView, $extendingView = '', $requireWidgetNamePrefix = false, &$replacementsMade = 0) {
        require_once CPCORE . 'Libraries/ThirdParty/SimpleHtmlDomExtension.php';

        $baseHtml = SimpleHtmlDomExtension\loadDom($baseView);

        if ($baseHtml === false) return ''; // View is an empty string.

        $customHtml = ($extendingView)
            ? SimpleHtmlDomExtension\loadDom($extendingView, false)
            : null;
        $customSelector = array("{$widgetName}-");
        if (!$requireWidgetNamePrefix) {
            $customSelector []= '';
        }

        $convertedView = self::reduceBlocks($baseHtml, $customHtml, $customSelector, $replacementsMade);
        $convertedView = $convertedView->outertext;
        $baseHtml->clear();

        return $convertedView;
    }

    /**
     * Creates a single set of `rn:block`s from $baseView and $extendingView. If
     * both views contain the same id the one defined in $extendingView will replace
     * the one defined in $baseView.
     * @param string $baseView The widget view containing `rn:block`s to replace
     * @param string $extendingView Extending view containing `rn:block`s to place into $baseView
     * @return string Combined set of `rn:block`s from both $baseView and $extendingView
     */
    public static function mergeExtendedWidgetViewBlocks($baseView, $extendingView) {
        $blockTag = 'rn:block'; // don't use a static as is causes opcode cache problems
        require_once CPCORE . 'Libraries/ThirdParty/SimpleHtmlDomExtension.php';
        $parentHtml = SimpleHtmlDomExtension\loadDom($baseView);
        $childHtml = SimpleHtmlDomExtension\loadDom($extendingView);
        if (!$parentHtml)
            return $childHtml->outertext;

        if ($childHtml) {
            $blocks = array();

            foreach ($childHtml->find($blockTag) as $childBlock) {
                $blocks[$childBlock->id] = $childBlock->outertext;
            }

            foreach ($parentHtml->find($blockTag) as $parentBlock) {
                if (!array_key_exists($parentBlock->id, $blocks))
                    $blocks[$parentBlock->id] = $parentBlock->outertext;
            }

            $parentHtml->outertext = implode("", $blocks);
        }

        return $parentHtml->outertext;
    }

    /**
     * Removes `rn:block`s in the given DOM node by replacing the block content with matching block
     * content in $customHtml or by simply removing the block tags from $baseHtml.
     * @param object $baseHtml Base view to remove blocks from
     * @param object|string|null $customHtml Custom view to replace blocks in $baseHtml. May be empty.
     * @param array $selector Selectors to search for matches in $customHtml
     * @param int &$replacementsMade Pass-by-reference Number of custom replacements were made.
     * @return object The resulting Dom node
     */
    private static function reduceBlocks($baseHtml, $customHtml, array $selector, &$replacementsMade) {
        $blockTag = 'rn:block'; // don't use a static as is causes opcode cache problems

        foreach ($baseHtml->find($blockTag) as $standardBlock) {
            if ($standardBlock->children()) {
                // Non-empty block that may surround other blocks: reduce it down.
                $standardBlock = self::reduceBlocks($standardBlock, $customHtml, $selector, $replacementsMade);
            }
            if ($customHtml) {
                $blockID = $standardBlock->id;
                $selectorString = implode(',', array_map(function($i) use ($blockTag, $blockID) {
                    return "{$blockTag}#{$i}{$blockID}";
                }, $selector));

                if (count($customOverride = $customHtml->find($selectorString))) {
                    // If, for some reason, there's more than one block with the same id, just use the last one
                    $customOverride = end($customOverride);
                    $standardBlock->innertext = $customOverride->innertext;
                    $replacementsMade++;
                }
            }
            $standardBlock->outertext = $standardBlock->innertext;
        }
        return $baseHtml;
    }

    /**
     * Gets the file at $path and replaces any rn: tags
     * with php replacements.
     * @param string $path Absolute path
     * @return string|boolean       transformed content or false
     *                                          if $path is invalid
     */
    private static function getTransformedViewContent($path) {
        return Tags::transformTags(@file_get_contents($path));
    }
}
