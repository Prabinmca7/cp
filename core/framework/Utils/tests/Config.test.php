<?php

use RightNow\Utils\Config,
    RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ConfigTest extends CPTestCase {
    public $testingClass = 'RightNow\Utils\Config';

    function testGetMessage() {
        $this->assertEqual('Customer Portal', Config::getMessage(CUSTOMER_PORTAL_LBL));
        $this->assertEqual('Customer Portal', Config::getMessage(CUSTOMER_PORTAL_LBL, 'RNW'));
        $this->assertEqual('Customer Portal', Config::getMessage(CUSTOMER_PORTAL_LBL, 'rnw'));
        $this->assertEqual('Customer Portal', Config::getMessage(CUSTOMER_PORTAL_LBL, 'foo'));
        $this->assertEqual('Customer Portal', Config::getMessage(CUSTOMER_PORTAL_LBL, 'COMMON'));
        $this->assertEqual('This message is here for testing purposes.', Config::getMessage('_INTERNAL_TEST_MESSAGE_'));
        $this->assertEqual('Folder ID', Config::getMessage(9999));

        try {
            Config::getMessage('CUSTOMER_PORTAL_LBL');
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }

        try {
            Config::getMessage(null);
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }
     }

    function testGetMessageJS() {
        $this->assertEqual('Customer Portal', Config::getMessageJS(CUSTOMER_PORTAL_LBL));
        $this->assertEqual('Customer Portal', Config::getMessageJS(CUSTOMER_PORTAL_LBL, 'RNW'));
        $this->assertEqual('Customer Portal', Config::getMessageJS(CUSTOMER_PORTAL_LBL, 'rnw'));
        $this->assertEqual('Customer Portal', Config::getMessageJS(CUSTOMER_PORTAL_LBL, 'foo'));
        $this->assertEqual('Customer Portal', Config::getMessageJS(CUSTOMER_PORTAL_LBL, 'COMMON'));
        $this->assertEqual('This message is here for testing purposes.', Config::getMessageJS('_INTERNAL_TEST_MESSAGE_'));
        $this->assertEqual('Folder ID', Config::getMessageJS(9999));

        $this->assertEqual("Use the controls below if you wish to rollback the most recent successful %s on \'%s\'.", Config::getMessageJS(CTRLS_WISH_ROLLBACK_RECENT_MSG));

        try {
            Config::getMessageJS('CUSTOMER_PORTAL_LBL');
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }

        try {
            Config::getMessageJS(null);
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }
     }

    function testASTRGetMessage() {
        $this->assertEqual('Customer Portal', Config::getMessage(CUSTOMER_PORTAL_LBL));
     }

    function testASTRGetMessageJS() {
        $this->assertEqual('Customer Portal', Config::getMessageJS(CUSTOMER_PORTAL_LBL));
        $this->assertEqual("\\n \\n \\n \\\\ \\' \\\"", Config::ASTRgetMessageJS("\n \r\n \r \\ ' \""));
     }

     function testMsgGetFrom() {
        $this->assertEqual('Customer Portal', Config::msgGetFrom(CUSTOMER_PORTAL_LBL));
        $this->assertEqual('Unknown message slot _INTERNAL_TEST_MESSAGE_', Config::msgGetFrom('_INTERNAL_TEST_MESSAGE_'));
        $this->assertEqual('Unknown message slot 0', Config::msgGetFrom(0));
        $this->assertEqual('Unknown message slot -1', Config::msgGetFrom(-1));
        $this->assertEqual('Unknown message slot 0', Config::msgGetFrom('0'));
        $this->assertEqual('Unknown message slot -1', Config::msgGetFrom('-1'));
        $this->assertEqual('Folder ID', Config::msgGetFrom(9999));
        $this->assertIsA(Config::msgGetFrom(CUSTOMER_PORTAL_LBL), 'string');

        //testing custom messagebase definition file out of synch scenario
        define('CUSTOM_MSG_TEST__BAD_MESSAGE', 1000100);
        $this->assertIdentical('Unknown message slot CUSTOM_MSG_TEST__BAD_MESSAGE', Config::msgGetFrom('CUSTOM_MSG_TEST__BAD_MESSAGE'));

        try {
            Config::msgGetFrom('202');
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }
     }

    function testGetConfig() {
        $this->assertEqual('home', Config::getConfig(CP_HOME_URL));
        $this->assertEqual('home', Config::getConfig(CP_HOME_URL, 'RNW_UI'));
        $this->assertEqual('home', Config::getConfig(CP_HOME_URL, 'rnw_ui'));
        $this->assertEqual('home', Config::getConfig(CP_HOME_URL, 'COMMON'));
        $this->assertEqual('This config is here for testing purposes.', Config::getConfig('_INTERNAL_TEST_CONFIG_'));
        $this->assertEqual(false, Config::getConfig('wap_interface_enabled'));
        $this->assertEqual(300, Config::getConfig(202));
        $this->assertIsA(Config::getConfig(CP_LOGIN_COOKIE_EXP), 'integer');
        $this->assertIsA(Config::getConfig(CP_404_URL), 'string');
        $this->assertIsA(Config::getConfig(SEC_END_USER_HTTPS), 'boolean');

        foreach(array(SEC_CONFIG_PASSWD, DB_PASSWD, PROD_DB_PASSWD, rep_db_passwd) as $prohibited) {
            $this->assertNull(Config::getConfig($prohibited, 'common'));
        }

        try {
            Config::getConfig('CP_HOME_URL');
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }

        try {
            Config::getConfig(null);
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }
    }

    function testGetConfigJS() {
        $this->assertEqual('home', Config::getConfigJS(CP_HOME_URL));
        $this->assertEqual('home', Config::getConfigJS(CP_HOME_URL, 'RNW_UI'));
        $this->assertEqual('home', Config::getConfigJS(CP_HOME_URL, 'rnw_ui'));
        $this->assertEqual('home', Config::getConfigJS(CP_HOME_URL, 'COMMON'));
        $this->assertEqual('This config is here for testing purposes.', Config::getConfigJS('_INTERNAL_TEST_CONFIG_'));
        $this->assertEqual(300, Config::getConfigJS(202));
        $this->assertIsA(Config::getConfigJS(CP_LOGIN_COOKIE_EXP), 'integer');
        $this->assertIsA(Config::getConfigJS(CP_404_URL), 'string');
        $this->assertIsA(Config::getConfigJS(SEC_END_USER_HTTPS), 'boolean');

        $this->assertEqual('^((([-_!#$%&\\\'*+/=?^~`{|}\\\w]+(\\\\.[.]?[-_!#$%&\\\'*+/=?^~`{|}\\\w]+)*)|(\"[^\"]+\"))@[0-9A-Za-z]+([\\\\-]+[0-9A-Za-z]+)*(\\\\.[0-9A-Za-z]+([\\\\-]+[0-9A-Za-z]+)*)+[; ]*)$', Config::getConfigJS(DE_VALID_EMAIL_PATTERN));

        foreach(array(SEC_CONFIG_PASSWD, DB_PASSWD, PROD_DB_PASSWD, rep_db_passwd) as $prohibited) {
            $this->assertNull(Config::getConfigJS($prohibited, 'common'));
        }

        try {
            Config::getConfigJS('CP_HOME_URL');
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }

        try {
            Config::getConfigJS(null);
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }
    }

    function testConfigGetFrom() {
        $this->assertEqual('home', Config::configGetFrom(CP_HOME_URL));
        $this->assertEqual('Unknown config slot _INTERNAL_TEST_CONFIG_', Config::configGetFrom('_INTERNAL_TEST_CONFIG_'));
        $this->assertEqual('Unknown config slot 0', Config::configGetFrom(0));
        $this->assertEqual('Unknown config slot -1', Config::configGetFrom(-1));
        $this->assertEqual('Unknown config slot 0', Config::configGetFrom('0'));
        $this->assertEqual('Unknown config slot -1', Config::configGetFrom('-1'));
        $this->assertEqual(300, Config::configGetFrom(202));
        $this->assertIsA(Config::configGetFrom(CP_LOGIN_COOKIE_EXP), 'integer');
        $this->assertIsA(Config::configGetFrom(CP_404_URL), 'string');
        $this->assertIsA(Config::configGetFrom(SEC_END_USER_HTTPS), 'boolean');

        foreach(array(SEC_CONFIG_PASSWD, DB_PASSWD, PROD_DB_PASSWD, rep_db_passwd) as $prohibited) {
            $this->assertNull(Config::configGetFrom($prohibited, 'common'));
        }

        try {
            Config::configGetFrom('202');
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }
    }

    function testContactLoginRequiredEnabled() {
        $this->assertIdentical(false, Config::contactLoginRequiredEnabled());
    }

    function testGetMinYear() {
        $year = Config::getMinYear();
        $this->assertIsA($year, 'integer');
        $this->assertTrue(checkdate(1, 1, $year));
        $this->assertTrue($year < Config::getMaxYear());
        $minYear = intval(Text::getSubStringBefore(MIN_DATE, '-'));
        $this->assertIsA($minYear, 'integer');
        $this->assertEqual(4, strlen((string) $minYear));
        $this->assertIdentical($minYear, Config::getMinYear());
    }

    function testGetMaxYear() {
        $year = Config::getMaxYear();
        $this->assertIsA($year, 'integer');
        $this->assertTrue(checkdate(1, 1, $year));
        $this->assertTrue(Config::getMinYear() < $year);

        $currentYear = (int) date('Y');
        $minYear = Config::getMinYear();
        $maxYear = intval(Text::getSubStringBefore(MAX_DATE, '-'));
        $this->assertIsA($minYear, 'integer');
        $this->assertEqual(4, strlen((string) $minYear));
        $this->assertTrue($minYear < $maxYear);
        $inputs = array(
            array($currentYear, $currentYear),
            array($minYear, $minYear),
            array($maxYear, $maxYear),
            array($currentYear, ''),
            array($currentYear, null),
            array($currentYear, Config::getConfig(EU_MAX_YEAR, 'COMMON')),
            array($currentYear, (string) $currentYear),
            array(2011, 2011),
            array(2011, '2011'),
            array($currentYear - 1, -1),
            array($currentYear - 1, ' - 1 '),
            array($currentYear - 1, '-1'),
            array($currentYear + 1, '+1'),
            array($currentYear + 1, '+  1'),
            array($currentYear + 50, '+50'),
        );

        foreach ($inputs as $pairs) {
            list($expected, $input) = $pairs;
            $this->assertIdentical($expected, Config::getMaxYear($input));
        }

        $invalidYears = array(
            $minYear - 1,
            $maxYear + 1,
            0,
            .483,
            -.483,
            'foo',
            '-foo',
            '+foo',
            '-',
            '+',
        );
        foreach ($invalidYears as $invalidYear) {
            try {
                $shouldNotGetSet = Config::getMaxYear($invalidYear);
                $this->assertFalse(isset($shouldNotGetSet), "[$invalidYear]");
            }
            catch (Exception $e) {
                // expected
            }
        }
    }

    function testMsg() {
        $input = array(
            array('Search Criteria', 'SEARCH_CRITERIA_CMD'),
            array('Search Criteria', 'SEARCH_CRITERIA_CMD:RNW'),
            array('~!@#$%^&*()_+{}|:"<>?`-=[]\;\',./', 'SOME_DEFINE_THAT_DOES_NOT_EXIST'),
        );
        foreach($input as $pair) {
            list($message, $define) = $pair;
            $this->assertIdentical($message, Config::msg($message, $define));
            if (defined($define)) {
                $this->assertIdentical($message, Config::getMessage(constant($define)));
            }
        }

        //testing custom messagebase definition file out of synch scenario
        define('CUSTOM_MSG_TEST__BAD_MESSAGE', 1000100);
        $this->assertIdentical('Unknown message slot CUSTOM_MSG_TEST__BAD_MESSAGE', Config::msg('','CUSTOM_MSG_TEST__BAD_MESSAGE'));
    }

    function testMsgJS() {
        $input = array(
            array('Search Criteria', 'Search Criteria', 'SEARCH_CRITERIA_CMD'),
            array('Search Criteria', 'Search Criteria', 'SEARCH_CRITERIA_CMD:RNW'),
            array("Use the controls below if you wish to rollback the most recent successful %s on '%s'.", "Use the controls below if you wish to rollback the most recent successful %s on \'%s\'.", 'CTRLS_WISH_ROLLBACK_RECENT_MSG'),
            array('~!@#$%^&*()_+{}|:"<>?`-;=[]\;\',./', '~!@#$%^&*()_+{}|:\"<>?`-;=[]\\\;\\\',./', 'SOME_DEFINE_THAT_DOES_NOT_EXIST'),
        );
        foreach($input as $pair) {
            list($message, $expected, $define) = $pair;
            $this->assertIdentical($message, Config::msg($message, $define));
            $this->assertIdentical($expected, Config::msgJS($message, $define));
            if (defined($define)) {
                $this->assertIdentical($message, Config::getMessage(constant($define)));
                $this->assertIdentical($expected, Config::getMessageJS(constant($define)));
            }
        }
    }

    function testGetMsgForRnMsgTag() {
        $errorMessage = \RightNow\Utils\Config::getMessage(PCT_S_IS_BADLY_FORMED_MESSAGE_TAG_MSG);
        $input = array(
            array('Search Criteria', '#rn:msg:SEARCH_CRITERIA_CMD#', false),
            array("\\RightNow\\Utils\\Config::msgGetFrom(SEARCH_CRITERIA_CMD)", '#rn:msg:SEARCH_CRITERIA_CMD#', true),
            array("\\RightNow\\Utils\\Config::msgGetFrom(SEARCH_CRITERIA_CMD)", '#rn:msg:SEARCH_CRITERIA_CMD:RNW#', true),
            array('Search Criteria', '#rn:msg:SEARCH_CRITERIA_CMD:RNW#', false),
            array(sprintf($errorMessage, '#rn:msg;SEARCH_CRITERIA_CMD#'), '#rn:msg;SEARCH_CRITERIA_CMD#', false),
            array('Search Criteria', '#rn:msg:{Search Criteria}#', false),
            array("\\RightNow\\Utils\\Config::msg('Search Criteria')", '#rn:msg:{Search Criteria}#', true),
            array("\\RightNow\\Utils\\Config::msg('A string containing {curly} braces')", '#rn:msg:{A string containing {curly} braces}#', true),
            array("\\RightNow\\Utils\\Config::msg('A string containing a curly}brace', 'SOME_NONEXISTENT_DEFINE')", '#rn:msg:{A string containing a curly}brace}:{SOME_NONEXISTENT_DEFINE}#', true),
            array("\\RightNow\\Utils\\Config::msg('Search Criteria', 'SEARCH_CRITERIA_CMD')", '#rn:msg:{Search Criteria}:{SEARCH_CRITERIA_CMD}#', true),
            array('Search Criteria', '#rn:msg:{Search Criteria}:{SEARCH_CRITERIA_CMD}#', false),
            array('Search Criteria', '#rn:msg:{Search Criteria}:{SEARCH_CRITERIA_CMD:RNW}#', false),
            array(sprintf($errorMessage, '#rn:msg;{SEARCH_CRITERIA_CMD}#'), '#rn:msg;{SEARCH_CRITERIA_CMD}#', false),
            array("Search ' Criteria", "#rn:msg:{Search ' Criteria}#", false),
            array("Search \" Criteria", "#rn:msg:{Search \" Criteria}#", false),
            array('Search " Criteria', '#rn:msg:{Search " Criteria}#', false),
            array('Search \' Criteria', '#rn:msg:{Search \' Criteria}#', false),
            array("\\RightNow\\Utils\\Config::msgGetFrom((1234))", '#rn:msg:(1234)#', true),
            array("\\RightNow\\Utils\\Config::msgGetFrom((1000012))", '#rn:msg:(1000012)#', true),
            array("\\RightNow\\Utils\\Config::msgGetFrom(CUSTOM_MSG_TEST__HOTKEY_DefaultCustom, 'CUSTOM-MSG-TEST--HOTKEY-DefaultCustom')", '#rn:msg:CUSTOM_MSG_TEST__HOTKEY_DefaultCustom#', true),
            array('test6', '#rn:msg:CUSTOM_MSG_TEST__HOTKEY_DefaultCustom#', false)
        );
        foreach($input as $triple) {
            list($message, $tag, $returnFunctionCall) = $triple;
            $privateMethod = $this->getStaticMethod('getMsgForRnMsgTag');
            $this->assertIdentical($message, $privateMethod($tag, $returnFunctionCall));
        }
    }

    function testGetReferenceModeConfigValue() {
        $methodInvoker = RightNow\UnitTest\Helper::getStaticMethodInvoker("Rnow", "getReferenceModeConfigValue");
        $this->assertIdentical('error404', $methodInvoker(CP_404_URL));
        $this->assertIdentical('utils/account_assistance', $methodInvoker(CP_ACCOUNT_ASSIST_URL));
        $this->assertIdentical('answers/detail', $methodInvoker(CP_ANSWERS_DETAIL_URL));
        $this->assertIdentical('account/notif/unsubscribe', $methodInvoker(CP_ANS_NOTIF_UNSUB_URL));
        $this->assertIdentical('chat/chat_launch', $methodInvoker(CP_CHAT_URL));
        $this->assertIdentical('home', $methodInvoker(CP_HOME_URL));
        $this->assertIdentical('account/questions/detail', $methodInvoker(CP_INCIDENT_RESPONSE_URL));
        $this->assertIdentical('utils/login_form', $methodInvoker(CP_LOGIN_URL));
        $this->assertIdentical('answers/detail', $methodInvoker(CP_WEBSEARCH_DETAIL_URL));
        $this->assertNull($methodInvoker(PTA_EXTERNAL_LOGIN_URL));
        $this->assertNull($methodInvoker(CP_CONTACT_LOGIN_REQUIRED));
    }

    function testFindAllJavaScriptHelperObjectCalls() {
        $method = $this->getStaticMethod('findAllJavaScriptHelperObjectCalls');
        $this->assertSame(array(), $method(''));
        $this->assertSame(array(), $method('.Field'));
        $this->assertSame(array(), $method('.Field()'));
        $this->assertSame(array(), $method('RightNow.Field'));
        $this->assertSame(array(), $method('RightNow.Field;'));
        $this->assertSame(array('Field', 'Form'), $method('RightNow.Field()'));
        $this->assertSame(array('Field', 'Form'), $method('RightNow.Field('));
        $this->assertSame(array('Field', 'Form'), $method('RightNow.Field.'));
        $this->assertSame(array(), $method('.Form'));
        $this->assertSame(array(), $method('.Form()'));
        $this->assertSame(array(), $method('RightNow.Form'));
        $this->assertSame(array(), $method('RightNow.Form;'));
        $this->assertSame(array('Form'), $method('RightNow.Form()'));
        $this->assertSame(array('Form'), $method('RightNow.Form('));
        $this->assertSame(array('Form'), $method('RightNow.Form.'));
        $this->assertSame(array(), $method('.SearchFilter'));
        $this->assertSame(array(), $method('.SearchFilter()'));
        $this->assertSame(array(), $method('RightNow.SearchFilter'));
        $this->assertSame(array(), $method('RightNow.SearchFilter;'));
        $this->assertSame(array('SearchFilter'), $method('RightNow.SearchFilter()'));
        $this->assertSame(array('SearchFilter'), $method('RightNow.SearchFilter('));
        $this->assertSame(array('SearchFilter'), $method('RightNow.SearchFilter.'));
        $this->assertSame(array(), $method('.ResultsDisplay'));
        $this->assertSame(array(), $method('.ResultsDisplay()'));
        $this->assertSame(array(), $method('RightNow.ResultsDisplay'));
        $this->assertSame(array(), $method('RightNow.ResultsDisplay;'));
        $this->assertSame(array('SearchFilter'), $method('RightNow.ResultsDisplay()'));
        $this->assertSame(array('SearchFilter'), $method('RightNow.ResultsDisplay('));
        $this->assertSame(array('SearchFilter'), $method('RightNow.ResultsDisplay.'));
        $this->assertSame(array('Form', 'Field'), $method('RightNow.Form(RightNow.Field.'));
        $this->assertSame(array('Form', 'Form'), $method('RightNow.Form(RightNow.Form.'));
        $this->assertSame(array('Form', 'Field', 'SearchFilter', 'SearchFilter'), $method('RightNow.Form(RightNow.Field. RightNow.SearchFilter() RightNow.ResultsDisplay.'));
        $this->assertSame(array('Field', 'SearchFilter', 'SearchFilter', 'Form'), $method('(RightNow.Field. RightNow.SearchFilter() RightNow.ResultsDisplay.'));
        $this->assertSame(array('Field', 'Field', 'SearchFilter', 'SearchFilter', 'Form'), $method('(RightNow.Field.RightNow.Field( RightNow.SearchFilter() RightNow.ResultsDisplay.'));
        $this->assertSame(array('Field', 'Field', 'Form', 'SearchFilter', 'SearchFilter'), $method('(RightNow.Field.RightNow.Field( RightNow.Form.RightNow.SearchFilter() RightNow.ResultsDisplay.'));
        $this->assertSame(array('SearchProducer'), $method('RightNow.SearchConsumer.extend'));
        $this->assertSame(array('SearchProducer'), $method('RightNow.SearchProducer.extend'));
        $this->assertSame(array('ProductCategory'), $method('RightNow.ProductCategory.prototype.buildPanel.call(this)'));
        $this->assertSame(array('ProductCategory'), $method('this.Y.augment(this, RightNow.ProductCategory)'));
    }

    function testFindAllJavaScriptMessageAndConfigCalls() {
        $file = 'someFile';
        $empty = array('message' => array(), 'config' => array());
        $method = $this->getStaticMethod('findAllJavaScriptMessageAndConfigCalls');

        $actual = $method("blah blah \nNOTHING TO SEE HERE\n blah blah", $file);
        $this->assertIdentical($empty, $actual);

        $content = "blah blah\nRightNow.Interface.getConfig('SOME_CONFIG');\n" .
            "blah blah RightNow.Interface.getMessage('SOME_REPEATED_MESSAGE');\n" .
            "blah blah RightNow.Interface.getMessage('SOME_REPEATED_MESSAGE');\n" .
            "blah blah /*RightNow.Interface.getMessage('COMMENTED')\n" .
            "blah blah //RightNow.Interface.getMessage('COMMENTED')\n" .
            "blah blah /* RightNow.Interface.getMessage('COMMENTED'); */\n" .
            "blah blah // RightNow.Interface.getMessage('COMMENTED')\n" .
            "blah blah // alert(RightNow.Interface.getMessage('COMMENTED'))\n" .
            "blah blah RightNow.Interface.getMessage('SOME_MESSAGE')\n" .
            "blah blah RightNow.Interface.getConfig('SOME_OTHER_CONFIG')\n" .

        $expected = array(
            'message' => array(
                $file => array(
                    0 => '\'SOME_REPEATED_MESSAGE\'',
                    2 => '\'SOME_MESSAGE\'',
                ),
            ),
            'config' => array(
                $file => array(
                    0 => '\'SOME_CONFIG\'',
                    1 => '\'SOME_OTHER_CONFIG\'',
                ),
            ),
        );

        $actual = $method($content, $file);
        $this->assertIdentical($expected, $actual);

        // minified JS
        $content = 'return;var surveyCompID=RightNow.Url.getParameter("survey_comp_id");var surveyTermID=RightNow.Url.getParameter("survey_term_id");var closingUrl=this.data.attrs.closing_url;var eventObject=new RightNow.Event.EventObject();eventObject.w_id=this.instanceID;eventObject.data.connectionData={absentInterval:RightNow.Interface.getConfig("ABSENT_INTERVAL","RNL"),absentRetryCount:RightNow.Interface.getConfig("USER_ABSENT_RETRY_COUNT","RNL"),chatServerHost:RightNow.Interface.getConfig("SRV_CHAT_HOST","RNL"),chatServerPort:RightNow.Interface.getConfig("SERVLET_HTTP_PORT","RNL"),dbName:RightNow.Interface.getConfig("DB_NAME","COMMON"),useHttps:window.location.protocol.indexOf("https:")===0};eventObject.data.surveyBaseUrl=this.data.js.maUrl;if(surveyCompID)';
        $expected = array(
          'message' => array(),
          'config' => array($file =>
            array (
              0 => '"ABSENT_INTERVAL","RNL"',
              1 => '"USER_ABSENT_RETRY_COUNT","RNL"',
              2 => '"SRV_CHAT_HOST","RNL"',
              3 => '"SERVLET_HTTP_PORT","RNL"',
              4 => '"DB_NAME","COMMON"',
            ),
          ),
        );

        $actual = $method($content, $file);
        $this->assertIdentical($expected, $actual);

        $content = 'return false;},_setCookie:function(){var date=new Date();date.setDate(date.getDate()+this.data.attrs.cookie_duration);document.cookie=this.instanceID+"="+this.data.js.question_id+";expires="+date.toUTCString()+";path=/";;},_validate:function(formElement){var errorDisplay=document.getElementById("rn_"+this.instanceID+"_ErrorMessage");RightNow.MarketingFeedback.removeErrorsFromForm(formElement,errorDisplay);return RightNow.MarketingFeedback.validateSurveyFields(formElement,this._getFieldData(),this._getSurveyFieldObjectList(formElement),errorDisplay);},_getFieldData:function(){return{reqd_msg:RightNow.Interface.getMessage("VALUE_REQD_MSG"),fld_too_mny_chars_msg:RightNow.Interface.getMessage("FLD_CONT_TOO_MANY_CHARS_MSG"),too_few_options_msg:RightNow.Interface.getMessage("NEED_TO_SELECT_MORE_OPTIONS_MSG"),too_many_options_msg:RightNow.Interface.getMessage("NEED_TO_SELECT_FEWER_OPTIONS_MSG")};},_getSurveyFieldObjectList:function(formElement){switch(this.data.js.question_type){case"text":case"choice":if(document.getElementById("val_q_"+this.data.js.question_id)){var validationInfo=document.getElementById("val_q_"+this.data.js.question_id).value;var validationArray=validationInfo.split(",");var min=parseInt(validationArray[1],10)>0?parseInt(validationArray[1],10):1;return[{id:parseInt(validationArray[0].replace(/"/g,""),10),min:min,max:parseInt(validationArray[2],10),type:parseInt(validationArray[3],10),elements:validationArray[4],question_text:""}];}';
        $expected = array (
            'message' => array('someFile' => array(
                0 => '"VALUE_REQD_MSG"',
                1 => '"FLD_CONT_TOO_MANY_CHARS_MSG"',
                2 => '"NEED_TO_SELECT_MORE_OPTIONS_MSG"',
                3 => '"NEED_TO_SELECT_FEWER_OPTIONS_MSG"',
              ),
            ),
            'config' => array(),
        );
        $actual = $method($content, $file);
        $this->assertIdentical($expected, $actual);


        //// VALID COMMENTS
        $this->assertIdentical($empty, $method("/* RightNow.Interface.getConfig('SOME_CONFIG'); */", $file));
        $this->assertIdentical($empty, $method("/*RightNow.Interface.getConfig('SOME_CONFIG'); */", $file));
        $this->assertIdentical($empty, $method("/* RightNow.Interface.getMessage('SOME_MESSAGE'); */", $file));
        $this->assertIdentical($empty, $method("// RightNow.Interface.getConfig('SOME_CONFIG');", $file));
        $this->assertIdentical($empty, $method("// RightNow.Interface.getMessage('SOME_MESSAGE');", $file));
        $this->assertIdentical($empty, $method("//RightNow.Interface.getMessage('SOME_MESSAGE');", $file));
        $this->assertIdentical($empty, $method("/* alert(RightNow.Interface.getMessage('SOME_MESSAGE'));", $file));

        //// NOT COMMENTS
        $expected = array('message' => array($file => array('\'NOT_COMMENTED\'')), 'config' => array());
        $this->assertIdentical($expected, $method("alert(RightNow.Interface.getMessage('NOT_COMMENTED'));", $file));
        $this->assertIdentical($expected, $method("RightNow.Interface.getMessage('NOT_COMMENTED');", $file));
        $this->assertIdentical($expected, $method("/ RightNow.Interface.getMessage('NOT_COMMENTED');", $file));
        $this->assertIdentical($expected, $method("* RightNow.Interface.getMessage('NOT_COMMENTED');", $file));
    }

    function testParseJavascriptMessages() {
        $method = $this->getStaticMethod('parseJavascriptMessages');

        // Default set
        $return = $method(array());
        $this->assertSame(2, count($return));
        $this->assertTrue(empty($return[1]));
        $this->assertFalse(empty($return[0]));
        $defaultSize = count($return[0]);

        // Some more
        $return = $method(array(
            'somefile' => array("'ROQL_BLOCKLIST_NOTIFICATION_LIST_DESC_LBL'", '"ANSWER_RATED_HELPFULNESS_LBL"'),
            'otherfile' => array("'GET_HELP_LBL'"),
        ));
        $this->assertTrue(empty($return[1]));
        $this->assertSame($defaultSize + 3, count($return[0]));
        $this->assertIsA($return[0]['GET_HELP_LBL']['value'], 'int');

        // Errors
        $return = $method(array(
            'somefile' => array("'ROQL_BLOCKLIST_NOTIFICATION_LIST_DESC_LBL'", "ANSWER_RATED_HELPFULNESS_LBL"),
            'otherfile' => array("'BANANA'"),
        ));
        $this->assertSame(2, count($return[1]));
        $this->assertSame($defaultSize + 1, count($return[0]));
        $this->assertIsA($return[0]['ROQL_BLOCKLIST_NOTIFICATION_LIST_DESC_LBL']['value'], 'int');
        $this->assertTrue(Text::stringContains($return[1][0], 'somefile'));
        $this->assertTrue(Text::stringContains($return[1][0], 'ANSWER_RATED_HELPFULNESS_LBL'));
        $this->assertTrue(Text::stringContains($return[1][1], 'otherfile'));
        $this->assertTrue(Text::stringContains($return[1][1], 'BANANA'));

        // Deploy
        $return = $method(array(), true);
        $this->assertTrue(empty($return[1]));
        $this->assertFalse(empty($return[0]));
        foreach ($return[0] as $key => $value) {
            $this->assertSame(1, preg_match('/^\<m4-ignore\>[A-Z0-9_]+\<\/m4-ignore\>$/', $key));
        }

        // Skip default set
        $return = $method(array(), false, false);
        $this->assertTrue(empty($return[0]));
        $this->assertTrue(empty($return[1]));

        $return = $method(array(
            'somefile' => array("'GET_HELP_LBL'"),
        ), false, false);
        $this->assertTrue(empty($return[1]));
        $this->assertSame(1, count($return[0]));
    }

    function testParseJavascriptConfigs() {
        $method = $this->getStaticMethod('parseJavascriptConfigs');

        // Default set
        $return = $method(array());
        $this->assertSame(2, count($return));
        $this->assertTrue(empty($return[1]));
        $this->assertFalse(empty($return[0]));
        $defaultSize = count($return[0]);

        // Some more
        $return = $method(array(
            'somefile' => array("'CP_404_URL'", '"CP_ACCOUNT_ASSIST_URL"'),
            'otherfile' => array("'CP_CHAT_URL'"),
        ));
        $this->assertTrue(empty($return[1]));
        $this->assertSame($defaultSize + 3, count($return[0]));
        $this->assertIsA($return[0]['CP_404_URL']['value'], 'int');

        // Errors
        $return = $method(array(
            'somefile' => array("'CP_404_URL'", "CP_ACCOUNT_ASSIST_URL"),
            'otherfile' => array("'BANANA'"),
        ));
        $this->assertSame(2, count($return[1]));
        $this->assertSame($defaultSize + 1, count($return[0]));
        $this->assertIsA($return[0]['CP_404_URL']['value'], 'int');
        $this->assertTrue(Text::stringContains($return[1][0], 'somefile'));
        $this->assertTrue(Text::stringContains($return[1][0], 'CP_ACCOUNT_ASSIST_URL'));
        $this->assertTrue(Text::stringContains($return[1][1], 'otherfile'));
        $this->assertTrue(Text::stringContains($return[1][1], 'BANANA'));

        // Deploy
        $return = $method(array(), true);
        $this->assertTrue(empty($return[1]));
        $this->assertFalse(empty($return[0]));
        foreach ($return[0] as $key => $value) {
            $this->assertSame(1, preg_match('/^\<m4-ignore\>[A-Z0-9_]+\<\/m4-ignore\>$/', $key));
        }

        // Skip default set
        $return = $method(array(), false, false);
        $this->assertTrue(empty($return[0]));
        $this->assertTrue(empty($return[1]));

        $return = $method(array(
            'somefile' => array("'GET_HELP_LBL'"),
        ), false, false);
        $this->assertTrue(empty($return[1]));
        $this->assertSame(1, count($return[0]));

        // Include chat
        $return = $method(array(), false, true, true);
        $this->assertTrue(empty($return[1]));
        $this->assertSame($defaultSize + 1, count($return[0]));
    }

    function testConvertJavaScriptCompileSafeDefinesDoesNotConvertSomePatterns() {
        $method = $this->getStaticMethod('convertJavaScriptCompileSafeDefines');

        $this->assertSame('', $method(''));

        $result = $method('<%= RightNow.Interface.getBananas("REQUIRED_LBL") %>');
        $this->assertIdentical($result, '<%= RightNow.Interface.getBananas("REQUIRED_LBL") %>');

        $result = $method('<%= getMessage("REQUIRED_LBL") %>');
        $this->assertIdentical($result, '<%= getMessage("REQUIRED_LBL") %>');

        $result = $method('<%= getConfig("CP_HOME_URL") %>');
        $this->assertIdentical($result, '<%= getConfig("CP_HOME_URL") %>');
    }

    function testThePatternsThatConvertJavaScriptCompileSafeDefinesDoesConvert() {
        $method = $this->getStaticMethod('convertJavaScriptCompileSafeDefines');

        $result = $method('<%= RightNowInterface.getMessage("REQUIRED_LBL") %>');
        $this->assertIdentical($result, '<%= RightNowInterface.getMessage(<m4-ignore>"REQUIRED_LBL"</m4-ignore>) %>');

        $result = $method("<%= RightNow\nInterface.getMessage('REQUIRED_LBL') %>");
        $this->assertIdentical($result, "<%= RightNow\nInterface.getMessage(<m4-ignore>'REQUIRED_LBL'</m4-ignore>) %>");

        $result = $method('<%= RightNow           Interface.getMessage("REQUIRED_LBL") %>');
        $this->assertIdentical($result, '<%= RightNow           Interface.getMessage(<m4-ignore>"REQUIRED_LBL"</m4-ignore>) %>');

        $result = $method('<%= RightNow . Interface.getMessage("REQUIRED_LBL") %>');
        $this->assertIdentical($result, '<%= RightNow . Interface.getMessage(<m4-ignore>"REQUIRED_LBL"</m4-ignore>) %>');

        $result = $method('<%= RightNow. Interface.getMessage("REQUIRED_LBL") %>');
        $this->assertIdentical($result, '<%= RightNow. Interface.getMessage(<m4-ignore>"REQUIRED_LBL"</m4-ignore>) %>');

        $result = $method('<%= RightNow .Interface.getMessage("REQUIRED_LBL") %>');
        $this->assertIdentical($result, '<%= RightNow .Interface.getMessage(<m4-ignore>"REQUIRED_LBL"</m4-ignore>) %>');

        $result = $method('<%= RightNow.Interface.getMessage("REQUIRED_LBL") %>');
        $this->assertIdentical($result, '<%= RightNow.Interface.getMessage(<m4-ignore>"REQUIRED_LBL"</m4-ignore>) %>');

        $result = $method('<%= RightNow.Interface.getMessage(REQUIRED_LBL) %>');
        $this->assertIdentical($result, '<%= RightNow.Interface.getMessage(<m4-ignore>REQUIRED_LBL</m4-ignore>) %>');

        $result = $method("<%= RightNow.Interface.getMessage('bananas') %>");
        $this->assertIdentical($result, "<%= RightNow.Interface.getMessage(<m4-ignore>'bananas'</m4-ignore>) %>");

        $result = $method("<%= RightNow.Interface.getMessage('bananas', 'RNW_UI') %>");
        $this->assertIdentical($result, "<%= RightNow.Interface.getMessage(<m4-ignore>'bananas'</m4-ignore>,<m4-ignore>'RNW_UI'</m4-ignore>) %>");

        $result = $method('<%= RightNow.Interface.getConfig("CP_HOME_URL") %>');
        $this->assertIdentical($result, '<%= RightNow.Interface.getConfig(<m4-ignore>"CP_HOME_URL"</m4-ignore>) %>');

        $result = $method('<%= RightNow.Interface.getConfig(CP_HOME_URL) %>');
        $this->assertIdentical($result, '<%= RightNow.Interface.getConfig(<m4-ignore>CP_HOME_URL</m4-ignore>) %>');

        $result = $method("<%= RightNow.Interface.getConfig('bananas') %>");
        $this->assertIdentical($result, "<%= RightNow.Interface.getConfig(<m4-ignore>'bananas'</m4-ignore>) %>");

        $result = $method("<%= RightNow.Interface.getConfig('bananas', 'COMMON') %>");
        $this->assertIdentical($result, "<%= RightNow.Interface.getConfig(<m4-ignore>'bananas'</m4-ignore>,<m4-ignore>'COMMON'</m4-ignore>) %>");
    }
}
