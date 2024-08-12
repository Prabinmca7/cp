<?php

namespace RightNow\Controllers;

use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Framework;

/**
 * Endpoint for handling old Classic enduser page requests to their CP equivalent
 */
final class Redirect extends Base
{
    public function __construct()
    {
        parent::__construct();
        require_once CPCORE . 'Internal/Libraries/Mapping.php';
    }

    /**
     * Do nothing.  This needs to take care of unauthenticated users.
     * @internal
     */
    public function _ensureContactIsAllowed() {
    }

    /**
     * Sets the proper page set cookie and redirect user to specified page or CP_HOME_URL. For instance:
     *
     *      /ci/redirect/pageSet/mobile/answers/detail/a_id/12
     *      /ci/redirect/pageSet/default/account/profile
     *      /ci/redirect/pageSet/basic/ask
     *      /ci/redirect/pageSet/mobile/app/ask
     *      /ci/redirect/pageSet/mobile/cc/ajaxCustom/ajaxFunctionHandler
     */
    public function pageSet()
    {
        // remove the segments we don't need
        $segments = array_slice($this->uri->segment_array(), 2);
        if (count($segments) <= 0)
        {
            $redirectTo = Url::getHomePage() . Url::sessionParameter();
            Framework::setLocationHeader(Url::getShortEufBaseUrl(false, $redirectTo));
            exit;
        }

        $cookie = isset($_COOKIE["agent"]) ? $_COOKIE["agent"] : null;
        $redirectTo = Url::deleteParameter("/" . implode("/", array_slice($segments, 1)), 'session') . Url::sessionParameter();

        if (!Text::beginsWith($redirectTo, "/cc")
            && !Text::beginsWith($redirectTo, "/ci")
            && !Text::beginsWith($redirectTo, "/app"))
            $redirectTo = "/app" . $redirectTo;

        // url decode to handle nested folders like: mobile%2Fandroid or mobile%2Fiphone
        $segments[0] = strtolower(urldecode($segments[0]));
        $enabledPageSetMappings = $this->model("Pageset")->getEnabledPageSetMappingArrays();
        $pageSetMapping = null;

        if ($segments[0] === "desktop" || $segments[0] === "default")
        {
            $pageSetMapping = "/";

            // remove the first segment and unset the agent cookie
            unset($_COOKIE["agent"]);
            array_shift($segments);
        }
        else if (isset($enabledPageSetMappings[$segments[0]]))
        {
            $pageSetMapping = $segments[0];
            $_COOKIE["agent"] = $pageSetMapping;
        }

        // does the page we are redirecting to exist on the selected pageset?
        $this->_setPageSet();
        $result = $this->getPageFromSegments($segments, true);
        if ($result['found'] === false)
            $redirectTo = Url::getHomePage(true) . Url::sessionParameter();

        // if we aren't changing the page set mapping (the requested one was disabled or the same)
        // do not set the cookie
        if ($pageSetMapping !== null && $pageSetMapping !== $cookie)
        {
            $expires = time() + (10 * 86400);
            \RightNow\Utils\Framework::setCPCookie("agent", $pageSetMapping, $expires);
        }
        Framework::setLocationHeader(Url::getShortEufBaseUrl(false, $redirectTo));
        exit;
    }

    /**
     * Redirect old cgi-bin/<interface>.cfg/php/enduser/* pages to app/home unless an alternate page is explicitly defined
     * in config/mapping.php. Preserve/map only defined url parameters, otherwise discard.
     */
    public function enduser()
    {
        $pathSegments = array_slice($this->uri->segment_array(), 2);
        //Get querystring params via superglobals rather than anything uri->segment_array() returns,
        //since it can collapse something like http:// to http:/.
        $urlParams = urldecode(http_build_query($_GET + $_POST));
        $pageName = end($pathSegments);
        $mappedUrl = $this->_getMapping($pageName, $urlParams);
        Framework::setLocationHeader(Url::getShortEufBaseUrl(false, $mappedUrl));
        exit;
    }

    /**
     * Redirect old cgi-bin/<interface>.cfg/php/wap/* pages to app/home unless an alternate page is explicitly defined
     * in config/mapping.php. Preserve/map only defined url parameters, otherwise discard.
     */
    public function wap()
    {
        $pathSegments = array_slice($this->uri->segment_array(), 2);
        $path = implode('/', $pathSegments);
        if ($path === 'wap/enduser.php')
            $pageName = '/';
        else
            $pageName = Text::getSubstringAfter($path, 'wap/enduser/', '/');
        //Get querystring params via superglobals rather than anything uri->segment_array() returns,
        //since it can collapse something like http:// to http:/.
        $urlParams = urldecode(http_build_query($_GET + $_POST));
        $mappedUrl = $this->_getMappingWap($pageName, $urlParams);
        Framework::setLocationHeader(Url::getShortEufBaseUrl(false, $mappedUrl), true);
        exit;
    }

    /**
     * Redirect old cgi-bin/<interface>.cfg/php/ma/* to app/home unless another mapping is defined
     */
    public function ma()
    {
        // the segments after ma/ begin after the 2nd element
        $segments = $pathSegments = array_slice($this->uri->segment_array(), 2);
        $path = implode('/', $segments);
        $urlParams = '';
        if (Text::stringContains($path, '?')) {
            $pathSegments = explode('/', Text::getSubstringBefore($path, '?'));
            $urlParams = Text::getSubstringAfter($path, '?');
        }
        $pageName = end($pathSegments);
        $mapping = $this->_getMappingMa($pageName, $urlParams);
        Framework::setLocationHeader(Url::getShortEufBaseUrl(false, $mapping->getPath()));
        exit;
    }

    /**
    * Returns a CP URL for the Classic page name and url parameters
    * @param string $pageName The classic php page
    * @param string $urlParams The classic query string
    * @return string CP URL to take the user to
    */
    private function _getMapping($pageName, $urlParams)
    {
        $globalMapping = array('p_cred' => 'cred', 'session' => 'session', 'p_search_text' => 'kw', 'p_prods' => 'p', 'p_cats' => 'c');

        $pageMapping = array('passwd_reset.php' => array('new_page' => 'account/reset_password'),
                             'passwd_setup.php' => array('new_page' => 'account/setup_password'),
                             'acct_assistance.php' => array('new_page' => 'utils/account_assistance'));

        // set customer modifiable $globalMapping and $pageMapping
        if (is_readable(APPPATH . 'config/mapping.php')) {
            require_once APPPATH . 'config/mapping.php';
        }

        // Setttings that override customer modifiable redirects.
        $pageMapping['doc_serve.php'] = array('new_page' => 'ci/documents/detail');
        $pageMapping['doc_view.php'] = array('new_page' => 'ci/documents/view');
        $pageMapping['sitemap.php'] = array('new_page' => 'ci/sitemap');

        return $this->_route($pageName, $urlParams, $globalMapping, $pageMapping, true);
    }

    /**
    * Returns a CP URL for the Classic page name and url parameters
    * @param string $pageName The classic or WAP php page
    * @param string $urlParams The classic or WAP query string
    * @param array|null $globalMapping Array of global mapping for URL parameters
    * @param array|null $pageMapping Array of mapping between requested page and CP destination
    * @param bool $isEnduser Whether the request is enduser (or wap)
    * @return string CP URL to take the user to
    */
    private function _route($pageName, $urlParams, $globalMapping, $pageMapping, $isEnduser) {
        //Convert the query string parameters into key value pairs
        parse_str($urlParams, $parameterArray);

        //if the Classic URL was going to the login page before going to the incident update page,
        //allow CP to do the equivalent thing by just setting the destination to the incident update page.
        if($isEnduser && $pageName === 'acct_login.php' && $parameterArray['p_next_page'] === 'myq_upd.php')
            $pageName = 'myq_upd.php';

        $mapping = new \RightNow\Internal\Libraries\Mapping();
        $mapping->createPathOut(Config::getConfig(CP_HOME_URL));
        $mappingOverrides = null;
        if (isset($pageMapping[$pageName])) {
            $mapping->createPathOut($pageMapping[$pageName]['new_page']);
            $mappingOverrides = $pageMapping[$pageName]['parms'];
        }
        else if ($isEnduser && $pageName === 'fattach_get.php')
        {
            //Special logic for fattach page, but only if the customer hasn't already created
            //a mapping for it
            $fileID = null;
            if($parameterArray['p_file_id'])
            {
                $fileID = $parameterArray['p_file_id'];
            }
            else if($parameterArray['p_created'] && $parameterArray['p_id'] && $parameterArray['p_tbl'])
            {
                $response = $this->model('FileAttachment')->getIDFromCreatedTime(
                    $parameterArray['p_id'],
                    $parameterArray['p_tbl'],
                    $parameterArray['p_created']
                );
                $fileID = $response->result;
            }
            if($fileID)
            {
                $fattachRedirect = "/ci/fattach/get/$fileID";
                if($parameterArray['p_created'])
                    $fattachRedirect .= '/' . $parameterArray['p_created'];
                $mapping->createPathOut($fattachRedirect);
            }
        }
        $mapping->createParameterMapping($globalMapping, $mappingOverrides);
        $docOverride = null;
        if ($isEnduser)
            $docOverride = $this->_getDocOverrideFunction($pageName);
        $mapping->createParamOut($urlParams, $docOverride);

        if($isEnduser && $parameterArray['p_li']) {
            //send URLs containing PTA string to PTA endpoint
            //and redirect back thru to the intended page
            return Url::addParameter(Url::addParameter('/ci/pta/login', 'redirect', $mapping->getPath()), 'p_li', $parameterArray['p_li']);
        }
        return $mapping->getPath();
    }

    /**
     * Return a function that is sent as an argument to $mapping->createParamOut() that specifies
     * url parameters that should be kept in addition to what is defined in mapping.php
     * @param string $pageName Name of current page
     * @return \Closure Method to execute to get URL parameters
     */
    private function _getDocOverrideFunction($pageName)
    {
        $calculationMethod = null;
        if (in_array($pageName, array('doc_serve.php', 'doc_view.php'))) {
            $calculationMethod = function($key) {
                $params = array('p_node', 'p_mailing_id', 'p_preview', 'p_stats',
                                'p_clear_cookie', 'p_c_id', 'p_i_id', 'p_op_id');
                return (in_array($key, $params) || Text::beginsWith($key, 'wf_') || preg_match('/^[0-9]+$/', $key));
            };
        }
        else {
            $calculationMethod = function() {
                return false;
            };
        }
        return $calculationMethod;
    }

    /**
    * Returns a CP URL for the WAP page name and url parameters
    * @param string $pageName The WAP php page
    * @param string $urlParams The WAP query string
    * @return string CP URL to take the user to
    */
    private function _getMappingWap($pageName, $urlParams)
    {
        $globalMappingWap = array('p_cred' => 'cred');

        $pageMappingWap = array('passwd_reset.php'    => array('new_page' => 'account/reset_password'),
                                'passwd_setup.php'    => array('new_page' => 'account/setup_password'),
                                'acct_assistance.php' => array('new_page' => 'utils/account_assistance'));

        // set customer modifiable $globalMappingWap and $pageMappingWap
        if (is_readable(APPPATH . 'config/mapping.php')) {
            require_once APPPATH . 'config/mapping.php';
        }

        return $this->_route($pageName, $urlParams, $globalMappingWap, $pageMappingWap, false);
    }

    /**
     * Returns mapping for marketing pages
     *
     * @param string $pageName Name of marketing page
     * @param string $urlParams The marketing query string
     *
     * @return object Mapping object instance
     */
    private function _getMappingMa($pageName, $urlParams)
    {
        $pageMapping = array('friend.php' => array('new_page' => 'ci/friend/forward'));
        $mapping = new \RightNow\Internal\Libraries\Mapping();
        $mapping->createPathOut(Config::getConfig(CP_HOME_URL));

        if (isset($pageMapping[$pageName])) {
            $mapping->createPathOut($pageMapping[$pageName]['new_page']);
        }

        $mapping->createParamOut($urlParams, function($key) {
            return preg_match('/^[0-9]+$/', $key);
        });
        return $mapping;
    }
}
