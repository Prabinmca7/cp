<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestMobileProductCategoryInput extends WidgetTestCase {
    public $testingWidget = "standard/input/MobileProductCategoryInput";

    function testFirstLevelDataIsStillRetrievedIfIDSpecifiedInURLDoesNotExist() {
        $this->addUrlParameters(array("incidents.prod" => '999999,11123131'));
        $this->createWidgetInstance(array('name' => 'Incident.Product'));

        $data = $this->getWidgetData();
        $this->assertTrue(count($data['firstLevel']) > 1);

        $this->restoreUrlParameters();
    }

    function testDefaultValueIsHonored() {
        $this->createWidgetInstance(array('name' => 'Incident.Product', 'default_value' => 1));
        $data = $this->getWidgetData();
        $this->assertTrue(count($data['firstLevel']) > 1);
        $this->assertTrue($data['firstLevel'][1]['selected']);
    }

    function testPermissions() {
        //Selective Access
        $this->logIn('userprodonly');
        $expectedPermissionedIDs = array(0, 129, 120, 138);
        $expectedReadableIDs = array(0 => 128, 1 => 129, 2 => 130, 3 => 131, 4 => 120, 5 => 138);
        $expectedReadableIDsWithChildren = array(0 => 128, 1 => 129, 2 => 130, 3 => 131);
        $this->clearCache();
        $this->setWidgetAttributes(array('default_value' => '129', 'name' => 'Incident.Product', 'verify_permissions' => 'Create'));
        $data = $this->getWidgetData();
        $this->assertSame($data['firstLevel'], $this->getExpectedPermissionedHierData());
        $this->assertSame($data['js']['permissionedProdcatIds'], $expectedPermissionedIDs);
        $this->assertSame($data['js']['readableProdcatIds'], $expectedReadableIDs);
        $this->assertSame($data['js']['readableProdcatIdsWithChildren'], $expectedReadableIDsWithChildren);
        $this->assertSame($data['js']['initial'], $this->getExpectedPermissionedInitialData());
        $this->logOut();

        //Full Access
        $this->logIn('modactive1');
        $expectedPermissionedIDs = $expectedReadableIDs = $expectedReadableIDsWithChildren = array();
        $this->clearCache();
        $this->setWidgetAttributes(array('default_value' => '139', 'name' => 'Incident.Product', 'verify_permissions' => 'Create'));
        $data = $this->getWidgetData();
        $this->assertSame($data['firstLevel'], $this->getExpectedNonPermissionedHierData());
        $this->assertSame($data['js']['permissionedProdcatIds'], $expectedPermissionedIDs);
        $this->assertSame($data['js']['readableProdcatIds'], $expectedReadableIDs);
        $this->assertSame($data['js']['readableProdcatIdsWithChildren'], $expectedReadableIDsWithChildren);
        $this->assertSame($data['js']['initial'], $this->getNonPermissionedInitialData());

        $this->logOut();
    }

    function clearCache() {
        $cacheKeys = array('getReportData10163a:5:{s:10:"param_args";a:0:{}s:11:"search_args";a:2:{s:13:"search_field0";a:3:{s:4:"name";i:1;s:4:"oper";i:1;s:3:"val";i:35;}s:13:"search_field1";a:3:{s:4:"name";i:2;s:4:"oper";i:1;s:3:"val";i:1335;}}s:9:"sort_args";N;s:10:"limit_args";a:2:{s:9:"row_limit";i:2147483647;s:9:"row_start";i:0;}s:10:"count_args";a:2:{s:13:"get_row_count";i:1;s:19:"get_node_leaf_count";i:1;}}',
            'securityToken10163',
            'reportDef_10163',
            'getRuntimeFilters10163',
            'getTableAlias10163table9',
            'getTableAlias10163table1',
            'getTableAlias10163table542',
            'getTableAlias10163table311',
            'reportHeaders10163',
            'reportVisHeaders10163',
            'getFormattedData10163912586825',
            'getFormattedAid10163912586825',
            'topicWords-');
        foreach ($cacheKeys as $key) {
            RightNow\Utils\Framework::removeCache($key, null);
        }
    }

    function getExpectedPermissionedHierData() {
        return array(
            0 => array(
                'id' => 128,
                'label' => 'p1',
                'hasChildren' => true,
                'selected' => true,
            ),
        );
    }

    function getExpectedNonPermissionedHierData() {
        return array(
            0 => array(
                'id' => 0,
                'label' => 'All Products',
            ),
            1 => array(
                'id' => 1,
                'label' => 'Mobile Phones',
                'hasChildren' => true,
            ),
            2 => array(
                'id' => 6,
                'label' => 'Voice Plans',
                'hasChildren' => false,
            ),
            3 => array(
                'id' => 162,
                'label' => 'Text Messaging',
                'hasChildren' => false,
            ),
            4 => array(
                'id' => 163,
                'label' => 'Mobile Broadband',
                'hasChildren' => false,
            ),
            5 => array(
                'id' => 7,
                'label' => 'Replacement/Repair Coverage',
                'hasChildren' => false,
            ),
            6 => array(
                'id' => 128,
                'label' => 'p1',
                'hasChildren' => true,
                'selected' => true,
            ),
        );
    }

    function getExpectedPermissionedInitialData() {
        return array(
            0 => array(
                'id' => 128,
                'label' => 'p1',
                'hasChildren' => true,
                'selected' => true,
            ),
            1 => array(
                'id' => 129,
                'label' => 'p2',
            ),
        );
    }

    function getNonPermissionedInitialData() {
        return array(
            0 => array(
                'id' => 128,
                'label' => 'p1',
                'hasChildren' => true,
                'selected' => true,
            ),
            1 => array(
                'id' => 129,
                'label' => 'p2',
            ),
            2 => array(
                'id' => 130,
                'label' => 'p3',
            ),
            3 => array(
                'id' => 131,
                'label' => 'p4',
            ),
            4 => array(
                'id' => 139,
                'label' => 'p4a',
            ),
        );
    }
}

class TestMobileProductCategoryInputWithProductLinkingEnabled extends WidgetTestCase {
    public $testingWidget = "standard/input/MobileProductCategoryInput";
    private static $model;

    function __construct() {
        parent::__construct();

        $this->makeMocks();
        $this->setMockProdCatModel();
        $this->setMockController();
    }

    function makeMocks() {
        if (!class_exists('\RightNow\Models\MockProdcat')) {
            Mock::generate('\RightNow\Models\Prodcat', '\RightNow\Models\MockProdcat');
        }
        if (!class_exists('\RightNow\Controllers\MockBase')) {
            Mock::generate('\RightNow\Controllers\Base', '\RightNow\Controllers\MockBase', array('getProfileData'));
        }
    }

    function setMockProdCatModel() {
        self::$model = new \RightNow\Models\MockProdCat();
        self::$model->returns('getLinkingMode', true);
        self::$model->returns('getDirectDescendants', $this->CI->model('Prodcat')->getDirectDescendants('Category'));
    }

    function setMockController() {
        $this->mockCI = new \RightNow\Controllers\MockBase();
        $this->mockCI->setReturnValue('model', self::$model, array('Prodcat'));
        $this->mockCI->input = $this->CI->input;
    }

    function testNestedCategoriesAreRetrievedWhenLinkingIsEnabled() {
        // Linking is enabled but no product is selected:
        // Categories should be retrieved normally.

        self::$model->returns('getDefaultProductID', 0);
        self::$model->returnsAt(0, 'getFormattedChain', (object) array(
            'result' => array(71, 77),
        ));
        self::$model->returnsAt(1, 'getFormattedChain', (object) array(
            'result' => array(
                array(
                    'id' => 71,
                    'label' => 'Troubleshooting',
                ),
                array(
                    'id' => 77,
                    'label' => 'Call Quality',
                ),
            ),
        ));
        self::$model->expectCallCount('getFormattedChain', 2);
        self::$model->expectNever('getLinkedCategories');

        $this->addUrlParameters(array('incidents.cat' => '77'));
        $this->createWidgetInstance(array('name' => 'Incident.Category'));
        $data = $this->getWidgetData();
        $this->assertSame(2, count($data['js']['initial']));
        $last = end($data['js']['initial']);
        $this->assertIdentical(77, $last['id']);

        $this->restoreUrlParameters();
    }
}
