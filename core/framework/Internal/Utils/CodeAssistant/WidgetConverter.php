<?php

namespace RightNow\Internal\Utils\CodeAssistant;

use RightNow\Utils\Filesystem,
    RightNow\Utils\Text,
    RightNow\Internal\Utils\CodeAssistant as CodeAssistantUtils,
    RightNow\Utils\Config;

require_once CPCORE . 'Internal/Libraries/Widget/Builder.php';
require_once CPCORE . 'Internal/Utils/CodeAssistant/WidgetConverterWriter.php';

final class WidgetConverter {
    public static function getUnits() {
        $absolutePath = CUSTOMER_FILES . 'widgets/';

        $widgets = $units = array();
        foreach(CodeAssistantUtils::getFiles(CodeAssistantUtils::WIDGETS) as $file) {
            $widgetPath = Text::getSubstringAfter($file, $absolutePath, null);

            if($widgetPath !== null && $lastSlash = strrpos($widgetPath, '/')) {
                $widgetKey = substr($widgetPath, 0, $lastSlash);
                $fileName = substr($widgetPath, $lastSlash + 1, strlen($widgetPath));

                $widgets[$widgetKey][] = $fileName;
            }
        }

        //Include every widget which has a view file and does not have an info.yml file
        foreach($widgets as $widgetKey => $files) {
            if(!in_array('info.yml', $files) && in_array('view.php', $files)) {
                $units[] = $widgetKey;
            }
        }
        return $units;
    }

    public static function executeUnit($widgetKey, $context) {
        //Get all of the widget information
        $context->setAbsolutePath(CUSTOMER_FILES . 'widgets/');
        if(!$viewContent = $context->getFile($widgetKey . '/view.php')) {
            throw new \Exception(sprintf(Config::getMessage(VIEW_PHP_FILE_REQD_WIDGET_VIEW_FILE_MSG), $widgetKey . '/view.php'));
        }

        list($meta, $modifiedView) = \RightNow\Utils\Tags::parseMetaInfo($viewContent);
        if(!$meta['controller_path']) {
            throw new \Exception(Config::getMessage(CONTROLLERPATH_REQD_WIDGET_MSG));
        }

        $info = self::getWidgetInformation($meta, $widgetKey, $context);

        //Using the Builder generate a template for the new widget
        $segments = explode('/', $widgetKey);
        $builder = new \RightNow\Internal\Libraries\Widget\Builder(end($segments), implode('/', array_slice($segments, 1, -1)));

        if($parent = $info['parent']) {
            $builder->setParent(\RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo($parent));

            if($info['components']['view']) {
                $builder->addComponent('view');
            }
            if($info['extendCSS']) {
                $builder->addComponent('parentCss');
            }
        }

        if($info['components']['js']) {
            $builder->addComponent('js');
        }
        if($info['components']['php']) {
            $builder->addComponent('php');
        }

        //Create the controller and logic files if necessary
        if(!$builder->save(new WidgetConverterWriter($context, $info['components']), null, false)) {
            $errors = $builder->getErrors();
            throw new \Exception($errors[0]);
        }

        $targetPath = "$widgetKey/1.0";

        //If the widget isn't being extended, the view needs to be manually copied over.
        if(!$info['parent']) {
            if(!$context->modifyFile($widgetKey . '/view.php', $modifiedView)) {
                throw new \Exception(sprintf(Config::getMessage(MODIFY_VIEW_FILE_LOCATED_PCT_S_MSG), $widgetKey . '/view.php'));
            }
            $context->moveFile("$widgetKey/view.php", "$targetPath/view.php");
        }

        //Copy over the base.css if it exists
        if(($baseCSS = $info['components']['css']) && $context->fileExists($baseCSS['path'])) {
            $context->moveFile($baseCSS['path'], "$targetPath/base.css");
        }

        //Move preview directory if it exists
        $previewPath = CUSTOMER_FILES . "widgets/{$widgetKey}/preview";
        if (FileSystem::isReadableDirectory($previewPath) && FileSystem::listDirectory($previewPath, false, false, array('method', 'isFile'))) {
            $context->moveDirectory("$widgetKey/preview/", "$targetPath/preview/");
        }

        //Delete the old files
        foreach($info['components'] as $type => $component) {
            if(!$component['isStandard']) {
                $context->deleteFile($component['path']);
            }
        }
    }

    public static function postExecute($units) {
        $widgets = array();
        foreach($units as $widgetKey) {
            if(!\RightNow\Internal\Libraries\Widget\Registry::isWidgetOnDisk($widgetKey)) {
                throw new \Exception(Config::getMessage(WIDGET_S_SUCC_MIGRATED_ACTIVATED_MSG));
            }
            $widgets[$widgetKey] = "1.0";
        }

        if(!\RightNow\Internal\Utils\Widgets::modifyWidgetVersions($widgets)) {
            throw new \Exception(Config::getMessage(WIDGET_S_SUCC_MIGRATED_ACTIVATED_MSG));
        }
    }

    private static function getWidgetInformation($meta, $widgetKey, $context) {
        //Check the controller
        $controllerInformation = self::getControllerInformation($meta['controller_path'], $widgetKey, $context);

        //Check the logic
        $logicInformation = self::getLogicInformation($meta['js_path'], $controllerInformation['parent'], $widgetKey, $context);

        //Handle the CSS
        self::addMessagesForAntiquatedStyling($meta['css_path'], $widgetKey, $context);
        $parent = $controllerInformation['parent'] ?: $logicInformation['parent'];
        $styleInformation = self::getStyleInformation($meta['presentation_css'], $meta['base_css'], $parent, $widgetKey, $context);

        //Merge all the information
        $components = array();
        foreach(array('php' => $controllerInformation, 'js' => $logicInformation, 'css' => $styleInformation) as $infoKey => $information) {
            if(!$information) continue;

            if($information['component']) {
                $components[$infoKey] = $information['component'];
            }
        }

        //Tack on the view extension if applicable and add an informative message
        if($parent) {
            $components['view'] = self::getComponentArray($widgetKey . '/view.php');
            $context->addMessage(Config::getMessage(WIDGET_COMPONENTS_STD_WIDGETS_MSG));
        }

        return array(
            'extendCSS' => $styleInformation['extendCSS'],
            'components' => $components,
            'parent' => $parent
        );
    }

    private static function getControllerInformation($controller, $widgetKey, $context) {
        if($controller === $widgetKey) {
            //Just a custom widget
            return array(
                'component' => self::getComponentArray($widgetKey . '/controller.php')
            );
        }

        if(Text::beginsWithCaseInsensitive($controller, 'standard/')) {
            if($parent = \RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo($controller)) {
                //Valid standard widget, mark the parent
                return array(
                    'parent' => $controller
                );
            }

            if($context->fileExists(DOCROOT . '/euf/application/rightnow/widgets/' . $controller . '/controller.php')) {
                //Attempted to extend, but the parent no longer exists, bring over the old v2 standard controller code
                return array(
                    'component' => self::getComponentArray($controller . '/controller.php', true)
                );
            }

            throw new \Exception(Config::getMessage(CONTROLLERPATH_WIDGET_EX_WIDGET_MSG));
        }

        throw new \Exception(sprintf(Config::getMessage(CONTROLLERPATH_WIDGET_SET_CUST_MSG), $controller));
    }

    private static function getLogicInformation($logic, $parent, $widgetKey, $context) {
        if($logic) {
            if($parent) {
                if($logic === $widgetKey) {
                    //The controller extends a standard widget, but this uses a custom logic. Add the file and extend the standard widget.
                    return array(
                        'component' => self::getComponentArray($widgetKey . '/logic.js'),
                    );
                }

                if($logic === $parent) {
                    //Don't do anything, it's using a standard PHP and JS (this widget is extending, but not altering those files)
                    return array();
                }

                throw new \Exception(Config::getMessage(JSPATH_CONTROLLER_PATH_CONTENT_MSG));
            }

            if(Text::beginsWithCaseInsensitive($logic, 'standard/')) {
                if($parent = \RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo($logic)) {
                    //Use a standard JS, but a custom PHP, just add a parent. Having the PHP component will cause it to be extended.
                    return array(
                        'parent' => $logic
                    );
                }

                if($context->fileExists(DOCROOT . '/euf/application/rightnow/widgets/' . $logic . '/logic.js')) {
                    //Attempted to extend, but the parent no longer exists, bring over the old v2 standard logic.js
                    return array(
                        'component' => self::getComponentArray($logic . '/logic.js', true)
                    );
                }

                throw new \Exception(sprintf(Config::getMessage(JSPATH_WIDGET_EX_JS_PATH_MSG), $widgetKey));
            }

            if($logic === $widgetKey) {
                //Not extending, not using a standard widget, just bring over the custom logic.js
                return array(
                    'component' => self::getComponentArray($widgetKey . '/logic.js')
                );
            }

            throw new \Exception(sprintf(Config::getMessage(JSPATH_WIDGET_SET_CUST_WIDGET_MSG), $logic));
        }
    }

    private static function addMessagesForAntiquatedStyling($cssPath, $widgetKey, $context) {
        //In CP 1, we would load css out of the css/widgets directory. If that file exists, recommend that they move it manually and ignore it.
        if($context->fileExists(HTMLROOT . "/euf/assets/css/widgets/$widgetKey.css")) {
            $context->addMessage(sprintf(Config::getMessage(CUST_PORTAL_FRAMEWORK_VERSIONS_3_MSG), $widgetKey));
        }

        //'css_path' is no longer supported, ask them to convert it to the appropriate themed files
        if($cssPath) {
            $context->addMessage(sprintf(Config::getMessage(CSTPORTAL_FRAMEWORK_VERSIONS_3_MSG), $cssPath, $widgetKey));
        }
    }

    private static function getStyleInformation($presentation, $base, $parent, $widgetKey, $context) {
        //Only extend CSS if they've already extended a standard widget. We don't want to do this if they've got everything else custom.
        if($base && $presentation && $base === $parent && $presentation === self::getPresentationPath($parent)) {
            return array(
                'extendCSS' => true
            );
        }

        //Add in base.css messaging and move over the file if necessary
        $result = array();
        if($base) {
            if($base !== $widgetKey) {
                //The base css doesn't match the widget itself, or the parent it's extending.
                if($base !== $parent) {
                    $context->addMessage(sprintf(Config::getMessage(BSECSS_PATH_DOESNT_MATCH_WIDGET_MSG), $base, $widgetKey));
                }
                //The base css doesn't match the widget, but it does match the parent
                else {
                    $context->addMessage(sprintf(Config::getMessage(BSECSS_PATH_REFS_STD_WIDGET_MSG), $base, $widgetKey));
                }
            }
            //The base css matches the widget
            else {
                $result['component'] = self::getComponentArray($widgetKey . '/base.css');
            }
        }

        //Add in messaging for presentation.css - We don't need to move anything since the locations are the same.
        if($presentation) {
            if($presentation !== self::getPresentationPath($widgetKey)) {
                //Presentation CSS doesn't match the widget or the parent
                if($presentation !== self::getPresentationPath($parent)) {
                    $context->addMessage(sprintf(Config::getMessage(PRESENTATIONCSS_PATH_DOESNT_MSG), $presentation, self::getPresentationPath($widgetKey)));
                }
                //Matches the parent, but the base.css doesn't so don't extend
                else {
                    $context->addMessage(sprintf(Config::getMessage(PRESENTATIONCSS_PATH_REFS_STD_MSG), $presentation, self::getPresentationPath($widgetKey)));
                }
            }
        }

        return $result;
    }

    private static function getWidgetName($widgetKey) {
        $segments = explode('/', $widgetKey);
        return end($segments);
    }

    private static function getPresentationPath($widgetKey) {
        return sprintf("widgetCss/%s.css", self::getWidgetName($widgetKey));
    }

    private static function getComponentArray($path, $isStandard = false) {
        return array(
            'path' => $path,
            'isStandard' => $isStandard
        );
    }
}