<?php

namespace RightNow\Internal\Libraries\Widget;

/**
 * A simple class to provide basic file-based information about a widget.
 */
final class PathInfo {
    private $type;
    private $relativePath;
    private $absolutePath;
    private $version;
    private $shortVersion;
    private $controller;
    private $logic;
    private $view;
    private $className;
    private $namespacedClassName;
    private $namespace;
    private $jsClassName;
    private $meta = array();

    public function __construct($type, $relativePath, $absolutePath, $version)
    {
        $this->type = $type;
        $this->relativePath = $relativePath;
        $this->version = $version;
        $this->shortVersion = $version;
        if($version && $type === 'standard')
            $this->shortVersion = substr($version, 0, strrpos($version, '.'));
        // don't use version in the path for standard widgets during tarball deploy
        $versionInPaths = ($version && (!IS_TARBALL_DEPLOY || $type === 'custom')) ? '/' . $version : '';
        $this->absolutePath = $absolutePath . $versionInPaths;

        $this->controller = $this->absolutePath . '/controller.php';
        $this->logic = $this->absolutePath . '/logic.js';
        $this->view = $this->absolutePath . '/view.php';

        $widgetPathElements = explode("/", $relativePath);
        $widgetDirectory = end($widgetPathElements);
        $this->className = $widgetDirectory;

        $this->namespacedClassName = $type === 'standard'
            ? "\\RightNow\\Widgets\\" . $widgetDirectory
            : ($type === 'custom'
            ? "\\Custom\\Widgets\\" . implode("\\", array_slice($widgetPathElements, 1)) // remove beginning 'custom' element
            : "\\" . $widgetDirectory);

        $this->namespace = $type === 'standard'
            ? "RightNow\\Widgets"
            : ($type === 'custom'
            ? rtrim("Custom\\Widgets\\" . implode("\\", array_slice($widgetPathElements, 1, -1)), "\\") // remove beginning 'custom' element and widget name, handle widget being in 'root'
            : "");

        $this->jsClassName = str_replace("\\", ".", substr($this->namespacedClassName, 1));

        if(IS_OPTIMIZED && !IS_TARBALL_DEPLOY){
            $widgetHeaderFunction = "\\" . $this->namespace . "\\" . \RightNow\Utils\Widgets::generateWidgetFunctionName($this->relativePath, '_header');

            //This should only ever happen when we're doing an ajax call and the wigdet has it's own handler method. Otherwise, all widget classes should already be inlined
            if(!function_exists($widgetHeaderFunction)){
                if(!IS_HOSTED && get_instance()->router->fetch_class() !== 'ajax'){
                    throw new \Exception("The $widgetHeaderFunction method doesn't exist, but this isn't a widget Ajax call, why isn't it already included in the page?");
                }
                require_once $this->absolutePath . '/optimized/optimizedWidget.php';
            }
            if(function_exists($widgetHeaderFunction)){
                $this->meta = $widgetHeaderFunction();
            }
            else{
                exit(\RightNow\Utils\Config::getMessage(FATAL_ERR_WIDGET_CALLS_PHP_SUPP_MSG));
            }
        }
        else{
            $this->meta = \RightNow\Utils\Widgets::getWidgetInfoFromManifest($this->absolutePath, $this->relativePath);
        }

        if (is_array($this->meta) && isset($this->meta['extends']) && ($extends = $this->meta['extends']) && isset($extends['overrideViewAndLogic']) && !$extends['overrideViewAndLogic']) {
            foreach (array('php' => 'controller', 'js' => 'logic', 'view' => 'view') as $key => $component) {
                if (!array_key_exists($key, $extends['components'])) {
                    $this->$component = null;
                }
            }
        }
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        throw new \Exception("Property '$property' does not exist");
    }

    public function __toString()
    {
        return $this->relativePath;
    }
}