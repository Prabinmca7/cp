<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

Use \RightNow\UnitTest\Helper,
    \RightNow\Utils\Text;
class ApiControllerTest extends CPTestCase {

    function testApiv1() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/answers/52',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Vary: Origin"));
        $this->assertTrue(Text::stringContains($response, '"id":"52","type":"answers"'));

        //request with domain not on whitelist
        $response = $this->makeRequest(
            '/ci/api/v1/answers/52',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://rogueTestSite.com',
                    'Accept' => 'application/json, text/javascript, */*; q=0.01'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, " HTTP/1.1 404 Not Found"));
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => $oitCorsWhitelist), true);
    }

    function testAnswerById() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/answers/52',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=600, max-age=600"));
        $this->assertTrue(Text::stringContains($response, '"id":"52","type":"answers"'));
    }

    function testAnswerList() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/answers',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertSame(substr_count($response, '"type":"answers"'), 10);
    }

    function testAnswerUserSearch() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/answers?filter[$content][contains]=question&searchType=user',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: private, max-age=300"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
    }

    function testAnswerSystemSearch() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/answers?filter[$content][contains]=question&searchType=system',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
    }

    function testError() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with wrong attribute
        $response = $this->makeRequest(
            '/ci/api/v1/answers?fields[answers]=dummy',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertFalse(Text::stringContains($response, "Cache-Control:"));
        $this->assertTrue(Text::stringContains($response, 'HTTP/1.1 400 Bad Request'));
    }

    function testCreateIncident() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/incidents',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                ),
                'post' => '{
                    "data": {
                    "type": "incidents",
                    "attributes": {
                    "threads": [{"body": "this is body 4"}],
                    "email": "prashant28@oracle.com",
                    "subject": "this is subject 4"
                    }
                    }
                    }'
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertFalse(Text::stringContains($response, "Cache-Control:"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
    }

    function testCustomFieldApi() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/customFields?filter[type]=incidents&filter[visibility]=endUserEdit&filter[fields]=c$textarea1',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
        $this->assertTrue(Text::stringContains($response, '"type":"customFields"'));
    }

    function testCustomFieldApiWithFilterFieldsAsCommas() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/customFields?filter[type]=incidents&filter[visibility]=endUserEdit&filter[fields]=,,',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertTrue(Text::stringContains($response, '"data":[]'));
    }

    function testCustomFieldApiWithFilterFieldsContainingSpaces() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/customFields?filter[type]=incidents&filter[visibility]=endUserEdit&filter[fields]=c$textarea1,  c$int1',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
        $this->assertTrue(Text::stringContains($response, '"type":"customFields"'));
    }

    function testCustomFieldApiWithoutFieldsFilter() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/customFields?filter[type]=incidents&filter[visibility]=endUserEdit',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, 'HTTP/1.1 400 Bad Request'));
    }

    function testProductsApi() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/products',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
        $this->assertTrue(Text::stringContains($response, '"type":"products"'));
    }

    function testProductByIdApi() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/products/1',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
        $this->assertTrue(Text::stringContains($response, '"type":"products"'));
    }

    function testCategoriesApi() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/categories',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
        $this->assertTrue(Text::stringContains($response, '"type":"categories"'));
    }

    function testCategoriesByIdApi() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/api/v1/categories/161',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/vnd.api+json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Cache-Control: public, s-maxage=900, max-age=900"));
        $this->assertTrue(Text::stringContains($response, '"data":'));
        $this->assertTrue(Text::stringContains($response, '"type":"categories"'));
    }
}
