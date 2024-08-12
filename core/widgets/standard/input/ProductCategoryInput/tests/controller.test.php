<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestProductCategoryInput extends WidgetTestCase {
    public $testingWidget = "standard/input/ProductCategoryInput";

    function testGetData() {
        $this->createWidgetInstance(array('default_value' => 160, 'name' => 'Incident.Product'));
        $data = $this->getWidgetData();

        $this->assertSame($data['attrs']['default_value'], 160);
        $this->assertSame($data['attrs']['name'], 'Incident.Product');
        $this->assertTrue(is_array($data['js']['hierData']));
        $this->assertTrue(is_array($data['js']['hierData'][4]));
        $this->assertSame(count($data['js']['hierData'][4]), 2);
        $this->assertTrue(is_array($data['js']['hierData'][4][0]));
        $this->assertSame($data['js']['hierData'][4][1]['id'], 160);
        $this->assertSame($data['js']['hierData'][4][1]['selected'], true);
        $this->assertSame($data['js']['hierData'][4][0]['selected'], false);

        $this->setWidgetAttributes(array('default_value' => 77, 'name' => 'Incident.Category'));

        $data = $this->getWidgetData();
        $this->assertSame($data['attrs']['default_value'], 77);
        $this->assertSame($data['attrs']['name'], 'Incident.Category');
        $this->assertTrue(is_array($data['js']['hierData']));
        $this->assertTrue(is_array($data['js']['hierData'][71]));
        $this->assertSame(count($data['js']['hierData'][71]), 3);
        $this->assertTrue(is_array($data['js']['hierData'][71][0]));
        $this->assertSame($data['js']['hierData'][71][0]['id'], 77);
        $this->assertSame($data['js']['hierData'][71][0]['selected'], true);
        $this->assertSame($data['js']['hierData'][71][1]['selected'], false);

        //Selective Access
        $this->logIn('userprodonly');
        $expectedPermissionedIDs = array(0, 129, 120, 138);
        $expectedReadableIDs = array(0 => 0, 1 => 128, 2 => 129, 3 => 130, 4 => 131, 5 => 120, 6 => 138);
        $expectedReadableIDsWithChildren = array(0 => 128, 1 => 129, 2 => 130, 3 => 131);
        $this->clearCache();
        $this->setWidgetAttributes(array('default_value' => '129', 'name' => 'Incident.Product', 'verify_permissions' => 'Create'));
        $data = $this->getWidgetData();
        $this->assertSame($data['js']['hierData'], $this->getExpectedPermissionedHierData());
        $this->assertSame($data['js']['permissionedProdcatIds'], $expectedPermissionedIDs);
        $this->assertSame($data['js']['readableProdcatIds'], $expectedReadableIDs);
        $this->assertSame($data['js']['readableProdcatIdsWithChildren'], $expectedReadableIDsWithChildren);
        $this->logOut();

        //Full Access
        $this->logIn('modactive1');
        $expectedPermissionedIDs = $expectedReadableIDs = $expectedReadableIDsWithChildren = array();
        $this->clearCache();
        $this->setWidgetAttributes(array('default_value' => '129', 'name' => 'Incident.Product', 'verify_permissions' => 'Create'));
        $data = $this->getWidgetData();
        $this->assertSame($data['js']['hierData'], $this->getExpectedNonPermissionedHierData());
        $this->assertSame($data['js']['permissionedProdcatIds'], $expectedPermissionedIDs);
        $this->assertSame($data['js']['readableProdcatIds'], $expectedReadableIDs);
        $this->assertSame($data['js']['readableProdcatIdsWithChildren'], $expectedReadableIDsWithChildren);

        $this->logOut();
    }

    function testGetDefaultChain() {
        $this->createWidgetInstance(array('name' => 'Incident.Product'));
        $method = $this->getWidgetMethod('getDefaultChain');

        $result = $method();
        $this->assertTrue(is_array($result));
        $this->assertSame(count($result), 0);

        $this->setWidgetAttributes(array('default_value' => 10, 'name' => 'Incident.Product'));
        $this->getWidgetData();
        $result = $method();
        $this->assertTrue(is_array($result));
        $this->assertSame(count($result), 3);
        $this->assertSame($result, array(1, 2, 10));

        $this->setWidgetAttributes(array('default_value' => '8', 'name' => 'Incident.Product'));
        $this->getWidgetData();
        $result = $method();
        $this->assertTrue(is_array($result));
        $this->assertSame(count($result), 3);
        $this->assertSame($result, array(1, 2, 8));

        $this->setWidgetAttributes(array('default_value' => 77, 'name' => 'Incident.Category'));
        $this->getWidgetData();
        $result = $method();
        $this->assertTrue(is_array($result));
        $this->assertSame(count($result), 2);
        $this->assertSame($result, array(71, 77));
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
                0 => array(
                    'id' => 0,
                    'label' => 'All Products',
                ),
                1 => array(
                    'id' => 128,
                    'label' => 'p1',
                    'hasChildren' => true,
                    'selected' => false,
                ),
            ),
            128 => array(
                0 => array(
                    'id' => 129,
                    'label' => 'p2',
                    'hasChildren' => true,
                    'selected' => true,
                ),
            ),
            129 => array(
                0 => array(
                    'id' => 130,
                    'label' => 'p3',
                    'hasChildren' => true,
                    'selected' => false,
                ),
            ),
        );
    }

    function getExpectedNonPermissionedHierData() {
        return array(
            0 => array(
                0 => array(
                    'id' => 0,
                    'label' => 'All Products',
                ),
                1 => array(
                    'id' => 1,
                    'label' => 'Mobile Phones',
                    'hasChildren' => true,
                    'selected' => false,
                ),
                2 => array(
                    'id' => 6,
                    'label' => 'Voice Plans',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                3 => array(
                    'id' => 162,
                    'label' => 'Text Messaging',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                4 => array(
                    'id' => 163,
                    'label' => 'Mobile Broadband',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                5 => array(
                    'id' => 7,
                    'label' => 'Replacement/Repair Coverage',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                6 => array(
                    'id' => 128,
                    'label' => 'p1',
                    'hasChildren' => true,
                    'selected' => false,
                ),
            ),
            128 => array(
                0 => array(
                    'id' => 132,
                    'label' => 'p1a',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                1 => array(
                    'id' => 133,
                    'label' => 'p1b',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                2 => array(
                    'id' => 129,
                    'label' => 'p2',
                    'hasChildren' => true,
                    'selected' => true,
                ),
            ),
            129 => array(
                0 => array(
                    'id' => 134,
                    'label' => 'p2a',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                1 => array(
                    'id' => 135,
                    'label' => 'p2b',
                    'hasChildren' => false,
                    'selected' => false,
                ),
                2 => array(
                    'id' => 130,
                    'label' => 'p3',
                    'hasChildren' => true,
                    'selected' => false,
                ),
            ),
        );
    }
}
