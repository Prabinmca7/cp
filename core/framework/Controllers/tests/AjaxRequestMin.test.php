<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Utils\Text;

class AjaxRequestMinTest extends CPTestCase {
    function __construct() {
        parent::__construct();
    }

    function verifyErrorRedirect($response, $errorCode, $parameter) {
        $this->assertStatusCode($response, "302 Moved Temporarily");
        $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/$errorCode/errorParameter/$parameter [following]"));
    }

    function testGetBatchHierValues() {
        $base = "/ci/ajaxRequestMin/getbatchhiervalues/linking/0";
        $products = array(1, 128);
        $results = (array) json_decode($this->makeRequest("$base/filter/products/items/" . implode(',', $products)))->result;
        $this->assertEqual($products, array_keys($results));
        $values = array_values($results);
        // Each product has 3 children
        $this->assertEqual(3, count($values[0][0]));
        $this->assertEqual(3, count($values[1][0]));
    }

    function testGetHierValues() {
        $base = "/ci/ajaxRequestMin/gethiervalues";
        // Legit
        $response = $this->makeRequest("$base/filter/products/id/1/");
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertFalse(property_exists($response, 'errors'));

        // Doesn't exist
        $response = $this->makeRequest("$base/filter/products/id/23423");
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertIsA($response->errors, 'array');
        $this->assertSame(1, count($response->errors));

        $response = $this->makeRequest("$base/filter/dispositions/id/2");
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertIsA($response->errors, 'array');
        $this->assertSame(1, count($response->errors));

        // Invalid param
        $response = $this->makeRequest("$base/filter/categories/id/zzz", array('justHeaders' => true));
        $this->verifyErrorRedirect($response, 6, 'id');

        // TK determine if there's a way to set sci_cache_int to test prod linking

        $response = $this->makeRequest("$base/filter/products/id/1/linking/true");
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertFalse(property_exists($response, 'errors'));
        $this->assertFalse(array_key_exists('link_map', $response->result));
        $response = $this->makeRequest("$base/filter/products/id/1/linking/false");
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertFalse(array_key_exists('link_map', $response->result));
        $this->assertFalse(property_exists($response, 'errors'));

        // POST works too
        $response = $this->makeRequest($base, array('flags' => "--post-data 'filter=products&id=2'"));
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertFalse(property_exists($response, 'errors'));
    }

    function testGetAccessibleTreeView() {
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequestMin/getAccessibleTreeView/hm_type/13/linking_on/0'
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->result);
        $this->assertFalse(property_exists($response, 'errors'));
        $this->assertTrue(count($response->result) > 0);
    }

    function testGetCountryValues() {
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequestMin/getCountryValues/country_id/1'
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->Provinces);
        $this->assertFalse(property_exists($response, 'errors'));
        foreach ($response->Provinces as $state) {
            if ($state->ID === 32) {
                $this->assertEqual('MT', $state->Name, 'Montana is gone!');
            }
        }

        //Country with no provinces
        $response = json_decode($this->makeRequest(
            '/ci/AjaxRequestMin/getCountryValues/country_id/3'
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNull($response->Provinces);
        $this->assertFalse(property_exists($response, 'errors'));
    }

    function testGetHierValuesForProductCatalog() {
        $base = "/ci/ajaxRequestMin/getHierValuesForProductCatalog";
        // Legit

        $response = $this->makeRequest("$base/id/222005/level/1");
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertFalse(property_exists($response, 'errors'));

        // Doesn't exist
        $response = $this->makeRequest("$base/id/222005233/level/123");
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertFalse(property_exists($response, 'errors'));

        // Invalid param
        $response = $this->makeRequest("$base/id/zzz/level/zz", array('justHeaders' => true));
        $this->verifyErrorRedirect($response, 6, 'id');

        // POST works too
        $response = $this->makeRequest($base, array('flags' => "--post-data 'id=222005&level=1'"));
        $this->assertNotNull($response = json_decode($response));
        $this->assertIsA($response->result, 'array');
        $this->assertFalse(property_exists($response, 'errors'));
    }

    function testGetAccessibleProductCatalogTreeView() {
        $response = json_decode($this->makeRequest(
            '/ci/ajaxRequestMin/getAccessibleProductCatalogTreeView'
        ));

        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->result);
        $this->assertFalse(property_exists($response, 'errors'));
        $this->assertTrue(count($response->result) > 0);
    }
}
