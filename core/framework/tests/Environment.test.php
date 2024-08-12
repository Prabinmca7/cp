<?php

use RightNow\Environment,
    RightNow\Utils\Text;

//@@@ QA 130208-000024 Add tests for RightNow\Environment namespace methods
class EnvironmentTest extends CPTestCase {
    function testSetProductionValues(){
        $applicationFolder = $optimizedAssets = $deployTimestamp = null;

        Environment\setProductionValues($applicationFolder, $optimizedAssets, $deployTimestamp);
        $this->assertIdentical(OPTIMIZED_FILES . 'production/optimized/', $applicationFolder);
        $this->assertIdentical(HTMLROOT . '/euf/generated/optimized', $optimizedAssets);
        $this->assertIdentical(OPTIMIZED_FILES . 'production/deployTimestamp', $deployTimestamp);
    }

    function testRetrieveModeAndModeTokenFromCookie(){
        $originalLocation = $_COOKIE['location'];

        $_COOKIE['location'] = "development~abc";
        $this->assertIdentical(array('development', 'abc'), Environment\retrieveModeAndModeTokenFromCookie());
        $_COOKIE['location'] = "developmentabc";
        $this->assertIdentical(array('developmentabc'), Environment\retrieveModeAndModeTokenFromCookie());
        $_COOKIE['location'] = "development~staging~production";
        $this->assertIdentical(array('development', 'staging', 'production'), Environment\retrieveModeAndModeTokenFromCookie());
        $_COOKIE['location'] = "~";
        $this->assertIdentical(array('', ''), Environment\retrieveModeAndModeTokenFromCookie());

        $_COOKIE['location'] = $originalLocation;
    }

    function testVerifyOnlyOneModeDefineIsTrue(){
        try{
            Environment\verifyOnlyOneModeDefineIsTrue(array());
            $this->fail();
        }
        catch(\Exception $e){
            $this->pass();
        }

        try{
            Environment\verifyOnlyOneModeDefineIsTrue(array('1', '2', '3'));
            $this->fail();
        }
        catch(\Exception $e){
            $this->pass();
        }

        try{
            Environment\verifyOnlyOneModeDefineIsTrue(array('E_ERROR', 'E_WARNING', 'E_PARSE'));
            $this->fail();
        }
        catch(\Exception $e){
            $this->pass();
        }

        try{
            Environment\verifyOnlyOneModeDefineIsTrue(array('ZEND_THREAD_SAFE', 'TRUE'));
            $this->fail();
        }
        catch(\Exception $e){
            $this->pass();
        }
        $this->assertNull(Environment\verifyOnlyOneModeDefineIsTrue(array('ZEND_THREAD_SAFE', 'IS_HOSTED', 'IS_TARBALL_DEPLOY')));
    }

    function testFixHttpAuthenticationHeader(){
        $originalAuthValue = $_SERVER['HTTP_AUTHORIZATION'];
        $originalRedirectAuthValue = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];

        unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        Environment\fixHttpAuthenticationHeader();
        $this->assertIdentical($originalAuthValue, $_SERVER['HTTP_AUTHORIZATION']);

        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'redirect';
        Environment\fixHttpAuthenticationHeader();
        $this->assertIdentical('redirect', $_SERVER['HTTP_AUTHORIZATION']);

        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = true;
        Environment\fixHttpAuthenticationHeader();
        $this->assertIdentical(true, $_SERVER['HTTP_AUTHORIZATION']);

        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 1;
        Environment\fixHttpAuthenticationHeader();
        $this->assertIdentical(1, $_SERVER['HTTP_AUTHORIZATION']);

        $_SERVER['HTTP_AUTHORIZATION'] = $originalAuthValue;
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = $originalRedirectAuthValue;
    }

    function testXssSanitize(){
        $originalEnvironmentVariables = array($_SERVER['QUERY_STRING'], $_SERVER['REQUEST_URI'], $_SERVER['HTTP_REFERER'], $_SERVER['REDIRECT_URL'], $_GET, $_POST, $_REQUEST);

        $_SERVER['QUERY_STRING'] = "<div>";
        $_SERVER['REQUEST_URI'] = "\" and '";
        $_SERVER['HTTP_REFERER'] = 'javascript: javascript&#58; javascript&#x3A javascript%3A javascript%253A javascript%25253A';
        $_SERVER['REDIRECT_URL'] = "\t\0 and %09";

        Environment\xssSanitize();
        $this->assertIdentical($_SERVER['QUERY_STRING'], "&lt;div&gt;");
        $this->assertIdentical($_SERVER['REQUEST_URI'], "&quot; and &#039;");
        $this->assertIdentical($_SERVER['HTTP_REFERER'], "javascript  javascript  javascript  javascript  javascript  javascript ");
        $this->assertIdentical($_SERVER['REDIRECT_URL'], "     and     ");

        list($_SERVER['QUERY_STRING'], $_SERVER['REQUEST_URI'], $_SERVER['HTTP_REFERER'], $_SERVER['REDIRECT_URL'], $_GET, $_POST, $_REQUEST) = $originalEnvironmentVariables;
    }

    function testEscapeArrayOfData(){
        $simpleData = array('key' => 'value');
        $this->assertIdentical($simpleData, Environment\escapeArrayOfData($simpleData, false));

        $this->assertIdentical(array('key' => '&lt;value&gt;'), Environment\escapeArrayOfData(array('key' => '<value>'), false));
        $this->assertIdentical(array('&lt;key&gt;' => 'value'), Environment\escapeArrayOfData(array('<key>' => 'value'), false));

        $input = array('<one>' => array('%3Csub%3E' => 'second"Level%27%22', array('thirdLevel' => "\"'\t\0%09")), '<two>' => '<three>');

        $this->assertIdentical(array('&lt;one&gt;' => array('&lt;sub&gt;' => 'second"Level%27%22', array('thirdLevel' => '"\'        ')), '&lt;two&gt;' => '&lt;three&gt;'), Environment\escapeArrayOfData($input, false));
        $this->assertIdentical(array('&lt;one&gt;' => array('&lt;sub&gt;' => 'second&quot;Level&#039;&quot;', array('thirdLevel' => '&quot;&#039;        ')), '&lt;two&gt;' => '&lt;three&gt;'), Environment\escapeArrayOfData($input, true));
    }

    function testXssSanitizeReplacer() {
        $this->assertIdentical(0, Environment\xssSanitizeReplacer(0));
        $this->assertIdentical(1, Environment\xssSanitizeReplacer(1));
        $this->assertIdentical(-1, Environment\xssSanitizeReplacer(-1));
        $this->assertIdentical(1.532, Environment\xssSanitizeReplacer(1.532));
        $this->assertIdentical('', Environment\xssSanitizeReplacer(array()));
        $this->assertIdentical('', Environment\xssSanitizeReplacer(array('<script>')));
        $this->assertIdentical('', Environment\xssSanitizeReplacer((object)array('<script>')));

        $this->assertIdentical('&lt;', Environment\xssSanitizeReplacer('<'));
        $this->assertIdentical('&lt;', Environment\xssSanitizeReplacer('%3C'));
        $this->assertIdentical('&lt;', Environment\xssSanitizeReplacer('%253C'));
        $this->assertIdentical('&gt;', Environment\xssSanitizeReplacer('>'));
        $this->assertIdentical('&gt;', Environment\xssSanitizeReplacer('%3E'));
        $this->assertIdentical('&gt;', Environment\xssSanitizeReplacer('%253E'));
        $this->assertIdentical('&quot;', Environment\xssSanitizeReplacer('"'));
        $this->assertIdentical('&quot;', Environment\xssSanitizeReplacer('%22'));
        $this->assertIdentical('&quot;', Environment\xssSanitizeReplacer('%2522'));
        $this->assertIdentical('&#039;', Environment\xssSanitizeReplacer("'"));
        $this->assertIdentical('&#039;', Environment\xssSanitizeReplacer("%27"));
        $this->assertIdentical('&#039;', Environment\xssSanitizeReplacer("%2527"));
        $this->assertIdentical('"', Environment\xssSanitizeReplacer('"', false));
        $this->assertIdentical('%22', Environment\xssSanitizeReplacer('%22', false));
        $this->assertIdentical('%2522', Environment\xssSanitizeReplacer('%2522', false));
        $this->assertIdentical("'", Environment\xssSanitizeReplacer("'", false));
        $this->assertIdentical("%27", Environment\xssSanitizeReplacer("%27", false));
        $this->assertIdentical("%2527", Environment\xssSanitizeReplacer("%2527", false));

        $this->assertIdentical('javascript ', Environment\xssSanitizeReplacer('javascript:'));
        $this->assertIdentical('javascript ', Environment\xssSanitizeReplacer('javascript&#58;'));
        $this->assertIdentical('javascript ', Environment\xssSanitizeReplacer('javascript&#58'));
        $this->assertIdentical('javascript ', Environment\xssSanitizeReplacer('javascript&#x3A;'));
        $this->assertIdentical('javascript ', Environment\xssSanitizeReplacer('javascript&#x3A'));
        $this->assertIdentical('javascript ', Environment\xssSanitizeReplacer('javascript&#x3a'));
        $this->assertIdentical('javascript ', Environment\xssSanitizeReplacer('javascript%3A'));
        $this->assertIdentical('javascript ', Environment\xssSanitizeReplacer('javascript%3a'));

        $this->assertIdentical('', Environment\xssSanitizeReplacer("\0"));
        $this->assertIdentical('    ', Environment\xssSanitizeReplacer("\t"));
        $this->assertIdentical('    ', Environment\xssSanitizeReplacer("%09"));

        $this->assertIdentical('&lt;a href=javascript alert(&quot;xss&quot;);&gt;click here&lt;/a&gt;', Environment\xssSanitizeReplacer("<a href=javascript:alert(\"xss\");>click here</a>"));
        $this->assertIdentical('javascript  javascript  with [] and [    ] and [    ]', Environment\xssSanitizeReplacer("javascript&#58 javascript&#x3A; with [\0] and [\t] and [%09]"));
    }

    function testIgnoringQueryStringParameters(){
        $options = array(
            'justHeaders' => true,
        );
        $output = $this->makeRequest("/?foo=bar", $options);
        $this->assertTrue(Text::stringContains($output, 'HTTP/1.1 200'), "Request to /app?foo=bar did not return 200 success code: <pre>$output</pre>");

        $output = $this->makeRequest("/app?foo=bar", $options);
        $this->assertTrue(Text::stringContains($output, 'HTTP/1.1 200'), "Request to /app?foo=bar did not return 200 success code: <pre>$output</pre>");

        $output = $this->makeRequest("/app/?foo=bar", $options);
        $this->assertTrue(Text::stringContains($output, 'HTTP/1.1 200'), "Request to /app/?foo=bar did not return 200 success code: <pre>$output</pre>");

        $output = $this->makeRequest("/app/home?foo=bar", $options);
        $this->assertTrue(Text::stringContains($output, 'HTTP/1.1 200'), "Request to /app/home?foo=bar did not return 200 success code: <pre>$output</pre>");

        $options['justHeaders'] = false;
        $output = $this->makeRequest("/app/home/kw/roam?foo=bar", $options);
        $this->assertTrue(Text::stringContains($output, 'value="roam"'), "Request to /app/home/kw/roam?foo=bar did not return a input element prefilled with keyword search term");
    }

    function testKeepQueryStringParameters(){
        $output = $this->makeRequest("/cgi-bin/intf.cfg/php/enduser/doc_serve.php?5=4", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($output, "Location: /ci/redirect/enduser/enduser/doc_serve.php?5=4"));

        $output = $this->makeRequest("/ci/redirect/enduser/enduser/doc_serve.php?5=4", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($output, "Location: http://" . \RightNow\Utils\Config::getConfig(OE_WEB_SERVER) . "/ci/documents/detail/5/4"));
    }

    function testSanitizeHeaders(){
        $originalUserAgent = null;
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $originalUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $originalAcceptCharset = null;
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET']))
            $originalAcceptCharset = $_SERVER['HTTP_ACCEPT_CHARSET'];
        $originalRequestUri = null;
        if (isset($_SERVER['REQUEST_URI']))
            $originalRequestUri = $_SERVER['REQUEST_URI'];

        unset($_SERVER['HTTP_USER_AGENT']);
        Environment\sanitizeHeaders();
        $this->assertNull($_SERVER['HTTP_USER_AGENT']);

        unset($_SERVER['HTTP_ACCEPT_CHARSET']);
        Environment\sanitizeHeaders();
        $this->assertNull($_SERVER['HTTP_ACCEPT_CHARSET']);

        unset($_SERVER['REQUEST_URI']);
        Environment\sanitizeHeaders();
        $this->assertNull($_SERVER['REQUEST_URI']);

        $this->verifySanitizeHeaders('HTTP_USER_AGENT', "\0", "%00");
        $this->verifySanitizeHeaders('HTTP_ACCEPT_CHARSET', "\0", "%00");
        $this->verifySanitizeHeaders('REQUEST_URI', "\0", "\0");

        $this->verifySanitizeHeaders('HTTP_USER_AGENT', "\t", "%09");
        $this->verifySanitizeHeaders('HTTP_ACCEPT_CHARSET', "\t", "%09");
        $this->verifySanitizeHeaders('REQUEST_URI', "\t", "\t");

        $this->verifySanitizeHeaders('HTTP_USER_AGENT', "%09", "%09");
        $this->verifySanitizeHeaders('HTTP_ACCEPT_CHARSET', "%09", "%09");
        $this->verifySanitizeHeaders('REQUEST_URI', "%09", "%09");

        $this->verifySanitizeHeaders('HTTP_USER_AGENT', "Windows\xc2Great", "Windows%c2Great");
        $this->verifySanitizeHeaders('HTTP_ACCEPT_CHARSET', "Windows\xc2Great", "Windows%c2Great");
        $this->verifySanitizeHeaders('REQUEST_URI', "Windows\xc2Great", "Windows\xc2Great");

        $this->verifySanitizeHeaders('HTTP_USER_AGENT', "Windows\xa0Great", "Windows%a0Great");
        $this->verifySanitizeHeaders('HTTP_ACCEPT_CHARSET', "Windows\xa0Great", "Windows%a0Great");
        $this->verifySanitizeHeaders('REQUEST_URI', "Windows\xa0Great", "Windows\xa0Great");

        $this->verifySanitizeHeaders('HTTP_USER_AGENT', "Windows\xc2\xa0Great", "Windows Great");
        $this->verifySanitizeHeaders('HTTP_ACCEPT_CHARSET', "Windows\xc2\xa0Great", "Windows%c2%a0Great");
        $this->verifySanitizeHeaders('REQUEST_URI', "Windows\xc2\xa0Great", "Windows\xc2\xa0Great");

        $this->verifySanitizeHeaders('HTTP_USER_AGENT', "Windows\xc3Bad", "Windows%c3Bad");
        $this->verifySanitizeHeaders('HTTP_ACCEPT_CHARSET', "Windows\xc3Bad", "Windows%c3Bad");
        $this->verifySanitizeHeaders('REQUEST_URI', "Windows\xc3Bad", "Windows\xc3Bad");

        $this->verifySanitizeHeaders('HTTP_USER_AGENT', "Mozilla Whatever it's great (asdf) \" yeah!!!", "Mozilla Whatever it's great (asdf) \" yeah!!!");
        $this->verifySanitizeHeaders('HTTP_ACCEPT_CHARSET', "Mozilla Whatever it's great (asdf) \" yeah!!!", "Mozilla Whatever it's great (asdf) \" yeah!!!");
        $this->verifySanitizeHeaders('REQUEST_URI', "Mozilla Whatever it's great (asdf) \" yeah!!!", "Mozilla Whatever it's great (asdf) \" yeah!!!");

        if ($originalUserAgent !== null)
            $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
        else
            unset($_SERVER['HTTP_USER_AGENT']);
        if ($originalAcceptCharset !== null)
            $_SERVER['HTTP_ACCEPT_CHARSET'] = $originalAcceptCharset;
        else
            unset($_SERVER['HTTP_ACCEPT_CHARSET']);
        if ($originalRequestUri !== null)
            $_SERVER['REQUEST_URI'] = $originalRequestUri;
        else
            unset($_SERVER['REQUEST_URI']);
    }
    
    function testUnsanitizedPostVariable(){
        $_POST["CommentBody"] = "<p><strong>Test Comment</strong></p>";
        // for tests post data is empty thus any change in post afterwards should not reflect in unsanitizedPostVariable 
        $this->assertNull(Environment\unsanitizedPostVariable("CommentBody"));
    }

    function verifySanitizeHeaders($key, $input, $expected) {
        $_SERVER[$key] = $input;
        Environment\sanitizeHeaders();
        $this->assertIdentical($expected, $_SERVER[$key]);
    }
}
