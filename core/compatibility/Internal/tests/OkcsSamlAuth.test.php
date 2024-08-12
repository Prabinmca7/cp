<?php
use RightNow\Internal\OkcsSamlAuth,
    \RightNow\Controllers\UnitTest,
    \RightNow\Controllers,
    \RightNow\UnitTest\Helper,
    \RightNow\Api;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsSamlAuthClassTest extends CPTestCase
{
    public $testingClass = 'RightNow\Internal\OkcsSamlAuth';

    public function Content()
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>' . "\r\n\r\n" .
                   '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" >' . "\r\n\r\n" .
                   '<soap:Body><EnlightenResponse xmlns="http://xyz.com/"/></soap:Body></soap:Envelope>';
        return $content;
    }
    public function testGetSamlToken()
    {   
        $testSpFromDb = \RightNow\Internal\Sql\Okcs::getOkcsServiceProviderDetails();
        $testSp = array(
            'sp_id' => 3,
            'sp_enabled' => 1,
            'sp_acs_url' => 'https://oracle.com.sso.example3/saml/sp/post',
            'app_url' => 'https://oracle.com.sso.example2/apps/inq',
            'app_enabled' => 1
        );
        $getSamlToken = $this->getMethod('getSamlToken');
        $this->assertNotNull($getSamlToken($testSpFromDb));
        $this->assertNotNull($getSamlToken($testSp));
        $testSp['sp_acs_url'] = 'https://xyz.com/xyz';
    }

    public function testCheckEnabled()
    {
        $testSp = array(
            'sp_id' => 3, 
            'sp_enabled' => 1,
            'sp_acs_url' => 'https://oracle.com.sso.example3/saml/sp/post',
            'app_url' => 'https://oracle.com.sso.example2/apps/inq',
            'app_enabled' => 1
        );

        $CheckEnabled = $this->getMethod('CheckEnabled');
        $this->assertTrue($CheckEnabled($testSp));

        $testSp['sp_enabled'] = 0;
        $testSp['app_enabled'] = 0;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = 1;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = 0;
        $testSp['app_enabled'] = 1;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = True;
        $testSp['app_enabled'] = True;
        $this->assertTrue($CheckEnabled($testSp));

        $testSp['sp_enabled'] = False;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = True;
        $testSp['app_enabled'] = False;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = False ;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = null;
        $testSp['app_enabled'] = null;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = 1;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = null;
        $testSp['app_enabled'] = 1;
        $this->assertFalse($CheckEnabled($testSp));

        $testSp['sp_enabled'] = 1 ;
        $this->assertTrue($CheckEnabled($testSp));

    }

    public function testValidateServiceProviderDetails()
    {
        $testSp = array(
            'sp_id' => 3, 
            'sp_enabled' => 1,
            'sp_acs_url' => null,
            'app_url' => 'https://oracle.com.sso.example2/apps/inq',
            'app_enabled' => 1
        );
        $validateServiceProviderDetails = $this->getMethod('validateServiceProviderDetails');
        $this->assertFalse($validateServiceProviderDetails($testSp));
       
        $testSp['app_url'] = null;
        $this->assertFalse($validateServiceProviderDetails($testSp));

        $testSp['sp_acs_url'] = 'https://oracle.com.sso.example3/saml/sp/post';
        $this->assertFalse($validateServiceProviderDetails($testSp));

        $testSp['sp_id'] = null;
        $this->assertFalse($validateServiceProviderDetails($testSp));

        $testSp['sp_id'] = 3;
        $testSp['app_url'] = 'https://oracle.com.sso.example2/apps/inq';
        $this->assertTrue($validateServiceProviderDetails($testSp));

        $testSp['sp_id'] = null;
        $this->assertFalse($validateServiceProviderDetails($testSp));
    }

    public function testGetContentBody()
    {
        $content = self::Content();
        $contentTest = '<soap:Body><EnlightenResponse xmlns="http://xyz.com/"/></soap:Body></soap:Envelope>';
        $getContentBody = $this->getMethod('getContentBody');
        $this->assertNotNull($getContentBody($content));
        $this->assertIdentical($getContentBody($content), $contentTest);
    }
}
