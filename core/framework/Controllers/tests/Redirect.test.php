<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text;

class RedirectTest extends CPTestCase
{
    function testEnduser() {
        $response = $this->makeRequest("/ci/Redirect/enduser", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/home.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/acct_login.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/utils/login_form"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/std_alp.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/list"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/std_adp.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/detail"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/myq_upd.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/questions/detail"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/myq_ilp.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/questions/list"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/myq_idp.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/questions/detail"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/widx_alp.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/list"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/popup_adp.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/detail"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/ask.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/ask"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/acct_new.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/utils/create_account"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/chat.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/chat/chat_launch"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/acct_assistance.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/utils/account_assistance"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/passwd_reset.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/reset_password"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/passwd_setup.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/setup_password"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/doc_serve.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/ci/documents/detail"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/doc_view.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/ci/documents/view"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/sitemap.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/ci/sitemap"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/home.php?p_cred=credentials", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home/cred/credentials"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/home.php?session=me", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home/session/me"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/home.php?p_search_text=phone&p_prods=1&p_cats=2", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home/kw/phone/p/1/c/2/search/1"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/home.php?p_li=hey", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/ci/pta/login/redirect//app/home/p_li/hey"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/home.php?p_userid=bob", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home/username/bob"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/home.php?p_faqid=52", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home/a_id/52"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/Redirect/enduser/enduser/home.php?p_iid=143", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home/i_id/143"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
    }

    //@@@ QA 130311-000049
    function testWap() {
        $response = $this->makeRequest("/ci/Redirect/wap", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/login.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/utils/login_form"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/listans.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/list"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/search.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/list"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/ans.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/detail"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/myq.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/questions/list"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/inc.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/questions/detail"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/myq_upd.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/questions/detail"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/myprofile.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/profile"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/ask.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/ask"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/acct_new.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/utils/create_account"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/acct_assistance.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/utils/account_assistance"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/passwd_change.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/change_password"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/passwd_reset.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/reset_password"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/passwd_setup.php", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/setup_password"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/listans.php?p_type=3&p_key=phone", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/list/kw/phone/search/1"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/listans.php?p_list=0&p_key=1:4:160", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/list/p/160/search/1"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/listans.php?p_list=1&p_key=71:77", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/list/c/77/search/1"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/ans.php?p_faqid=52", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/answers/detail/a_id/52"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser/inc.php?p_iid=143", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/account/questions/detail/i_id/143"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/anyurl.php?p_cred=credentials", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home/cred/credentials"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));

        $response = $this->makeRequest("/ci/Redirect/wap/wap/enduser.php?session=me", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertFalse(Text::stringContains($response, "/app/home/"));
        $this->assertTrue(Text::stringContains($response, "301 Moved Permanently"));
    }

    // TODO: uncomment once apache/environment redirects for WAP are in place
    function testWapEnvironmentRedirect() {
        // Ensure environment-related redirects work

        // Basic redirect
        $siteName = \RightNow\Internal\Api::intf_name();
        $response = $this->makeRequest("/cgi-bin/{$siteName}.cfg/php/wap/enduser.php", array(
            'justHeaders'         => true,
            'dontFollowRedirects' => true
        ));
        $this->assertTrue(Text::stringContains($response, "Location: /ci/redirect/wap/wap/enduser.php [following]"));

        // Anything after wap/ should appended to ci/redirect/wap/wap/ for the redirect
        $response = $this->makeRequest("/cgi-bin/{$siteName}.cfg/php/wap/crazyPotato.aspx?isCrazy=1&burrito=yummy", array(
            'justHeaders'         => true,
            'dontFollowRedirects' => true
        ));
        $this->assertTrue(Text::stringContains($response, "Location: /ci/redirect/wap/wap/crazyPotato.aspx?isCrazy=1&burrito=yummy [following]"));
    }

    function testMa() {
        $response = $this->makeRequest("/ci/Redirect/ma", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
    }

    function testPageSet() {
        $requestOptions = array(
            "justHeaders"   => true,
            "flags"         => "--level=1",
        );

        // with no parameters, redirect to CP_HOME_URL and do not set a cookie
        $response = $this->makeRequest("/ci/redirect/pageSet", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical(0, $match);
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        // if mobile is requested and disabled, redirect to home and do not set a cookie
        $response = $this->makeRequest("/ci/redirect/pageSet/mobile/answers/detail/a_id/12", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical(0, $match);
        $this->assertTrue(Text::stringContains($response, "/app/answers/detail/a_id/12"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        // enable mobile and try again
        \RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/enableMobilePageSet", $requestOptions);

        // without /app/
        $response = $this->makeRequest("/ci/redirect/pageSet/mobile/ask", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical("mobile", $cookieString);
        $this->assertTrue(Text::stringContains($response, "/app/ask"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        // with /app/
        $response = $this->makeRequest("/ci/redirect/pageSet/mobile/app/answers/detail/a_id/12", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical("mobile", $cookieString);
        $this->assertTrue(Text::stringContains($response, "/app/answers/detail/a_id/12"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        // to standard controller
        $response = $this->makeRequest("/ci/redirect/pageSet/mobile/ci/about", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical("mobile", $cookieString);
        $this->assertTrue(Text::stringContains($response, "/ci/about"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        // to custom controller
        $response = $this->makeRequest("/ci/redirect/pageSet/mobile/cc/ajaxCustom/ajaxFunctionHandler", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical("mobile", $cookieString);
        $this->assertTrue(Text::stringContains($response, "/cc/ajaxCustom/ajaxFunctionHandler"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        // to page that does not exist
        $response = $this->makeRequest("/ci/redirect/pageSet/mobile/an/invalid/path", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical("mobile", $cookieString);
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        $response = $this->makeRequest("/ci/redirect/pageSet/default/an/invalid/path", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical("/", urldecode($cookieString));
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));

        //@@@ QA 130305-000119 Make a request as a mobile device and make sure it redirects properly
        $requestOptions['headers'] = array('User-Agent' => 'Mozilla/5.0 (iPhone; U;)');
        $response = $this->makeRequest("/ci/redirect/pageSet/default/answers/detail/a_id/4", $requestOptions);
        list($match, $cookieString) = $this->extractCookie($response);
        $this->assertIdentical("/", urldecode($cookieString));
        $this->assertTrue(Text::stringContains($response, "/app/answers/detail/a_id/4"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));


        // disable mobile page set
        \RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/disableMobilePageSet", $requestOptions);
    }

    private function extractCookie($output, $cookieName="agent") {
        $matches = preg_match("|Set-Cookie: $cookieName=([A-Za-z0-9_%/]+);|", $output, $profileCookie);
        return array($matches, $profileCookie[1]);
    }

    public function enableMobilePageSet() {
        $this->CI->model('Pageset')->enableItem(1, true);
        \RightNow\Utils\Framework::runSqlMailCommitHook();
    }

    public function disableMobilePageSet() {
        $this->CI->model('Pageset')->enableItem(1, false);
        \RightNow\Utils\Framework::runSqlMailCommitHook();
    }
}
