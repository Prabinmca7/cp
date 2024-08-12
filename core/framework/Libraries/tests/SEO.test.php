<?php

use RightNow\Utils\Config;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SEOTest extends CPTestCase
{
    public $testingClass = 'RightNow\Libraries\SEO';
    private $mockAnswers;
    private $mockIncidents;
    private $mockProducts;
    private $mockQuestions;
    private $mockCategories;
    private $mockAssets;
    private $mockSocialUsers;
    private $initialConfigValues = array();

    function __construct()
    {
        parent::__construct();

        // Add test points of the form answerID => textTitle
        $this->mockAnswers = array (
            3 => 'MOCK - Do you have service area maps?',
            13 => 'MOCK - What is a dropped call?',
            33 => array('input' => 'MOCK - <html>hack', 'expected' => 'MOCK - hack'),
            83 => 'MOCK - <dropped &?',

            // Test for invalid answer ID's
            500000000 => Config::getMessage(ANSWER_LBL),  // missing
            -10 => Config::getMessage(ANSWER_LBL),        // invalid
            'asdf' => Config::getMessage(ANSWER_LBL),     // alpha
            '123foo' => Config::getMessage(ANSWER_LBL),   // alphanumeric
        );

        // Add test points of the form incidentID => textTitle. This is NOT ref_no!
        $this->mockIncidents = array (
            2 => 'MOCK - Do you cover areas in Hawaii?',
            10 => 'MOCK - Are there long distance charges for my calling plan?',
            57 => 'MOCK - "\'hey',
            68 => array('input' => 'MOCK - <busted></busted>', 'expected' => 'MOCK - '),

            // Test for invalid incident ID's
            10000000 => Config::getMessage(VIEW_QUESTION_HDG),  // missing
            -1 => Config::getMessage(VIEW_QUESTION_HDG),        // invalid
            'asdf' => Config::getMessage(VIEW_QUESTION_HDG),    // alpha
            '123foo' => Config::getMessage(VIEW_QUESTION_HDG),  // alphanumeric
        );

        $this->mockQuestions = array (
            12 => 'MOCK - WAT?',
            37 => 'MOCK - "\'hey',
            58 => array('input' => 'MOCK - <busted></busted>', 'expected' => 'MOCK - '),

            // Test for invalid incident ID's
            1000000 => Config::getMessage(VIEW_QUESTION_HDG),  // missing
            -1 => Config::getMessage(VIEW_QUESTION_HDG),        // invalid
            'adsfsdf' => Config::getMessage(VIEW_QUESTION_HDG),    // alpha
            '12o' => Config::getMessage(VIEW_QUESTION_HDG),  // alphanumeric
        );

        $this->mockProductsAndCategories = array(
            47 => 'MOCK - bananas product',
            23 => 'MOCK - bananas category',
            33 => 'MOCK - <>bananas category',
        );

        // Add test points of the form assetID => textTitle.
        $this->mockAssets = array (
            2 => 'MOCK - HP Printer 1234',
            10 => 'MOCK - Casio G-Shock 4',

            // Test for invalid asset ID's
            10000000 => \RightNow\Utils\Config::getMessage(VIEW_ASSET_CMD),  // missing
            -1 => \RightNow\Utils\Config::getMessage(VIEW_ASSET_CMD),        // invalid
            'asdf' => \RightNow\Utils\Config::getMessage(VIEW_ASSET_CMD),    // alpha
            '123foo' => \RightNow\Utils\Config::getMessage(VIEW_ASSET_CMD),  // alphanumeric
        );

        $this->mockSocialUsers = array(
            0 => array('input' => null, 'expected' => 'Public Profile'),
            12 => array('input' => 'useractive1', 'expected' => 'useractive1 - profile'),
            15 => array('input' => 'moderator', 'expected' => 'moderator - profile'),
        );
    }

    public function setUp()
    {
        $this->initialConfigValues['OKCS_API_TIMEOUT'] = \Rnow::getConfig(OKCS_API_TIMEOUT);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        $this->initialConfigValues['OKCS_SRCH_API_URL'] = \Rnow::getConfig(OKCS_SRCH_API_URL);
        \Rnow::updateConfig('OKCS_SRCH_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsSrt/api/', true);
        $this->initialConfigValues['OKCS_IM_API_URL'] = \Rnow::getConfig(OKCS_IM_API_URL);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->setupMockController();
        parent::setUp();
    }

    public function tearDown()
    {
        foreach ($this->initialConfigValues as $config => $value) {
            \Rnow::updateConfig($config, $value, true);
        }
        parent::tearDown();
    }

    public function validateTitles($testCases, $recordName, array $args = array()) {
        foreach ($testCases as $id => $title) {
            $this->assertEqual(\RightNow\Libraries\SEO::getDynamicTitle($recordName, $id, $args), is_array($title) ? $title['expected'] : $title);
        }
    }

    public function validateRawTitles($testCases, $recordName) {
        foreach ($testCases as $id => $title) {
            $this->assertEqual(\RightNow\Libraries\SEO::getRecordTitle($recordName, $id), is_array($title) ? $title['input'] : $title);
        }
    }

    public function extractHrefFromLinkTag($tag) {
        $matches = array();
        preg_match("/<link rel='canonical' href='(.*)'\/>/", $tag, $matches);

        return $matches[1];
    }

    public function testGetDynamicTitle()
    {
        $this->validateTitles($this->mockAnswers, 'answer');
        $this->validateTitles($this->mockIncidents, 'Incidents');
        $this->validateTitles($this->mockProductsAndCategories, 'PRODUCTS');
        $this->validateTitles($this->mockProductsAndCategories, 'category');
        $this->validateTitles($this->mockAssets, 'asset');
        $this->validateTitles($this->mockSocialUsers, 'PublicProfile', array('suffix' => ' - profile'));
        $this->validateTitles(array(15 => 'moderator'), 'PublicProfile');
    }

    public function testGetRecordTitle()
    {
        $this->validateRawTitles($this->mockAnswers, 'answerandquestion', true);
        $this->validateRawTitles($this->mockIncidents, 'Incident or something', true);
        $this->validateRawTitles($this->mockProductsAndCategories, 'PRODUCTS', true);
        $this->validateRawTitles($this->mockProductsAndCategories, 'category', true);
        $this->validateRawTitles($this->mockProductsAndCategories, 'categories', true);
        $this->validateRawTitles($this->mockAssets, 'assets', true);
    }

    public function testExceptionThrownWhenRequestingInvalidRecordType() {
        $this->expectException('Exception');

        \RightNow\Libraries\SEO::getRecordTitle('somethingInvalid', 32);
    }

    public function testGetCanonicalAnswerUrl()
    {
        $siteURL = \RightNow\Utils\Url::getShortEufAppUrl('sameAsCurrentPage', Config::getConfig(CP_ANSWERS_DETAIL_URL));

        // Add test points of the form answerID => canonicalTitle
        $testPoints = array (
            3 => 'mock---do-you-have-service-area-maps%3F',
            13 => 'mock---what-is-a-dropped-call%3F',

            // Test for invalid answer ID's
            500000000 => strtolower(Config::getMessage(ANSWER_LBL)),
            -10 => strtolower(Config::getMessage(ANSWER_LBL)),
        );

        // Test the array
        foreach ($testPoints as $answerID => $title)
        {
            $this->assertEqual(\RightNow\Libraries\SEO::getCanonicalAnswerURL($answerID),
                $siteURL .
                '/a_id/' . $answerID .
                '/~/' . $title);
        }
    }

    public function testGetCanonicalQuestionUrl()
    {
        $siteURL = \RightNow\Utils\Url::getShortEufAppUrl('sameAsCurrentPage', 'social/questions/detail');
        $defaultLabel = strtolower(str_replace(' ', '-', Config::getMessage(VIEW_QUESTION_HDG)));

        // Add test points of the form questionID => canonicalTitle
        $testPoints = array (
            12 => 'mock---wat%3F',
            37 => 'mock---hey',

            // Test for invalid answer ID's
            500000000 => $defaultLabel,
            -10 => $defaultLabel,
        );

        // Test the array
        foreach ($testPoints as $questionID => $title)
        {
            $this->assertEqual(\RightNow\Libraries\SEO::getCanonicalQuestionURL($questionID),
                "{$siteURL}/qid/$questionID/~/$title");
        }
    }

    public function testGetCanonicalLinkTagForAnswers()
    {
        $this->assertNull(\RightNow\Libraries\SEO::getCanonicalLinkTag());

        $CI = new \RightNow\Controllers\MockBase();
        $CI->returns('model', $this->createAnswerModel(), array('Answer'));
        $CI->page = Config::getConfig(CP_ANSWERS_DETAIL_URL);
        \RightNow\Libraries\SEO::setMockController($CI);

        // No answer id parameter.
        $this->assertNull(\RightNow\Libraries\SEO::getCanonicalLinkTag());

        // Falsy answer id parameter.
        $this->addUrlParameters(array('a_id' => 0));
        $this->assertNull(\RightNow\Libraries\SEO::getCanonicalLinkTag());
        $this->restoreUrlParameters();

        // Answer doesn't exist.
        $this->addUrlParameters(array('a_id' => 1));
        $result = \RightNow\Libraries\SEO::getCanonicalLinkTag();
        $result = $this->extractHrefFromLinkTag($result);
        $this->assertIsA($result, 'string');
        $this->assertEndsWith($result,
            Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/1/~/' . strtolower(Config::getMessage(ANSWER_LBL)));
        $this->restoreUrlParameters();

        // Legit Answer.
        $this->addUrlParameters(array('a_id' => 3));
        $result = \RightNow\Libraries\SEO::getCanonicalLinkTag();
        $result = \RightNow\Libraries\SEO::getCanonicalLinkTag();
        $result = $this->extractHrefFromLinkTag($result);
        $this->assertIsA($result, 'string');
        $this->assertPattern('/\/a_id\/3\/~\/[a-z-A-Z0-9%]+$/', $result);
        $this->assertStringContains($result, 'mock');
        $this->restoreUrlParameters();

        \RightNow\Libraries\SEO::setMockController(null);
    }

    public function testGetCanonicalLinkTagForQuestions()
    {
        $CI = new \RightNow\Controllers\MockBase();
        $CI->returns('model', $this->createQuestionModel(), array('CommunityQuestion'));
        $CI->page = 'social/questions/detail';
        \RightNow\Libraries\SEO::setMockController($CI);

        // No question id parameter.
        $this->assertNull(\RightNow\Libraries\SEO::getCanonicalLinkTag());

        // Falsy question id parameter.
        $this->addUrlParameters(array('qid' => 0));
        $this->assertNull(\RightNow\Libraries\SEO::getCanonicalLinkTag());
        $this->restoreUrlParameters();

        // Question doesn't exist.
        $this->addUrlParameters(array('qid' => 1));
        $result = \RightNow\Libraries\SEO::getCanonicalLinkTag();
        $result = $this->extractHrefFromLinkTag($result);
        $this->assertIsA($result, 'string');
        $this->assertEndsWith($result,
            'social/questions/detail/qid/1/~/' . strtolower(str_replace(' ', '-', Config::getMessage(VIEW_QUESTION_HDG))));
        $this->restoreUrlParameters();

        // Legit Question.
        $this->addUrlParameters(array('qid' => 12));
        $result = \RightNow\Libraries\SEO::getCanonicalLinkTag();
        $result = $this->extractHrefFromLinkTag($result);
        $this->assertIsA($result, 'string');
        $this->assertPattern('/\/qid\/12\/~\/[a-z-A-Z0-9%]+$/', $result);
        $this->assertStringContains($result, 'mock');
        $this->restoreUrlParameters();

        \RightNow\Libraries\SEO::setMockController(null);
    }

    public function testGetProductTitle()
    {
        \RightNow\Libraries\SEO::setMockController(get_instance());

        $method = $this->getMethod('getProductTitle');
        $defaultLabel = Config::getMessage(PRODUCT_LBL);

        $result = $method('1');
        $this->assertIsA($result, 'string');
        $this->assertNotEqual($defaultLabel, $result);

        $result = $method('1,2');
        $this->assertIsA($result, 'string');
        $this->assertNotEqual($defaultLabel, $result);

        $result = $method('bananas');
        $this->assertIsA($result, 'string');
        $this->assertEqual($defaultLabel, $result);
    }

    public function testGetCategoryTitle()
    {
        \RightNow\Libraries\SEO::setMockController(get_instance());

        $method = $this->getMethod('getCategoryTitle');
        $defaultLabel = Config::getMessage(CATEGORY_LBL);

        $result = $method('161');
        $this->assertIsA($result, 'string');
        $this->assertNotEqual($defaultLabel, $result);

        $result = $method('71,78');
        $this->assertIsA($result, 'string');
        $this->assertNotEqual($defaultLabel, $result);

        $result = $method('bananas');
        $this->assertIsA($result, 'string');
        $this->assertEqual($defaultLabel, $result);
    }

    public function testGetOkcsBrowsePageTitle()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        $method = $this->getMethod('getDynamicTitle');
        $result = $method('answer', '1000002');
        $this->assertIsA($result, 'string');
        //$this->assertEqual(" INVESTMENT PROOF SUBMISSION GUIdELINES FOR THE FY 2014-2015", $result);
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    public function testGetOkcsSearchPageTitle()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        $this->addUrlParameters(array('s' => '16777218_60498436955E4120FAA157', 'prTxnId' => '835929468', 'txnId' => '835929468'));
        $method = $this->getMethod('getDynamicTitle');
        $result = $method('answer', '1000002');
        $this->assertIsA($result, 'string');
        //$this->assertEqual(" INVESTMENT PROOF SUBMISSION GUIdELINES FOR THE FY 2014-2015", $result);
        $this->restoreUrlParameters();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    private function setupMockController()
    {
        static $visited = false;
        if ($visited) return;

        if (!class_exists('\RightNow\Controllers\MockBase')) {
            Mock::generate('\RightNow\Controllers\Base', '\RightNow\Controllers\MockBase');
        }
        $CI = new \RightNow\Controllers\MockBase();
        $CI->returns('model', $this->createAnswerModel(), array('Answer'));
        $CI->returns('model', $this->createIncidentModel(), array('Incident'));
        $CI->returns('model', $this->createQuestionModel(), array('CommunityQuestion'));
        $CI->returns('model', $this->createProdcatModel(), array('Prodcat'));
        $CI->returns('model', $this->createAssetModel(), array('Asset'));
        $CI->returns('model', $this->createSocialUserModel(), array('CommunityUser'));

        \RightNow\Libraries\SEO::setMockController($CI);

        $visited = true;
    }

    private function createAssetModel() {
        return $this->createModel('Asset', $this->mockAssets, 'Name');
    }

    private function createSocialUserModel() {
        return $this->createModel('CommunityUser', $this->mockSocialUsers, 'DisplayName');
    }

    private function createIncidentModel() {
        return $this->createModel('Incident', $this->mockIncidents, 'Subject');
    }

    private function createAnswerModel() {
        return $this->createModel('Answer', $this->mockAnswers, 'Summary');
    }

    private function createQuestionModel() {
        return $this->createModel('CommunityQuestion', $this->mockQuestions, 'Subject');
    }

    private function createProdcatModel() {
        return $this->createModel('Prodcat', $this->mockProductsAndCategories, 'LookupName');
    }

    private function createModel($name, $mockValues, $titleField) {
        $mockName = "Mock{$name}";

        if (!class_exists("\\RightNow\\Models\\{$mockName}")) {
            require_once(CPCORE . "Models/{$name}.php");
            Mock::generate("\\RightNow\\Models\\{$name}", "\\RightNow\\Models\\$mockName");
        }
        $class = "\\RightNow\\Models\\{$mockName}";
        $model = new $class;

        $values = array();
        foreach ($mockValues as $id => $summary) {
            $values[$id] = (object) array('result' => (object) array(
                'ID'        => $id,
                $titleField => (is_array($summary)) ? $summary['input'] : $summary,
            ));
            $model->setReturnReference('get', $values[$id], array($id));
        }
        $model->setReturnValue('get', (object) array('result' => null));

        return $model;
    }
}
