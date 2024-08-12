<?php

namespace RightNow\Controllers\Admin;

use RightNow\Utils\Text,
    RightNow\Internal\Utils\Framework,
    RightNow\Utils\Config;

class Overview extends Base
{
    function __construct()
    {
        parent::__construct(true, '_verifyLoginWithCPEditPermission', array(
            'set_cookie' => false, // Eventually calls _setModeCookie, which calls _verifyLoginWithCPEditPermission as appropriate
            'developmentRedirect' => false, // Eventually calls _setModeCookie, which calls _verifyLoginWithCPEditPermission as appropriate
            'productionRedirect' => false, // Eventually calls _setModeCookie, which calls _verifyLoginWithCPEditPermission as appropriate
            ));
    }

    function index()
    {
        $data = array('phpVersionList' => Framework::getSupportedPhpVersions());
        return $this->_render('main/index', $data, Config::getMessage(ADMINISTRATION_LBL), array(
            'css' => 'main/index',
            'js'  => 'main/index',
        ));
    }

    /**
     * Renders a 404 page within the admin template.
     * Provides suggestions for old routes that have been changed.
     * Redirects old /ci/admin routes thru `overview`.
     */
    function admin404() {
        $wanted = explode('/', substr($_SERVER['QUERY_STRING'], 1));
        $controller = strtolower(array_shift($wanted));
        $method = current($wanted);
        $wanted = implode('/', $wanted);

        $defaults = array(
            'tags' => array(
                'replacement' => 'docs',
                'methods'     => array(
                    'widgets'     => 'docs/widgets',
                    'page_tags'   => 'docs/framework/pageTags',
                    'page_meta'   => 'docs/framework/pageMeta',
                    'widget_meta' => 'docs/widgets/info',
                ),
            ),
        );
        $autoRedirects = array(
            'set_cookie'          => 'overview/set_cookie',
            'setmode'             => 'overview/setMode',
            'developmentredirect' => 'overview/developmentRedirect',
            'productionredirect'  => 'overview/productionRedirect',
            'stagingredirect'     => 'overview/stagingRedirect',
        );

        if (isset($autoRedirects[strtolower($method)]) && $redirect = $autoRedirects[strtolower($method)]) {
            \RightNow\Environment\permanentRedirect("/ci/admin/{$redirect}" . Text::getSubstringAfter($wanted, $method));
            exit;
        }

        $suggestions = array();
        foreach ($defaults as $oldController => $info) {
            if (Text::beginsWith($wanted, $oldController)) {
                $suggestions []= $info['replacement'];
                foreach ($info['methods'] as $oldName => $newPath) {
                    if (Text::stringContainsCaseInsensitive($wanted, $oldName)) {
                        $suggestions []= $newPath;
                        break;
                    }
                }
                break;
            }
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->_render('main/404', array(
            'page'        => $wanted,
            'suggestions' => $suggestions,
        ), Config::getMessage(PAGE_NOT_FOUND_LBL));
    }

    function setupTestData($environmentPath, $environmentModel, $environmentFunction)
    {
        require_once CPCORE . 'Libraries/TestDevelopmentSetup.php';
        $output = \RightNow\Libraries\TestDevelopmentSetup::loadEnvironment(
            str_replace("_", "/", $environmentPath),
            $environmentModel, $environmentFunction);
        $this->_renderJSON($output ?: array());
    }

    function resetTestDatabase($site, $suffix)
    {
        require_once CPCORE . 'Libraries/TestDevelopmentSetup.php';
        echo \RightNow\Libraries\TestDevelopmentSetup::resetTestDatabase($site, $suffix);
    }

    function runTests()
    {
        require_once CPCORE . 'Libraries/TestFramework/CustomerTestFramework.php';
        $runTestsData = \RightNow\Libraries\TestFramework\CustomerTestFramework::runTests();
        echo var_export($runTestsData, true);
    }

    function runTest()
    {
        $test = json_decode(urldecode($_POST['test']), true);
        $environmentData = json_decode(urldecode($_POST['environmentData']), true);

        if(!$test || !is_array($test) || !$environmentData || !is_array($environmentData))
        {
            echo "test or environment data not posted";
            return;
        }

        require_once CPCORE . 'Libraries/TestFramework/RunnerPhpFunctional.php';

        $this->_renderJSON($test['runner']::runTest($test, $environmentData));
    }

    function setMode()
    {
        return $this->_renderTemplate(Config::getMessage(ADMINISTRATION_LBL), 'main/setMode', true, array(
            'js'  => 'overview/setMode',
            'css' => 'overview/setMode',
        ));
    }

    /**
     * Sets a cookie specifying the interface name and corresponding language and
     * then refreshes the page, loading the appropriate message base.
     * @param string $interface The name of the interface having the desired language.
     * @param string $languageCode The associated language code (e.g. en_US) for $interface.
     * @param string $redirectTo The relative path to redirect to after setting the language cookie.
     */
    function setLanguage($interface, $languageCode, $redirectTo = '/ci/admin')
    {
        $cookieValue = urldecode($interface) . "|$languageCode";
        if ($cookieValue !== trim($_COOKIE['cp_admin_lang_intf'])) {
            \RightNow\ActionCapture::record('language', 'change', $languageCode);
            setcookie('cp_admin_lang_intf', $cookieValue, 0, '/ci/admin', $_SERVER['HTTP_HOST'], Config::getConfig(SEC_ADMIN_HTTPS), true);
        }
        $redirectTo = urldecode($redirectTo);

        if (($hash = Text::getSubStringAfter($redirectTo, '#')) && Text::stringContains($hash, '/')) {
            // FF decodes the forward-slash between widget paths, so re-encode them.
            $redirectTo = Text::getSubstringBefore($redirectTo, '#') . '#' . str_replace('/', '%2F',  $hash);
        }

        $this->_redirectAndExit(\RightNow\Utils\Url::getShortEufBaseUrl(true, $redirectTo), 0);
    }

    function setBasicMode()
    {
        require_once CPCORE . 'Internal/Utils/Admin.php';
        $stagingAreas = \RightNow\Internal\Utils\Admin::getStagingEnvironmentModes();
        $this->_render('main/setBasicMode', array('stagingAreas' => $stagingAreas), Config::getMessage(ADMINISTRATION_LBL), array(), 'basicTemplate');
    }

    // @codingStandardsIgnoreStart
    function set_cookie($mode = '', $agent = '', $abuse = '')
    {
        $this->_setModeCookie($mode, $agent, $abuse, "/app");
    }
    // @codingStandardsIgnoreEnd

    /**
     * Cut Dreamweaver a little slack; redirect its attempts to preview pages to the right place.
     * Set the development cookie first so that they actually hit the development page.
     */
    function developmentRedirect()
    {
        $this->_redirect('development');
    }

    function referenceRedirect()
    {
        $this->_redirect('reference');
    }

    function okcsReferenceRedirect()
    {
        $this->_redirect('okcs_reference');
    }

    function stagingRedirect($stagingDirectory = null)
    {
        $this->_redirect(($stagingDirectory === null) ? STAGING_PREFIX . '01' : $stagingDirectory);
    }

    function productionRedirect()
    {
        $this->_redirect('production');
    }

    /**
     * Will toggle the abuse mode of the site. If enabled, this will disable
     * it and vice versa. Additionally, will put the user in Development mode.
     */
    function toggleAbuseRedirect()
    {
        $abuseMode = \RightNow\Libraries\AbuseDetection::isForceAbuseCookieSet() ? 'false' : 'true';
        $redirectTo = '/app/' . Text::getSubstringAfter($this->uri->uri_string(), "/admin/overview/toggleAbuseRedirect/");
        $this->_setModeCookie('development', '', $abuseMode, $redirectTo);
    }

    function previewErrorPages()
    {
        $baseDirectory = DOCROOT . "/euf/config";
        $eufConfigFiles = \RightNow\Utils\FileSystem::listDirectoryRecursively($baseDirectory);
        for($i = 0; $i < count($eufConfigFiles); $i++) {
            $eufConfigFiles[$i] = Text::getSubstringAfter($eufConfigFiles[$i], $baseDirectory, $eufConfigFiles[$i]);
        }
        sort($eufConfigFiles);
        $this->_render('main/previewErrorPages', array('eufConfigFiles' => $eufConfigFiles), Config::getMessage(PREVIEW_ERROR_PAGES_CMD));
    }

    // Used by previewErrorPages
    function showEufConfigPage($page = 'splash.html')
    {
        $filename = DOCROOT . "/euf/config/$page";
        if(!Text::stringContains($page, "..") && \RightNow\Utils\FileSystem::isReadableFile($filename))
        {
            include $filename;
        }
    }

    private function _redirect($toWhere)
    {
        $redirectTo = '/app/' . Text::getSubstringAfter($this->uri->uri_string(), "/admin/overview/{$toWhere}Redirect/");
        $this->_setModeCookie($toWhere, '', '', $redirectTo);
    }

    /**
    * Passes data to the specified view.
    * @param string $pageTitle Title of the page being rendered
    * @param string $pagePath Filesystem path of view file (from rightnow/views/)
    * @param bool $includePageMapping Whether pageset mapping data should be included in the data sent to the page
    * @param array|null $options Any options to pass onto #_render. See Admin/Base#_render for details
    */
    private function _renderTemplate($pageTitle, $pagePath, $includePageMapping, $options)
    {
        if($includePageMapping) {
            $pageData = array(
                'mappings' => $this->model('Pageset')->getPageSetMappingUniqueValues(),
                'defaultMappings' => $this->model('Pageset')->getPageSetDefaultMappingUniqueValues(),
                'pageSet' => $this->getPageSetPath(),
            );
        }
        return $this->_render($pagePath, $pageData, $pageTitle, $options);
    }

    /**
     * This will set the specified cookie and redirect to the given URL.
     *
     * @param string $mode Is production / development / staging_0X / reference
     * @param string $agent The user agent folder
     * @param string $abuse Indicates whether site should act like it's in an abuse state
     * @param string $redirectTo The path to redirect to
     */
    protected function _setModeCookie($mode = '', $agent = '', $abuse = '', $redirectTo = '/app')
    {
        $redirect = false;
        if ($agent && $agent !== '')
        {
            $agent = urldecode($agent);
            $this->_verifyLoginWithCPEditPermission();
            if ($agent === 'default')
            {
                // Set the time to be negative so the cooke expires immediately.
                $time = -1;
            }
            else
            {
                // Set the cookie expiration time to just under 10 days.
                $time = 860000;
            }
            $this->_setCookie('agent', $agent, $time);
            $redirect = true;
        }
        if ($abuse && $abuse !== '')
        {
            $abuse = urldecode($abuse);
            // only allow abuse mode to be set in development mode
            if ($mode && $mode === 'development' && $abuse === 'true')
            {
                $this->_setCookie('isInAbuse', $abuse, 0); // set session cookie
            }
            else {
                $this->_setCookie('isInAbuse', '', -1); // remove cookie immediately
            }
        }
        // disable abuse detection
        if($abuse === '')
        {
            if ($mode && $mode === 'development')
            {
                $this->_setCookie('isInAbuse', '', -1); // remove cookie immediately
            }
        }
        if($mode !== '')
        {
            $mode = urldecode($mode);
            $frameworkVersions = \RightNow\Internal\Utils\Version::getVersionsInEnvironments('framework');
            $versionToCheck = null;
            if ($mode === 'development' || $mode === 'developmentInspector' || $mode === 'reference' || $mode === 'okcs_reference' || Text::beginsWith($mode, STAGING_PREFIX))
            {
                $this->_verifyLoginWithCPEditPermission();
                // Set the cookie expiration time to just under 10 days.
                $time = 860000;
                $versionToCheck = (Text::beginsWith($mode, STAGING_PREFIX)) ? $frameworkVersions['Staging'] : $frameworkVersions['Development'];
            }
            else
            {
                // Set the time to be negative so the cooke expires immediately.
                $time = -1;
            }

            if($mode === 'development')
            {
                $this->_setCookie('developmentInspector', 'inspection', -1);
            }
            else if($mode === 'developmentInspector')
            {
                $this->_setCookie('developmentInspector', 'inspection', $time);
            }

            // only use the less suitable encryption if we are switching to a mode where the CP framework version is less than 3.4
            $useInsecureEncryption = ($versionToCheck && (\RightNow\Internal\Utils\Version::compareVersionNumbers($versionToCheck, "3.4") === -1));
            $modeToken = \RightNow\Utils\Framework::createLocationToken($mode, $useInsecureEncryption);
            $this->_setCookie('location', $mode . "~" . $modeToken, $time);
            $redirect = true;
        }
        if ($redirect)
        {
            $this->_redirectAndExit(\RightNow\Utils\Url::getShortEufBaseUrl(false, $redirectTo), 0);
        }
    }
}
