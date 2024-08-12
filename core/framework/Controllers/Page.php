<?php

namespace RightNow\Controllers;

use RightNow\Internal\Libraries\HeaderBuilder,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\ClientLoader,
    RightNow\Internal\Libraries\Widget\Locator,
    RightNow\Internal\Libraries\Cache\CacheHeaders,
    RightNow\Libraries\PostRequest,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Framework,
    RightNow\Utils\Widgets,
    RightNow\Utils\Tags,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Libraries,
    RightNow\ActionCapture,
    RightNow\Internal\Libraries as InternalLibraries;

/**
 * Controller responsible for rendering all /app CP requests.
 */
final class Page extends Base
{
    /**
     * Fully qualified path to the currently executing page
     */
    private $pagePath;
    private $systemInitializationErrors = array();
    public $postHandler;

    /**
     * Instance of ClientLoader class
     * @internal
     */
    public $clientLoader;

    /**
     * Meta array for the current page
     * @internal
     */
    public $meta;

    /**
     * Relative name of current page being rendered, such as 'home' or 'answers/list'
     */
    public $page;

    /**
     * List of widget calls on the currently executing page
     * @internal
    */
    public $widgetCallsOnPage;
    protected $shouldAllowPrePageRenderHook = true;

    public function __construct()
    {
        parent::__construct();
        parent::_setClickstreamMapping(array(
            "render" => "page_render",
        ));
        $this->_fixUpUriForHomePage();
        $this->_fixUpUriFor404Page();
        $this->_setUserAgentFolder();
        $this->_setPageVars();

        // if there is at least one mapping that is enabled, add 'User-Agent' to the 'Vary' header
        // to tell search engines (and caching servers) that the user agent can change the page content
        $pageSets = $this->model('Pageset')->getPageSetMappingMergedArray();
        foreach ($pageSets as $id => $mapping)
        {
            if ($mapping->enabled && $mapping->item !== '')
            {
                header('Vary: User-Agent', false);
                break;
            }
        }
    }

    /**
     * Handles the page/template rendering. This method is called automatically using the /app URL
     * scheme. Do not call this method directly.
     * @throws \Exception If we couldn't figure out which mode to render the page in.
     */
    public function render()
    {
        //Attempt to handle any `POST`ed data that may be arriving at the page controller. If _processPostRequest
        //returns true, then simply return because the user will be redirected
        if($_SERVER['REQUEST_METHOD'] === 'POST' && $this->_processPostRequest()) {
            return;
        }

        $this->_recordPageView('/app/');
        if (IS_PRODUCTION)
        {
            $this->_renderProduction();
        }
        else if (IS_STAGING)
        {
            $this->_renderStaging();
        }
        else if (IS_DEVELOPMENT || IS_REFERENCE)
        {
            $this->_renderNonProduction();
        }
        else
        {
            throw new \Exception('Somehow you got into the page controller without setting the appropriate define.');
        }
    }

    /**
     * Do nothing.  We'll handle this in check meta when we know a little more about the page.
     * @internal
     */
    public function _ensureContactIsAllowed() {
    }

    /**
     * Function to retrieve the contents of the meta tag of the current page/template.
     * @return array The meta information
     * @internal
     */
    public function _getMetaInformation()
    {
        return $this->meta;
    }

    /**
     * Since errors can occur prior to the development header being initialized, keep track of any of those
     * errors here and then add them later once it's available.
     * @param string $error The label of the error/warning
     * @param boolean $severe Whether this is an error(true) or warning(false)
     * @internal
     */
    public function _addErrorPriorToHeaderInitialization($error, $severe){
        $this->systemInitializationErrors[] = array('label' => $error, 'severe' => $severe);
    }

    /**
     * Utility function to send user to error page
     * @param int $errorCode The error code to display to the user
     * @param boolean $permanent Denotes if the page move is considered permanent which will use a 301 header code instead of a 302
     * @internal
     */
    protected function _sendUserToErrorPage($errorCode, $permanent = false)
    {
        Url::redirectToErrorPage($errorCode, $permanent);
    }

    /**
     * Renders the page for staging mode
     * @internal
     */
    protected function _renderStaging()
    {
        require_once CPCORE . 'Internal/Libraries/ThemeParser.php';
        Registry::setTargetPages(STAGING_LOCATION);
        $this->clientLoader = new \RightNow\Libraries\ClientLoader(new InternalLibraries\StagingModeClientLoaderOptions());
        if (!FileSystem::isReadableFile($this->pagePath)) {
            exit(Config::getMessage(STAGING_PAGES_SET_CORRECTLY_MSG));
        }
        ob_start();
        // @codingStandardsIgnoreStart
        require $this->pagePath;
        // @codingStandardsIgnoreEnd
        $mainContent = ob_get_clean();
        $meta = $this->_getMetaInformation();
        if ($javaScriptModule = strtolower($meta['javascript_module'])) {
            $this->clientLoader->setJavaScriptModule($javaScriptModule);
        }
        $this->stagingHeader = $this->_createDevelopmentHeaderBuilder(null, null);
        $insertedFrameworkCode = '';
        if ($javaScriptModule === ClientLoader::MODULE_NONE || $javaScriptModule === ClientLoader::MODULE_MOBILE) {
            $mainContent = Tags::insertAfterTag($mainContent, $this->stagingHeader->getDevelopmentHeaderHtml(), Tags::OPEN_BODY_TAG_REGEX);
            $insertedFrameworkCode .= '<script type="text/javascript" src="' . Url::getCoreAssetPath('debug-js/RightNow.UI.SimpleDevelopmentHeader.js') . '"></script>';
        }
        else {
            $insertedFrameworkCode .= $this->stagingHeader->getDevelopmentHeaderHtml();
        }
        $mainContent = Tags::insertBeforeTag($mainContent, $insertedFrameworkCode, Tags::CLOSE_BODY_TAG_REGEX);
        $mainContent = str_replace(ClientLoader::runtimeHeadContentPlaceholder, $this->clientLoader->getHeadContent() . $this->stagingHeader->getDevelopmentHeaderCss(), $mainContent);
        $this->_sendContent($mainContent);
    }


    /**
     * Renders the page for non-production modes
     * Parses the page path and parameters from the uri segment array, meta info from the page
     * content to retrieve the template, and then evaluates the combined
     * content.
     *
     * @internal
     */
    protected function _renderNonProduction()
    {
        require_once CPCORE . 'Internal/Libraries/ThemeParser.php';
        if (IS_REFERENCE) {
            Registry::setTargetPages('reference');
        }

        $this->clientLoader = new \RightNow\Libraries\ClientLoader(new InternalLibraries\DevelopmentModeClientLoaderOptions());
        $pageDetails = array();
        $pageContent = file_get_contents($this->pagePath);
        $pageContent = str_replace("\r", "", $pageContent);
        $pageDetails["pageContent"] = $pageContent;
        $pageDetails["pagePath"] = $this->pagePath;
        $widgetLocator = new Locator($this->pagePath, $pageContent);
        list($meta, $pageContent) = Tags::parseMetaInfo($pageContent);
        $pageThemes = $this->_parseThemesAndReportErrors($pageContent, $this->pagePath);

        $templateContent = $this->_getTemplateContentIfAny($meta);
        $pageDetails["templateContent"] = $templateContent;
        if ($templateContent)
        {
            $widgetLocator->addContentToProcess($meta['template'], $templateContent);
            list($templateMeta, $templateContent) = Tags::parseMetaInfo($templateContent);
            $meta = Tags::mergeMetaArrays($meta, $templateMeta);
            $pageDetails["templatePath"] = $this->_getViewsPath() . 'templates/' . $meta['template'];
            if (IS_OKCS_REFERENCE)
            {
                $mainContentPath = 'views/templates/okcs/' . $meta['template'];
            }
            else
            {
                $mainContentPath = 'views/templates/' . $meta['template'];
            }
            $templateThemes = $this->_parseThemesAndReportErrors($templateContent, $mainContentPath);
            $mainContent = $templateContent;
            $subordinateContent = $pageContent;
        }
        else
        {
            $mainContent = $pageContent;
            $mainContentPath = 'views/pages/' . Text::getSubstringAfter($this->pagePath, 'views/pages/');
            $subordinateContent = false;
            $templateThemes = false;
        }
        $shouldOutputHtmlFiveTags = Tags::containsHtmlFiveDoctype($mainContent);

        $this->widgetCallsOnPage = $widgetLocator->getWidgets();
        $this->widgetCallsOnPage = $widgetLocator::removeNonReferencedParentWidgets($this->widgetCallsOnPage);

        // We must set the runtime theme data before checkMeta because checkMeta calls the pre_page_render hook, which needs to have the themes set.
        $this->themes->setRuntimeThemeData(InternalLibraries\ThemeParser::convertListOfThemesToRuntimeInformation($pageThemes, $templateThemes));
        $this->_checkMeta($meta);

        try
        {
            Tags::ensureContentHasHeadAndBodyTags($mainContent, $mainContentPath);
        }
        catch (\Exception $ex)
        {
            $this->_error($ex->getMessage());
        }
        $javaScriptModule = isset($meta['javascript_module']) ? strtolower($meta['javascript_module']) : '';
        $headerDetails = Widgets::getWidgetDetailsForHeader($this->widgetCallsOnPage);
        $this->config->set_item('w_id', 0);
        $this->developmentHeader = $this->_createDevelopmentHeaderBuilder($headerDetails, $pageDetails);
        $this->_insertSystemInitializationErrors();
        $this->clientLoader->setJavaScriptModule($javaScriptModule);
        $this->clientLoader->addHeadContent(Framework::evalCodeAndCaptureOutput(Tags::getMetaHeaders($shouldOutputHtmlFiveTags)));
        $this->clientLoader->addHeadContent($this->clientLoader->getBaseSiteCss());
        $themeBaseUrl = Url::getShortEufBaseUrl('sameAsRequest', "{$this->themes->getThemePath()}/");
        $closeBaseTag = $shouldOutputHtmlFiveTags ? '' : '</base>';
        $this->clientLoader->addHeadContent("<base href='$themeBaseUrl'>$closeBaseTag\n");

        foreach (InternalLibraries\ThemeParser::getCssPathsForTheme($this->themes->getTheme(), $pageThemes, $templateThemes) as $cssPath) {
            $this->clientLoader->addStylesheet($cssPath);
        }

        $this->clientLoader->parseSupportingWidgetContent($this->widgetCallsOnPage, $this->themes->getThemePath());
        $mergedContent = Tags::mergePageAndTemplate($mainContent, $subordinateContent);
        if(IS_DEVELOPMENT) {
            $parseOptions = array("mergedContent" => $mergedContent, "template" => $pageDetails["templateContent"], "body" => $pageDetails["pageContent"], "noMetaBody" => $subordinateContent);
            $mainContent = Tags::transformTags($mergedContent, $parseOptions);
        }else{
            $mainContent = Tags::transformTags($mergedContent);
        }

        $mainContent = Framework::evalCodeAndCaptureOutputWithScope($mainContent, Text::getSubstringStartingWith($this->pagePath, 'development/views'), get_instance());

        // All of the following comes after evaluating the page so that any runtime javascript or stylesheet additions go in the correct place.
        $headContent = $this->clientLoader->getHeadContent() . $this->developmentHeader->getDevelopmentHeaderCss();
        $mainContent = Tags::insertHeadContent($mainContent, $headContent);
        $insertedFrameworkCode = $this->clientLoader->getJavaScriptContent(isset($meta['include_chat']) && $meta['include_chat'] === 'true') . $this->clientLoader->getAdditionalJavaScriptReferences();

        if($javaScriptModule === ClientLoader::MODULE_NONE || $javaScriptModule === ClientLoader::MODULE_MOBILE)
        {
            $mainContent = Tags::insertAfterTag($mainContent, $this->developmentHeader->getDevelopmentHeaderHtml(), Tags::OPEN_BODY_TAG_REGEX);
            $insertedFrameworkCode .= '<script type="text/javascript" src="' . Url::getCoreAssetPath('debug-js/RightNow.UI.SimpleDevelopmentHeader.js') . '"></script>';
        }
        else
        {
            $insertedFrameworkCode .= $this->developmentHeader->getDevelopmentHeaderHtml();
        }

        if($randomString = Text::getRandomStringOnHttpsLogin()) {
            $insertedFrameworkCode .= $randomString;
        }

        $mainContent = Tags::insertBeforeTag($mainContent, $insertedFrameworkCode, Tags::CLOSE_BODY_TAG_REGEX);
        $this->_sendContent($mainContent);
    }

    /**
     * Render function for Production mode. Loads the header file produced from the compile operation
     * @internal
     */
    protected function _renderProduction()
    {
        $this->clientLoader = new \RightNow\Libraries\ClientLoader(new InternalLibraries\ProductionModeClientLoaderOptions());
        if (!FileSystem::isReadableFile($this->pagePath))
            exit(Config::getMessage(PRODUCTION_SITE_SETUP_CORRECTLY_LBL));
        ob_start();
        try{
            // @codingStandardsIgnoreStart
            require $this->pagePath;
            // @codingStandardsIgnoreEnd
            $content = ob_get_clean();
        }
        catch(\Exception $e){
            $this->_sendContent($e->getMessage());
            exit;
        }
        $javaScriptModule = (isset($meta['javascript_module']) && $meta['javascript_module']) ? strtolower($meta['javascript_module']) : null;
        if($javaScriptModule)
            $this->clientLoader->setJavaScriptModule($javaScriptModule);
        $this->_sendContent(str_replace(ClientLoader::runtimeHeadContentPlaceholder, $this->clientLoader->getHeadContent(), $content));
    }

    /**
     * This override handles the wrinkles that are added by the page's meta tags, specifically
     * login_required and account_session_required
     * @internal
     */
    protected function _isContactAllowed() {
        if (Framework::isLoggedIn()) {
            //If the password is expired, then route to change password page
            if(!Framework::ensurePasswordIsNotExpired()){
                if($this->page != Config::getConfig(CP_CHANGE_PASSWORD_URL)){
                    $changePasswordPage = '/app/' . Config::getConfig(CP_CHANGE_PASSWORD_URL) . "/msg/".Config::getMessage(PASSWORD_EXPIRED_PLEASE_SET_MSG);
                    $changePasswordURL = Url::getShortEufBaseUrl('sameAsRequest', $changePasswordPage);
                    $this->_redirectAndExit($changePasswordURL, true);
                }
            }
            return true;
        }

        if (isset($this->meta['login_required']) && $this->meta['login_required'] === 'true') {
            return false;
        }

        if (!Config::contactLoginRequiredEnabled()) {
            return true;
        }

        return ($this->meta['login_required'] === 'false' || $this->meta['account_session_required'] === 'true' ||
            $this->page === 'agent/polling_preview' ||
            $this->page === Config::getConfig(CP_LOGIN_URL) ||
            $this->page === Config::getConfig(CP_ACCOUNT_ASSIST_URL));
    }

    /**
     * If the home page was requested implicitly, fix up the CI router to act as
     * if the home page was requested explicitly.
     * @internal
     */
    protected function _fixUpUriForHomePage()
    {
        // If the request was for / or /app, then load the default URL
        if (0 === count(array_slice($this->uri->segment_array(), 2)))
        {
            // Reverse engineer the CI router class to put everything back where
            // it belongs for the implicitly requested URI.
            $routerUri = '/page/render/' . Url::getHomePage(false);
            $_SERVER['REQUEST_URI'] = Url::getHomePage(true);
            $router  = $this->router;
            $router->segments = explode('/', $routerUri);
            unset($router->segments[0]);
            $router->rsegments = $router->segments;
            $router->uri_string = substr($routerUri, 1);
        }
    }

    /**
     * Check to replace the 404 page placeholder variable with the value within the config setting
     * @internal
     */
    protected function _fixUpUriFor404Page()
    {
        if(!empty($this->uri->segment(3)) && strtolower($this->uri->segment(3)) === "%error404%")
        {
            $nonExistantErrorPage = Config::getConfig(CP_404_URL);
            if($nonExistantErrorPage)
            {
                $nonExistantErrorPageParts = array_values(array_filter(explode('/', $nonExistantErrorPage)));
                //Splice off the %error404% segment (which is at index 3) and replace it with the segments for the 404 page. This
                //will persist any other segments that might be after the %error404% placeholder, such as the session parameter
                array_splice($this->router->segments, 2, 1, $nonExistantErrorPageParts);
                $this->router->rsegments = $this->router->segments;
                $this->router->uri_string = str_ireplace('%error404%', $nonExistantErrorPage, $this->router->uri_string);
                header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            }
            else
            {
                show_404(Text::getSubstringAfter($this->router->uri_string, '/page/render/'));
            }
        }
    }

    /**
     * When a page redirects to the login page, it needs to do things a little differently than
     * the default because it needs to preserve some URL parameters and it needs to use the
     * page that we resolved to, not necessarily what was originally requested.
     * @internal
     */
    protected function _getLoginReturnUrl() {
        $url = $this->page;
        $preservedParameters = Framework::getPreservedParameters();
        $customParameters = array_values($this->_getCustomLoginParameters());
        foreach (array_merge($preservedParameters, $customParameters) as $param) {
            $value = Url::getParameter($param);
            if ($value) {
                $url .= "/$param/$value";
            }
        }

        return $url;
    }

    /**
     * This function is called to get any custom login mapping that was defined.
     * custom login mapping is defined at customer/development/config/mapping.php
     * as $globalMapping['custom_login_id'] = 'id', or $globalMapping['custom_pta_case'] = 'case'
     * @internal
     */
    private function _getCustomLoginParameters() {
        if (is_readable(APPPATH . 'config/mapping.php')) {
            require_once APPPATH . 'config/mapping.php';
        }
        if($globalMapping) {
            $customParams = array_filter($globalMapping, function ($key) {
                return strpos($key, 'custom_login_') === 0;
            }, ARRAY_FILTER_USE_KEY);
            return $customParams;
        }
        return array();
    }

    /**
     * Records an action for the current page view. Records an error if we're on the error page or the 404 page,
     * otherwise records a successful page view to the parameter-less URL.
     * @param string $urlPrefix Leading path to add to the page being recorded
     * @internal
     */
    protected function _recordPageView($urlPrefix){
        if($this->page === 'error'){
            ActionCapture::record('page', 'error', Url::getParameter('error_id'));
        }
        else if($this->page === Config::getConfig(CP_404_URL)){
            ActionCapture::record('page', 'error', '404');
        }
        else{
            ActionCapture::record('page', 'view', substr($urlPrefix . $this->page, 0, ActionCapture::OBJECT_MAX_LENGTH));
        }
    }

    /**
     * Checks if the page meta tag specifies a template and returns its contents
     *
     * @param array $meta Meta information for the current page
     * @return string|boolean Contents of the current called out template or false if none are specified
     */
    private function _getTemplateContentIfAny(array $meta)
    {
        if (!array_key_exists('template', $meta))
            return false;

        if (IS_OKCS_REFERENCE)
        {
            $templatePath = $this->_getViewsPath() . 'templates/okcs/' . $meta['template'];
        }
        else
        {
            $templatePath = $this->_getViewsPath() . 'templates/' . $meta['template'];
        }
        if(!is_file($templatePath))
            $this->_error(sprintf(Config::getMessage(TEMPLATE_FILE_PCT_S_EXIST_MSG), $meta['template']));
        return file_get_contents($templatePath);
    }

    /**
     * Sends the specified content to the page while adding a number of cache related headers
     *
     * @param string $content The content to send to the browser.
     */
    private function _sendContent($content) {
        // If for whatever goofy reason, content has already been sent, avoid generating errors.  That shouldn't happen.
        if (!headers_sent()) {
            require_once CPCORE . 'Internal/Libraries/Cache/CacheHeaders.php';
            $jsonPath = CUSTOMER_FILES . "cacheControl.json";
            $cacheHeaders = CacheHeaders::sendHeaders(ORIGINAL_REQUEST_URI, $jsonPath);
            foreach ($cacheHeaders as $header) {
                header($header);
            }
            header("Content-Length: " . strlen($content));
        }
        echo $content;
        flush();
    }

    /**
     * Check if there are any errors with the declared themes for the page
     *
     * @param string $content Content of page being rendered
     * @param string $contentPath Path to file being rendered
     *
     * @return object Theme parser instance
     */
    private function _parseThemesAndReportErrors($content, $contentPath)
    {
        if (IS_REFERENCE) {
            // We use the ReferenceModeThemeResolver because it doesn't try to validate that the theme exists.
            $themeResolver = new InternalLibraries\ReferenceModeThemeResolver();
        }
        else if (IS_STAGING) {
            $themeResolver = new InternalLibraries\SpecifiedHtmlRootThemeResolver(sprintf('%s/euf/generated/staging/%s/source/assets', HTMLROOT, STAGING_LOCATION));
        }
        else {
            $themeResolver = new InternalLibraries\NormalThemeResolver();
        }
        $themes = InternalLibraries\ThemeParser::parseAndValidate($content, $contentPath, $themeResolver);
        if (is_string($themes)) {
            $this->_error(htmlspecialchars($themes));
        }
        if (IS_REFERENCE && !IS_OKCS_REFERENCE) {
            $themes = InternalLibraries\ThemeParser::translateThemesToReferenceEquivalent($themes, $contentPath);
        }
        if (IS_OKCS_REFERENCE) {
            $themes = InternalLibraries\ThemeParser::translateThemesToKAReferenceEquivalent($themes, $contentPath);
        }

        return $themes;
    }

    /**
     * Function to validate the attributes within the page meta array
     *  login_required
     *  sla_failed_page
     *  answer_details
     *
     * @param array $meta Page meta array
     */
    private function _checkMeta(array $meta)
    {
        Url::redirectToHttpsIfNecessary();

        $this->meta = $meta;
        //Record current pages clickstream tag. Only record this if we're not handling a basic POST submit action.
        //If CUSTOM_CONTROLLER_REQUEST is set, then we are rendering the 404 page and that clickstream has already been recorded.
        if(!$this->postHandler && !CUSTOM_CONTROLLER_REQUEST){
            $clickstream = new \RightNow\Hooks\Clickstream();
            $clickstream->trackSession('page');
            $this->_recordClickstreamActionToAcs(isset($this->meta['clickstream']) ? $this->meta['clickstream'] : '');
        }

        if (Framework::isLoggedIn() && (isset($meta['redirect_if_logged_in']) && $redirectLocation = $meta['redirect_if_logged_in'])) {
            if($redirectUrlParameter = Url::getParameter('redirect'))
            {
                $redirectLocation = urldecode(urldecode($redirectUrlParameter));
            }

            //Only set if redirect host in CP_REDIRECT_HOSTS config
            $checkRedirect = Url::isRedirectAllowedForHost($redirectLocation);
            if($checkRedirect === true) {
                $parsedUrl = @parse_url($redirectLocation);
            }
            if($parsedUrl === false || ((!isset($parsedUrl['scheme']) || !$parsedUrl['scheme']) && !Text::beginsWith($parsedUrl['path'], '/ci/') && !Text::beginsWith($parsedUrl['path'], '/cc/')))
            {
                $redirectLocation = Text::beginsWith($redirectLocation, '/app/') ? $redirectLocation : "/app/$redirectLocation";
            }
            Framework::setLocationHeader($redirectLocation . Url::sessionParameter());
            exit;
        }

        if (isset($meta['account_session_required']) && $meta['account_session_required'] === 'true')
            Admin\Base::verifyAccountLoginBySessionId();

        parent::_ensureContactIsAllowed();

        $action = Framework::pageAllowed($this->pagePath, $this);
        if (is_array($action))
        {
            if ($action['type'] === 'error')
            {
                if(isset($action['permanentRedirect']) && $action['permanentRedirect'])
                    $this->_sendUserToErrorPage($action['code'], true);
                else
                    $this->_sendUserToErrorPage($action['code']);
            }
            else if ($action['type'] === 'login')
                $this->_loginRedirect();
            else if ($action['type'] === 'location')
                $this->_redirectAndExit($action['url']);
        }

        // redirect current request if the page requires https
        if ((isset($meta['force_https']) && $meta['force_https'] === 'true') || Url::shouldPageForceSsl($_SERVER['REQUEST_URI']))
            Url::redirectIfPageNeedsToBeSecure();

        Framework::installPathRestrictions();
        if($this->shouldAllowPrePageRenderHook)
        {
            $preHookData = array();
            Libraries\Hooks::callHook('pre_page_render', $preHookData);
            $this->themes->disableSettingTheme();
        }

        // in optimized pages, the rn:meta tag is already processed, so this function (_checkMeta) is
        // the best place to check a meta tag in all modes
        if (array_key_exists('noindex', $meta) && $meta['noindex'] === 'true') {
            $this->clientLoader->addHeadContent('<meta name="robots" content="noindex" />');
        }
    }

    /**
     * Displays a page error, with the page name and message supplied
     *
     * @param string $message Error message to display
     */
    private function _error($message)
    {
        $errorLabel = Config::getMessage(PAGE_ERROR_LBL);
        exit("<p><h2><b>$errorLabel</b> - ({$this->page})</h2>$message</p>");
    }

    /**
     * Returns an instance of one of the header classes for the current site mode
     *
     * @param array|null $headerDetails Information about the widgets on the page, URL parameters, etc to display in the header
     * @param array $pageDetails Php sources of page and template
     * @return object Header instance for the mode
     * @throws \Exception If site mode could not be determined
     */
    private function _createDevelopmentHeaderBuilder($headerDetails, $pageDetails)
    {
        $meta = $this->_getMetaInformation();
        $javaScriptModule = isset($meta['javascript_module']) ? strtolower($meta['javascript_module']) : '';
        $simpleMode = ($javaScriptModule === ClientLoader::MODULE_MOBILE || $javaScriptModule === ClientLoader::MODULE_NONE);

        if (IS_DEVELOPMENT)
        {
            if($simpleMode)
            {
                require_once CPCORE . 'Internal/Libraries/HeaderBuilder/SimpleDevelopment.php';
                return new HeaderBuilder\SimpleDevelopment($headerDetails, $this->widgetCallsOnPage, $this->page);
            }
            require_once CPCORE . 'Internal/Libraries/HeaderBuilder/Development.php';

            return new HeaderBuilder\Development($headerDetails, $this->widgetCallsOnPage, $this->page, $pageDetails);
        }
        if (IS_REFERENCE)
        {
            require_once CPCORE . 'Internal/Libraries/HeaderBuilder/Reference.php';
            if($simpleMode)
            {
                return new HeaderBuilder\SimpleReference($headerDetails, $this->widgetCallsOnPage, $this->page);
            }
            return new HeaderBuilder\Reference($headerDetails, $this->widgetCallsOnPage, $this->page);
        }
        if (IS_STAGING)
        {
            require_once CPCORE . 'Internal/Libraries/HeaderBuilder/Staging.php';
            return new HeaderBuilder\Staging($headerDetails, $this->widgetCallsOnPage, $this->page, $simpleMode);
        }
        list($location) = \RightNow\Environment\retrieveModeAndModeTokenFromCookie();
        throw new \Exception("Unrecognized value for the 'location' cookie: $location");
    }

    /**
     * Loop through any potential system initialization errors and add them to the dev header
     */
    private function _insertSystemInitializationErrors(){
        foreach($this->systemInitializationErrors as $problem){
            if($problem['severe']){
                Framework::addDevelopmentHeaderError($problem['label']);
            }
            else{
                Framework::addDevelopmentHeaderWarning($problem['label']);
            }
        }
    }


    /**
     * Parses the uri segments and sets the $page and $pagePath variables
     */
    private function _setPageVars()
    {
        //Strip off first two segments since they will be page/render or facebook/render
        $rsegments = array_slice($this->router->segments, 2);
        $segments = array_slice($this->uri->segment_array(), 2);
        $segments = (empty($segments)) ? $rsegments : $segments;
        $this->pagePath = $this->_getPagesPath();
        $resolved = $this->getPageFromSegments($segments);
        if ($resolved['page'])
            $this->page = $resolved['page'];
        if ($resolved['path'])
            $this->pagePath = $resolved['path'];
        if ($resolved['segmentIndex'])
            $this->config->set_item('parm_segment', $resolved['segmentIndex']);

        if(!$resolved['found'])
        {
            //We didn't find the page we were looking for so attempt to load up the
            //404 page within the config setting in place of this page (thereby keeping the
            //URL entered intact). If the config isn't set, go to the old 404 page.
            if($nonExistantErrorPage = Config::getConfig(CP_404_URL))
            {
                $pageSetPrefix = ($this->getPageSetPath() !== null) ? $this->getPageSetPath() . '/' : '';
                $customErrorPage = "{$this->pagePath}{$pageSetPrefix}{$nonExistantErrorPage}.php";
                if(FileSystem::isReadableFile($customErrorPage))
                {
                    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
                    $this->page = $nonExistantErrorPage;
                    $this->pagePath = $customErrorPage;
                }
                else
                {
                    show_404("{$resolved['currentPath']}.php");
                }
            }
            else
            {
                show_404("{$resolved['currentPath']}.php");
            }
        }
    }

    /**
     * Check the user agent mapping file and append the folder if appropriate
     */
    private function _setUserAgentFolder()
    {
        if ($this->getPageSetPath())
            $this->_changeUriForAgent();
    }

    /**
     * Change the uri to the specified folder
     */
    private function _changeUriForAgent()
    {
        if(Text::beginsWithCaseInsensitive(parse_url($this->getPageSetPath(), PHP_URL_SCHEME), 'http'))
        {
            $url = $this->getPageSetPath();
            Framework::setLocationHeader($url);
            exit;
        }
        $firstPageSegment = 2;  // /page/render are the first two
        $router = $this->router;
        $parameters = array_slice($router->segments, $firstPageSegment);
        $uri = array_slice($router->segments, 0, $firstPageSegment);
        foreach (explode('/', $this->getPageSetPath()) as $segment)
            $uri[] = $segment;

        $finalSegments = array_merge($uri, $parameters);
        // $router->segments is a 1 based array
        for ($i = count($finalSegments); $i >= 0; $i--)
        {
            $finalSegments[$i] = (isset($finalSegments[$i - 1])) ? $finalSegments[$i - 1] : null;
        }
        unset($finalSegments[0]);
        $router->segments = $router->rsegments = $finalSegments;
        $pageUri = '';
        for ($i = $firstPageSegment; $i < count($finalSegments); $i++)
        {
            $pageUri .= '/'. $finalSegments[$i];
        }

        $router->uri_string = "page/render$pageUri";
        $router->setUriData();
    }

    /**
     * Attempt to process any data that was `POST`ed to this controller. If the decrypted data
     * contains a valid handler, run it to validate the POST data. If the handler method has successfully
     * processed the data and the user should be redirected, it should return a valid URL segment. If the
     * handler method successfully processed the data and a redirect should not occur, it should return true. If
     * the handler failed to process the data, it should return a falsey value, signifying that a validation error
     * has occurred and the page should continue rendering.
     * @returns boolean True if the page is redirecting.
     *                  False if the POST data encountered an error that needs to be rendered on the same page, or the
     *                  data was processed and a redirect is unnecessary.
     */
    private function _processPostRequest() {
        if(!($postHandler = $this->input->post('handler'))) {
            return false;
        }

        require_once CPCORE . 'Libraries/PostRequest.php';
        if(!Framework::isValidPostToken($this->input->post('validationToken'), $this->input->post('constraints'), ORIGINAL_REQUEST_URI, $postHandler)) {
            PostRequest::addError(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
            return false;
        }
        if(!$this->postHandler = Framework::validatePostHandler($postHandler, true)) {
            PostRequest::addError(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
            return false;
        }

        //Get clickstream tag for current method if it exists and record the action
        $method = substr($postHandler, strrpos($postHandler, '/') + 1);
        if($clickstreamTag = $this->postHandler->clickstreamActionMapping[$method]) {
            $clickstream = new \RightNow\Hooks\Clickstream();
            $clickstream->trackSession('page', $clickstreamTag);
            $this->_recordClickstreamActionToAcs($clickstreamTag);
        }

        $returnValue = $this->postHandler->{$method}();
        /* Enable unit tests */ if(func_num_args() === 1) return $returnValue;
        if(!$returnValue || !is_string($returnValue) || !($url = parse_url($returnValue))) {
            return false;
        }

        Framework::setLocationHeader($returnValue . (($url['host'] && $url['host'] !== Config::getConfig(OE_WEB_SERVER)) ? '' : Url::sessionParameter()));
        return true;
    }

    /**
     * Takes a clickstream tag and potentially modifies it to conform to ACS requirements before recording it to ACS
     * @param string $action Action to record
     */
    private function _recordClickstreamActionToAcs($action){
        if(!$action){
            return;
        }
        ActionCapture::record('clickstream', 'tag', substr($action, 0, ActionCapture::OBJECT_MAX_LENGTH));
    }
}
