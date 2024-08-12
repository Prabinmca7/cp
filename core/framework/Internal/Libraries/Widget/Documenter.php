<?
namespace RightNow\Internal\Libraries\Widget;

use RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Widget\PathInfo,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Widgets,
    RightNow\Utils\Text,
    RightNow\Utils\Tags,
    RightNow\Utils\Framework;

/**
* Retrieves information about a widget for documentation purposes.
*/
final class Documenter{
    /**
     * Gets all information about a widget given its PathInfo.
     * @param PathInfo $widget PathInfo for the widget
     * @param array|null $options Whether to retrieve certain pieces;
     *  if not specified, ALL information is returned.
     *	if 'previewFiles' = false, no preview images are returned
     *	if 'events' = false, no JS events are returned
     * @return array|string Array containing all information about a widget
     * or string error message
     */
    public static function getWidgetDetails(PathInfo $widget, $options = array()) {
        $metaInfo = Widgets::getWidgetInfo($widget);
        if(is_string($metaInfo)) {
            // Invalid yaml error
            return $metaInfo;
        }

        $metaInfo = Widgets::convertAttributeTagsToValues($metaInfo, array('validate' => true, 'eval' => true));
        if(is_string($metaInfo)) {
            return $metaInfo;
        }

        $metaInfo = Widgets::convertUrlParameterTagsToValues($metaInfo);
        if(is_string($metaInfo)) {
            return $metaInfo;
        }

        return self::buildWidgetDetails($metaInfo, $widget, $options);
    }

    /**
     * Retrieves the attributes for the given widget.
     * @param PathInfo $widget PathInfo for the widget
     * @param bool $raw Whether to convert `rn:msg`, `rn:config` calls
     * @return array|string Array attributes or string error message
     */
    public static function getWidgetAttributes(PathInfo $widget, $raw = false) {
        $metaInfo = Widgets::getWidgetInfo($widget);

        if(is_string($metaInfo)) {
            // Invalid yaml error
            return $metaInfo;
        }
        if (!$raw) {
            $metaInfo = Widgets::convertAttributeTagsToValues($metaInfo, array('validate' => true, 'eval' => true));
        }
        if (isset($metaInfo['attributes']) && is_array($metaInfo['attributes'])) {
            ksort($metaInfo['attributes']);
        }

        return isset($metaInfo['attributes']) ? $metaInfo['attributes'] : array();
    }

    /**
     * Returns the info part of the widget's info.yml.
     * @param PathInfo $widget Widget to get the info
     * @param string $key If specified, the key within the info array to use, otherwise the
     * whole info array is returned
     * @param boolean $raw If $key is urlParameters, whether to return the raw values
     * or to convert rn: tags
     * @return string|array|null String error message or value or null if the value doesn't exist
     */
    public static function getWidgetInfo(PathInfo $widget, $key = '', $raw = false) {
        $metaInfo = Widgets::getWidgetInfo($widget);

        if(is_string($metaInfo)) {
            // Invalid yaml error
            return $metaInfo;
        }

        if (!$raw && $key === 'urlParameters' && isset($metaInfo['info']) && isset($metaInfo['info'][$key]) && $metaInfo['info'][$key]) {
            $metaInfo = Widgets::convertUrlParameterTagsToValues($metaInfo);
        }

        return ($key)
            ? isset($metaInfo['info'][$key]) ? $metaInfo['info'][$key] : null
            : $metaInfo['info'];
    }

    /**
     * Returns the requires part of the widget's info.yml.
     * @param PathInfo $widget Widget to get the info
     * @param string $key If specified, the key within the requires array to use, otherwise the
     * whole requires array is returned
     * @return string|array|null String error message or value or null if the value doesn't exist
     */
    public static function getWidgetRequirements(PathInfo $widget, $key = '') {
        $metaInfo = Widgets::getWidgetInfo($widget);

        if(is_string($metaInfo)) {
            // Invalid yaml error
            return $metaInfo;
        }

        return ($key)
            ? $metaInfo['requires'][$key]
            : $metaInfo['requires'];
    }

    /**
     * Returns PathInfo about all the widgets that are contained within a widget view.
     * @param string $viewContent The widget view
     * @param bool $includeAdditionalInfo Whether to also return an array of
     *  additional info about each widget call.
     * @return array
     *  If `$includeAdditionalInfo` isn't specified, each item is a PathInfo object
     *  if `$includeAdditionalInfo`, each item is an array with the following keys:
     *      -pathInfo: PathInfo object
     *      -attributes: Array attributes being set in each call
     *      -match: String Widget tag match
     *      -matchedPath: String matched widget path specified as the 'path' attribute of the widget tag;
     *             might not include the "standard/" or "custom/" prefix, although pathInfo is the
     *             correctly resolved widget
     */
    public static function getContainingWidgets($viewContent, $includeAdditionalInfo = false) {
        $containingWidgets = array();

        preg_match_all(\RightNow\Utils\Tags::getRightNowTagPattern(), $viewContent, $matches, PREG_PATTERN_ORDER);

        foreach ($matches[0] as $widgetCall) {
            $attributes = Tags::getHtmlAttributes($widgetCall);
            $matchedPath = Tags::getAttributeValueFromCollection($attributes, 'path');
            if (!$widgetPathInfo = Registry::getWidgetPathInfo($matchedPath)) continue;

            if ($includeAdditionalInfo) {
                $containingWidgets []= array(
                    'match'       => $widgetCall,
                    'pathInfo'    => $widgetPathInfo,
                    'attributes'  => $attributes,
                    'matchedPath' => $matchedPath,
                );
            }
            else {
                $containingWidgets []= $widgetPathInfo;
            }
        }

        return $containingWidgets;
    }

    /**
     * Returns a list of the widget's preview images.
     * @param PathInfo $widget PathInfo object
     * @return array Array with relative paths to any preview images; an empty array if there
     *      are no preview images
     */
    public static function getWidgetPreviewImages(PathInfo $widget) {
        $images = array();
        if (is_dir($fullPath = "{$widget->absolutePath}/preview")) {
            $imageFileExtensions = array('png', 'jpg', 'jpeg', 'gif');
            $previewFiles = FileSystem::getDirectoryTree($fullPath, $imageFileExtensions);
            if (count($previewFiles)) {
                asort($previewFiles);
                foreach ($previewFiles as $previewFileName => $previewFileTime) {
                    if (FileSystem::isReadableFile("$fullPath/$previewFileName")) {
                        $images[] = "$widget->relativePath/preview/$previewFileName";
                    }
                }
            }
        }
        return $images;
    }

    /**
     * Gloms all information associated to a widget such as attributes,
     * url parameters, events, etc.
     * @param array $meta Array containing file system paths to important folders
     * @param PathInfo $widget PathInfo of the widget
     * @param array|null $options Whether to include certain components
     * @return array Containing details about the widget
     */
    private static function buildWidgetDetails(array $meta, PathInfo $widget, $options = array()) {
        $widgetDetails = self::getWidgetPieces($meta);

        // pull out the class name of the controller
        $controllerPath = explode('/', $widgetDetails['controllerLocation']);
        $widgetDetails['controllerClass'] = $controllerPath[count($controllerPath) - 2];

        if (isset($meta['attributes']) && is_array($meta['attributes'])) {
            ksort($meta['attributes']);
            $widgetDetails['attributes'] = $meta['attributes'];
        }
        else {
            $widgetDetails['attributes'] = array();
        }

        if (!isset($options['sort']) || $options['sort'] !== false) {
            $widgetDetails['attributes'] = self::sortByValues(self::categorizeAttributes($widgetDetails['attributes']));
        }

        if (isset($meta['info']) && $meta['info']) {
            $widgetDetails['info'] = $meta['info'];
            $widgetDetails['info']['notes'] = Widgets::parseManifestRNField($meta['info']['description'], false);
            unset($meta['info']['description']);
            if (isset($meta['info']['urlParameters']) && $meta['info']['urlParameters']) {
                foreach ($meta['info']['urlParameters'] as $name => $param) {
                    $widgetDetails['urlParameters'][$name] = (object) $param;
                    ksort($widgetDetails['urlParameters']);
                }
            }
        }

        if ($options['events'] !== false) {
            $widgetDetails['events'] = self::populateWidgetEvents($meta);
        }
        if ($options['previewFiles'] !== false) {
            $widgetDetails['previewFiles'] = self::getWidgetPreviewImages($widget);
        }
        $widgetDetails['containingWidgets'] = self::getSubWidgetDetails($widget, $meta);

        return $widgetDetails;
    }

    /**
     * Returns details about widgets referenced insided the supplied
     * widget's view(s).
     * @param  PathInfo $widgetPathInfo Widget PathInfo instance
     * @param  array    $widgetFileInfo Widget file meta info
     * @return array                   See parseContainingWidgets
     */
    private static function getSubWidgetDetails(PathInfo $widgetPathInfo, array $widgetFileInfo) {
        $views = array_merge(array('view.php'), isset($widgetFileInfo['view_partials']) && $widgetFileInfo['view_partials'] ? $widgetFileInfo['view_partials'] : array());
        $widgetFileInfo['contains'] = isset($widgetFileInfo['contains']) && $widgetFileInfo['contains'] ? $widgetFileInfo['contains'] : array();
        $subWidgets = array();

        $pathToWidgetFiles = $widgetPathInfo->absolutePath;

        if(IS_HOSTED){
            // If we're in hosted, scan the non-script-compiled version of the view to get accurate messagebase labels
            $pathToWidgetFiles = str_ireplace('/scripts/cp/core', '/scripts/cp/src/core', $pathToWidgetFiles);
        }

        foreach ($views as $view) {
            $subWidgets = array_merge($subWidgets, self::parseContainingWidgets(@file_get_contents("{$pathToWidgetFiles}/{$view}"), $view, $widgetFileInfo['contains']));
        }

        return $subWidgets;
    }

     /**
     * Returns an array containing info about a widget's files
     * @param array $meta Contains the widget meta information populated from Widget methods
     * @return array Contains various keys and values depending upon whether files exist
     */
    private static function getWidgetPieces(array $meta) {
        $widgetDetails = array();
        
        if(isset($meta['extends_info'])) {
            if (isset($meta['extends_info']['controller']) && is_array($meta['extends_info']['controller']) && count($meta['extends_info']['controller']) > 0) {
                $widgetDetails['controllerExtends'] = array_map(
                    function ($element) { return $element . '/controller.php'; },
                    array_reverse($meta['extends_info']['controller'])
                );
            }
            if ((!isset($meta['extends']) || !isset($meta['extends']['overrideViewAndLogic']) || !$meta['extends']['overrideViewAndLogic']) && count($meta['extends_info']['logic']) > 0) {
                $widgetDetails['logicExtends'] = array_map(
                    function ($element) { return $element . '/logic.js'; },
                    array_reverse($meta['extends_info']['logic'])
                );
            }
            if ((!isset($meta['extends']) || !isset($meta['extends']['overrideViewAndLogic']) || !$meta['extends']['overrideViewAndLogic']) && count($meta['extends_info']['view']) > 0) {
                $widgetDetails['viewExtends'] = array_map(
                    function ($element) { return $element . '/view.php'; },
                    array_reverse($meta['extends_info']['view'])
                );
            }
        }
        if (isset($meta['js_path']) && $meta['js_path']) {
            $widgetDetails['jsPath'] = "{$meta['js_path']}/logic.js";
        }
        if ($meta['view_path']) {
            $widgetDetails['viewPath'] = "{$meta['view_path']}/view.php";
        }
        if (isset($meta['js_templates']) && is_array($meta['js_templates']) && count($meta['js_templates'])) {
            foreach ($meta['js_templates'] as $name => $template) {
                $widgetDetails['jsTemplates'][] = "{$meta['template_path']}/$name.ejs";
            }
        }
        if (isset($meta['base_css']) && $meta['base_css']) {
            $widgetDetails['baseCss'] = $meta['base_css'];
        }
        if(isset($meta['extends_info'])) {
            if (count($meta['extends_info']['base_css']) > 0) {
                $widgetDetails['baseCssExtends'] = array_reverse($meta['extends_info']['base_css']);
            }
            if (count($meta['extends_info']['presentation_css']) > 0) {
                $widgetDetails['presentationCssExtends'] = array_reverse($meta['extends_info']['presentation_css']);
            }
        }
        if (isset($meta['presentation_css']) && $meta['presentation_css']) {
            $widgetDetails['presentationCss'] = $meta['presentation_css'];
        }        

        // Default to blank controller
        $widgetDetails['controllerLocation'] = "standard/utils/Blank/controller.php";

        if ($meta['controller_path']) {
            // Widget has a controller file
            if (FileSystem::isReadableFile("{$meta['absolutePath']}/controller.php")) {
                $widgetDetails['controllerLocation'] = "{$meta['controller_path']}/controller.php";
            }
            // Widget has no controller file, but extends from another widget
            else if (isset($meta['extends_info']) && count($meta['extends_info']['controller']) > 0) {
                $extendedWidgets = array_reverse($meta['extends_info']['controller']);
                // Run through each controller listed until we find one that exists and use that
                foreach ($extendedWidgets as $eligibleParent) {
                    $parentPathInfo = Registry::getWidgetPathInfo($eligibleParent);
                    if (FileSystem::isReadableFile("$parentPathInfo->absolutePath/controller.php")) {
                        $widgetDetails['controllerLocation'] = "$eligibleParent/controller.php";
                        break;
                    }
                }
            }
        }

        return $widgetDetails;
    }

    /**
     * Scrapes a widget javascript file for all events that are fired and subscribed.
     * @param array $meta Meta information about the widget such as file paths
     * @return array Listing of all events associated to that widget
     */
    private static function populateWidgetEvents(array $meta) {
        $events = array(
            'subscribe' => array(),
            'fire' 		=> array(),
        );

        if(!$widgetPathInfo = Registry::getWidgetPathInfo(isset($meta['js_path']) ? $meta['js_path'] : null)) {
            return $events;
        }
        $jsPath = $widgetPathInfo->logic;

        if(!FileSystem::isReadableFile($jsPath)) {
            return $events;
        }

        $contents = file_get_contents($jsPath);

        // SUBSCRIBE
        // Top-level RightNow NS is optional if RightNow.Event has been aliased to RightNowEvent
        preg_match_all('@RightNow[.]?Event[.](?:subscribe|on)\s*[(]([\'"][^,)]*)@', $contents, $newSubscribeEventCalls);
        $events['subscribe'] = str_replace(array('"', "'"), '', $newSubscribeEventCalls[1]);

        //FIRE
        // Top-level RightNow NS is optional if RightNow.Event has been aliased to RightNowEvent
        preg_match_all('@RightNow[.]?Event[.]fire\s*[(]([\'"][^,)]*)@', $contents, $newFireEventCalls);
        $events['fire'] = str_replace(array('"', "'"), '', $newFireEventCalls[1]);

        return $events;
    }

    /**
     * Generate a list of all the widgets that are contained within a widget view and parse out all
     * the attributes that are set for the sub-widget.
     * @param string $viewContent The view content of a widget
     * @param string $viewName Name of file containing the view content
     * @param array $meta The 'contains' data from the widget's info.yml file
     * @return array Details about the containing widgets;
     *  Each item in the array contains the following keys:
     *      -path: String relative widget path
     *      -description: String sub-widget specific description
     *      -link: String CI admin link
     *      -attributes: Array
     *          -name: String attribute name
     *          -value: String value being set for the attribute
     */
    private static function parseContainingWidgets($viewContent, $viewName, array $meta) {
        $containingWidgets = array();

        foreach (self::getContainingWidgets($viewContent, true) as $widgetInfo) {
            $attributes = array();
            foreach ($widgetInfo['attributes'] as $attribute) {
                if ($attribute->attributeName === 'path') continue;

                $attributes []= trim($attribute->completeAttribute);
            }

            $description = '';
            foreach ($meta as $containsInfo) {
                if (isset($containsInfo['description']) && $containsInfo['description'] &&
                    $containsInfo['widget'] === $widgetInfo['pathInfo']->relativePath) {
                    $description = Widgets::parseManifestRNField($containsInfo['description'], false);
                }
            }

            $containingWidgets []= array(
                'file'        => $viewName,
                'path'        => $widgetInfo['pathInfo']->relativePath,
                'description' => $description,
                'match'       => $widgetInfo['match'],
                'attributes'  => $attributes,
                'matchedPath' => $widgetInfo['matchedPath'],
            );
        }

        return $containingWidgets;
    }

    /**
     * Sort alphabetically by attribute name w/in each category (i.e. labels, urls, other).
     * @param array $categories An associative array whose key is the defined attribute categories.
     * @return array An array of categories with the 'values' sorted alphabetically.
     */
    private static function sortByValues(array $categories) {
        foreach($categories as $category => $data) {
            $values = $data['values'];
            ksort($values);
            $categories[$category]['values'] = $values;
        }
        return $categories;
    }

    /**
     * Categorizes attributes into specific types.
     * @param array $attributes Array keyed by attributes' names whose values are Attribute objects
     * @return array Array keyed by the category names whose values are arrays with `label` and `values` keys
     */
    private static function categorizeAttributes(array $attributes) {
        $buckets = array(
            'required'  => array('label' => \RightNow\Utils\Config::getMessage(REQUIRED_LBL), 'values' => array()),
            'labels'    => array('label' => \RightNow\Utils\Config::getMessage(LABELS_LBL), 'values' => array()),
            'bool'      => array('label' => \RightNow\Utils\Config::getMessage(TOGGLES_LBL), 'values' => array()),
            'option'    => array('label' => \RightNow\Utils\Config::getMessage(OPTIONS_LBL), 'values' => array()),
            'filepath'  => array('label' => \RightNow\Utils\Config::getMessage(IMAGE_PATHS_LBL), 'values' => array()),
            'urls'      => array('label' => \RightNow\Utils\Config::getMessage(URLS_LBL), 'values' => array()),
            'ajax'      => array('label' => \RightNow\Utils\Config::getMessage(AJAX_ENDPOINTS_LBL), 'values' => array()),
            'other'     => array('label' => \RightNow\Utils\Config::getMessage(OTHER_LBL), 'values' => array()),
        );

        foreach ($attributes as $name => $attribute) {
            $bucket = 'other';
            if($attribute->required){
                $bucket = 'required';
            }
            else if (strtolower($attribute->type) === 'string') {
                if (Text::beginsWith($name, 'label_') || Text::stringContains($name, '_label_') || Text::endsWith($name, '_label')) {
                    $bucket = 'labels';
                }
                else if (Text::endsWith($name, '_url')) {
                    $bucket = 'urls';
                }
            }
            else {
                $type = strtolower($attribute->type);
                if (isset($buckets[$type]) && $buckets[$type]) {
                    $bucket = $type;
                }
                else if($type === 'multioption'){
                    $bucket = 'option';
                }
            }
            $buckets[$bucket]['values'][$name] = $attribute;
        }

        return $buckets;
    }
}
