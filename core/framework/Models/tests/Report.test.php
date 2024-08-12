<?php
use RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\UnitTest\Helper,
    RightNow\Internal\Sql\Report as Sql;

Helper::loadTestedFile(__FILE__);
class ReportModelTest extends CPTestCase
{
    public $testingClass = 'RightNow\Models\Report';
    protected $reportModel;
    protected $defaultIncidentReport = 196;
    protected $defaultAssetReport = 228;
    protected $defaultAnswersReport = CP_NOV09_ANSWERS_DEFAULT;
    protected $defaultWidxReport = CP_NOV09_WIDX_DEFAULT;
    static $initialConfigValues = array();

    function __construct()
    {
        parent::__construct();
        $this->reportModel = new RightNow\Models\Report();
        $this->reflectionClass = new ReflectionClass('RightNow\Models\Report');
        $this->reflectionInstance = $this->reflectionClass->newInstance();
    }

    function setUp() {
        // clear out the token processCache
        $reflectionClass = new ReflectionClass('RightNow\Utils\Framework');
        $reflectionProperty = $reflectionClass->getProperty('processCache');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(array());
    }

    function setInstanceProperty($propertyName, $propertyValue){
        Helper::setInstanceProperty($this->reflectionClass, $this->reflectionInstance, $propertyName, $propertyValue);
    }

    function getInstanceProperty($propertyName){
        return Helper::getInstanceProperty($this->reflectionClass, $this->reflectionInstance, $propertyName);
    }

    function callInstanceMethod($methodName){
        $arguments = array_slice(func_get_args(), 1);
        $method = $this->reflectionClass->getMethod($methodName);
        $method->setAccessible(true);
        $params = $method->getParameters();
        for ($i = 0; $i < count($arguments); $i++) {
            if ($params[$i] && $params[$i]->isPassedByReference()) {
                $arguments[$i] = &$arguments[$i];
            }
        }
        return $method->invokeArgs($this->reflectionInstance, $arguments);
    }

    // verify default search types for the two major reports
    function testGetSearchFilterTypeDefault()
    {
        $reportNumber = $this->defaultAnswersReport;
        $expected = array(
                'fltr_id' => 5,
                'name' => 'search_cpx',
                'type' => 1,
                'oper_id' => 1,
                'data_type' => 5,
                'optlist_id' => null,
                'expression1' => 'answers.search_cpx',
                'attributes' => 32785,
                'default_value' => "",
                'prompt' => 'Complex Expression',
                'required' => 0
            );
        $response = $this->reportModel->getSearchFilterTypeDefault($reportNumber);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);
        $reportNumber = $this->defaultIncidentReport;
        $expected = array
            (
                'fltr_id' => 1,
                'name' => 'search_thread',
                'type' => 1,
                'oper_id' => 1,
                'data_type' => 5,
                'optlist_id' => null,
                'expression1' => 'incidents.search_thread',
                'attributes' => 32785,
                'default_value' => "",
                'prompt' => 'Summary/Thread',
                'required' => 0
            );
        $response = $this->reportModel->getSearchFilterTypeDefault($reportNumber);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);
    }

    // verify correct filter data is returned
    function testGetFilterById()
    {
        $reportNumber = $this->defaultAnswersReport;
        $filterID = 2;
        $expected = array('fltr_id' => 2,
                          'name' => 'map_prod_hierarchy',
                          'type' => 1,
                          'oper_id' => 10,
                          'data_type' => 1,
                          'optlist_id' => 9,
                          'expression1' => 'answers.map_prod_hierarchy',
                          'attributes' => 273,
                          'default_value' => '~any~',
                          'prompt' => 'Product Hierarchy',
                          'required' => 0
                          );

        $response = $this->reportModel->getFilterById($reportNumber, 2);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);

        $response = $this->reportModel->getFilterById($reportNumber, 9);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 1);
        $this->assertTrue(count($response->warnings) === 0);
    }


    function testGetFilterByName()
    {
        $reportNumber = $this->defaultAnswersReport;
        $filterName = 'prod';
        $expected = array('fltr_id' => 2,
                          'name' => 'map_prod_hierarchy',
                          'type' => 1,
                          'oper_id' => 10,
                          'data_type' => 1,
                          'optlist_id' => 9,
                          'expression1' => 'answers.map_prod_hierarchy',
                          'attributes' => 273,
                          'default_value' => '~any~',
                          'prompt' => 'Product Hierarchy',
                          'required' => 0
                          );

        $response = $this->reportModel->getFilterByName($reportNumber, 'prod');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);

        $response = $this->reportModel->getFilterByName($reportNumber, 'product');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 1);
        $this->assertTrue(count($response->warnings) === 0);
    }

    function testGetSearchTypeFromValue()
    {
        $reportNumber = $this->defaultAnswersReport;
        $expected = array('fltr_id' => 6,
                          'name' => 'search_nl',
                          'type' => 1,
                          'oper_id' => 1,
                          'data_type' => 5,
                          'optlist_id' => null,
                          'expression1' => 'answers.search_nl',
                          'attributes' => 32785,
                          'default_value' => null,
                          'prompt' => 'Phrases',
                          'required' => 0
                         );
        $response = $this->reportModel->getSearchTypeFromValue($reportNumber, SRCH_TYPE_NL);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);


        $expected = array('fltr_id' => 7,
                          'name' => 'search_fnl',
                          'type' => 1,
                          'oper_id' => 1,
                          'data_type' => 5,
                          'optlist_id' => null,
                          'expression1' => 'answers.search_fnl',
                          'attributes' => 32785,
                          'default_value' => null,
                          'prompt' => 'Similar Phrases',
                          'required' => 0
                         );
        $response = $this->reportModel->getSearchTypeFromValue($reportNumber, SRCH_TYPE_FNL);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);


        $expected = array ('fltr_id' => 8,
                           'name' => 'search_ex',
                           'type' => 1,
                           'oper_id' => 1,
                           'data_type' => 5,
                           'optlist_id' => null,
                           'expression1' => 'answers.search_ex',
                           'attributes' => 32785,
                           'default_value' => null,
                           'prompt' => 'Exact Search',
                           'required' => 0
                          );
        $response = $this->reportModel->getSearchTypeFromValue($reportNumber, SRCH_TYPE_EX);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);


        $expected = array ('fltr_id' => 5,
                           'name' => 'search_cpx',
                           'type' => 1,
                           'oper_id' => 1,
                           'data_type' => 5,
                           'optlist_id' => null,
                           'expression1' => 'answers.search_cpx',
                           'attributes' => 32785,
                           'default_value' => null,
                           'prompt' => 'Complex Expression',
                           'required' => 0
                         );
        $response = $this->reportModel->getSearchTypeFromValue($reportNumber, SRCH_TYPE_CPX);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);


        $response = $this->reportModel->getSearchTypeFromValue($reportNumber, "");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 1);
        $this->assertTrue(count($response->warnings) === 0);


        $response = $this->reportModel->getSearchTypeFromValue($this->defaultIncidentReport, SRCH_TYPE_CPX);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 1);
        $this->assertTrue(count($response->warnings) === 0);
    }

    //todo - need a custom report here with custom int and text runtime filters
    function testGetRuntimeIntTextData()
    {
        $reportNumber = $this->defaultAnswersReport;
        $expected = array(
            2 => array('fltr_id' => 5,
                       'name' => 'search_cpx',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_cpx',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Complex Expression',
                       'required' => 0
                       ),
            3 => array('fltr_id' => 6,
                       'name' => 'search_nl',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_nl',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Phrases',
                       'required' => 0
                       ),
            4 => array('fltr_id' => 7,
                       'name' => 'search_fnl',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_fnl',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Similar Phrases',
                       'required' => 0
                       ),
            5 => array('fltr_id' => 8,
                       'name' => 'search_ex',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_ex',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Exact Search',
                       'required' => 0
                       )
            );

        $response = $this->reportModel->getRuntimeIntTextData($reportNumber);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);
    }

    function testGetSearchFilterData()
    {
        $expected = array(
            0 => array('fltr_id' => 5,
                       'name' => 'search_cpx',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_cpx',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Complex Expression',
                       'required' => 0
                       ),
            1 => array('fltr_id' => 6,
                       'name' => 'search_nl',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_nl',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Phrases',
                       'required' => 0
                       ),
            2 => array('fltr_id' => 7,
                       'name' => 'search_fnl',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_fnl',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Similar Phrases',
                       'required' => 0
                       ),
            3 => array('fltr_id' => 8,
                       'name' => 'search_ex',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_ex',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Exact Search',
                       'required' => 0
                       )
            );
        $response = $this->reportModel->getSearchFilterData($this->defaultAnswersReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);


        $expected = array(
                0 => array(
                        'fltr_id' => 1,
                        'name' => 'search_thread',
                        'type' => 1,
                        'oper_id' => 1,
                        'data_type' => 5,
                        'optlist_id' => null,
                        'expression1' => 'incidents.search_thread',
                        'attributes' => 32785,
                        'default_value' => null,
                        'prompt' => 'Summary/Thread',
                        'required' => 0,
                    ),
                1 => array(
                        'fltr_id' => 2,
                        'name' => 'ref_no',
                        'type' => 1,
                        'oper_id' => 19,
                        'data_type' => 5,
                        'optlist_id' => null,
                        'expression1' => 'incidents.ref_no',
                        'attributes' => 1,
                        'default_value' => null,
                        'prompt' => 'Reference #',
                        'required' => 0
                    )

            );
        $response = $this->reportModel->getSearchFilterData($this->defaultIncidentReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);
    }


    function testGetIndexOfColumnDefinition(){
        $response = $this->reportModel->getIndexOfColumnDefinition($this->defaultAnswersReport, 'answers.summary');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical(array('columnID' => 1, 'index' => 0), $response->result);


        $response = $this->reportModel->getIndexOfColumnDefinition($this->defaultAnswersReport, 'answers.solution');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical(array('columnID' => 3, 'index' => 2), $response->result);


        $response = $this->reportModel->getIndexOfColumnDefinition($this->defaultIncidentReport, 'incidents.subject');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical(array('columnID' => 1, 'index' => 0), $response->result);


        $response = $this->reportModel->getIndexOfColumnDefinition($this->defaultIncidentReport, 'incidents.created');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical(array('columnID' => 4, 'index' => 3), $response->result);
    }

    function testGetExternalDocumentSearchOptions()
    {
        $expected = array(
                        1 => 'Any',
                        2 => 'All',
                        3 => 'Complex'
                    );


        $response = $this->reportModel->getExternalDocumentSearchOptions();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);
    }

    function testGetExternalDocumentSortOptions()
    {
        $expected = array
                    (
                        1 => 'Score',
                        2 => 'Time',
                        3 => 'Title',
                        5 => 'Reverse Time',
                        6 => 'Reverse Title'
                    );
        $response = $this->reportModel->getExternalDocumentSortOptions();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);
    }

    function testGetIncidentAlias()
    {
        $response = $this->reportModel->getIncidentAlias($this->defaultIncidentReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_string($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical("incidents", $response->result);

        \Rnow::updateConfig('WIDX_MODE', 1, true);
        $response = $this->reportModel->getIncidentAlias($this->defaultWidxReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        \Rnow::updateConfig('WIDX_MODE', 0, true);

        $response = $this->reportModel->getIncidentAlias($this->defaultAnswersReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
    }

    function testGetAnswerAlias()
    {
        $response = $this->reportModel->getAnswerAlias($this->defaultAnswersReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical('answers', $response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);

        $response = $this->reportModel->getAnswerAlias($this->defaultIncidentReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);

        \Rnow::updateConfig('WIDX_MODE', 1, true);
        $response = $this->reportModel->getAnswerAlias($this->defaultWidxReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        \Rnow::updateConfig('WIDX_MODE', 0, true);
    }

    function testGetOrganizationAlias()
    {
        $response = $this->reportModel->getOrganizationAlias($this->defaultIncidentReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical('orgs', $response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);

        \Rnow::updateConfig('WIDX_MODE', 1, true);
        $response = $this->reportModel->getOrganizationAlias($this->defaultWidxReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        \Rnow::updateConfig('WIDX_MODE', 0, true);

        $response = $this->reportModel->getOrganizationAlias($this->defaultAnswersReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
    }

    function testGetClusterToAnswersAlias()
    {
        $response = $this->reportModel->getClusterToAnswersAlias(CP_FEB10_CLUSTER_DEFAULT);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical('cluster_tree2answers', $response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);

        $response = $this->reportModel->getClusterToAnswersAlias($this->defaultIncidentReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);

        $response = $this->reportModel->getClusterToAnswersAlias($this->defaultAnswersReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
    }

    function testGetAssetAlias()
    {
        $response = $this->reportModel->getAssetAlias($this->defaultAssetReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_string($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical("assets", $response->result);

        \Rnow::updateConfig('WIDX_MODE', 1, true);
        $response = $this->reportModel->getAssetAlias($this->defaultWidxReport);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        \Rnow::updateConfig('WIDX_MODE', 0, true);
    }

    function testCreateSearchFilter()
    {
        $reportNumber = $this->defaultAnswersReport;
        $name = "my_custom_filter";
        $filterID = "contacts.contact_id";
        $value = 3;
        $operatorID = OPER_LT;
        $rnSearchType = 'myName';
        $expected = (object)array('filters' =>
                    (object)array(
                        'rnSearchType' => 'myName',
                        'searchName' => 'my_custom_filter',
                        'report_id' => 176,
                        'data' => (object)array('val' => 3),
                        'oper_id' => 3,
                        'fltr_id' => 'contacts.contact_id'
                        )
                    );

        $method = $this->getMethod('createSearchFilter');
        $response = $method($reportNumber, $name, $filterID, $value, $rnSearchType, $operatorID);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
        $this->assertIdentical($expected, $response->result);
    }

    function testGetRuntimeFilters()
    {
        $expected = array(
            0 => array('fltr_id' => 2,
                        'name' => 'map_prod_hierarchy',
                        'type' => 1,
                        'oper_id' => 10,
                        'data_type' => 1,
                        'optlist_id' => 9,
                        'expression1' => 'answers.map_prod_hierarchy',
                        'attributes' => 273,
                        'default_value' => '~any~',
                        'prompt' => 'Product Hierarchy',
                        'required' => 0
                        ),
            1 => array('fltr_id' => 3,
                        'name' => 'map_cat_hierarchy',
                        'type' => 1,
                        'oper_id' => 10,
                        'data_type' => 1,
                        'optlist_id' => 12,
                        'expression1' => 'answers.map_cat_hierarchy',
                        'attributes' => 273,
                        'default_value' => '~any~',
                        'prompt' => 'Category Hierarchy',
                        'required' => 0
                        ),
            2 => array('fltr_id' => 5,
                       'name' => 'search_cpx',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_cpx',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Complex Expression',
                       'required' => 0
                       ),
            3 => array('fltr_id' => 6,
                       'name' => 'search_nl',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_nl',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Phrases',
                       'required' => 0
                       ),
            4 => array('fltr_id' => 7,
                       'name' => 'search_fnl',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_fnl',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Similar Phrases',
                       'required' => 0
                       ),
            5 => array('fltr_id' => 8,
                       'name' => 'search_ex',
                       'type' => 1,
                       'oper_id' => 1,
                       'data_type' => 5,
                       'optlist_id' => null,
                       'expression1' => 'answers.search_ex',
                       'attributes' => 32785,
                       'default_value' => null,
                       'prompt' => 'Exact Search',
                       'required' => 0
                       )
            );

            $method = $this->getMethod('getRuntimeFilters');
            $response = $method($this->defaultAnswersReport);
            $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
            $this->assertTrue(is_array($response->result));
            $this->assertTrue(count($response->errors) === 0);
            $this->assertTrue(count($response->warnings) === 0);
            $this->assertIdentical($expected, $response->result);
    }

    function testGetSearchTerm(){
        $response = $this->reportModel->getSearchTerm(176, null, null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);

        $reportToken = Framework::createToken(176);
        $response = $this->reportModel->getSearchTerm(176, $reportToken, null);
        $this->assertNull($response->result);

        $reportToken = Framework::createToken(194);
        $response = $this->reportModel->getSearchTerm(194, $reportToken, null);
        $this->assertNull($response->result);
    }

    function testGetReportHeaders(){
        $response = $this->reportModel->getReportHeaders(176, 'garbage', null, null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array(), $response->result);

        $reportToken = Framework::createToken(176);
        $expected = array(
          array('heading' => 'Summary', 'width' => '15.77608', 'data_type' => 5, 'col_id' => 1, 'order' => 0, 'col_definition' => 'answers.summary', 'visible' => true, 'url_info' => '/app/answers/detail/a_id/&lt;5&gt;'),
          array('heading' => 'New or Updated', 'width' => '14.5038166', 'data_type' => 5, 'col_id' => 2, 'order' => 1, 'col_definition' => 'if (date_diff(date_trunc(sysdate(), DAYS), date_trunc(answers.created, DAYS)) / 86400 < $new, msg_lookup(5064), if(date_diff(date_trunc(sysdate(), DAYS), date_trunc(answers.updated, DAYS)) / 86400 < $updated, msg_lookup(6861)))', 'visible' => true),
          array('heading' => 'Description', 'width' => '12.9770994', 'data_type' => 6, 'col_id' => 3, 'order' => 2, 'col_definition' => 'answers.solution', 'visible' => true),
          array('heading' => 'Date Updated', 'width' => '27.7353687', 'data_type' => 4, 'col_id' => 4, 'order' => 3, 'col_definition' => 'answers.updated', 'visible' => true),
        );
        $response = $this->reportModel->getReportHeaders(176, $reportToken, null, null);

        $this->assertIdentical($expected, $response->result);

        $reportToken = Framework::createToken(194);
        $expected = array(
          array('heading' => 'Summary', 'width' => '15.77608', 'data_type' => 5, 'col_id' => 1, 'order' => 0, 'col_definition' => 'answers.summary', 'visible' => true, 'url_info' => '/app/answers/detail/a_id/&lt;2&gt;'),
        );
        $response = $this->reportModel->getReportHeaders(194, $reportToken, null, null);
        $this->assertIdentical($expected, $response->result);
    }

    function testGetDataHtml(){
        $nullValue = null;
        $response = $this->reportModel->getDataHTML(194, Framework::createToken(194), $nullValue, null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_array($response->result['data']));
        $this->assertTrue(is_array($response->result['headers']));
        $this->assertTrue(is_int($response->result['per_page']));
        $this->assertTrue(is_float($response->result['total_pages']));
        $this->assertTrue(is_int($response->result['total_num']));
        $this->assertTrue(is_int($response->result['row_num']));
        $this->assertTrue(is_int($response->result['truncated']));
        $this->assertTrue(is_int($response->result['start_num']));
        $this->assertTrue(is_int($response->result['end_num']));
        $this->assertTrue(is_int($response->result['initial']));
        $this->assertTrue(is_int($response->result['search_type']));
        $this->assertTrue(is_int($response->result['search']));
        $this->assertIdentical(194, $response->result['report_id']);
        $this->assertNull($response->result['search_term']);
        $this->assertTrue(is_int($response->result['grouped']));
        $this->assertTrue(is_array($response->result['exceptions']));
        $this->assertIdentical(1, $response->result['page']);
        $this->assertNull($response->result['error']);
        $this->assertTrue(is_string($response->result['spelling']));
        $this->assertTrue(is_string($response->result['not_dict']));
        $this->assertNull($response->result['ss_data']);
        $this->assertTrue(is_array($response->result['topic_words']));
        $this->assertTrue(is_array($response->result['related_cats']));
        $this->assertTrue(is_array($response->result['related_prods']));

        $filters = array('page' => 2, 'per_page' => 2);
        $response = $this->reportModel->getDataHTML(194, Framework::createToken(194), $filters, null);
        $this->assertIdentical(2, $response->result['page']);
        $this->assertIdentical(2, $response->result['per_page']);

        $filters = array (
          'keyword' => (object)array(
             'filters' => (object)array(
               'rnSearchType' => 'keyword',
               'data' => 'roam',
               'report_id' => 176,
            )),
             'type' => 'keyword',
        );
        $response = $this->reportModel->getDataHTML(176, Framework::createToken(176), $filters, null);
        $this->assertIdentical('roam', $response->result['search_term']);
    }

    function testGetDataHTMLUsingSubReports () {
        list(
            $class,
            $appliedFilters
            ) = $this->reflect('appliedFilters');
        $filters = array(
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'col_id' => 8,
                    'sort_direction' => 1,
                    'sort_order' => 1,
                )),
            'page' => 1,
            'per_page' => 10
        );

        $response = $this->reportModel->getDataHTML(15100, Framework::createToken(15100), $filters, null);
        $mainReportFilters = $appliedFilters->getValue($this->reportModel);
        $this->assertNotNull($mainReportFilters["question_id"]);
        $this->assertEqual($filters["sort_args"]->filters->col_id, 8);

        list(
            $class,
            $appliedFilters
            ) = $this->reflect('appliedFilters');
        $filters = array(
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'col_id' => 3,
                    'sort_direction' => 1,
                    'sort_order' => 1,
                )),
            'questions.updated' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => 'last_24_hours',
                    'oper_id' => 6,
                )),
            'page' => 1,
            'per_page' => 10
        );

        $response = $this->reportModel->getDataHTML(15100, Framework::createToken(15100), $filters, null);
        $mainReportFilters = $appliedFilters->getValue($this->reportModel);
        $this->assertNull($mainReportFilters["question_id"]);
        $this->assertEqual($filters["sort_args"]->filters->col_id, 3);
        $this->assertNotEqual('last_24_hours', $mainReportFilters["questions.updated"]['filters']['data']);

        $filters = array(
            'questions.updated' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => 'last_24_hours',
                    'oper_id' => 9,
                )),
            'page' => 1,
            'per_page' => 10
        );
        $response = $this->reportModel->getDataHTML(15100, Framework::createToken(15100), $filters, null);
        $mainReportFilters = $appliedFilters->getValue($this->reportModel);
        $this->assertNotEqual('last_24_hours', $mainReportFilters["questions.updated"]['filters']['data']);
        $this->assertNotNull($mainReportFilters["question_id"]);
        $this->assertEqual($mainReportFilters["sort_args"]['filters']['col_id'], 8);

        $filters = array(
            'questions.status' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'questions.status',
                    'fltr_id' => 1,
                    'data' => 999999,
                    'oper_id' => 10,
                    'report_id' => 15100,
                ),
            ),
            'page' => 1,
            'per_page' => 10
        );
        $response = $this->reportModel->getDataHTML(15100, Framework::createToken(15100), $filters, null);
        $mainReportFilters = $appliedFilters->getValue($this->reportModel);
        $this->assertNotNull($mainReportFilters["question_id"]);
        $this->assertEqual($mainReportFilters["question_id"]->filters->data, -1);
        $this->assertEqual($mainReportFilters["sort_args"]['filters']['col_id'], 8);

        $filters = array(
            'questions.updated' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => '01/01/2013|01/04/2013',
                    'oper_id' => 9,
                )),
            'page' => 1,
            'per_page' => 10
        );
        $response = $this->reportModel->getDataHTML(15100, Framework::createToken(15100), $filters, null);
        $mainReportFilters = $appliedFilters->getValue($this->reportModel);
        $this->assertEqual(1, preg_match("/^[0-9]+\|[0-9]+$/", $mainReportFilters["questions.updated"]['filters']['data']));
        $this->assertNotNull($mainReportFilters["question_id"]);
        $this->assertEqual($mainReportFilters["sort_args"]['filters']['col_id'], 8);

        $filters = array(
            'questions.updated' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => 'invalid|invalid',
                    'oper_id' => 9,
                )),
            'page' => 1,
            'per_page' => 10
        );
        $response = $this->reportModel->getDataHTML(15100, Framework::createToken(15100), $filters, null);
        $mainReportFilters = $appliedFilters->getValue($this->reportModel);
        $this->assertEqual(null, $mainReportFilters["questions.updated"]['filters']['data']);
        $this->assertNotNull($mainReportFilters["question_id"]);
        $this->assertEqual($mainReportFilters["sort_args"]['filters']['col_id'], 8);
    }

    function testGetDataHtmlNoCache() {
        $nullValue = null;
        $response = $this->reportModel->getDataHTML(194, Framework::createToken(194), $nullValue, null, true, true);

        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertTrue(count($response->result) > 0);
        $this->assertTrue(is_array($response->result['data']));
    }

    function testGetSortArgsColumn () {
        list(
            $class,
            $getSortArgsColumn
            ) = $this->reflect('method:getSortArgsColumn');
        $filters = array(
            'sort_args' => array(
                'filters' => array(
                    'col_id' => 7,
                    'sort_direction' => 1,
                    'sort_order' => 1,
                )),
            'page' => 1,
            'per_page' => 10
        );
        $this->assertEqual(7, $getSortArgsColumn->invoke($this->reportModel, $filters));
        $filters = array(
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'data' => (object) array('col_id' => 7,
                        'sort_direction' => 1,
                        'sort_order' => 1,
                    ),
                )),
            'page' => 1,
            'per_page' => 10
        );
        $this->assertEqual(7, $getSortArgsColumn->invoke($this->reportModel, $filters));
    }

    function testSetSortArgsColumn() {
        list(
                $class,
                $getSortArgsColumn
                ) = $this->reflect('method:getSortArgsColumn');
        list(
                $class,
                $setSortArgsColumn
                ) = $this->reflect('method:setSortArgsColumn');
        $filters = array(
            'sort_args' => array(
                'filters' => array(
                    'col_id' => 7,
                    'sort_direction' => 1,
                    'sort_order' => 1,
                )),
            'page' => 1,
            'per_page' => 10
        );
        $filters = $setSortArgsColumn->invoke($this->reportModel, $filters, 6, 1, 2);
        $this->assertEqual(2, $filters['sort_args']['filters']['sort_direction']);
        $this->assertEqual(1, $filters['sort_args']['filters']['sort_order']);
        $this->assertEqual(6, $getSortArgsColumn->invoke($this->reportModel, $filters));
        $filters = array(
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'data' => (object) array('col_id' => 7,
                        'sort_direction' => 1,
                        'sort_order' => 1,
                    ),
                )),
            'page' => 1,
            'per_page' => 10
        );
        $filters = $setSortArgsColumn->invoke($this->reportModel, $filters, 6, 1, 2);
        $this->assertEqual(2, $filters['sort_args']->filters->data->sort_direction);
        $this->assertEqual(1, $filters['sort_args']->filters->data->sort_order);
        $this->assertEqual(6, $getSortArgsColumn->invoke($this->reportModel, $filters));
    }

    function testGetFilterValue () {
        list(
            $class,
            $getFilterValue
            ) = $this->reflect('method:getFilterValue');
        $filters = array(
            'questions.updated' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => 1,
                    'oper_id' => 6,
                ))
        );
        $this->assertEqual(1, $getFilterValue->invoke($this->reportModel, $filters, "questions.updated"));
        $filters = array(
            'questions.updated' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => 1,
                    'oper_id' => 6,
                )),
        );
        $this->assertEqual(1, $getFilterValue->invoke($this->reportModel, $filters, "questions.updated"));
        $filters = array(
            'questions.updated' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                ))
        );
        $this->assertEqual(1, $getFilterValue->invoke($this->reportModel, $filters, "questions.updated"));
    }

    function testSetFilterValue () {
        list(
            $class,
            $setFilterValue
            ) = $this->reflect('method:setFilterValue');
        list(
            $class,
            $getFilterValue
            ) = $this->reflect('method:getFilterValue');

        $filters = array(
            'questions.updated' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => 1,
                    'oper_id' => 6,
                ))
        );
        $this->assertEqual(4, $getFilterValue->invoke($this->reportModel, $setFilterValue->invoke($this->reportModel, $filters, "questions.updated", 4), "questions.updated"));
        $filters = array(
            'questions.updated' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => 1,
                    'oper_id' => 6,
                ))
        );
        $this->assertEqual(4, $getFilterValue->invoke($this->reportModel, $setFilterValue->invoke($this->reportModel, $filters, "questions.updated", 4), "questions.updated"));
        $filters = array(
            'questions.updated' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => '',
                    'oper_id' => 6,
                ))
        );
        $this->assertEqual('', $getFilterValue->invoke($this->reportModel, $setFilterValue->invoke($this->reportModel, $filters, "questions.updated", ''), "questions.updated"));


        $filters = array(
            'questions.updated' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                ))
        );
        $this->assertEqual(4, $getFilterValue->invoke($this->reportModel, $setFilterValue->invoke($this->reportModel, $filters, "questions.updated", 4), "questions.updated"));
    }

    function testIsFilter(){
        $filters = array(
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
            'NotAFilter' => "NotAFilter",
            'AnotherNotAFilter' => array(1, 2, 3),
        );
        list(
                $class,
                $isFilter
                ) = $this->reflect('method:isFilter');

        $this->assertTrue($isFilter->invoke($this->reportModel, $filters, "p"));
        $this->assertFalse($isFilter->invoke($this->reportModel, $filters, "NotAFilter"));
        $this->assertFalse($isFilter->invoke($this->reportModel, $filters, "AnotherNotAFilter"));
    }

    function testRemoveFilters(){
        $filters = array(
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
            'excludeThisFilter' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
            'NotAFilter' => "NotAFilter",
            'AnotherNotAFilter' => array(1, 2, 3),
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
        );
        list(
                $class,
                $removeFilters
                ) = $this->reflect('method:removeFilters');

        $filters = $removeFilters->invoke($this->reportModel, $filters, array("excludeThisFilter"));
        $this->assertEqual(4, count($filters));
        $this->assertNotNull($filters["excludeThisFilter"]);
        $this->assertNull($filters["p"]);
        $this->assertEqual("NotAFilter", $filters["NotAFilter"]);
        $this->assertEqual(3, count($filters["AnotherNotAFilter"]));
    }

    function testSetSubReportFilterID() {
        list(
                $class,
                $setSubReportFilters
                ) = $this->reflect('method:setSubReportFilters');
        $filters = array(
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
            'c' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
            'AMainReportFilterWithCollidingFilterIdOfSubReport' => array(
                'filters' => array(
                    'fltr_id' => 4,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
            'AnotherMainReportFilterWithCollidingFilterIdOfSubReport' => array(
                'filters' => array(
                    'fltr_id' => 5,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
        );
        list(
                $class,
                $getSubReportMapping
                ) = $this->reflect('method:getSubReportMapping');

        $subReportMap = $getSubReportMapping->invoke($this->reportModel);
        $newFilters = $setSubReportFilters->invoke($this->reportModel, $filters, 15142, $subReportMap[15100]);
        $this->assertEqual(4, $newFilters["p"]->filters->fltr_id);
        $this->assertEqual(3, $filters["p"]->filters->fltr_id);
        $this->assertEqual(4, count($filters));
        $this->assertEqual(1, count($newFilters));
        $newFilters = $setSubReportFilters->invoke($this->reportModel, $filters, 15143, $subReportMap[15100]);
        $this->assertEqual(4, count($filters));
        $this->assertEqual(1, count($newFilters));
        $this->assertEqual(4, $newFilters["c"]["filters"]["fltr_id"]);
        $this->assertEqual(3, $filters["c"]["filters"]["fltr_id"]);
    }

    function testGetSubReportKeyAndFilters() {
        list(
                $class,
                $getSubReportKeyAndFilters
                ) = $this->reflect('method:getSubReportKeyAndFilters');
        $filters = array(
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'data' => (object) array('col_id' => 8,
                        'sort_direction' => 1,
                        'sort_order' => 1,
                    ),
                )),
            'question_content_flags.flag' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                ))
        );
        list(
                $class,
                $getSubReportMapping
                ) = $this->reflect('method:getSubReportMapping');

        $subReportMap = $getSubReportMapping->invoke($this->reportModel);
        $subReportKeyAndFilters = $getSubReportKeyAndFilters->invoke($this->reportModel, 15100, $subReportMap[15100], $filters);
        $this->assertEqual("question_content_flags.flag8", $subReportKeyAndFilters["SubReportKey"]);

        $filters = array(
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'data' => (object) array('col_id' => 8,
                        'sort_direction' => 1,
                        'sort_order' => 1,
                    ),
                )),
            'question_content_flags.flag' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                )),
            'p' => array(
                'filters' => array(
                    'fltr_id' => 3,
                    'data' => array(1),
                    'oper_id' => 6,
                ))
        );

        $subReportKeyAndFilters = $getSubReportKeyAndFilters->invoke($this->reportModel, 15100, $subReportMap[15100], $filters);
        $this->assertEqual("pquestion_content_flags.flag8", $subReportKeyAndFilters["SubReportKey"]);

        $filters = array(
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'data' => (object) array('col_id' => 8,
                        'sort_direction' => 1,
                        'sort_order' => 1,
                    ),
                ))
        );
        $subReportKeyAndFilters = $getSubReportKeyAndFilters->invoke($this->reportModel, 15100, $subReportMap[15100], $filters);
        $this->assertEqual("8", $subReportKeyAndFilters["SubReportKey"]);
    }

    function testGetTopicWords(){
        $response = $this->reportModel->getTopicWords();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(1, count($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);

        $response = $this->reportModel->getTopicWords('iphone');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(3, count($response->result));
        $this->assertTrue(count($response->errors) === 0);
        $this->assertTrue(count($response->warnings) === 0);
    }

    function testGetKBStrings(){
        $getKBStrings = $this->getMethod('getKBStrings');

        $this->assertNull($getKBStrings());

        $existingReturnData = $this->getInstanceProperty('returnData');

        $this->setInstanceProperty('returnData', array());
        $this->callInstanceMethod('getKBStrings');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertIdentical('', $returnData['spelling']);
        $this->assertIdentical('', $returnData['not_dict']);
        $this->assertIdentical(null, $returnData['ss_data']);
        $this->assertTrue(is_array($returnData['topic_words']));
        $this->assertTrue(count($returnData['topic_words']) > 0);

        $this->setInstanceProperty('returnData', array('search_term' => 'phon'));
        $this->callInstanceMethod('getKBStrings');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertIdentical('phone', $returnData['spelling']);
        $this->assertIdentical(null, $returnData['not_dict']);
        $this->assertIdentical(null, $returnData['stopword']);
        $this->assertIdentical(null, $returnData['ss_data']);
        $this->assertTrue(is_array($returnData['topic_words']));
        $this->assertTrue(count($returnData['topic_words']) > 0);

        $this->setInstanceProperty('returnData', array('search_term' => 'mobile and phon or roa'));
        $this->callInstanceMethod('getKBStrings');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertIdentical('mobile phone roam', $returnData['spelling']);
        $this->assertIdentical(null, $returnData['not_dict']);
        $this->assertIdentical('and, or', $returnData['stopword']);
        $this->assertIdentical(null, $returnData['ss_data']);
        $this->assertTrue(is_array($returnData['topic_words']));
        $this->assertTrue(count($returnData['topic_words']) > 0);

        $this->setInstanceProperty('returnData', array('search_term' => 'iPhone and asdf'));
        $this->callInstanceMethod('getKBStrings');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertIdentical('', $returnData['spelling']);
        $this->assertIdentical('asdf', $returnData['not_dict']);
        $this->assertIdentical('and', $returnData['stopword']);
        $this->assertIdentical(null, $returnData['ss_data']);
        $this->assertTrue(is_array($returnData['topic_words']));
        $this->assertTrue(count($returnData['topic_words']) > 0);

        $this->setInstanceProperty('returnData', array('search_term' => 'Android and <i>phon</i>'));
        $this->callInstanceMethod('getKBStrings');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertIdentical('android phone', $returnData['spelling']);
        $this->assertIdentical(null, $returnData['not_dict']);
        $this->assertIdentical('and', $returnData['stopword']);
        $this->assertIdentical(null, $returnData['ss_data']);
        $this->assertTrue(is_array($returnData['topic_words']));
        $this->assertTrue(count($returnData['topic_words']) > 0);

        $this->setInstanceProperty('returnData', $existingReturnData);
    }

    function testIsReportWidx(){
        $isReportWidx = $this->getMethod('isReportWidx');

        $this->assertFalse($isReportWidx(null));
        $this->assertFalse($isReportWidx(false));
        $this->assertFalse($isReportWidx(array()));
        $this->assertFalse($isReportWidx('widx'));
        $this->assertFalse($isReportWidx(1));
        $this->assertFalse($isReportWidx(176));
        $this->assertFalse($isReportWidx(CP_WIDX_REPORT_DEFAULT));
        $this->assertFalse($isReportWidx(CP_NOV09_WIDX_DEFAULT));

        \Rnow::updateConfig('WIDX_MODE', 1, true);

        $this->assertFalse($isReportWidx(1));
        $this->assertFalse($isReportWidx(176));
        $this->assertTrue($isReportWidx(CP_WIDX_REPORT_DEFAULT));
        $this->assertTrue($isReportWidx(CP_NOV09_WIDX_DEFAULT));

        \Rnow::updateConfig('WIDX_MODE', 0, true);
    }

    function testIsAnswerListReportWithoutSpecialSettingsFilter(){
        $isAnswerListReportWithoutSpecialSettingsFilter = $this->getMethod('isAnswerListReportWithoutSpecialSettingsFilter');

        $this->assertTrue($isAnswerListReportWithoutSpecialSettingsFilter(1));
        $this->assertFalse($isAnswerListReportWithoutSpecialSettingsFilter(176));
        $this->assertFalse($isAnswerListReportWithoutSpecialSettingsFilter(196));
        $this->assertFalse($isAnswerListReportWithoutSpecialSettingsFilter(194));
    }

    function testDoesReportIncludeAnswerTable(){
        $doesReportIncludeAnswerTable = $this->getMethod('doesReportIncludeAnswerTable');

        $report = _report_get(1);

        $this->assertTrue($doesReportIncludeAnswerTable(_report_get(1)));
        $this->assertTrue($doesReportIncludeAnswerTable(_report_get(176)));
        $this->assertTrue($doesReportIncludeAnswerTable(_report_get(194)));
        $this->assertFalse($doesReportIncludeAnswerTable(_report_get(196)));

        $this->assertFalse($doesReportIncludeAnswerTable(array('tables' => 1)));
        $this->assertFalse($doesReportIncludeAnswerTable(array('tables' => false)));
        $this->assertFalse($doesReportIncludeAnswerTable(array('tables' => 'answers')));
        $this->assertFalse($doesReportIncludeAnswerTable(array('tables' => null)));
        $this->assertFalse($doesReportIncludeAnswerTable(array('tables' => array())));
        $this->assertFalse($doesReportIncludeAnswerTable(array('tables' => array('answers', 'incidents'))));
        $this->assertFalse($doesReportIncludeAnswerTable(array('tables' => array(array('answer' => TBL_ANSWERS)))));
        $this->assertFalse($doesReportIncludeAnswerTable(array('tables' => array(array('tbl' => VTBL_INCIDENTS), array('tbl' => 0)))));

        $this->assertTrue($doesReportIncludeAnswerTable(array('tables' => array(array('tbl' => TBL_ANSWERS)))));
        $this->assertTrue($doesReportIncludeAnswerTable(array('tables' => array(array('tbl' => VTBL_INCIDENTS), array('tbl' => TBL_ANSWERS)))));
    }

    function testDoesReportIncludeAnswerSpecialSettingsFilter(){
        $doesReportIncludeAnswerSpecialSettingsFilter = $this->getMethod('doesReportIncludeAnswerSpecialSettingsFilter');

        $this->assertTrue($doesReportIncludeAnswerSpecialSettingsFilter(_report_get(176)));
        $this->assertTrue($doesReportIncludeAnswerSpecialSettingsFilter(_report_get(194)));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(_report_get(1)));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(_report_get(196)));

        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => 1)));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => false)));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => 'answers')));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => null)));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => array())));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => array('answers', 'incidents'))));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => array(array('answer' => TBL_ANSWERS)))));
        $this->assertFalse($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => array(array('val1' => VTBL_INCIDENTS), array('val1' => 0)))));

        $this->assertTrue($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => array(array('val1' => 'answers.special_settings')))));
        $this->assertTrue($doesReportIncludeAnswerSpecialSettingsFilter(array('filters' => array(array('val1' => VTBL_INCIDENTS), array('val1' => 'answers.special_settings')))));
    }

    function testPreProcessData(){
        $preProcessData = $this->getMethod('preProcessData');

        $this->assertFalse($preProcessData(176, 'asdf', null, null));
        $reportToken = Framework::createToken(194);
        $this->assertFalse($preProcessData(176, $reportToken, null, null));
        $reportToken = Framework::createToken(176);
        $this->assertTrue($preProcessData(176, $reportToken, null, null));

        $existingReturnData = $this->getInstanceProperty('returnData');

        $this->setInstanceProperty('returnData', array());
        $reportToken = Framework::createToken(CP_WIDX_REPORT_DEFAULT);
        $preProcessResult = $this->callInstanceMethod('preProcessData', CP_WIDX_REPORT_DEFAULT, $reportToken, null, null);
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertFalse($preProcessResult);
        $this->assertTrue(is_string($returnData['error']));
        $this->assertIdentical(CP_WIDX_REPORT_DEFAULT, $returnData['report_id']);

        $this->setInstanceProperty('returnData', $existingReturnData);
    }

    function testGetOtherKnowledgeBaseData(){
        $existingReturnData = $this->getInstanceProperty('returnData');

        $this->setInstanceProperty('returnData', array());
        $this->callInstanceMethod('getOtherKnowledgeBaseData');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_array($returnData['related_prods']));
        $this->assertTrue(is_array($returnData['related_cats']));
        $this->assertTrue(is_string($returnData['not_dict']));

        $this->setInstanceProperty('returnData', array('search_term' => 'mobile phones'));
        $this->callInstanceMethod('getOtherKnowledgeBaseData');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_array($returnData['related_prods']));
        $this->assertTrue(is_array($returnData['related_cats']));
        $this->assertTrue(count($returnData['related_prods']) === 0);
        $this->assertTrue(count($returnData['related_cats']) === 0);
        $this->assertNull($returnData['not_dict']);
    }

    function testCheckTokenError(){
        $checkTokenError = $this->getMethod('checkTokenError');

        $this->assertTrue($checkTokenError(null));
        $this->assertTrue($checkTokenError(1));
        $this->assertTrue($checkTokenError(false));
        $this->assertTrue($checkTokenError('token'));
        $this->assertTrue($checkTokenError(Framework::createToken(194)));

        $existingReportID = $this->getInstanceProperty('reportID');

        $this->setInstanceProperty('reportID', 176);
        $this->assertTrue($this->callInstanceMethod('checkTokenError', Framework::createToken(194)));
        $this->assertFalse($this->callInstanceMethod('checkTokenError', Framework::createToken(176)));

        $this->setInstanceProperty('reportID', $existingReportID);
    }

    function testCheckInterfaceError(){
        $checkInterfaceError = $this->getMethod('checkInterfaceError');

        $this->assertTrue($checkInterfaceError());

        $existingReportID = $this->getInstanceProperty('reportID');

        $this->setInstanceProperty('reportID', 176);
        $this->assertFalse($this->callInstanceMethod('checkInterfaceError'));
        $this->setInstanceProperty('reportID', CP_WIDX_REPORT_DEFAULT);
        $this->assertFalse($this->callInstanceMethod('checkInterfaceError'));
        $this->setInstanceProperty('reportID', null);
        $this->assertTrue($this->callInstanceMethod('checkInterfaceError'));
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_string($returnData['error']));

        $this->setInstanceProperty('reportID', $existingReportID);
    }

    function testSetDefaultReportResult(){
        $existingReturnData = $this->getInstanceProperty('returnData');

        $this->callInstanceMethod('setDefaultReportResult');
        $returnData = $this->getInstanceProperty('returnData');

        $this->assertIdentical(array(
            'data' => array(),
            'headers' => array(),
            'per_page' => 0,
            'total_pages' => 0,
            'total_num' => 0,
            'row_num' => 1,
            'truncated' => 0,
            'start_num' => 0,
            'end_num' => 0,
            'initial' => 0,
            'search_type' => 0,
            'search' => 0
        ), $returnData);

        $this->setInstanceProperty('returnData', $existingReturnData);
    }

    function testCheckValidPageNumberRequest(){
        $existingReturnData = $this->getInstanceProperty('returnData');
        $existingAppliedFilters = $this->getInstanceProperty('appliedFilters');

        $this->setInstanceProperty('returnData', array());
        $this->setInstanceProperty('appliedFilters', null);
        $resultingData = array('total_pages' => 0,
                               'data' => array(),
                               'total_num' => 0,
                               'start_num' => 0,
                               'end_num' => 0,
                               'page' => 1);

        $this->callInstanceMethod('checkValidPageNumberRequest');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_array($returnData));
        $this->assertTrue(count($returnData) === 0);

        $this->setInstanceProperty('returnData', array('total_pages' => 5));
        $this->callInstanceMethod('checkValidPageNumberRequest');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_array($returnData));
        $this->assertTrue(count($returnData) === 1);

        $this->setInstanceProperty('returnData', array('total_pages' => 5));
        $this->setInstanceProperty('appliedFilters', array('page' => 2));
        $this->callInstanceMethod('checkValidPageNumberRequest');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_array($returnData));
        $this->assertTrue(count($returnData) === 1);

        $this->setInstanceProperty('returnData', array('total_pages' => 5));
        $this->setInstanceProperty('appliedFilters', array('page' => -1));
        $this->callInstanceMethod('checkValidPageNumberRequest');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_array($returnData));
        $this->assertIdentical($resultingData, $returnData);

        $this->setInstanceProperty('returnData', array('total_pages' => 5));
        $this->setInstanceProperty('appliedFilters', array('page' => 0));
        $this->callInstanceMethod('checkValidPageNumberRequest');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_array($returnData));
        $this->assertIdentical($resultingData, $returnData);

        $this->setInstanceProperty('returnData', array('total_pages' => 5));
        $this->setInstanceProperty('appliedFilters', array('page' => 6));
        $this->callInstanceMethod('checkValidPageNumberRequest');
        $returnData = $this->getInstanceProperty('returnData');
        $this->assertTrue(is_array($returnData));
        $this->assertIdentical($resultingData, $returnData);

        $this->setInstanceProperty('returnData', $existingReturnData);
        $this->setInstanceProperty('appliedFilters', $existingAppliedFilters);
    }

    function testReplaceColumnLinks()
    {
        $method = $this->getMethod('replaceColumnLinks');
        $this->assertIdentical("abc123def", $method('abc&lt;1&gt;def', array('123')));
        $this->assertIdentical("abc&lt;def", $method('abc&lt;1&gt;def', array('&lt;')));
        $this->assertIdentical("abc&gt;def", $method('abc&lt;1&gt;def', array('&gt;')));
        $this->assertIdentical("&lt;abc&gt;", $method('&lt;1&gt;abc&lt;2&gt;', array('&lt;', '&gt;')));
        $this->assertIdentical("&lt;abc&gt;", $method('&lt;11&gt;abc&lt;12&gt;', array('10' => '&lt;', '11' => '&gt;')));
        $this->assertIdentical("&lt;abc&gt;", $method('&lt;101&gt;abc&lt;2&gt;', array('100' => '&lt;', '1' => '&gt;')));
        $this->assertIdentical("&lt;abc&gt;", $method('&lt;1&gt;abc&lt;102&gt;', array('0' => '&lt;', '101' => '&gt;')));
        $this->assertIdentical("-abc|", $method('&lt;11&gt;abc&lt;12&gt;', array('10' => '-', '11' => '|')));
        $this->assertIdentical("abc123defparm", $method('abc&lt;1&gt;def', array('123'), 'parm'));
        $this->assertIdentical("abcparm", $method('abc', array('123'), 'parm'));
        $this->assertIdentical("a/parm1/parm2", $method('a', array(), '/parm1/parm2'));
        $this->assertIdentical("a?parm1=parm2", $method('a', array(), '?parm1=parm2'));
        $this->assertIdentical("a?parm1=parm2&amp;parm3=parm4/kw/iphone+droid", $method('a', array(), '?parm1=parm2&parm3=parm4/kw/iphone+droid'));
        $this->assertIdentical("a?parm1=parm2&amp;parm3=parm4/kw/本人", $method('a', array(), '?parm1=parm2&parm3=parm4/kw/本人'));
        $this->assertIdentical("a/kw/iphone%20droid", $method('a', array(), '/kw/iphone%20droid'));
        $this->assertIdentical("a", $method('a', array(), ' alert(\'"<>();'));
        $this->assertIdentical("a", $method('a', array(), "\nalert"));
        $this->assertIdentical("aalert(&#039;&quot;&lt;&gt;();", $method('a', array(), 'alert(\'"<>();'));
        $this->assertIdentical("a?parm1=combine%20parm2&amp;amp;parm3", $method('a', array(), '?parm1=combine%20parm2&amp;parm3'));
    }

    function testDidYouMean(){
        $didYouMean = $this->getMethod('didYouMean');

        $this->assertTrue(is_array($didYouMean()));
        $this->assertTrue(count($didYouMean()) === 0);

        $existingReturnData = $this->getInstanceProperty('returnData');

        $this->setInstanceProperty('returnData', array('search_term' => 'phon'));
        $result = $this->callInstanceMethod('didYouMean');
        $this->assertTrue(is_array($result));
        $this->assertIdentical('<i>phone</i>', $result['dym']);
        $this->assertIdentical('', $result['aliases']);

        $this->setInstanceProperty('returnData', array('search_term' => 'androi and tex'));
        $result = $this->callInstanceMethod('didYouMean');
        $this->assertTrue(is_array($result));
        $this->assertIdentical('<i>android</i> <i>text</i>', $result['dym']);
        $this->assertIdentical('', $result['aliases']);
        $this->assertIdentical('and', $result['stopword']);

        $this->setInstanceProperty('returnData', array('search_term' => 'asdf'));
        $result = $this->callInstanceMethod('didYouMean');
        $this->assertTrue(is_array($result));
        $this->assertIdentical('asdf', $result['nodict']);

        $this->setInstanceProperty('returnData', $existingReturnData);
    }

    function getHighlightingFromAnswerID(){
        $getHighlightingFromAnswerID = $this->getMethod('getHighlightingFromAnswerID');

        $this->assertIdentical('', $getHighlightingFromAnswerID(0));
        $this->assertIdentical('', $getHighlightingFromAnswerID(999));

        $this->assertTrue(is_string($getHighlightingFromAnswerID(1)));
        $this->assertTrue(is_string($getHighlightingFromAnswerID(52)));
    }

    function _testLoadHTDigLibrary(){
        $existingHTDigFlag = $this->getInstanceProperty('isHTDigLoaded');

        $this->setInstanceProperty('isHTDigLoaded', false);
        $this->callInstanceMethod('loadHTDigLibrary');
        $this->assertTrue($this->getInstanceProperty('isHTDigLoaded'));

        $this->setInstanceProperty('isHTDigLoaded', $existingHTDigFlag);
    }

    function testGetStandardAnswerReportInsteadOfWidx(){
        $existingReportID = $this->getInstanceProperty('reportID');
        $existingAnswerTableAlias = $this->getInstanceProperty('answerTableAlias');
        $existingIncidentTableAlias = $this->getInstanceProperty('incidentTableAlias');
        $existingReportIsTypeExternalDocument = $this->getInstanceProperty('reportIsTypeExternalDocument');
        $existingAppliedFilters = $this->getInstanceProperty('appliedFilters');

        $this->setInstanceProperty('reportID', CP_WIDX_REPORT_DEFAULT);
        $this->setInstanceProperty('appliedFilters', array('sort_args'));

        $this->callInstanceMethod('getStandardAnswerReportInsteadOfWidx');
        $this->assertIdentical(CP_REPORT_DEFAULT, $this->getInstanceProperty('reportID'));
        $this->assertIdentical(false, $this->getInstanceProperty('reportIsTypeExternalDocument'));
        $appliedFilters = $this->getInstanceProperty('appliedFilters');
        $this->assertNull($appliedFilters['sort_args']);
        $this->assertIdentical('answers', $this->getInstanceProperty('answerTableAlias'));
        $this->assertNull($this->getInstanceProperty('incidentTableAlias'));

        $this->setInstanceProperty('reportID', $existingReportID);
        $this->setInstanceProperty('answerTableAlias', $existingAnswerTableAlias);
        $this->setInstanceProperty('incidentTableAlias', $existingIncidentTableAlias);
        $this->setInstanceProperty('reportIsTypeExternalDocument', $existingReportIsTypeExternalDocument);
        $this->setInstanceProperty('appliedFilters', $existingAppliedFilters);
    }

    function testFormatExternalSearchData(){
        $existingReportID = $this->getInstanceProperty('reportID');
        $existingReturnData = $this->getInstanceProperty('returnData');
        $existingAppliedFormats = $this->getInstanceProperty('appliedFormats');

        $this->setInstanceProperty('reportID', CP_WIDX_REPORT_DEFAULT);
        $this->setInstanceProperty('appliedFormats', array(
            'truncate_size' => 200,
            'max_wordbreak_trunc' => 10,
            'highlight' => true,
            'search_term' => 'phone',
            'emphasisHighlight' => true,
            'highlightLength' => 2)
        );

        $this->setInstanceProperty('returnData', array('data' => array(
            array(null, 'phone title', 'phone summary', pow(2, 10), 1352440800, 60),
            array(null, 'normal title', str_repeat('content', 50), 0, 0, 20),
        ), 'search_term' => 'phone'));

        $this->callInstanceMethod('formatExternalSearchData');

        $returnData = $this->getInstanceProperty('returnData');
        $returnData = $returnData['data'];

        $this->assertTrue(is_array($returnData));
        $this->assertIdentical(2, count($returnData));
        $this->assertNull($returnData[0][0]);
        $this->assertIdentical('<span class="highlight">phone</span> title', $returnData[0][1]);
        $this->assertIdentical('<span class="highlight">phone</span> summary', $returnData[0][2]);
        $this->assertIdentical('1KB', $returnData[0][3]);
        $this->assertIdentical('11/08/2012', $returnData[0][4]);
        $this->assertTrue(Text::stringContains($returnData[0][5], 'Score: 60'));
        $this->assertNull($returnData[1][0]);
        $this->assertIdentical('normal title', $returnData[1][1]);
        $this->assertIdentical(203, strlen($returnData[1][2]));
        $this->assertTrue(Text::endsWith($returnData[1][2], '...'));
        $this->assertIdentical('0b', $returnData[1][3]);
        $this->assertIdentical('12/31/1969', $returnData[1][4]);
        $this->assertTrue(Text::stringContains($returnData[1][5], 'Score: 20'));

        $this->setInstanceProperty('reportID', $existingReportID);
        $this->setInstanceProperty('returnData', $existingReturnData);
        $this->setInstanceProperty('appliedFormats', $existingAppliedFormats);
    }

    function testGetExternalDocumentSortByType(){
        $getExternalDocumentSortByType = $this->getMethod('getExternalDocumentSortByType');

        $this->assertIdentical(HTSEARCH_SORT_SCORE_STR, $getExternalDocumentSortByType(null));
        $this->assertIdentical(HTSEARCH_SORT_SCORE_STR, $getExternalDocumentSortByType(false));
        $this->assertIdentical(HTSEARCH_SORT_SCORE_STR, $getExternalDocumentSortByType(0));
        $this->assertIdentical(HTSEARCH_SORT_SCORE_STR, $getExternalDocumentSortByType('test'));

        $this->assertIdentical(HTSEARCH_SORT_SCORE_STR, $getExternalDocumentSortByType(WIDX_SCORE_SORT));
        $this->assertIdentical(HTSEARCH_SORT_REV_TIME_STR, $getExternalDocumentSortByType(WIDX_TIME_SORT));
        $this->assertIdentical(HTSEARCH_SORT_TITLE_STR, $getExternalDocumentSortByType(WIDX_TITLE_SORT));
        $this->assertIdentical(HTSEARCH_SORT_TIME_STR, $getExternalDocumentSortByType(WIDX_REV_TIME_SORT));
        $this->assertIdentical(HTSEARCH_SORT_REV_TITLE_STR, $getExternalDocumentSortByType(WIDX_REV_TITLE_SORT));
    }

    function testGetExternalDocumentSearchByType(){
        $getExternalDocumentSearchByType = $this->getMethod('getExternalDocumentSearchByType');

        $this->assertIdentical(76, $getExternalDocumentSearchByType(null));
        $this->assertIdentical(76, $getExternalDocumentSearchByType('test'));
        $this->assertIdentical(76, $getExternalDocumentSearchByType(false));
        $this->assertIdentical(76, $getExternalDocumentSearchByType(array()));

        $this->assertIdentical(4, $getExternalDocumentSearchByType(WIDX_ANY_SEARCH));
        $this->assertIdentical(1, $getExternalDocumentSearchByType(WIDX_ALL_SEARCH));
    }

    function testGetHeaders(){
        $existingViewDef = $this->getInstanceProperty('viewDefinition');
        $existingAnswerAlias = $this->getInstanceProperty('answerTableAlias');
        $existingReportID = $this->getInstanceProperty('reportID');

        $this->setInstanceProperty('viewDefinition', array('all_cols' => array()));
        $this->setInstanceProperty('answerTableAlias', '');

        $this->assertIdentical(array(), $this->callInstanceMethod('getHeaders', false));

        $this->setInstanceProperty('reportID', 12);
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(array('heading' => 'one', 'visible' => false), array('heading' => 'two', 'visible' => false))));
        $this->assertIdentical(array(), $this->callInstanceMethod('getHeaders', false));

        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            array('heading' => 'one', 'width' => '15px', 'data_type' => 'string', 'visible' => 1),
            array('heading' => 'two', 'order' => 2, 'col_definition' => 'col', 'visible' => true, 'url_info' => array('url' => 'http://google.com')))));
        $this->setInstanceProperty('reportID', 13);

        $result = array(
            array('heading' => 'one', 'width' => '15px', 'data_type' => 'string', 'col_id' => 1, 'order' => null, 'col_definition' => null, 'visible' => true),
            array('heading' => 'two', 'width' => null, 'data_type' => null, 'col_id' => 2, 'order' => 2, 'col_definition' => 'col', 'visible' => false, 'url_info' => 'http://google.com')
        );
        $this->assertIdentical($result, $this->callInstanceMethod('getHeaders', false));

        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            array('heading' => 'one', 'width' => '15px', 'data_type' => 'string', 'visible' => 1, 'col_definition' => 'answers.summary'),
            array('heading' => 'two', 'order' => 2, 'col_definition' => 'answers.updated', 'visible' => 0),
            array('heading' => 'three', 'order' => 3, 'col_definition' => 'answers.solved', 'visible' => 0),
            )));
        $this->setInstanceProperty('reportID', 13);
        $this->setInstanceProperty('answerTableAlias', 'answers');

        $result = array(
            array('heading' => 'one', 'width' => '15px', 'data_type' => 'string', 'col_id' => 1, 'order' => null, 'col_definition' => 'answers.summary', 'visible' => true, 'col_alias' => 'summary'),
            array('heading' => 'two', 'width' => null, 'data_type' => null, 'col_id' => 2, 'order' => 2, 'col_definition' => 'answers.updated', 'visible' => false, 'col_alias' => 'updated'),
            array('heading' => 'three', 'width' => null, 'data_type' => null, 'col_id' => 3, 'order' => 3, 'col_definition' => 'answers.solved', 'visible' => false, 'col_alias' => 'score'),
        );
        $this->assertIdentical($result, $this->callInstanceMethod('getHeaders', true));

        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            array('heading' => 'one', 'width' => '15px', 'data_type' => 'string', 'visible' => 0, 'col_definition' => 'answers.summary'),
            array('heading' => 'two', 'order' => 2, 'col_definition' => 'answers.updated', 'visible' => 1),
            array('heading' => 'three', 'order' => 3, 'col_definition' => 'answers.solved', 'visible' => 1),
            )));
        $this->setInstanceProperty('reportID', 14);
        $this->setInstanceProperty('answerTableAlias', 'answers');

        $result = array(
            array('heading' => 'one', 'width' => '15px', 'data_type' => 'string', 'col_id' => 1, 'order' => null, 'col_definition' => 'answers.summary', 'visible' => false, 'col_alias' => 'summary'),
            array('heading' => 'two', 'width' => null, 'data_type' => null, 'col_id' => 2, 'order' => 2, 'col_definition' => 'answers.updated', 'visible' => true, 'col_alias' => 'updated'),
            array('heading' => 'three', 'width' => null, 'data_type' => null, 'col_id' => 3, 'order' => 3, 'col_definition' => 'answers.solved', 'visible' => true, 'col_alias' => 'score'),
        );
        $this->assertIdentical($result, $this->callInstanceMethod('getHeaders', true));

        $result = array(
            array('heading' => 'two', 'width' => null, 'data_type' => null, 'col_id' => 2, 'order' => 2, 'col_definition' => 'answers.updated', 'visible' => true),
            array('heading' => 'three', 'width' => null, 'data_type' => null, 'col_id' => 3, 'order' => 3, 'col_definition' => 'answers.solved', 'visible' => true),
        );
        $this->assertIdentical($result, $this->callInstanceMethod('getHeaders', false));

        $this->setInstanceProperty('answerTableAlias', $existingAnswerAlias);
        $this->setInstanceProperty('viewDefinition', $existingViewDef);
        $this->setInstanceProperty('reportID', $existingReportID);
    }

    function testSetViewDefinition(){
        $existingReportID = $this->getInstanceProperty('reportID');
        $existingViewDef = $this->getInstanceProperty('viewDefinition');

        $this->setInstanceProperty('reportID', 176);
        $this->setInstanceProperty('viewDefinition', null);
        $this->callInstanceMethod('setViewDefinition');
        $this->assertIdentical(
          array('all_cols', 'initial_search', 'display_args', 'exceptions', 'rpt_per_page',
            'num_per_page', 'grouped', 'row_num', 'col_names', 'opts', 'row_limit'),
          array_keys($this->getInstanceProperty('viewDefinition')));

        $this->setInstanceProperty('reportID', $existingReportID);
        $this->setInstanceProperty('viewDefinition', $existingViewDef);
    }

    function testConvertCPFiltersToQueryArguments(){
        $existingFilters = $this->getInstanceProperty('appliedFilters');
        $existingViewDef = $this->getInstanceProperty('viewDefinition');

        $this->setInstanceProperty('appliedFilters', array());
        $this->setInstanceProperty('viewDefinition', array());

        $this->assertIdentical(array(
            'param_args' => array(),
            'search_args' => array(),
            'sort_args' => null,
            'limit_args' => array(
                'row_limit' => 15,
                'row_start' => 0,
            ),
            'count_args' => array(
                'get_row_count' => 1,
                'get_node_leaf_count' => 1,
            )
        ), $this->callInstanceMethod('convertCPFiltersToQueryArguments'));

        $this->setInstanceProperty('appliedFilters', array('page' => 3, 'start_index' => "6"));
        $this->setInstanceProperty('viewDefinition', array('rpt_per_page' => 7, ));
        $this->assertIdentical(array(
            'param_args' => array(),
            'search_args' => array(),
            'sort_args' => null,
            'limit_args' => array(
                'row_limit' => 7,
                'row_start' => 6,
            ),
            'count_args' => array(
                'get_row_count' => 1,
                'get_node_leaf_count' => 1,
            )
        ), $this->callInstanceMethod('convertCPFiltersToQueryArguments'));

        $this->setInstanceProperty('appliedFilters', array('page' => 3));
        $this->setInstanceProperty('viewDefinition', array('rpt_per_page' => 7));
        $this->assertIdentical(array(
            'param_args' => array(),
            'search_args' => array(),
            'sort_args' => null,
            'limit_args' => array(
                'row_limit' => 7,
                'row_start' => 14,
            ),
            'count_args' => array(
                'get_row_count' => 1,
                'get_node_leaf_count' => 1,
            )
        ), $this->callInstanceMethod('convertCPFiltersToQueryArguments'));

        $this->setInstanceProperty('appliedFilters', $existingFilters);
        $this->setInstanceProperty('viewDefinition', $existingViewDef);
    }

    function testGetNumberPerPage(){
        $existingFilters = $this->getInstanceProperty('appliedFilters');

        $this->setInstanceProperty('appliedFilters', array());

        $this->assertIdentical(15, $this->callInstanceMethod('getNumberPerPage', null));
        $this->assertIdentical(15, $this->callInstanceMethod('getNumberPerPage', 0));
        $this->assertIdentical(10, $this->callInstanceMethod('getNumberPerPage', 10));

        $this->setInstanceProperty('appliedFilters', array('per_page' => null));
        $this->assertIdentical(10, $this->callInstanceMethod('getNumberPerPage', 10));

        $this->setInstanceProperty('appliedFilters', array('per_page' => 0));
        $this->assertIdentical(10, $this->callInstanceMethod('getNumberPerPage', 10));

        $this->setInstanceProperty('appliedFilters', array('per_page' => -1));
        $this->assertIdentical(0, $this->callInstanceMethod('getNumberPerPage', 10));

        $this->setInstanceProperty('appliedFilters', array('per_page' => 5));
        $this->assertIdentical(5, $this->callInstanceMethod('getNumberPerPage', 10));

        $this->setInstanceProperty('appliedFilters', array('per_page' => "7"));
        $this->assertIdentical(7, $this->callInstanceMethod('getNumberPerPage', 10));

        $this->setInstanceProperty('appliedFilters', array('per_page' => "abc"));
        $this->assertIdentical(0, $this->callInstanceMethod('getNumberPerPage', 10));

        $this->setInstanceProperty('appliedFilters', $existingFilters);
    }

    function testSetMaxResultsBasedOnSearchLimiting(){
        $existingFilters = $this->getInstanceProperty('appliedFilters');
        $existingReturnData = $this->getInstanceProperty('returnData');

        $this->setInstanceProperty('appliedFilters', array());
        $this->setInstanceProperty('returnData', array());
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', null);
        $this->assertIdentical(array(), $this->getInstanceProperty('returnData'));

        \Rnow::updateConfig('SEARCH_RESULT_LIMITING', 1, true);

        $keywordObject = (object)array('filters' => (object)array('data' => 'search term'));

        $this->setInstanceProperty('appliedFilters', array('page' => 1, 'keyword' => $keywordObject));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 1);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 0.9), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 12);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 10.8), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', null);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 13.5), $this->getInstanceProperty('returnData'));

        \Rnow::updateConfig('SEARCH_RESULT_LIMITING', 2, true);
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 1);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 0.5), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 12);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 6), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', null);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 7.5), $this->getInstanceProperty('returnData'));

        \Rnow::updateConfig('SEARCH_RESULT_LIMITING', 3, true);
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 1);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 0.2), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 12);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 2.4), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', null);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 3), $this->getInstanceProperty('returnData'));

        \Rnow::updateConfig('SEARCH_RESULT_LIMITING', 4, true);
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 1);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 0.2), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 12);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 2.4), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', null);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 3), $this->getInstanceProperty('returnData'));

        \Rnow::updateConfig('SEARCH_RESULT_LIMITING', -1, true);
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 1);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 0), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 12);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 0), $this->getInstanceProperty('returnData'));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', null);
        $this->assertIdentical(array('truncated' => 1, 'max_results' => 0), $this->getInstanceProperty('returnData'));

        $this->setInstanceProperty('returnData', array());
        $this->setInstanceProperty('appliedFilters', array('no_truncate' => true, 'page' => 1, 'keyword' => $keywordObject));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 1);
        $this->assertIdentical(array(), $this->getInstanceProperty('returnData'));

        $this->setInstanceProperty('appliedFilters', array('page' => 2, 'keyword' => $keywordObject));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 1);
        $this->assertIdentical(array(), $this->getInstanceProperty('returnData'));

        $keywordObject->filters->data = null;
        $this->setInstanceProperty('appliedFilters', array('page' => 1, 'keyword' => $keywordObject));
        $this->callInstanceMethod('setMaxResultsBasedOnSearchLimiting', 1);
        $this->assertIdentical(array(), $this->getInstanceProperty('returnData'));


        \Rnow::updateConfig('SEARCH_RESULT_LIMITING', 0, true);
        $this->setInstanceProperty('appliedFilters', $existingFilters);
        $this->setInstanceProperty('returnData', $existingReturnData);
    }

    function testFormatViewsData(){
        $existingReportID = $this->getInstanceProperty('reportID');
        $existingViewDefinition = $this->getInstanceProperty('viewDefinition');
        $existingViewDataColumnDefinition = $this->getInstanceProperty('viewDataColumnDefinition');
        $existingReturnData = $this->getInstanceProperty('returnData');
        $existingAppliedFormats = $this->getInstanceProperty('appliedFormats');
        $existingAnswerIDList = $this->getInstanceProperty('answerIDList');
        $existingAnswerTableAlias = $this->getInstanceProperty('answerTableAlias');
        $existingIncidentTableAlias = $this->getInstanceProperty('incidentTableAlias');

        $newReturnData = array(
            'data' => array(
                array('<b>BOLD123!!!</b>', '<b>BOLD456!!!</b>', '<b>BOLD789!!!</b>', 55),
        ));

        $this->setInstanceProperty('reportID', 12345678);
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'answers.smalltext'),
            'field1' => array('col_definition' => 'answers.solution'),
            'field2' => array('col_definition' => 'answers.longtext'),
            'field3' => array('col_definition' => 'answers.number'),
        )));
        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'answers.smalltext', 'bind_type' => BIND_NTS),
            'col_item1' => array('hidden' => 0, 'val' => 'answers.solution', 'bind_type' => BIND_MEMO),
            'col_item2' => array('hidden' => 0, 'val' => 'answers.longtext', 'bind_type' => BIND_MEMO),
            'col_item3' => array('hidden' => 1, 'val' => 'answers.number', 'bind_type' => BIND_INT),
        ));
        $this->setInstanceProperty('appliedFormats', array());
        $this->setInstanceProperty('answerIDList', array());
        $this->setInstanceProperty('answerTableAlias', 'answers');

        // escape text fields that are not answers.solution or answers.description
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array('&lt;b&gt;BOLD123!!!&lt;/b&gt;', '<b>BOLD456!!!</b>', '&lt;b&gt;BOLD789!!!&lt;/b&gt;'),
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        // return hidden columns if desired
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData', true, true);
        $expected = array(
            'data' => array(
                array('&lt;b&gt;BOLD123!!!&lt;/b&gt;', '<b>BOLD456!!!</b>', '&lt;b&gt;BOLD789!!!&lt;/b&gt;', 55),
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        // variables in answers should be expanded, incidents should not
        $this->setInstanceProperty('incidentTableAlias', 'incidents');
        $newReturnData = array(
            'data' => array(
                array('answer test $toll_free', 'incident test $toll_free'),
        ));

        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'answers.solution'),
            'field1' => array('col_definition' => 'incident.subject'),
        )));

        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'answers.solution', 'bind_type' => BIND_NTS),
            'col_item1' => array('hidden' => 0, 'val' => 'incident.subject', 'bind_type' => BIND_NTS),
        ));

        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array("answer test (800) 555-5555", "incident test \$toll_free")
            )
        );
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        //Remove the answerTableAlias and verify that variables are not expanded
        $this->setInstanceProperty('answerTableAlias', null);
        $this->callInstanceMethod('formatViewsData');
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        // @@@ QA 140422-000110 Do not format href if no value for URL column
        $this->setInstanceProperty('incidentTableAlias', 'incidents');
        $newReturnData = array(
            'data' => array(
                array('http://google.com', ''),
        ));

        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'answers.url1', 'url_info' => array('url' => '&lt;1&gt;')),
            'field1' => array('col_definition' => 'answers.url2', 'url_info' => array('url' => '//site.com/details/&lt;2&gt;')),
        )));

        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'answers.url1', 'bind_type' => BIND_MEMO),
            'col_item1' => array('hidden' => 0, 'val' => 'answers.url2', 'bind_type' => BIND_MEMO),
        ));

        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array("<a href='http://google.com" . \RightNow\Utils\Url::sessionParameter() . "'>http://google.com</a>", "")
            )
        );
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        $this->setInstanceProperty('appliedFormats', array('no_session' => true));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array("<a href='http://google.com' >http://google.com</a>", "")
            )
        );
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        // sanitize data
        $newReturnData = array(
            'data' => array(
                array('**BOLD123!!!** and \( and \) also.', '<b>BOLD456!!!</b>', '<b>BOLD789!!!</b>', 55),
        ));
        $this->setInstanceProperty('appliedFormats', array('sanitizeData' => array(1 => 'text/x-markdown')));
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'answers.smalltext', 'visible' => 1, 'order' => 0),
            'field1' => array('col_definition' => 'answers.solution', 'visible' => 1, 'order' => 1),
            'field2' => array('col_definition' => 'answers.longtext', 'visible' => 0, 'order' => 2),
            'field3' => array('col_definition' => 'answers.number', 'visible' => 0, 'order' => 3)
        )));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array('<p><strong>BOLD123!!!</strong> and &#40; and &#41; also.</p>'.chr(10), '&lt;b&gt;BOLD456!!!&lt;/b&gt;')
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        // sanitize data as text/html
        $newReturnData = array(
            'data' => array(
                array('<b>BOLD123!!!</b> and \( and \) also.', '<b>BOLD456!!!</b>', '<b>BOLD789!!!</b>', 55),
        ));
        $this->setInstanceProperty('appliedFormats', array('sanitizeData' => array('1' => 'text/html')));
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'answers.smalltext', 'visible' => 1, 'order' => 0),
            'field1' => array('col_definition' => 'answers.solution', 'visible' => 1, 'order' => 1),
            'field2' => array('col_definition' => 'answers.longtext', 'visible' => 0, 'order' => 2),
            'field3' => array('col_definition' => 'answers.number', 'visible' => 0, 'order' => 3)
        )));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array('&lt;b&gt;BOLD123!!!&lt;/b&gt; and \( and \) also.', '&lt;b&gt;BOLD456!!!&lt;/b&gt;')
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        $newReturnData = array(
            'data' => array(
                array('0'),
                array('000'),
        ));
        $this->setInstanceProperty('appliedFormats', array('no_session' => true));
        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'answers.url1', 'bind_type' => BIND_NTS)
        ));
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
                'field0' => array('col_definition' => 'answers.url1', 'visible' => 1, 'url_info' => array('url' => '//site.com/details/&lt;2&gt;')),
        )));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array("<a href='//site.com/details/' >0</a>"),
                array("<a href='//site.com/details/' >000</a>"),
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        //comments should be truncated and in plain text when sanitize_data and truncate_size are specified
        $this->setInstanceProperty('reportID', 111122223);
        $newReturnData = array(
            'data' => array(
                array('This is *markdown* format. A [link](www.google.com) to search page.', 'Text Messaging', 2),
        ));
        $this->setInstanceProperty('appliedFormats', array('sanitizeData' => array('1' => 'text/x-markdown'), 'truncate_size' => 20));
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'sss_question_comments.body', 'visible' => 1, 'order' => 0),
            'field1' => array('col_definition' => 'products.id.name', 'visible' => 1, 'order' => 1),
            'field2' => array('col_definition' => 'sss_question_comments.sss_question_comment_id', 'visible' => 1, 'order' => 2)
        )));
        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'sss_question_comments.body', 'bind_type' => 2),
            'col_item1' => array('hidden' => 0, 'val' => 'products.id.name', 'bind_type' => 2),
            'col_item2' => array('hidden' => 0, 'val' => 'sss_question_comments.sss_question_comment_id', 'bind_type' => 2)
        ));
        $this->setInstanceProperty('socialCommentTableAlias', 'sss_question_comments');
        $expected = array(
            'data' => array(
                array('This is markdown...', 'Text Messaging', '2')
        ));

        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        //question body should be truncated and in plain text when sanitize_data and truncate_size are specified
        $this->setInstanceProperty('reportID', 111122233);
        $newReturnData = array(
            'data' => array(
                array('<html>This is HTML format. <b> And this is in bold. </b></html> format', 'Text Messaging', 2),
        ));
        $this->setInstanceProperty('appliedFormats', array('sanitizeData' => array('1' => 'text/html'), 'truncate_size' => 20));
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'sss_questions.body', 'visible' => 1, 'order' => 0),
            'field1' => array('col_definition' => 'products.id.name', 'visible' => 1, 'order' => 1),
            'field2' => array('col_definition' => 'sss_question_comments.sss_question_comment_id', 'visible' => 1, 'order' => 2)
        )));
        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'sss_questions.body', 'bind_type' => 2),
            'col_item1' => array('hidden' => 0, 'val' => 'products.id.name', 'bind_type' => 2),
            'col_item2' => array('hidden' => 0, 'val' => 'sss_question_comments.sss_question_comment_id', 'bind_type' => 2)
        ));
        $this->setInstanceProperty('socialQuestionTableAlias', 'sss_questions');
        $expected = array(
            'data' => array(
                array('This is HTML...', 'Text Messaging', '2')
        ));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        $newReturnData = array(
            'data' => array(
                array('<html>This is HTML format. <b> And this is in bold. </b></html> format', 2, 'text/html'),
        ));
        $this->setInstanceProperty('appliedFormats', array('sanitizeData' => array('1' => '3'), 'truncate_size' => 20));
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'sss_questions.body', 'visible' => 1, 'order' => 0),
            'field1' => array('col_definition' => 'sss_question_comments.sss_question_comment_id', 'visible' => 1, 'order' => 1),
            'field2' => array('col_definition' => 'sss_questions.content_type', 'visible' => 0, 'order' => 2)
        )));
        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'sss_questions.body', 'bind_type' => 2),
            'col_item1' => array('hidden' => 0, 'val' => 'sss_question_comments.sss_question_comment_id', 'bind_type' => 2),
            'col_item2' => array('hidden' => 1, 'val' => 'sss_questions.content_type', 'bind_type' => 2)
        ));
        $this->setInstanceProperty('socialQuestionTableAlias', 'sss_questions');
        $expected = array(
            'data' => array(
                array('This is HTML...', '2')
        ));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        // @@@ QA: 160309-000188 Date-time wrap in <span>
        $newReturnData = array(
            'data' => array(
                array('Some text here', 1464174815)
        ));
        $this->setInstanceProperty('appliedFormats', array('dateFormat' => 'date_time'));
        $this->setInstanceProperty('viewDefinition', array('all_cols' => array(
            'field0' => array('col_definition' => 'answers.smalltext', 'visible' => 1, 'order' => 0),
            'field1' => array('col_definition' => 'answers.date', 'visible' => 1, 'order' => 1)
        )));
        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'answers.smalltext', 'bind_type' => BIND_NTS),
            'col_item1' => array('hidden' => 0, 'val' => 'answers.date', 'bind_type' => BIND_DATE),
        ));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');

        $expected = array(
            'data' => array(
                array('Some text here', "<span>05/25/2016</span> <span>05:13 AM </span>")
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        $this->setInstanceProperty('appliedFormats', array('dateFormat' => 'long'));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array('Some text here', "<span>05/25/2016</span> <span>05:13 AM </span>")
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        $this->setInstanceProperty('appliedFormats', array('dateFormat' => 'short'));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array('Some text here', "05/25/2016")
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        $this->setInstanceProperty('appliedFormats', array('dateFormat' => 'raw'));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array('Some text here', 1464174815)
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        //Handle datetime type
        $newReturnData = array(
            'data' => array(
                array('value', 1598202422)
        ));
        $this->setInstanceProperty('appliedFormats', array('dateFormat' => 'date_time'));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');
        $expected = array(
            'data' => array(
                array('value', "<span>08/23/2020</span> <span>11:07 AM </span>")
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        // @@@ 210129-000105 need to treat empty date strings like nulls
        $newReturnData = array(
            'data' => array(
                array(null, null, null, '', '', '')
        ));
        $this->setInstanceProperty('viewDataColumnDefinition', array(
            'col_item0' => array('hidden' => 0, 'val' => 'answers.date', 'bind_type' => BIND_DATE),
            'col_item1' => array('hidden' => 0, 'val' => 'answers.date', 'bind_type' => BIND_DTTM),
            'col_item2' => array('hidden' => 0, 'val' => 'answers.date', 'bind_type' => CP_BIND_BIG_DATE),
            'col_item3' => array('hidden' => 0, 'val' => 'answers.date', 'bind_type' => BIND_DATE),
            'col_item4' => array('hidden' => 0, 'val' => 'answers.date', 'bind_type' => BIND_DTTM),
            'col_item5' => array('hidden' => 0, 'val' => 'answers.date', 'bind_type' => CP_BIND_BIG_DATE),
        ));
        $this->setInstanceProperty('returnData', $newReturnData);
        $this->callInstanceMethod('formatViewsData');

        $expected = array(
            'data' => array(
                array('', '', '', '', '', '')
        ));
        $this->assertIdentical($expected, $this->getInstanceProperty('returnData'));

        $this->setInstanceProperty('reportID', $existingReportID);
        $this->setInstanceProperty('viewDefinition', $existingViewDefinition);
        $this->setInstanceProperty('viewDataColumnDefinition', $existingViewDataColumnDefinition);
        $this->setInstanceProperty('returnData', $existingReturnData);
        $this->setInstanceProperty('appliedFormats', $existingAppliedFormats);
        $this->setInstanceProperty('answerIDList', $existingAnswerIDList);
        $this->setInstanceProperty('answerTableAlias', $existingAnswerTableAlias);
        $this->setInstanceProperty('incidentTableAlias', $existingIncidentTableAlias);
    }

    function testSetExceptionTags(){
        $setExceptionTags = $this->getMethod('setExceptionTags');

        $this->assertIdentical(array(), $setExceptionTags(null, null));
        $this->assertIdentical(array(), $setExceptionTags(array(), null));

        $this->assertIdentical(array(2), $setExceptionTags(array(array('col_id' => 2, 'e_id' => 1, 'xml_data' => '<test></test>')), array(12, 13)));
        $exceptions = array(array(
            'col_id' => 2,
            'e_id' => 1,
            'xml_data' => '<?xml version="1.0" encoding="utf-16"?>
                <ExceptionXmlData xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                  <Name>
                    <LabelStr>new ex</LabelStr>
                  </Name>
                  <Style>
                    <AllCaps>false</AllCaps>
                    <ForeColorString>FFFF0000</ForeColorString>
                    <Padding>
                      <All>-1</All>
                      <Bottom>0</Bottom>
                      <Left>0</Left>
                      <Right>0</Right>
                      <Top>0</Top>
                    </Padding>
                    <Spacing>0</Spacing>
                    <FontSize>8</FontSize>
                    <FontStyle>Regular</FontStyle>
                    <FontFamily>Tahoma</FontFamily>
                    <StyleType>None</StyleType>
                    <Sequence>0</Sequence>
                  </Style>
                </ExceptionXmlData>'),
          array(
            'col_id' => 2,
            'e_id' => 2,
            'xml_data' => '<?xml version="1.0" encoding="utf-16"?>
                <ExceptionXmlData xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                  <Name>
                    <LabelId>41534</LabelId>
                  </Name>
                  <Style>
                    <AllCaps>false</AllCaps>
                    <ForeColorString>FF008000</ForeColorString>
                    <Padding>
                      <All>-1</All>
                      <Bottom>0</Bottom>
                      <Left>0</Left>
                      <Right>0</Right>
                      <Top>0</Top>
                    </Padding>
                    <Spacing>0</Spacing>
                    <FontSize>8</FontSize>
                    <FontStyle>Regular</FontStyle>
                    <FontFamily>Tahoma</FontFamily>
                    <StyleType>None</StyleType>
                    <Sequence>0</Sequence>
                  </Style>
                </ExceptionXmlData>')
        );

        $this->assertIdentical(array(2, 2), $this->callInstanceMethod('setExceptionTags', $exceptions, array(0 => 12, 1 => 13)));

        $resultingExceptions = array(array(
            'col_id' => 2,
            'e_id' => 1,
            'xml_data' => '<?xml version="1.0" encoding="utf-16"?>
                <ExceptionXmlData xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                  <Name>
                    <LabelStr>new ex</LabelStr>
                  </Name>
                  <Style>
                    <AllCaps>false</AllCaps>
                    <ForeColorString>FFFF0000</ForeColorString>
                    <Padding>
                      <All>-1</All>
                      <Bottom>0</Bottom>
                      <Left>0</Left>
                      <Right>0</Right>
                      <Top>0</Top>
                    </Padding>
                    <Spacing>0</Spacing>
                    <FontSize>8</FontSize>
                    <FontStyle>Regular</FontStyle>
                    <FontFamily>Tahoma</FontFamily>
                    <StyleType>None</StyleType>
                    <Sequence>0</Sequence>
                  </Style>
                </ExceptionXmlData>'),
          array(
            'col_id' => 2,
            'e_id' => 2,
            'xml_data' => '<?xml version="1.0" encoding="utf-16"?>
                <ExceptionXmlData xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                  <Name>
                    <LabelId>41534</LabelId>
                  </Name>
                  <Style>
                    <AllCaps>false</AllCaps>
                    <ForeColorString>FF008000</ForeColorString>
                    <Padding>
                      <All>-1</All>
                      <Bottom>0</Bottom>
                      <Left>0</Left>
                      <Right>0</Right>
                      <Top>0</Top>
                    </Padding>
                    <Spacing>0</Spacing>
                    <FontSize>8</FontSize>
                    <FontStyle>Regular</FontStyle>
                    <FontFamily>Tahoma</FontFamily>
                    <StyleType>None</StyleType>
                    <Sequence>0</Sequence>
                  </Style>
                </ExceptionXmlData>')
        );
        $this->assertIdentical($resultingExceptions, $exceptions);
    }

    function testXmlToArray(){
        $xmlToArray = $this->getMethod('xmlToArray');

        $this->assertIdentical(array(), $xmlToArray(''));
        $this->assertIdentical(array(array('name' => 'test')), $xmlToArray('<test></test>'));

        $this->assertIdentical(array(array('name' => 'catalog', 'elements' => array(array('name' => 'book', 'text' => 'test')))), $xmlToArray('<?xml version="1.0"?><catalog><book>test</book></catalog>'));

        $result = array(
          array(
            'name' => 'catalog',
            'elements' => array(
              array(
                'name' => 'book',
                'attributes' => array(
                  'id' => 'bk101',
                ),
                'elements' => array(
                  array(
                    'name' => 'author',
                    'text' => 'Gambardella, Matthew',
                  ),
                  array(
                    'name' => 'title',
                    'text' => 'XML Developer\'s Guide',
                  ),
                  array(
                    'name' => 'genre',
                    'text' => 'Computer',
                  ),
                  array(
                    'name' => 'price',
                    'text' => '44.95',
                  ),
                  array(
                    'name' => 'publish_date',
                    'text' => '2000-10-01',
                  ),
                  array(
                    'name' => 'description',
                    'text' => 'An in-depth look at creating applications with XML.',
        ))))));


        $this->assertIdentical($result, $xmlToArray('<?xml version="1.0"?><catalog><book id="bk101"><author>Gambardella, Matthew</author><title>XML Developer\'s Guide</title><genre>Computer</genre><price>44.95</price><publish_date>2000-10-01</publish_date><description>An in-depth look at creating applications with XML.</description></book></catalog>'));
    }

    function testBadFiltersToSearchArgs() {
        $filters = array(
            "custom_any" => (object)array(
                 'filters' => (object)array(
                    'rnSearchType' => "custom_search_type",
                    'searchName' => "custom_search",
                    'report_id' => $this->defaultIncidentReport,
                    'data' => (object)array('val' => false),
                    'oper_id' => 1,
                    'fltr_id' => '1 and (select count(*) from spitable) = 1 or 1=0 '
                )
            )
        );

        $reportMeta = new \ReflectionClass($this->reportModel);
        $filterPropMeta = $reportMeta->getProperty('appliedFilters');
        $filterPropMeta->setAccessible(true);
        $filterPropMeta->setValue($this->reportModel, $filters);
        $filterMethod = $reportMeta->getMethod('filtersToSearchArgs');
        $filterMethod->setAccessible(true);
        $response = $filterMethod->invoke($this->reportModel);

        $this->assertTrue(is_array($response));
        $this->assertTrue(count($response) === 0);
    }

    function testIsFilterIDValid() {
        $reportInstance = new $this->testingClass;
        list(, $reportID, $isFilterIDValid) = $this->reflect('reportID', 'method:isFilterIDValid');

        $reportID->setValue($reportInstance, $this->defaultIncidentReport); // standard questions list report

        $this->assertTrue($isFilterIDValid->invoke($reportInstance, 2));
        $this->assertTrue($isFilterIDValid->invoke($reportInstance, '3'));
        $this->assertFalse($isFilterIDValid->invoke($reportInstance, 0));

        $this->assertTrue($isFilterIDValid->invoke($reportInstance, 'prod'));
        $this->assertFalse($isFilterIDValid->invoke($reportInstance, 'test'));
        $this->assertFalse($isFilterIDValid->invoke($reportInstance, '1 and (select count(*) from spitable) = 1 or 1=0 '));

        $this->assertTrue($isFilterIDValid->invoke($reportInstance, 'incidents.org_id'));
        $this->assertTrue($isFilterIDValid->invoke($reportInstance, 'incidents.c_id'));
        $this->assertTrue($isFilterIDValid->invoke($reportInstance, 'orgs.lvl3_id'));
        $this->assertFalse($isFilterIDValid->invoke($reportInstance, 'tttt.tttt'));
        $this->assertFalse($isFilterIDValid->invoke($reportInstance, 'incidents.fred'));
        $this->assertFalse($isFilterIDValid->invoke($reportInstance, 'orgs.lvl333_id'));
        $this->assertFalse($isFilterIDValid->invoke($reportInstance, ' tttt . tttt'));
        $this->assertFalse($isFilterIDValid->invoke($reportInstance, 'ttttttttt'));

        $reportID->setValue($reportInstance, 228); // standard asset list report
        $this->assertTrue($isFilterIDValid->invoke($reportInstance, 'assets.c_id'));
    }

    function testIsNumericFilterIDValid() {
        $reportInstance = new $this->testingClass;
        list(, $reportID, $isNumericFilterIDValid) = $this->reflect('reportID', 'method:isNumericFilterIDValid');

        $reportID->setValue($reportInstance, $this->defaultIncidentReport); // standard questions list report

        $this->assertTrue($isNumericFilterIDValid->invoke($reportInstance, 2));
        $this->assertTrue($isNumericFilterIDValid->invoke($reportInstance, '2'));
        $this->assertFalse($isNumericFilterIDValid->invoke($reportInstance, 0));
    }

    function testIsNamedFilterIDValid() {
        $reportInstance = new $this->testingClass;
        list(, $reportID, $isNamedFilterIDValid) = $this->reflect('reportID', 'method:isNamedFilterIDValid');

        $reportID->setValue($reportInstance, $this->defaultIncidentReport); // standard questions list report

        $this->assertTrue($isNamedFilterIDValid->invoke($reportInstance, 'prod'));
        $this->assertFalse($isNamedFilterIDValid->invoke($reportInstance, 'test'));
        $this->assertFalse($isNamedFilterIDValid->invoke($reportInstance, '1 and (select count(*) from spitable) = 1 or 1=0 '));
    }

    function testIsColumnAliasFilterIDValid() {
        $reportInstance = new $this->testingClass;
        list(, $reportID, $isColumnAliasFilterIDValid) = $this->reflect('reportID', 'method:isColumnAliasFilterIDValid');

        $reportID->setValue($reportInstance, $this->defaultIncidentReport); // standard questions list report

        $this->assertTrue($isColumnAliasFilterIDValid->invoke($reportInstance, 'incidents.org_id'));
        $this->assertTrue($isColumnAliasFilterIDValid->invoke($reportInstance, 'incidents.c_id'));
        $this->assertTrue($isColumnAliasFilterIDValid->invoke($reportInstance, 'orgs.lvl3_id'));
        $this->assertFalse($isColumnAliasFilterIDValid->invoke($reportInstance, 'tttt.tttt'));
        $this->assertFalse($isColumnAliasFilterIDValid->invoke($reportInstance, 'incidents.fred'));
        $this->assertFalse($isColumnAliasFilterIDValid->invoke($reportInstance, 'orgs.lvl333_id'));
        $this->assertFalse($isColumnAliasFilterIDValid->invoke($reportInstance, ' tttt . tttt'));
    }

    function testProductCatalogFiltersToSearchArgs()
    {
        $filters = array(
            "pc" => (object)array(
                'filters' => (object)array(
                    'rnSearchType' => "menufilter",
                    'searchName' => "pc",
                    'report_id' => 228,
                    'data' => array ( 0 => NULL, ),
                    'oper_id' => 1,
                    'fltr_id' => 2
                )
            )
        );

        $reportMeta = new \ReflectionClass($this->reportModel);
        $filterPropMeta = $reportMeta->getProperty('appliedFilters');
        $filterPropMeta->setAccessible(true);
        $filterPropMeta->setValue($this->reportModel, $filters);
        $filterMethod = $reportMeta->getMethod('filtersToSearchArgs');
        $filterMethod->setAccessible(true);
        $response = $filterMethod->invoke($this->reportModel);

        $this->assertTrue(is_array($response));

        foreach ($response as $key => $value) {
            if($value['name'] == "2") {
                $this->assertEqual($value['val'], "~any~");
            }
        }

        $filters = array(
            "pc" => (object)array(
                'filters' => (object)array(
                    'rnSearchType' => "menufilter",
                    'searchName' => "pc",
                    'report_id' => 228,
                    'data' => array (0 => array (0 => 222004, 1 => 222007, 2 => 222012, 3 => 8,),),
                    'oper_id' => 1,
                    'fltr_id' => 2
                )
            )
        );

        $reportMeta = new \ReflectionClass($this->reportModel);
        $filterPropMeta = $reportMeta->getProperty('appliedFilters');
        $filterPropMeta->setAccessible(true);
        $filterPropMeta->setValue($this->reportModel, $filters);
        $filterMethod = $reportMeta->getMethod('filtersToSearchArgs');
        $filterMethod->setAccessible(true);
        $response = $filterMethod->invoke($this->reportModel);

        $this->assertTrue(is_array($response));

        foreach ($response as $key => $value) {
            if($value['name'] == "2") {
                $this->assertEqual($value['val'], "8");
            }
        }

        $filters = array(
            "p" => (object)array(
                'filters' => (object)array(
                    'rnSearchType' => "menufilter",
                    'searchName' => "p",
                    'report_id' => 15100,
                    'data' => array ( 0 => -1),
                    'oper_id' => 10,
                    'fltr_id' => 5
                )
            )
        );

        $reportMeta = new \ReflectionClass($this->reportModel);
        $filterPropMeta->setValue($this->reportModel, $filters);
        $filterPropMeta = $reportMeta->getProperty('appliedFilters');
        $filterPropMeta->setAccessible(true);
        $filterMethod = $reportMeta->getMethod('filtersToSearchArgs');
        $filterMethod->setAccessible(true);
        $response = $filterMethod->invoke($this->reportModel);
        $this->assertTrue(is_array($response));

        foreach ($response as $key => $value) {
            if($value['name'] == "5") {
                $this->assertEqual($value['val'], "1.u0");
            }
        }

    }

    function testFiltersToOutputVariables(){
        $existingReportID = $this->getInstanceProperty('reportID');

        $this->setInstanceProperty('reportID', $this->defaultAnswersReport);
        $this->assertTrue(is_array($this->callInstanceMethod('filtersToOutputVariables')));

        $this->setInstanceProperty('reportID', $this->defaultIncidentReport);
        $this->assertTrue(is_array($this->callInstanceMethod('filtersToOutputVariables')));

        $this->setInstanceProperty('reportID', $existingReportID);
    }

    function testAddContactInformation(){
        $addContactInformation = $this->getMethod('addContactInformation');

        $this->assertTrue(is_array($addContactInformation(true, array())));
        $this->assertIdentical('stuff', $addContactInformation(true, 'stuff'));

        $existingIncidentTableAlias = $this->getInstanceProperty('incidentTableAlias');
        $this->setInstanceProperty('incidentTableAlias', true);

        $this->assertTrue(is_array($this->callInstanceMethod('addContactInformation', true, array())));
        $this->assertIdentical(array('search_field0' => array('name' => '1.c_id', 'oper_id' => 1, 'val' => '0')), $this->callInstanceMethod('addContactInformation', false, array()));
        $this->assertIdentical(array('search_field6' => array('name' => '1.c_id', 'oper_id' => 1, 'val' => '0')), $this->callInstanceMethod('addContactInformation', false, array(), 6));

        $existingAssetTableAlias = $this->getInstanceProperty('assetTableAlias');
        $this->setInstanceProperty('assetTableAlias', true);
        $this->setInstanceProperty('incidentTableAlias', $existingIncidentTableAlias);

        $this->assertIdentical(array('search_field7' => array('name' => '1.c_id', 'oper_id' => 1, 'val' => '0')), $this->callInstanceMethod('addContactInformation', false, array(), 7));
        $this->setInstanceProperty('assetTableAlias', $existingAssetTableAlias);
    }

    function testToFilterArray(){
        $toFilterArray = $this->getMethod('toFilterArray');

        $this->assertIdentical(array('name' => null, 'oper_id' => null, 'val' => null), $toFilterArray(null, null, null));
        $this->assertIdentical(array('name' => '', 'oper_id' => '', 'val' => null), $toFilterArray('', '', ''));
        $this->assertIdentical(array('name' => 'name', 'oper_id' => 'oper', 'val' => 'val'), $toFilterArray('name', 'oper', 'val'));
        $this->assertIdentical(array('name' => 'name', 'oper_id' => 'oper', 'val' => '   val   '), $toFilterArray('name', 'oper', '   val   '));
        $this->assertIdentical(array('name' => 'name', 'oper_id' => 'oper', 'val' => null), $toFilterArray('name', 'oper', '      '));
    }

    function testFiltersToSortArgs(){
        $existingAppliedFilters = $this->getInstanceProperty('appliedFilters');

        $this->setInstanceProperty('appliedFilters', array());
        $this->assertNull($this->callInstanceMethod('filtersToSortArgs'));
        $this->setInstanceProperty('appliedFilters', array('sort_args' => null));
        $this->assertNull($this->callInstanceMethod('filtersToSortArgs'));

        $this->setInstanceProperty('appliedFilters', array('sort_args' => array('filters' => array('sort_field0' => 'sort value'))));
        $this->assertIdentical(array('sort_field0' => 'sort value'), $this->callInstanceMethod('filtersToSortArgs'));

        $this->setInstanceProperty('appliedFilters', array('sort_args' => array('filters' => 'sort value again')));
        $sortArgs = $this->callInstanceMethod('filtersToSortArgs');
        $this->assertIdentical('sort value again', $sortArgs['sort_field0']);

        $this->setInstanceProperty('appliedFilters', array('sort_args' => (object)array('filters' => (object)array('data' => (object)array('col_id' => 18, 'sort_direction' => 1)))));
        $this->assertIdentical(array('sort_field0' => array('col_id' => 18, 'sort_direction' => 1,'sort_order' => 1)), $this->callInstanceMethod('filtersToSortArgs'));

        $this->setInstanceProperty('appliedFilters', array('sort_args' => (object)array('filters' => (object)array('data' => (object)array('col_id' => "18", 'sort_direction' => "1")))));
        $this->assertIdentical(array('sort_field0' => array('col_id' => 18, 'sort_direction' => 1,'sort_order' => 1)), $this->callInstanceMethod('filtersToSortArgs'));

        $this->setInstanceProperty('appliedFilters', array('sort_args' => (object)array('filters' => (object)array('col_id' => 18, 'sort_direction' => 1))));
        $this->assertIdentical(array('sort_field0' => array('col_id' => 18, 'sort_direction' => 1,'sort_order' => 1)), $this->callInstanceMethod('filtersToSortArgs'));

        $this->setInstanceProperty('appliedFilters', $existingAppliedFilters);
    }

    function testGetComplex(){
        $existingReportID = $this->getInstanceProperty('reportID');

        $this->setInstanceProperty('reportID', $this->defaultAnswersReport);

        $this->assertFalse($this->callInstanceMethod('getComplex', 2));
        $this->assertFalse($this->callInstanceMethod('getComplex', 1));
        $this->assertTrue($this->callInstanceMethod('getComplex', 5));

        $this->setInstanceProperty('reportID', $existingReportID);
    }

    function testPreProcessClusterTreeFilter()
    {
        $existingReportID = $this->getInstanceProperty('reportID');
        $existingAppliedFilters = $this->getInstanceProperty('appliedFilters');
        $existingCI = $this->getInstanceProperty('CI');

        $this->setInstanceProperty('reportID', CP_FEB10_CLUSTER_DEFAULT);

        if (!class_exists('\RightNow\Models\MockTopicBrowse')) {
            // make sure Report model is loaded
            get_instance()->model('TopicBrowse');
            Mock::generate('\RightNow\Models\TopicBrowse', '\RightNow\Models\MockTopicBrowse');
        }
        if (!class_exists('\RightNow\Controllers\MockBase')) {
            Mock::generate('\RightNow\Controllers\Base', '\RightNow\Controllers\MockBase');
        }
        $mockTopicBrowse = new \RightNow\Models\MockTopicBrowse();
        $mockTopicBrowse->returns('getBestMatchClusterID', (object)array('result' => 42), array('jibber'));
        $CI = new \RightNow\Controllers\MockBase();
        $this->setInstanceProperty('CI', $CI);
        $CI->returnsByValue('model', $mockTopicBrowse, array('Topicbrowse'));

        // parent filter is populated when not specified
        $input = array(
            'keyword' => (object)array('filters' => (object)array('data' => 'jibber')),
        );
        $expected = array(
            'keyword' => (object)array('filters' => (object)array('data' => 'jibber')),
            'parent' => (object)array('filters' => (object)array(
                'rnSearchType' => 'topicBrowse',
                'searchName' => 'parent',
                'report_id' => 178,
                'data' => (object)array('val' => 42),
                'oper_id' => 1,
                'fltr_id' => 10,
            )),
        );
        $this->setInstanceProperty('appliedFilters', $input);
        $this->callInstanceMethod('preProcessClusterTreeFilter');
        $this->assertIdentical($expected, $this->getInstanceProperty('appliedFilters'));

        // parent filter is untouched when getting a data->val (from JS)
        $input = array(
            'keyword' => (object)array('filters' => (object)array('data' => 'jibber')),
            'parent' => (object)array('filters' => (object)array(
                'data' => (object)array('val' => 'jabber'),
            )),
        );
        $expected = array(
            'keyword' => (object)array('filters' => (object)array('data' => 'jibber')),
            'parent' => (object)array('filters' => (object)array(
                'data' => (object)array('val' => 'jabber'),
            )),
        );
        $this->setInstanceProperty('appliedFilters', $input);
        $this->callInstanceMethod('preProcessClusterTreeFilter');
        $this->assertIdentical($expected, $this->getInstanceProperty('appliedFilters'));

        // parent filter is untouched when getting a data array (from URL parameters)
        $input = array(
            'keyword' => (object)array('filters' => (object)array('data' => 'jibber')),
            'parent' => (object)array('filters' => (object)array(
                'data' => array('fool'),
            )),
        );
        $expected = array(
            'keyword' => (object)array('filters' => (object)array('data' => 'jibber')),
            'parent' => (object)array('filters' => (object)array(
                'data' => array('fool'),
            )),
        );
        $this->setInstanceProperty('appliedFilters', $input);
        $this->callInstanceMethod('preProcessClusterTreeFilter');
        $this->assertIdentical($expected, $this->getInstanceProperty('appliedFilters'));

        $this->setInstanceProperty('reportID', $existingReportID);
        $this->setInstanceProperty('appliedFilters', $existingAppliedFilters);
        $this->setInstanceProperty('CI', $existingCI);
    }

    function testGetTableAlias(){
        $getTableAlias = $this->getMethod('getTableAlias');

        $response = $getTableAlias(0, 'answer');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);

        $response = $getTableAlias($this->defaultAnswersReport, TBL_ANSWERS);
        $this->assertIdentical('answers', $response->result);

        $response = $getTableAlias($this->defaultIncidentReport, VTBL_INCIDENTS);
        $this->assertIdentical('incidents', $response->result);

        $response = $getTableAlias($this->defaultAssetReport, TBL_ASSETS);
        $this->assertIdentical('assets', $response->result);

        \Rnow::updateConfig('WIDX_MODE', 1, true);
        $response = $getTableAlias($this->defaultWidxReport, VTBL_INCIDENTS);
        \Rnow::updateConfig('WIDX_MODE', 0, true);
        $this->assertNull($response->result);
    }

    function testConvertBytesToLargestUnit()
    {
        $invoke = $this->getMethod('convertBytesToLargestUnit');
        $this->assertEqual('-1b', $invoke(-1));
        $this->assertEqual('0b', $invoke(0));
        $this->assertEqual('1023b', $invoke(pow(2, 10) - 1));
        $this->assertEqual('1KB', $invoke(pow(2, 10)));
        $this->assertEqual('1KB', $invoke(pow(2, 10) + 1));
        $this->assertEqual('1KB', $invoke(pow(2, 10) * 1.5 - 1));
        $this->assertEqual('2KB', $invoke(pow(2, 10) * 1.5));
        $this->assertEqual('2KB', $invoke(pow(2, 10) * 1.5 + 1));
        $this->assertEqual('2KB', $invoke(pow(2, 10) * 2));
        $this->assertEqual('1024KB', $invoke(pow(2, 20) - 1));
        $this->assertEqual('1MB', $invoke(pow(2, 20)));
        $this->assertEqual('1MB', $invoke(pow(2, 20) + 1));
        $this->assertEqual('1024MB', $invoke(pow(2, 30) - 1));
        $this->assertEqual('1GB', $invoke(pow(2, 30)));
        $this->assertEqual('1GB', $invoke(pow(2, 30) + 1));
        $this->assertEqual('1024GB', $invoke(pow(2, 40) - 1));
        $this->assertEqual('1TB', $invoke(pow(2, 40)));
        $this->assertEqual('1TB', $invoke(pow(2, 40) + 1));
        $this->assertEqual('1.5TB', $invoke(pow(2, 40) * 1.5));
        $this->assertEqual('1.56TB', $invoke(pow(2, 40) * 1.56));
        $this->assertEqual('1.57TB', $invoke(pow(2, 40) * 1.567));
    }

    function testHooks()
    {
        $reportID = 176;
        $filters = array (
            'searchType' => (object)array (
                'filters' => (object)array (
                    'rnSearchType' => 'searchType',
                    'fltr_id' => 5,
                    'data' => 5,
                    'oper_id' => 1,
                    'report_id' => 176,
                ),
                'type' => 'searchType',
            ),
            'keyword' => (object)array (
                'filters' => (object)array (
                    'searchName' => 'keyword',
                    'data' => '',
                    'rnSearchType' => 'keyword',
                    'report_id' => 176,
                ),
            ),
            'p' => (object)array (
                'filters' => (object)array (
                    'rnSearchType' => 'menufilter',
                    'searchName' => 'p',
                    'report_id' => 176,
                    'fltr_id' => 2,
                    'oper_id' => 10,
                    'data' => array (
                        array (
                            '0' => 1,
                        ),
                    ),
                ),
            ),
            'page' => 1,
        );

        self::$hookData = null;
        $this->setHookReport('pre_report_get', 'preReportGetHook');
        $this->setHookReport('pre_report_get_data', 'preReportGetDataHook');
        $this->setHookReport('post_report_get_data', 'postReportGetDataHook');

        $results = $this->reportModel->getDataHTML($reportID, \RightNow\Utils\Framework::createToken($reportID), $filters, array())->result;
        // make sure we have hook results, since all three will be fired via getDataHTML()
        $this->assertIsA(self::$hookData['pre_report_get']['data'], 'array');
        $this->assertIdentical(self::$hookData['pre_report_get']['data']['reportId'], $reportID);
        $this->assertIsA(self::$hookData['pre_report_get']['data']['filters'], 'array');
        $this->assertIdentical(self::$hookData['pre_report_get']['data']['filters'], $filters);

        $preReportData = self::$hookData['pre_report_get_data']['data'];
        $this->assertIsA($preReportData['reportId'], 'integer');
        $this->assertIsA($preReportData, 'array');
        $this->assertIsA($preReportData['queryArguments'], 'array');
        $this->assertIsA($preReportData['queryArguments']['param_args'], 'array');
        $this->assertIsA($preReportData['queryArguments']['search_args'], 'array');

        $postReportData = self::$hookData['post_report_get_data']['data'];
        $this->assertIsA($postReportData, 'array');
        $this->assertIsA($postReportData['reportId'], 'integer');
        $this->assertIdentical($postReportData['returnData']['report_id'], $reportID);

        if (is_array($postReportData['returnData'])) {
            $returnData = $postReportData['returnData'];
            // the results contain more information than the hook receives
            // so we can't do an identical comparison on everything
            foreach (array_keys($returnData) as $key) {
                if ($key === "data") continue;
                $this->assertIdentical($returnData[$key], $results[$key]);
            }
        }
        else {
            $this->fail();
        }
        $this->assertIdentical($results['search_term'], 'Test_Search_Term');
    }

    function testPreSubReportCheckHook()
    {
        $reportID = 15100;
        $filters = array(
            'questions.updated' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'questions.updated',
                    'fltr_id' => 3,
                    'data' => 5,
                    'oper_id' => 4,
                    'report_id' => 15100,
                ),
            ),
            'questions.status' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'questions.status',
                    'fltr_id' => 1,
                    'data' => 29,
                    'oper_id' => 10,
                    'report_id' => 15100,
                ),
            ),
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'searchName' => 'sort_args',
                    'report_id' => 15100,
                    'data' => (object) array(
                        'col_id' => 3,
                        'sort_direction' => 1
                    ),
                ),
            ),
            'page' => 1,
        );

        self::$hookData = null;
        $this->setHookReport('pre_sub_report_check', 'preSubReportCheckHook');

        $this->reportModel->getDataHTML($reportID, \RightNow\Utils\Framework::createToken($reportID), $filters, array())->result;
        $this->assertIsA(self::$hookData['pre_sub_report_check'], 'array');
        $this->assertIdentical(self::$hookData['pre_sub_report_check'][15100]['MainReportIDFilter'], "question_id");

        self::$hookData = null;
        $this->reportModel->getDataHTML($reportID, \RightNow\Utils\Framework::createToken($reportID), $filters, array(), false)->result;
        $this->assertIdentical(self::$hookData['pre_sub_report_check'], null);
    }

    function testPreReportFilterCleanHook()
    {
        $reportID = 15100;
        $filters = array(
            'questions.updated' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'questions.updated',
                    'fltr_id' => 3,
                    'data' => 'invalid',
                    'oper_id' => 4,
                    'report_id' => 15100,
                ),
            ),
            'sort_args' => (object) array(
                'filters' => (object) array(
                    'searchName' => 'sort_args',
                    'report_id' => 15100,
                    'data' => (object) array(
                        'col_id' => 3,
                        'sort_direction' => 1
                    ),
                ),
            ),
            'page' => 1,
        );

        self::$hookData = null;
        $this->setHookReport('pre_report_filter_clean', 'preReportFilterCleanHook');

        $this->reportModel->getDataHTML($reportID, \RightNow\Utils\Framework::createToken($reportID), $filters, array())->result;

        $this->assertIsA(self::$hookData['pre_report_filter_clean']['cleanFilterFunctionsMap'], 'array');
        $this->assertNotNull(self::$hookData['pre_report_filter_clean']['cleanFilterFunctionsMap']['questions.updated']);
        $this->assertNotNull(self::$hookData['pre_report_filter_clean']['cleanFilterFunctionsMap']['comments.updated']);
    }

    function testGetDefaultSortDefinitions()
    {
        $getDefaultSortDefinitions = $this->getMethod('getDefaultSortDefinitions');
        $sortColumnDefinitions = $getDefaultSortDefinitions(15100);
        $this->assertNotNull($sortColumnDefinitions);
        $this->assertIdentical($sortColumnDefinitions[0]["col_id"], 8);
    }

    function testCleanFilterValues() {
        $cleanFiltervalues = $this->getMethod('cleanFilterValues');
        $reportID = 15100;
        $filters = array(
            'questions.updated' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'questions.updated',
                    'fltr_id' => 3,
                    'data' => 'invalid',
                    'oper_id' => 4,
                    'report_id' => 15100,
                ),
            ),
            'comments.updated' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'questions.updated',
                    'fltr_id' => 3,
                    'data' => '01/01/2003|01/07/2003',
                    'oper_id' => 4,
                    'report_id' => 15100,
                ),
            ),
            'page' => 1,
        );
        $validationFunction = function ($filterValue) {
            $dateFormatObj = Text::getDateFormatFromDateOrderConfig();
            $dateValue = Text::validateDateRange($filterValue, $dateFormatObj["short"], "|", false, "90 days");
            return $dateValue;
        };
        $cleanFunctions = array("questions.updated" => $validationFunction,
            "comments.updated" => $validationFunction);
        $filters = $cleanFiltervalues($filters, $cleanFunctions);
        $this->assertTrue(empty($filters["questions.updated"]->filters->data));
        $this->assertTrue(!empty($filters["comments.updated"]->filters->data));
    }

    function testIsValidOrgFilter() {
        $isValidOrgFilter = $this->getMethod('isValidOrgFilter');
        $profile = (object)array('orgID' => 2, 'orgLevel' => 3, 'contactID' => 1286);
        $filters = '{
            "org":{
                "w_id":"OrgList_30",
                "filters":{
                    "rnSearchType":"org",
                    "report_id":156,
                    "searchName":"org",
                    "data":{
                        "val":2,
                        "fltr_id":"incidents.org_id",
                        "oper_id":1,
                        "selected":1
                    }
                }
            },
            "search":1
        }';
        $filters = json_decode($filters);
        $filters = get_object_vars($filters);

        //method should return true for authorized organization access
        $this->assertTrue($isValidOrgFilter($filters, $profile));

        //method should return false for unauthorized organization access
        $filters["org"]->filters->data->val = 3;
        $this->assertFalse($isValidOrgFilter($filters, $profile));

        //method should return true for authorized subsidiary organization access
        $filters["org"]->filters->data->val = 2;
        $filters["org"]->filters->data->fltr_id = "org.lvl3_id";
        $this->assertTrue($isValidOrgFilter($filters, $profile));

        //method should return false for unauthorized subsidiary organization access
        $filters["org"]->filters->data->fltr_id = "org.lvl4_id";
        $this->assertFalse($isValidOrgFilter($filters, $profile));

        //method should return false for unauthorized organization access by modifying filter contact id
        $filters["org"]->filters->data->fltr_id = "incidents.c_id";
        $filters["org"]->filters->data->val = 1285;
        $this->assertFalse($isValidOrgFilter($filters, $profile));
    }

    /**
     * Testcase for the fix 151021-000147
     */
    function testModerationReportsAreHidden() {
        $moderationReportIDs = array(15100, 15101, 15102, 15107, 15108, 15109, 15110, 15111, 15112, 15113,
            15114, 15115, 15116, 15117, 15118, 15119, 15120, 15121, 15122, 15123, 15124, 15125, 15126,
            15127, 15128, 15129, 15130, 15131, 15132, 15133, 15140, 15141, 15142, 15143, 15144, 15145,
            15146, 15147, 15148, 15149, 15150, 15151, 15152, 15153, 15154, 15155);
        foreach($moderationReportIDs as $reportID) {
            $metadata = Sql::_report_get($reportID);
            $this->assertEqual($metadata["ac_type"], 4);
        }
    }

    function setHookReport($hookName, $methodName, $append = true)
    {
        $newHook = array(
            'class'     => 'ReportModelTest',
            'function'  => $methodName,
            'filepath'  => 'Models/tests/Report.test.php',
        );

        $hooks = \RightNow\UnitTest\Helper::getHooks();
        $hooksArray = ($append === true) ? $hooks->getValue() : null;
        if (is_array($hooksArray)) {
            $hooksArray[$hookName] = $newHook;
        }
        else {
            $hooksArray = array($hookName => $newHook);
        }
        $hooks->setValue($hooksArray);
    }

    protected static $hookData;

    static function preReportGetHook($data)
    {
        self::$hookData['pre_report_get'] = $data;
    }

    static function preReportGetDataHook($data)
    {
        self::$hookData['pre_report_get_data'] = $data;
    }

    static function postReportGetDataHook($data)
    {
        self::$hookData['post_report_get_data'] = $data;
        $data['data']['returnData']['search_term'] = 'Test_Search_Term';
    }

    static function preSubReportCheckHook($data)
    {
       self::$hookData['pre_sub_report_check'] = $data;
       return $data;
    }

    static function preReportFilterCleanHook($data)
    {
       self::$hookData['pre_report_filter_clean'] = $data;
       return $data;
    }

}
