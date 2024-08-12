<?php

use RightNow\Api,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\UnitTest\Helper as TestHelper;

TestHelper::loadTestedFile(__FILE__);

class ProdcatTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Prodcat';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\Prodcat();
        $this->productIDs = array(1, 2, 3, 4, 6, 7, 8, 9, 10, 120, 121, 128, 129, 130, 131, 132, 133, 134, 135, 137, 138, 139, 140, 141, 142, 159, 160, 162, 163);
        $this->categoryIDs = array(68, 70, 71, 77, 78, 79, 122, 123, 124, 125, 126, 127, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 158, 161);
        $this->interfaceID = \RightNow\Api::intf_id();
    }

    function testGet() {
        $this->assertResponseObject($this->model->get(9999), 'is_null', 1);
        $this->assertResponseObject($this->model->get(null), 'is_null', 1);

        foreach ($this->productIDs as $id) {
            $response = $this->model->get($id);
            $this->assertResponseObject($response, 'RightNow\Connect\v1_4\ServiceProduct');
        }
        foreach ($this->categoryIDs as $id) {
            $response = $this->model->get($id);
            $this->assertResponseObject($response, 'RightNow\Connect\v1_4\ServiceCategory');
        }
    }

    function testGetPermissionedList() {
        $testUsers = function($expectedLimitedList, $that, $jsonDecode = true) {
            // pass in jsonDecode parameter to make it easier to output results without json decoding automatically
            $url = "/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/getPermissionedListResults";
            $result = TestHelper::makeRequest($url);
            if ($jsonDecode)
                $result = json_decode($result, true);
            $that->assertTrue($result['read'], "%s while verifying anonymous");
            $that->assertNull($result['create'], "%s while verifying anonymous");
            $that->assertNull($result['update_avatar'], "%s while verifying anonymous");
            $that->assertNull($result['nonsense'], "%s while verifying anonymous");

            $result = TestHelper::makeRequest("$url/slatest");
            if ($jsonDecode)
                $result = json_decode($result, true);
            $that->assertTrue($result['read'], "%s while verifying slatest");
            $that->assertTrue($result['create'], "%s while verifying slatest");
            $that->assertTrue($result['update_avatar'], "%s while verifying slatest");
            $that->assertNull($result['nonsense'], "%s while verifying slatest");

            $result = TestHelper::makeRequest("$url/useractive1");
            if ($jsonDecode)
                $result = json_decode($result, true);
            $that->assertTrue($result['read'], "%s while verifying useractive1");
            $that->assertTrue($result['create'], "%s while verifying useractive1");
            $that->assertTrue($result['update_avatar'], "%s while verifying useractive1");
            $that->assertNull($result['nonsense'], "%s while verifying useractive1");

            $result = TestHelper::makeRequest("$url/user_data_conditions_1314");
            if ($jsonDecode)
                $result = json_decode($result, true);
            $that->assertIdentical($result['read'], $expectedLimitedList, "%s while verifying user_data_conditions_1314 with list " . var_export($expectedLimitedList, true));
            $that->assertIdentical($result['create'], $expectedLimitedList, "%s while verifying user_data_conditions_1314 with list " . var_export($expectedLimitedList, true));
            $that->assertIdentical($result['update_avatar'], $expectedLimitedList, "%s while verifying user_data_conditions_1314 with list " . var_export($expectedLimitedList, true));
            $that->assertNull($result['nonsense'], "%s while verifying user_data_conditions_1314");
        };

        $products = array(
            array('ID' => 1, 'Label' => 'Mobile Phones', 'Level1' => 1, 'Level2' => NULL, 'Level3' => NULL, 'Level4' => NULL, 'Level5' => NULL, 'Level6' => NULL),
            array('ID' => 2, 'Label' => 'Android', 'Level1' => 1, 'Level2' => 2, 'Level3' => NULL, 'Level4' => NULL, 'Level5' => NULL, 'Level6' => NULL),
            array('ID' => 3, 'Label' => 'Blackberry', 'Level1' => 1, 'Level2' => 3, 'Level3' => NULL, 'Level4' => NULL, 'Level5' => NULL, 'Level6' => NULL),
            array('ID' => 4, 'Label' => 'iPhone', 'Level1' => 1, 'Level2' => 4, 'Level3' => NULL, 'Level4' => NULL, 'Level5' => NULL, 'Level6' => NULL),
        );

        $testUsers($products, $this);

        // remove visibilty to two of the four specified products
        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 0 WHERE id IN (1,2)");
        Connect\ConnectAPI::commit();
        $testUsers(array_slice($products, 2), $this);

        // remove visibilty to remaining specified products
        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 0 WHERE id IN (3,4)");
        Connect\ConnectAPI::commit();
        $testUsers(null, $this);

        // restore visibilty to four specified products
        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 1 WHERE id IN (1,2,3,4)");
        Connect\ConnectAPI::commit();
        $testUsers($products, $this);
    }

    function testGetPermissionedListWithExtendedHierarchy() {
        $testUsers = function($expectedLimitedList, $that, $isProduct, $jsonDecode = true) {
            // pass in jsonDecode parameter to make it easier to output results without json decoding automatically
            $url = "/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/getPermissionedListResults" . ($isProduct ? "" : "/useCategory");
            $result = TestHelper::makeRequest("$url/userprodonly");
            if ($jsonDecode)
                $result = json_decode($result, true);
            if ($isProduct) {
                $that->assertIdentical($result['read'], $expectedLimitedList, "%s while verifying userprodonly with list " . var_export($expectedLimitedList, true));
                $that->assertIdentical($result['create'], $expectedLimitedList, "%s while verifying userprodonly with list " . var_export($expectedLimitedList, true));
                $that->assertIdentical($result['update_avatar'], $expectedLimitedList, "%s while verifying userprodonly with list " . var_export($expectedLimitedList, true));
            }
            else {
                $that->assertNull($result['read'], "%s while verifying userprodonly");
                $that->assertNull($result['create'], "%s while verifying userprodonly");
                $that->assertNull($result['update_avatar'], "%s while verifying userprodonly");
            }
            $that->assertNull($result['nonsense'], "%s while verifying userprodonly");

            $result = TestHelper::makeRequest("$url/usercatonly");
            if ($jsonDecode)
                $result = json_decode($result, true);
            if ($isProduct) {
                $that->assertNull($result['read'], "%s while verifying usercatonly");
                $that->assertNull($result['create'], "%s while verifying usercatonly");
                $that->assertNull($result['update_avatar'], "%s while verifying usercatonly");
            }
            else {
                $that->assertIdentical($result['read'], $expectedLimitedList, "%s while verifying usercatonly with list " . var_export($expectedLimitedList, true));
                $that->assertIdentical($result['create'], $expectedLimitedList, "%s while verifying usercatonly with list " . var_export($expectedLimitedList, true));
                $that->assertIdentical($result['update_avatar'], $expectedLimitedList, "%s while verifying usercatonly with list " . var_export($expectedLimitedList, true));
            }
            $that->assertNull($result['nonsense'], "%s while verifying usercatonly");

            $result = TestHelper::makeRequest("$url/userprodandcat");
            if ($jsonDecode)
                $result = json_decode($result, true);
            $that->assertIdentical($result['read'], $expectedLimitedList, "%s while verifying userprodandcat with list " . var_export($expectedLimitedList, true));
            $that->assertIdentical($result['create'], $expectedLimitedList, "%s while verifying userprodandcat with list " . var_export($expectedLimitedList, true));
            $that->assertIdentical($result['update_avatar'], $expectedLimitedList, "%s while verifying userprodandcat with list " . var_export($expectedLimitedList, true));
            $that->assertNull($result['nonsense'], "%s while verifying userprodandcat");
        };

        $products = array(
            array('ID' => 129, 'Label' => 'p2', 'Level1' => 128, 'Level2' => 129, 'Level3' => NULL, 'Level4' => NULL, 'Level5' => NULL, 'Level6' => NULL),
            array('ID' => 120, 'Label' => 'p5', 'Level1' => 128, 'Level2' => 129, 'Level3' => 130, 'Level4' => 131, 'Level5' => 120, 'Level6' => NULL),
            array('ID' => 138, 'Label' => 'p3b', 'Level1' => 128, 'Level2' => 129, 'Level3' => 130, 'Level4' => 138, 'Level5' => NULL, 'Level6' => NULL),
        );

        $testUsers($products, $this, true);

        // remove visibilty to two of the three specified products
        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 0 WHERE id IN (129,120)");
        Connect\ConnectAPI::commit();
        $testUsers(array_slice($products, 2), $this, true);

        // remove visibilty to remaining specified products
        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 0 WHERE id IN (138)");
        Connect\ConnectAPI::commit();
        $testUsers(null, $this, true);

        // restore visibilty to three specified products
        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 1 WHERE id IN (129,120,138)");
        Connect\ConnectAPI::commit();
        $testUsers($products, $this, true);

        $categories = array(
            array('ID' => 77, 'Label' => 'Call Quality', 'Level1' => 71, 'Level2' => 77, 'Level3' => NULL, 'Level4' => NULL, 'Level5' => NULL, 'Level6' => NULL),
            array('ID' => 123, 'Label' => 'c2', 'Level1' => 122, 'Level2' => 123, 'Level3' => NULL, 'Level4' => NULL, 'Level5' => NULL, 'Level6' => NULL),
            array('ID' => 125, 'Label' => 'c4', 'Level1' => 122, 'Level2' => 123, 'Level3' => 124, 'Level4' => 125, 'Level5' => NULL, 'Level6' => NULL),
            array('ID' => 127, 'Label' => 'c6', 'Level1' => 122, 'Level2' => 123, 'Level3' => 124, 'Level4' => 125, 'Level5' => 126, 'Level6' => 127),
        );

        $testUsers($categories, $this, false);

        // remove visibilty to two of the four specified categories
        Api::test_sql_exec_direct("UPDATE cat_vis SET enduser = 0 WHERE id IN (77,123)");
        Connect\ConnectAPI::commit();
        $testUsers(array_slice($categories, 2), $this, false);

        // remove visibilty to remaining specified categories
        Api::test_sql_exec_direct("UPDATE cat_vis SET enduser = 0 WHERE id IN (125,127)");
        Connect\ConnectAPI::commit();
        $testUsers(null, $this, false);

        // restore visibilty to four specified categories
        Api::test_sql_exec_direct("UPDATE cat_vis SET enduser = 1 WHERE id IN (77,123,125,127)");
        Connect\ConnectAPI::commit();
        $testUsers($categories, $this, false);
    }

    function getPermissionedListResults() {
        $user = Text::getSubstringAfter(get_instance()->uri->uri_string(), 'getPermissionedListResults/');
        $isProduct = true;
        // if $user contains a '/', then we want to get category results instead of product results
        if ($user && Text::stringContains($user, '/')) {
            $isProduct = false;
            $user = Text::getSubstringAfter($user, '/');
        }
        else if ($user === 'useCategory') {
            $user = null;
            $isProduct = false;
        }
        if ($user) {
            $this->logIn($user);
        }
        $results = array(
            'read' => $this->model->getPermissionedListSocialQuestionRead($isProduct)->result,
            'create' => $this->model->getPermissionedListSocialQuestionCreate($isProduct)->result,
            'update_avatar' => $this->model->getPermissionedList(PERM_SOCIALUSER_UPDATE_AVATAR, $isProduct)->result,
            'nonsense' => $this->model->getPermissionedList(5050, $isProduct)->result,
        );
        echo json_encode($results);
        if ($user) {
            $this->logOut();
        }
    }

    function testGetHierarchy() {
        $expectedProductResults1 = array (
            1 =>
            array (
              'id' => 1,
              'label' => 'Mobile Phones',
              'seq' => 1,
              'parent' => 1,
              'level' => 0,
              'hierList' => '1',
            ),
            6 =>
            array (
              'id' => 6,
              'label' => 'Voice Plans',
              'seq' => 2,
              'parent' => 6,
              'level' => 0,
              'hierList' => '6',
            ),
            162 =>
            array (
              'id' => 162,
              'label' => 'Text Messaging',
              'seq' => 3,
              'parent' => 162,
              'level' => 0,
              'hierList' => '162',
            ),
            163 =>
            array (
              'id' => 163,
              'label' => 'Mobile Broadband',
              'seq' => 4,
              'parent' => 163,
              'level' => 0,
              'hierList' => '163',
            ),
            7 =>
            array (
              'id' => 7,
              'label' => 'Replacement/Repair Coverage',
              'seq' => 5,
              'parent' => 7,
              'level' => 0,
              'hierList' => '7',
            ),
            128 =>
            array (
              'id' => 128,
              'label' => 'p1',
              'seq' => 6,
              'parent' => 128,
              'level' => 0,
              'hierList' => '128',
            ),
        );

        $expectedProductResults2 = array (
            1 =>
            array (
              'id' => 1,
              'label' => 'Mobile Phones',
              'seq' => 1,
              'parent' => 1,
              'level' => 0,
              'hierList' => '1',
              'subItems' =>
              array (
                0 =>
                array (
                  'id' => 2,
                  'label' => 'Android',
                  'seq' => 1,
                  'parent' => 1,
                  'level' => 1,
                  'hierList' => '1,2',
                ),
                1 =>
                array (
                  'id' => 3,
                  'label' => 'Blackberry',
                  'seq' => 2,
                  'parent' => 1,
                  'level' => 1,
                  'hierList' => '1,3',
                ),
                2 =>
                array (
                  'id' => 4,
                  'label' => 'iPhone',
                  'seq' => 3,
                  'parent' => 1,
                  'level' => 1,
                  'hierList' => '1,4',
                ),
              ),
            ),
            6 =>
            array (
              'id' => 6,
              'label' => 'Voice Plans',
              'seq' => 2,
              'parent' => 6,
              'level' => 0,
              'hierList' => '6',
              'subItems' =>
              array (
              ),
            ),
            162 =>
            array (
              'id' => 162,
              'label' => 'Text Messaging',
              'seq' => 3,
              'parent' => 162,
              'level' => 0,
              'hierList' => '162',
              'subItems' =>
              array (
              ),
            ),
            163 =>
            array (
              'id' => 163,
              'label' => 'Mobile Broadband',
              'seq' => 4,
              'parent' => 163,
              'level' => 0,
              'hierList' => '163',
              'subItems' =>
              array (
              ),
            ),
            7 =>
            array (
              'id' => 7,
              'label' => 'Replacement/Repair Coverage',
              'seq' => 5,
              'parent' => 7,
              'level' => 0,
              'hierList' => '7',
              'subItems' =>
              array (
              ),
            ),
            128 =>
            array (
              'id' => 128,
              'label' => 'p1',
              'seq' => 6,
              'parent' => 128,
              'level' => 0,
              'hierList' => '128',
              'subItems' =>
              array (
                0 =>
                array (
                  'id' => 132,
                  'label' => 'p1a',
                  'seq' => 1,
                  'parent' => 128,
                  'level' => 1,
                  'hierList' => '128,132',
                ),
                1 =>
                array (
                  'id' => 133,
                  'label' => 'p1b',
                  'seq' => 2,
                  'parent' => 128,
                  'level' => 1,
                  'hierList' => '128,133',
                ),
                2 =>
                array (
                  'id' => 129,
                  'label' => 'p2',
                  'seq' => 3,
                  'parent' => 128,
                  'level' => 1,
                  'hierList' => '128,129',
                ),
              ),
            ),
        );

        $expectedProductResults3 = array (
            2 => array (
                'id' => 2,
                'label' => "Android",
                'seq' => 1,
                'parent' => 1,
                'level' => 0,
                'hierList' => "1,2",
            ),
            3 => array (
                'id' => 3,
                'label' => "Blackberry",
                'seq' => 2,
                'parent' => 1,
                'level' => 0,
                'hierList' => "1,3",
            ),
            4 => array (
                'id' => 4,
                'label' => "iPhone",
                'seq' => 3,
                'parent' => 1,
                'level' => 0,
                'hierList' => "1,4",
            ),
        );


        $expectedCategoryResults1 = array (
            161 =>
            array (
              'id' => 161,
              'label' => 'Basics',
              'seq' => 1,
              'parent' => 161,
              'level' => 0,
              'hierList' => '161',
            ),
            153 =>
            array (
              'id' => 153,
              'label' => 'Mobile Services',
              'seq' => 2,
              'parent' => 153,
              'level' => 0,
              'hierList' => '153',
            ),
            68 =>
            array (
              'id' => 68,
              'label' => 'Account and Billing',
              'seq' => 3,
              'parent' => 68,
              'level' => 0,
              'hierList' => '68',
            ),
            158 =>
            array (
              'id' => 158,
              'label' => 'Rollover Minutes',
              'seq' => 4,
              'parent' => 158,
              'level' => 0,
              'hierList' => '158',
            ),
            70 =>
            array (
              'id' => 70,
              'label' => 'Mobile Broadband',
              'seq' => 5,
              'parent' => 70,
              'level' => 0,
              'hierList' => '70',
            ),
            71 =>
            array (
              'id' => 71,
              'label' => 'Troubleshooting',
              'seq' => 6,
              'parent' => 71,
              'level' => 0,
              'hierList' => '71',
            ),
        );

        $expectedCategoryResults2 = array (
            161 =>
            array (
              'id' => 161,
              'label' => 'Basics',
              'seq' => 1,
              'parent' => 161,
              'level' => 0,
              'hierList' => '161',
              'subItems' =>
              array (
              ),
            ),
            153 =>
            array (
              'id' => 153,
              'label' => 'Mobile Services',
              'seq' => 2,
              'parent' => 153,
              'level' => 0,
              'hierList' => '153',
              'subItems' =>
              array (
              ),
            ),
            68 =>
            array (
              'id' => 68,
              'label' => 'Account and Billing',
              'seq' => 3,
              'parent' => 68,
              'level' => 0,
              'hierList' => '68',
              'subItems' =>
              array (
              ),
            ),
            158 =>
            array (
              'id' => 158,
              'label' => 'Rollover Minutes',
              'seq' => 4,
              'parent' => 158,
              'level' => 0,
              'hierList' => '158',
              'subItems' =>
              array (
              ),
            ),
            70 =>
            array (
              'id' => 70,
              'label' => 'Mobile Broadband',
              'seq' => 5,
              'parent' => 70,
              'level' => 0,
              'hierList' => '70',
              'subItems' =>
              array (
              ),
            ),
            71 =>
            array (
              'id' => 71,
              'label' => 'Troubleshooting',
              'seq' => 6,
              'parent' => 71,
              'level' => 0,
              'hierList' => '71',
              'subItems' =>
              array (
                0 =>
                array (
                  'id' => 77,
                  'label' => 'Call Quality',
                  'seq' => 1,
                  'parent' => 71,
                  'level' => 1,
                  'hierList' => '71,77',
                ),
                1 =>
                array (
                  'id' => 79,
                  'label' => 'Batteries',
                  'seq' => 3,
                  'parent' => 71,
                  'level' => 1,
                  'hierList' => '71,79',
                ),
              ),
            ),
        );

        // INVALID ENTRIES
        $response = $this->model->getHierarchy('prozuct', 1);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierarchy('cazegory', 1);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierarchy('product', -1); // invalid level
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierarchy('product', 3); // exceeds max level
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierarchy('category', 3); // exceeds max level
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierarchy('category', 1, 31); // exceeds max limit
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierarchy('category', 1, 0); // exceeds min limit
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierarchy('category', 2, 30, array(), 31); // exceeds max descendant limit
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierarchy('category', 2, 30, array(), 0); // exceeds min descendant limit
        $this->assertResponseObject($response, 'is_null', 1);

        // VALID PRODUCT CALLS
        $response = $this->model->getHierarchy('prod', 0);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expectedProductResults1);

        $response = $this->model->getHierarchy('product', 0);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expectedProductResults1);

        $response = $this->model->getHierarchy('product', 1);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expectedProductResults1);

        $response = $this->model->getHierarchy('product', 2);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expectedProductResults2);

        // Even though descendantLimit is invalid, pass the call through, since we set level to 1
        $response = $this->model->getHierarchy('prod', 1, 30, array(), -1);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expectedProductResults1);

        // VALID CATEGORY CALLS
        $response = $this->model->getHierarchy('cat', 0);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expectedCategoryResults1);

        $response = $this->model->getHierarchy('cat', 1);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expectedCategoryResults1);

        $connectionIssues = $this->model->get(78)->result;
        $connectionIssues->EndUserVisibleInterfaces = null;
        $connectionIssues->save();

        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}Category2Hierarchy", null);
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}78Visible", null);

        $response = $this->model->getHierarchy('cat', 2);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expectedCategoryResults2);

        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}Category2Hierarchy", null);
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}78Visible", null);

        $connectionIssues->EndUserVisibleInterfaces = new Connect\NamedIDDeltaOptListArray();
        $connectionIssues->EndUserVisibleInterfaces[] = new Connect\NamedIDDeltaOptList();
        $connectionIssues->EndUserVisibleInterfaces[0]->ID = 1;
        $connectionIssues->save();

        //Limit results
        $response = $this->model->getHierarchy('prod', 1, 1);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical(count($response->result), 1);
        $this->assertIdentical($response->result[1]['id'], 1);

        $response = $this->model->getHierarchy('prod', 2, 1, array(), 1);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical(count($response->result), 1);
        $this->assertIdentical(count($response->result[1]['subItems']), 1);
        $this->assertIdentical($response->result[1]['subItems'][0]['id'], 2);

        $response = $this->model->getHierarchy('prod', 2, 3, array(), 3);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical(count($response->result), 3);
        $this->assertIdentical(count($response->result[1]['subItems']), 3);

        // Top level IDs
        $response = $this->model->getHierarchy('prod', 2, 30, array(1), 30);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical(count($response->result), 1);
        $this->assertIdentical(count($response->result[1]['subItems']), 3);

        $response = $this->model->getHierarchy('prod', 2, 30, array(1, 7, 128, 163), 30);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical(count($response->result), 4);
        $this->assertIdentical(count($response->result[1]['subItems']), 3);

        // Descendant Limits
        $response = $this->model->getHierarchy('prod', 2, 2, array(), 2);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical(count($response->result), 2);
        $this->assertIdentical(count($response->result[1]['subItems']), 2);
        $this->assertIdentical($response->result[1]['subItems'][0]['id'], 2);

        $response = $this->model->getHierarchy('prod', 2, 6, array(), 4);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical(count($response->result), 6);
        $this->assertIdentical(count($response->result[1]['subItems']), 2);
        $this->assertIdentical(count($response->result[128]['subItems']), 2);
    }

    function testGetChain() {
        // INVALID prod/cat ID
        $response = $this->model->getChain(9999, 1);
        $this->assertResponseObject($response, 'is_null', 1);

        // INVALID levels
        $response = $this->model->getChain(1, -1);
        $this->assertResponseObject($response, 'is_null', 1);

        $id = 1;

        // Support id prepended with a 'u' where level needs to be decremented by 1 (needed for views engine ?)
        $this->assertIdentical($this->model->getChain("u$id", 2)->result, $this->model->getChain($id, 1)->result);

        // LEVEL 1 PRODUCT (no children)
        $response = $this->model->getChain($id, 1);
        $this->assertResponseObject($response, 'is_array');

        $this->assertIdentical(array($id), $response->result);
        $this->assertIdentical(array($id, null), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array($id, null, null), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array($id, null, null, null), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array($id, null, null, null, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array($id, null, null, null, null, null), $this->model->getChain($id, 6)->result);

        // LEVEL 6 PRODUCT
        $id = 121;

        $this->assertIdentical(array(128), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(128, 129), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(128, 129, 130), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(128, 129, 130, 131), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(128, 129, 130, 131, 120), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(128, 129, 130, 131, 120, 121), $this->model->getChain($id, 6)->result);

        // $ignoreVisibility = true;
        $this->assertIdentical(array(128), $this->model->getChain($id, 1, true)->result);
        $this->assertIdentical(array(128, 129), $this->model->getChain($id, 2, true)->result);
        $this->assertIdentical(array(128, 129, 130), $this->model->getChain($id, 3, true)->result);
        $this->assertIdentical(array(128, 129, 130, 131), $this->model->getChain($id, 4, true)->result);
        $this->assertIdentical(array(128, 129, 130, 131, 120), $this->model->getChain($id, 5, true)->result);
        $this->assertIdentical(array(128, 129, 130, 131, 120, 121), $this->model->getChain($id, 6, true)->result);

        $id = 120;
        $this->assertIdentical(array(128), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(128, 129), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(128, 129, 130), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(128, 129, 130, 131), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(128, 129, 130, 131, 120), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(128, 129, 130, 131, 120, null), $this->model->getChain($id, 6)->result);

        $id = 131;
        $this->assertIdentical(array(128), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(128, 129), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(128, 129, 130), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(128, 129, 130, 131), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(128, 129, 130, 131, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(128, 129, 130, 131, null, null), $this->model->getChain($id, 6)->result);

        $id = 130;
        $this->assertIdentical(array(128), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(128, 129), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(128, 129, 130), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(128, 129, 130, null), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(128, 129, 130, null, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(128, 129, 130, null, null, null), $this->model->getChain($id, 6)->result);

        $id = 129;
        $this->assertIdentical(array(128), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(128, 129), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(128, 129, null), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(128, 129, null, null), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(128, 129, null, null, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(128, 129, null, null, null, null), $this->model->getChain($id, 6)->result);

        $id = 128;
        $this->assertIdentical(array(128), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(128, null), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(128, null, null), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(128, null, null, null), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(128, null, null, null, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(128, null, null, null, null, null), $this->model->getChain($id, 6)->result);

        // $ignoreVisibility = true;
        $this->assertIdentical(array(128), $this->model->getChain($id, 1, true)->result);
        $this->assertIdentical(array(128, null), $this->model->getChain($id, 2, true)->result);
        $this->assertIdentical(array(128, null, null), $this->model->getChain($id, 3, true)->result);
        $this->assertIdentical(array(128, null, null, null), $this->model->getChain($id, 4, true)->result);
        $this->assertIdentical(array(128, null, null, null, null), $this->model->getChain($id, 5, true)->result);
        $this->assertIdentical(array(128, null, null, null, null, null), $this->model->getChain($id, 6, true)->result);


        // // LEVEL 1 CATEGORY (no children)
        $id = 68;
        $this->assertIdentical(array($id), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array($id, null), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array($id, null, null), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array($id, null, null, null), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array($id, null, null, null, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array($id, null, null, null, null, null), $this->model->getChain($id, 6)->result);

        // LEVEL 6 CATEGORY
        $id = 127;
        $this->assertIdentical(array(122), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(122, 123), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(122, 123, 124), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(122, 123, 124, 125), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(122, 123, 124, 125, 126), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(122, 123, 124, 125, 126, 127), $this->model->getChain($id, 6)->result);

        $id = 126;
        $this->assertIdentical(array(122), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(122, 123), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(122, 123, 124), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(122, 123, 124, 125), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(122, 123, 124, 125, 126), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(122, 123, 124, 125, 126, null), $this->model->getChain($id, 6)->result);

        $id = 125;
        $this->assertIdentical(array(122), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(122, 123), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(122, 123, 124), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(122, 123, 124, 125), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(122, 123, 124, 125, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(122, 123, 124, 125, null, null), $this->model->getChain($id, 6)->result);

        $id = 124;
        $this->assertIdentical(array(122), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(122, 123), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(122, 123, 124), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(122, 123, 124, null), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(122, 123, 124, null, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(122, 123, 124, null, null, null), $this->model->getChain($id, 6)->result);

        $id = 123;
        $this->assertIdentical(array(122), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(122, 123), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(122, 123, null), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(122, 123, null, null), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(122, 123, null, null, null), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(122, 123, null, null, null, null), $this->model->getChain($id, 6)->result);

        $id = 122; // not enduser visible
        $this->assertIdentical(array(), $this->model->getChain($id, 1)->result);
        $this->assertIdentical(array(), $this->model->getChain($id, 2)->result);
        $this->assertIdentical(array(), $this->model->getChain($id, 3)->result);
        $this->assertIdentical(array(), $this->model->getChain($id, 4)->result);
        $this->assertIdentical(array(), $this->model->getChain($id, 5)->result);
        $this->assertIdentical(array(), $this->model->getChain($id, 6)->result);

        $this->assertIdentical(array(122), $this->model->getChain($id, 1, true)->result);
        $this->assertIdentical(array(122, null), $this->model->getChain($id, 2, true)->result);
        $this->assertIdentical(array(122, null, null), $this->model->getChain($id, 3, true)->result);
        $this->assertIdentical(array(122, null, null, null), $this->model->getChain($id, 4, true)->result);
        $this->assertIdentical(array(122, null, null, null, null), $this->model->getChain($id, 5, true)->result);
        $this->assertIdentical(array(122, null, null, null, null, null), $this->model->getChain($id, 6, true)->result);
    }

    function testIsEnduserVisible() {
        $this->assertNull($this->model->isEnduserVisible(9999));
        $this->assertNull($this->model->isEnduserVisible(-1));
        $this->assertNull($this->model->isEnduserVisible(null));
        $this->assertNull($this->model->isEnduserVisible('foo'));


        foreach (array_merge($this->productIDs, $this->categoryIDs) as $id) {
            if ($id === 122) { // Currently the only non-enduser visible hier_menu (a Category, in this case);
                $this->assertFalse($this->model->isEnduserVisible($id), "'$id did not return false'");
            }
            else {
                $this->assertTrue($this->model->isEnduserVisible($id), "'$id did not return true'");
            }
        }
    }

    function testGetEnduserVisibleHierarchy() {
        //Any chain longer than 6 items should only return the first six end user visible items
        $response = $this->model->getEnduserVisibleHierarchy($this->productIDs);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, array_slice($this->productIDs, 0, 6));

        // CURRENT FUNCTIONALITY throws all values away and returns an emtpy array if ANY values are not an integer...
        $response = $this->model->getEnduserVisibleHierarchy(array_merge(array('1', 'notAnInteger'), $this->productIDs));
        $this->assertResponseObject($response, 'is_array', 1);
        $this->assertIdentical($response->result, array());

        //122 is not end user visible so remove all IDs after that position
        $visibleCategories = array(68, 70, 71, 77);
        $response = $this->model->getEnduserVisibleHierarchy(array(68, 70, 71, 77, 122, 78));
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $visibleCategories);

        $visibleCategories = array(68, 70, 71, 77, 78, 79);
        $response = $this->model->getEnduserVisibleHierarchy($visibleCategories);
        $this->assertIdentical($response->result, $visibleCategories);

        $response = $this->model->getEnduserVisibleHierarchy(array(997, 996, 995));
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, array());

        $response = $this->model->getEnduserVisibleHierarchy(array());
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, array());
    }

    function testSetAndGetDefaultProductID() {
        $this->assertNull($this->model->getDefaultProductID());
        $default = 160;
        $response = $this->model->setDefaultProductID($default);
        $this->assertResponseObject($response, 'is_bool');
        $this->assertIdentical($response->result, true);
        $this->assertIdentical($default, $this->model->getDefaultProductID());

        $response = $this->model->setDefaultProductID('not-valid');
        $this->assertResponseObject($response, 'is_bool', 1);
        $this->assertIdentical($response->result, false);
        $this->assertIdentical($default, $this->model->getDefaultProductID());
    }

    function testGetDirectDescendants() {

        $response = $this->model->getDirectDescendants('Prozuct'); //Fail
        $this->assertResponseObject($response, 'is_null', 1);
        $this->assertNull($response->result);

        $response = $this->model->getDirectDescendants('Cazegory'); //Fail
        $this->assertResponseObject($response, 'is_null', 1);
        $this->assertNull($response->result);

        $response = $this->model->getDirectDescendants('Product', 123123213); //Invalid ID
        $this->assertResponseObject($response, 'is_null', 1);
        $this->assertNull($response->result);

        $response = $this->model->getDirectDescendants('Category', 12321312); //Invalid ID
        $this->assertResponseObject($response, 'is_null', 1);
        $this->assertNull($response->result);

        $response = $this->model->getDirectDescendants('Category', 122); //Non enduser visible category
        $this->assertResponseObject($response, 'is_null', 1);
        $this->assertNull($response->result);

        //No descendants from these products (result should be empty)
        $response = $this->model->getDirectDescendants('Product', 6);
        $this->assertResponseObject($response, 'is_array');

        $response = $this->model->getDirectDescendants('Product', 162);
        $this->assertResponseObject($response, 'is_array');

        $response = $this->model->getDirectDescendants('Product', 163);
        $this->assertResponseObject($response, 'is_array');

        $response = $this->model->getDirectDescendants('Product', 7);
        $this->assertResponseObject($response, 'is_array');

        //Top Level Products
        $expected = array(
            array('id' => 1, 'label' => 'Mobile Phones', 'hasChildren' => true),
            array('id' => 6, 'label' => 'Voice Plans', 'hasChildren' => false),
            array('id' => 162, 'label' => 'Text Messaging', 'hasChildren' => false),
            array('id' => 163, 'label' => 'Mobile Broadband', 'hasChildren' => false),
            array('id' => 7, 'label' => 'Replacement/Repair Coverage', 'hasChildren' => false),
            array('id' => 128, 'label' => 'p1', 'hasChildren' => true)
        );
        $response = $this->model->getDirectDescendants('Product');
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //Products with descendants
        $cached = $expected = array(
            array('id' => 2, 'label' => 'Android', 'hasChildren' => true),
            array('id' => 3, 'label' => 'Blackberry', 'hasChildren' => false),
            array('id' => 4, 'label' => 'iPhone', 'hasChildren' => true)
        );

        $response = $this->model->getDirectDescendants('Product', 1);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(
            array('id' => 132, 'label' => 'p1a', 'hasChildren' => false),
            array('id' => 133, 'label' => 'p1b', 'hasChildren' => false),
            array('id' => 129, 'label' => 'p2', 'hasChildren' => true)
        );

        $response = $this->model->getDirectDescendants('Product', 128);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //Top Level Categories
        $expected = array(
            array('id' => 161, 'label' => 'Basics', 'hasChildren' => false),
            array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false),
            array('id' => 68, 'label' => 'Account and Billing', 'hasChildren' => false),
            array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false),
            array('id' => 70, 'label' => 'Mobile Broadband', 'hasChildren' => false),
            array('id' => 71, 'label' => 'Troubleshooting', 'hasChildren' => true)
        );

        $response = $this->model->getDirectDescendants('Category'); //Test end user visibility removal (Note: c1  -> cN shouldn't be displayed)
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //Categories with descendants
        $expected = array(
            array('id' => 77, 'label' => 'Call Quality', 'hasChildren' => false),
            array('id' => 78, 'label' => 'Connection Issues', 'hasChildren' => false),
            array('id' => 79, 'label' => 'Batteries', 'hasChildren' => false)
        );

        $response = $this->model->getDirectDescendants('Category', 71);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //Alter the iPhone's children so they are not end-user visible
        $iphone3g = $this->model->get(159)->result;
        $iphone3gs = $this->model->get(160)->result;
        $iphone3g->EndUserVisibleInterfaces = null;
        $iphone3gs->EndUserVisibleInterfaces = null;
        $iphone3g->save();
        $iphone3gs->save();

        $response = $this->model->getDirectDescendants('Product', 1);
        $this->assertResponseObject($response, 'is_array');
        // Value should still be cached
        $this->assertIdentical($response->result, $cached);

        Connect\ConnectApi::rollback();
    }

    function testGetFormattedChain() {
        $response = $this->model->getFormattedChain('Prozuct', 1); //Fail
        $this->assertResponseObject($response, 'is_array', 1);
        $this->assertIdentical($response->result, array());

        $response = $this->model->getFormattedChain('Cazegory', 1254); //Fail
        $this->assertResponseObject($response, 'is_array', 1);
        $this->assertIdentical($response->result, array());

        $response = $this->model->getFormattedChain('Product', 2132193); //Invalid ID
        $this->assertResponseObject($response, 'is_array', 1);
        $this->assertIdentical($response->result, array());

        $response = $this->model->getFormattedChain('Category', 132423442); //Invalid ID
        $this->assertResponseObject($response, 'is_array', 1);
        $this->assertIdentical($response->result, array());

        $response = $this->model->getFormattedChain('Category', 122); //Non end user visible ID
        $this->assertResponseObject($response, 'is_array', 0);
        $this->assertIdentical($response->result, array());

        $response = $this->model->getFormattedChain('Category', 147); //ID in chain with non end user visible parents
        $this->assertResponseObject($response, 'is_array', 0);
        $this->assertIdentical($response->result, array());

        //Top level products and categories - result should only include self
        $expected = array(array('id' => 6, 'label' => 'Voice Plans'));
        $response = $this->model->getFormattedChain('Product', 6);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(array('id' => 162, 'label' => 'Text Messaging'));
        $response = $this->model->getFormattedChain('Product', 162);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(array('id' => 161, 'label' => 'Basics'));
        $response = $this->model->getFormattedChain('Category', 161);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(array('id' => 153, 'label' => 'Mobile Services'));
        $response = $this->model->getFormattedChain('Category', 153);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //Products and categories with an actual chain
        $expected = array(
            array('id' => 128, 'label' => 'p1'),
            array('id' => 129, 'label' => 'p2'),
            array('id' => 130, 'label' => 'p3'),
            array('id' => 137, 'label' => 'p3a')
        );

        $response = $this->model->getFormattedChain('Product', 137);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(
            array('id' => 71, 'label' => 'Troubleshooting'),
            array('id' => 79, 'label' => 'Batteries')
        );

        $response = $this->model->getFormattedChain('Category', 79);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //Flat array functionality - i.e. excluding labels
        $expected = array(128, 129, 130, 137);
        $response = $this->model->getFormattedChain('Product', 137, true);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

    }
    function testGetFormattedTree() {
        $selectedChain = array(1, 4, 160);

        //Check all of the default parameters - make sure the range checks work
        $response = $this->model->getFormattedTree('Prozuct', $selectedChain);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getFormattedTree('Cazegory', $selectedChain);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getFormattedTree('Product', $selectedChain, false, 1, 7);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getFormattedTree('Product', $selectedChain, false, 1, 0);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getFormattedTree('Category', $selectedChain, true, array(), 0);
        $this->assertResponseObject($response, 'is_null', 1);

        //Test empty selection chain - should return top level products
        $expected = array(
            0 => array(
                array('id' => 1, 'label' => 'Mobile Phones', 'hasChildren' => true, 'selected' => false),
                array('id' => 6, 'label' => 'Voice Plans', 'hasChildren' => false, 'selected' => false),
                array('id' => 162, 'label' => 'Text Messaging', 'hasChildren' => false, 'selected' => false),
                array('id' => 163, 'label' => 'Mobile Broadband', 'hasChildren' => false, 'selected' => false),
                array('id' => 7, 'label' => 'Replacement/Repair Coverage', 'hasChildren' => false, 'selected' => false),
                array('id' => 128, 'label' => 'p1', 'hasChildren' => true, 'selected' => false),
            )
        );
        $response = $this->model->getFormattedTree('Product', array());
        $this->assertIdentical($response->result, $expected);

        //Test a full product chain and ensure the tree is correct
        $expected = array(
            0 => array(
                array('id' => 1, 'label' => 'Mobile Phones', 'hasChildren' => true, 'selected' => false),
                array('id' => 6, 'label' => 'Voice Plans', 'hasChildren' => false, 'selected' => false),
                array('id' => 162, 'label' => 'Text Messaging', 'hasChildren' => false, 'selected' => false),
                array('id' => 163, 'label' => 'Mobile Broadband', 'hasChildren' => false, 'selected' => false),
                array('id' => 7, 'label' => 'Replacement/Repair Coverage', 'hasChildren' => false, 'selected' => false),
                array('id' => 128, 'label' => 'p1', 'hasChildren' => true, 'selected' => false),
            ),
            1 => array(
                array('id' => 2, 'label' => 'Android', 'hasChildren' => true, 'selected' => false),
                array('id' => 3, 'label' => 'Blackberry', 'hasChildren' => false, 'selected' => false),
                array('id' => 4, 'label' => 'iPhone', 'hasChildren' => true, 'selected' => false),
            ),
            4 => array(
                array('id' => 159, 'label' => 'iPhone 3G', 'hasChildren' => false, 'selected' => false),
                array('id' => 160, 'label' => 'iPhone 3GS', 'hasChildren' => false, 'selected' => true),
            ),
        );
        $response = $this->model->getFormattedTree('Product', $selectedChain);
        $this->assertIdentical($response->result, $expected);

        //Test a product chain with a max level applied
        $expected = array(
            0 => array(
                array('id' => 1, 'label' => 'Mobile Phones', 'hasChildren' => true, 'selected' => false),
                array('id' => 6, 'label' => 'Voice Plans', 'hasChildren' => false, 'selected' => false),
                array('id' => 162, 'label' => 'Text Messaging', 'hasChildren' => false, 'selected' => false),
                array('id' => 163, 'label' => 'Mobile Broadband', 'hasChildren' => false, 'selected' => false),
                array('id' => 7, 'label' => 'Replacement/Repair Coverage', 'hasChildren' => false, 'selected' => false),
                array('id' => 128, 'label' => 'p1', 'hasChildren' => true, 'selected' => false),
            ),
            1 => array(
                array('id' => 2, 'label' => 'Android', 'hasChildren' => false, 'selected' => false),
                array('id' => 3, 'label' => 'Blackberry', 'hasChildren' => false, 'selected' => false),
                array('id' => 4, 'label' => 'iPhone', 'hasChildren' => false, 'selected' => true),
            ),
        );
        $response = $this->model->getFormattedTree('Product', $selectedChain, false, null, 2);

        $this->assertIdentical($response->result, $expected);

        //Test empty selection chain - should return top level categories
        $expected = array(
            0 => array(
                array('id' => 161, 'label' => 'Basics', 'hasChildren' => false, 'selected' => false),
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false, 'selected' => false),
                array('id' => 68, 'label' => 'Account and Billing', 'hasChildren' => false, 'selected' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false, 'selected' => false),
                array('id' => 70, 'label' => 'Mobile Broadband', 'hasChildren' => false, 'selected' => false),
                array('id' => 71, 'label' => 'Troubleshooting', 'hasChildren' => true, 'selected' => false),
            ),
        );
        $response = $this->model->getFormattedTree('Category', array());
        $this->assertIdentical($response->result, $expected);

        //Test a full category chain and ensure the tree is correct
        $selectedChain = array(71,77);
        $expected = array(
            0 => array(
                array('id' => 161, 'label' => 'Basics', 'hasChildren' => false, 'selected' => false),
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false, 'selected' => false),
                array('id' => 68, 'label' => 'Account and Billing', 'hasChildren' => false, 'selected' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false, 'selected' => false),
                array('id' => 70, 'label' => 'Mobile Broadband', 'hasChildren' => false, 'selected' => false),
                array('id' => 71, 'label' => 'Troubleshooting', 'hasChildren' => true, 'selected' => false),
            ),
            71 => array(
                array('id' => 77, 'label' => 'Call Quality', 'hasChildren' => false, 'selected' => true),
                array('id' => 78, 'label' => 'Connection Issues', 'hasChildren' => false, 'selected' => false),
                array('id' => 79, 'label' => 'Batteries', 'hasChildren' => false, 'selected' => false),
            ),
        );
        $response = $this->model->getFormattedTree('Category', $selectedChain);
        $this->assertIdentical($response->result, $expected);

        //Test a category chain with a max level applied
        $selectedChain = array(71,77);
        $expected = array(
            0 => array(
                array('id' => 161, 'label' => 'Basics', 'hasChildren' => false, 'selected' => false),
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false, 'selected' => false),
                array('id' => 68, 'label' => 'Account and Billing', 'hasChildren' => false, 'selected' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false, 'selected' => false),
                array('id' => 70, 'label' => 'Mobile Broadband', 'hasChildren' => false, 'selected' => false),
                array('id' => 71, 'label' => 'Troubleshooting', 'hasChildren' => false, 'selected' => true),
            ),
        );
        $response = $this->model->getFormattedTree('Category', $selectedChain, false, null, 1);
        $this->assertIdentical($response->result, $expected);

        //Test linked categories - null ID = all linked categories
        $expected = array(
            0 => array(
                array('id' => 161, 'label' => 'Basics', 'hasChildren' => false, 'selected' => false),
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false, 'selected' => false),
                array('id' => 68, 'label' => 'Account and Billing', 'hasChildren' => false, 'selected' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false, 'selected' => false),
                array('id' => 70, 'label' => 'Mobile Broadband', 'hasChildren' => false, 'selected' => false),
                array('id' => 71, 'label' => 'Troubleshooting', 'hasChildren' => true, 'selected' => false)
            ),
            71 => array(
                array('id' => 77, 'label' => 'Call Quality', 'hasChildren' => false, 'selected' => true),
                array('id' => 78, 'label' => 'Connection Issues', 'hasChildren' => false, 'selected' => false),
                array('id' => 79, 'label' => 'Batteries', 'hasChildren' => false, 'selected' => false),
            )
        );

        $response = $this->model->getFormattedTree('Category', $selectedChain, true, null); //Null ID - returns top level categories
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //@@@ QA 130405-000115 130405-000117 Test Linked categories with selection chain and invalid last node
        $selectedChain = array(71, 121);
        $expected = array(
            0 => array(
                array('id' => 161, 'label' => 'Basics', 'hasChildren' => false, 'selected' => false),
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false, 'selected' => false),
                array('id' => 68, 'label' => 'Account and Billing', 'hasChildren' => false, 'selected' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false, 'selected' => false),
                array('id' => 70, 'label' => 'Mobile Broadband', 'hasChildren' => false, 'selected' => false),
                array('id' => 71, 'label' => 'Troubleshooting', 'hasChildren' => true, 'selected' => true)
            ),
            71 => array(
                array('id' => 77, 'label' => 'Call Quality', 'hasChildren' => false, 'selected' => false),
                array('id' => 78, 'label' => 'Connection Issues', 'hasChildren' => false, 'selected' => false),
                array('id' => 79, 'label' => 'Batteries', 'hasChildren' => false, 'selected' => false),
            )
        );

        $response = $this->model->getFormattedTree('Category', $selectedChain, true, null); //Null ID - returns top level categories
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);
    }

    function testGetNonEnduserVisibleChain() {
        // invalid entries
        $expected = array();
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(null));
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain('abc'));
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(123456789));

        // Products (all enduser visible in our data set)
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(120));
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain($this->model->get(120)->result));
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(130));
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(128));

        // Categories
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(122)); // Is non-enduser-visible, but has visible ancestors
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(68)); // First level, visible category
        $expected = array(122, 123, 124, 125, 126, 127);
        $this->assertIdentical(array(122, 123, 124, 125, 126, 127), $this->model->getNonEnduserVisibleChain(127)); // Has 122 as ancestor
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(127)); // Has 122 as ancestor
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain($this->model->get(127)->result)); // as an object
        $expected = array(122, 123);
        $this->assertIdentical($expected, $this->model->getNonEnduserVisibleChain(123)); // Has 122 as ancestor
    }

    function testGetLinkedCategories() {
        $response = $this->model->getLinkedCategories(12321312); //Invalid ID
        $this->assertResponseObject($response, 'is_null', 1);
        $this->assertIdentical($response->result, null);

        $response = $this->model->getLinkedCategories(160); //Only linked dispositions, no linked categories
        $this->assertResponseObject($response, 'is_array', 0, 1);
        $this->assertIdentical($response->result, array());

        //Top level categories
        $expected = array(
            0 => array(
                array('id' => 161, 'label' => 'Basics', 'hasChildren' => false),
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false),
                array('id' => 68, 'label' => 'Account and Billing', 'hasChildren' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false),
                array('id' => 70, 'label' => 'Mobile Broadband', 'hasChildren' => false),
                array('id' => 71, 'label' => 'Troubleshooting', 'hasChildren' => true)
            )
        );

        $response = $this->model->getLinkedCategories(); //Null ID - returns top level categories
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        $response = $this->model->getLinkedCategories(1);
        $this->assertResponseObject($response, 'is_array', 0, 1);
        $this->assertIdentical($response->result, array());


        // @@@ 140818-000045 Make sure chain is still populated when last link is not visibile
        // Need to turn on branch starting at 122
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=1 WHERE tbl = 65 AND id = 122 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}122Visible", true);
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=0 WHERE tbl = 65 AND id = 152 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}152Visible", false);
        $expected = array(
            0   => array(array('id' => 122, 'label' => 'c1', 'hasChildren' => true)),
            122 => array(array('id' => 123, 'label' => 'c2', 'hasChildren' => true)),
            123 => array(array('id' => 124, 'label' => 'c3', 'hasChildren' => true)),
            124 => array(array('id' => 125, 'label' => 'c4', 'hasChildren' => true)),
            125 => array(array('id' => 126, 'label' => 'c5', 'hasChildren' => false))
        );
        $response = $this->model->getLinkedCategories(1);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        Api::test_sql_exec_direct("UPDATE visibility SET enduser=1 WHERE tbl = 65 AND id = 152 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}152Visible", true);

        // @@@ 140818-000045 Make sure chain stops at invisible category
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=0 WHERE tbl = 65 AND id = 125 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}125Visible", false);

        $expected = array(
            0   => array(array('id' => 122, 'label' => 'c1', 'hasChildren' => true)),
            122 => array(array('id' => 123, 'label' => 'c2', 'hasChildren' => true)),
            123 => array(array('id' => 124, 'label' => 'c3', 'hasChildren' => false)),
        );
        $response = $this->model->getLinkedCategories(4);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);


        Api::test_sql_exec_direct("UPDATE visibility SET enduser=1 WHERE tbl = 65 AND id = 125 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}125Visible", true);
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=0 WHERE tbl = 65 AND id = 122 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}122Visible", false);


        $response = $this->model->getLinkedCategories(2);
        $this->assertResponseObject($response, 'is_array', 0, 1);
        $this->assertIdentical($response->result, array());

        $expected = array(
            0 => array(
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false)
            )
        );

        $response = $this->model->getLinkedCategories(162);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        // 153 and 158 are both end user visible so they are legitimate but branch starting with 122 is not because 122 is not visible
        $expected = array(
            0 => array(
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false)
            ),
        );

        $response = $this->model->getLinkedCategories(163);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //Retrieve the linked categories with a maxLevel of 1
        $expected = array(
            0 => array(
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false)
            ),
        );
        $response = $this->model->getLinkedCategories(163, 1);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        //Retrieve the linked categories with a maxLevel of 2
        $expected = array(
            0 => array(
                array('id' => 153, 'label' => 'Mobile Services', 'hasChildren' => false),
                array('id' => 158, 'label' => 'Rollover Minutes', 'hasChildren' => false)
            ),
        );
        $response = $this->model->getLinkedCategories(163, 2);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($response->result, $expected);

        $response = $this->model->getLinkedCategories(128); //No linked categories
        $this->assertResponseObject($response, 'is_array', 0, 1);
        $this->assertIdentical($response->result, array());
    }



    function testGetHierPopup() {
        // Invalid $filterTypes (should return null and an error)
        $response = $this->model->getHierPopup(null);
        $this->assertResponseObject($response, 'is_null', 1);

        $response = $this->model->getHierPopup(9999);
        $this->assertResponseObject($response, 'is_null', 1);

        // Invalid $linkingValues (should return empty array and a warning)
        $response = $this->model->getHierPopup(HM_PRODUCTS, 9999);
        $this->assertResponseObject($response, 'is_array', 0, 1);

        $response = $this->model->getHierPopup(HM_PRODUCTS, 'foo');
        $this->assertResponseObject($response, 'is_array', 0, 1);

        // PRODUCTS, no linking
        $expected = array(
            0 =>
            array (
              0 => 'Mobile Phones',
              1 => 1,
              2 => 1,
              3 => 1,
              9 => '',
              'level' => 0,
              'hier_list' => '1',
            ),
            1 =>
            array (
              0 => 'Android',
              1 => 2,
              2 => 1,
              3 => 1,
              4 => 2,
              9 => '',
              'level' => 1,
              'hier_list' => '1,2',
            ),
            2 =>
            array (
              0 => 'Motorola Droid',
              1 => 8,
              2 => 1,
              3 => 1,
              4 => 2,
              5 => 8,
              9 => '',
              'level' => 2,
              'hier_list' => '1,2,8',
            ),
            3 =>
            array (
              0 => 'Nexus One',
              1 => 9,
              2 => 2,
              3 => 1,
              4 => 2,
              5 => 9,
              9 => '',
              'level' => 2,
              'hier_list' => '1,2,9',
            ),
            4 =>
            array (
              0 => 'HTC',
              1 => 10,
              2 => 3,
              3 => 1,
              4 => 2,
              5 => 10,
              9 => '',
              'level' => 2,
              'hier_list' => '1,2,10',
            ),
            5 =>
            array (
              0 => 'Blackberry',
              1 => 3,
              2 => 2,
              3 => 1,
              4 => 3,
              9 => '',
              'level' => 1,
              'hier_list' => '1,3',
            ),
            6 =>
            array (
              0 => 'iPhone',
              1 => 4,
              2 => 3,
              3 => 1,
              4 => 4,
              9 => '',
              'level' => 1,
              'hier_list' => '1,4',
            ),
            7 =>
            array (
              0 => 'iPhone 3G',
              1 => 159,
              2 => 1,
              3 => 1,
              4 => 4,
              5 => 159,
              9 => '',
              'level' => 2,
              'hier_list' => '1,4,159',
            ),
            8 =>
            array (
              0 => 'iPhone 3GS',
              1 => 160,
              2 => 2,
              3 => 1,
              4 => 4,
              5 => 160,
              9 => '',
              'level' => 2,
              'hier_list' => '1,4,160',
            ),
            9 =>
            array (
              0 => 'Voice Plans',
              1 => 6,
              2 => 2,
              3 => 6,
              9 => '',
              'level' => 0,
              'hier_list' => '6',
            ),
            10 =>
            array (
              0 => 'Text Messaging',
              1 => 162,
              2 => 3,
              3 => 162,
              9 => '',
              'level' => 0,
              'hier_list' => '162',
            ),
            11 =>
            array (
              0 => 'Mobile Broadband',
              1 => 163,
              2 => 4,
              3 => 163,
              9 => '',
              'level' => 0,
              'hier_list' => '163',
            ),
            12 =>
            array (
              0 => 'Replacement/Repair Coverage',
              1 => 7,
              2 => 5,
              3 => 7,
              9 => '',
              'level' => 0,
              'hier_list' => '7',
            ),
            13 =>
            array (
              0 => 'p1',
              1 => 128,
              2 => 6,
              3 => 128,
              9 => '',
              'level' => 0,
              'hier_list' => '128',
            ),
            14 =>
            array (
              0 => 'p1a',
              1 => 132,
              2 => 1,
              3 => 128,
              4 => 132,
              9 => '',
              'level' => 1,
              'hier_list' => '128,132',
            ),
            15 =>
            array (
              0 => 'p1b',
              1 => 133,
              2 => 2,
              3 => 128,
              4 => 133,
              9 => '',
              'level' => 1,
              'hier_list' => '128,133',
            ),
            16 =>
            array (
              0 => 'p2',
              1 => 129,
              2 => 3,
              3 => 128,
              4 => 129,
              9 => '',
              'level' => 1,
              'hier_list' => '128,129',
            ),
            17 =>
            array (
              0 => 'p2a',
              1 => 134,
              2 => 1,
              3 => 128,
              4 => 129,
              5 => 134,
              9 => '',
              'level' => 2,
              'hier_list' => '128,129,134',
            ),
            18 =>
            array (
              0 => 'p2b',
              1 => 135,
              2 => 2,
              3 => 128,
              4 => 129,
              5 => 135,
              9 => '',
              'level' => 2,
              'hier_list' => '128,129,135',
            ),
            19 =>
            array (
              0 => 'p3',
              1 => 130,
              2 => 3,
              3 => 128,
              4 => 129,
              5 => 130,
              9 => '',
              'level' => 2,
              'hier_list' => '128,129,130',
            ),
            20 =>
            array (
              0 => 'p3a',
              1 => 137,
              2 => 1,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 137,
              9 => '',
              'level' => 3,
              'hier_list' => '128,129,130,137',
            ),
            21 =>
            array (
              0 => 'p3b',
              1 => 138,
              2 => 2,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 138,
              9 => '',
              'level' => 3,
              'hier_list' => '128,129,130,138',
            ),
            22 =>
            array (
              0 => 'p4',
              1 => 131,
              2 => 3,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 131,
              9 => '',
              'level' => 3,
              'hier_list' => '128,129,130,131',
            ),
            23 =>
            array (
              0 => 'p4a',
              1 => 139,
              2 => 1,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 131,
              7 => 139,
              9 => '',
              'level' => 4,
              'hier_list' => '128,129,130,131,139',
            ),
            24 =>
            array (
              0 => 'p4b',
              1 => 140,
              2 => 2,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 131,
              7 => 140,
              9 => '',
              'level' => 4,
              'hier_list' => '128,129,130,131,140',
            ),
            25 =>
            array (
              0 => 'p5',
              1 => 120,
              2 => 3,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 131,
              7 => 120,
              9 => '',
              'level' => 4,
              'hier_list' => '128,129,130,131,120',
            ),
            26 =>
            array (
              0 => 'p5a',
              1 => 141,
              2 => 1,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 131,
              7 => 120,
              8 => 141,
              9 => '',
              'level' => 5,
              'hier_list' => '128,129,130,131,120,141',
            ),
            27 =>
            array (
              0 => 'p5b',
              1 => 142,
              2 => 2,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 131,
              7 => 120,
              8 => 142,
              9 => '',
              'level' => 5,
              'hier_list' => '128,129,130,131,120,142',
            ),
            28 =>
            array (
              0 => 'p6',
              1 => 121,
              2 => 3,
              3 => 128,
              4 => 129,
              5 => 130,
              6 => 131,
              7 => 120,
              8 => 121,
              9 => '',
              'level' => 5,
              'hier_list' => '128,129,130,131,120,121',
            ),
        );
        $response = $this->model->getHierPopup(HM_PRODUCTS);
        $this->assertResponseObject($response, 'is_array');
        $expectedKeys = $actualKeys = array();
        foreach($expected as $key => $value) {
            $expectedKeys[] = $value[0];
        }
        foreach($response->result as $key => $value) {
            $actualKeys[] = $value[0];
        }
        $this->assertIdentical($expected, $response->result);


        $expected = array(
            array(
                0 => 'Mobile Services',
                1 => 153,
                2 => 2,
                3 => 153,
                9 => '',
                'level' => 0,
                'hier_list' => '153',
            ),
            'prod_chain' => '162',
        );
        $this->assertIdentical($expected, $this->model->getHierPopup(HM_PRODUCTS, 162)->result);

        $expected = array(
            array(
              0 => 'Mobile Services',
              1 => 153,
              2 => 2,
              3 => 153,
              9 => '',
              'level' => 0,
              'hier_list' => '153',
            ),
            array(
              0 => 'Rollover Minutes',
              1 => 158,
              2 => 4,
              3 => 158,
              9 => '',
              'level' => 0,
              'hier_list' => '158',
            ),
            'prod_chain' => '163',
        );
        $this->assertIdentical($expected, $this->model->getHierPopup(HM_PRODUCTS, 163)->result);

        // Products with no category linking
        foreach (array(2, 3, 6, 7, 8, 9, 10, 120, 121, 128, 129, 130, 131, 132, 133, 134, 135, 137, 138, 139, 140, 141, 142) as $productID) {
            $response = $this->model->getHierPopup(HM_PRODUCTS, $productID);
            $this->assertResponseObject($response, 'is_array', 0, 1);
            $this->assertIdentical(array(), $response->result);
        }

        // CATEGORIES, no linking
        $expected = array(
            0 =>
            array (
              0 => 'Basics',
              1 => 161,
              2 => 1,
              3 => 161,
              9 => '',
              'level' => 0,
              'hier_list' => '161',
            ),
            1 =>
            array (
              0 => 'Mobile Services',
              1 => 153,
              2 => 2,
              3 => 153,
              9 => '',
              'level' => 0,
              'hier_list' => '153',
            ),
            2 =>
            array (
              0 => 'Account and Billing',
              1 => 68,
              2 => 3,
              3 => 68,
              9 => '',
              'level' => 0,
              'hier_list' => '68',
            ),
            3 =>
            array (
              0 => 'Rollover Minutes',
              1 => 158,
              2 => 4,
              3 => 158,
              9 => '',
              'level' => 0,
              'hier_list' => '158',
            ),
            4 =>
            array (
              0 => 'Mobile Broadband',
              1 => 70,
              2 => 5,
              3 => 70,
              9 => '',
              'level' => 0,
              'hier_list' => '70',
            ),
            5 =>
            array (
              0 => 'Troubleshooting',
              1 => 71,
              2 => 6,
              3 => 71,
              9 => '',
              'level' => 0,
              'hier_list' => '71',
            ),
            6 =>
            array (
              0 => 'Call Quality',
              1 => 77,
              2 => 1,
              3 => 71,
              4 => 77,
              9 => '',
              'level' => 1,
              'hier_list' => '71,77',
            ),
            7 =>
            array (
              0 => 'Connection Issues',
              1 => 78,
              2 => 2,
              3 => 71,
              4 => 78,
              9 => '',
              'level' => 1,
              'hier_list' => '71,78',
            ),
            8 =>
            array (
              0 => 'Batteries',
              1 => 79,
              2 => 3,
              3 => 71,
              4 => 79,
              9 => '',
              'level' => 1,
              'hier_list' => '71,79',
            ),
        );
        $response = $this->model->getHierPopup(HM_CATEGORIES);
        $this->assertResponseObject($response, 'is_array');
        $this->assertIdentical($expected, $response->result);

        foreach (array(68, 70, 71, 77, 78, 79, 122, 123, 124, 125, 126, 127, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 158, 161) as $categoryID) {
            $response = $this->model->getHierPopup(HM_CATEGORIES, $categoryID);
            $this->assertResponseObject($response, 'is_array', 0, 1);
            $this->assertIdentical(array(), $response->result);
        }

        // Turn on visiblity for 122 for 122->123->124->125-126->152 chain to be visible for following tests
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=1 WHERE tbl = 65 AND id = 122 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}122Visible", true);
        $this->assertIdentical(array('prod_chain' => '1'), $this->model->getHierPopup(HM_PRODUCTS, 1)->result);
        $this->assertIdentical(array('prod_chain' => '1,4'), $this->model->getHierPopup(HM_PRODUCTS, 4)->result);
        $this->assertIdentical(array('prod_chain' => '1,4,159'), $this->model->getHierPopup(HM_PRODUCTS, 159)->result);

        Api::test_sql_exec_direct("UPDATE visibility SET enduser=0 WHERE tbl = 65 AND id = 122 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}122Visible", false);
    }

    function testGetFilter() {
        $getFilter = $this->getMethod('getFilter');
        $this->assertIdentical('Product', $getFilter('prod'));
        $this->assertIdentical('Product', $getFilter('product'));
        $this->assertIdentical('Product', $getFilter(HM_PRODUCTS));
        $this->assertIdentical(HM_PRODUCTS, $getFilter('prod', 'id'));
        $this->assertIdentical('Category', $getFilter('cat'));
        $this->assertIdentical('Category', $getFilter('category'));
        $this->assertIdentical('Category', $getFilter(HM_CATEGORIES));
        $this->assertIdentical(HM_CATEGORIES, $getFilter('cat', 'id'));
    }

    function testGetChainFromObject(){
        $getChainFromObject = $this->getMethod('getChainFromObject');

        $this->assertIdentical(array(null), $getChainFromObject((object)array()));
        $this->assertIdentical(array(7), $getChainFromObject((object)array('ID' => 7)));

        $this->assertIdentical(array(1), $getChainFromObject($this->model->get(1)->result));
        $this->assertIdentical(array(1, 2), $getChainFromObject($this->model->get(2)->result));
        $this->assertIdentical(array(1, 3), $getChainFromObject($this->model->get(3)->result));
        $this->assertIdentical(array(128, 129, 130, 131, 120, 121), $getChainFromObject($this->model->get(121)->result));
        $this->assertIdentical(array(122, 123, 124, 125, 126, 127), $getChainFromObject($this->model->get(127)->result));
    }

    function testGetChildCategories()
    {
        $getChildCategories = $this->getMethod('getChildCategories');

        //Invalid ID
        $this->assertIdentical($getChildCategories(12342134), array());
        $this->assertIdentical($getChildCategories('NaN'), array());

        //Switch first category in branch to visible for upcoming tests
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=1 WHERE tbl = 65 AND id = 122 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}122Visible", true);
        $expected = array(
            122 => true,
            123 => true,
            124 => true,
            125 => true,
            126 => true,
            152 => false
        );
        $this->assertIdentical($getChildCategories(1), $expected);
        $this->assertIdentical($getChildCategories(4), $expected);
        $this->assertIdentical($getChildCategories(159), $expected);

        // @@@ 140818-000045 Make sure chain is still populated when last link is not visibile
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=0 WHERE tbl = 65 AND id = 152 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}152Visible", false);
        $expected = array(
            122 => true,
            123 => true,
            124 => true,
            125 => true,
            126 => false,
        );
        $this->assertIdentical($getChildCategories(1), $expected);
        $this->assertIdentical($getChildCategories(4), $expected);
        $this->assertIdentical($getChildCategories(159), $expected);
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=1 WHERE tbl = 65 AND id = 152 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}152Visible", true);

        // @@@ 140818-000045 Make sure chain stops in middle when it hits a non-visible category
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=0 WHERE tbl = 65 AND id = 125 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}125Visible", false);
        $expected = array(
            122 => true,
            123 => true,
            124 => false,
        );
        $this->assertIdentical($getChildCategories(1), $expected);
        $this->assertIdentical($getChildCategories(4), $expected);
        $this->assertIdentical($getChildCategories(159), $expected);
        Api::test_sql_exec_direct("UPDATE visibility SET enduser=1 WHERE tbl = 65 AND id = 125 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}125Visible", true);

        $expected = array(153 => false);
        $this->assertIdentical($getChildCategories(162), $expected);

        $expected = array(
            122 => true,
            123 => true,
            124 => true,
            125 => true,
            126 => true,
            152 => false,
            153 => false,
            158 => false
        );
        $this->assertIdentical($getChildCategories(163), $expected);

        //Valid Linkage with a maxLevel specified
        $expected = array(
          122 => true,
          123 => false
        );
        $this->assertIdentical($getChildCategories(1, 2), $expected);

        //Valid Linkage with a maxLevel specified
        $expected = array(
          122 => false
        );
        $this->assertIdentical($getChildCategories(1, 1), $expected);

        //No Linked categories
        $this->assertIdentical($getChildCategories(160), array());

        Api::test_sql_exec_direct("UPDATE visibility SET enduser=0 WHERE tbl = 65 AND id = 122 AND interface_id = 1");
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}122Visible", false);
    }

    function testVerifyRequest() {
        $verifyRequest = $this->getMethod('verifyRequest');

        $return = $verifyRequest('prd', 'blah');
        $this->assertTrue(Text::stringContains($return->error->internalMessage, 'filter'));
        $return = $verifyRequest('ct', 'blah');
        $this->assertTrue(Text::stringContains($return->error->internalMessage, 'filter'));

        $return = $verifyRequest('prod', 'blah');
        $this->assertTrue(Text::stringContains($return->error->internalMessage, 'id'));
        $return = $verifyRequest('cat', 'blah');
        $this->assertTrue(Text::stringContains($return->error->internalMessage, 'id'));

        $return = $verifyRequest('prod', '11.1');
        $this->assertTrue(Text::stringContains($return->error->internalMessage, 'id'));
        $return = $verifyRequest('cat', '77e23');
        $this->assertTrue(Text::stringContains($return->error->internalMessage, 'id'));

        $return = $verifyRequest('prod', '222222222222');
        $this->assertTrue(Text::stringContains($return->error->internalMessage, 'id'));
        $return = $verifyRequest('cat', '222222222222');
        $this->assertTrue(Text::stringContains($return->error->internalMessage, 'id'));

        $this->assertNull($verifyRequest('prod', $this->productIDs[0] . ''));
        $this->assertNull($verifyRequest('cat', $this->categoryIDs[0] . ''));
    }

    function testGetPluralizedName(){
        $getPluralizedName = $this->getMethod('getPluralizedName');

        $this->assertIdentical('Categories', $getPluralizedName('Category'));
        $this->assertIdentical('Products', $getPluralizedName('category'));
        $this->assertIdentical('Products', $getPluralizedName('Products'));
        $this->assertIdentical('Products', $getPluralizedName('Product'));
        $this->assertIdentical('Products', $getPluralizedName(null));
        $this->assertIdentical('Products', $getPluralizedName(false));
        $this->assertIdentical('Products', $getPluralizedName(array()));
        $this->assertIdentical('Products', $getPluralizedName(1));
    }

    function testGetObjectsByParent(){
        $getTopLevelObjects = $this->getMethod('getTopLevelObjects');

        try{
            $this->assertTrue(is_array($getTopLevelObjects('foo', 10)));
            $this->fail("No such table exception should be thrown");
        }
        catch(\Exception $e){
            $this->pass();
        }

        $response = $getTopLevelObjects('Product', 10);
        $this->assertTrue(is_array($response));
        $this->assertIdentical(6, count($response));
        foreach($response as $product){
            $this->assertIsA($product, 'RightNow\Connect\v1_4\ServiceProduct');
            $this->assertTrue(count($product->EndUserVisibleInterfaces) > 0);
        }

        $response = $getTopLevelObjects('Product', 2);
        $this->assertIdentical(2, count($response));

        $response = $getTopLevelObjects('Category', 10);
        $this->assertTrue(is_array($response));
        $this->assertIdentical(6, count($response));
        foreach($response as $product){
            $this->assertIsA($product, 'RightNow\Connect\v1_4\ServiceCategory');
            $this->assertTrue(count($product->EndUserVisibleInterfaces) > 0);
        }

        $response = $getTopLevelObjects('Category', 1);
        $this->assertIdentical(1, count($response));

        // Single top level product
        $response = $getTopLevelObjects('Product', 10, array(1));
        $this->assertTrue(is_array($response));
        $this->assertIdentical(1, count($response));
        foreach($response as $product){
            $this->assertIsA($product, 'RightNow\Connect\v1_4\ServiceProduct');
            $this->assertTrue(count($product->EndUserVisibleInterfaces) > 0);
        }

        // Non top level products should be ignored
        $response = $getTopLevelObjects('Product', 10, array(1, 2));
        $this->assertIdentical(1, count($response));
        $this->assertEqual(1, $response[0]->ID);


        // Multiple top level products
        $response = $getTopLevelObjects('Product', 10, array(1, 128, 162, 163));
        $this->assertTrue(is_array($response));
        $this->assertIdentical(4, count($response));
        foreach($response as $product){
            $this->assertIsA($product, 'RightNow\Connect\v1_4\ServiceProduct');
            $this->assertTrue(count($product->EndUserVisibleInterfaces) > 0);
        }

        // Non top level products should be ignored
        $response = $getTopLevelObjects('Product', 10, array(1, 2));
        $this->assertIdentical(1, count($response));
        $this->assertEqual(1, $response[0]->ID);


        // Multiple top level products
        $response = $getTopLevelObjects('Product', 10, array(1, 128, 162, 163));
        $this->assertTrue(is_array($response));
        $this->assertEqual(4, count($response));
        $this->assertEqual(1, $response[0]->ID);
        $this->assertEqual(162, $response[1]->ID);
        $this->assertEqual(163, $response[2]->ID);
        $this->assertEqual(128, $response[3]->ID);
    }

    function testGetDirectDescendantsFromList(){
        $getDirectFromList = $this->getMethod('getDirectDescendantsFromList');

        $response = $getDirectFromList(array(), 'Product', 10);
        $this->assertTrue(is_array($response));
        $this->assertIdentical(0, count($response));

        $response = $getDirectFromList(array((object)(array('ID' => 1))), 'Category', 10);
        $this->assertTrue(is_array($response));
        $this->assertIdentical(0, count($response));

        $expected = array(
            2 => array(
                'ID' => '2',
                'LookupName' => 'Android',
                'DisplayOrder' => '1',
                'ParentID' => '1',
            ),
            3 => array(
                'ID' => '3',
                'LookupName' => 'Blackberry',
                'DisplayOrder' => '2',
                'ParentID' => '1',
            ),
            4 => array(
                'ID' => '4',
                'LookupName' => 'iPhone',
                'DisplayOrder' => '3',
                'ParentID' => '1',
            ),
        );

        $response = $getDirectFromList(array((object)(array('ID' => 1))), 'Product', 10);
        $this->assertIdentical($expected, $response);

        $expected = array(
            2 => array(
                'ID' => '2',
                'LookupName' => 'Android',
                'DisplayOrder' => '1',
                'ParentID' => '1',
            ),
        );

        $response = $getDirectFromList(array((object)(array('ID' => 1))), 'Product', 1);
        $this->assertIdentical($expected, $response);

        $response = $getDirectFromList(array((object)(array('ID' => 1)), (object)(array('ID' => 1))), 'Product', 1);
        $this->assertIdentical($expected, $response);

        $response = $getDirectFromList(array((object)(array('ID' => 1)), (object)(array('ID' => 10))), 'Product', 1);
        $this->assertIdentical($expected, $response);

        $expected = array(
            2 =>array(
                'ID' => '2',
                'LookupName' => 'Android',
                'DisplayOrder' => '1',
                'ParentID' => '1',
            ),
        );

        $response = $getDirectFromList(array((object)(array('ID' => 1)), (object)(array('ID' => 7))), 'Product', 1);
        $this->assertIdentical($expected, $response);

        try{
            $response = $getDirectFromList(array((object)(array('ID' => 1)), (object)(array('ID' => 7))), 'SomethingElse', 1);
            $this->fail('Passed in an invalid Connect object name, should have thrown an ROQL error');
        }
        catch(\Exception $e){}
    }

    function testCombineParentsAndChildren(){
        $combineParentsAndChildren = $this->getMethod('combineParentsAndChildren');

        $response = $combineParentsAndChildren(array(), array(), 1);
        $this->assertTrue(is_array($response));
        $this->assertIdentical(0, count($response));

        $parents = array(
            3 => (object)array(
                'ID'           => 3,
                'LookupName'   => 'product',
                'DisplayOrder' => 2,
            )
        );

        $expected = array(3 => array(
            'id'       => 3,
            'label'    => 'product',
            'seq'      => 2,
            'parent'   => 3,
            'level'    => 0,
            'hierList' => '3'
        ));
        $response = $combineParentsAndChildren($parents, array(), 1);
        $this->assertIdentical($expected, $response);

        $expected[3]['subItems'] = array();
        $response = $combineParentsAndChildren($parents, array(), 2);
        $this->assertIdentical($expected, $response);

        $children = array(
            10 => array(
                'ID'           => 10,
                'LookupName'   => 'child product',
                'DisplayOrder' => 1,
                'ParentID'     => 1
            )
        );
        $response = $combineParentsAndChildren($parents, $children, 2);
        $this->assertIdentical($expected, $response);

        $children[10]['ParentID'] = 3;
        $expected[3]['subItems'] = array(array(
            'id' => 10,
            'label' => 'child product',
            'seq' => 1,
            'parent' => 3,
            'level' => 1,
            'hierList' => "3,10"
        ));

        $response = $combineParentsAndChildren($parents, $children, 2);
        $this->assertIdentical($expected, $response);

        //Even if we have children, if level is still 1, don't add them
        unset($expected[3]['subItems']);
        $response = $combineParentsAndChildren($parents, $children, 1);
        $this->assertIdentical($expected, $response);
    }

    function testGetSortedItems(){
        $getSortedItems = $this->getMethod('getSortedItems');

        $result = $getSortedItems('Product');
        foreach($result as $product){
            $this->assertTrue(is_string($product[0]));
            $this->assertTrue(is_int($product[1]));
            $this->assertTrue(is_int($product[2]));
            $this->assertTrue(is_int($product[3]));
            $this->assertTrue(is_int($product['level']));
            $this->assertTrue(is_string($product['hier_list']));
            $this->assertIdentical('', $product[9]);
            $this->assertIdentical(implode(',', array_filter(array_slice($product, 3, -2))), $product['hier_list']);
        }

        $result = $getSortedItems('Category');
        foreach($result as $product){
            $this->assertTrue(is_string($product[0]));
            $this->assertTrue(is_int($product[1]));
            $this->assertTrue(is_int($product[2]));
            $this->assertTrue(is_int($product[3]));
            $this->assertTrue(is_int($product['level']));
            $this->assertTrue(is_string($product['hier_list']));
            $this->assertIdentical('', $product[9]);
            $this->assertIdentical(implode(',', array_filter(array_slice($product, 3, -2))), $product['hier_list']);
        }
    }

    //@@@ QA 150804-000212 Test product hierarchy IDs to search in
    function testGetProductLevels(){
        $getProductLevels = $this->getMethod('getProductLevels');

        $result = $getProductLevels(12321312); //Invalid ID
        $this->assertIdentical($result, array());

        $expectedValue = array(
            163 => array(0 => "ID", 1 => "Parent", 2 => "Parent.level1"),
            128 => array(0 => "ID", 1 => "Parent", 2 => "Parent.level1"),
            129 => array(0 => "ID", 1 => "Parent", 2 => "Parent.level2"),
            130 => array(0 => "ID", 1 => "Parent", 2 => "Parent.level3"),
            131 => array(0 => "ID", 1 => "Parent", 2 => "Parent.level4"),
            120 => array(0 => "ID", 1 => "Parent"),
            121 => array(0 => "ID"),
        );

        foreach($expectedValue as $id => $expected) {
            $result = $getProductLevels($id);
            $this->assertIdentical($expected, $result);
        }
    }

    function testGetProdCatByIDs(){
        $getProductCategories = $this->getMethod('getProdCatByIDs');

        $result = $getProductCategories('product', array(), true);
        $this->assertTrue(count($result->result) > 0, "Count of total products");

        $result = $getProductCategories('product', array(128));
        $this->assertIdentical($result->result[128]['name'], 'p1');
        $this->assertIdentical($result->result[128]['desc'], null);
    }
}
