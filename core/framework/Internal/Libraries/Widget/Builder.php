<?php

namespace RightNow\Internal\Libraries\Widget;

use RightNow\Utils\Text,
    RightNow\Utils\Widgets,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Config,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Libraries\ThirdParty\SimpleHtmlDom;

require_once CPCORE . 'Libraries/ThirdParty/SimpleHtmlDom.php';
require_once CPCORE . 'Internal/Libraries/Widget/Documenter.php';

/**
 * Generates Boilerplate code for a custom widget
 */
final class Builder {
    private $name = '';
    private $parent = null;
    private $baseAncestor = null;
    private $overridesParent = false;

    /**
     * Folder path relative to basePath
     */
    private $folderPath = '';
    private $attributes = array();

    /**
     * YUI Modules
     */
    private $yuiModules = array();

    /**
     * Info like description, attributes, etc.
     */
    private $info = array();

    /**
     * Parts (php, view, etc.)
     */
    private $components = array();

    /**
     * Keep track of errors
     */
    private $errors = array();

    /**
     * Map component names to files
     */
    private $componentsMapping = array(
        'php'      => 'controller.php',
        'view'     => 'view.php',
        'js'       => 'logic.js',
        'manifest' => 'info.yml',
    );

    /**
     * Methods used to set the various components.
     * @see #addComponent
     */
    private $componentHandlers = array(
        'php'       => 'setPhp',
        'view'      => 'setView',
        'js'        => 'setJS',
        'jsView'    => 'setJSView',
        'parentCss' => null, // No handler method
    );

    /**
     * View block boilerplate
     */
    private $blockBoilerPlate = "<!--\n<rn:block id='%s'>\n\n</rn:block>\n-->\n\n";

    /**
     * Creates a new widget builder
     * @param string $name Widget name
     * @param string $folderPath Widget directory path
     */
    public function __construct($name, $folderPath) {
        $this->name = $this->validateName($name);
        $this->folderPath = $this->validatePath($folderPath);
    }

    /**
     * Add a component to the widget. Should be called
     * after #setAttributes and #setParent in order to
     * generate the most accurate components.
     * @param string $type Must be one of the following:
     *  - 'php'
     *  - 'js'
     *  - 'view'
     *  - 'jsView'
     *  - 'parentCss'
     * optional params may be passed in, such as:
     * Boolean true when setting 'js', 'view', 'jsView' to indicate
     * that the widget should override its parent's components
     * @return bool Whether the component was successfully added
     */
    public function addComponent($type /*, variable */) {
        if (array_key_exists($type, $this->componentHandlers)) {
            if ($method = $this->componentHandlers[$type]) {
                $this->components[$type] = call_user_func_array(array($this, $method), array_slice(func_get_args(), 1));
            }
            else {
                $this->components[$type] = true;
            }
            return true;
        }
        return false;
    }

    /**
     * Makes sure modules is an array
     * before setting the yuiModules member.
     * @param array|null $modules List of YUI modules to include
     */
    public function setYUIModules($modules) {
        if (is_array($modules)) {
            $this->yuiModules = array_filter($modules);
        }
    }

    /**
     * Makes sure info is an array
     * before setting the info member.
     * @param array|null $info Associative array of info properties to set
     */
    public function setInfo($info) {
        if (is_array($info)) {
            $this->info = $info;
        }
    }

    /**
     * Sets the widget's parent.
     * @param PathInfo $parent PathInfo of the parent
     */
    public function setParent(PathInfo $parent) {
        $this->parent = $parent;
    }

    /**
     * Sets the widget's original ancestor.
     * @param PathInfo $ancestor PathInfo of the original ancestor
     */
    public function setBaseAncestor(PathInfo $ancestor) {
        $this->baseAncestor = $ancestor;
    }

    /**
     * Sets the attributes. All at once.
     * @param array $attributes Attributes
     * @param boolean $decodeAttributes Whether the attribute list should have html entities decoded.
     *                                  Should only be set to true during widget creation.
     * @return Boolean Whether the operation was a success
     */
    public function setAttributes(array $attributes, $decodeAttributes = false) {
        if($decodeAttributes) {
             $attributes = self::parseHTMLEntitiesOnAttributes($attributes, true);
        }

        foreach ($attributes as $key => $attribute) {
            //validateWidgetAttributeStructure expects attributes to be objects
            $attributes[$key] = (object) $attribute;
        }
        if ($error = Widgets::validateWidgetAttributeStructure(array('attributes' => $attributes))) {
            return $this->addError($error);
        }
        $this->attributes = $attributes;
        return true;
    }

    /**
     * Saves the various components to disk.
     * @param object|null $writer A FileWriter instance or null
     * @param object|string|null $updater A class that responds to
     *  updateWidgetVersion. Defaults to using
     * \RightNow\Utils\Widgets if unspecified
     * @param bool $activateWidget True to activate the widget after save, false to skip
     * @return boolean|array
     * False if errors prevent the widget from being saved
     * Array of info on success:
     *  'files': [
     *      {each component file name}: '/webdav/path'
     *  ],
     *  'widget': [
     *      'name': relative widget path + name
     *      'link': webdav path to widget directory
     *  ]
     */
    public function save($writer = null, $updater = null, $activateWidget = true) {
        if (count($this->getErrors()) !== 0) return false;

        if ($writer && !$writer instanceof FileWriter) return false;
        if ($updater && (!class_exists($updater) || !method_exists($updater, 'updateWidgetVersion'))) return false;

        $writer || ($writer = new FileWriter());

        $widget = "custom/{$this->folderPath}/{$this->name}";
        $writer->setDirectory($widget);

        $updater || ($updater = '\RightNow\Utils\Widgets');

        if ($writer->directoryAlreadyExists()) {
            return $this->addError(sprintf(Config::getMessage(WIDGET_NAME_EXISTS_PCT_S_LBL), $widget));
        }
        if (!$writer->writeDirectory()) {
            return $this->addError(sprintf(Config::getMessage(ERR_ENC_TRYNG_CREATE_WIDGET_LBL), $widget));
        }

        $davPath = "/dav/cp/customer/development/widgets/$widget/1.0";
        $widgetInfo = array(
                        'widget' =>
                            array('davLabel' => Config::getMessageJS(VIEW_CODE_CMD),
                                  'davLink' => $davPath,
                                  'docLabel' => Config::getMessageJS(VIEW_DOCUMENTATION_CMD),
                                  'docLink' => '/ci/admin/versions/manage#widget=' . urlencode($widget)),
                        'files' => array());
        $components = array('manifest' => $this->getManifest()) + $this->components;

        $components = $this->mergeFileListIntoComponents('jsView', $components, '.ejs');
        $components = $this->mergeFileListIntoComponents('viewPartials', $components);
        if (isset($components['parentCss']) && $components['parentCss']) {
            // Parent CSS isn't a component, although it's added that way.
            unset($components['parentCss']);
        }

        // Create each file
        foreach($components as $component => $code) {
            $filename = isset($this->componentsMapping[$component]) ? $this->componentsMapping[$component] : $component;
            $widgetInfo['files'][$component] = "$davPath/$filename";
            if (!$writer->write($filename, $code)) {
                return $this->addError(sprintf(Config::getMessage(ERR_ENC_TRYING_CREATE_WIDGET_LBL), "$widget/$filename"));
            }
        }

        // Create CSS
        $writeCssTo = array(
            'presentation' => array(
                'path'    => HTMLROOT . ASSETS_ROOT . 'themes/standard/widgetCss/',
                'name'    => "{$this->name}.css",
                'davPath' => '/dav/cp/customer/assets/themes/standard/widgetCss/',
            ),
            'base' => array(
                'path' => '',
                'name' => 'base.css',
            ),
        );
        foreach ($this->generateCss() as $type => $css) {
            $toWrite = $writeCssTo[$type];
            // @codingStandardsIgnoreStart
            if ($type === 'presentation' && \RightNow\Utils\FileSystem::isReadableFile($writeCssTo['presentation']['path'] . $writeCssTo['presentation']['name'])) {
                // Don't overwrite an existing presentation CSS file; skip the write() call below.
            }
            // @codingStandardsIgnoreEnd
            else if (!$writer->write($toWrite['path'] . $toWrite['name'], $css, (bool) strlen($toWrite['path']))) {
                return $this->addError(sprintf(Config::getMessage(ERR_ENC_TRYING_CREATE_WIDGET_LBL), "$widget/{$toWrite['name']}"));
            }
            $widgetInfo['files'][$type] = ((isset($toWrite['davPath']) && $toWrite['davPath']) ? $toWrite['davPath'] : "$davPath/") . $toWrite['name'];
        }

        // Activate the widget
        if ($activateWidget && !$updater::updateWidgetVersion($widget, '1.0')) {
            return $this->addError(sprintf(Config::getMessage(ERROR_ENC_ATT_ACTIVATE_PCT_S_LBL), $widget));
        }

        return $widgetInfo;
    }

    /**
     * Returns any errors.
     * @return array List of error messages; empty if no errors
     */
    public function getErrors() {
        return $this->errors;
    }


    /**
     * Takes a list of widget attributes and either encodes or decodes all html entities
     * @param array $attributes Array of widget attributes
     * @param boolean $shouldDecode Set to true to decode entities, false to encode them
     * @return array Array of parsed attributes
     */
    public static function parseHTMLEntitiesOnAttributes(array $attributes, $shouldDecode) {
        $attributesToParse = array('description', 'default', 'options');
        $parseFunc = $shouldDecode ? "html_entity_decode" : "htmlspecialchars";

        foreach($attributes as $key => $attribute) {
            foreach($attribute as $attrKey => $attrVal) {
                if(!in_array($attrKey, $attributesToParse)) {
                    continue;
                }

                $attrIsObject = (gettype($attribute) === 'object');
                if(is_array($attrVal)) {
                    foreach($attrVal as $optionKey => $optionValue) {
                        if(is_string($optionValue)) {
                            $attrIsObject ? ($attributes[$key]->{$attrKey}[$optionKey] = call_user_func($parseFunc, $optionValue)) :
                                            ($attributes[$key][$attrKey][$optionKey] = call_user_func($parseFunc, $optionValue));
                        }
                    }
                }
                else if(is_string($attrVal)) {
                    $attrIsObject ? ($attributes[$key]->{$attrKey} = call_user_func($parseFunc, $attrVal)) :
                                    ($attributes[$key][$attrKey] = call_user_func($parseFunc, $attrVal));
                }
            }
        }
        return $attributes;
    }

    /**
     * Generates the presentation & base CSS for the widget.
     * Naïve: Only outputs the widget's immediate parent selector,
     * if the widget has a parent, and assumes that the widget's
     * class name is prefixed with "rn_".
     * @return array Array with the following keys:
     *  -presentation: String CSS
     *  -base:         String CSS
     */
    private function generateCss() {
        static $css = array(
            'presentation' => '%s.css: Presentation CSS',
            'base'         => 'base.css: Base CSS for %s',
        );

        $selector = ".rn_{$this->name}";

        if ($this->parent) {
            $selector .= ".rn_{$this->parent->className}";
        }

        $generated = array();
        foreach ($css as $type => $desc) {
            $comment = sprintf($desc, $this->name);
            $generated[$type] =

<<<CSS
/**
 * $comment
 */
$selector {

}

CSS;
        }

        return $generated;
    }

    /**
    * Generates the JS logic code for the widget.
    * @param bool|null $overridesParent Whether the widget completely replaces its parent
    * @return string Code
    */
    private function setJS($overridesParent = false) {
        $namespace = 'Custom.Widgets.' . str_replace('/', '.', $this->folderPath) . ".{$this->name}";
        list($toExtend, $constructor) = $this->getJavascriptExtendee($overridesParent);
        $viewCalls = $this->getJavascriptViewCalls();
        $ajaxCalls = $this->getJavascriptAjaxCalls();

        return

<<<JS
RightNow.namespace('$namespace');
$namespace = $toExtend.extend({ $constructor
    /**
     * Sample widget method.
     */
    methodName: function() {

    }$ajaxCalls$viewCalls
});
JS;

    }

    /**
     * Generates the JS view(s) for the widget, including any ancestors.
     * @param bool|null $overridesParent Whether the widget
     * completely replaces its parent
     * @return array Each key is the filename to write
     *  and each value is the string view content
     */
    private function setJSView($overridesParent = false) {
        $views = null;
        if ($this->parent) {
            $parent = $this->parent;
            if ($this->baseAncestor && $this->baseAncestor->absolutePath !== $this->parent->absolutePath) {
                // find the base view to use - either the base ancestor, or some parent that
                // overrode the view and logic files
                while ($parent && isset($parent->meta['extends']) && $parent->meta['extends'] && !isset($parent->meta['extends']['overrideViewAndLogic'])) {
                    $parent = Registry::getWidgetPathInfo($parent->meta['extends']['widget']);
                }
            }
            $views = $this->getJSViews($parent, $overridesParent);
        }
        return $views ?: array('view' => '');
    }

    /**
     * Returns the JS view(s) for the widget.
     * @param PathInfo $parent The parent widget to return the view for
     * @param bool|null $overridesParent Whether the widget
     * completely replaces its parent
     * @return array Each key is the filename to write
     *  and each value is the string view content
     */
    private function getJSViews(PathInfo $parent, $overridesParent = false) {
        $views = array();
        $parentViews = Widgets::getJavaScriptTemplates($parent->absolutePath);

        if (is_array($parentViews)) {
            foreach ($parentViews as $fileName => $viewContent) {
                $view = '';

                if (!$overridesParent && !empty($viewContent)) {
                    $viewContent = SimpleHtmlDom\str_get_html($viewContent, true, true, DEFAULT_TARGET_CHARSET, false);

                    if ($viewContent === false) continue; // Parent view is empty

                    $view = $this->getBlockBoilerPlate($viewContent);
                }

                $views[$fileName] = $view;
            }
        }
        return $views;
    }

    /**
     * Generates the PHP controller code for the widget.
     * @return string Code
     */
    private function setPhp() {
        $extendFrom = $this->getControllerExtendee();
        $overrides = $this->getParentPhpMethods();
        $folderPath = $this->replaceForwardSlashes($this->folderPath);
        $ajaxInit = $ajaxMethods = '';

        if ($ajaxAttrs = $this->getAjaxAttributes(true)) {
            $ajaxNames = $ajaxMethods = '';
            foreach ($ajaxAttrs as $name) {
                $ajaxNames .=

<<<PHP

            '$name' => array(
                'method'      => 'handle_$name',
                'clickstream' => 'custom_action',
            ),
PHP;

                $ajaxMethods .=

<<<PHP


    /**
     * Handles the $name AJAX request
     * @param array \$params Get / Post parameters
     */
    function handle_$name(\$params) {
        // Perform AJAX-handling here...
        // echo response
    }
PHP;
            }
            $ajaxInit =

<<<PHP


        \$this->setAjaxHandlers(array($ajaxNames
        ));
PHP;
        }

        return

<<<PHP
<?php
namespace Custom\Widgets\\{$folderPath};

class {$this->name} extends $extendFrom {
    function __construct(\$attrs) {
        parent::__construct(\$attrs);$ajaxInit
    }

    function getData() {

        return parent::getData();

    }$ajaxMethods$overrides
}
PHP;
    }

    /**
     * Generates the view code for the widget.
     * @param bool|null $overridesParent Whether the widget
     * completely replaces its parent
     * @return string view contents
     */
    private function setView($overridesParent = false) {
        $this->overridesParent = $overridesParent;

        $view = '';
        if ($this->parent && FileSystem::isReadableFile($this->parent->view)) {
            if ($overridesParent) {
                return "<? /* Overriding {$this->parent->className}'s view */ ?>\n" . $this->getDefaultView($this->name);
            }

            // only get the rn:block tags from the original ancestor
            foreach ($this->getSubViews($this->parent) as $widget) {
                $html = SimpleHtmlDom\str_get_html($widget['view'], true, true, DEFAULT_TARGET_CHARSET, false);

                if ($html === false) continue; // Parent view is empty

                $view .= $this->getBlockBoilerPlate($html, $widget['name'] . '-');
            }

            $partials = $this->getViewPartials($this->parent);
            foreach ($partials as $name => $content) {
                $html = SimpleHtmlDom\str_get_html($content, true, true, DEFAULT_TARGET_CHARSET, false);
                $partials[$name] = '';
                if ($html === false) continue;

                $partials[$name] = $this->getBlockBoilerPlate($html);
            }
            $this->components['viewPartials'] = $partials;
        }
        else if(!$this->parent) {
            $view = $this->getDefaultView();
        }

        return $view;
    }

    /**
     * Return the default PHP view
     * @param String $content Content to be inserted into the view
     * @return String the view
     */
    private function getDefaultView($content = '') {
        return <<<PHP
<div id="rn_<?= \$this->instanceID ?>" class="<?= \$this->classList ?>">
{$content}
</div>
PHP;
    }

    /**
     * Returns the views of all sub-widgets within a widget.
     * @param PathInfo $widget PathInfo for a widget
     * @param array &$views Pass-by-reference to combine views; used for recursion only: do not use
     * @return array
     *  Each item in the array contains the following keys:
     *  -view: String view content of the widget
     *  -name: String name of the widget
     */
    private function getSubViews(PathInfo $widget, array &$views = array()) {
        $view = @file_get_contents($widget->view);

        $views []= array(
            'view' => $view,
            'name' => $widget->className,
        );

        foreach (Documenter::getContainingWidgets($view) as $subWidget) {
            self::getSubViews($subWidget, $views);
        }

        return $views;
    }

    /**
     * Populates a list of view partials for the given widget. Navigates the widget's
     * inherited view hierarchy, starting with the highest ancestor, populating view
     * partial contents. In that way, ancestor-most views have precedence and aren't
     * overwritten with the contents of extending views (which contain only blocks).
     * @param Object $widget PathInfo for a widget
     * @return array           Empty if no partials, if populated,
     *                               partial file name → partial file content
     */
    private function getViewPartials(PathInfo $widget) {
        $partials = array();
        $widgetFileInfo = Widgets::getWidgetInfo($widget);

        if (is_string($widgetFileInfo)) return $partials;

        $ancestors = (isset($widgetFileInfo['extends_info']) && !is_null($widgetFileInfo['extends_info']))
            ? $widgetFileInfo['extends_info']['view']
            : array();
        $ancestors []= $widget->relativePath;

        while ($ancestors) {
            $ancestor = array_shift($ancestors);
            $widgetFileInfo = Widgets::getWidgetInfo(Registry::getWidgetPathInfo($ancestor));
            if (isset($widgetFileInfo['view_partials']) && $widgetFileInfo['view_partials']) {
                foreach ($widgetFileInfo['view_partials'] as $partialName) {
                    if (!$partials[$partialName]) {
                        $partials [$partialName] = @file_get_contents("{$widgetFileInfo['absolutePath']}/{$partialName}");
                    }
                }
            }
        }

        return $partials;
    }

    /**
     * Returns a namespaced PHP class for the widget to extend.
     * @return string Namespaced class
     */
    private function getControllerExtendee() {
        return ($this->parent) ? $this->parent->namespacedClassName : '\RightNow\Libraries\Widget\Base';
    }

    /**
     * Returns the constructor to use for the widget JS:
     * straight-up or within an `overrides` block.
     * @param bool $overridesParent Whether we're overriding parent JS class
     * @return string Constructor code
     */
    private function getJavascriptExtendee($overridesParent) {
        if (!$overridesParent && $this->parent && FileSystem::isReadableFile($this->parent->logic)) {
            $toExtend = $this->parent->jsClassName;
            $overrides = $this->getParentJSMethods();
            $constructor =

<<<JS

    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides $toExtend#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();
        }$overrides
    },

JS;
        }
        else {
            // Widget either:
            // - Doesn't have a parent
            // - Parent doesn't have JS
            // - Chose to override the view (thus parent JS isn't used and widget is forced to use its own JS)
            $toExtend = 'RightNow.Widgets';
            $constructor =

<<<JS

    /**
     * Widget constructor.
     */
    constructor: function() {

    },

JS;
        }

        return array($toExtend, $constructor);
    }

    /**
     * Generate a default widget controller AJAX function call which can be inserted into the
     * generated widget's controller.
     * @return string The generated content
     */
    private function getJavaScriptAjaxCalls() {
        return $this->generateJavascriptProperties($this->getAjaxAttributes(), function($index, $name) {
            $methodName = 'get' . ucfirst($name);
            $callbackName = $name . 'Callback';
            return

<<<JS


    /**
     * Makes an AJAX request for `{$name}`.
     */
    {$methodName}: function() {
        // Make AJAX request:
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            // Parameters to send
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.{$name}, eventObj.data, {
            successHandler: this.{$callbackName},
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `{$name}`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #{$methodName}
     */
    {$callbackName}: function(response, originalEventObj) {
        // Handle response
    }
JS;

        });
    }

    /**
     * Returns sample calls to render JS views;
     * each call is wrapped in a widget method.
     * @return string Sample calls or empty string
     *  if there are no JS views for the widget
     */
    private function getJavascriptViewCalls() {
        return $this->generateJavascriptProperties(isset($this->components['jsView']) && $this->components['jsView'] ? $this->components['jsView'] : array(), function($name, $value) {
            $methodName = 'render' . ucfirst($name);
            return

<<<JS


    /**
     * Renders the `{$name}.ejs` JavaScript template.
     */
    {$methodName}: function() {
        // JS view:
        var content = new EJS({text: this.getStatic().templates.$name}).render({
            // Variables to pass to the view
            // display: this.data.attrs.display
        });
    }
JS;

        });
    }

    /**
     * Returns the widget's ajax attributes.
     * @param bool $isController Whether ajax endpoint is within controller
     * @return array Contains names of ajax
     * attributes; empty if there are none
     */
    private function getAjaxAttributes($isController = false) {
        $ajaxAttrs = array();
        $attrs = $this->mergeParentAttributes();

        foreach ($attrs as $name => $attr) {
            if ($attr->type === 'ajax' && (!$isController || $attr->default === '/ci/ajax/widget')) {
                $ajaxAttrs []= $name;
            }
        }
        return $ajaxAttrs;
    }

    /**
     * Produces (commented-out) method signatures for the widget
     * controller for every non-private method in its parent.
     * - Looks only at the immediate parent's code
     * - Matches on commented out function signatures
     * - Ignores #__construct, #getData, and private methods
     *   in the parent
     * @return null|string Null if nothing found, String code otherwise
     */
    private function getParentPhpMethods() {
        if (!$this->parent) return;
        
        $code = $matches = $methods = '';

        // this could be null if extending an extended widget that didn't extend the controller
        if ($this->parent->controller && FileSystem::isReadableFile($this->parent->controller))
            $code = file_get_contents($this->parent->controller);

        // now recursively get any protected methods from any other parents
        $parent = $this->parent;
        while (isset($parent->meta['extends']) && $parent->meta['extends']) {
            $parent = Registry::getWidgetPathInfo($parent->meta['extends']['widget']);
            if ($parent->controller && FileSystem::isReadableFile($parent->controller))
                $code .= file_get_contents($parent->controller);
        }

        /*
        * In lieu of #token_get_all (method missing) and loading arbitrary PHP code to do some reflection...
        * Regular Expressions!!!
        * This will dumbly match commented-out code and things like `privatestatic function foo()`.
        * It captures all args, including default arg assignments.
        */
        if (preg_match_all("/(protected|public|private)*(\s*static)?\s*function\s*([\w_]*)\((.*)(?=\))\)[\s\r\n]*/", $code, $matches)) {
            list($declarations, $accesses, $statics, $names) = $matches;
            $parentName = $this->parent->className;
            $methods = '';
            foreach ($declarations as $index => $match) {
                $match = trim($match);
                $access = trim($accesses[$index]);
                $name = $names[$index];

                if ($access === 'private' || $name === '__construct' || $name === 'getData') continue;

                $methods .=

<<<PHP

    // $match
PHP;
            }

            if ($methods) {
                $preface =

<<<PHP


    /**
     * Overridable methods from $parentName:
     */
PHP;
                $methods = "$preface$methods";
            }
        }

        return $methods;
    }

    /**
     * Produces (commented-out) method signatures for the widget
     * JS for every method in its parent.
     * - Looks only at the immediate parent's code
     * - Matches on commented out function signatures
     * - Ignores #constructor this #getJavascriptExtendee already produces that
     * @return null|string Null if nothing found, String code otherwise
     */
    private function getParentJSMethods() {
        if (!$this->parent) return;

        $code = file_get_contents($this->parent->logic);

        /*
        * In lieu of the v8js extension, regular expressions.
        * This will dumbly match commented-out code and anything like `\n name : function()`
        * event if it's declared in a hash _within_ a widget method.
        * It captures all args.
        */
        if (preg_match_all("/\n\s*(([\w\$]*)\s*:\s+function\s*\((.*)(?=\))\))\s*\{/", $code, $matches)) {
            list($fullMatches, $signatures, $names) = $matches;
            $parentName = $this->parent->className;
            $methods =

<<<JS


        /**
         * Overridable methods from $parentName:
         *
         * Call `this.parent()` inside of function bodies
         * (with expected parameters) to call the parent
         * method being overridden.
         */
JS;

            foreach ($fullMatches as $index => $match) {
                if ($names[$index] === 'constructor') continue;

                $methods .=

<<<JS

        // $signatures[$index]
JS;
            }
        }

        return $methods;
    }

    /**
     * Handles JSON-style property declaration for widget bodies.
     * @param array $toIterate Data to iterate thru
     * @param \Closure $callback To call for each iteration, passing key and value;
     *  expects a string return value
     * @return string Code; a comma is prefixed to the beginning if properties exist
     */
    private function generateJavascriptProperties(array $toIterate, \Closure $callback) {
        $code = '';
        $count = 0;
        $numberOfItems = count($toIterate);

        foreach ($toIterate as $key => $value) {
            $code .= $callback($key, $value)
                  . ((++$count === $numberOfItems) ? '' : ',');
        }

        if ($code) {
            return ",$code";
        }
        return $code;
    }

    /**
     * Replaces '/'' with '\' for use in a PHP namespace.
     * @param string $path Path to replace slashes in
     * @return string Path with slashes replaced
     */
    private function replaceForwardSlashes($path) {
        return str_replace('/', '\\', $path);
    }

    /**
     * Validates the widget name.
     * @param string $name Widget name
     * @return string Widget name or empty if invalid
     */
    private function validateName($name) {
        return $this->validate($name, "/^[a-zA-Z0-9_-]+$/");
    }

    /**
     * Validates the widget path.
     * @param string $path Widget path
     * @return string Widget path or empty if invalid
     */
    private function validatePath($path) {
        // TK - more robust: handling multiple slashes in a row and a limit to dirs
        return $this->validate($path, "/^[a-zA-Z0-9\/_-]+$/");
    }

    /**
     * Validates a string against a regular expression.
     * @param string $value Value to validate
     * @param string $regex Regular expression to use
     * @return string Trimmed value or empty if invalid
     */
    private function validate($value, $regex) {
        $value = trim($value, ' /\\');
        if (preg_match($regex, $value) === 1) {
            return $value;
        }
        $this->addError(sprintf(Config::getMessage(PCT_S_IS_INVALID_MSG), $value));
        return '';
    }

    /**
     * Adds a message to the errors array.
     * @param string $problem Error message
     * @return bool False so that callers can simply return `$this->addError`
     */
    private function addError($problem) {
        $this->errors []= $problem;
        return false;
    }

    /**
     * Pulls a list of items out of $components and adds
     * each item as a top-level item in $components.
     * @param string $keyForFiles   Index to array of key-val
     *                               pairs to insert into $components
     * @param array $components    List of components
     * @param string $fileExtension Optional file extension to add to
     *                               each key set on components
     * @return array                Components populated with new key-vals
     *                                         and with index $keyForFiles removed
     */
    private function mergeFileListIntoComponents($keyForFiles, array $components, $fileExtension = '') {
        $fileList = isset($components[$keyForFiles]) ? $components[$keyForFiles] : null;

        if ($fileList && is_array($fileList)) {
            foreach ($fileList as $name => $content) {
                $components[$name . $fileExtension] = $content;
            }
        }

        unset($components[$keyForFiles]);

        return $components;
    }

    /**
     * Returns a copy of `$this->attributes` where,
     *  - Any attributes that are identical between parent and child are removed
     *  - Any attributes existing in the parent but not the child are `unset`
     * @param array|null $parentManifest Optional parent's manifest data
     * @return array
     */
    private function mergeParentAttributes($parentManifest = null) {
        $parent = $this->parent;
        $attributes = $this->attributes;
        if (!$parent || !$attributes) {
            return $attributes;
        }

        $parentManifest || ($parentManifest = $parent->meta);
        $parentManifest = Widgets::convertAttributeTagsToValues($parentManifest, array('validate' => false, 'eval' => true));
        $parentAttributes = $parentManifest['attributes'] ?: array();
        foreach ($parentAttributes as $key => $values) {
            if (!$attributes[$key]) {
                $attributes[$key] = 'unset';
                continue;
            }

            $modified = false;
            foreach (array('type', 'description', 'default', 'required', 'options') as $attribute) {
                //If value is an array, do a loose comparison as we don't care about order, just key/values
                $attributeFromValue = isset($values->$attribute) ? $values->$attribute : '';
                $attributeFromKey = isset($attributes[$key]->$attribute) ? $attributes[$key]->$attribute : '';
                if (is_array($attributeFromValue) || is_array($attributeFromKey)){
                    if($attributeFromValue && $attributeFromKey && $attributeFromValue != $attributeFromKey){
                        $modified = true;
                        break;
                    }
                }
                else if (html_entity_decode(strtolower($attributeFromValue)) !== strtolower($attributeFromKey)) {
                    $modified = true;
                    break;
                }
            }
            if (!$modified) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    /**
     * Returns the generated info.yml contents for the widget.
     * @return string YAML
     */
    private function getManifest() {
        if ($this->parent) {
            $manifest = array(
                'extends' => array(
                    'widget'     => $this->parent->relativePath,
                    'components' => $this->getParentComponents(),
                ),
                'attributes' => $this->mergeParentAttributes($this->parent->meta),
            );
            if ($this->overridesParent) {
                $manifest['extends']['overrideViewAndLogic'] = 'true';
            }
            if ($this->parent->meta['requires'] && ($yui = isset($this->parent->meta['requires']['yui']) ? $this->parent->meta['requires']['yui'] : null)) {
                $manifest['requires'] = array('yui' => $yui);
            }
        }
        else {
            $manifest = array('attributes' => $this->attributes);
        }
        if ($this->yuiModules) {
            if ($manifest['requires']) {
                // Parent has YUI modules: Combine, remove dupes, reindex
                $manifest['requires']['yui'] = array_values(array_unique(array_merge($manifest['requires']['yui'], $this->yuiModules)));
            }
            else {
                $manifest['requires'] = array('yui' => $this->yuiModules);
            }
        }
        if ($this->info) {
            $manifest['info'] = array();
            if ($description = (isset($this->info['description']) ? $this->info['description'] : null)) {
                $manifest['info']['description'] = $description;
            }
            if ($urlParams = $this->info['urlParams']) {
                $manifest['info']['urlParameters'] = $urlParams;
            }
            if ($jsModule = $this->info['jsModule']) {
                isset($manifest['requires']) || ($manifest['requires'] = array());
                $manifest['requires']['jsModule'] = $jsModule;
            }
        }

        return Widgets::buildManifest($manifest, true);
    }

    /**
     * Returns an array of the components for use in
     * the widget's info.yml when the widget has a parent.
     * @return array Array with the proper keys; if the widget intends
     *  to override its parent's view and js, then the proper
     *  deletion of keys occurs.
     */
    private function getParentComponents() {
        $components = $this->components;

        unset($components['jsView'], $components['viewPartials']);

        if (isset($components['parentCss']) && $components['parentCss']) {
            // Rename parentCss to css for info.yml.
            $components['css'] = $components['parentCss'];
            unset($components['parentCss']);
        }
        if ($this->overridesParent) {
            unset($components['view'], $components['js']);
        }

        return array_keys($components);
    }

    /**
     * Returns boiler plates for all found rn:blocks
     * @param SimpleHtmlDom\simple_html_dom $html SimpleHtmlDom object
     * @param string $prefix Prefix to add to the block IDs
     * @return string View contents with rn:blocks
     */
    private function getBlockBoilerPlate(SimpleHtmlDom\simple_html_dom $html, $prefix = '') {
        $view = '';
        $blockIDs = array();
        foreach ($html->find('rn:block') as $block) {
            $blockID = $block->id;
            if (in_array($blockID, $blockIDs))
                continue;
            $blockIDs[] = $blockID;
            $view .= sprintf($this->blockBoilerPlate, $prefix . $blockID);
        }
        return $view;
    }
}

/**
 * Interacts with the file system.
 */
class FileWriter {
    protected $baseDir = '';

    /**
     * The only thing in this class that's specific to
     * widgets:
     * Sets the widget's base directory.
     *  - absolute (.../customer/development/widgets/custom/...)
     *  - version directory (1.0) is added after $baseDir
     * Should be called before anything else.
     * @param string $baseDir Relative custom widget path
     *  e.g. 'custom/foo/Bar', 'custom/a/b/c/d/'
     * @return string The full, absolute path that's set
     */
    public function setDirectory($baseDir) {
        return $this->baseDir = CUSTOMER_FILES . 'widgets/' . trim($baseDir, '/') . '/1.0';
    }

    /**
     * Checks if the directory set via #setDirectory
     * already exists.
     * @return bool Whether the directory already exists
     */
    public function directoryAlreadyExists() {
        return $this->baseDir !== '' && FileSystem::isReadableDirectory($this->baseDir);
    }

    /**
     * Writes the directory for the widget specified via #setDirectory.
     * @return bool Whether the operation succeeded.
     */
    public function writeDirectory() {
        if (!$this->baseDir) return false;

        umask(IS_HOSTED ? 0002 : 0);
        try {
            FileSystem::mkdirOrThrowExceptionOnFailure($this->baseDir, true);
        }
        catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Writes a file out.
     * @param string $file Absolute path to the file
     * @param string $contents Contents to write to the file
     * @param bool|null $absolutePathSpecified Whether the
     *  path specified in $file is absolute (T) or whether the
     *  directory specified via #setDirectory should be used
     *  (F or unspecified)
     * @return bool True if successful, False otherwise
     */
    public function write($file, $contents, $absolutePathSpecified = false) {
        if (!$this->baseDir && !$absolutePathSpecified) return false;

        $file = ($absolutePathSpecified) ? $file : "{$this->baseDir}/$file";
        try {
            FileSystem::filePutContentsOrThrowExceptionOnFailure($file, $contents);
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }
}
