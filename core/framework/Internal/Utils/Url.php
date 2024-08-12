<?php
namespace RightNow\Internal\Utils;

use RightNow\Utils\Text,
    RightNow\Utils\Config;

class Url
{
    /**
     * Functions to convert rn:url_param tags to url parameter strings
     *
     * @param string $matches The buffer to convert on
     * @return string The php function call for a widget
     */
    public static function urlParameterReplacer($matches)
    {
        if($matches[1])
            return '<?=' . self::getParameterFunctionCall($matches[2]) . ';?>';
        return '<?=' . self::getParameterWithKeyFunctionCall($matches[2]) . ';?>';
    }

    /**
     * Functions to convert rn:url_param tags to url parameter strings
     *
     * @param string $matches The buffer to convert
     * @return string The php call
     */
    public static function urlParameterReplacerWithinPhp($matches)
    {
        if($matches[1])
            return "' . " . self::getParameterFunctionCall($matches[2]) . " . '";
        return "' . " . self::getParameterWithKeyFunctionCall($matches[2]) . " . '";
    }

    /**
     * Returns the first index of a parameter without any page set mapping parameter added.
     * JavaScript takes the url straight from the browser.
     *
     * @return int The segment number
     */
    public static function getJavaScriptParameterIndex()
    {
        $CI = get_instance();
        $parmSegment = $CI->config->item('parm_segment');
        $offset = $CI->getPageSetOffset();
        return $parmSegment - $offset;
    }

    /**
     * Function to replace the community_token tag when used within widget attributes
     * @param array|null $matches Matches from the widget attribute
     * @return string Community token function call
     */
    public static function communitySsoTokenWithinPhp($matches)
    {
        return "' . \RightNow\Utils\Url::communitySsoToken('" . $matches[2] . "') . '";
    }

    /**
     * Determines the product or category search filter for the page based on the URL or Report Default
     * @param string $paramName Either 'c' or 'p' denoting category or product respectively
     * @param string|null $runtimeValue URL parameter or fixed filter value; if falsy, the report's default
     *         value (if any) is used
     * @param array &$filters Reference of the collection of report filters for the current page
     * @param int $reportID The report ID for the filters
    */
    public static function getProductOrCategoryFilter($paramName, $runtimeValue, array &$filters, $reportID)
    {
        $longParamName = ($paramName === 'p') ? 'prod' : 'cat';
        $CI = func_num_args() > 4 ? func_get_arg(4) : get_instance(); /* Enable unit tests */
        $filter = $CI->model('Report')->getFilterByName($reportID, $longParamName)->result;
        if(!isset($filters[$paramName]) || !$filters[$paramName]){
            $filters[$paramName] = (object)array('filters' => (object)array(), 'type' => null, 'report_default' => null);
        }
        $filters[$paramName]->filters->fltr_id = isset($filter['fltr_id']) ? $filter['fltr_id'] : null;
        $filters[$paramName]->filters->oper_id = isset($filter['oper_id']) ? $filter['oper_id'] : null;
        $filters[$paramName]->filters->optlist_id = isset($filter['optlist_id']) ? $filter['optlist_id'] : null;
        $filters[$paramName]->filters->report_id = $reportID;
        $filters[$paramName]->type = isset($filter['name']) ? $filter['name'] : null;
        $filters[$paramName]->report_default = isset($filter['default_value']) ? $filter['default_value'] : null;
        $filters[$paramName]->filters->rnSearchType = 'menufilter';
        $filters[$paramName]->filters->data[0] = null;

        //Check the URL first
        if($runtimeValue) {
            $data = explode(';', urldecode($runtimeValue));
            $filterName = ($paramName === 'p') ? 'Product' : 'Category';
            if ((int)$data[0] === -1) {
                // -1 is for 'No Value'
                $filters[$paramName]->filters->data = array('-1');
            }
            else {
                for($i = 0; $i < count($data); $i++) {
                    //If it is only a single ID check if it has a chain. If so, add it.
                    $defaultChain = explode(',', $data[$i]);
                    $defaultChain = (count($defaultChain) === 1)
                                        ? $CI->model('Prodcat')->getFormattedChain($filterName, $defaultChain[0], true)->result
                                        : $CI->model('Prodcat')->getEnduserVisibleHierarchy($defaultChain)->result;
                    $data[$i] = implode(',', $defaultChain);
                }
                $filters[$paramName]->filters->data = $data;
            }
        }
        //Then check the report default
        else if($filters[$paramName]->report_default && $filters[$paramName]->report_default !== ANY_FILTER_VALUE)
        {
            $hierMenuValues = explode(';', $filters[$paramName]->report_default);
            if(is_array($hierMenuValues) && count($hierMenuValues))
            {
                $returnValues = array();
                foreach($hierMenuValues as $value)
                {
                    list($level, $id) = explode('.', $value);
                    if($level !== '1')
                    {
                        if (!$chain = $CI->model('Prodcat')->getChain($id, $level, true)->result) {
                            // the last element is not visible, so just ignore the whole chain
                            continue;
                        }
                        $id = implode(',', $CI->model('Prodcat')->getEnduserVisibleHierarchy($chain)->result);
                    }
                    else if(Text::stringContains($id, 'u0'))
                    {
                        //'no value' is represented as '1.u0' -> skip it
                        continue;
                    }
                    else if (!$CI->model('Prodcat')->isEnduserVisible($id))
                    {
                        //top-level value is not visible
                        continue;
                    }
                    $returnValues[] = $id;
                }
            }
            $filters[$paramName]->filters->data = $filters[$paramName]->report_default = $returnValues ?: '';
        }
    }

    /**
     * Gets a search type filter structure for either the specified default value
     * or (if no default value specified) the report's default value.
     * @param  Number $reportID     Report ID for the filter
     * @param  String|Null $runtimeValue Default value to apply
     * @param  Object $CI           CI instance
     * @return Object               Filter structure
     */
    public static function getSearchTypeFilter($reportID, $runtimeValue, $CI) {
        if ($runtimeValue) {
            $filter = $CI->model('Report')->getFilterById($reportID, $runtimeValue)->result ?: array('fltr_id' => $runtimeValue); // needed for widx
        }
        else {
            $filter = $CI->model('Report')->getSearchFilterTypeDefault($reportID)->result;
        }

        return (object) array(
            'filters' => (object) array(
                'rnSearchType' => 'searchType',
                'fltr_id' => isset($filter['fltr_id']) ? $filter['fltr_id'] : null,
                'data' => isset($filter['fltr_id']) ? $filter['fltr_id'] : null,
                'oper_id' => isset($filter['oper_id']) ? $filter['oper_id'] : null,
                'report_id' => $reportID,
            ),
            'type' => 'searchType',
        );
    }

    /**
     * Gets a keyword filter structure for either the specified default value
     * or (if no default value specified) the report's default value.
     * @param  Number $reportID     Report ID for the filter
     * @param  String|Null $runtimeValue Default keyword to apply
     * @param  Object $CI           CI instance
     * @return Object          Filter structure
     */
    public static function getKeywordFilter($reportID, $runtimeValue, $CI) {
        $keywordParam = ($runtimeValue !== null) ? $runtimeValue : $CI->input->post('kw');

        $keyword = null;
        if ($keywordParam !== null && $keywordParam !== false) {
            $keyword = trim($keywordParam);
        }
        else {
            $word = $CI->model('Report')->getSearchFilterTypeDefault($reportID)->result;
            $keyword = (isset($word['default_value']) && $word['default_value']) ? $word['default_value'] : '';
        }

        return (object) array(
            'filters' => (object) array(
                'rnSearchType' => 'keyword',
                'data' => $keyword,
                'report_id' => $reportID,
            ),
            'type' => 'keyword',
        );
    }

    /**
     * Gets an organization filter structure for either the specified default value
     * or (if no default value specified) the report's default value.
     * @param  Number $reportID     Report ID for the filter
     * @param  String|Null $runtimeValue Default organization id to apply
     * @param  Object $CI           CI instance
     * @return Object               Filter structure
     */
    public static function getOrganizationFilter($reportID, $runtimeValue, $CI) {
        $filterData = array();

        if (($incidentAlias = $CI->model('Report')->getIncidentAlias($reportID)->result) &&
            ($profile = $CI->session->getProfile(true)) && $profile->orgID > 0) {
            $orgAlias = $CI->model('Report')->getOrganizationAlias($reportID)->result;

            if ($runtimeValue === '2' && $orgAlias) {
                $lvl = $profile->orgLevel ?: 1;
                $filterData = array(
                    'fltr_id' => "$orgAlias.lvl{$lvl}_id",
                    'oper_id' => 1,
                    'val'     => $profile->orgID,
                );
            }
            else if ($runtimeValue === '1') {
                $filterData = array(
                    'fltr_id' => "$incidentAlias.org_id",
                    'oper_id' => 1,
                    'val'     => $profile->orgID,
                );
            }
            else {
                $filterData = array(
                    'fltr_id' => "$incidentAlias.c_id",
                    'oper_id' => 1,
                    'val' => $profile->contactID,
                );
            }
        }

        return (object) array(
            'filters' => (object) array_merge(array(
                'rnSearchType' => 'org',
                'report_id' => $reportID,
            ), $filterData),
            'type' => 'org'
        );
    }

    /**
     * Gets the sort filter structure for the specified sort.
     * @param  String $sortValue Comma-separated column and direction (numbers)
     * @return Object                   Sort filter
     */
    public static function getSortFilter($sortValue) {
        list($column, $direction) = explode(',', $sortValue);

        // This filter inexplicably isn't an object... (◔_◔)
        return array('filters' => array(
            'col_id' => intval($column),
            //The sort direction must be either 1 (ascending) or 2 (descending). If we see something higher than 2, change it to a 2. Negative values are handled in cleanse code.
            'sort_direction' => min((intval($direction) ?: 1), 2),
            'sort_order' => 1,
        ));
    }

    /**
     * Gets custom filter structures for the given filter values.
     * @param  Number $reportID     Report ID for the filters
     * @param  Array $filterValues Associative array keyed by filter names
     * @param  Object $CI           CI instance
     * @return Array               Containing custom filters or empty if none found
     */
    public static function getCustomFilters($reportID, $filterValues, $CI) {
        $filters = array();
        $standardFilters = array_flip(array('p', 'c', 'search', 'st', 'kw', 'sort', 'page', 'org'));

        foreach ($filterValues as $key => $value) {
            if (isset($standardFilters[$key]) && !is_null($standardFilters[$key])) continue;

            $key = urldecode($key);
            $value = urldecode($value);

            if ($filter = $CI->model('Report')->getFilterByName($reportID, $key)->result)  {
                $filters[$key] = (object) array('filters' => (object) array(
                        'fltr_id' => $filter['fltr_id'],
                        'oper_id' => $filter['oper_id'],
                        'report_id' => $reportID,
                        'rnSearchType' => 'filter',
                        'data' => explode(';', $value),
                    ),
                    'type' => $key,
                );
            }
        }

        return $filters;
    }

    /**
     * Adds WIDX filters if the report and current set of filters dictate that they should be included.
     * @param  Number $reportID      Report id
     * @param  array &$filters       Reference to current set of filters
     * @param  array $runtimeValues Current set of runtime values for filters
     * @return array|null                Array containing WIDX filter values or null
     */
    public static function applyWebIndexFilters($reportID, array &$filters, array $runtimeValues) {
        if (!in_array($reportID, array(CP_NOV09_WIDX_DEFAULT, CP_WIDX_REPORT_DEFAULT)) || !($searchTypeFilter = $filters['searchType'])) return;

        $filters['webSearchType'] = (object) array('filters' => (object) array(
            'rnSearchType' => 'webSearchType',
            'fltr_id' => $searchTypeFilter->filters->fltr_id,
            'data' => $searchTypeFilter->filters->fltr_id,
            'report_id' => $reportID,
        ));

        if (isset($filters['sort_args']) && $filters['sort_args']) {
            if ($reportID == CP_WIDX_REPORT_DEFAULT && ($runtimeSearchType = $runtimeValues['st'])) {
                $filters['sort_args']['filters']['search_type'] = $runtimeSearchType;
            }

            $filters['webSearchSort'] = (object) array('filters' => (object) array(
                'rnSearchType' => 'webSearchSort',
                'data' => (object) array('col_id' => $filters['sort_args']['filters']['col_id']),
                'report_id' => $reportID,
            ));
        }
    }

    /**
     * Builds an associative array for a given
     * comma-separated list of key=value filter values.
     * e.g.
     *
     *     'p=11,c=,org=1,sort=1:34,c$priority=10' =>
     *     [
     *         'p' => '11',
     *         'c' => '',
     *         'kw' => 'bananas',
     *         'sort' => '1:34',
     *         'c$priority' => '10',
     *     ]
     *
     * @param  String $filters Comma-separated list of filter values
     * @return Array          Associative array
     */
    public static function extractFilters($filters) {
        $filters = explode(',', trim($filters));
        $filters = array_filter($filters);

        $extracted = array();
        foreach ($filters as $pair) {
            list($key, $value) = explode('=', $pair);
            // Wasn't in the form 'x=y'
            if (is_null($value)) continue;

            $extracted[trim($key)] = trim($value);
        }

        return $extracted;
    }

    /**
     * Product Catalog Search filter for the page based on the URL
     * @param bool $addURLFilter True if we should add the URL filter to this report, false if not
     * @param array &$filters Reference of the collection of report filters for the current page
     * @param int $reportID The report ID for the filters
    */
    public static function getProductCatalogFilter($addURLFilter, array &$filters, $reportID)
    {
        $paramName = 'pc';
        $longParamName = "assets.product_id";
        $CI = func_num_args() > 4 ? func_get_arg(4) : get_instance(); /* Enable unit tests */
        $filter = $CI->model('Report')->getFilterByName($reportID, $longParamName)->result;

        if(!isset($filters[$paramName]) || !$filters[$paramName]){
            $filters[$paramName] = (object)array('filters' => (object)array(), 'type' => null, 'report_default' => null);
        }
        $filters[$paramName]->filters->fltr_id = $filter['fltr_id'];
        $filters[$paramName]->filters->oper_id = $filter['oper_id'];
        $filters[$paramName]->filters->optlist_id = $filter['optlist_id'];
        $filters[$paramName]->filters->report_id = $reportID;
        $filters[$paramName]->type = $filter['name'];
        $filters[$paramName]->report_default = $filter['default_value'];
        $filters[$paramName]->filters->rnSearchType = 'menufilter';
        if(!isset($filters[$paramName]->filters->data)) {
            $filters[$paramName]->filters->data = null;
        } else if (isset($filters[$paramName]->filters->data) && is_array($filters[$paramName]->filters->data)) {
            $filters[$paramName]->filters->data[0] = null;
        }

        //Check the URL first
        if(($urlValue = \RightNow\Utils\Url::getParameter($paramName)) && $addURLFilter) {
            $data = urldecode($urlValue);
            $defaultChain = $CI->model('ProductCatalog')->getFormattedChain($data, true)->result;
            $data = implode(',', $defaultChain);
            $filters[$paramName]->filters->data = $data;
        }
    }

    /**
     * Generates the base URL (protocol + domain) for the current interface
     * @param mixed $matchProtocol Indicates if the URL should match the current page, the request, or one of the protocol configs.
     *                             Defaults to the same value as the current page.
     *                             Valid values are true, false, and 'sameAsRequest'.
     *                             'sameAsRequest' should be used if including resources (e.g. CSS, images) onto the current page. This looks at the actual connection.
     *                             'shouldBeSecure' should be used if URL must use https
     *                             true will use the admin protocol config
     *                             false will use the enduser protocol config
     * @return string Base URL
     */
    public static function calculateEufBaseUrl($matchProtocol)
    {
        if ($matchProtocol === 'shouldBeSecure')
        {
            $protocol = 'https';
        }
        else if ($matchProtocol === 'sameAsRequest')
        {
            $protocol = ((\RightNow\Utils\Url::isRequestHttps()) ? 'https' : 'http');
        }
        else
        {
            $useHttps = ($matchProtocol) ? Config::getConfig(SEC_ADMIN_HTTPS) : Config::getConfig(SEC_END_USER_HTTPS);
            $protocol = ($useHttps) ? 'https' : 'http';
        }
        $webServer = Config::getConfig(OE_WEB_SERVER);
        return "$protocol://$webServer";
    }

    /**
     * Returns the base url for the site
     *  e.g. http://server.com/cgi-bin/XXX.cfg/php/euf
     *
     * @param string $isAdmin Indicates if the URL is for an admin page.  Defaults to the same value as the current page.  Valid values are true, false, 'sameAsRequest', and 'sameAsCurrentPage'.  'sameAsRequest' should be used if including resources (e.g. CSS, images) onto the current page.  'sameAsCurrentPage' should be used if creating a link to another page in the same security context.  The former looks at the actual connection; the latter looks at what the current page's configuration.
     * @param string $path Path to append to URL
     * @return string Base URL for site plus any appended paths
     */
    public static function getLongEufBaseUrl($isAdmin='sameAsCurrentPage', $path='')
    {
        if ($isAdmin === 'sameAsCurrentPage')
        {
            $isAdmin = USES_ADMIN_HTTPS_SEC_RULES;
        }
        if (strlen($path) > 0 && !Text::beginsWith($path, '/'))
        {
            $path = '/' . $path;
        }

        $base = \RightNow\Api::cgi_url($isAdmin ? CALLED_BY_ADMIN : CALLED_BY_END_USER);
        $url = "$base/cp$path";
        if ($isAdmin === 'excludeProtocolAndHost')
        {
            return preg_replace('@^[^:/]*:/+[^/]+(/.*$)@', '\\1', $url);
        }
        if ($isAdmin === 'sameAsRequest')
        {
            return ((\RightNow\Utils\Url::isRequestHttps()) ? 'https' : 'http') . Text::getSubstringStartingWith($url, ':');
        }
        return $url;
    }

    /**
     * Checks if the call is from tags gallery.
     *
     * @param string $subPath The subfolder under tags gallery. By default, it checks if the call is coming from syndicated_widgets folder or not.
     * @return bool True, if it was called from tags gallery. Otherwise, false.
     */
    public static function isCallFromTagGallery($subPath='syndicatedWidgets')
    {
        return Text::stringContainsCaseInsensitive((isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''), "/ci/admin/docs/$subPath");
    }

    /**
     * Checks if the incoming call is a PTA logout.
     *
     * @return boolean True, if PTA logout, otherwise false.
     */
    public static function isPtaLogout()
    {
        $CI = func_num_args() > 0 ? func_get_arg(0) : get_instance(); // Allow unit
        $request = $CI->uri->segment(1) . '/' . $CI->uri->segment(2);
        return (strcasecmp($request, 'pta/logout') === 0);
    }

    /**
     * Given a $host string, determine if it corresponds to one of the $validHosts, and is therefore approved.
     *
     * @param string $host A string containing a hostname to be validated (e.g. foo.com, http://blah.foo.com)
     * @param array $validHosts A list of acceptable hosts (e.g *.foo.com)
     * @return bool Returns true if $host is allowed per $validHosts.
     */
    public static function hostIsAllowed($host, array $validHosts = array()) {
        $normalize = function($string) {
            $string = strtolower(trim(urldecode($string)));
            $string = Text::getSubstringAfter($string, '//', $string);
            return Text::getSubstringAfter($string, 'www.', $string);
        };

        $host = $normalize($host);
        foreach($validHosts as $validHost) {
            $validHost = $normalize($validHost);
            if ($host === $validHost || (Text::beginsWith($validHost, '*.') && preg_match('@^.*' . preg_quote(substr($validHost, 2)) . '$@', $host))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return the source path for the old YUI 2.7 source files. This path is still needed
     * in order to grab files for syndicated widgets, as well as resolving CSS image paths
     * @param string $module The YUI Module path to load (optional)
     * @return string The full path to the 2.7 YUI source files
     */
    public static function getOldYuiCodePath($module){
        return "/rnt/rnw/yui_2.7/$module";
    }

    /**
     * Functions to convert rn:url_param tags to url parameter strings
     *
     * @param string $paramName The buffer to convert
     * @return string Adds the php function call
     */
    private static function getParameterWithKeyFunctionCall($paramName)
    {
        return "\RightNow\Utils\Url::getParameterWithKey('$paramName')";
    }

    /**
     * Functions to convert rn:url_param_value tags to url parameter values
     *
     * @param string $paramName The buffer to convert
     * @return string Adds the php function call
     */
    private static function getParameterFunctionCall($paramName)
    {
        return "\RightNow\Utils\Url::getParameter('$paramName')";
    }
}
