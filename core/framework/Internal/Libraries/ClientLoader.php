<?php
namespace RightNow\Internal\Libraries;

use RightNow\Api,
    RightNow\Utils\Tags,
    RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Text,
    RightNow\Utils\Widgets,
    RightNow\Utils\Framework,
    RightNow\Internal\Libraries\Widget\Registry;

class ClientLoader {
    const runtimeHeadContentPlaceholder = '9c1379bc-cca6-4750-aee7-188f8348a6c3';
    const runtimeAdditionalJavaScriptPlaceholder = 'e8ccfa38-6d4d-4158-8fa9-841dd1f67aca';

    const MODULE_STANDARD = 'standard';
    const MODULE_NONE = 'none';
    const MODULE_MOBILE = 'mobile';

    const OPEN_SCRIPT_TAG = "<script>\n/* <![CDATA[ */\n";
    const CLOSE_SCRIPT_TAG = "/* ]]> */\n</script>\n";

    private $widgetInstantiationCalls = array();
    private $widgetStatics = array();
    private $widgetInterfaceCalls = array();

    protected $javaScriptFiles = null;
    protected $includedHeadContent = null;

    public function __construct()
    {
        $this->javaScriptFiles = new JavaScriptFileManager();
    }

    /**
     * Returns JS include code for any additional JavaScript loaded
     * by individual widgets. This function is public because it is
     * called from optimized pages.
     * @return string The list of files in include syntax
     */
    public function getAdditionalJavaScriptReferences()
    {
        $additionalJavaScriptContent = '';

        foreach($this->javaScriptFiles->additional as $addedFile)
        {
            $additionalJavaScriptContent .= Tags::createJSTag($addedFile) . "\n";
        }

        foreach($this->javaScriptFiles->async as $addedFile)
        {
            $additionalJavaScriptContent .= Tags::createJSTag($addedFile, 'async defer') . "\n";
        }

        foreach($this->javaScriptFiles->asyncYUI as $args)
        {
            $additionalJavaScriptContent .= Tags::createYUIGetJsTag($args[0], $args[1], $args[2]) . "\n";
        }

        if(count($this->javaScriptFiles->inline))
        {
            $additionalJavaScriptContent .= "<script>\n";

            foreach($this->javaScriptFiles->inline as $inlineFile)
            {
                if($inlineFile['path'])
                {
                    //We've checked for the existence of the file already, so if it's in this list, we
                    //should be confident that it exists
                    $additionalJavaScriptContent .= file_get_contents($inlineFile['path']) . "\n";
                }
                else if($inlineFile['code'])
                {
                    $additionalJavaScriptContent .= $inlineFile['code'] . "\n";
                }
            }
            $additionalJavaScriptContent .= "</script>\n";
        }
        return $additionalJavaScriptContent;
    }

    /**
     * Adds a reference to a JavaScript file to add to the current page.
     * @param string $path Path to the JavaScript file
     * @param string $type Type of JS file being added
     * @param string $attributes Additional script tag attributes
     * @return string Script tag which points to the file
     * @internal
     */
    public function addJavaScriptInclude($path, $type, $attributes = '') {
        if($this->javaScriptFiles->addFile($path, $type))
            return Tags::createJSTag($path, $attributes);
    }

    /**
     * Loads the JavaScript resource(s) specified by $urls. Resource will be loaded asynchronously by default.
     * @param string|array $urls The path to the JS being loaded, or a list of paths.
     * @param array $options Options that control whether the resource is fetched asynchronously
     *                       as well as other options that can be passed along to `YUI.Get.js` such as a callback.
     *                       If options is empty, or only contains the 'async' attribute, the JS resource will be loaded via the `script` tag.
     *                       Specifying any other options, such as `callback` will result in loading the resource via `YUI.Get.js`.
     * @return string The code used to load the resource, either via the `script` tag or `YUI.Get.js`
     */
    public function loadJavaScriptResource($urls, array $options = array('async' => true)) {
        $script = '';
        if (!$options || (count($options) === 1 && array_key_exists('async', $options))) {
            $async = $options['async'];
            foreach ((is_array($urls) ? $urls : array($urls)) as $url) {
                $script .= $this->addJavaScriptInclude($url, $async ? 'async' : 'additional', $async ? 'async defer' : '') . '\n';
            }
        }
        else {
            if ($callback = $options['callback']) {
                unset($options['callback']);
            }
            $this->javaScriptFiles->asyncYUI[] = array($urls, $options, $callback);
            $script .= Tags::createYUIGetJsTag($urls, $options, $callback);
        }

        return $script;
    }

    /**
     * Sets the JavaScript module for the page. This will determine which JS files are
     * automatically loaded on the page. This function is public because it is called from optimized pages.
     * @param string $module The module to use. Supported options are standard, mobile, or none
     */
    public function setJavaScriptModule($module)
    {
        $this->javaScriptFiles->setModuleType($module);
    }

    /**
     * Returns the JavaScript module for the page.
     * @return string The current module, empty string if
     * none has been set
     */
    public function getJavaScriptModule()
    {
        return $this->javaScriptFiles->getModuleType();
    }

    /**
     * Produces a JS script snippet that contains the YUI_config to
     * use for the page.
     * @param  array $configuration YUI_config properties to set
     * @return string                script tag
     */
    public function getYuiConfigurationSnippet(array $configuration = array()) {
        return '<script>var YUI_config=' . str_replace('\/', '/', json_encode(self::getYuiConfigurationProperties($configuration) /*, TK - PHP 5.4 provides JSON_UNESCAPED_SLASHES option */)) . ";</script>\n";
    }

    /**
     * Returns necessary configuration items for YUI. Sets the base directory from which to dynamically load additional modules.
     * @param array $properties Array of additional properties to add to the returned YUI configuraiton.
     * @return string Script tag and content needed to configure YUI
     */
    private function getYuiConfigurationProperties(array $properties) {
        $config = self::getStaticYuiConfiguration();

        $comboService = '/ci/cache/yuiCombo/';
        if(IS_HOSTED && ($cachedContentServer = \RightNow\Utils\Config::getConfig(CACHED_CONTENT_SERVER))) {
            $comboService = "//{$cachedContentServer}{$comboService}";
        }

        $config += array('comboBase' => $comboService, 'groups' => self::getCustomYuiGroups());

        return ($properties) ? array_merge($config, $properties) : $config;
    }

    /**
     * Registration info for custom YUI modules that widgets can call for.
     * @return array modules
     */
    static function getCustomYUIModules() {
        return array(
            'RightNowTreeView'         => Url::getCoreAssetPath('debug-js/modules/ui/treeview.js'),
            'RightNowTreeViewDialog'   => Url::getCoreAssetPath('debug-js/modules/ui/treeviewdialog.js'),
            'RightNowTreeViewDropdown' => Url::getCoreAssetPath('debug-js/modules/ui/treeviewdropdown.js'),
        );
    }

    /**
     * Returns all content needed just before the closing body tag of the document
     * @param boolean $includeChat Include chat javascript files if true
     * @return string The content for the page before the closing body tag
     */
    public function getJavaScriptContent($includeChat)
    {
        $bodyContent = '';
        if($this->javaScriptFiles->moduleType !== self::MODULE_NONE) {
            $bodyContent .= $this->getYuiConfigurationSnippet();
            foreach($this->javaScriptFiles->yui as $yuiFile)
                $bodyContent .= Tags::createJSTag($yuiFile) . "\n";
            foreach($this->javaScriptFiles->framework as $frameworkFile)
                $bodyContent .= Tags::createJSTag($frameworkFile) . "\n";

            if($includeChat) {
                foreach($this->javaScriptFiles->chat as $chatFile)
                    $bodyContent .= Tags::createJSTag($chatFile) . "\n";
            }
        }

        foreach($this->javaScriptFiles->getDependencySortedWidgetJavaScriptFiles() as $widgetFile)
            $bodyContent .= Tags::createJSTag($widgetFile) . "\n";

        if($this->javaScriptFiles->moduleType !== self::MODULE_NONE) {
            $bodyContent .= $this->getClientInitializer() . "\n";
            if(!IS_REFERENCE) {
                foreach($this->javaScriptFiles->custom as $customFile) {
                    if(FileSystem::isReadableFile(APPPATH . $customFile) && filesize(APPPATH . $customFile)) {
                        $bodyContent .= Tags::createJSTag(Url::getLongEufBaseUrl('excludeProtocolAndHost', "/customer/development/{$customFile}")) . "\n";
                    }
                }
            }
        }
        return $bodyContent;
    }

    /**
     * Retrieves the list of YUI files loaded for this page
     * @return array
     */
    public function getYuiJavaScriptFiles()
    {
        return $this->javaScriptFiles->yui;
    }

    public function getChatJavaScriptFiles()
    {
        return $this->javaScriptFiles->chat;
    }

    /**
     * Retrieves the list of CP framework files loaded for this page
     * @return array
     */
    public function getFrameworkJavaScriptFiles()
    {
        return $this->javaScriptFiles->framework;
    }

    /**
     * Retrieves the list of widget helper files
     * @return array
     */
    public function getWidgetHelperJavaScriptFiles()
    {
        return $this->javaScriptFiles->widgetHelpers;
    }

    /**
     * Adds a new JavaScript widget instance to the page.
     * @param array $info Array with the following keys:
     *  jsonData: string JSON encoded data to be passed to the widget
     *  instanceID: string The ID of the widget instance
     *  path: string The path to the widget logic file
     *  className: string The name of the widgets JavaScript class
     *  widgetName: string The name of the widget (different from className if extending another widget's JS)
     *  widgetID: string The unique ID of the widget instance
     *  (optional)
     *  static: array Key-value properties that are static for the widget;
     *                  any values of complex-type data must be JSON-encoded
     */
    public function addWidgetJavaScriptInstance(array $info) {
        $contextData = $info['contextData'];
        $contextToken = $info['contextToken'];
        $instanceID = $info['instanceID'];
        $fullJavaScriptPath = $info['path'];
        $jsClassName = $info['className'];
        $suffix = $info['widgetID'];
        $formToken = $info['formToken'];

        $widgetInstanceCall = array(json_encode($info['data']), "'$contextData'", "'$contextToken'",  $info['timestamp'], "'$instanceID'", "'$fullJavaScriptPath'", "'$jsClassName'", "'$suffix'", "'$formToken'");
        if ($this->shouldThrowWidgetJavaScriptErrors())
            $widgetInstanceCall []= 'true';

        $this->widgetInstantiationCalls []= $widgetInstanceCall;

        if ($statics = $info['static']) {
            $this->addWidgetStatics(array(
                'fullJavaScriptPath' => $fullJavaScriptPath,
                'jsClassName' => $jsClassName,
                'statics' => $statics,
            ));
        }
    }

    /**
     * Parses and combines each widget CSS into a single block and adds
     * it to the head of the document. This also parses each widget looking for
     * JavaScript messagebase calls and adds them to the JavaScript section.
     *
     * @param array $widgetCalls An array of widget paths to widget info.  Widget info is an array with keys like 'meta'.
     * @param string|null $themePath Where to look for widget presentation CSS, etc.
     */
    public function parseSupportingWidgetContent(array $widgetCalls, $themePath = null)
    {
        if ($themePath)
            $this->combineWidgetCSS($widgetCalls, $themePath);

        $CI = get_instance();
        $pageMeta = method_exists($CI, '_getMetaInformation') ? $CI->_getMetaInformation() : array();
        $this->preProcessWidgetJavaScript($widgetCalls, $pageMeta);
    }

    /**
     * Parses the given content for JavaScript interface calls.
     * Adds all matches onto `widgetInterfaceCalls`.
     * Duplicates and pre-existing messages that are matched are
     * ignored.
     * @param string|array $content The content to parse
     * If given a string, parses the string.
     * If given an array, parses each value in the array, which
     * is assumed to be a string.
     * The keys in the array are used as the filename for any error
     * messages that are output if any of the matched calls are messed up.
     * @return array The first element is an array containing matched messages,
     *  the second element is an array containing matched configs
     */
    public function parseJavaScript($content) {
        if (!is_array($content)) {
            $content = array($content);
        }
        $configs = $messages = array();

        foreach ($content as $name => $code) {
            $interfaceCalls = Config::findAllJavaScriptMessageAndConfigCalls($code, (string) $name);
            $configs += $interfaceCalls['config'];
            $messages += $interfaceCalls['message'];
        }

        list($messages, $messageErrors) = Config::parseJavascriptMessages($messages, false, false);
        list($configs, $configErrors) = Config::parseJavascriptConfigs($configs, false, false);

        foreach (array_merge($messageErrors, $configErrors) as $error) {
            Framework::addDevelopmentHeaderError($error);
        }

        $this->convertWidgetInterfaceCalls($messages, $configs);

        return array($messages, $configs);
    }

    /**
     * Returns the content that is needed in the page <head> tag
     * @return string The content to add to the page head
     */
    public function getHeadContent()
    {
        return $this->includedHeadContent;
    }

    /**
     * Checks for a 'none' moduleType in a page set.
     * @return boolean True if moduleType is 'none', false otherwise
     */
    public function isJavaScriptModuleNone() {
        return $this->javaScriptFiles->moduleType === self::MODULE_NONE;
    }

    /**
     * Checks for a 'standard' moduleType in a page set.
     * @return boolean True if moduleType is '' or 'standard', false otherwise
     */
    public function isJavaScriptModuleStandard() {
        return $this->javaScriptFiles->moduleType === '' || $this->javaScriptFiles->moduleType === self::MODULE_STANDARD;
    }

    /**
     * Checks for a 'mobile' moduleType in a page set.
     * @return boolean True if moduleType is 'mobile', false otherwise
     */
    public function isJavaScriptModuleMobile() {
        return $this->javaScriptFiles->moduleType === self::MODULE_MOBILE;
    }

    /**
     * Retrieves the list of custom JS files loaded for this page
     * @return array
     */
    protected function getCustomJavaScriptFiles()
    {
        return $this->javaScriptFiles->custom;
    }

    /**
     * Creates the block of JavaScript code needed for the framework. This includes
     * a list of PHP constants, profile information, and widget instantiation calls.
     * This function is public because it is called from optimized pages.
     * @return string The JavaScript initializer code block
     */
    protected function getClientInitializer()
    {
        if(($module = $this->javaScriptFiles->moduleType) === self::MODULE_NONE) return '';

        $module || ($module = self::MODULE_STANDARD); // Unspecified module means standard module.
        $CI = get_instance();

        return self::OPEN_SCRIPT_TAG .
            "RightNow.Env=(function(){var _props={module:'$module',coreAssets:'" . Url::getCoreAssetPath() . "',yuiCore:'" . Url::getYUICodePath() . "',profileData:" . $this->getProfileData() . "};return function(prop){return _props[prop];};})();" .
            $this->getWidgetInterfaceCalls() .
            $this->getClientSession() .
            "YUI().use('event-base',function(Y){Y.on('domready',function(){" . $this->getWidgetInstantiationCode() . "})});\n" .
            self::CLOSE_SCRIPT_TAG .
            \RightNow\ActionCapture::createScriptSnippet(\RightNow\Utils\Config::getConfig(ACS_CAPTURE_HOST, 'RNW'), IS_OPTIMIZED, '/');
    }

    /**
     * Returns a partial JSON string containing profile data
     * @return string Partial JSON string of profile data
     */
    protected function getProfileData()
    {
        $profileDataArray = array(
            'isLoggedIn'            => Framework::isLoggedIn(),
            'previouslySeenEmail'   => get_instance()->session->getSessionData('previouslySeenEmail'),
        );

        if (Framework::isLoggedIn()) {
            $profile = get_instance()->session->getProfile(true);
            $profileDataArray = array_merge($profileDataArray, array(
                'contactID'     => $profile->contactID,
                'socialUserID'  => $profile->socialUserID,
                'firstName'     => $profile->firstName,
                'lastName'      => $profile->lastName,
                'email'         => $profile->email,
            ));
        }

        return json_encode($profileDataArray);
    }

    /**
     * Returns a block of JavaScript needed to instantiate all of the widgets on the page.
     * @return string The widget instantiation code (does not include surrounding <script> tags)
     */
    protected function getWidgetInstantiationCode()
    {
        // called within pages
        if($this->javaScriptFiles->moduleType === self::MODULE_NONE) return '';

        $code = '';
        if(count($this->widgetInstantiationCalls))
        {
            $code .= "var n=RightNow.namespace,W=RightNow.Widgets,c='createWidgetInstance';\n";

            foreach($this->widgetStatics as $static)
            {
                foreach($static['static'] as $key => $value)
                {
                    $code .= "n('{$static['class']}').{$key}={$value};\n";
                }
            }

            $code .= "W.setInitialWidgetCount(" . count($this->widgetInstantiationCalls) . ");\n";
            foreach($this->widgetInstantiationCalls as $instance)
            {
                $code .= 'W[c](' . implode(',', $instance) . ");\n";
            }
        }
        return $code;
    }

    /**
     * Takes JavaScript interface calls and converts them to their values.
     * Sets the results (or appends) on 'config' and 'message' keys  on the
     * `widgetInterfaceCalls` member variable.
     * This function is public because it is called from optimized pages.
     * @param array $messageBaseInformation Array of message base values
     * @param array $configBaseInformation Array of config base values
     */
    protected function convertWidgetInterfaceCalls(array $messageBaseInformation, array $configBaseInformation) {
        foreach ($messageBaseInformation as $slotName => $messageBaseDetails) {
            $value = $messageBaseDetails['value'];
            // Use #getMessage rather than #getMessageJS so that values aren't doubly-encoded (JSON-encoding happens later on).
            $messageBaseInformation[$slotName] = (is_string($value))
                ? $value                // Message has already been retrieved in Config.php#getCoreJavaScriptMessages.
                : \RightNow\Utils\Config::getMessage($value);
        }

        foreach ($configBaseInformation as $slotName => $configBaseDetails) {
            $configBaseInformation[$slotName] = \RightNow\Utils\Config::getConfig($configBaseDetails['value']);
        }

        if ($this->widgetInterfaceCalls) {
            $this->widgetInterfaceCalls['config'] += $configBaseInformation;
            $this->widgetInterfaceCalls['message'] += $messageBaseInformation;
        }
        else {
            $this->widgetInterfaceCalls = array(
                'config' => $configBaseInformation,
                'message' => $messageBaseInformation,
            );
        }
    }

    /**
     * Returns custom groups to use in the YUI configuration.
     * @return array custom groups
     */
    private static function getCustomYUIGroups() {
        return array(
            'gallery-treeview' => array(
                'base'    => Url::getYUICodePath('gallery-treeview/'),
                'modules' => array(
                    'gallery-treeview' => array('path' => 'gallery-treeview-min.js'),
                ),
            ),
        );
    }

    /**
     * Returns YUI configuration that doesn't rely on dynamic site configuration.
     * @return array static YUI configuration properties
     */
    private static function getStaticYuiConfiguration() {
        static $configuration;
        if (!$configuration) {
            $configuration = array(
                'fetchCSS'  => false,
                'modules'   => self::getCustomYUIModules(),
                // add en-US as a fallback in case YUI has trouble loading anything in the correct language
                'lang'      => array(Text::getLanguageCode(), 'en-US'),
                'injected'  => true,
            );
        }
        return $configuration;
    }

    /**
     * Adds head content for found CSS
     * @param array $widgetCalls An array of widget paths
     * @param string $themePath String Where to look for widget presentation CSS, etc.
     */
    private function combineWidgetCSS(array $widgetCalls, $themePath) {
        $css = '';
        foreach(array_keys($widgetCalls) as $widgetPath) {
            if(!$widget = Registry::getWidgetPathInfo($widgetPath))
                continue;
            $css .= Widgets::accumulateWidgetCss($widget, $themePath, true);
        }
        if(trim($css) !== '') {
            $this->addHeadContent("<style type='text/css'>\n$css</style>\n");
        }
    }

    /**
     * Looks for messagebase / configbase strings in widgets; looks for and includes
     * widget helper objects.
     * @param array $widgetCalls An array of widget paths
     * @param array $pageMeta Meta info for the page
     */
    private function preProcessWidgetJavaScript(array $widgetCalls, array $pageMeta) {
        if ($this->javaScriptFiles->getModuleType() === self::MODULE_NONE) return;

        $allWidgetInterfaceCalls = $widgetHelperCalls = array();

        foreach($widgetCalls as $widgetPath => $widgetInfo) {
            if(isset($widgetInfo['meta']['js_path']) && $jsPath = $widgetInfo['meta']['js_path']) {
                if(!$widget = Registry::getWidgetPathInfo($jsPath))
                    continue;

                $fileContents = @file_get_contents($widget->logic);
                $widgetInterfaceCalls = Config::findAllJavaScriptMessageAndConfigCalls($fileContents, $jsPath);
                $allWidgetInterfaceCalls = array_merge_recursive($allWidgetInterfaceCalls, $widgetInterfaceCalls);
                $widgetHelperCalls = array_merge($widgetHelperCalls, Config::findAllJavaScriptHelperObjectCalls($fileContents));

                // in development, ensure all widgets that _may_ occur in page have their logic.js linked
                if(!IS_OPTIMIZED) {
                    $widgetLogicPath = $this->javaScriptFiles->getWidgetJavaScriptPath($widget->logic);
                    $this->addJavaScriptInclude(
                        $widgetLogicPath,
                        'widget'
                    );
                    // add dependency info if needed
                    if(isset($widgetInfo['meta']['extends'] ) && $widgetInfo['meta']['extends'] && $widgetInfo['meta']['extends_info']['logic']) {
                        $this->javaScriptFiles->addDependentWidgetRelationships($widgetLogicPath, $widgetInfo['meta']['extends']['widget'], false);
                    }
                }

                if ($statics = Widgets::getWidgetStatics($widgetInfo['meta'], $this)) {
                    $this->addWidgetStatics(array(
                        'fullJavaScriptPath' => $widgetInfo['meta']['js_path'],
                        'jsClassName' => $widget->jsClassName,
                        'statics' => $statics,
                    ));
                }
            }
        }

        $widgetHelperCalls = array_unique($widgetHelperCalls);
        if (count($widgetHelperCalls)) {
            $filesystemBase = FileSystem::getCoreAssetFileSystemPath("debug-js/modules/widgetHelpers/");
            $browserBase = Url::getCoreAssetPath("debug-js/modules/widgetHelpers/");
            $this->javaScriptFiles->addFile($browserBase . 'EventProvider.js', 'framework'); // every helper uses EventProvider.js
            foreach($widgetHelperCalls as $helper) {
                if ($helper === 'SearchProducer' || $helper === 'SearchConsumer') {
                    $helper = 'SourceSearchFilter';
                }
                $path = "{$filesystemBase}{$helper}.js";
                // include the file
                $this->javaScriptFiles->addFile("{$browserBase}{$helper}.js", 'framework');
                // parse for config / message bases
                $allWidgetInterfaceCalls = array_merge_recursive($allWidgetInterfaceCalls, Config::findAllJavaScriptMessageAndConfigCalls(@file_get_contents($path), $path));
            }
        }

        $autoloadInterfaceCalls = Config::findAllJavaScriptMessageAndConfigCalls(@file_get_contents(APPPATH . '/javascript/autoload.js'), '/euf/development/javascript/autoload.js');
        $allWidgetInterfaceCalls = array_merge_recursive($allWidgetInterfaceCalls, $autoloadInterfaceCalls);

        list($messageBaseInformation, $messageErrors) = Config::parseJavascriptMessages($allWidgetInterfaceCalls['message']);
        $includeChat = isset($pageMeta['include_chat']) && $pageMeta['include_chat'] ? $pageMeta['include_chat'] : false;
        list($configBaseInformation, $configErrors) = Config::parseJavascriptConfigs($allWidgetInterfaceCalls['config'], false, true, $includeChat);

        $errors = array_merge($messageErrors, $configErrors);
        foreach($errors as $error)
            Framework::addDevelopmentHeaderError($error);

        $this->convertWidgetInterfaceCalls($messageBaseInformation, $configBaseInformation);
    }

    /**
     * Returns session initialization code.
     * @return string JavaScript code
     */
    private function getClientSession() {
        $CI = get_instance();
        $client = 'RightNow.Url.setParameterSegment(' . Url::getJavaScriptParameterIndex() . ");\n";
        if(Url::sessionParameter() !== '')
            $client .= "RightNow.Url.setSession('" . Text::getSubstringAfter(Url::sessionParameter(), 'session/') . "');\n";
        if(!$CI->session->canSetSessionCookies() || !$CI->session->getSessionData('cookiesEnabled'))
            $client .= "RightNow.Event.setNoSessionCookies(true);\n";
        $client .= "RightNow.Interface.Constants = \n" . json_encode($this->getJSConstants()) . ";\n";
        return $client;
    }

    /**
     * Returns JavaScript code that sets config and message base values within RightNow.Interface.
     * @return string Interface initializer or empty string if there are no widget interface calls
     */
    private function getWidgetInterfaceCalls() {
        if ($this->widgetInterfaceCalls) {
            return "RightNow.Interface.setMessagebase(function(){return " . json_encode($this->widgetInterfaceCalls['message']) .
            ";});\nRightNow.Interface.setConfigbase(function(){return " . json_encode($this->widgetInterfaceCalls['config']) . ";});\n";
        }
        return '';
    }

    /**
     * Returns the list of PHP constants to pass into Javascript
     */
    private function getJSConstants()
    {
        $constants = array(
            '_API_VALIDATION_REGEX_EMAIL' => API_VALIDATION_REGEX_EMAIL,
        );

        // Chat needs the constants for custom field types. Adding all enduser field data type constants if include_chat is found on the page.
        $pageMeta = get_instance()->_getMetaInformation();

        if(isset($pageMeta['include_chat']) && $pageMeta['include_chat'])
        {
            $constants = array_merge($constants, array(
                '_EUF_DT_DATE'       => 1,
                '_EUF_DT_DATETIME'   => 2,
                '_EUF_DT_INT'        => 5,
                '_EUF_DT_RADIO'      => 3)
            );
        }

        return Config::convertScriptCompileSafeDefines($this->options->shouldAddM4Ignore(), $constants, false);
    }

    /**
     * Adds new widget static information
     * @param array $info Array with the following keys:
     *  fullJavaScriptPath: string The path to the widget logic file
     *  jsClassName: the name of the widgets JavaScript class
     *  statics: array Key-value properties that are static for the widget;
     *                  any values of complex-type data must be JSON-encoded
     */
    private function addWidgetStatics(array $info) {
        $this->widgetStatics["{$info['fullJavaScriptPath']}_{$info['jsClassName']}"] = array(
            'class' => $info['jsClassName'],
            'static' => $info['statics']
        );
    }

    /**
     * Returns the rules needed for site-wide base css
     * @return string CSS style tag needed for site-wide css rules
     */
    public static function getBaseSiteCss()
    {
        return "<style type='text/css'>\n <!-- \n" .
               ".rn_ScreenReaderOnly{position:absolute; height:1px; left:-10000px; overflow:hidden; top:auto; width:1px;}\n.rn_Hidden{display:none !important;}\n -->" .
               "</style>\n";
    }

    /**
     * Getter for revealing if widget's javascript should show errors
     * @return bool Whether or not to show errors
     */
    public function shouldThrowWidgetJavaScriptErrors() {
        return $this->options->shouldThrowWidgetJavaScriptErrors();
    }
}

final class JavaScriptFileManager
{
    public $moduleType = "";

    /**
     * Types of JS files
     */
    public $additional = array();
    public $async = array();
    public $asyncYUI = array();
    public $chat = array();
    public $custom = array();
    public $inline = array();
    public $widget = array();
    public $widgetHelpers = array();
    private $widgetDependencyRelationships = array();

    /**
     * Tracks the types defined above
     */
    private $jsTypes = array();

    /**
     * Liable to change between module types
     */
    public $yui = array();
    public $framework = array();
    private $yuiModules = array();

    /**
     * Standard defaults
     */
    private $standardYUI = array();
    private $standardFramework = array();
    private $standardYUIModules = array();

    public function __construct()
    {
        $basePath = Url::getCoreAssetPath();
        $yuiBasePath = Url::getYUICodePath('');
        $this->widgetHelpers = array(
            "EventProvider.js",
            "Field.js",
            "Form.js",
            "SearchFilter.js",
            "SourceSearchFilter.js",
            "ProductCategory.js",
            "Avatar.js",
            "RequiredLabel.js"
        );
        $this->chat = array(
            "{$yuiBasePath}get/get-min.js",
            "{$yuiBasePath}cookie/cookie-min.js",
            "{$basePath}debug-js/modules/chat/RightNow.Chat.UI.js",
            "{$basePath}debug-js/modules/chat/RightNow.Chat.Model.js",
            "{$basePath}debug-js/modules/chat/RightNow.Chat.Communicator.js",
            "{$basePath}debug-js/modules/chat/RightNow.Chat.Controller.js",
            "{$basePath}debug-js/modules/chat/RightNow.Chat.LS.js"
        );

        $this->standardYUI = $this->yui = array(
            "{$yuiBasePath}combined-yui.js",
            "{$basePath}ejs/1.0/ejs-min.js",
        );
        $this->standardFramework = $this->framework = array(
            "{$basePath}debug-js/RightNow.js",
            "{$basePath}debug-js/RightNow.UI.js",
            "{$basePath}debug-js/RightNow.Ajax.js",
            "{$basePath}debug-js/RightNow.Url.js",
            "{$basePath}debug-js/RightNow.Text.js",
            "{$basePath}debug-js/RightNow.UI.AbuseDetection.js",
            "{$basePath}debug-js/RightNow.Event.js",
        );
        // Specify the modules included in the YUI rollup so that a module
        // isn't needlessly included if a widget tries to add one.
        $this->standardYUIModules = $this->yuiModules = array(
            "{$yuiBasePath}transition/transition-min.js",
            "{$yuiBasePath}array-extras/array-extras-min.js",
            "{$yuiBasePath}attribute-base/attribute-base-min.js",
            "{$yuiBasePath}base-base/base-base-min.js",
            "{$yuiBasePath}dom-base/dom-base-min.js",
            "{$yuiBasePath}dom-core/dom-core-min.js",
            "{$yuiBasePath}dom-screen/dom-screen-min.js",
            "{$yuiBasePath}dom-style/dom-style-min.js",
            "{$yuiBasePath}escape/escape-min.js",
            "{$yuiBasePath}event-custom-base/event-custom-base-min.js",
            "{$yuiBasePath}event-custom-complex/event-custom-complex-min.js",
            "{$yuiBasePath}event-delegate/event-delegate-min.js",
            "{$yuiBasePath}event-key/event-key-min.js",
            "{$yuiBasePath}event-outside/event-outside-min.js",
            "{$yuiBasePath}event-synthetic/event-synthetic-min.js",
            "{$yuiBasePath}history-base/history-base-min.js",
            "{$yuiBasePath}history-hash/history-hash-min.js",
            "{$yuiBasePath}history-hash-ie/history-hash-ie-min.js",
            "{$yuiBasePath}history-html5/history-html5-min.js",
            "{$yuiBasePath}intl/intl-min.js",
            "{$yuiBasePath}io-base/io-base.js",
            "{$yuiBasePath}io-form/io-form-min.js",
            "{$yuiBasePath}io-queue/io-queue-min.js",
            "{$yuiBasePath}io-upload-iframe/io-upload-iframe-min.js",
            "{$yuiBasePath}json-parse/json-parse-min.js",
            "{$yuiBasePath}json-stringify/json-stringify-min.js",
            "{$yuiBasePath}node-base/node-base-min.js",
            "{$yuiBasePath}node-core/node-core-min.js",
            "{$yuiBasePath}node-event-delegate/node-event-delegate-min.js",
            "{$yuiBasePath}node-screen/node-screen-min.js",
            "{$yuiBasePath}node-style/node-style-min.js",
            "{$yuiBasePath}oop/oop-min.js",
            "{$yuiBasePath}panel/panel-min.js",
            "{$yuiBasePath}querystring-stringify-simple/querystring-stringify-simple-min.js",
            "{$yuiBasePath}queue-promote/queue-promote-min.js",
            "{$yuiBasePath}selector-native/selector-native-min.js",
            "{$yuiBasePath}selector/selector-min.js",
            "{$yuiBasePath}widget-autohide/widget-autohide-min.js",
            "{$yuiBasePath}widget-base/widget-base-min.js",
            "{$yuiBasePath}widget-buttons/widget-buttons-min.js",
            "{$yuiBasePath}widget-htmlparser/widget-htmlparser-min.js",
            "{$yuiBasePath}widget-modality/widget-modality-min.js",
            "{$yuiBasePath}widget-position/widget-position-min.js",
            "{$yuiBasePath}widget-position-align/widget-position-align-min.js",
            "{$yuiBasePath}widget-position-constrain/widget-position-constrain-min.js",
            "{$yuiBasePath}widget-position-constrain/widget-position-constrain-min.js",
            "{$yuiBasePath}widget-stdmod/widget-stdmod-min.js",
            "{$yuiBasePath}widget-stdmod/widget-stdmod-min.js",
            "{$yuiBasePath}widget-uievents/widget-uievents-min.js",
            "{$yuiBasePath}widget-uievents/widget-uievents-min.js",
            "{$yuiBasePath}yui-core/yui-core-min.js",
            "{$yuiBasePath}color-base/color-base-min.js",
        );

        $this->custom = array('/javascript/autoload.js');
    }

    /**
     * Sets up the correct JS files depending on which module is
     * being requested
     * @param string $module The module of JS files to load
     */
    public function setModuleType($module)
    {
        $this->moduleType = strtolower($module);
        switch($this->moduleType)
        {
            //Default cases, leave default files intact
            case '':
            case ClientLoader::MODULE_STANDARD:
                $this->framework = $this->standardFramework;
                $this->yui = $this->standardYUI;
                $this->yuiModules = $this->standardYUIModules;
                break;
            case ClientLoader::MODULE_MOBILE:
                $basePath = Url::getCoreAssetPath();
                $yuiBasePath = Url::getYUICodePath();

                $this->framework = array(
                    "{$basePath}debug-js/RightNow.js",
                    "{$basePath}debug-js/RightNow.Ajax.js",
                    "{$basePath}debug-js/RightNow.Text.js",
                    "{$basePath}debug-js/RightNow.UI.Mobile.js",
                    "{$basePath}debug-js/RightNow.UI.AbuseDetection.js",
                    "{$basePath}debug-js/RightNow.Url.js",
                    "{$basePath}debug-js/RightNow.Event.js",
                );
                $this->yui = array(
                    "{$yuiBasePath}combined-mobile-yui.js",
                    "{$basePath}ejs/1.0/ejs-min.js",
                );
                $this->yuiModules = array(
                    "{$yuiBasePath}transition/transition-min.js",
                    "{$yuiBasePath}array-extras/array-extras-min.js",
                    "{$yuiBasePath}attribute-base/attribute-base-min.js",
                    "{$yuiBasePath}attribute-core/attribute-core-min.js",
                    "{$yuiBasePath}attribute-extras/attribute-extras-min.js",
                    "{$yuiBasePath}attribute-observable/attribute-observable-min.js",
                    "{$yuiBasePath}base-base/base-base-min.js",
                    "{$yuiBasePath}base-core/base-core-min.js",
                    "{$yuiBasePath}base-observable/base-observable-min.js",
                    "{$yuiBasePath}dom-base/dom-base-min.js",
                    "{$yuiBasePath}dom-core/dom-core-min.js",
                    "{$yuiBasePath}dom-screen/dom-screen-min.js",
                    "{$yuiBasePath}dom-style/dom-style-min.js",
                    "{$yuiBasePath}escape-min.js",
                    "{$yuiBasePath}event-base/event-base-min.js",
                    "{$yuiBasePath}event-custom-base/event-custom-base-min.js",
                    "{$yuiBasePath}event-custom-complex/event-custom-complex-min.js",
                    "{$yuiBasePath}event-delegate/event-delegate-min.js",
                    "{$yuiBasePath}event-synthetic/event-synthetic-min.js",
                    "{$yuiBasePath}history-base/history-base-min.js",
                    "{$yuiBasePath}history-html5/history-html5-min.js",
                    "{$yuiBasePath}io-base/io-base-min.js",
                    "{$yuiBasePath}io-form/io-form-min.js",
                    "{$yuiBasePath}io-queue/io-queue-min.js",
                    "{$yuiBasePath}io-upload-iframe/io-upload-iframe-min.js",
                    "{$yuiBasePath}json-parse/json-parse-min.js",
                    "{$yuiBasePath}json-stringify/json-stringify-min.js",
                    "{$yuiBasePath}node-base/node-base-min.js",
                    "{$yuiBasePath}node-core/node-core-min.js",
                    "{$yuiBasePath}node-event-delegate/node-event-delegate-min.js",
                    "{$yuiBasePath}node-screen/node-screen-min.js",
                    "{$yuiBasePath}node-style/node-style-min.js",
                    "{$yuiBasePath}oop/oop-min.js",
                    "{$yuiBasePath}querystring-stringify-simple/querystring-stringify-simple-min.js",
                    "{$yuiBasePath}queue-promote/queue-promote-min.js",
                    "{$yuiBasePath}selector/selector-min.js",
                    "{$yuiBasePath}selector-native/selector-native-min.js",
                    "{$yuiBasePath}yui/yui-min.js",
                    "{$yuiBasePath}color-base/color-base-min.js",
                );
                break;
            case ClientLoader::MODULE_NONE:
                $this->yui = $this->framework = $this->widgetHelpers = $this->chat = $this->custom = $this->yuiModules = array();
                break;
            default:
                Framework::addDevelopmentHeaderError(sprintf(\RightNow\Utils\Config::getMessage(INV_VAL_PCT_S_ATTRIB_VAL_PCT_S_MSG), 'javascript_module', $this->moduleType));
        }
    }

    /**
     * Retrieves the currently set module type
     * @return string The module type specified
     */
    public function getModuleType()
    {
        return $this->moduleType;
    }

    /**
     * Adds a file to one of the categorized JavaScript locations
     *
     * @param string $path The path to the file being included
     * @param string $type The category of where to include the file (affects loading location)
     * @return boolean Denotes if file was added or if it already exists
     */
    public function addFile($path, $type)
    {
        if(!$this->fileAlreadyExists($path))
        {
            $this->jsTypes[] = $type;
            $this->{$type}[] = $path;
            return true;
        }
        return false;
    }

    /**
     * Adds literal JS code or path to JS code to include inline on the
     * rendered page
     *
     * @param string $pathOrCode Path to JS file or literal JS code to include
     * @param boolean $isCode Denotes if content is literal JS code
     */
    public function addCode($pathOrCode, $isCode)
    {
        if($isCode)
            $this->inline[] = array('code' => $pathOrCode);
        else if(!$this->fileAlreadyExists($pathOrCode))
            $this->inline[] = array('path' => $pathOrCode);
    }

    /**
     * Checks if the file path being included already exists in the
     * list of files.
     *
     * @param string $path The path to the file being included
     * @return boolean True if file exists, false otherwise
     */
    private function fileAlreadyExists($path)
    {
        $CI = get_instance();
        $meta = method_exists($CI, '_getMetaInformation') ? $CI->_getMetaInformation() : array();

        if(isset($meta['include_chat']) && $meta['include_chat'] && in_array($path, $this->chat)) {
            return true;
        }

        foreach($this->inline as $inline) {
            if($inline['path'] === $path) {
                return true;
            }
        }

        foreach(array_unique($this->jsTypes) as $type) {
            if (in_array($path, $this->{$type})) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the path for a widget's logic.js file
     *
     * @param string $widget The widget for which to return the logic.js path
     * @return string The path of the widget's logic.js
     */
    public function getWidgetJavaScriptPath($widget) {
            $CI = get_instance();
            return Url::getLongEufBaseUrl('excludeProtocolAndHost', Text::getSubstringAfter($widget, '.cfg/'.$CI->cpwrapper->cpPhpWrapper->getScriptPath().'/cp'));
    }

    /**
     * Adds a widget logic dependency relationship
     *
     * @param string $prerequisite The widget which has a dependency
     * @param string $dependent The widget which is the dependent
     */
    public function addWidgetDependencyRelationship($prerequisite, $dependent) {
        $this->widgetDependencyRelationships[$prerequisite] = $dependent;
    }

    /**
     * Returns the object's JavaScript widget paths ($this->widget) sorted according to
     * dependency. If no dependency relationships are indicated for the various
     * JavaScript widget paths, $this->widget is returned.
     *
     * @return array Returns sorted paths if dependency relationships are indicated;
     *   otherwise, it returns $this->widget
     */
    public function getDependencySortedWidgetJavaScriptFiles() {
        $dependencyRelationships = $this->widgetDependencyRelationships;

        if(count($dependencyRelationships)) {
            // build argument for array_multisort
            $pathDepthMap = array();
            foreach(array_keys($dependencyRelationships) as $pathWithDependencies) {
                $pathDepthMap []= $this->getJavaScriptPathDependencyCount($dependencyRelationships, $pathWithDependencies);
            }

            array_multisort($pathDepthMap, SORT_ASC, $dependencyRelationships);
            $orderedPathsWithDependencyRelationships = array_unique(array_merge(array_values($dependencyRelationships), array_keys($dependencyRelationships)));

            // prepend remaining paths without dependencies
            $additionalPaths = array_diff($this->widget, $orderedPathsWithDependencyRelationships);
            return array_merge($additionalPaths, $orderedPathsWithDependencyRelationships);
        }

        return $this->widget;
    }

    /**
     * Recursively adds depedendent widget relationships depending on widget's meta information.
     *
     * @param string $dependentWidgetPathOrLogicPath The relative widget path or logic path for the
     *   dependent widget
     * @param string $prerequisiteWidgetPath The relative widget path for which $dependentWidgetPath
     *   depends. Example: 'standard/input/TextInput'
     * @param bool $isDeploying When true, method uses relative paths as opposed to complete logic path.
     *   Optional.
     */
    public function addDependentWidgetRelationships($dependentWidgetPathOrLogicPath, $prerequisiteWidgetPath, $isDeploying = false) {
        if(!$prerequisiteWidgetPathInfo = Registry::getWidgetPathInfo($prerequisiteWidgetPath)) {
            return;
        }

        if($isDeploying === true) {
            // Avoid use of Url::getLongEufBaseUrl when deploying
            foreach($this->widget as $widgetLogicPath) {
                if(Text::stringContains($widgetLogicPath, $dependentWidgetPathOrLogicPath)) {
                    $dependentWidgetPathOrLogicPath = $widgetLogicPath;
                    break;
                }
            }
            $this->addWidgetDependencyRelationship($dependentWidgetPathOrLogicPath, $prerequisiteWidgetPathInfo->logic);
        }
        else {
            // Ensure use of complete logic path
            if(Registry::isWidget($dependentWidgetPathOrLogicPath) && ($dependentWidgetPathInfo = Registry::getWidgetPathInfo($dependentWidgetPathOrLogicPath))) {
                $dependentWidgetPathOrLogicPath = $this->getWidgetJavaScriptPath($dependentWidgetPathInfo->logic);
            }
            $prerequisiteWidgetJSPath = $this->getWidgetJavaScriptPath($prerequisiteWidgetPathInfo->logic);
            $this->addWidgetDependencyRelationship($dependentWidgetPathOrLogicPath, $prerequisiteWidgetJSPath);
        }

        if(isset($prerequisiteWidgetPathInfo->meta['extends']) && $prerequisiteWidgetPathInfo->meta['extends'] && $prerequisiteWidgetPathInfo->meta['extends']['widget'] && $prerequisiteWidgetPathInfo->meta['extends']['components']['js']) {
            $this->addDependentWidgetRelationships($prerequisiteWidgetPathInfo->relativePath, $prerequisiteWidgetPathInfo->meta['extends']['widget'], $isDeploying);
        }
    }

    /**
     * Counts dependencies of a path in an array of path dependency relationships
     *
     * @param array $widgetDependencyRelationships An associative array of dependency
     *   relationships, in which the key 'depends on' the value.
     * @param string $path The JavaScript path in which to compute how many other
     *   paths ultimately depend on it in $widgetDependencyRelationships
     * @param array $pathRegistry An internally used "registry" which keeps track of paths.
     *   Not intended to be manually passed to this method.
     * @return integer The dependency count of $path arg
     */
    private function getJavaScriptPathDependencyCount($widgetDependencyRelationships, $path, $pathRegistry = array()) {
        if(!isset($widgetDependencyRelationships[$path]))
            return 0;
        if(in_array($path, $pathRegistry))
            return -1;
        $pathRegistry []= $path;
        $currentCount = $this->getJavaScriptPathDependencyCount($widgetDependencyRelationships, $widgetDependencyRelationships[$path], $pathRegistry);
        return ($currentCount === -1) ? -1 : ++$currentCount;
    }
}

abstract class BaseClientLoaderOptions{
    /**
     * Returns true if defines should be wrapped by &lt;m4-ignore&gt; tags.
     * We need to do that so we can have the define's name in production.
     * @return bool
     */
    abstract function shouldAddM4Ignore();

    /**
     * Returns true if widgets will be created in debug mode.
     * In debug mode, the user is alerted when errors occur.
     * @return bool
     */
    abstract function shouldThrowWidgetJavaScriptErrors();
}

abstract class RuntimeBaseClientLoaderOptions extends BaseClientLoaderOptions {
    public function shouldAddM4Ignore() {
        return false;
    }
}

final class DevelopmentModeClientLoaderOptions extends RuntimeBaseClientLoaderOptions {
    public function shouldThrowWidgetJavaScriptErrors() {
        return true;
    }
}

final class StagingModeClientLoaderOptions extends RuntimeBaseClientLoaderOptions {
    public function shouldThrowWidgetJavaScriptErrors() {
        return false;
    }
}

final class ProductionModeClientLoaderOptions extends RuntimeBaseClientLoaderOptions {
    public function shouldThrowWidgetJavaScriptErrors() {
        return false;
    }
}

final class DeployerClientLoaderOptions extends BaseClientLoaderOptions {
    public function shouldAddM4Ignore() {
        return true;
    }
    public function shouldThrowWidgetJavaScriptErrors() {
        return false;
    }
}
