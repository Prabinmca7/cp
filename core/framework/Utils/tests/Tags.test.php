<?php

use RightNow\Utils\Tags;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TagsTest extends CPTestCase {
    public function testGetPageTitleAtRuntime() {
        $this->assertIdentical('no title', Tags::getPageTitleAtRuntime());

        RightNow\Controllers\UnitTest\PhpFunctional::$metaInformation = array('title2' => 'monkeys');
        $this->assertIdentical('no title', Tags::getPageTitleAtRuntime());

        RightNow\Controllers\UnitTest\PhpFunctional::$metaInformation = array('title' => 'monkeys');
        $this->assertIdentical('monkeys', Tags::getPageTitleAtRuntime());
        RightNow\Controllers\UnitTest\PhpFunctional::$metaInformation = array('title' => ' here be monkeys? ');
        $this->assertIdentical(' here be monkeys? ', Tags::getPageTitleAtRuntime());

        RightNow\Controllers\UnitTest\PhpFunctional::$metaInformation = array();
    }

    public function testCreateCssTag() {
        $this->assertIdentical("<link href='' rel='stylesheet' type='text/css' media='all' />", Tags::createCssTag(null));
        $this->assertIdentical("<link href='pathToThings' rel='stylesheet' type='text/css' media='all' />", Tags::createCssTag('pathToThings'));
    }

    public function testCreateJSTag() {
        $this->assertIdentical("<script src=''></script>", Tags::createJSTag(null));
        $this->assertIdentical("<script src='pathToThings'></script>", Tags::createJSTag('pathToThings'));

        $expected = "<script src='pathToThings' type='text/javascript' id='myID' async defer></script>";
        $this->assertIdentical($expected, Tags::createJSTag('pathToThings', 'type="text/javascript" id="myID" async defer'));
        $this->assertIdentical($expected, Tags::createJSTag('pathToThings', 'type=\'text/javascript\' id=\'myID\' async defer'));
        $this->assertIdentical($expected, Tags::createJSTag('pathToThings', "type=\"text/javascript\" id=\"myID\" async defer"));
        $this->assertIdentical($expected, Tags::createJSTag('pathToThings', "type='text/javascript' id='myID' async defer"));
    }

    public function testCreateYUIGetJsTag() {
        $expected = '<script>YUI().use("get", function(Y){Y.Get.js("http:\/\/somesite\/some.js", null, null);});</script>';
        $actual = Tags::createYUIGetJsTag('http://somesite/some.js');
        $this->assertIdentical($expected, $actual);

        $expected = '<script>YUI().use("get", function(Y){Y.Get.js(["http:\/\/somesite\/some.js","http:\/\/somesite\/someother.js"], null, null);});</script>';
        $actual = Tags::createYUIGetJsTag(array('http://somesite/some.js', 'http://somesite/someother.js'));
        $this->assertIdentical($expected, $actual);

        $expected = '<script>YUI().use("get", function(Y){Y.Get.js(["http:\/\/somesite\/some.js","http:\/\/somesite\/someother.js"], {"async":true,"callback":"function (err) {if (err) {console.log(\"Resource failed to load!\");} else {console.log(\"Resource was loaded successfully\");}}"}, null);});</script>';
        $actual = Tags::createYUIGetJsTag(array('http://somesite/some.js', 'http://somesite/someother.js'), array(
            'async' => true,
            'callback' => 'function (err) {if (err) {console.log("Resource failed to load!");} else {console.log("Resource was loaded successfully");}}',
        ));
        $this->assertIdentical($expected, $actual);
    }

    public function testInsertHeadContent1() {
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<rn:head_content/>', 'a')), 'a');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<head></head>', 'a')), '<head>a</head>');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('</head>', 'a')), 'a</head>');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<rn:head_content/>', '')), '');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<head></head>', '')), '<head></head>');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('</head>', '')), '</head>');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('1<rn:head_content/>2', 'a')), '1a2');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<head>1</head>2', 'a')), "<head>1a</head>2");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('1</head>2', 'a')), "1a</head>2");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('1<rn:head_content/>2', 'abcd')), '1abcd2');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<head>1</head>2', 'abcd')), "<head>1abcd</head>2");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('1</head>2', 'abcd')), "1abcd</head>2");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('123<rn:head_content/>2', 'a')), '123a2');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<head>123</head>2', 'a')), "<head>123a</head>2");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('123</head>2', 'a')), "123a</head>2");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('1<rn:head_content/>', 'a')), '1a');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<head>1</head>', 'a')), "<head>1a</head>");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('1</head>', 'a')), "1a</head>");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<rn:head_content/>2', 'a')), 'a2');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<head></head>2', 'a')), '<head>a</head>2');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('</head>2', 'a')), 'a</head>2');

        $this->assertEqual(Tags::insertHeadContent('</head >', 'A'), "\nA\n</head >\n");
        $this->assertEqual(Tags::insertHeadContent('</head prop="val">', 'A'), "\nA\n</head prop=\"val\">\n");
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('</headCustomXML></head>', 'a')), '</headCustomXML>a</head>');

        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<html>1<rn:head_content/>2</head>3', 'abc')), '<html>1abc2</head>3');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<!--<html>1<rn:head_content/>2</head>3-->', 'abc')), '<!--<html>1<rn:head_content/>2abc</head>3-->');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent('<!--<html>1<rn:head_content/>2</head>3--><rn:head_content/>', 'abc')), '<!--<html>1<rn:head_content/>2</head>3-->abc');

        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent("<html>1<rn:head_content\n/>2</head>3", 'abc')), '<html>1abc2</head>3');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent("<!--\n<html>1<rn:head_content\n/>2</head>3\n-->", 'abc')), '<!--<html>1<rn:head_content/>2abc</head>3-->');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent("<!--\n<html>1<rn:head_content\n/>2</head>3\n--><rn:head_content/>", 'abc')), '<!--<html>1<rn:head_content/>2</head>3-->abc');

        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent("<html>1<rn:head_content\n\r/>2</head>3", 'abc')), '<html>1abc2</head>3');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent("<!--\n\r<html>1<rn:head_content\n\r/>2</head>3\n\r-->", 'abc')), '<!--<html>1<rn:head_content/>2abc</head>3-->');
        $this->assertEqual(self::stripWhitespace(Tags::insertHeadContent("<!--\n\r<html>1<rn:head_content\n\r/>2</head>3\n\r--><rn:head_content/>", 'abc')), '<!--<html>1<rn:head_content/>2</head>3-->abc');
    }

    private static function stripWhitespace($s) {
        return strtr($s, array("\r" => '', "\n" => '', ' ' => ''));
    }

    public function testGetCommentableRNTagPattern() {
        $this->assertEqual(Tags::getCommentableRNTagPattern('widgets?|condition|field|theme|condition|condition_else', true), '@<!--.*?-->|</?rn:(widgets?|condition|field|theme|condition|condition_else)\b(?:\s*(?:[-.:\w]+\s*=\s*(?:([\'"]).*?\2|\S*\b)|[^<>\s]*))*\s*/?>@s');
        $this->assertEqual(Tags::getCommentableRNTagPattern('widgets?|condition|field|theme|condition|condition_else', false), '@<!--.*?-->|<rn:(widgets?|condition|field|theme|condition|condition_else)\b(?:\s*(?:[-.:\w]+\s*=\s*(?:([\'"]).*?\2|\S*\b)|[^<>\s]*))*\s*/?>@s');
    }

    public function testGetCommentableRNTagPattern2() {
        $pattern = Tags::getCommentableRNTagPattern('foo|bar', true);
        $input = "
            <!---->
            <!--\n-->
            <!--<rn:foo/>-->
            <!--\n<rn:foo/>\n-->
            <rn:foo/>
            <rn:foo />
            <rn:foo\n/>
            <rn:foo m='m'/>
            <rn:foo n=\"n\"/>
            <rn:foo p=p/>
            <rn:foo m='m'>
            <rn:foo n=\"n\">
            <rn:foo p=p>
            </rn:foo m='m'>
            </rn:foo n=\"n\">
            </rn:foo p=p>
            <rn:bar b\n=\n\"\nc\n\"d\n=\n'\ne\n'\nf=asdf;qwer>
            <rn:bar z\n=\n\"\ny\n\"x\n=\n'\ne\n'\nf=asdf;'qwer/>";

        $matchCount = preg_match_all($pattern, $input, $matches, PREG_SET_ORDER);
        $this->assertIdentical(18, $matchCount);
        $expectedMatches = array (
            0 =>
            array (
                0 => '<!---->',
            ),
            1 =>
            array (
                0 => "<!--\n-->",
            ),
            2 =>
            array (
                0 => '<!--<rn:foo/>-->',
            ),
            3 =>
            array (
                0 => "<!--\n<rn:foo/>\n-->",
            ),
            4 =>
            array (
                0 => '<rn:foo/>',
                1 => 'foo',
            ),
            5 =>
            array (
                0 => '<rn:foo />',
                1 => 'foo',
            ),
            6 =>
            array (
                0 => "<rn:foo\n/>",
                1 => 'foo',
            ),
            7 =>
            array (
                0 => "<rn:foo m='m'/>",
                1 => 'foo',
                2 => '\'',
            ),
            8 =>
            array (
                0 => "<rn:foo n=\"n\"/>",
                1 => 'foo',
                2 => '"',
            ),
            9 =>
            array (
                0 => "<rn:foo p=p/>",
                1 => 'foo',
            ),
            10 =>
            array (
                0 => "<rn:foo m='m'>",
                1 => 'foo',
                2 => '\'',
            ),
            11 =>
            array (
                0 => "<rn:foo n=\"n\">",
                1 => 'foo',
                2 => '"',
            ),
            12 =>
            array (
                0 => "<rn:foo p=p>",
                1 => 'foo',
            ),
            13 =>
            array (
                0 => "</rn:foo m='m'>",
                1 => 'foo',
                2 => '\'',
            ),
            14 =>
            array (
                0 => "</rn:foo n=\"n\">",
                1 => 'foo',
                2 => '"',
            ),
            15 =>
            array (
                0 => "</rn:foo p=p>",
                1 => 'foo',
            ),
            16 =>
            array (
                0 => "<rn:bar b\n=\n\"\nc\n\"d\n=\n'\ne\n'\nf=asdf;qwer>",
                1 => 'bar',
                2 => '\'',
            ),
            17 =>
            array (
                0 => "<rn:bar z\n=\n\"\ny\n\"x\n=\n'\ne\n'\nf=asdf;'qwer/>",
                1 => 'bar',
                2 => '\'',
            ),
        );
        $this->assertIdentical($matches, $expectedMatches);
    }

    public function testGetHtmlAttributes1() {
        $attrs = Tags::getHtmlAttributes('<rn:foo/>');
        $this->assertIdentical(0, count($attrs));
    }

    public function testGetHtmlAttributes2() {
        $attrs = Tags::getHtmlAttributes('<rn:foo \n/>');
        $this->assertIdentical(0, count($attrs));
    }

    public function testGetHtmlAttributes3() {
        $attrs = Tags::getHtmlAttributes('<rn:foo \n//>');
        $this->assertIdentical(0, count($attrs));
    }

    public function testGetHtmlAttributes4() {
        $attrs = Tags::getHtmlAttributes('<rn:foo aasdfsa />');
        $this->assertIdentical(0, count($attrs));
    }

    public function testGetHtmlAttributes5() {
        $attrs = Tags::getHtmlAttributes('<rn:foo asdfasdf"asdf />');
        $this->assertIdentical(0, count($attrs));
    }

    public function testGetHtmlAttributes6() {
        $attrs = Tags::getHtmlAttributes('<rn:foo a="b" />');
        $this->assertIdentical(1, count($attrs));
        $this->assertIdentical($attrs[0]->leadingWhitespace, ' ');
        $this->assertIdentical($attrs[0]->attributeName, 'a');
        $this->assertIdentical($attrs[0]->attributeValue, 'b');
        $this->assertIdentical($attrs[0]->valueDelimiter, '"');
    }

    public function testGetHtmlAttributes7() {
        $attrs = Tags::getHtmlAttributes('<rn:foo a="b"  c=\'d\'/>');
        $this->assertIdentical(2, count($attrs));
        $this->assertIdentical($attrs[1]->leadingWhitespace, '  ');
        $this->assertIdentical($attrs[1]->attributeName, 'c');
        $this->assertIdentical($attrs[1]->attributeValue, 'd');
        $this->assertIdentical($attrs[1]->valueDelimiter, '\'');
    }

    public function testGetHtmlAttributes8() {
        $attrs = Tags::getHtmlAttributes('<rn:foo a="b"  c=\'d\'   e=f/>');
        $this->assertIdentical(3, count($attrs));
        $this->assertIdentical($attrs[2]->leadingWhitespace, '   ');
        $this->assertIdentical($attrs[2]->attributeName, 'e');
        $this->assertIdentical($attrs[2]->attributeValue, 'f');
        $this->assertIdentical($attrs[2]->valueDelimiter, '');
    }

    public function testGetHtmlAttributes9() {
        $attrs = Tags::getHtmlAttributes('<rn:foo a="b" junk  c=\'d\' junk  e=f junk/>');
        $this->assertIdentical(3, count($attrs));
    }

    public function testGetHtmlAttributes10() {
        $attrs = Tags::getHtmlAttributes('<rn:foo\na="b"\n\nc=\'d\'\n\n\ne=f/>');
        $this->assertIdentical(0, count($attrs)); // There's no whitespace, so it should find no attributes.
    }

    public function testGetHtmlAttributes11() {
        $attrs = Tags::getHtmlAttributes("<rn:foo\na=\"b\"\n\nc='d'\n\n\ne=f/>");
        $this->assertIdentical(3, count($attrs));
        $this->assertIdentical($attrs[0]->leadingWhitespace, "\n");
        $this->assertIdentical($attrs[0]->attributeName, 'a');
        $this->assertIdentical($attrs[0]->attributeValue, 'b');
        $this->assertIdentical($attrs[0]->valueDelimiter, '"');
        $this->assertIdentical($attrs[1]->leadingWhitespace, "\n\n");
        $this->assertIdentical($attrs[1]->attributeName, 'c');
        $this->assertIdentical($attrs[1]->attributeValue, 'd');
        $this->assertIdentical($attrs[1]->valueDelimiter, '\'');
        $this->assertIdentical($attrs[2]->leadingWhitespace, "\n\n\n");
        $this->assertIdentical($attrs[2]->attributeName, 'e');
        $this->assertIdentical($attrs[2]->attributeValue, 'f');
        $this->assertIdentical($attrs[2]->valueDelimiter, '');
    }

    public function testGetHtmlAttributes12() {
        $attrs = Tags::getHtmlAttributes("<rn:foo\na\n=\n\"\nb\n\"\n\nc\n=\n'\nd\n'\n\n\ne\n=\nf\n/>");
        $this->assertIdentical(3, count($attrs));
        $this->assertIdentical($attrs[0]->leadingWhitespace, "\n");
        $this->assertIdentical($attrs[0]->attributeName, 'a');
        $this->assertIdentical($attrs[0]->attributeValue, "\nb\n");
        $this->assertIdentical($attrs[0]->valueDelimiter, '"');
        $this->assertIdentical($attrs[1]->leadingWhitespace, "\n\n");
        $this->assertIdentical($attrs[1]->attributeName, 'c');
        $this->assertIdentical($attrs[1]->attributeValue, "\nd\n");
        $this->assertIdentical($attrs[1]->valueDelimiter, '\'');
        $this->assertIdentical($attrs[2]->leadingWhitespace, "\n\n\n");
        $this->assertIdentical($attrs[2]->attributeName, 'e');
        $this->assertIdentical($attrs[2]->attributeValue, 'f');
        $this->assertIdentical($attrs[2]->valueDelimiter, '');
    }

    public function testGetHtmlAttributes13() {
        $attrs = Tags::getHtmlAttributes('<rn:widget path="search/KeywordText2" label_text="#rn:msg:FIND_THE_ANSWER_TO_YOUR_QUESTION_CMD#" initial_focus="true"/>');
        $this->assertIdentical($attrs[1]->completeAttribute, ' label_text="#rn:msg:FIND_THE_ANSWER_TO_YOUR_QUESTION_CMD#"');
        $this->assertIdentical($attrs[1]->attributeValue, "#rn:msg:FIND_THE_ANSWER_TO_YOUR_QUESTION_CMD#");

        // Test no quotes around rn:msg attribute
        $attrs = Tags::getHtmlAttributes('<rn:widget path="search/KeywordText2" label_text=#rn:msg:FIND_THE_ANSWER_TO_YOUR_QUESTION_CMD# initial_focus="true"/>');
        $this->assertIdentical($attrs[1]->completeAttribute, ' label_text=#rn:msg:FIND_THE_ANSWER_TO_YOUR_QUESTION_CMD#');
        $this->assertIdentical($attrs[1]->attributeValue, "#rn:msg:FIND_THE_ANSWER_TO_YOUR_QUESTION_CMD#");
    }

    public function testContainsHtmlFiveDoctype1() {
        $this->assertTrue(Tags::containsHtmlFiveDoctype("<rn:meta javascript_module='mobile_may_10'/> <!DOCTYPE html> <html>"));
        $this->assertTrue(Tags::containsHtmlFiveDoctype("<rn:meta javascript_module='mobile_may_10'/> <!DOCTYPE  html  system  'about:legacy-compat' > <html>"));
        $this->assertTrue(Tags::containsHtmlFiveDoctype("<!doctype HTML> <html>"));
        $this->assertTrue(Tags::containsHtmlFiveDoctype("<!doctype  HTML  SYSTEM  'about:legacy-compat' > <html>"));
        $this->assertFalse(Tags::containsHtmlFiveDoctype(''));
        $this->assertFalse(Tags::containsHtmlFiveDoctype('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> asdfasdf'));
    }

    public function testEnsureContentHasHeadAndBodyTags() {
        $path = '/foo/bar';
        $mustContainBodyTag = sprintf(\RightNow\Utils\Config::getMessage(PCT_S_MUST_CONTAIN_A_BODY_TAG_MSG), $path);
        $mustContainClosingBodyTag = sprintf(\RightNow\Utils\Config::getMessage(PCT_S_CONTAIN_CLOSING_BODY_TAG_MSG), $path);
        $mustContainHeadTag = sprintf(\RightNow\Utils\Config::getMessage(PCT_S_MUST_CONTAIN_A_HEAD_TAG_MSG), $path);
        $mustContainClosingHeadTag = sprintf(\RightNow\Utils\Config::getMessage(PCT_S_CONTAIN_CLOSING_HEAD_TAG_MSG), $path);

        $testItems = array(
          array(null, "<html>\n<head>\n<rn:head_content/>\n</head>\n<body>\n<rn:page_content/>\n</body>\n</html>"),
          array(null, "<html>\n<head>\n</head>\n<body>\n</body>\n</html>"),
          array(null, "<html>\n<head foo=bar>\n</head foo=bar>\n<body foo=bar>\n</body foo=bar>\n</html>"),
          array(null, "<html>\n<head	foo=bar>\n</head	foo=bar>\n<body	foo=bar>\n</body	foo=bar>\n</html>"),
          array(null, "<html>\n<head>\n</head>\n<body <? if(true): ?>foo=bar<? endif; ?>>\n</body>\n</html>"),
          array($mustContainBodyTag, "<html>\n<head>\n<rn:head_content/>\n</head>\n</html>"),
          array($mustContainBodyTag, "<html>\n<head>\n<rn:head_content/>\n</head>\n</html>"),
          array($mustContainBodyTag, null),
          array($mustContainBodyTag, ''),
          array($mustContainBodyTag, '~!@#$%^&*()_+{}|:"<>?`-=[];\',./'),
          array($mustContainHeadTag, "<html>\n<body>\n<rn:page_content/>\n</body></html>"),
          array($mustContainClosingBodyTag, "<html>\n<head>\n</head>\n<body>\n</html>"),
          array($mustContainClosingBodyTag, "<html>\n<head>\n</head>\n<body>\n<body></html>"),
          array($mustContainClosingHeadTag, "<html>\n<head>\n<body>\n</body>\n</html>"),
          array($mustContainClosingHeadTag, "<html>\n<head>\n<head>\n<body>\n</body>\n</html>"),

          array($mustContainBodyTag, "<html>\n<head>\n<rn:head_content/>\n</head>\n<bodyTag></html>"),
          array($mustContainHeadTag, "<html>\n<headTag><body>\n<rn:page_content/>\n</body></html>"),
        );

        foreach ($testItems as $pairs) {
            list($expected, $content) = $pairs;
            try {
                $actual = Tags::ensureContentHasHeadAndBodyTags($content, $path);
                if ($expected !== $actual) {
                    printf("Expected: '%s'<br>Actual: '%s'<br>", var_export($expected, true), var_export($actual, true));
                }
                $this->assertIdentical($expected, $actual);
            }
            catch (Exception $e) {
                $actual = $e->getMessage();
                if ($expected !== $actual) {
                    print("Expected: '$expected'<br>Actual (exception): '$actual'<br>");
                }
                $this->assertIdentical($expected, $actual);
            }
        }
    }

    function testTransformTags() {
        $inputs = array(
            array('<h1 class="whatever">#rn:msg:SEARCH_CRITERIA_CMD#</h1>',
                "<h1 class=\"whatever\"><?=\\RightNow\\Utils\\Config::msgGetFrom(SEARCH_CRITERIA_CMD);?></h1>"),

            array('<h1 class="whatever">#rn:msg:SEARCH_CRITERIA_CMD:RNW#</h1>',
                "<h1 class=\"whatever\"><?=\\RightNow\\Utils\\Config::msgGetFrom(SEARCH_CRITERIA_CMD);?></h1>"),

            array('<h1 class="whatever">#rn:msg:{Search Criteria}#</h1>',
                "<h1 class=\"whatever\"><?=\\RightNow\\Utils\\Config::msg('Search Criteria');?></h1>"),

            array('<h1 class="whatever">#rn:msg:{Search Criteria}:{SEARCH_CRITERIA_CMD}#</h1>',
                "<h1 class=\"whatever\"><?=\\RightNow\\Utils\\Config::msg('Search Criteria', 'SEARCH_CRITERIA_CMD');?></h1>"),

            array('<h1 class="whatever">#rn:msg:{Search Criteria}:{SEARCH_CRITERIA_CMD:RNW}#</h1>',
                "<h1 class=\"whatever\"><?=\\RightNow\\Utils\\Config::msg('Search Criteria', 'SEARCH_CRITERIA_CMD:RNW');?></h1>"),

            array('<h1 class="whatever">#rn:msg:{A String Containing a # symbol}:{SOME_NONEXISTENT_DEFINE}#</h1>',
                "<h1 class=\"whatever\"><?=\\RightNow\\Utils\\Config::msg('A String Containing a # symbol', 'SOME_NONEXISTENT_DEFINE');?></h1>"),

            array('<h1 class="whatever">#rn:msg:{A String Containing {curly} braces}:{SOME_NONEXISTENT_DEFINE}#</h1>',
                "<h1 class=\"whatever\"><?=\\RightNow\\Utils\\Config::msg('A String Containing {curly} braces', 'SOME_NONEXISTENT_DEFINE');?></h1>"),

            array('<h1 class="whatever">#rn:msg:(1234)#</h1>',
                "<h1 class=\"whatever\"><?=\\RightNow\\Utils\\Config::msgGetFrom((1234));?></h1>"),

            array('<h1 class="whatever">#rn:as' . 'tr:Title that needs to get processed into a message#</h1>',
                "<h1 class=\"whatever\">Title that needs to get processed into a message</h1>"),

            array('<h1 class="whatever">#rn:AS' . 'TR:Title that needs to get processed into a message#</h1>',
                "<h1 class=\"whatever\">Title that needs to get processed into a message</h1>"),

            //<rn:form> tags
            array('<rn:form post_handler="postRequest/sendForm"></rn:form>',
                  "<form method='post' action=\"<?= \RightNow\Utils\Url::deleteParameter(\RightNow\Utils\Url::deleteParameter(ORIGINAL_REQUEST_URI, 'session'), 'messages') . \RightNow\Utils\Url::sessionParameter(); ?>\"><div><?= \RightNow\Utils\Widgets::addServerConstraints(\RightNow\Utils\Url::deleteParameter(ORIGINAL_REQUEST_URI, \"messages\"), 'postRequest/sendForm'); ?></div></form>"),


            array('<rn:form action="/app/#rn:config:CP_HOME_URL#" name="Test" post_handler="postRequest/sendForm" name2="Test"></rn:form>',
                  "<form method='post' action='/app/<?=\RightNow\Utils\Config::configGetFrom(CP_HOME_URL);?>' name='Test' name2='Test'><div><?= \RightNow\Utils\Widgets::addServerConstraints('/app/' . \RightNow\Utils\Config::configGetFrom(CP_HOME_URL) . '', 'postRequest/sendForm'); ?></div></form>"),

            array('<rn:form action="/app/#rn:php:strtolower(\'ABC\')#/" post_handler="postRequest/sendForm"></rn:form>',
                  "<form method='post' action='/app/<?= strtolower('ABC') ?>/'><div><?= \RightNow\Utils\Widgets::addServerConstraints('/app/' . strtolower('ABC') . '/', 'postRequest/sendForm'); ?></div></form>"),

            array(
                '<rn:field name="Answer.Products"/>',
                "<?=\RightNow\Utils\Connect::getFormattedObjectFieldValue(array (
  0 => 'Answer',
  1 => 'Products',
), false, false);?>",
            ),

            array(
                '<rn:field name="Answer.Products" id="52"/>',
                "<?=\RightNow\Utils\Connect::getFormattedObjectFieldValue(array (
  0 => 'Answer',
  1 => 'Products',
), false, '52');?>",
            ),

            array(
                '<rn:field name="Answer.Products" id="52" label="no format string"/>',
                "<?=\RightNow\Utils\Connect::getFormattedObjectFieldValue(array (
  0 => 'Answer',
  1 => 'Products',
), false, '52');?>",
            ),

            array(
                '<rn:field name="Answer.Products" id="52" label="format string %s"/>',
                "<?=sprintf('format string %s', \RightNow\Utils\Connect::getFormattedObjectFieldValue(array (
  0 => 'Answer',
  1 => 'Products',
), false, '52'));?>",
            ),

            array(
                '<a href="/app/public_profile/user/#rn:profile:socialUserID#">User</a>',
                '<a href="/app/public_profile/user/<?=get_instance()->session->getProfileData("socialUserID");?>">User</a>',
            ),
            array(
                '<rn:widget path="standard/social/CommunityPostDisplay">',
                '<?=\\RightNow\\Utils\\Widgets::rnWidgetRenderCall(\'standard/social/CommunityPostDisplay\', array());
?>'
            ),
            array(
                '<rn:widget path="user/AvatarDisplay"/>',
                '<?=\\RightNow\\Utils\\Widgets::rnWidgetRenderCall(\'user/AvatarDisplay\', array());
?>'
            ),
        );

        $this->logIn();
        foreach ($inputs as $pair) {
            list($input, $expected) = $pair;
            $this->assertIdentical($expected, Tags::transformTags($input));
        }
        $this->logOut();
    }

    function testEscapeForWithinPhp() {
        $inputs = array(
            array('"#rn:msg:(1234)#"',
                  '"\' . \\RightNow\\Utils\\Config::msgGetFrom((1234)) . \'"'),

            array('"#rn:msg:SEARCH_CRITERIA_CMD#"',
                  '"\' . \\RightNow\\Utils\\Config::msgGetFrom(SEARCH_CRITERIA_CMD) . \'"'),

            array('#rn:msg:SEARCH_CRITERIA_CMD#',
                  '\' . \\RightNow\\Utils\\Config::msgGetFrom(SEARCH_CRITERIA_CMD) . \''),

            array('"#rn:msg:{Search Criteria}#"',
                  '"\' . \\RightNow\\Utils\\Config::msg(\'Search Criteria\') . \'"'),

            array('"#rn:msg:{Search Criteria}:{SEARCH_CRITERIA_CMD}#"',
                  '"\' . \\RightNow\\Utils\\Config::msg(\'Search Criteria\', \'SEARCH_CRITERIA_CMD\') . \'"'),

            array('#rn:msg:{Search Criteria}:{SEARCH_CRITERIA_CMD}#',
                  '\' . \\RightNow\\Utils\\Config::msg(\'Search Criteria\', \'SEARCH_CRITERIA_CMD\') . \''),

            array('"#rn:msg:{A String Containing a # symbol}:{SOME_NONEXISTENT_DEFINE}#"',
                  '"\' . \\RightNow\\Utils\\Config::msg(\'A String Containing a # symbol\', \'SOME_NONEXISTENT_DEFINE\') . \'"'),

            array('"#rn:url_param_value:step#"',
                    '"\' . \\RightNow\\Utils\\Url::getParameter(\'step\') . \'"'),

            array('"#rn:php:strtolower(\'ABC\')#"',
                  '"\' . strtolower(\'ABC\') . \'"'),

            array('"#rn:AS' . /* prevent translation */ 'TR:string#"',
                  '"\' . \'string\' . \'"'),

            array('"#rn:session#"',
                  '"\' . \\RightNow\\Utils\\Url::sessionParameter() . \'"'),

            array('"#rn:profile:contactID#"',
                '"\' . get_instance()->session->getProfileData(\'contactID\') . \'"'),

            array('"#rn:flashdata:test#"',
                '"\' . get_instance()->session->getFlashData(\'test\') . \'"'),

            array('"#rn:language_code#"',
                  '"\' . \\RightNow\\Utils\\Text::getLanguageCode() . \'"'),
        );

        $this->logIn();
        $template = "<rn:widget path=\"search/KeywordText2\" label_text=%s initial_focus=\"true\"/>";
        foreach ($inputs as $pair) {
            $input = sprintf($template, $pair[0]);
            $expected = sprintf($template, $pair[1]);
            $this->assertIdentical($expected, Tags::escapeForWithinPhp($input));
        }
        $this->logOut();
    }

    function testGetAttributeValuesFromCollection() {
        $this->assertIdentical(array(), Tags::getAttributeValuesFromCollection(array(), array()));
        $this->assertIdentical(array('found' => false), Tags::getAttributeValuesFromCollection(array(), array('found')));
        $this->assertIdentical(array(), Tags::getAttributeValuesFromCollection(array((object) array('attributeName' => 'foo', 'attributeValue' => 'bar')), array()));
        $this->assertIdentical(array('found' => 'bananas', 'or' => '', 'barrio' => false), Tags::getAttributeValuesFromCollection(array(
            (object) array('attributeName' => 'foo', 'attributeValue' => 'bar'),
            (object) array('attributeName' => 'or', 'attributeValue' => ''),
            (object) array('attributeName' => 'found', 'attributeValue' => 'bananas'),
        ), array('found', 'or', 'barrio')));
    }
}
