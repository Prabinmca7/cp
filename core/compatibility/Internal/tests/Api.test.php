<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Api,
    RightNow\Utils\Text,
    RightNow\UnitTest\Helper;

class InternalApiTest extends CPTestCase
{
    public $testingClass = 'RightNow\Internal\Api';

    public function testCertPath(){
        $this->assertTrue(\RightNow\Utils\Text::endsWith(Api::cert_path(), '.db/certs'));
    }

    public function testSiebelEnabled(){
        $method = $this->getMethod('siebelEnabled');

        $previousValues = Helper::getConfigValues(array('SIEBEL_EAI_HOST'));

        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => ''));
        $this->assertFalse($method());

        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => ' '));
        $this->assertFalse($method());

        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => 'bob'));
        $this->assertTrue($method());

        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => ' bob '));
        $this->assertTrue($method());

        Helper::setConfigValues($previousValues);
    }

    public function testValidateCountryAndProvince(){
        $method = $this->getMethod('validateCountryAndProvince');

        $this->assertIdentical(array(1,2,3), $method(array(1,2,3)));
        $this->assertIdentical(array('foo' => 'bar'), $method(array('foo' => 'bar')));
        $this->assertIdentical(array('city' => 'Bozeman', 'street' => 'Discovery Dr'), $method(array('city' => 'Bozeman', 'street' => 'Discovery Dr')));

        $this->assertIdentical(array('prov_id' => INT_NULL, 'country_id' => INT_NULL), $method(array('prov_id' => INT_NULL, 'country_id' => INT_NULL)));
        $this->assertIdentical(array('prov_id' => INT_NOT_SET, 'country_id' => INT_NOT_SET), $method(array('prov_id' => INT_NOT_SET, 'country_id' => INT_NOT_SET)));

        $this->assertIdentical(array(), $method(array('country_id' => 10000, 'prov_id' => 10000)));
        $this->assertIdentical(array('country_id' => 1), $method(array('country_id' => 1, 'prov_id' => 10000)));
        $this->assertIdentical(array('prov_id' => 1), $method(array('prov_id' => 1)));

        //Invalid country-state combinations
        $this->assertIdentical(array('country_id' => 1), $method(array('country_id' => 1, 'prov_id' => 60)));
        $this->assertIdentical(array('country_id' => 2), $method(array('country_id' => 2, 'prov_id' => 1)));

        //Valid combinations
        $this->assertIdentical(array('country_id' => 1, 'prov_id' => 30), $method(array('country_id' => 1, 'prov_id' => 30)));
        $this->assertIdentical(array('country_id' => 2, 'prov_id' => 60), $method(array('country_id' => 2, 'prov_id' => 60)));
    }

    public function testCheckForValidState(){
        $method = $this->getMethod('checkForValidState');

        $this->assertFalse($method(array(), 1));
        $this->assertFalse($method(array(), 'abc'));
        $this->assertFalse($method(array(), null));
        $this->assertFalse($method(array(), true));

        $this->assertFalse($method(array((object)array('ID' => 1)), 0));
        $this->assertFalse($method(array((object)array('ID' => 1)), 2));
        $this->assertTrue($method(array((object)array('ID' => 1)), 1));
        $this->assertTrue($method(array((object)array('ID' => 1), (object)array('ID' => 2), (object)array('ID' => 900)), 1));
        $this->assertTrue($method(array((object)array('ID' => 1), (object)array('ID' => 2), (object)array('ID' => 900)), 2));
        $this->assertTrue($method(array((object)array('ID' => 1), (object)array('ID' => 2), (object)array('ID' => 900)), 900));
        $this->assertTrue($method(array((object)array('ID' => 1), (object)array('ID' => 2), (object)array('ID' => 900)), "900"));
        $this->assertTrue($method(array((object)array('ID' => 1), (object)array('ID' => 2), (object)array('ID' => 900)), 900.51));
        $this->assertFalse($method(array((object)array('ID' => 1), (object)array('ID' => 2), (object)array('ID' => 900)), 901));
    }

    function fetch($objectID, $mode) {
        require_once(CPCORE . 'Internal/Utils/VersionTracking.php');
        $reportID = \RightNow\Internal\Utils\VersionTracking::$reports['versions'];
        $filters = null;
        $rows = get_instance()->model('Report')->getDataHTML(
            $reportID,
            null,
            $filters,
            array(),
            false,
            true
        )->result['data'] ?: array();

        foreach($rows as $row) {
            if ($objectID === intval($row[0]) && $row[2] === $mode) {
                return $row;
            }
        }

    }

    function testCpObjectMethods() {
        $objectName = 'search/MegaSearch' . microtime(true);
        $objectType = CP_OBJECT_TYPE_WIDGET_CUSTOM;
        $mode = CP_OBJECT_MODE_DEVELOPMENT;

        $api = function($method, $pairData) {
            $method = "cp_object_{$method}";
            $result = Api::$method($pairData);
            return $result;
        };

        $getPairData = function($id, $mode, $action, $version = null) {
            $pairData = array(
                'cp_object_id' => $id,
                'version' => array(
                    'version_item' => array(
                        'mode' => $mode,
                        'interface_id' => 1,
                        'action' => $action,
                    )
                )
            );
            if ($version) {
                $pairData['version']['version_item']['version'] = $version;
            }
            return $pairData;
        };

        // CREATE row in cp_objects
        $objectID = $api('create', array(
            'name' => $objectName,
            'type' => $objectType,
        ));
        $this->assertIsA($objectID, 'int');
        $this->assertTrue($objectID > 0);

        // GET row from cp_objects
        $result = $api('get', array('id' => $objectID));
        $expected = array(
            'cp_object_id' => $objectID,
            'name' => $objectName,
            'type' => $objectType,
        );
        $this->assertIdentical($expected, $result);

        // ADD row to cp_object_versions using cp_object_update
        $version = '3.3.3';
        $this->assertEqual(1, $api('update', $getPairData($objectID, $mode, ACTION_ADD, $version)));
        $row = $this->fetch($objectID, $mode);
        $this->assertEqual($objectType, $row[1]);
        $this->assertEqual($mode, $row[2]);
        $this->assertEqual($objectName, $row[3]);
        $this->assertEqual($version, $row[4]);

        // ADD another row to cp_object_versions for a different mode (development|staging|production)
        $this->assertEqual(1, $api('update', $getPairData($objectID, CP_OBJECT_MODE_PRODUCTION, ACTION_ADD, $version)));
        $row = $this->fetch($objectID, CP_OBJECT_MODE_PRODUCTION);
        $this->assertEqual($objectType, $row[1]);
        $this->assertEqual(CP_OBJECT_MODE_PRODUCTION, $row[2]);
        $this->assertEqual($objectName, $row[3]);
        $this->assertEqual($version, $row[4]);

        // UPDATE existing row in cp_object_versions using cp_object_update
        $version = '3.4.5';
        $this->assertEqual(1, $api('update', $getPairData($objectID, $mode, ACTION_UPD, $version)));
        $row = $this->fetch($objectID, $mode);
        $this->assertEqual($version, $row[4]);

        // GET rows from cp_object_versions using cp_object_get
        $return = $api('get', array(
            'id' => $objectID,
            'sub_tbl' => array(
                'tbl_id1' => TBL_CP_OBJECT_VERSIONS,
            ),
        ));
        $this->assertEqual($objectID, $return['cp_object_id']);
        $this->assertEqual($objectName, $return['name']);
        $this->assertEqual($objectType, $return['type']);

        $productionEntry = $return['version']['version_item0'];
        $this->assertEqual(CP_OBJECT_MODE_PRODUCTION, $productionEntry['mode']);
        $this->assertEqual('3.3.3', $productionEntry['version']);

        $developmentEntry = $return['version']['version_item1'];
        $this->assertEqual($mode, $developmentEntry['mode']);
        $this->assertEqual($version, $developmentEntry['version']);

        // DELETE a row from cp_object_versions using cp_object_update
        $this->assertEqual(1, $api('update', $getPairData($objectID, $mode, ACTION_DEL)));
        $this->assertNull($this->fetch($objectID, $mode));

        // DELETE row from cp_objects
        $this->assertIdentical(1, $api('destroy', array('cp_object_id' => $objectID)));
    }

    public function testGetExistingChannelFields(){
        $method = $this->getMethod('getExistingChannelFields');

        $result = $method('asdf346264');
        $this->assertTrue(is_array($result));
        $this->assertIdentical(0, count($result));

        $result = $method('mmyer@rightnow.com.invalid');
        $this->assertTrue(is_array($result));
        $this->assertIdentical(0, count($result));

        $result = $method('ddespain@rightnow.com');
        $this->assertTrue(is_array($result));
        $this->assertIdentical(1, count($result));
        $this->assertIdentical('AContactUsername', $result[1]['Username']);
        $this->assertIdentical('userid', $result[1]['UserNumber']);

        test_sql_exec_direct("insert into sm_users (c_id, chan_type_id, username, user_ref) values (1268, 11, 'fakeTwitter', 'user_ref')");

        $result = $method('eturner@rightnow.com.invalid');
        $this->assertTrue(is_array($result));
        $this->assertIdentical(1, count($result));
        $this->assertIdentical('fakeTwitter', $result[11]['Username']);
        $this->assertIdentical('user_ref', $result[11]['UserNumber']);
    }

    public function testGetListOfModifiedChannelFields(){
        $method = $this->getMethod('getListOfModifiedChannelFields');

        $result = $method(array(), 'ddespain@rightnow.com');
        $this->assertIdentical($result, array(array('chan_type_id' => 1, 'username' => '')));
        $result = $method(array(array('chan_type_id' => 1, 'username' => 'AContactUsername')), 'ddespain@rightnow.com');
        $this->assertIdentical($result, array());
        $result = $method(array(array('chan_type_id' => 11, 'username' => 'fbusername')), 'ddespain@rightnow.com');
        $this->assertIdentical($result, array(array('chan_type_id' => 11, 'username' => 'fbusername'), array('chan_type_id' => 1, 'username' => '')));

        $result = $method(array(array('chan_type_id' => 1, 'username' => 'newusername')), 'ddespain@rightnow.com');
        $this->assertIdentical($result, array(array('chan_type_id' => 1, 'username' => 'newusername')));

        $result = $method(array(array('chan_type_id' => 1, 'username' => 'AContactUsername'), array('chan_type_id' => 11, 'username' => 'fbusername')), 'ddespain@rightnow.com');
        $this->assertIdentical($result, array(array('chan_type_id' => 11, 'username' => 'fbusername')));
    }

    public function testMemcacheValueSet(){
        $method = $this->getMethod('memcache_value_set');

        $result = $method(MEMCACHE_TYPE_CP_GENERIC, 'usefulKey', 'usefulValue', 1);
        $this->assertNull($result, 0);

        $tooBigValue = 'tooBigValue' . str_pad('.', 2000000, '.');
        try {
            memcache_value_set(MEMCACHE_TYPE_CP_GENERIC, 'usefulKey', $tooBigValue, 1);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertIdentical($e->getMessage(), 'ITEM TOO BIG');
        }

        $method(MEMCACHE_TYPE_CP_GENERIC, 'usefulKey', $tooBigValue, 1);
        $this->assertNull($result, 0);
    }

    public function testFattachFullPath() {
        $fattach_full_method = $this->getMethod('fattach_full_path');

        $result = $fattach_full_method("walrus");
        $this->assertTrue(Text::stringContains($result, "/tmp/walrus"));
        $result = $fattach_full_method("walrus", true);
        $this->assertTrue(Text::stringContains($result, "/prod_tmp/walrus"));
        $result = $fattach_full_method("walrus.shoes", true);
        $this->assertTrue(Text::stringContains($result, "/prod_tmp/walrus.shoes"));
    }

    /**
     * @@@ 210414-000040 PTA login error due to missing source level
     * contact_get is called by createOrUpdateChannelFields which
     * started failing during PTA after Core started enforcing source levels
     */
    public function testContactGet() {
        $smUsers = contact_get(array(
            'id'      => 1,
            'sub_tbl' => array('tbl_id' => TBL_SM_USERS),
            'source_in' => array(
                'lvl_id1' => SRC1_EU,
                'lvl_id2' => SRC2_EU_PASSTHRU,
            ))
        );
        $this->assertNotNull($smUsers);
    }
}
