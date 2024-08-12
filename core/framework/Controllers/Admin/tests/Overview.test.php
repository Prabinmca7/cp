<?php

use RightNow\UnitTest\Helper;

Helper::loadTestedFile(__FILE__);

class OverviewTest extends CPTestCase {
    function testSetLanguage() {
        $interface = \RightNow\Internal\Api::intf_name();
        $redirect = '/ci/admin/versions/manage#tab=0&widget=standard%2Finput%2FFormInput';
        $result = $this->makeRequest("/ci/admin/overview/setLanguage/$interface/en_US/" . urlencode($redirect), array('admin' => true, 'justHeaders' => true));
        $expected = "Set-Cookie: cp_admin_lang_intf={$interface}%7Cen_US;";
        $this->assertStringContains($result, $expected, "Cookie not set: '$expected'");
        $this->assertStatusCode($result, "302 Moved Temporarily");
        $host = \RightNow\Utils\Config::getConfig(OE_WEB_SERVER);
        $expected = "Location: http://{$host}{$redirect} [following]";
        $this->assertStringContains($result, $expected, str_replace('%', '{percent}', "Location not set: $expected"));

        Helper::setConfigValues(array('SEC_ADMIN_HTTPS' => true), true);
        $result = $this->makeRequest("/ci/admin/overview/setLanguage/$interface/en_US/" . urlencode($redirect), array(
            'admin' => true, 'justHeaders' => true, 'useHttps' => true));
        $expected = "Location: https://{$host}{$redirect} [following]";
        $this->assertStringContains($result, $expected, str_replace('%', '{percent}', "Location not set: $expected"));
        Helper::setConfigValues(array('SEC_ADMIN_HTTPS' => false), true);
    }

    function testSetCookie() {
        $scope = $this;

        $decodeData = function($cookie, $mode, $isSecure) use ($scope) {
            $modeToken = urldecode($cookie);
            $modeToken = \RightNow\Api::decode_base64_urlsafe($modeToken);
            $data = explode('|', $isSecure ? \RightNow\Api::ver_ske_decrypt($modeToken) : \RightNow\Api::pw_rev_decrypt($modeToken));
            $data[1] = null;
            $scope->assertIdentical($data[0], (string)crc32($mode));
            return $data;
        };

        $compareData = function($insecureCookie, $secureCookie, $mode) use ($scope, $decodeData) {
            $scope->assertIdentical($decodeData($insecureCookie, $mode, false), $decodeData($secureCookie, $mode, true));
        };

        $getLocationCookie = function($location, $isCPv2 = false) use ($scope) {
            $expected = "Set-Cookie: location=";
            $regex = '/location=[^ ;]+%7E([^ ;]+)/';

            $result = $scope->makeRequest(
                "/ci/admin/" . (!$isCPv2 ? 'overview/' : '') . "set_cookie/$location",
                array('admin' => true, 'justHeaders' => true, 'noDevCookie' => true)
            );
            $scope->assertStringContains($result, $expected, "Cookie not set: '$expected'");
            preg_match($regex, $result, $matches);
            return $matches[1];
        };

        $productionSecure = $getLocationCookie('production');
        $stagingSecure = $getLocationCookie('staging_01');
        $developmentSecure = $getLocationCookie('development');
        $referenceSecure = $getLocationCookie('reference');

        $frameworkVersionFiles = array(
            'production' => OPTIMIZED_FILES . 'production/optimized/frameworkVersion',
            'staging' => OPTIMIZED_FILES . 'staging/staging_01/optimized/frameworkVersion',
            'development' => CUSTOMER_FILES . 'frameworkVersion',
        );

        $frameworkVersions = array(
            'production' => file_get_contents($frameworkVersionFiles['production']),
            'staging' => file_get_contents($frameworkVersionFiles['staging']),
            'development' => file_get_contents($frameworkVersionFiles['development']),
        );

        file_put_contents($frameworkVersionFiles['development'], "3.3");
        file_put_contents($frameworkVersionFiles['staging'], "3.3");
        file_put_contents($frameworkVersionFiles['production'], "3.3");

        $productionInsecure = $getLocationCookie('production');
        $stagingInsecure = $getLocationCookie('staging_01');
        $developmentInsecure = $getLocationCookie('development');
        $referenceInsecure = $getLocationCookie('reference');

        // production is always secure, since the cookie is just set to expire
        $this->assertIdentical($decodeData($productionInsecure, 'production', true), $decodeData($productionSecure, 'production', true));
        $compareData($stagingInsecure, $stagingSecure, 'staging_01');
        $compareData($developmentInsecure, $developmentSecure, 'development');
        $compareData($referenceInsecure, $referenceSecure, 'reference');

        file_put_contents($frameworkVersionFiles['development'], "2.0");
        file_put_contents($frameworkVersionFiles['staging'], "2.0");
        file_put_contents($frameworkVersionFiles['production'], "2.0");

        $productionInsecure = $getLocationCookie('production', true);
        $stagingInsecure = $getLocationCookie('staging_01', true);
        $developmentInsecure = $getLocationCookie('development', true);
        $referenceInsecure = $getLocationCookie('reference', true);

        // production is insecure for CPv2
        $compareData($productionInsecure, $productionSecure, 'production');
        $compareData($stagingInsecure, $stagingSecure, 'staging_01');
        $compareData($developmentInsecure, $developmentSecure, 'development');
        $compareData($referenceInsecure, $referenceSecure, 'reference');

        file_put_contents($frameworkVersionFiles['development'], $frameworkVersions['development']);
        file_put_contents($frameworkVersionFiles['staging'], $frameworkVersions['staging']);
        file_put_contents($frameworkVersionFiles['production'], $frameworkVersions['production']);

        $result = $this->makeRequest("/ci/admin/overview/set_cookie/development/true/");
        $abuseMode = \RightNow\Libraries\AbuseDetection::isForceAbuseCookieSet() ? 'true' : 'false';
        $this->assertIdentical( $abuseMode, 'false');
    }
}
