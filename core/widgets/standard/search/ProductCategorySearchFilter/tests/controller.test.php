<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

// use a mock controller with a mocked getLinkedID in some tests to avoid filter/link strangeness
class MockWidgetControllerWithMockedGetLinkedIDMethod extends \RightNow\Widgets\ProductCategorySearchFilter {
    public $linkedIDValue;
    public function getLinkedID() {
        return $this->linkedIDValue;
    }
}

// use a mock controller with a mocked getReportFilters in some tests to avoid filter/link strangeness
class MockWidgetControllerWithMockedGetReportFiltersMethod extends \RightNow\Widgets\ProductCategorySearchFilter {
    public $reportFiltersValue;
    public function getReportFilters() {
        return $this->reportFiltersValue;
    }
}

class TestProductCategorySearchFilter extends WidgetTestCase {
    public $testingWidget = "standard/search/ProductCategorySearchFilter";

    function __construct() {
        parent::__construct();
        $this->reflectionClass = new ReflectionClass('RightNow\Widgets\ProductCategorySearchFilter');
        $this->reflectionInstance = $this->reflectionClass->newInstance(array());
    }

    function setUp() {
        // clear out the token processCache
        $reflectionClass = new ReflectionClass('RightNow\Utils\Framework');
        $reflectionProperty = $reflectionClass->getProperty('processCache');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(array());
    }

    function testPrependAllValueNode() {
        $heirValues = array(
            array(
                array(
                    'id' => 1,
                    'label' => 'Mobile Phones'
                ),
                array(
                    'id' => 6,
                    'label' => 'Voice Plans'
                ),
                array(
                    'id' => 162,
                    'label' => 'Text Messaging'
                )
            )
        );

        $this->reflectionClass->getProperty('data')->setValue($this->reflectionInstance, array(
            'attrs' => array(
                'label_all_values' => 'Potato Latkes'
            )
        ));
        $prependAllValueNodeMethod = $this->reflectionClass->getMethod('prependAllValueNode');
        $prependAllValueNodeMethod->setAccessible(true);
        $prependAllValueNodeMethod->invokeArgs($this->reflectionInstance, array(&$heirValues));

        $this->assertEqual(array(
            array(
                array(
                    'id' => null,
                    'label' => 'Potato Latkes'
                ),
                array(
                    'id' => 1,
                    'label' => 'Mobile Phones'
                ),
                array(
                    'id' => 6,
                    'label' => 'Voice Plans'
                ),
                array(
                    'id' => 162,
                    'label' => 'Text Messaging'
                )
            )
        ), $heirValues);
    }

    function testGetTreeData() {
        $this->reflectionClass = new ReflectionClass('MockWidgetControllerWithMockedGetLinkedIDMethod');
        $this->reflectionInstance = $this->reflectionClass->newInstance(array());

        $this->reflectionClass->getProperty('data')->setValue($this->reflectionInstance, array(
            'attrs' => array(
                'label_all_values' => 'All Products'
            )
        ));

        $getTreeDataMethod = $this->reflectionClass->getMethod('getTreeData');
        $getTreeDataMethod->setAccessible(true);

        // test nested product
        $this->reflectionClass->getProperty('linkedIDValue')->setValue($this->reflectionInstance, 129);
        $result = $getTreeDataMethod->invoke($this->reflectionInstance, array(129), 'Product');

        $this->assertEqual(array(
            0 => array(
                array(
                    'id' => 0,
                    'label' => 'All Products'
                ),
                array(
                    'id' => 1,
                    'label' => 'Mobile Phones',
                    'hasChildren' => 1,
                    'selected' => null
                ),
                array(
                    'id' => 6,
                    'label' => 'Voice Plans',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 162,
                    'label' => 'Text Messaging',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 163,
                    'label' => 'Mobile Broadband',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 7,
                    'label' => 'Replacement/Repair Coverage',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 128,
                    'label' => 'p1',
                    'hasChildren' => 1,
                    'selected' => null
                )
            ),
            128 => array(
                array(
                    'id' => 132,
                    'label' => 'p1a',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 133,
                    'label' => 'p1b',
                    'hasChildren' => null,
                    'selected' => null,
                ),
                array(
                    'id' => 129,
                    'label' => 'p2',
                    'hasChildren' => 1,
                    'selected' => 1
                )
            ),
            129 => array(
                array(
                    'id' => 134,
                    'label' => 'p2a',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 135,
                    'label' => 'p2b',
                    'hasChildren' => null,
                    'selected' => null,
                ),
                array(
                    'id' => 130,
                    'label' => 'p3',
                    'hasChildren' => 1,
                    'selected' => null
                )
            )
        ), $result);

        // test non-nested product
        $this->reflectionClass->getProperty('linkedIDValue')->setValue($this->reflectionInstance, 163);
        $result = $getTreeDataMethod->invoke($this->reflectionInstance, array(163), 'Product');

        $this->assertEqual(array(
            0 => array(
                array(
                    'id' => 0,
                    'label' => 'All Products'
                ),
                array(
                    'id' => 1,
                    'label' => 'Mobile Phones',
                    'hasChildren' => 1,
                    'selected' => null
                ),
                array(
                    'id' => 6,
                    'label' => 'Voice Plans',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 162,
                    'label' => 'Text Messaging',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 163,
                    'label' => 'Mobile Broadband',
                    'hasChildren' => null,
                    'selected' => 1
                ),
                array(
                    'id' => 7,
                    'label' => 'Replacement/Repair Coverage',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 128,
                    'label' => 'p1',
                    'hasChildren' => 1,
                    'selected' => null
                )
            )
        ), $result);

        // test nested category
        $this->reflectionClass->getProperty('linkedIDValue')->setValue($this->reflectionInstance, 78);
        $result = $getTreeDataMethod->invoke($this->reflectionInstance, array(78), 'Category');

        $this->assertEqual(array(
            0 => array (
                array (
                    'id' => 0,
                    'label' => 'All Products'
                ),
                array (
                    'id' => 161,
                    'label' => 'Basics',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 153,
                    'label' => 'Mobile Services',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 68,
                    'label' => 'Account and Billing',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 158,
                    'label' => 'Rollover Minutes',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 70,
                    'label' => 'Mobile Broadband',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 71,
                    'label' => 'Troubleshooting',
                    'hasChildren' => 1,
                    'selected' => null
                )
            ),
            71 => array (
                array(
                    'id' => 77,
                    'label' => 'Call Quality',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array(
                    'id' => 78,
                    'label' => 'Connection Issues',
                    'hasChildren' => null,
                    'selected' => 1
                ),
                array(
                    'id' => 79,
                    'label' => 'Batteries',
                    'hasChildren' => null,
                    'selected' => null
                )
            )
        ), $result);

        // test non-nested category
        $this->reflectionClass->getProperty('linkedIDValue')->setValue($this->reflectionInstance, 161);
        $result = $getTreeDataMethod->invoke($this->reflectionInstance, array(161), 'Category');

        $this->assertEqual(array(
            0 => array (
                array (
                    'id' => 0,
                    'label' => 'All Products'
                ),
                array (
                    'id' => 161,
                    'label' => 'Basics',
                    'hasChildren' => null,
                    'selected' => 1
                ),
                array (
                    'id' => 153,
                    'label' => 'Mobile Services',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 68,
                    'label' => 'Account and Billing',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 158,
                    'label' => 'Rollover Minutes',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 70,
                    'label' => 'Mobile Broadband',
                    'hasChildren' => null,
                    'selected' => null
                ),
                array (
                    'id' => 71,
                    'label' => 'Troubleshooting',
                    'hasChildren' => 1,
                    'selected' => null
                )
            )
        ), $result);
    }

    function testGetTreeDataWithInstanceArgs() {
        // test if "No Value" is added
        $this->createWidgetInstance(array('enable_prod_cat_no_value_option' => true));
        $getTreeData = $this->getWidgetMethod('getTreeData');
        $result = $getTreeData(array(129),'Product');
        $this->assertEqual(array (
            0 =>array (
                array (
                    'id' => 0,
                    'label' => 'All Products',
                ),
                array (
                    'id' => -1,
                    'label' => 'No Value',
                    'selected' => false,
                ),
                array (
                    'id' => 1,
                    'label' => 'Mobile Phones',
                    'hasChildren' => true,
                    'selected' => false,
                ),
                array (
                    'id' => 6,
                    'label' => 'Voice Plans',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                array (
                    'id' => 162,
                    'label' => 'Text Messaging',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                array (
                    'id' => 163,
                    'label' => 'Mobile Broadband',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                array (
                    'id' => 7,
                    'label' => 'Replacement/Repair Coverage',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                array (
                    'id' => 128,
                    'label' => 'p1',
                    'hasChildren' => true,
                    'selected' => false,
                ),
            ),
        ), $result);
    }

    function testGetReportFilters() {
        $this->reflectionClass = new ReflectionClass('MockWidgetControllerWithMockedGetReportFiltersMethod');
        $this->reflectionInstance = $this->reflectionClass->newInstance(array());

        $getLinkedIDMethod = $this->reflectionClass->getMethod('getLinkedID');
        $getLinkedIDMethod->setAccessible(true);

        // test for category being searched
        $this->reflectionClass->getProperty('reportFiltersValue')->setValue($this->reflectionInstance, array(
            'searchType' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'searchType',
                    'fltr_id' => null,
                    'data' => null,
                    'oper_id' => null,
                    'report_id' => null,
                ),
                'type' => 'searchType'
            ),
            'keyword' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'keyword',
                    'data' => 'help',
                    'report_id' => null,
                ),
                'type' => 'keyword'
            ),
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => array(null)
                ),
                'type' => null,
                'report_default' => null
            ),
            'c' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => array('71,77')
                ),
                'type' => null,
                'report_default' => null,
            ),
            'page' => 1,
            'per_page' => 4
        ));

        $result = $getLinkedIDMethod->invoke($this->reflectionInstance, 'Category');
        $this->assertEqual($result, 77);

        // test for product being searched
        $this->reflectionClass->getProperty('reportFiltersValue')->setValue($this->reflectionInstance, array(
            'searchType' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'searchType',
                    'fltr_id' => null,
                    'data' => null,
                    'oper_id' => null,
                    'report_id' => null,
                ),
                'type' => 'searchType'
            ),
            'keyword' => (object) array(
                'filters' => (object) array(
                    'rnSearchType' => 'keyword',
                    'data' => 'help',
                    'report_id' => null,
                ),
                'type' => 'keyword'
            ),
            'p' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => array('1,3')
                ),
                'type' => null,
                'report_default' => null
            ),
            'c' => (object) array(
                'filters' => (object) array(
                    'fltr_id' => null,
                    'oper_id' => null,
                    'optlist_id' => null,
                    'report_id' => null,
                    'rnSearchType' => 'menufilter',
                    'data' => array(null)
                ),
                'type' => null,
                'report_default' => null,
            ),
            'page' => 1,
            'per_page' => 4
        ));

        $result = $getLinkedIDMethod->invoke($this->reflectionInstance, 'Product');
        $this->assertEqual($result, 3);
    }


    function testGetProdcatInfoFromPermissionedHierarchies() {
        $prodcatHierarchies = array(
            array(
                'ID'     => 129,
                'Label'  => 'p2',
                'Level1' => 128,
                'Level2' => 129,
                'Level3' => null,
                'Level4' => null,
                'Level5' => null,
                'Level6' => null
            ),
            array(
                'ID'     => 120,
                'Label'  => 'p5',
                'Level1' => 128,
                'Level2' => 129,
                'Level3' => 130,
                'Level4' => 131,
                'Level5' => 120,
                'Level6' => null
            ),
            array(
                'ID'     => 138,
                'Label'  => 'p3b',
                'Level1' => 128,
                'Level2' => 129,
                'Level3' => 130,
                'Level4' => 138,
                'Level5' => null,
                'Level6' => null
            )
        );

        $this->reflectionClass->getProperty('data')->setValue($this->reflectionInstance, array(
            'attrs' => array(
                'verify_permissions' => true
            )
        ));

        $this->logIn('userprodonly');

        $getProdcatInfoFromPermissionedHierarchiesMethod = $this->reflectionClass->getMethod('getProdcatInfoFromPermissionedHierarchies');
        $getProdcatInfoFromPermissionedHierarchiesMethod->setAccessible(true);
        $result = $getProdcatInfoFromPermissionedHierarchiesMethod->invoke($this->reflectionInstance, $prodcatHierarchies);

        $this->assertEqual(array(
            array(128, 129, 130, 131, 120, 138),
            array(128, 129, 130, 131)
        ), $result);

        $this->logOut();
    }

    function testUpdateProdcatsForReadPermissions() {
        // Test case when user has specific read perms
        $prodcats = array(
            array(
                array(
                    'id'          => 1,
                    'label'       => 'Mobile Phones',
                    'hasChildren' => 1,
                    'selected'    => null
                ),
                array(
                    'id'          => 6,
                    'label'       => 'Voice Plans',
                    'hasChildren' => null,
                    'selected'    => null
                ),
                array(
                    'id'          => 162,
                    'label'       => 'Text Messaging',
                    'hasChildren' => null,
                    'selected'    => null
                ),
                array(
                    'id'          => 163,
                    'label'       => 'Mobile Broadband',
                    'hasChildren' => null,
                    'selected'    => null
                ),
                array(
                    'id'          => 7,
                    'label'       => 'Replacement/Repair Coverage',
                    'hasChildren' => null,
                    'selected'    => null
                ),
                array(
                    'id'          => 128,
                    'label'       => 'p1',
                    'hasChildren' => 1,
                    'selected'    => null
                )
            )
        );
        $readableProdcatIds = array(128, 129, 130, 131, 120, 138);
        $readableProdcatIdsWithChildren = array(128, 129, 130, 131);

        $this->reflectionClass->getProperty('data')->setValue($this->reflectionInstance, array(
            'attrs' => array(
                'verify_permissions' => true
            )
        ));

        $this->logIn('userprodonly');

        $updateProdcatsForReadPermissionsMethod = $this->reflectionClass->getMethod('updateProdcatsForReadPermissions');
        $updateProdcatsForReadPermissionsMethod->setAccessible(true);
        $updateProdcatsForReadPermissionsMethod->invokeArgs($this->reflectionInstance, array(&$prodcats, $readableProdcatIds, $readableProdcatIdsWithChildren));

        $this->assertEqual(array(
            array(
                '5' => array(
                    'id'          => 128,
                    'label'       => 'p1',
                    'hasChildren' => 1,
                    'selected'    => null
                )
            )
        ), $prodcats);

        $this->logOut();

        // Ensure initial hasChildren values are set
        $readableProdcatIds = array(128);
        $readableProdcatIdsWithChildren = array();

        $updateProdcatsForReadPermissionsMethod->invokeArgs($this->reflectionInstance, array(&$prodcats, $readableProdcatIds, $readableProdcatIdsWithChildren));

        $this->assertEqual(array(
            array(
                '5' => array(
                    'id'          => 128,
                    'label'       => 'p1',
                    'hasChildren' => null,
                    'selected'    => null
                )
            )
        ), $prodcats);
    }

    function testGetFormattedChainNoFilter() {
        $this->createWidgetInstance();
        $getFormattedChainMethod = $this->reflectionClass->getMethod('getFormattedChain');
        $getFormattedChainMethod->setAccessible(true);

        $chain = $getFormattedChainMethod->invoke($this->widgetInstance);
        $this->assertIsA($chain, 'Array');
        $this->assertTrue(count($chain) === 0);
    }

    function testGetFormattedChainSingleFilter() {
        $this->createWidgetInstance();
        $getFormattedChainMethod = $this->reflectionClass->getMethod('getFormattedChain');
        $getFormattedChainMethod->setAccessible(true);

        // set the filter value to 2 (Mobile Phones > Android)
        $dataProperty = $this->reflectionClass->getProperty('data');
        $data = $dataProperty->getValue($this->widgetInstance);
        $data['js']['filter'] = array('value' => "2");
        $dataProperty->setValue($this->widgetInstance, $data);

        $chain = $getFormattedChainMethod->invoke($this->widgetInstance);
        $this->assertIsA($chain, 'Array');
        $this->assertTrue(count($chain) > 0);
        $this->assertSame($chain[0]['id'], 1);
        $this->assertSame($chain[1]['id'], 2);
    }
    function testGetFormattedChainNoValueFilter() {
        $this->createWidgetInstance(array('enable_prod_cat_no_value_option' => true));
        $getFormattedChainMethod = $this->reflectionClass->getMethod('getFormattedChain');
        $getFormattedChainMethod->setAccessible(true);

        // set the filter value to -1 (No Value)
        $dataProperty = $this->reflectionClass->getProperty('data');
        $data = $dataProperty->getValue($this->widgetInstance);
        $data['js']['filter'] = array('value' => -1);
        $dataProperty->setValue($this->widgetInstance, $data);

        $chain = $getFormattedChainMethod->invoke($this->widgetInstance);
        $this->assertIsA($chain, 'Array');
        $this->assertTrue(count($chain) > 0);
        $this->assertSame($chain[0]['id'], -1);
    }

    function testGetFormattedChainNestedFilter() {
        $this->createWidgetInstance();
        $getFormattedChainMethod = $this->reflectionClass->getMethod('getFormattedChain');
        $getFormattedChainMethod->setAccessible(true);

        // set the filter value to 1,2 (Mobile Phones > Android)
        $dataProperty = $this->reflectionClass->getProperty('data');
        $data = $dataProperty->getValue($this->widgetInstance);
        $data['js']['filter'] = array('value' => "1,2");
        $dataProperty->setValue($this->widgetInstance, $data);

        $chain = $getFormattedChainMethod->invoke($this->widgetInstance);
        $this->assertIsA($chain, 'Array');
        $this->assertTrue(count($chain) > 0);
        $this->assertSame($chain[0]['id'], 1);
        $this->assertSame($chain[1]['id'], 2);
    }

    function testGetFormattedChainNoPermission() {
        $this->createWidgetInstance();
        $getFormattedChainMethod = $this->reflectionClass->getMethod('getFormattedChain');
        $getFormattedChainMethod->setAccessible(true);

        // set the filter value to 1,2 (Mobile Phones > Android) and readableProdcatIds to something else
        $dataProperty = $this->reflectionClass->getProperty('data');
        $data = $dataProperty->getValue($this->widgetInstance);
        $data['js']['filter'] = array('value' => "1,2");
        $data['js']['readableProdcatIds'] = array(42);
        $dataProperty->setValue($this->widgetInstance, $data);

        $chain = $getFormattedChainMethod->invoke($this->widgetInstance);
        $this->assertIsA($chain, 'Array');
        $this->assertTrue(count($chain) === 0);
    }

    function testIsChainReadable() {
        $this->createWidgetInstance();
        $isChainReadable = $this->reflectionClass->getMethod('isChainReadable');
        $isChainReadable->setAccessible(true);

        // set readableProdcatIds
        $dataProperty = $this->reflectionClass->getProperty('data');
        $data = $dataProperty->getValue($this->widgetInstance);
        $data['js']['readableProdcatIds'] = array(1, 2, 3);
        $dataProperty->setValue($this->widgetInstance, $data);

        $chain = array(
            array(
                'ID' => 1,
                'label' => 'Walrus',
            ),
            array(
                'ID' => 2,
                'label' => 'Shoes',
            ),
        );
        $this->assertTrue($isChainReadable->invoke($this->widgetInstance, $chain));

        $data['js']['readableProdcatIds'] = array(42);
        $dataProperty->setValue($this->widgetInstance, $data);

        $this->assertFalse($isChainReadable->invoke($this->widgetInstance, $chain));
    }
}
