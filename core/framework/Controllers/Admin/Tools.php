<?php

namespace RightNow\Controllers\Admin;

use RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Widget\Builder,
    RightNow\Internal\Libraries\Widget\Documenter,
    RightNow\Utils\Config;

/**
 * Provides CP admin tooling capabilities.
 */
class Tools extends Base {
    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
    }

    function index() {
        $this->_render('tools/index', array(), Config::getMessage(TOOLS_LBL));
    }

    /**
     * Acts as the interface for going back to CP2.
     * Via GET: renders migrateFramework view
     * Via POST:
     *  - Changes the framework version to 2.0
     *  - Development mode is set
     *  - User is redirected to /
     *  OR re-renders the view if there's an error
     */
    function migrateFramework() {
        if (($downgradeAllowed = Config::getConfig(CP_DOWNGRADE_TO_V2_ALLOWED)) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once CPCORE . 'Internal/Utils/Admin.php';
            if (\RightNow\Internal\Utils\Admin::back2cp2()) {
                $this->_setCookie('location', 'development~' . \RightNow\Utils\Framework::createLocationToken('development', true), 860000);
                $this->_redirectAndExit('/');
            }
            $error = Config::getMessage(ERROR_CHANGING_FRAMEWORK_VERSION_MSG);
        }

        $this->_render('tools/migrateFramework',
            array('error' => isset($error) ? $error : null, 'downgradeAllowed' => $downgradeAllowed),
            Config::getMessage(FRAMEWORK_MIGRATION_LBL),
            array('css' => 'tools/migrateFramework'));
    }

    /**
     * Acts as a router for all /ci/tools/widgetBuilder requests.
     */
    function widgetBuilder() {
        $numArgs = func_num_args();
        $args = func_get_args();

        if ($numArgs === 0) {
            return $this->_widgetBuilder();
        }

        $method = "_{$args[0]}";
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), array_slice($args, 1));
        }
        show_404($args[0]);
    }

    /**
     * Validates the form token in the POST request
     * @return boolean True if token is valid else False
     */
    protected function _verifyPostCsrfToken() {
        //Allowing Hudson to migrate without token to run CpV2 testcases
        if (!IS_HOSTED && array_key_exists('REQUEST_URI', $_SERVER) && strpos($_SERVER['REQUEST_URI'], "/migrateFramework") !== -1) {
            return true;
        }
        return parent::_verifyPostCsrfToken();
    }

    /**
     * Used by unit tests
     */
    private function _extension() {
        return $this->_widgetBuilder();
    }

    /**
     * Used by unit tests
     */
    private function _standalone() {
        return $this->_widgetBuilder();
    }

    /**
     * Renders the widget builder page
     */
    private function _widgetBuilder() {
        return $this->_render('tools/widgetBuilder', array(
            'allWidgets' => Registry::getAllWidgets(),
        ), Config::getMessage(BUILD_A_NEW_WIDGET_LBL), array(
            'js' => 'tools/widgetBuilder/widgetBuilder',
            'css' => 'tools/widgetBuilder',
        ));
    }

    /**
     * Retrieves and renders the specified widget's properties as JSON.
     * @param string $property Widget property to retrieve; either
     * 'attributes', 'urlParameters', or 'jsModule'
     * @param string $widgetPath URL-encoded relative widget path
     */
    private function _getWidgetInfo($property, $widgetPath) {
        $widgetPath = urldecode($widgetPath);
        Registry::setTargetPages();
        if ($widget = Registry::getWidgetPathInfo($widgetPath)) {
            require_once CPCORE . 'Internal/Libraries/Widget/Documenter.php';

            if ($property === 'attributes') {
                require_once CPCORE . 'Internal/Libraries/Widget/Builder.php';
                $details = Builder::parseHTMLEntitiesOnAttributes(Documenter::getWidgetAttributes($widget), false);
            }
            else if ($property === 'urlParameters') {
                $details = Documenter::getWidgetInfo($widget, $property);
            }
            else if ($property === 'jsModule') {
                $details = Documenter::getWidgetRequirements($widget, $property);
            }

            if (is_string($details)) {
                $details = array('error' => $details);
            }
            $this->_renderJSONAndExit($details);
        }
    }

    /**
    * Builds and saves the widget specified by the posted object.
    * The JSON-encoded object must conform to the following structure:
    *   name: String widget name
    *   folder: String relative folder structure
    *   extendsFrom: String relative widget path
    *   components: {
    *       php:          Boolean
    *       ajax:         Boolean
    *       view:         Boolean
    *       jsView:       Boolean
    *       js:           Boolean
    *       parentCss:    Boolean (optional, may be specified if `extendsFrom` is specified)
    *       overrideView: Boolean (optional, may be specified if `extendsFrom` is specified)
    *   }
    *   attributes: {
    *       {attr_name}: {
    *           type: String (option|boolean|ajax|filepath|int|string|image),
    *           default: String default value,
    *           required: Boolean,
    *           description: String sentence or two,
    *           options: [ (only for option types)
    *               option_name1,
    *               option_name2,
    *           ]
    *       }
    *   }
    *   yuiModules: Array (optional, may be specified if `js` component is specified)
    *   info: (optional) {
    *     description: string
    *     urlParams: {}
    *     jsModule: []
    *   }
    */
    private function _buildWidget() {
        static $componentsToProcess = array(
            'php',
            'view',
            'jsView',
            'js',
            'parentCss',
        );

        $this->_verifyAjaxPost();

        if (!($widgetInfo = $this->input->post('widget')) || !($widgetInfo = @json_decode($widgetInfo, true))) {
            $this->_renderJSONAndExit(array('error' => Config::getMessage(THERE_WAS_PROBLEM_SUPPLIED_DATA_LBL)));
        }

        require_once CPCORE . 'Internal/Libraries/Widget/Builder.php';

        $widgetBuilder = new Builder($widgetInfo['name'], $widgetInfo['folder']);

        if ($widgetInfo['extendsFrom']) {
            Registry::setTargetPages();
            if ($parent = Registry::getWidgetPathInfo($widgetInfo['extendsFrom'])) {
                $widgetBuilder->setParent($parent);

                // find the base widget
                $nextParent = $parent;
                while ($nextParent && isset($nextParent->meta['extends'])) {
                    $nextParent = Registry::getWidgetPathInfo($nextParent->meta['extends']['widget']);
                }
                $widgetBuilder->setBaseAncestor($nextParent ?: $parent);
            }
            else {
                $this->_renderJSONAndExit(array('error' => sprintf(Config::getMessage(WIDGET_EXTEND_PCT_S_INVALID_MSG), $widgetInfo['extendsFrom'])));
            }
        }

        $widgetBuilder->setAttributes($widgetInfo['attributes'], true);
        $widgetBuilder->setYUIModules(array_unique((is_array($widgetInfo['yuiModules'])) ? $widgetInfo['yuiModules'] : array()));
        $widgetBuilder->setInfo($widgetInfo['info']);

        if ($components = $widgetInfo['components']) {
            foreach ($componentsToProcess as $component) {
                if (isset($components[$component]) && $components[$component]) {
                    $widgetBuilder->addComponent($component, $widgetInfo['extendsFrom'] && isset($components['overrideView']) && $components['overrideView']);
                }
            }
        }
        else {
            $this->_renderJSONAndExit(array('error' => Config::getMessage(NO_WIDGET_COMPONENTS_ARE_SPECIFIED_MSG)));
        }

        ($results = $widgetBuilder->save()) || ($results = array('error' => $widgetBuilder->getErrors()));

        $this->_renderJSONAndExit($results);
    }


}
