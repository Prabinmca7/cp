<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\FileSystem,
    RightNow\UnitTest\Helper as TestHelper,
    RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

class UrlTest extends CPTestCase {
    public $testingClass = 'RightNow\Utils\Url';

    function __construct() {
        $this->protocol = Url::isRequestHttps() ? 'https' : 'http';
        $this->host = Config::getConfig(OE_WEB_SERVER);
        $this->hostname = "{$this->protocol}://{$this->host}";

        $this->parameterSegment = 0;
        $this->routerSegments = array();
    }

    /**
     * Returns a mock CI object having mock 'config' and 'uri' modules.
     * @param array $returnUriValues An optional array of key/value pairs to send to uri->setReturnValue()
     * @param array $returnInputValues An optional array of key/value pairs to send to input->setReturnValue()
     * @return object
     */
    function getMockCI(array $returnUriValues = array(), array $returnInputValues = array()) {
        if (!class_exists('\RightNow\Controllers\MockBase')) {
            Mock::generate('\RightNow\Controllers\Base', '\RightNow\Controllers\MockBase');
        }
        Mock::generate('CI_Config');
        Mock::generate('CI_Input');
        Mock::generate('CI_URI');

        $CI = new \RightNow\Controllers\MockBase();
        $CI->config = new MockCI_Config();
        $CI->input = new MockCI_Input();
        $CI->uri = new MockCI_URI();

        foreach($returnUriValues as $key => $value) {
            $CI->uri->setReturnValue($key, $value);
        }
        foreach($returnInputValues as $key => $scenarios) {
            foreach($scenarios as $scenario) {
                list($value, $args) = $scenario;
                $CI->input->setReturnValue($key, $value, $args);
            }
        }
        return $CI;
    }

    static function useShouldPageForceSsl() {
        $url = explode('/', $_SERVER['REQUEST_URI']);
        $url = urldecode(end($url));
        var_export(Url::shouldPageForceSsl($url));
    }

    // Inject url parameters reflected in getParameter()
    function addUrlParameters(array $parameters) {
        $this->CI = $this->CI ?: get_instance();
        $this->parameterSegment = $this->CI->config->item('parm_segment');
        $this->routerSegments = $segments = $this->CI->router->segments;
        $firstKey = null;
        foreach($parameters as $key => $value) {
            $firstKey = $firstKey ?: $key;
            $segments[] = $key;
            $segments[] = $value;
        }
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        if (!Url::getParameter($firstKey)) {
            $this->CI->config->set_item('parm_segment', $this->parameterSegment - 1);
        }
    }

    // Used to reset original url parameters following a call to addUrlParameters above
    function restoreUrlParameters() {
        $this->CI = $this->CI ?: get_instance();
        if ($this->CI->config && isset($this->parameterSegment)) {
            $this->CI->config->set_item('parm_segment', $this->parameterSegment);
        }
        $this->CI->uri  = $this->CI->uri ?: new stdClass();
        $this->CI->router  = $this->CI->router ?: new stdClass();
        $this->CI->router->segments = $this->routerSegments;
        $this->CI->router->setUriData();
    }

    function testAddParameter() {
        $this->assertEqual('a/asdf/qwer', Url::addParameter('a', 'asdf', 'qwer'));
        $this->assertEqual('a/asdf/qwer', Url::addParameter('a/asdf/qwer', 'asdf', 'qwer'));
        $this->assertEqual('a/asdf/qwer', Url::addParameter('a/asdf/a', 'asdf', 'qwer'));
        $this->assertEqual('a/asdf/qwer', Url::addParameter('a/asdf/asdf', 'asdf', 'qwer'));
        $this->assertEqual('a/asdf/asdf/qwer', Url::addParameter('a/asdf', 'asdf', 'qwer'));
        $this->assertEqual('/asdf/qwer/f/zxcv', Url::addParameter('/asdf/qwer', 'f', 'zxcv'));
        $this->assertEqual('/asdf/qwer/f/zxcv', Url::addParameter('/asdf/qwer/f/yuio', 'f', 'zxcv'));
        $this->assertEqual('/asdf/qwer/f/zxcv', Url::addParameter('/asdf/qwer/f/', 'f', 'zxcv'));
        $this->assertEqual('/asdf/qwer/f/zxcv/b/c', Url::addParameter('/asdf/qwer/f//b/c', 'f', 'zxcv'));
    }

    function testDeleteParameter() {
        $this->assertEqual('a', Url::deleteParameter('a', 'asdf', 'qwer'));
        $this->assertEqual('a/asdf/qwer', Url::deleteParameter('a/asdf/qwer', 'sdf'));
        $this->assertEqual('a', Url::deleteParameter('a/asdf/qwer', 'asdf'));
        $this->assertEqual('/', Url::deleteParameter('/session/1234/', 'session'));
    }

    function testGetOriginalUrl() {
        $expectedUrl = 'http://' . $_SERVER['SERVER_NAME'];
        $url = Url::getOriginalUrl(false);
        $this->assertEqual($expectedUrl, $url);

        $urlWithUri = Url::getOriginalUrl();
        $this->assertTrue(strlen($urlWithUri) > strlen($expectedUrl));
        $this->assertTrue(Text::stringContains($urlWithUri, $expectedUrl));
    }

    function testGetParameterIndex() {
        get_instance()->router->setUriData();
        $this->assertEqual(Url::getParameterIndex(), array_search('test', get_instance()->uri->segment_array()) + 1);
    }

    function testGetParameter() {
        $CI = $this->getMockCI(array('uri_to_assoc' => array('foo' => 'bar', 'baz' => 'bam', 'slash' => 'a%2fb')));
        $this->assertEqual('bar', Url::getParameter('foo', $CI));
        $this->assertEqual('bam', Url::getParameter('baz', $CI));
        $this->assertEqual('a/b', Url::getParameter('slash', $CI));
        $this->assertNull(Url::getParameter('notThere', $CI));
    }

    // It would be nice to add tests for getParameterString, getParameter, etc.
    // Those method rely on $CI from get_instance.  I could make them have an
    // optional parameter to specify a $CI which would allow me to specify a mock CI.  That would
    // give me a chance to exercise simpletest's mock framework.

    function testGetParameterString() {
        $CI = $this->getMockCI(array('uri_to_assoc' => array('foo' => 'bar', 'baz' => 'bam')));
        $this->assertEqual('/foo/bar/baz/bam', Url::getParameterString($CI));

        $CI->uri = new MockCI_URI();
        $CI->uri->setReturnValue('uri_to_assoc', array());
        $this->assertEqual('', Url::getParameterString($CI));
    }

    function testGetParameterWithKey() {
        $CI = $this->getMockCI(array('uri_to_assoc' => array('foo' => 'bar', 'baz' => 'bam')));
        $this->assertEqual('foo/bar', Url::getParameterWithKey('foo', false, $CI));
        $this->assertEqual('baz/bam', Url::getParameterWithKey('baz', false, $CI));
        $this->assertNull(Url::getParameterWithKey('asdf', false, $CI));

        $CI->uri = new MockCI_URI();
        $CI->uri->setReturnValue('uri_to_assoc', array());
        $this->assertNull(Url::getParameterWithKey('asdf', false, $CI));

        $CI = $this->getMockCI(array('uri_to_assoc' => array()), array('post' => array(array('bar', array('foo')), array('bam', array('baz')), array(false, array('*')))));
        $this->assertEqual('foo/bar', Url::getParameterWithKey('foo', true, $CI));
        $this->assertEqual('baz/bam', Url::getParameterWithKey('baz', true, $CI));
        $this->assertNull(Url::getParameterWithKey('asdf', true, $CI));
        $this->assertNull(Url::getParameterWithKey('foo', false, $CI));
        $this->assertNull(Url::getParameterWithKey('baz', false, $CI));

        $CI->input = new MockCI_Input();
        $CI->input->setReturnValue('post', false, array('*'));
        $this->assertNull(Url::getParameterWithKey('asdf', true, $CI));

        $CI = $this->getMockCI(array('uri_to_assoc' => array('foo' => 'bar1', 'baz' => 'bam1')), array('post' => array(array('bar2', array('foo')), array('bam2', array('baz')), array(false, array('*')))));
        $this->assertEqual('foo/bar1', Url::getParameterWithKey('foo', true, $CI));
        $this->assertEqual('baz/bam1', Url::getParameterWithKey('baz', true, $CI));
        $this->assertNull(Url::getParameterWithKey('asdf', true, $CI));


        //@@@ QA 130312-000001 Ensure POST parameters are encoded
        $CI = $this->getMockCI(array('uri_to_assoc' => array('foo' => '!@$%^&*()_-+={}[]],<.>/?',)), array('post' => array(array('!@$%^&*()_-+={}[]],<.>/?', array('bar')), array(false, array('*')))));
        $this->assertEqual('foo/!@$%^&*()_-+={}[]],<.>/?', Url::getParameterWithKey('foo', true, $CI));
        $this->assertEqual('bar/%21%40%24%25%5E%26%2A%28%29_-%2B%3D%7B%7D%5B%5D%5D%2C%3C.%3E%2F%3F', Url::getParameterWithKey('bar', true, $CI));
        $this->assertNull(Url::getParameterWithKey('asdf', true, $CI));
    }

    function testGetParametersFromList() {
        $CI = $this->getMockCI(array('uri_to_assoc' => array('one' => '1', 'two' => '2')));
        $exclude = array();
        $this->assertEqual('', Url::getParametersFromList(array(), $exclude, false, $CI));
        $this->assertEqual('', Url::getParametersFromList(null, $exclude, false, $CI));
        $this->assertEqual('', Url::getParametersFromList('', $exclude, false, $CI));
        $this->assertEqual('', Url::getParametersFromList('three', $exclude, false, $CI));
        $this->assertEqual('/one/1', Url::getParametersFromList('one', $exclude, false, $CI));
        $this->assertEqual('/one/1/two/2', Url::getParametersFromList('one,two', $exclude, false, $CI));
        $this->assertEqual('/one/1/two/2', Url::getParametersFromList('one,two,three', $exclude, false, $CI));
        $this->assertEqual('/two/2', Url::getParametersFromList('one,two', array('one'), false, $CI));

        $CI = $this->getMockCI(array('uri_to_assoc' => array()), array('post' => array(array('1', array('one')), array('2', array('two')), array(false, array('*')))));
        $exclude = array();
        $this->assertEqual('', Url::getParametersFromList(array(), $exclude, true, $CI));
        $this->assertEqual('', Url::getParametersFromList(null, $exclude, true, $CI));
        $this->assertEqual('', Url::getParametersFromList('', $exclude, true, $CI));
        $this->assertEqual('', Url::getParametersFromList('three', $exclude, true, $CI));
        $this->assertEqual('/one/1', Url::getParametersFromList('one', $exclude, true, $CI));
        $this->assertEqual('/one/1/two/2', Url::getParametersFromList('one,two', $exclude, true, $CI));
        $this->assertEqual('/one/1/two/2', Url::getParametersFromList('one,two,three', $exclude, true, $CI));
        $this->assertEqual('/two/2', Url::getParametersFromList('one,two', array('one'), true, $CI));

        $CI = $this->getMockCI(array('uri_to_assoc' => array('one' => '11', 'two' => '21')), array('post' => array(array('12', array('one')), array('22', array('two')), array(false, array('*')), array('blank', array('')))));
        $exclude = array();
        $this->assertEqual('', Url::getParametersFromList(array(), $exclude, true, $CI));
        $this->assertEqual('', Url::getParametersFromList(null, $exclude, true, $CI));
        $this->assertEqual('', Url::getParametersFromList('', $exclude, true, $CI));
        $this->assertEqual('', Url::getParametersFromList('three', $exclude, true, $CI));
        $this->assertEqual('', Url::getParametersFromList('blank', $exclude, true, $CI));
        $this->assertEqual('/one/11', Url::getParametersFromList('one', $exclude, true, $CI));
        $this->assertEqual('/one/11/two/21', Url::getParametersFromList('one,two', $exclude, true, $CI));
        $this->assertEqual('/one/11/two/21', Url::getParametersFromList('one,two,three', $exclude, true, $CI));
        $this->assertEqual('/two/21', Url::getParametersFromList('one,two', array('one'), true, $CI));
    }

    function testSessionParameter() {
        // Returns an empty string since we have a session and cookies are enabled.
        $this->assertEqual('', Url::sessionParameter());
    }

    function testCommunitySsoToken() {
        // Returns an empty string since COMMUNITY configs are not configured and user is not logged in.
        $this->assertEqual('', Url::communitySsoToken());
    }

    function testGetYUICodePath() {
        $path = Url::getYUICodePath();
        $this->assertIsA($path, 'string');
        $this->assertTrue(strlen($path) > 0);
        $this->assertTrue(FileSystem::isReadableDirectory(HTMLROOT . "/$path"));
        $this->assertEqual("{$path}yui_base", Url::getYUICodePath('yui_base'));
    }

    function testGetCoreAssetPath() {
        $path = Url::getCoreAssetPath();
        $this->assertTrue(FileSystem::isReadableDirectory(HTMLROOT . $path));
        $this->assertEqual($path, Url::getCoreAssetPath(null));
        $this->assertEqual($path, Url::getCoreAssetPath(''));
        $this->assertEqual($path, Url::getCoreAssetPath(0));
        $this->assertEqual($path, Url::getCoreAssetPath(array()));
        $this->assertEqual("{$path}images", Url::getCoreAssetPath('images'));
    }

    function testSetFiltersFromAttributesAndUrl() {
        $filters = $attributes = array();
        // Base case: no url params, no attributes
        $expected = array(
            'searchType' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'searchType',
                    'fltr_id' => NULL,
                    'data' => NULL,
                    'oper_id' => NULL,
                    'report_id' => NULL,
                ),
                'type' => 'searchType',
            ),
           'keyword' => (object) array(
               'filters' => (object) array(
                   'rnSearchType' => 'keyword',
                   'data' => '',
                   'report_id' => NULL,
                ),
                'type' => 'keyword',
            ),
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => NULL,
                    'oper_id' => NULL,
                    'optlist_id' => NULL,
                    'report_id' => NULL,
                    'rnSearchType' => 'menufilter',
                    'data' => array(NULL),
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
            'c' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => NULL,
                    'oper_id' => NULL,
                    'optlist_id' => NULL,
                    'report_id' => NULL,
                    'rnSearchType' => 'menufilter',
                    'data' => array(NULL),
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
        );
        Url::setFiltersFromAttributesAndUrl($attributes, $filters);
        $this->assertIdentical($expected, $filters);

        // Attributes
        $filters = array();
        $attributes = array(
            'report_id' => 176,
            'per_page' => 2,
            'arbitrary_attribute' => 'ignored',
        );
        Url::setFiltersFromAttributesAndUrl($attributes, $filters);
        $this->assertEqual($attributes['report_id'], $filters['searchType']->filters->report_id);
        $this->assertEqual($attributes['report_id'], $filters['keyword']->filters->report_id);
        $this->assertEqual($attributes['report_id'], $filters['p']->filters->report_id);
        $this->assertEqual($attributes['report_id'], $filters['c']->filters->report_id);
        $this->assertEqual($attributes['per_page'], $filters['per_page']);
        $this->assertFalse(array_key_exists('arbitrary_attribute', $filters));

        // Sending in original filters results in no change
        $attributes = array();
        $filters = $expected;
        Url::setFiltersFromAttributesAndUrl($attributes, $filters);
        $this->assertIdentical($expected, $filters);

        // Extra filter parameters persist
        $filters = $expected;
        $filters['foo'] = array('one' => 1, 'two' => 2);
        $this->assertNotIdentical($filters, $expected);
        Url::setFiltersFromAttributesAndUrl($attributes, $filters);
        $this->assertEqual(1, $filters['foo']['one']);
        $this->assertEqual(2, $filters['foo']['two']);

        // URL parameters
        $parameters = array(
            'st' => '5',
            'kw' => 'phone',
            'p' => '1',
            'c' => '2',
            'org' => '1',
            'sort' => '2,3',
            'page' => '2',
            'search' => '1',
        );
        $this->addUrlParameters($parameters);

        $filters = array();
        Url::setFiltersFromAttributesAndUrl(array('report_id' => 176), $filters);
        $this->assertEqual($parameters['st'], $filters['searchType']->filters->fltr_id);
        $this->assertEqual($parameters['st'], $filters['searchType']->filters->data);
        $this->assertEqual($parameters['kw'], $filters['keyword']->filters->data);
        // Note: Prod/Cat further tested in testGetProductOrCategoryFilter()
        $this->assertEqual($parameters['p'], $filters['p']->filters->data[0]);
        $this->assertEqual('', $filters['c']->filters->data[0]);
        $this->assertEqual('org', $filters['org']->filters->rnSearchType);
        $this->assertEqual(1, $filters['sort_args']['filters']['sort_order']);
        $this->assertEqual(2, $filters['sort_args']['filters']['col_id']);
        $this->assertEqual(2, $filters['sort_args']['filters']['sort_direction']);
        $this->assertEqual($parameters['page'], $filters['page']);
        $this->assertEqual($parameters['search'], $filters['search']);

        $this->restoreUrlParameters();

        $this->addUrlParameters(array('sort' => '5,235'));
        $filters = array();
        Url::setFiltersFromAttributesAndUrl(array('report_id' => 176), $filters);
        $this->assertEqual(5, $filters['sort_args']['filters']['col_id']);
        $this->assertEqual(2, $filters['sort_args']['filters']['sort_direction']);

        $this->restoreUrlParameters();
    }

    function testSetFiltersFromAttributesAndUrlWithKeywordPost() {
        if (!class_exists('\RightNow\Models\MockReport')) {
            // make sure Report model is loaded
            get_instance()->model('Report');
            Mock::generate('\RightNow\Models\Report', '\RightNow\Models\MockReport');
        }
        if (!class_exists('\RightNow\Controllers\MockBase')) {
            Mock::generate('\RightNow\Controllers\Base', '\RightNow\Controllers\MockBase');
        }
        $mockReport = new \RightNow\Models\MockReport();
        $CI = $this->getMockCI(array('uri_to_assoc' => array()));
        $CI->returnsByValue('model', $mockReport, array('Report'));
        $CI->returnsByValue('model', get_instance()->model('Prodcat'), array('Prodcat'));

        $CI->input->returnsByValue('post', false, array('kw'));
        $reportID = '176' . time() . rand();
        $responseObject = new \RightNow\Libraries\ResponseObject('is_array');
        $responseObject->result = array('default_value' => 'iphone');
        $filters = array();
        $expected = array(
            'searchType' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'searchType',
                    'fltr_id' => null,
                    'data' => null,
                    'oper_id' => null,
                    'report_id' => $reportID,
                ),
                'type' => 'searchType',
            ),
            'keyword' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'keyword',
                    'data' => 'iphone',
                    'report_id' => $reportID,
                ),
                'type' => 'keyword',
            ),
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => $reportID,
                    'rnSearchType' => 'menufilter',
                    'data' => array(null),
                ),
                'type' => null,
                'report_default' => null,
            ),
            'c' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => $reportID,
                    'rnSearchType' => 'menufilter',
                    'data' => array(null),
                ),
                'type' => null,
                'report_default' => null,
            ),
        );
        $mockReport->returnsByValue('getSearchFilterTypeDefault', $responseObject, array($reportID));
        Url::setFiltersFromAttributesAndUrl(array('report_id' => $reportID), $filters, $CI);
        $this->assertIdentical($expected, $filters, 'Failed with test ' . var_export($test, true) . '.  Expected: ' . var_export($expected, true) . ' and got: ' . var_export($filters, true) . '. %s');

        $CI->input = new MockCI_Input();
        $CI->input->returnsByValue('post', 'android', array('kw'));
        $reportID = '176' . time() . rand();
        $responseObject->result = array('default_value' => 'iphone');
        $filters = array();
        $expected = array(
            'searchType' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'searchType',
                    'fltr_id' => null,
                    'data' => null,
                    'oper_id' => null,
                    'report_id' => $reportID,
                ),
                'type' => 'searchType',
            ),
            'keyword' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'keyword',
                    'data' => 'android',
                    'report_id' => $reportID,
                ),
                'type' => 'keyword',
            ),
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => $reportID,
                    'rnSearchType' => 'menufilter',
                    'data' => array(null),
                ),
                'type' => null,
                'report_default' => null,
            ),
            'c' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => $reportID,
                    'rnSearchType' => 'menufilter',
                    'data' => array(null),
                ),
                'type' => null,
                'report_default' => null,
            ),
            'search' => '1',
        );
        $mockReport->returnsByValue('getSearchFilterTypeDefault', $responseObject, array($reportID));
        Url::setFiltersFromAttributesAndUrl(array('report_id' => $reportID), $filters, $CI);
        $this->assertIdentical($expected, $filters, 'Failed with expected: ' . var_export($expected, true) . ' and got: ' . var_export($filters, true) . '. %s');
    }

    function testSetFiltersFromAttributesAndUrlWithReportDefaults() {
        if (!class_exists('\RightNow\Models\MockReport')) {
            // make sure Report model is loaded
            get_instance()->model('Report');
            Mock::generate('\RightNow\Models\Report', '\RightNow\Models\MockReport');
        }
        if (!class_exists('\RightNow\Controllers\MockBase')) {
            Mock::generate('\RightNow\Controllers\Base', '\RightNow\Controllers\MockBase');
        }
        $mockReport = new \RightNow\Models\MockReport();
        $CI = $this->getMockCI(array('uri_to_assoc' => array()));
        $CI->returnsByValue('model', $mockReport, array('Report'));
        $CI->returnsByValue('model', get_instance()->model('Prodcat'), array('Prodcat'));
        $CI->input->returnsByValue('post', false);

        $responseObjectGetFilterByNameProduct = new \RightNow\Libraries\ResponseObject('is_array');
        $responseObjectGetFilterByNameCategory = new \RightNow\Libraries\ResponseObject('is_array');
        $responseObjectGetSearchFilterTypeDefault = new \RightNow\Libraries\ResponseObject('is_array');

        $tests = array(
            // visible product at root
            array('kw' => 'iphone', 'p' => array('default_value' => '1.6', 'data' => array('6'), 'report_default' => array('6'))),
            // visible child product
            array('kw' => 'iphone', 'p' => array('default_value' => '3.160', 'data' => array('1,4,160'), 'report_default' => array('1,4,160'))),
            // product none selected
            array('kw' => 'iphone', 'p' => array('default_value' => '1.u0', 'data' => '', 'report_default' => '')),
            // product any selected
            array('kw' => 'iphone', 'p' => array('default_value' => '~any~', 'data' => array(null), 'report_default' => '~any~')),

            // visible category at root
            array('kw' => 'phone', 'c' => array('default_value' => '1.161', 'data' => array('161'), 'report_default' => array('161'))),
            // non-visible category at root
            array('kw' => 'phone', 'c' => array('default_value' => '1.122', 'data' => '', 'report_default' => '')),
            // visible child category
            array('kw' => 'phone', 'c' => array('default_value' => '2.77', 'data' => array('71,77'), 'report_default' => array('71,77'))),
            // category none selected
            array('kw' => 'phone', 'c' => array('default_value' => '1.u0', 'data' => '', 'report_default' => '')),
            // category any selected
            array('kw' => 'phone', 'c' => array('default_value' => '~any~', 'data' => array(null), 'report_default' => '~any~')),

            // visible product at root
            array('p' => array('default_value' => '1.6', 'data' => array('6'), 'report_default' => array('6'))),
            // visible child product
            array('p' => array('default_value' => '3.160', 'data' => array('1,4,160'), 'report_default' => array('1,4,160'))),
            // product none selected
            array('p' => array('default_value' => '1.u0', 'data' => '', 'report_default' => '')),
            // product any selected
            array('p' => array('default_value' => '~any~', 'data' => array(null), 'report_default' => '~any~')),

            // visible category at root
            array('c' => array('default_value' => '1.161', 'data' => array('161'), 'report_default' => array('161'))),
            // non-visible category at root
            array('c' => array('default_value' => '1.122', 'data' => '', 'report_default' => '')),
            // visible child category
            array('c' => array('default_value' => '2.77', 'data' => array('71,77'), 'report_default' => array('71,77'))),
            // category none selected
            array('c' => array('default_value' => '1.u0', 'data' => '', 'report_default' => '')),
            // category any selected
            array('c' => array('default_value' => '~any~', 'data' => array(null), 'report_default' => '~any~')),
        );
        foreach ($tests as $test) {
            $reportID = '176' . time() . rand();
            $responseObjectGetFilterByNameProduct->result = array(
                'fltr_id' => 2,
                'name' => 'bobby1',
                'type' => 1,
                'oper_id' => 10,
                'data_type' => 1,
                'optlist_id' => 9,
                'expression1' => 'tables.bobby1',
                'attributes' => 273,
                'default_value' => $test['p']['default_value'] ?: '~any',
                'prompt' => 'P Hierarchy',
                'required' => 0,
            );
            $responseObjectGetFilterByNameCategory->result = array(
                'fltr_id' => 2,
                'name' => 'bobby2',
                'type' => 1,
                'oper_id' => 10,
                'data_type' => 1,
                'optlist_id' => 9,
                'expression1' => 'tables.bobby2',
                'attributes' => 273,
                'default_value' => $test['c']['default_value'] ?: '~any',
                'prompt' => 'C Hierarchy',
                'required' => 0,
            );
            $responseObjectGetSearchFilterTypeDefault->result = array('default_value' => $test['kw'] ?: '');
            $filters = array();
            $expected = array(
                'searchType' => (object) array(
                    'filters' => (object) array(
                        'rnSearchType' => 'searchType',
                        'fltr_id' => null,
                        'data' => null,
                        'oper_id' => null,
                        'report_id' => $reportID,
                    ),
                    'type' => 'searchType',
                ),
                'keyword' => (object) array(
                    'filters' => (object) array(
                        'rnSearchType' => 'keyword',
                        'data' => $test['kw'] ?: '',
                        'report_id' => $reportID,
                    ),
                    'type' => 'keyword',
                ),
                'p' => (object) array(
                    'filters' => (object) array(
                        'fltr_id' => 2,
                        'oper_id' => 10,
                        'optlist_id' => 9,
                        'report_id' => $reportID,
                        'rnSearchType' => 'menufilter',
                        'data' => $test['p']['data'] ?: '',
                    ),
                    'type' => 'bobby1',
                    'report_default' => $test['p']['report_default'] ?: '',
                ),
                'c' => (object) array(
                    'filters' => (object) array(
                        'fltr_id' => 2,
                        'oper_id' => 10,
                        'optlist_id' => 9,
                        'report_id' => $reportID,
                        'rnSearchType' => 'menufilter',
                        'data' => $test['c']['data'] ?: '',
                    ),
                    'type' => 'bobby2',
                    'report_default' => $test['c']['report_default'] ?: '',
                ),
            );
            $mockReport->returnsByValue('getFilterByName', $responseObjectGetFilterByNameProduct, array($reportID, 'prod'));
            $mockReport->returnsByValue('getFilterByName', $responseObjectGetFilterByNameCategory, array($reportID, 'cat'));
            $mockReport->returnsByValue('getSearchFilterTypeDefault', $responseObjectGetSearchFilterTypeDefault, array($reportID));
            Url::setFiltersFromAttributesAndUrl(array('report_id' => $reportID), $filters, $CI);
            $this->assertIdentical($expected, $filters, 'Failed with test ' . var_export($test, true) . '.  Expected: ' . var_export($expected, true) . ' and got: ' . var_export($filters, true) . '. %s');
        }
    }

    function testSetUFiltersFromAttributesAndUrlAppliesFixedFilters() {
        $expected = array(
            'searchType' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'searchType',
                    'fltr_id' => 4,
                    'data' => 4,
                    'oper_id' => 9,
                    'report_id' => 111,
                ),
                'type' => 'searchType',
            ),
           'keyword' => (object) array(
               'filters' => (object) array(
                   'rnSearchType' => 'keyword',
                   'data' => 'bananas',
                   'report_id' => 111,
                ),
                'type' => 'keyword',
            ),
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => NULL,
                    'oper_id' => NULL,
                    'optlist_id' => NULL,
                    'report_id' => 111,
                    'rnSearchType' => 'menufilter',
                    'data' => array('1'),
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
            'c' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => NULL,
                    'oper_id' => NULL,
                    'optlist_id' => NULL,
                    'report_id' => 111,
                    'rnSearchType' => 'menufilter',
                    'data' => array('161'),
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
        );
        $output = array();
        Url::setFiltersFromAttributesAndUrl(array('report_id' => 111, 'static_filter' => 'kw=bananas,c=161,p=1'), $output);
        $this->assertSame(count($expected), count($output));
        $this->assertIdentical($expected['p'], $output['p']);
        $this->assertIdentical($expected['c'], $output['c']);
        $this->assertIdentical($expected['keyword'], $output['keyword']);
        $this->assertIdentical($expected['searchType'], $output['searchType']);
    }

    function testSetUFiltersFromAttributesAndUrlFixedFiltersTakePrecedenceOverUrlParams() {
        // URL parameters
        $parameters = array(
            'st' => '5',
            'kw' => 'phone',
            'p' => '1',
            'c' => '2',
            'org' => '1',
            'sort' => '2,3',
            'page' => '2',
            'search' => '1',
        );
        $this->addUrlParameters($parameters);

        $filters = array();
        Url::setFiltersFromAttributesAndUrl(array('report_id' => 176, 'static_filter' => 'st=7,kw=bananas,p=2,c=,org=2,sort=3,page=3,search=2'), $filters);
        $this->assertEqual('7', $filters['searchType']->filters->fltr_id);
        $this->assertEqual('7', $filters['searchType']->filters->data);
        $this->assertEqual('bananas', $filters['keyword']->filters->data);
        $this->assertEqual('1,2', $filters['p']->filters->data[0]);
        $this->assertEqual('', $filters['c']->filters->data[0]);
        $this->assertEqual('org', $filters['org']->filters->rnSearchType);
        $this->assertEqual(1, $filters['sort_args']['filters']['sort_order']);
        $this->assertEqual(3, $filters['sort_args']['filters']['col_id']);
        $this->assertEqual(1, $filters['sort_args']['filters']['sort_direction']);
        $this->assertEqual('3', $filters['page']);
        $this->assertEqual('1', $filters['search']);

        $this->restoreUrlParameters();
    }

    function testReplaceExternalLoginVariables() {
        $configs = TestHelper::getConfigValues(array('PTA_EXTERNAL_LOGIN_URL', 'PTA_ERROR_URL'));

        Rnow::updateConfig('PTA_EXTERNAL_LOGIN_URL', '', true);
        Rnow::updateConfig('PTA_ERROR_URL', '', true);
        $this->assertNull(Url::replaceExternalLoginVariables(null, null));

        Rnow::updateConfig('PTA_EXTERNAL_LOGIN_URL', 'pta_external_login_url/%next_page%/%error_code%', true);
        $this->assertEqual('pta_external_login_url/goHereNext/5', Url::replaceExternalLoginVariables(5, 'goHereNext'));

        Rnow::updateConfig('PTA_EXTERNAL_LOGIN_URL', 'pta_external_login_url/%next_page%', true);
        $this->assertEqual('pta_external_login_url/goHereNext', Url::replaceExternalLoginVariables(0, 'goHereNext'));

        Rnow::updateConfig('PTA_ERROR_URL', 'pta_error_url/%next_page%/%error_code%', true);
        $this->assertEqual('pta_error_url/goHereNext/5', Url::replaceExternalLoginVariables(5, 'goHereNext'));

        Rnow::updateConfig('PTA_EXTERNAL_LOGIN_URL', 'pta_external_login_url', true);
        $this->assertEqual('pta_external_login_url?p_next_page=goHereNext', Url::replaceExternalLoginVariables(0, 'goHereNext'));

        TestHelper::setConfigValues($configs);
    }

    function testGetProductVersionForLinks() {
        $this->assertNotNull(Url::getProductVersionForLinks());
        $this->assertIdentical('cloud12a', Url::getProductVersionForLinks('12.2'));
        $this->assertIdentical('cloud12b', Url::getProductVersionForLinks('12.5'));
        $this->assertIdentical('cloud12c', Url::getProductVersionForLinks('12.8'));
        $this->assertIdentical('cloud12d', Url::getProductVersionForLinks('12.11'));
        $this->assertIdentical('', Url::getProductVersionForLinks('12.13'));
        $this->assertIdentical('', Url::getProductVersionForLinks('999999'));
    }

    function testGetShortEufAppUrl() {
        $expected = "{$this->hostname}/app";
        $this->assertEqual($expected, Url::getShortEufAppUrl());
        $this->assertEqual($expected, Url::getShortEufAppUrl('sameAsCurrentPage'));
        $this->assertEqual($expected, Url::getShortEufAppUrl('sameAsRequest'));
        $this->assertEqual("$expected/foo", Url::getShortEufAppUrl('sameAsRequest', 'foo'));
    }

    function testShouldPageForceSsl() {
        $configs = TestHelper::getConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS', 'CP_LOGIN_URL'));
        $urlRequest = "/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/useShouldPageForceSsl/";
        $pages = array(
            "utils/login_form" => 'true',
            "account/reset_password" => 'true',
            "account/setup_password" => 'true',
            "account/questions/detail" => 'false',
            "utils/account_assistance" => 'false',
        );
        // Variations using the pages above.
        // Specify a value of 'true' or 'false' to over-ride expected value from $pages
        $variations = array(
            '/app/%s' => null,
            '/app/%s/abc' => null,
            '/app/%s#abc' => null,
            '/app/%s2' => 'false',
            '/ci/%s' => 'false',
        );

        $getResults = function($force = 1) use ($urlRequest, $pages, $variations) {
            Rnow::updateConfig('CP_FORCE_PASSWORDS_OVER_HTTPS', $force, false);
            $results = array();
            foreach($pages as $page => $expected) {
                foreach($variations as $variation => $override) {
                    $url = sprintf($variation, $page);
                    $expected = $force ? ($override === null ? $expected : $override) : 'false';
                    $actual = TestHelper::makeRequest($urlRequest . urlencode($url));
                    if ($expected !== $actual) {
                        $error = "Expected: '$expected', received: '$actual' for page: '$url'<br/>";
                    }
                    $results[] = array($url, $expected, $actual, $error);
                }
            }
            return $results;
        };

        // CP_FORCE_PASSWORDS_OVER_HTTPS = true
        foreach ($getResults() as $results) {
            list($url, $expected, $actual, $error) = $results;
            $this->assertIdentical($expected, $actual, $error);
        }

        // CP_FORCE_PASSWORDS_OVER_HTTPS = false
        foreach ($getResults(0) as $results) {
            list($url, $expected, $actual, $error) = $results;
            $this->assertIdentical($expected, $actual, $error);
        }

        TestHelper::setConfigValues($configs, true);
    }

    function testHttpsRedirectHandling() {
        $config = TestHelper::getConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS'));

        TestHelper::setConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS' => '0'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/redirectIfPageNeedsToBeSecure", array('justHeaders' => true,'useHttps' => false));
        $redirect = $this->httpsRedirectEncountered($output);
        $this->assertEqual(0, $redirect);

        TestHelper::setConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS' => '0'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/forceRedirectToHttps", array('justHeaders' => true,'useHttps' => false));
        $redirect = $this->httpsRedirectEncountered($output);
        $this->assertEqual(1, $redirect);

        TestHelper::setConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS' => '0'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/redirectIfPageNeedsToBeSecure", array('justHeaders' => true,'useHttps' => true));
        $redirect = $this->httpsRedirectEncountered($output);
        $this->assertEqual(0, $redirect);

        TestHelper::setConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS' => '0'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/forceRedirectToHttps", array('justHeaders' => true,'useHttps' => true));
        $redirect = $this->httpsRedirectEncountered($output);
        $this->assertEqual(0, $redirect);

        TestHelper::setConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS' => '1'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/redirectIfPageNeedsToBeSecure", array('justHeaders' => true,'useHttps' => false));
        $redirect = $this->httpsRedirectEncountered($output);
        $this->assertEqual(1, $redirect);

        TestHelper::setConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS' => '1'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/forceRedirectToHttps", array('justHeaders' => true,'useHttps' => false));
        $redirect = $this->httpsRedirectEncountered($output);
        $this->assertEqual(1, $redirect);

        TestHelper::setConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS' => '1'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/redirectIfPageNeedsToBeSecure", array('justHeaders' => true,'useHttps' => true));
        $redirect = $this->httpsRedirectEncountered($output);
        $this->assertEqual(0, $redirect);

        TestHelper::setConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS' => '1'), true);
        $output = TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/forceRedirectToHttps", array('justHeaders' => true,'useHttps' => true));
        $redirect = $this->httpsRedirectEncountered($output);
        $this->assertEqual(0, $redirect);

        TestHelper::setConfigValues($config, true);
    }

    function httpsRedirectEncountered($headers) {
        return preg_match("/Location: https:\/\/[A-Za-z0-9_%]+/", $headers);
    }

    function redirectIfPageNeedsToBeSecure() {
        Url::redirectIfPageNeedsToBeSecure();
    }

    function forceRedirectToHttps() {
        Url::redirectIfPageNeedsToBeSecure(true);
    }

    function testGetShortEufBaseUrl() {
        $inputs = array(
            array(true,                null),
            array(false,               null),
            array('sameAsRequest',     null),
            array('sameAsCurrentPage', null),
            array(true,                ''),
            array(false,               ''),
            array('sameAsRequest',     ''),
            array('sameAsCurrentPage', ''),
            array(true,                '/app'),
            array(false,               '/app'),
            array('sameAsRequest',     '/app'),
            array('sameAsCurrentPage', '/app'),
            array(true,                '/app/utils/account_assistance'),
            array(false,               '/app/utils/account_assistance'),
            array('sameAsRequest',     '/app/utils/account_assistance'),
            array('sameAsCurrentPage', '/app/utils/account_assistance'),
            array(true,                '/app/account/questions/detail'),
            array(false,               '/app/account/questions/detail'),
            array('sameAsRequest',     '/app/account/questions/detail'),
            array('sameAsCurrentPage', '/app/account/questions/detail'),
            array(true,                '/app/utils/login_form', 'https'),
            array(false,               '/app/utils/login_form', 'https'),
            array('sameAsRequest',     '/app/utils/login_form', 'https'),
            array('sameAsCurrentPage', '/app/utils/login_form', 'https'),
            array(true,                '/app/account/reset_password', 'https'),
            array(false,               '/app/account/reset_password', 'https'),
            array('sameAsRequest',     '/app/account/reset_password', 'https'),
            array('sameAsCurrentPage', '/app/account/reset_password', 'https'),
            array(true,                '/app/account/setup_password', 'https'),
            array(false,               '/app/account/setup_password', 'https'),
            array('sameAsRequest',     '/app/account/setup_password', 'https'),
            array('sameAsCurrentPage', '/app/account/setup_password', 'https'),
        );

        $configs = TestHelper::getConfigValues(array('CP_FORCE_PASSWORDS_OVER_HTTPS'));
        Rnow::updateConfig('CP_FORCE_PASSWORDS_OVER_HTTPS', 1, true);

        foreach($inputs as $input) {
            list($matchProtocol, $path, $protocol) = $input;
            $protocol = $protocol ?: 'http';
            $expected = "$protocol://{$this->host}$path";
            $actual = Url::getShortEufBaseUrl($matchProtocol, $path);
            $this->assertIdentical($expected, $actual);
        };

        TestHelper::setConfigValues($configs);
    }

    function testGetCachedContentServer() {
        $configs = TestHelper::getConfigValues(array('CACHED_CONTENT_SERVER', 'SEC_END_USER_HTTPS'));

        Rnow::updateConfig('SEC_END_USER_HTTPS', 1, true);
        $widgetServer = $configs['CACHED_CONTENT_SERVER'] ?: $this->host;
        $this->assertEqual("https://$widgetServer", Url::getCachedContentServer());

        Rnow::updateConfig('CACHED_CONTENT_SERVER', '', true);
        // Falls back to OE_WEB_SERVER
        $this->assertEqual("https://$widgetServer", Url::getCachedContentServer());
        // requireCachedServer
        $this->assertFalse(Url::getCachedContentServer(true));

        TestHelper::setConfigValues($configs);
    }

    function testConvertInsecureUrlToNetworkPathReference() {
        $this->assertTrue(Url::convertInsecureUrlToNetworkPathReference(true));
        $this->assertFalse(Url::convertInsecureUrlToNetworkPathReference(false));
        $this->assertNull(Url::convertInsecureUrlToNetworkPathReference(null));
        $this->assertIdentical('', Url::convertInsecureUrlToNetworkPathReference(''));
        $this->assertIdentical('/', Url::convertInsecureUrlToNetworkPathReference('/'));
        $this->assertIdentical('https:', Url::convertInsecureUrlToNetworkPathReference('https:'));
        $this->assertIdentical('http:', Url::convertInsecureUrlToNetworkPathReference('http:'));
        $this->assertIdentical('//', Url::convertInsecureUrlToNetworkPathReference('http://'));
        $this->assertIdentical('//foo', Url::convertInsecureUrlToNetworkPathReference('http://foo'));
        $this->assertIdentical('//foo/http://', Url::convertInsecureUrlToNetworkPathReference('http://foo/http://'));
        $this->assertIdentical('https://', Url::convertInsecureUrlToNetworkPathReference('https://'));
        $this->assertIdentical('https://foo', Url::convertInsecureUrlToNetworkPathReference('https://foo'));
        $this->assertIdentical('https://foo/http/', Url::convertInsecureUrlToNetworkPathReference('https://foo/http/'));
        $this->assertIdentical('https://foo/http://', Url::convertInsecureUrlToNetworkPathReference('https://foo/http://'));
        $this->assertIdentical('HTTPS:', Url::convertInsecureUrlToNetworkPathReference('HTTPS:'));
        $this->assertIdentical('HTTP:', Url::convertInsecureUrlToNetworkPathReference('HTTP:'));
        $this->assertIdentical('//', Url::convertInsecureUrlToNetworkPathReference('HTTP://'));
        $this->assertIdentical('//foo', Url::convertInsecureUrlToNetworkPathReference('HTTP://foo'));
        $this->assertIdentical('//foo/HTTP://', Url::convertInsecureUrlToNetworkPathReference('HTTP://foo/HTTP://'));
        $this->assertIdentical('HTTPS://', Url::convertInsecureUrlToNetworkPathReference('HTTPS://'));
        $this->assertIdentical('HTTPS://foo', Url::convertInsecureUrlToNetworkPathReference('HTTPS://foo'));
        $this->assertIdentical('HTTPS://foo/HTTP/', Url::convertInsecureUrlToNetworkPathReference('HTTPS://foo/HTTP/'));
        $this->assertIdentical('HTTPS://foo/HTTP://', Url::convertInsecureUrlToNetworkPathReference('HTTPS://foo/HTTP://'));
    }

    function testIsRequestHttps()  {
        $expected = Text::getSubstringBefore($_SERVER['SCRIPT_URI'], ':') == 'https';
        $this->assertIdentical($expected, Url::isRequestHttps());
    }

    function testGetHomePage() {
        $configs = TestHelper::getConfigValues(array('CP_HOME_URL'));
        $home = $configs['CP_HOME_URL'];
        $this->assertIdentical("/app/$home", Url::getHomePage());
        $this->assertIdentical("/app/$home", Url::getHomePage(true));
        $this->assertIdentical($home, Url::getHomePage(false));

        Rnow::updateConfig('CP_HOME_URL', '', true);
        $this->assertIdentical("/app/$home", Url::getHomePage());
        $this->assertIdentical("/app/$home", Url::getHomePage(true));
        $this->assertIdentical($home, Url::getHomePage(false));

        TestHelper::setConfigValues($configs);
    }

    function testIsExternalUrl() {
        $this->assertTrue(Url::isExternalUrl("http://google.com"));
        $this->assertFalse(Url::isExternalUrl("https://" . $this->host . "/app/answers/list"));
    }

    function testUrlParameterReplacer() {
        $expected = '<?=\\RightNow\\Utils\\Url::getParameterWithKey(\'\');?>';
        $this->assertIdentical($expected, Url::urlParameterReplacer(0));
        $this->assertIdentical($expected, Url::urlParameterReplacer(''));
        $this->assertIdentical($expected, Url::urlParameterReplacer(null));
        $this->assertIdentical($expected, Url::urlParameterReplacer(array()));

        $expected = '<?=\\RightNow\\Utils\\Url::getParameterWithKey(\'some parameter\');?>';
        $this->assertIdentical($expected, Url::urlParameterReplacer(array('match0', null, 'some parameter')));

        $expected = '<?=\\RightNow\\Utils\\Url::getParameter(\'some parameter\');?>';
        $this->assertIdentical($expected, Url::urlParameterReplacer(array('match0', 'match1', 'some parameter')));
    }

    function testUrlParameterReplacerWithinPhp() {
        $expected = '\' . \\RightNow\\Utils\\Url::getParameterWithKey(\'\') . \'';
        $this->assertIdentical($expected, Url::urlParameterReplacerWithinPhp(0));
        $this->assertIdentical($expected, Url::urlParameterReplacerWithinPhp(''));
        $this->assertIdentical($expected, Url::urlParameterReplacerWithinPhp(null));
        $this->assertIdentical($expected, Url::urlParameterReplacerWithinPhp(array()));

        $expected = '\' . \\RightNow\\Utils\\Url::getParameter(\'some parameter\') . \'';
        $actual = Url::urlParameterReplacerWithinPhp(array('match0', 'match1', 'some parameter'));
        $this->assertIdentical($expected, $actual);

        $expected = '\' . \\RightNow\\Utils\\Url::getParameterWithKey(\'some parameter\') . \'';
        $actual = Url::urlParameterReplacerWithinPhp(array('match0', null, 'some parameter'));
        $this->assertIdentical($expected, $actual);
    }

    function testGetJavaScriptParameterIndex() {
        $this->assertIsA(Url::getJavaScriptParameterIndex(), 'integer');
        $this->assertTrue(Url::getJavaScriptParameterIndex() > 0);
    }

    function testCommunitySsoTokenWithinPhp() {
        $expected = '\' . \\RightNow\\Utils\\Url::communitySsoToken(\'\') . \'';
        $this->assertIdentical($expected, Url::communitySsoTokenWithinPhp(0));
        $this->assertIdentical($expected, Url::communitySsoTokenWithinPhp(null));
        $this->assertIdentical($expected, Url::communitySsoTokenWithinPhp(''));
        $this->assertIdentical($expected, Url::communitySsoTokenWithinPhp(array()));
        $this->assertIdentical($expected, Url::communitySsoTokenWithinPhp(array('blah')));
        $this->assertIdentical($expected, Url::communitySsoTokenWithinPhp(array('blah', 'blah')));

        $expected = '\' . \\RightNow\\Utils\\Url::communitySsoToken(\'something\') . \'';
        $this->assertIdentical($expected, Url::communitySsoTokenWithinPhp(array('blah', 'blah', 'something')));
    }

    function testGetProductOrCategoryFilter() {
        $filters = array();
        $expected = array(
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => array(NULL),
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
        );
        Url::getProductOrCategoryFilter('p', null, $filters, null);
        $this->assertIdentical($expected, $filters);

        Url::getProductOrCategoryFilter('p', false, $filters, null);
        $this->assertIdentical($expected, $filters);

        $filters = array();
        $expected = array(
            'c' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => array(NULL),
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
        );
        Url::getProductOrCategoryFilter('c', false, $filters, null);
        $this->assertIdentical($expected, $filters);

        // Product specified in URL and report_id specified
        $filters = array();
        Url::getProductOrCategoryFilter('p', '1', $filters, 176);
        $expected = array(
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 2,
                    'oper_id' => 10,
                    'optlist_id' => 9,
                    'report_id' => 176,
                    'rnSearchType' => 'menufilter',
                    'data' => array('1'),
                ),
                'type' => 'map_prod_hierarchy',
                'report_default' => '~any~',
            ),
        );
        $this->assertIdentical($expected, $filters);

        // "No Value", filter to fetch the content that do not have any product associated with
        $filters = array();
        Url::getProductOrCategoryFilter('p', '-1', $filters, 176);
        $expected = array(
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 2,
                    'oper_id' => 10,
                    'optlist_id' => 9,
                    'report_id' => 176,
                    'rnSearchType' => 'menufilter',
                    'data' => array("-1"),
                ),
                'type' => 'map_prod_hierarchy',
                'report_default' => '~any~',
            ),
        );
        $this->assertIdentical($expected, $filters);

        // Multiple products separated by a semi-colon
        $filters = array();
        Url::getProductOrCategoryFilter('p', '1;128', $filters, 176);
        $this->assertIdentical(array('1', '128'), $filters['p']->filters->data);

        // Product specified in URL but no report_id specified
        $filters = array();
        Url::getProductOrCategoryFilter('p', '1', $filters, null);
        $expected = array(
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => array('1'),
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
        );
        $this->assertIdentical($expected, $filters);

        // runtimeValue false
        $filters = array();
        Url::getProductOrCategoryFilter('p', false, $filters, 176);
        $expected = array(
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 2,
                    'oper_id' => 10,
                    'optlist_id' => 9,
                    'report_id' => 176,
                    'rnSearchType' => 'menufilter',
                    'data' => array(null),
                ),
                'type' => 'map_prod_hierarchy',
                'report_default' => '~any~',
            ),
        );
        $this->assertIdentical($expected, $filters);

        // Categories
        $filters = array();
        $expected = array(
            'c' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'oper_id' => 10,
                    'optlist_id' => 12,
                    'report_id' => 176,
                    'rnSearchType' => 'menufilter',
                    'data' => array(''),
                ),
                'type' => 'map_cat_hierarchy',
                'report_default' => '~any~',
            ),
        );
        Url::getProductOrCategoryFilter('c', true, $filters, 176);
        $this->assertIdentical($expected, $filters);
    }

    function testGetProductOrCategoryFilterWithReportDefaults() {
        if (!class_exists('\RightNow\Models\MockReport')) {
            // make sure Report model is loaded
            get_instance()->model('Report');
            Mock::generate('\RightNow\Models\Report', '\RightNow\Models\MockReport');
        }
        if (!class_exists('\RightNow\Controllers\MockBase')) {
            Mock::generate('\RightNow\Controllers\Base', '\RightNow\Controllers\MockBase');
        }
        $mockReport = new \RightNow\Models\MockReport();
        $CI = new \RightNow\Controllers\MockBase();
        $CI->returnsByValue('model', $mockReport, array('Report'));
        $CI->returnsByValue('model', get_instance()->model('Prodcat'), array('Prodcat'));

        $responseObject = new \RightNow\Libraries\ResponseObject('is_array');

        $tests = array(
            // visible product at root
            array('type' => 'p', 'default_value' => '1.6', 'data' => array('6'), 'report_default' => array('6')),
            // visible child product
            array('type' => 'p', 'default_value' => '3.160', 'data' => array('1,4,160'), 'report_default' => array('1,4,160')),
            // product none selected
            array('type' => 'p', 'default_value' => '1.u0', 'data' => '', 'report_default' => ''),
            // product any selected
            array('type' => 'p', 'default_value' => '~any~', 'data' => array(null), 'report_default' => '~any~'),

            // visible category at root
            array('type' => 'c', 'default_value' => '1.161', 'data' => array('161'), 'report_default' => array('161')),
            // non-visible category at root
            array('type' => 'c', 'default_value' => '1.122', 'data' => '', 'report_default' => ''),
            // visible child category
            array('type' => 'c', 'default_value' => '2.77', 'data' => array('71,77'), 'report_default' => array('71,77')),
            // category none selected
            array('type' => 'c', 'default_value' => '1.u0', 'data' => '', 'report_default' => ''),
            // category any selected
            array('type' => 'c', 'default_value' => '~any~', 'data' => array(null), 'report_default' => '~any~'),
        );
        foreach ($tests as $test) {
            $reportID = '176' . time() . rand();
            $responseObject->result = array(
                'fltr_id' => 2,
                'name' => 'bobby',
                'type' => 1,
                'oper_id' => 10,
                'data_type' => 1,
                'optlist_id' => 9,
                'expression1' => 'tables.bobby',
                'attributes' => 273,
                'default_value' => $test['default_value'],
                'prompt' => 'PC Hierarchy',
                'required' => 0,
            );
            $filters = array();
            $expected = array(
                $test['type'] => (object) array(
                    'filters' => (object) array(
                        'fltr_id' => 2,
                        'oper_id' => 10,
                        'optlist_id' => 9,
                        'report_id' => $reportID,
                        'rnSearchType' => 'menufilter',
                        'data' => $test['data'],
                    ),
                    'type' => 'bobby',
                    'report_default' => $test['report_default'],
                ),
            );
            $mockReport->returnsByValue('getFilterByName', $responseObject, array($reportID, 'prod'));
            $mockReport->returnsByValue('getFilterByName', $responseObject, array($reportID, 'cat'));
            Url::getProductOrCategoryFilter($test['type'], false, $filters, $reportID, $CI);
            $this->assertIdentical($expected, $filters, 'Failed with test ' . var_export($test, true) . '.  Expected: ' . var_export($expected, true) . ' and got: ' . var_export($filters, true) . '. %s');
        }
    }

    function testGetSearchTypeFilter() {
        $CI = get_instance();
        $expected = function($filterID, $operID, $reportID = 176) {
            return (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'searchType',
                    'fltr_id' => $filterID,
                    'data' => $filterID,
                    'oper_id' => $operID,
                    'report_id' => $reportID,
                ),
                'type' => 'searchType',
            );
        };

        // Widx default
        $this->assertIdentical($expected('bananas', null), Url::getSearchTypeFilter(176, 'bananas', $CI));
        // No runtime value but default report value
        $this->assertIdentical($expected(5, 1), Url::getSearchTypeFilter(176, '', $CI));
        // Legit runtime value
        $this->assertIdentical($expected(5, 1), Url::getSearchTypeFilter(176, '5', $CI));
        // No runtime value, no default report value.
        $this->assertIdentical($expected(null, null, 1), Url::getSearchTypeFilter(1, null, $CI));
    }

    function testGetKeywordFilter() {
        $CI = get_instance();
        $expected = function($keyword, $reportID = 176) {
            return (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'keyword',
                    'data' => $keyword,
                    'report_id' => $reportID,
                ),
                'type' => 'keyword',
            );
        };

        // Null guard
        $this->assertIdentical($expected(''), Url::getKeywordFilter(176, null, $CI));
        // False guard
        $this->assertIdentical($expected(''), Url::getKeywordFilter(176, false, $CI));
        // Empty string
        $this->assertIdentical($expected(''), Url::getKeywordFilter(176, '', $CI));
        // Legit runtime value
        $this->assertIdentical($expected('bananas'), Url::getKeywordFilter(176, 'bananas', $CI));
    }

    function testGetOrganizationFilter() {
        $CI = get_instance();
        $expected = function($filterID = null, $operID = null, $val = null, $reportID = 176) {
            $filter = (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'org',
                    'report_id' => $reportID,
                ),
                'type' => 'org',
            );
            if ($filterID) {
                $filter->filters->fltr_id = $filterID;
            }
            if ($operID) {
                $filter->filters->oper_id = $operID;
            }
            if ($val) {
                $filter->filters->val = $val;
            }
            return $filter;
        };

        $this->assertIdentical($expected(), Url::getOrganizationFilter(176, null, $CI));
        $this->assertIdentical($expected(), Url::getOrganizationFilter(176, '', $CI));
        $this->assertIdentical($expected(), Url::getOrganizationFilter(176, '1', $CI));
        $this->assertIdentical($expected(), Url::getOrganizationFilter(176, 'bananas', $CI));

        if (!class_exists('\RightNow\Libraries\MockSession')) {
            Mock::generate('\RightNow\Libraries\Session', '\RightNow\Libraries\MockSession');
        }
        $session = new \RightNow\Libraries\MockSession;
        $session->setReturnValue('getProfile', (object) array('orgID' => 1, 'contactID' => 1286));
        $mockReport = new \RightNow\Models\MockReport;
        $mockReport->setReturnValue('getOrganizationAlias', (object) array('result' => 'bananaorg'));
        $mockReport->setReturnValue('getIncidentAlias', (object) array('result' => 'bananaincident'));
        $CI = new \RightNow\Controllers\MockBase;
        $CI->session = $session;
        $CI->returnsByValue('model', $mockReport, array('Report'));

        // Incident c_id
        $this->assertIdentical($expected('bananaincident.c_id', 1, 1286, 196), Url::getOrganizationFilter(196, '', $CI));
        // Incident org_id
        $this->assertIdentical($expected('bananaincident.org_id', 1, 1, 196), Url::getOrganizationFilter(196, '1', $CI));
        // Org level (1)
        $this->assertIdentical($expected('bananaorg.lvl1_id', 1, 1, 196), Url::getOrganizationFilter(196, '2', $CI));

        $session = new \RightNow\Libraries\MockSession;
        $session->setReturnValue('getProfile', (object) array('orgID' => 323, 'contactID' => 1286, 'orgLevel' => 3));
        $CI = new \RightNow\Controllers\MockBase;
        $CI->session = $session;
        $CI->returnsByValue('model', $mockReport, array('Report'));

        // Org level (from profile)
        $this->assertIdentical($expected('bananaorg.lvl3_id', 1, 323, 196), Url::getOrganizationFilter(196, '2', $CI));
    }

    function testSortFilter() {
        $expected = function($column, $direction) {
            return array('filters' => array(
                'col_id' => $column,
                'sort_direction' => $direction,
                'sort_order' => 1,
            ));
        };

        $this->assertIdentical($expected(0, 1), Url::getSortFilter(''));
        $this->assertIdentical($expected(0, 1), Url::getSortFilter('bananas'));
        $this->assertIdentical($expected(0, 1), Url::getSortFilter(','));
        $this->assertIdentical($expected(0, 1), Url::getSortFilter(null));
        $this->assertIdentical($expected(1, 1), Url::getSortFilter('1,0'));
        $this->assertIdentical($expected(23, -1), Url::getSortFilter('23,-1'));
    }

    function testgetCustomFilters() {
        $CI = get_instance();
        $this->assertIdentical(array(), Url::getCustomFilters(176, array(), $CI));
        $this->assertIdentical(array(), Url::getCustomFilters(176, array('p' => 'bananas', 'bananas' => 'no'), $CI));

        $mockReport = new \RightNow\Models\MockReport;
        $mockReport->setReturnValue('getFilterByName', (object) array('result' => array('fltr_id' => 'bananas id', 'oper_id' => 34)));
        $CI = new \RightNow\Controllers\MockBase;
        $CI->returnsByValue('model', $mockReport, array('Report'));

        $this->assertIdentical(array(
            'bananas' => (object) array('filters' => (object) array(
                    'fltr_id' => 'bananas id',
                    'oper_id' => 34,
                    'report_id' => 176,
                    'rnSearchType' => 'filter',
                    'data' => array('tru', 'th'),
                ),
                'type' => 'bananas',
            )
        ), Url::getCustomFilters(176, array('p' => '12', 'sort' => 'no', 'bananas' => 'tru;th'), $CI));
    }

    function testApplyWebIndexFilters() {
        $actual = array();
        $expected = array();

        Url::applyWebIndexFilters(176, $actual, array());
        $this->assertIdentical($expected, $actual);
        Url::applyWebIndexFilters(CP_NOV09_WIDX_DEFAULT, $actual, array());
        $this->assertIdentical($expected, $actual);
        Url::applyWebIndexFilters(CP_WIDX_REPORT_DEFAULT, $actual, array());
        $this->assertIdentical($expected, $actual);

        // No sort args
        $expected = array('webSearchType' => (object) array('filters' => (object) array(
            'rnSearchType' => 'webSearchType',
            'fltr_id' => 'bananas',
            'data' => 'bananas',
            'report_id' => CP_NOV09_WIDX_DEFAULT,
        )));
        $input = array('searchType' => (object) array('filters' => (object) array('fltr_id' => 'bananas')));
        $expected = $input + $expected;
        Url::applyWebIndexFilters(CP_NOV09_WIDX_DEFAULT, $input, array());
        $this->assertIdentical($expected, $input);

        // sort args modified
        $expected['sort_args'] = array('filters' => array('search_type' => 'sorting bananas', 'col_id' => '23'));
        $expected['webSearchSort'] = (object) array('filters' => (object) array(
            'rnSearchType' => 'webSearchSort',
            'data' => (object) array('col_id' => '23'),
            'report_id' => CP_WIDX_REPORT_DEFAULT . '',
        ));
        $expected['webSearchType']->filters->report_id = CP_WIDX_REPORT_DEFAULT . '';
        $input['sort_args'] = array('filters' => array('search_type' => null, 'col_id' => '23'));
        Url::applyWebIndexFilters(CP_WIDX_REPORT_DEFAULT . '', $input, array('st' => 'sorting bananas'));
        $this->assertIdentical($expected, $input);

        // sort args untouched
        $input['sort_args']['filters']['search_type'] = $expected['sort_args']['filters']['search_type'] = null;
        $this->assertIdentical($expected, $input);
    }

    function testExtractFilters() {
        $this->assertIdentical(array(), Url::extractFilters(null));
        $this->assertIdentical(array(), Url::extractFilters(''));
        $this->assertIdentical(array(), Url::extractFilters('         '));
        $this->assertIdentical(array(), Url::extractFilters('     ,    '));
        $this->assertIdentical(array(), Url::extractFilters(' bananas '));
        $this->assertIdentical(array('bananas' => '67'), Url::extractFilters(' bananas = 67,footwear and more '));
        $this->assertIdentical(array(
            'p' => '1',
            'c' => '2;33',
            'kw' => 'bananas',
            'org_id' => '57',
        ), Url::extractFilters('p=1,c=2;33,kw= bananas , org_id =57'));
        $this->assertIdentical(array('p' => ''), Url::extractFilters('p='));
    }

    function testCalculateEufBaseUrl() {
        $this->assertEqual("https://{$this->host}", Url::calculateEufBaseUrl('shouldBeSecure'));
        $this->assertEqual($this->hostname, Url::calculateEufBaseUrl('sameAsRequest'));
    }

    function testGetLongEufBaseUrl() {
        $path = Text::getSubstringBefore($_SERVER['SCRIPT_NAME'], '.cfg') . '.cfg/php/cp';
        $expected = "{$this->hostname}$path";
        $this->assertEqual($expected, Url::getLongEufBaseUrl());
        $this->assertEqual($expected, Url::getLongEufBaseUrl('sameAsCurrentPage'));
        $this->assertEqual($expected, Url::getLongEufBaseUrl('sameAsRequest'));
        $this->assertEqual($path, Url::getLongEufBaseUrl('excludeProtocolAndHost'));

        $this->assertEqual("$expected/foo", Url::getLongEufBaseUrl(null, '/foo'));
        $this->assertEqual("$expected/foo", Url::getLongEufBaseUrl('sameAsCurrentPage', '/foo'));
        $this->assertEqual("$expected/foo", Url::getLongEufBaseUrl('sameAsRequest', '/foo'));
        $this->assertEqual("$path/foo", Url::getLongEufBaseUrl('excludeProtocolAndHost', '/foo'));
    }

    function testIsCallFromTagGallery() {
        $this->assertFalse(Url::isCallFromTagGallery());

        $referer = $_SERVER['HTTP_REFERER'];
        $_SERVER['HTTP_REFERER'] = '/ci/admin/docs/syndicatedWidgets';
        $this->assertTrue(Url::isCallFromTagGallery());

        $_SERVER['HTTP_REFERER'] = '/ci/admin/docs/some/other/directory';
        $this->assertTrue(Url::isCallFromTagGallery('some/other/directory'));

        $_SERVER['HTTP_REFERER'] = $referer;
    }

    function testIsPtaLogout() {
        $this->assertFalse(Url::isPtaLogout());

        $CI = $this->getMockCI();
        $CI->uri->setReturnValue('segment', 'pta', array(1));
        $CI->uri->setReturnValue('segment', 'logout', array(2));
        $this->assertTrue(Url::isPtaLogout($CI));
    }

    function testHostIsAllowed() {
        $this->assertTrue(Url::hostIsAllowed('http%3A%2f%2fsomeDomain.com', array('*.someDomain.com', '*.foo.com')));
        $this->assertTrue(Url::hostIsAllowed('http://someDomain.com', array('*.someDomain.com', '*.foo.com')));
        $this->assertTrue(Url::hostIsAllowed('http://someDomain.com', array('*.someDomain.com', '*.foo.com')));
        $this->assertTrue(Url::hostIsAllowed('http://www.someDomain.com', array('*.someDomain.com', '*.foo.com')));
        $this->assertTrue(Url::hostIsAllowed('HTTP://SoMeDoMaiN.com', array('*.someDomain.com', '*.foo.com')));
        $this->assertTrue(Url::hostIsAllowed('someDomain.com', array('*.someDomain.com', '*.foo.com')));

        $this->assertTrue(Url::hostIsAllowed($this->host, array($this->host)));
        $this->assertTrue(Url::hostIsAllowed($this->host, array('*.oraclevcn.com')));

        $this->assertFalse(Url::hostIsAllowed('malicious.com', array('*.us.oracle.com')));
        $this->assertFalse(Url::hostIsAllowed('malicious.com', array($this->host)));
        $this->assertFalse(Url::hostIsAllowed('http://malicious.com', array('*.us.oracle.com')));
        $this->assertFalse(Url::hostIsAllowed('http://www.malicious.com', array('*.us.oracle.com')));
        $this->assertFalse(Url::hostIsAllowed('HTTP://malicious.com', array('*.us.oracle.com')));
        $this->assertFalse(Url::hostIsAllowed($this->host, array('*.someDomain.com')));
        $this->assertFalse(Url::hostIsAllowed($this->host, array()));
    }

    function testGetOldYUICodePath() {
        $path = Url::getOldYUICodePath('');
        $this->assertIsA($path, 'string');
        $this->assertTrue(strlen($path) > 0);
        $this->assertTrue(FileSystem::isReadableDirectory(HTMLROOT . "/$path"));
        $this->assertEqual("{$path}base", Url::getOldYUICodePath('base'));
    }

    function testGetParameterWithKeyFunctionCall() {
        $method = $this->getStaticMethod('getParameterWithKeyFunctionCall');
        $expected = "\\RightNow\\Utils\\Url::getParameterWithKey('%s')";
        $this->assertIdentical(sprintf($expected, ''), $method(''));
        $this->assertIdentical(sprintf($expected, 'foo'), $method('foo'));
    }

    function testGetParameterFunctionCall() {
        $method = $this->getStaticMethod('getParameterFunctionCall');
        $expected = "\\RightNow\\Utils\\Url::getParameter('%s')";
        $this->assertIdentical(sprintf($expected, ''), $method(null));
        $this->assertIdentical(sprintf($expected, ''), $method(''));
        $this->assertIdentical(sprintf($expected, 'Array'), $method(array()));
        $this->assertIdentical(sprintf($expected, 'foo'), $method('foo'));
    }

    function testIsRedirectAllowedForHost() {
        // no external redirects should be allowed
        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '', 1);
        $this->assertFalse(Url::isRedirectAllowedForHost("http://www.oracle.com"));
        $this->assertFalse(Url::isRedirectAllowedForHost("http://www.oracle.com/to/some/other/page"));
        $this->assertFalse(Url::isRedirectAllowedForHost("http://someDomain.com"));
        $this->assertFalse(Url::isRedirectAllowedForHost("https://someDomain.com"));
        $this->assertFalse(Url::isRedirectAllowedForHost("//someDomain.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("someDomain.com"));

        // allow all external redirects
        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '*', 1);
        $this->assertTrue(Url::isRedirectAllowedForHost("http://www.oracle.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("http://www.oracle.com/to/some/other/page"));
        $this->assertTrue(Url::isRedirectAllowedForHost("http://someDomain.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("https://someDomain.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("//someDomain.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("someDomain.com"));

        // allow some external redirects
        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '*.oracle.com', 1);
        $this->assertTrue(Url::isRedirectAllowedForHost("http://www.oracle.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("https://www.oracle.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("//www.oracle.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("http://www.oracle.com/to/some/other/page"));
        $this->assertFalse(Url::isRedirectAllowedForHost("http://someDomain.com"));
        $this->assertFalse(Url::isRedirectAllowedForHost("https://someDomain.com"));
        $this->assertFalse(Url::isRedirectAllowedForHost("//someDomain.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("someDomain.com"));

        // with multiple entries
        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '*.foo.com, *.oracle.com', 1);
        $this->assertTrue(Url::isRedirectAllowedForHost("http://www.oracle.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("https://www.oracle.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("//www.oracle.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("http://www.oracle.com/to/some/other/page"));
        $this->assertFalse(Url::isRedirectAllowedForHost("http://someDomain.com"));
        $this->assertFalse(Url::isRedirectAllowedForHost("https://someDomain.com"));
        $this->assertFalse(Url::isRedirectAllowedForHost("//someDomain.com"));
        $this->assertTrue(Url::isRedirectAllowedForHost("someDomain.com"));

        \Rnow::updateConfig('CP_REDIRECT_HOSTS', '', 1);
    }

    //@@@ QA 131030-000035 E2E: ASSETS - CPv3 EU Pages - Asset search for "Anyone in my Organization" is not working
    function testGetProductCatalogFilter() {
        $filters = array();
        $expected = array(
            'pc' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => array(NULL),
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
        );

        Url::getProductCatalogFilter(true, $filters, null);
        $this->assertIdentical($expected, $filters);

        Url::getProductCatalogFilter(false, $filters, null);
        $this->assertIdentical($expected, $filters);

        $this->addUrlParameters(array('pc' => '1'));
        // Product specified in URL and report_id specified
        $filters = array();
        Url::getProductCatalogFilter(true, $filters, 228);
        $expected = array(
            'pc' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 2,
                    'oper_id' => 1,
                    'optlist_id' => 56,
                    'report_id' => 228,
                    'rnSearchType' => 'menufilter',
                    'data' => '1',
                ),
                'type' => 'Product',
                'report_default' => '~any~',
            ),
        );

        $this->assertIdentical($expected, $filters);

        // Product specified in URL but no report_id specified
        $filters = array();
        Url::getProductCatalogFilter(true, $filters, null);
        $expected = array(
            'pc' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => '1',
                ),
                'type' => NULL,
                'report_default' => NULL,
            ),
        );
        $this->assertIdentical($expected, $filters);

        $this->restoreUrlParameters();
    }

    function testDefaultQuestionUrl() {
        $this->assertStringContains(Url::defaultQuestionUrl(1), '/app/social/questions/detail/qid/1');
        $this->assertStringContains(Url::defaultQuestionUrl(1, 10), '/app/social/questions/detail/qid/1/comment/10');

        $this->addUrlParameters(array('kw' => 'phone'));
        $this->assertStringContains(Url::defaultQuestionUrl(1), '/app/social/questions/detail/qid/1/kw/phone');
        $this->restoreUrlParameters();
    }

    function testDefaultAnswerUrl() {
        $expected = '/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/1';

        $this->assertStringContains(Url::defaultAnswerUrl(1), $expected);

        $this->addUrlParameters(array('kw' => 'phone'));
        $this->assertStringContains(Url::defaultAnswerUrl(1), "$expected/kw/phone");
        $this->restoreUrlParameters();
    }

    function testGetRawFormFields(){
        $CI = $this->getMockCI();
        if (!class_exists('\RightNow\Models\MockField')) {
            get_instance()->model('Field');
            Mock::generate('\RightNow\Models\Field');
        }
        $mockFieldModel = new \RightNow\Models\MockField;
        $mockFieldModel->expectOnce('getRawFormFields', array('Socialquestion.Body'));
        $mockFieldModel->returnsByValue('getRawFormFields','test');
        $CI->returnsByValue('model', $mockFieldModel, array('Field'));
        $data = Url::getRawPostFields('Socialquestion.Body', true, $CI);
        $this->assertIdentical($data, 'test');
        $data = Url::getRawPostFields('Socialquestion.Body');
        $this->assertNull($data);
    }
}
