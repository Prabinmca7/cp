<?php

namespace RightNow\Utils;

use RightNow\Internal\Libraries\Widget\Registry;

/**
 * Methods dealing specifically with rendering widgets.
 */
final class Widgets extends \RightNow\Internal\Utils\Widgets
{
    /**
     * Initializes a widget and does all necessary calls
     *
     * @param string $widgetPath The file system path to the widget
     * @param array|null $attributes An array of attributes passed into the widget
     * @param string|null $libraryName Library name
     * @param string|null $widgetPosition Widget character position detail in the source
     * @return string The html/php/js code to display the widget
     * @internal
     */
    public static function rnWidgetRenderCall($widgetPath, $attributes = array(), $libraryName = '', $widgetPosition = null)
    {
        if (!$widget = Registry::getWidgetPathInfo($widgetPath))
        {
            if(!IS_OPTIMIZED && Registry::isWidgetOnDisk($widgetPath)) {
                return \RightNow\Libraries\Widget\Base::widgetError($widgetPath, sprintf(Config::getMessage(WIDGET_EXISTS_ACTIVATED_WIDGET_MSG), "/ci/admin/versions/manage#filters=both%3Anotinuse"));
            }
            return \RightNow\Libraries\Widget\Base::widgetError($widgetPath, Config::getMessage(WIDGET_LOADED_WIDGET_CONTAINED_MSG));
        }
        $attributes = self::addInheritedAttributes($attributes);

        self::pushAttributesOntoStack($attributes);
        $result = (IS_OPTIMIZED) ? self::widgetInProduction($widget, $attributes, $libraryName)
                                 : self::widgetInDevelopment($widget, $attributes, $widgetPosition);
        self::popAttributesFromStack();
        return $result;
    }

    /**
     * Returns the relative widget directory including its version and appends a slash to it. Used to build up a
     * directory path to a widget file.
     * @param string $widgetPath The relative path to the widget (i.e. standard/... or custom/...)
     * @return string The widget path including it's version directory (e.g. standard/sample/SampleWidget/1.4.5/)
     */
    public static function getFullWidgetVersionDirectory($widgetPath){
        $versionDirectory = '';
        if(!IS_TARBALL_DEPLOY) {
            try {
                $versionDirectory = self::getWidgetVersionDirectory($widgetPath);
            }
            catch (\Exception $e) {
                $versionDirectory = 'unknown_version';
            }
            if($versionDirectory){
                $versionDirectory .= '/';
            }
        }
        return $widgetPath . '/' . $versionDirectory;
    }

    /**
     * Pushes the current list of attributes onto the stack so they can be inherited by any child widgets.
     * @param array|null &$attributes List of attributes to push onto stack
     * @return void
     * @throws \Exception If $attributes is not an array
     * @internal
     */
    public static function pushAttributesOntoStack(&$attributes)
    {
        if (!is_array($attributes))
            throw new \Exception(Config::getMessage(ARG_PASSED_PUSHPROPERTIESONTOSTACK_MSG));
        array_push(self::$widgetAttributeStack, $attributes);
    }

    /**
     * Removes the top level of attributes from the stack so they don't affect other widgets.
     * @return Returns value of the attribute on the top of the stack or null if the stack is empty
     * @internal
     */
    public static function popAttributesFromStack()
    {
        if (count(self::$widgetAttributeStack))
            return array_pop(self::$widgetAttributeStack);
        return null;
    }

    /**
     * Combines and inserts all of the constraints gathered up to this point in the page and generates a
     * validation token over those constraints to ensure that the submitted data has not been altered.
     * @param string $page The endpoint where these constraints will be `POST`ed
     * @param string $handler The PostRequest endpoint that will handle the `POST`ed data
     * @return string The literal HTML code output on the page
     */
    public static function addServerConstraints($page, $handler) {
        if(!Framework::validatePostHandler($handler)) {
            Framework::addErrorToPageAndHeader(sprintf(Config::getMessage(PCT_S_TG_ERR_INV_SYNTAX_POST_MSG), 'RN:FORM', $handler, '\RightNow\Libraries\PostRequest'));
            return;
        }

        $constraints = base64_encode(json_encode(\RightNow\Libraries\Widget\Base::getFormConstraints()));
        $validationToken = Framework::createPostToken($constraints, $page, $handler);
        \RightNow\Libraries\Widget\Base::resetFormConstraints();
        return "<input type='hidden' name='validationToken' value='$validationToken'/>
                <input type='hidden' name='constraints' value='$constraints'/>
                <input type='hidden' name='handler' value='$handler'/>";
    }

    /**
     * Requires the widget's optimizedWidget.php file.
     * Does not check if the widget's class is already defined
     * and does not include any dependencies.
     * @param  string $relativePath Widget's relative path
     * @internal
     */
    public static function requireOptimizedWidgetController($relativePath) {
        // The side effect of executing the PathInfo constructor is that,
        // while in an optimized mode, its optimizedWidget.php file is required
        // if the widget's class does not already exist.
        Registry::getWidgetPathInfo($relativePath);
    }
}
