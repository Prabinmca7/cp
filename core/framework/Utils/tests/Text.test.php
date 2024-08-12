<?php

use RightNow\Utils\Text,
    RightNow\Api;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TextTest extends CPTestCase {
    public $testingClass = 'Rightnow\Utils\Text';
    public function testBeginsWith() {
        $this->assertTrue(Text::beginsWith('a', 'a'));
        $this->assertTrue(Text::beginsWith('ab', 'a'));
        $this->assertFalse(Text::beginsWith('a', 'ab'));
        $this->assertFalse(Text::beginsWith('ab', 'A'));
        $this->assertTrue(Text::beginsWith('a', ''));
        $this->assertTrue(Text::beginsWith('', ''));

        //This behavior seems insane, and it probably is, but it's out there already. Wait until a minor release.
        $this->assertTrue(Text::beginsWith('abcd', null));
        $this->assertTrue(Text::beginsWith('abcd', $a));
    }

    public function testEmphasizeText(){
        $bananaTextWrapper = "<em class='rn_Highlight'>%s</em>";
        $searchText = "Banana";
        $bananaStringWithEmphasis = Text::emphasizeText($searchText, array('query'=>'Bananas taste great!'));
        $raisinsStringWithEmphasis = Text::emphasizeText($searchText, array('query'=>'Raisins... not so great.'));
		$style_empasis = Text::emphasizeText($searchText, array('query'=>'<style> table#banana {border-left: 1px solid \#000;border-bottom: 1px solid \#000;border-right: 1px solid \#000; }</style>'));
		$script_empasis = Text::emphasizeText($searchText, array('query'=>'<script> alert banana</script>'));
		$script_text_empasis = Text::emphasizeText($searchText, array('query'=>'banana<script> alert banana</script>'));
		$style_text_empasis = Text::emphasizeText($searchText, array('query'=>'banana<style> alert banana</style>'));
		$script_empasis = Text::emphasizeText($searchText, array('query'=>'<script> alert banana</script>'));
		$this->assertNotEqual(sprintf($bananaTextWrapper, $searchText), $style_empasis);
		$this->assertNotEqual(sprintf($bananaTextWrapper, $searchText), $script_empasis);
        $this->assertSame(sprintf($bananaTextWrapper, $searchText), $bananaStringWithEmphasis);
		$this->assertSame(sprintf($bananaTextWrapper, $searchText), $script_text_empasis);
		$this->assertSame(sprintf($bananaTextWrapper, $searchText), $style_text_empasis);
        $this->assertNotEqual(sprintf($bananaTextWrapper, $searchText), $raisinsStringWithEmphasis);
    }

     public function testHighlightTextHelper(){
        $bananaTextWrapper = "<span class=\"highlight\">%s</span>";
        $searchText = "Bananas";
        $bananaStringWithHighlight = Text::highlightTextHelper($searchText, 'Bananas taste great!', 4);
        $raisinsStringWithEmphasis = Text::highlightTextHelper($searchText, 'Raisins... not so great.', 4);
        $this->assertSame(sprintf($bananaTextWrapper, $searchText), $bananaStringWithHighlight);
        $this->assertNotEqual(sprintf($bananaTextWrapper, $searchText), $raisinsStringWithEmphasis);
    }

    public function testBeginsWithCaseInsensitive(){
        $this->assertTrue(Text::beginsWithCaseInsensitive('a', 'a'));
        $this->assertTrue(Text::beginsWithCaseInsensitive('ab', 'A'));
        $this->assertFalse(Text::beginsWithCaseInsensitive('bA', 'A'));
        $this->assertTrue(Text::beginsWithCaseInsensitive('Ab', 'a'));
        $this->assertTrue(Text::beginsWithCaseInsensitive('A', 'a'));
        $this->assertTrue(Text::beginsWithCaseInsensitive('a', ''));
        $this->assertTrue(Text::beginsWithCaseInsensitive('', ''));
    }

    public function testEndsWith() {
        $this->assertTrue(Text::endsWith('a', 'a'));
        $this->assertTrue(Text::endsWith('ba', 'a'));
        $this->assertFalse(Text::endsWith('a', 'ab'));
        $this->assertFalse(Text::endsWith('ba', 'A'));
        $this->assertFalse(Text::endsWith('a', ''));
        $this->assertFalse(Text::endsWith('', ''));
    }

    public function testExpandAnswerTags() {
      $this->assertIdentical('Question about iPhone 5', Text::expandAnswerTags('Question about iPhone 5'));
      $this->assertNotEqual('Question about iPhone 5', Text::expandAnswerTags('Question about iPhone 4'));
      $this->assertEqual('iPhone 5', Text::expandAnswerTags('iPhone 5', true, true));

      $conditionalContent = 'iPhone 5 conditional section.';
      $conditionalSection = 'iPhone 5 <rn:answer_section title="iPhone 5">'.$conditionalContent.'</rn:answer_section>';

      $this->assertTrue(Text::stringContains(Text::expandAnswerTags($conditionalSection, true, true), $conditionalContent));
      $this->assertFalse(Text::stringContains(Text::expandAnswerTags($conditionalSection, true, false), $conditionalContent));
      $this->assertFalse(Text::stringContains(Text::expandAnswerTags($conditionalSection, false, false), $conditionalContent));
    }

    public function testGetSubstringAfter() {
        $this->assertEqual('bc', Text::getSubstringAfter('abc', 'a'));
        $this->assertFalse(Text::getSubstringAfter('asdf', 'q'));
        $this->assertFalse(Text::getSubstringAfter('banana/foo', 'foo', 'no'));
        $this->assertSame('foo', Text::getSubstringAfter('banana/foo', '/', 'no'));
        $this->assertFalse(Text::getSubstringAfter('banana', 'banana', 'banana'));
        $this->assertEqual('foo', Text::getSubstringAfter('asdf', 'q', 'foo'));
    }

    public function testGetSubstringBefore() {
        $this->assertEqual('abc', Text::getSubstringBefore('abcdef', 'def'));
        $this->assertFalse(Text::getSubstringBefore('asdf', 'q'));
        $this->assertEqual('foo', Text::getSubstringBefore('asdf', 'q', 'foo'));
    }

    public function testGetSubstringStartingWith() {
        $this->assertEqual('bcdef', Text::getSubstringStartingWith('abcdef', 'bc'));
        $this->assertFalse(Text::getSubstringStartingWith('asdf', 'q'));
        $this->assertEqual('foo', Text::getSubstringStartingWith('asdf', 'q', 'foo'));
    }

    public function testGetMultibyteSubstring() {
        $this->assertEqual('abc', Text::getMultibyteSubstring('abcdef', 0, 3));
        $this->assertEqual('bc', Text::getMultibyteSubstring('abcdef', 1, 2));
        $this->assertEqual('def', Text::getMultibyteSubstring('abcdef', 3, 7));
        $this->assertEqual('de', Text::getMultibyteSubstring('abcdef', -3, 2));
        $this->assertEqual('ßäöüßä', Text::getMultibyteSubstring('äöüßäöüßäöüß', 3, 6));
        $this->assertEqual('ü', Text::getMultibyteSubstring('äöüßäöüßäöüß', -2, 1));
        $this->assertEqual('öqwe', Text::getMultibyteSubstring('äöqwertyöüß', 1, 4));
        $this->assertEqual('efghijk', Text::getMultibyteSubstring('äbcdefghijk', 4));
        $this->assertEqual('defghi', Text::getMultibyteSubstring('äbcdefghijk', 3, -2));
        $this->assertEqual('defghijk', Text::getMultibyteSubstring('äbcdefghijk', 3, null));
        $this->assertEqual('c', Text::getMultibyteSubstring('äbc', 2));
        $this->assertFalse(Text::getMultibyteSubstring('äbc', 10));
        $this->assertFalse(Text::getMultibyteSubstring('äbc', 3));
        $this->assertFalse(Text::getMultibyteSubstring('äbc', 0, 0));
    }

    public function testStrlenCompare() {
        $this->assertEqual(0, Text::strlenCompare('abcdef', 'abc ef'));
        $this->assertEqual(1, Text::strlenCompare('abcdef ghij', 'abcdef'));
        $this->assertEqual(-1, Text::strlenCompare('abcdef', 'abcdef ghij'));
        $this->assertEqual(1, Text::strlenCompare('abcdef', 'a'));
        $this->assertEqual(-1, Text::strlenCompare('abc', 'abcdef'));
    }

    public function testGetMultibyteStringLength()
    {
        $this->assertEqual(26, Text::getMultibyteStringLength('コンソールで、デフォルトのモードは何になるでしょうか'));
        $this->assertEqual(26, Text::getMultibyteStringLength('abcdefghigklmnopqrstuvwxyz'));
        $this->assertNotEqual(26, Text::getMultibyteStringLength('のAAQで質問。コンソールで、デフォルトのモードは何になるでしょうか'));
        $this->assertNotEqual(26, Text::getMultibyteStringLength(''));
    }

    public function testGetMultibyteCharacters()
    {
        $testString = 'コンソールで、デフォルトのモードは何になるでしょうか';
        $result = Text::getMultibyteCharacters($testString);
        $this->assertEqual(26, count($result));
        $this->assertEqual('コ', $result[0]);
        //Arrays are indexed by byte in PHP, the first character in testString is a multibyte character, so these should differ
        $this->assertNotEqual($testString[0], $result[0]);

        $testString = 'abcdefghigklmnopqrstuvwxyz';
        $result = Text::getMultibyteCharacters($testString);
        $this->assertEqual(26, count($result));
        $this->assertEqual('a', $result[0]);
        //For standard ascii strings, a byte is equivalent to a character, so these should be the same
        $this->assertEqual($testString[0], $result[0]);
    }

    public function testSlugify()
    {
        $this->assertEqual('walrus', Text::slugify('Walrus'));
        $this->assertEqual('shoes', Text::slugify(' shoes '));
        $this->assertEqual('walrus-shoes', Text::slugify('Walrus Shoes'));
        $this->assertEqual('walrus-shoes', Text::slugify('walrus------shoes'));
        $this->assertEqual('walrus-shoes', Text::slugify('\'walrus\' "shoes"'));
        $this->assertEqual('books-shoes', Text::slugify('books & shoes'));
        //non ASCII characters should be preserved
        $this->assertEqual('chn-海象鞋', Text::slugify('CHN-海象鞋'));
        $this->assertEqual('海象鞋', Text::slugify('海象鞋'));
        $this->assertEqual('chn-海象鞋', Text::slugify('CHN<>海象鞋'));
        $this->assertEqual('海象-鞋', Text::slugify('海象###`!鞋'));
    }

    public function testRemoveTrailingSlash()
    {
        $this->assertEqual('/one/two/three/four', Text::removeTrailingSlash('/one/two/three/four/'));
        $this->assertEqual('/', Text::removeTrailingSlash('/////////'));
        $this->assertNotEqual('/one/two/three/four/', Text::removeTrailingSlash('/one/two/three/four/'));
    }

    public function testStringContains() {
        $this->assertTrue(Text::stringContains('/one/two/three/four', '/two/three/'));
        $this->assertFalse(Text::stringContains('/one/two/three/four', '/One/TWO/'));
    }

    public function testEscapeStringForJavaScript()
    {
        $this->assertEqual('a line break \\\r\\\n here', Text::escapeStringForJavaScript('a line break \r\n here'));
        $this->assertEqual('another line break \\\r here', Text::escapeStringForJavaScript('another line break \r here'));
        $this->assertEqual('another line break \\\n here', Text::escapeStringForJavaScript('another line break \n here'));
        $this->assertEqual('escape backslash \\\ here', Text::escapeStringForJavaScript('escape backslash \ here'));
        $this->assertEqual('escape single quote \\\' here', Text::escapeStringForJavaScript("escape single quote ' here"));
        $this->assertEqual('escape single quote \\\' here', Text::escapeStringForJavaScript('escape single quote \' here'));
        $this->assertEqual('escape double quotes \\" here', Text::escapeStringForJavaScript('escape double quotes " here'));
        $this->assertNotEqual('a line break \r\n here', Text::escapeStringForJavaScript('a line break \r\n here'));
        $this->assertNotEqual('another line break \r here', Text::escapeStringForJavaScript('another line break \r here'));
        $this->assertNotEqual('another line break \n here', Text::escapeStringForJavaScript('another line break \n here'));
        $this->assertNotEqual('escape backslash \ here', Text::escapeStringForJavaScript('escape backslash \ here'));
        $this->assertNotEqual("escape single quote ' here", Text::escapeStringForJavaScript("escape single quote ' here"));
        $this->assertNotEqual('escape single quote \' here', Text::escapeStringForJavaScript('escape single quote \' here'));
        $this->assertNotEqual('escape double quotes " here', Text::escapeStringForJavaScript('escape double quotes " here'));
    }

    public function testUnescapeQuotes(){
        $this->assertEqual('"', Text::unescapeQuotes('&quot;'));
        $this->assertEqual('""', Text::unescapeQuotes('&quot;"'));
        $this->assertNotEqual('&quot;', Text::unescapeQuotes('&quot;'));
    }

    public function testEscapeHtml(){
        $this->assertIdentical('aSdf', Text::escapeHtml('aSdf'));
        $this->assertIdentical('%2f', Text::escapeHtml('%2f'));
        $this->assertIdentical('test@example.com', Text::escapeHtml('test@example.com'));
        $this->assertIdentical('!@#$%^*()_-+=|\{[]}', Text::escapeHtml('!@#$%^*()_-+=|\{[]}'));

        $this->assertIdentical('&amp;', Text::escapeHtml('&'));
        $this->assertIdentical('&gt;', Text::escapeHtml('>'));
        $this->assertIdentical('&lt;', Text::escapeHtml('<'));
        $this->assertIdentical('&quot;', Text::escapeHtml('"'));
        $this->assertIdentical('&#039;', Text::escapeHtml("'"));
        $this->assertIdentical('', Text::escapeHtml(""));

        $this->assertNull(Text::escapeHtml(null));
        $this->assertFalse(Text::escapeHtml(false));
        $this->assertIdentical(array(), Text::escapeHtml(array()));
        $this->assertIdentical((object)array(), (object)Text::escapeHtml(array()));
        $this->assertIdentical(1, Text::escapeHtml(1));

        //Double encoding tests
        $this->assertIdentical('&amp;amp;', Text::escapeHtml('&amp;'));
        $this->assertIdentical('&amp;amp;', Text::escapeHtml('&amp;', true));
        $this->assertIdentical('&amp;amp;', Text::escapeHtml('&amp;', 1));
        $this->assertIdentical('&amp;', Text::escapeHtml('&amp;', false));
        $this->assertIdentical('&amp;lt;', Text::escapeHtml('&lt;', true));
        $this->assertIdentical('&lt;', Text::escapeHtml('&lt;', false));
        $this->assertIdentical('&amp;gt;', Text::escapeHtml('&gt;', true));
        $this->assertIdentical('&gt;', Text::escapeHtml('&gt;', false));
    }

    public function testUnescapeHtml() {
        $this->assertIdentical('aSdf', Text::unescapeHtml('aSdf'));
        $this->assertIdentical('%2f', Text::unescapeHtml('%2f'));
        $this->assertIdentical('test@example.com', Text::unescapeHtml('test@example.com'));
        $this->assertIdentical('!@#$%^*()_-+=|\{[]}', Text::unescapeHtml('!@#$%^*()_-+=|\{[]}'));

        $this->assertIdentical('&', Text::unescapeHtml('&amp;'));
        $this->assertIdentical('>', Text::unescapeHtml('&gt;'));
        $this->assertIdentical('<', Text::unescapeHtml('&lt;'));
        $this->assertIdentical('"', Text::unescapeHtml('&quot;'));
        $this->assertIdentical("'", Text::unescapeHtml('&#039;'));
        $this->assertIdentical('', Text::unescapeHtml(""));

        $this->assertNull(Text::unescapeHtml(null));
        $this->assertFalse(Text::unescapeHtml(false));
        $this->assertIdentical(array(), Text::unescapeHtml(array()));
        $this->assertIdentical((object)array(), (object)Text::unescapeHtml(array()));
        $this->assertIdentical(1, Text::unescapeHtml(1));

    }

    public function testMinifyCss(){
        $string_CSS = ".rn_Grid .yui3-datatable-table{".
                     " border:1px solid     #888;".
                     " border-spacing: 0;".
                     " /*CSS comments*/".
                     " border-collapse: separate;     }";
        $string_result =".rn_Grid .yui3-datatable-table{border:1px solid #888;border-spacing: 0;border-collapse: separate;}";
        $this->assertEqual($string_result, Text::minifyCss($string_result));
    }

    public function testGetReadableFileSize(){
        $this->assertEqual('1005 bytes',Text::getReadableFileSize(1005));
        $this->assertEqual('1 KB',Text::getReadableFileSize(1024));
        $this->assertEqual('1023.93 KB',Text::getReadableFileSize(1048500));
        $this->assertEqual('1 MB',Text::getReadableFileSize(1048576));
        $this->assertNotEqual('1 GB',Text::getReadableFileSize(1099511627776));
    }

    public function testGetLocaleTruncatedValue(){
        // Different combinations, but default locale
        $this->assertSame('434', Text::getLocaleTruncatedValue(434, 0));
        $this->assertSame('434.00', Text::getLocaleTruncatedValue(434.00, 2));
        $this->assertSame('434', Text::getLocaleTruncatedValue(434.00, 2, false, true));
        $this->assertSame('434.00', Text::getLocaleTruncatedValue(434, 2));
        $this->assertSame('434,000,000,000.00', Text::getLocaleTruncatedValue(434000000000, 2));
        $this->assertSame('434,000,000,000.55', Text::getLocaleTruncatedValue(434000000000.55390481, 2));
        $this->assertSame('434000000000.00', Text::getLocaleTruncatedValue(434000000000, 2, true));
        $this->assertSame('434000000000.55', Text::getLocaleTruncatedValue(434000000000.55390481, 2, true));
        $this->assertSame('435', Text::getLocaleTruncatedValue(434.55390481, 0));
        $this->assertSame('434.55', Text::getLocaleTruncatedValue(434.55390481, 2));
        $this->assertSame('434.55390', Text::getLocaleTruncatedValue(434.55390481, 5));
        $this->assertSame('435', Text::getLocaleTruncatedValue(434.55390481, 0));
        $this->assertSame('434.55', Text::getLocaleTruncatedValue(434.55390481, 2));

        // Same combinations above, but German locale
        $this->assertSame('434', Text::getLocaleTruncatedValue(434, 0, false, false, 'de_DE'));
        $this->assertSame('434,00', Text::getLocaleTruncatedValue(434.00, 2, false, false, 'de_DE'));
        $this->assertSame('434', Text::getLocaleTruncatedValue(434.00, 2, false, true, 'de_DE'));
        $this->assertSame('434,00', Text::getLocaleTruncatedValue(434, 2, false, false, 'de_DE'));
        $this->assertSame('434.000.000.000,00', Text::getLocaleTruncatedValue(434000000000, 2, false, false, 'de_DE'));
        $this->assertSame('434.000.000.000,55', Text::getLocaleTruncatedValue(434000000000.55390481, 2, false, false, 'de_DE'));
        $this->assertSame('434000000000,00', Text::getLocaleTruncatedValue(434000000000, 2, true, false, 'de_DE'));
        $this->assertSame('434000000000,55', Text::getLocaleTruncatedValue(434000000000.55390481, 2, true, false, 'de_DE'));
        $this->assertSame('435', Text::getLocaleTruncatedValue(434.55390481, 0, false, false, 'de_DE'));
        $this->assertSame('434,55', Text::getLocaleTruncatedValue(434.55390481, 2, false, false, 'de_DE'));
        $this->assertSame('434,55390', Text::getLocaleTruncatedValue(434.55390481, 5, false, false, 'de_DE'));
        $this->assertSame('435', Text::getLocaleTruncatedValue(434.55390481, 0, false, false, 'de_DE'));
        $this->assertSame('434,55', Text::getLocaleTruncatedValue(434.55390481, 2, false, false, 'de_DE'));
    }

    public function testJoinOmittingBlanks()
    {
        $this->assertEqual('1 AND 2 AND 3', Text::joinOmittingBlanks(' AND ', $TestArray = array('a' => 1, 'b'=> 2, 'c' => 3)));
        $this->assertEqual('1 AND 3', Text::joinOmittingBlanks(' AND ', $TestArray = array('a' => 1, 'b'=> '', 'c' => 3)));
        $this->assertEqual('1 AND 3', Text::joinOmittingBlanks(' AND ', $TestArray = array('a' => 1, 'b'=> null, 'c' => 3)));
        $this->assertNotEqual('1 AND 2 AND 3', Text::joinOmittingBlanks(' AND ', $TestArray = array('a' => 1, 'b'=> null, 'c' => 3)));
        $this->assertNotEqual('1 AND AND 3', Text::joinOmittingBlanks(' AND ', $TestArray = array('a' => 1, 'b'=> '', 'c' => 3)));
    }

    public function testStringContainsCaseInsensitive() {
        $this->assertTrue(Text::stringContainsCaseInsensitive('/one/two/three/four', '/One/TWO/'));
        $this->assertTrue(Text::stringContainsCaseInsensitive('/one/two/three/four', '/three/four'));
        $this->assertFalse(Text::stringContainsCaseInsensitive('/one/two/three/four', '/three/four/five'));
    }

    public function testRemoveSuffixIfExists() {
        $haystack = 'abc def abc';
        $this->assertEqual('abc def ', Text::removeSuffixIfExists($haystack, 'abc'));
        $this->assertEqual($haystack, Text::removeSuffixIfExists($haystack, 'def'));
    }

    function testIsValidEmailAddress() {
        $this->assertFalse(Text::isValidEmailAddress(''));
        $this->assertFalse(Text::isValidEmailAddress('     ' . 'asdf@asdf.com'));
        $this->assertFalse(Text::isValidEmailAddress('asdf@'));
        $this->assertFalse(Text::isValidEmailAddress('@asdf'));
        $this->assertTrue(Text::isValidEmailAddress('asdf@asdf.com'));
        $this->assertTrue(Text::isValidEmailAddress("paddyo'reilly@asdf.com"));
        $looong = '';
        while(strlen($looong) < 300)
            $looong .= 'a';
        $this->assertFalse(Text::isValidEmailAddress($looong));
        $looong = $looong . '@foo.com';
        $this->assertFalse(Text::isValidEmailAddress($looong));
        $looong = substr($looong, 255);
        $this->assertTrue(Text::isValidEmailAddress($looong));
    }

    function testIsValidUrl() {
        //Valud URLs
        $this->assertTrue(Text::isValidUrl("foo.com"));
        $this->assertTrue(Text::isValidUrl("foo.com/"));
        $this->assertTrue(Text::isValidUrl("foo.com/one/two"));
        $this->assertTrue(Text::isValidUrl("www.foo.com"));
        $this->assertTrue(Text::isValidUrl("ß∂ƒ´®´∑œ∑∂ß.au"));
        $this->assertTrue(Text::isValidUrl("a.b"));
        $this->assertTrue(Text::isValidUrl("http://www.foo.com"));
        $this->assertTrue(Text::isValidUrl("HTTP://WWw.fOo.COM"));
        $this->assertTrue(Text::isValidUrl("http://foo.bar.baz.com"));
        $this->assertTrue(Text::isValidUrl("http://foo.com/blah_blah_(wikipedia)"));
        $this->assertTrue(Text::isValidUrl("http://www.example.com/wpstyle/?p=364"));
        $this->assertTrue(Text::isValidUrl("http://✪df.ws/123"));
        $this->assertTrue(Text::isValidUrl("http://userid:password@example.com:8080"));
        $this->assertTrue(Text::isValidUrl("http://userid@example.com"));
        $this->assertTrue(Text::isValidUrl("http://foo.com/blah_(wikipedia)#cite-1"));
        $this->assertTrue(Text::isValidUrl("http://مثال.إالعربية"));
        $this->assertTrue(Text::isValidUrl("http://例子.测试"));
        $this->assertTrue(Text::isValidUrl("http://उदाहरण.परीक्षा/उदाहरण/परीक्षा"));
        $this->assertTrue(Text::isValidUrl("ab://____.___"));
        $this->assertTrue(Text::isValidUrl("google.com/~`!@#$%^&*()_-+={[}]|\;:'\"<,>.?/"));
        $this->assertTrue(Text::isValidUrl("a9://foo.com"));
        $this->assertTrue(Text::isValidUrl("a9://foo.com"));
        $this->assertTrue(Text::isValidUrl("a9.+-://foo.com"));
        $this->assertTrue(Text::isValidUrl("http://.www.foo.bar/"));

        //IPv6 addresses
        $this->assertTrue(Text::isValidUrl("[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/index.html"));
        $this->assertTrue(Text::isValidUrl("http://[1080:0:0:0:8:800:200C:417a]/index.html"));
        $this->assertTrue(Text::isValidUrl("http://[::192.9.5.5]/ipng"));
        $this->assertTrue(Text::isValidUrl("http://[2010:836B:4179::836B:4179]"));
        $this->assertTrue(Text::isValidUrl("http://[::ffff:129.144.52.38]:80/index.html"));
        $this->assertTrue(Text::isValidUrl("[:]/index.html"));
        $this->assertTrue(Text::isValidUrl("[::::::::::]/index.html"));

        //Invalid URLs

        //Invalid parameter types
        $this->assertFalse(Text::isValidUrl(20));
        $this->assertFalse(Text::isValidUrl(null));
        $this->assertFalse(Text::isValidUrl(false));
        $this->assertFalse(Text::isValidUrl(array()));

        //Protocol checks
        $this->assertFalse(Text::isValidUrl("a://b.com"));
        $this->assertFalse(Text::isValidUrl("9://b.com"));
        $this->assertFalse(Text::isValidUrl("9a://b.com"));
        $this->assertFalse(Text::isValidUrl("123://google.com/"));
        $this->assertFalse(Text::isValidUrl("ab!@#$%^<>://b.com"));
        $this->assertFalse(Text::isValidUrl("http://"));
        $this->assertFalse(Text::isValidUrl("http://."));
        $this->assertFalse(Text::isValidUrl("http://.."));

        $this->assertFalse(Text::isValidUrl("http://#"));

        //Hostname checks
        $this->assertFalse(Text::isValidUrl("asdf"));
        $this->assertFalse(Text::isValidUrl("/foo.com/"));

        $this->assertFalse(Text::isValidUrl("http:// shouldfail.com"));
        $this->assertFalse(Text::isValidUrl("http://<helloworld>.com"));
        $this->assertFalse(Text::isValidUrl("http://foo/bar.com"));
        $this->assertFalse(Text::isValidUrl("asdf//google.com/"));
        $this->assertFalse(Text::isValidUrl("http/google.com/foo?bar=af"));
        $this->assertFalse(Text::isValidUrl("http://foo"));
        $this->assertFalse(Text::isValidUrl("http://foo."));
        $this->assertFalse(Text::isValidUrl("http://foo.b ar"));
        $this->assertFalse(Text::isValidUrl("http://foo.<bar>"));
        $this->assertFalse(Text::isValidUrl("http://foo./bar"));

        //Invalid IPv6
        $this->assertFalse(Text::isValidUrl("FEDC:BA98:7654:3210:FEDC:BA98:7654:3210/index.html"));
        $this->assertFalse(Text::isValidUrl("[]/index.html"));
        $this->assertFalse(Text::isValidUrl("[zy]/index.html"));
        $this->assertFalse(Text::isValidUrl("[ef:gh:ij:kl:mn:op:qr:st:uv:wx:yz]/index.html"));

        //Path Checks
        $this->assertFalse(Text::isValidUrl("http://foo.bar?q=Spaces should be encoded"));
        $this->assertFalse(Text::isValidUrl("http://foo.bar?q=\n\t\r"));
    }

    //@@@ QA 130320-000031
    function testIsValidDate() {
        $this->assertNull(Text::isValidDate(null));
        $this->assertNull(Text::isValidDate(array()));
        $this->assertNull(Text::isValidDate(array(1, 2)));
        $this->assertNull(Text::isValidDate(1));
        $this->assertNull(Text::isValidDate(-1));
        $this->assertNull(Text::isValidDate("1"));
        $this->assertNull(Text::isValidDate("-1"));
        $this->assertNull(Text::isValidDate("2008-14-23"));
        $this->assertNull(Text::isValidDate("2008-14-23 a:b:c"));
        $this->assertTrue(Text::isValidDate("2009-1-5 00:00:00"));
        $this->assertTrue(Text::isValidDate("2008-2-29 00:00:00"));
        $this->assertFalse(Text::isValidDate("2009-2-29 00:00:00"));
        $this->assertFalse(Text::isValidDate("2009-14-23 00:00:00"));
    }

    public function testTruncateText() {
        $testText = 'abcdefghi jklmnopqr stuvwxyz 1234567890 1234567890StartTruncationHere';
        $this->assertEqual('abcdefghi jklmnopqr stuvwxyz 1234567890...', Text::truncateText($testText, 50));
        $this->assertEqual('abcdefghi jklmnopqr stuvwxyz 1234567890', Text::truncateText($testText, 50, false));
        $this->assertEqual('abcdefghi jklmnopqr stuvwxyz 1234567890 1234567890', Text::truncateText($testText, 50, false, 5));
        $this->assertEqual('abcdefghi jklmnopqr stuvwxyz 1234567890', Text::truncateText($testText, 50, false, 12));
    }

    function testGetMaskPairs() {
        $method = $this->getMethod('getMaskPairs', true);
        $actual = $method('M#M#M#F-M#M#F-M#M#M#M#');
        $expected = array('M#', 'M#', 'M#', 'F-', 'M#', 'M#', 'F-', 'M#', 'M#', 'M#', 'M#');
        $this->assertIdentical($expected, $actual);
    }

    function testMapCharactersToMask() {
        $method = $this->getMethod('mapCharactersToMask', true);

        $actual = $method('123-45-6789', 'M#M#M#F-M#M#F-M#M#M#M#');
        $expected = array(
          array('1', 'M#'),
          array('2', 'M#'),
          array('3', 'M#'),
          array('-', 'F-'),
          array('4', 'M#'),
          array('5', 'M#'),
          array('-', 'F-'),
          array('6', 'M#'),
          array('7', 'M#'),
          array('8', 'M#'),
          array('9', 'M#'),
        );
        $this->assertIdentical($expected, $actual);

        $actual = $method('(123', 'F(M#M#M#F)', false);
        $expected = array(
          array('(', 'F('),
          array('1', 'M#'),
          array('2', 'M#'),
          array('3', 'M#'),
          array(null, 'F)'),
        );
        $this->assertIdentical($expected, $actual);

        $actual = $method('(123)-', 'F(M#M#M#F)', false);
        $expected = array(
          array('(', 'F('),
          array('1', 'M#'),
          array('2', 'M#'),
          array('3', 'M#'),
          array(')', 'F)'),
          array('-', null),
        );
        $this->assertIdentical($expected, $actual);

        $actual = $method(null, 'F(', false);
        $expected = array(array(null, 'F('));
        $this->assertIdentical($expected, $actual);

        $error = 'Mask length [5] does not match value length [4]';
        try {
            $actual = $method('(123', 'F(M#M#M#F)');
            $this->fail($error);
        }
        catch (\Exception $e) {
            $this->pass();
        }

        $error = 'Exception should have been thrown for mask not being a string, or having an odd number of characters';
        try {
            $method('123', 'M#M#M');
            $this->fail($error);
        }
        catch (\Exception $e) {
            $this->pass();
        }

        try {
            $method('123', null);
            $this->fail($error);
        }
        catch (\Exception $e) {
            $this->pass();
        }

        try {
            $method('123', array());
            $this->fail($error);
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testGetSimpleMaskString() {
        $inputs = array(
            array('#', 'F#'),
            array('A', 'FA'),
            array('L', 'FL'),
            array('C', 'FC'),
            array('9', 'F9'),

            array('#', 'U#'),
            array('@', 'UA'),
            array('A', 'UL'),
            array('@', 'UC'),

            array('#', 'L#'),
            array('@', 'LA'),
            array('a', 'LL'),
            array('@', 'LC'),

            array('#', 'M#'),
            array('@', 'MA'),
            array('@', 'ML'),
            array('@', 'MC'),

            array('###-##-####', 'M#M#M#F-M#M#F-M#M#M#M#'),
            array('(###) ###-####', 'F(M#M#M#F)F M#M#M#F-M#M#M#M#'),
            array('A#A #A#', 'ULM#ULF M#ULM#'),
            array('+1 (###) ###-####', 'F+F1F F(M#M#M#F)F M#M#M#F-M#M#M#M#'),
        );

        foreach($inputs as $input) {
            list($expected, $mask) = $input;
            $actual = Text::getSimpleMaskString($mask);
            $error = sprintf("Expected: '%s' got '%s' for mask '%s'", $expected, $actual, $mask);
            $this->assertIdentical($expected, $actual, $error);
        }
    }

    function testStripInputMask() {
        $inputs = array(
            array('123456789', '123-45-6789', 'M#M#M#F-M#M#F-M#M#M#M#'),
            array('1234567890', '(123) 456-7890', 'F(M#M#M#F)F M#M#M#F-M#M#M#M#'),
            array('A1B2C3', 'A1B 2C3', 'ULM#ULF M#ULM#'),
            array('1111231111', '+1 (111) 123-1111', 'F+F1F F(M#M#M#F)F M#M#M#F-M#M#M#M#'),
        );

        foreach($inputs as $input) {
            list($expected, $value, $mask) = $input;
            $actual = Text::stripInputMask($value, $mask);
            $this->assertIdentical($expected, $actual);
        }
    }

    function testValidateInputMask() {
        // $inputs FORMAT: NUMBER_OF_ERRORS, INPUT_VALUE, MASK
        $inputs = array(
            // Valid combinations
            array(0, "Süpérbrôsé", 'ULLLLLLLLLLLLLLLLLLL'),
            array(0, "Ilık süt", 'ULLLLLLLF LLLLLL'),
            array(0, '123-45-6789', 'M#M#M#F-M#M#F-M#M#M#M#'),
            array(0, ' 123-45-6789 ', ' M#M#M#F-M#M#F-M#M#M#M#'),
            array(0, '+1 (111) 123-1111', 'F+F1F F(M#M#M#F)F M#M#M#F-M#M#M#M#'),
            array(0, '(123) 456-7890', 'F(M#M#M#F)F M#M#M#F-M#M#M#M#'),
            array(0, '12345-6789', 'M#M#M#M#M#F-M#M#M#M#'),
            array(0, 'A1B 2C3', 'ULM#ULF M#ULM#'),
            array(0, 'Max', 'ULMLML'),
            array(0,  9, 'M#'),
            array(0, '9', 'M#'),
            array(0, '#', 'F#'),
            array(0,  9, 'U#'),
            array(0,  9, 'L#'),
            array(0, '-', 'F-'),
            array(0, 'A', 'UA'),
            array(0, 'A', 'UL'),
            array(0, 'A', 'UC'),
            array(0, 'a', 'LA'),
            array(0, 'a', 'LL'),
            array(0, 'a', 'LC'),
            array(0, 'A', 'MA'),
            array(0, 'a', 'MA'),
            array(0, 'a', 'ML'),
            array(0, 'A', 'ML'),
            array(0, 'A', 'MC'),
            array(0, 'a', 'MC'),
            array(0, '*', 'MC'),
            array(0, '(',  'MC'),

            // 'Character \'X\' at position [Y] is not a lowercase letter'
            array(1, 'A', 'LA'),
            array(1, 'A', 'LL'),
            array(1, 'A', 'LC'),

            // 'Character \'X\' at position [Y] is not a letter'
            array(1, 'Ma8', 'ULMLML'),

            // The input is too long
            array(1, '(123) 456-7890a', 'F(M#M#M#F)F M#M#M#F-M#M#M#M#'),
            array(1, '12345-6789a', 'M#M#M#M#M#F-M#M#M#M#'),

            // 'Character \'X\' at position [Y] does not match expected formatting character \'Z\''
            array(1, '123+45-6789', 'M#M#M#F-M#M#F-M#M#M#M#'),
            array(1,  9, 'F#'),
            array(1, '(', 'F-'),

            // 'The input is too long'
            array(1, 'Maxine', 'ULMLML'),
            array(1, array(),  'MC'),

            // 'Character \'X\' at position [Y] is not an uppercase letter'
            array(1, 'A1b 2C3', 'ULM#ULF M#ULM#'),
            array(1, 'a', 'UA'),
            array(1, 'a', 'UL'),

            // 'Character \'X\' at position [Y] is not a number'
            array(1, 'Max', 'ULMLU#'),
            array(1, 'M', 'L#'),
            array(1, 'M', 'U#'),
            array(1, '12b-45-6789', 'M#M#M#F-M#M#F-M#M#M#M#'),
            array(1, 'A', 'M#'),

            // 'Mask must contain an even number of characters'
            array(1, 'M', 'U'),
            array(1, 'M', 'M#M'),
            array(1, 'M', ''),
            array(1, 'M', 9),
            array(1, 'M', null),
            array(1, 'M', array()),

            // 'Character \'X\' at position [Y] is not a letter'
            // 'The input is too short'
            array(2, 'Ma', 'ULMLML'),

            // 'Character \'X\' at position [Y] is not an uppercase letter'
            // 'The input is too short',
            array(2, '', 'UL'),
            array(2, null, 'UL'),

            // 'Character \'1\' at position [1] does not match expected formatting character \'(\'',
            // 'Character \' \' at position [4] is not a number',
            // 'Character \'4\' at position [5] does not match expected formatting character \')\'',
            // 'Character \'5\' at position [6] does not match expected formatting character \' \'',
            // 'Character \' \' at position [8] is not a number',
            // 'Character \'8\' at position [10] does not match expected formatting character \'-\'',
            // 'Character \'\' at position [13] is not a number',
            // 'The input is too short',
            array(8, '123 456 7890', 'F(M#M#M#F)F M#M#M#F-M#M#M#M#'),
        );

        foreach ($inputs as $triple) {
            list($expected, $value, $mask) = $triple;
            $actual = Text::validateInputMask($value, $mask);
            if (!is_array($actual) || $expected !== count($actual)) {
                $this->fail(sprintf("Expected: '%s' error(s) got: '%s' for value: '%s' mask: '%s' - %s",
                    var_export($expected, true),
                    count($actual),
                    var_export($value, true),
                    var_export($mask, true),
                    var_export($actual, true)
                ));
            }
        }
    }

    function testPrintText2Str() {
        $testString = "I've reset your GQ password to
op     N!Yi3<cM`?3hXy
<
>
asdf>
which will expire in one day.


which will expire in one day.

<div>div <b>stuff</b> to say</div>
<as-html>as-html <b>hmmm</b></as-html>

<as-html>as-html <:-)  <<<<<<<<< >>>>>>>>>>></as-html>
<div> <:-)  <<<<<<<<< >>>>>>>>>>></div>
<:-)  <<<<<<<<< >>>>>>>>>>>";
        $this->assertEqual($testString,
            Api::print_text2str($testString, 0));
        $this->assertEqual("I've reset your GQ password to<br />op     N!Yi3<cM`?3hXy<br /><<br />><br />asdf><br />which will expire in one day.<br /><br /><br />which will expire in one day.<br /><br /><div>div <b>stuff</b> to say</div><br /><as-html>as-html <b>hmmm</b></as-html><br /><br /><as-html>as-html <:-)  <<<<<<<<< >>>>>>>>>>></as-html><br /><div> <:-)  <<<<<<<<< >>>>>>>>>>></div><br /><:-)  <<<<<<<<< >>>>>>>>>>>",
            Api::print_text2str($testString, OPT_NL_EXPAND));
        $this->assertEqual("I've reset your GQ password to
op     N!Yi3&lt;cM`?3hXy
&lt;
&gt;
asdf&gt;
which will expire in one day.


which will expire in one day.

&lt;div&gt;div &lt;b&gt;stuff&lt;/b&gt; to say&lt;/div&gt;
&lt;as-html&gt;as-html &lt;b&gt;hmmm&lt;/b&gt;&lt;/as-html&gt;

&lt;as-html&gt;as-html &lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&lt;/as-html&gt;
&lt;div&gt; &lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&lt;/div&gt;
&lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;",
            Api::print_text2str($testString, OPT_ESCAPE_HTML));
        $this->assertEqual("I've reset your GQ password to
op     N!Yi3<cM`?3hXy
<
>
asdf>
which will expire in one day.


which will expire in one day.

<div>div <b>stuff</b> to say</div>
<as-html>as-html <b>hmmm</b></as-html>

<as-html>as-html <:-)  <<<<<<<<< >>>>>>>>>>></as-html>
<div> <:-)  <<<<<<<<< >>>>>>>>>>></div>
<:-)  <<<<<<<<< >>>>>>>>>>>",
            Api::print_text2str($testString, OPT_SPACE2NBSP));
        $this->assertEqual("I've reset your GQ password to
op     N!Yi3&lt;cM`?3hXy
&lt;
&gt;
asdf&gt;
which will expire in one day.


which will expire in one day.

<div>div <b>stuff</b> to say</div>
<as-html>as-html <b>hmmm</b></as-html>

<as-html>as-html &lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;</as-html>
<div> &lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;</div>
&lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;",
            Api::print_text2str($testString, OPT_ESCAPE_SCRIPT));
        $this->assertEqual("I've reset your GQ password to
op     N!Yi3<cM`?3hXy
<
>
asdf>
which will expire in one day.


which will expire in one day.

<div>div <b>stuff</b> to say</div>
<as-html>as-html <b>hmmm</b></as-html>

<as-html>as-html <:-)  <<<<<<<<< >>>>>>>>>>></as-html>
<div> <:-)  <<<<<<<<< >>>>>>>>>>></div>
<:-)  <<<<<<<<< >>>>>>>>>>>",
            Api::print_text2str($testString, OPT_SUPPORT_AS_HTML));
        $this->assertEqual("I've reset your GQ password to<br />op     N!Yi3&lt;cM`?3hXy<br />&lt;<br />&gt;<br />asdf&gt;<br />which will expire in one day.<br /><br /><br />which will expire in one day.<br /><br />&lt;div&gt;div &lt;b&gt;stuff&lt;/b&gt; to say&lt;/div&gt;<br /><as-html>as-html <b>hmmm</b></as-html><br /><br /><as-html>as-html &lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;</as-html><br />&lt;div&gt; &lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&lt;/div&gt;<br />&lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;",
            Api::print_text2str($testString, OPT_REF_TO_URL_PREVIEW|OPT_NL_EXPAND|OPT_HTTP_EXPAND|OPT_ESCAPE_HTML|OPT_SPACE2NBSP|OPT_ESCAPE_SCRIPT|OPT_SUPPORT_AS_HTML));
        $this->assertEqual("I've reset your GQ password to<br />op     N!Yi3&lt;cM`?3hXy<br />&lt;<br />&gt;<br />asdf&gt;<br />which will expire in one day.<br /><br /><br />which will expire in one day.<br /><br /><div>div <b>stuff</b> to say</div><br /><as-html>as-html <b>hmmm</b></as-html><br /><br /><as-html>as-html &lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;</as-html><br /><div> &lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;</div><br />&lt;:-)  &lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt; &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;",
            Api::print_text2str($testString, OPT_REF_TO_URL_PREVIEW|OPT_NL_EXPAND|OPT_HTTP_EXPAND|OPT_SPACE2NBSP|OPT_ESCAPE_SCRIPT|OPT_SUPPORT_AS_HTML));
    }

    public function testExtractCommaSeparatedID() {
        // Original type is preserved
        $this->assertIdentical(null, Text::extractCommaSeparatedID(null));
        $this->assertIdentical(127, Text::extractCommaSeparatedID(127));
        $this->assertIdentical(false, Text::extractCommaSeparatedID(false));
        $this->assertIdentical(true, Text::extractCommaSeparatedID(true));

        $this->assertIdentical('', Text::extractCommaSeparatedID(''));
        $this->assertIdentical('', Text::extractCommaSeparatedID(','));
        $this->assertIdentical(' ', Text::extractCommaSeparatedID(', '));
        $this->assertIdentical('bananas', Text::extractCommaSeparatedID('bananas'));
        $this->assertIdentical('1', Text::extractCommaSeparatedID('1'));
        $this->assertIdentical('3', Text::extractCommaSeparatedID('1,2,3'));
        $this->assertIdentical('  3', Text::extractCommaSeparatedID('1,2,  3'));
    }

    public function testgetRandomStringOnHttpsLogin() {
        $origSecEndUserHttps =\RightNow\UnitTest\Helper::getConfigValues(array("SEC_END_USER_HTTPS"))["SEC_END_USER_HTTPS"] ?: 0;
        try {
            \RightNow\UnitTest\Helper::setConfigValues(array("SEC_END_USER_HTTPS" => false), false);
            $this->assertNull(Text::getRandomStringOnHttpsLogin());
            $this->login();
            // if the test is being run over https, we won't get a null string back regardless of the config value
            if (\RightNow\Utils\Url::isRequestHttps()) {
                $this->assertNotNull(Text::getRandomStringOnHttpsLogin());
            } else {
                $this->assertNull(Text::getRandomStringOnHttpsLogin());
                \RightNow\UnitTest\Helper::setConfigValues(array("SEC_END_USER_HTTPS" => true), false);
                $this->assertNotNull(Text::getRandomStringOnHttpsLogin());
            }
            $this->logout();
        } finally {
            \RightNow\UnitTest\Helper::setConfigValues(array("SEC_END_USER_HTTPS" => $origSecEndUserHttps), false);
        }
    }

    public function testGenerateRandomString() {
        $this->assertNotNull(Text::generateRandomString());
        $this->assertNotEqual(Text::generateRandomString(),Text::generateRandomString());
        $this->assertNotEqual(Text::generateRandomString(),Text::generateRandomString(450));
        $this->assertNotEqual(Text::generateRandomString(400),Text::generateRandomString(500));
        $this->assertNotEqual(strlen(Text::generateRandomString(400)),strlen(Text::generateRandomString(500)));
        $this->assertEqual(strlen(Text::generateRandomString(600)),strlen(Text::generateRandomString(600)));
    }

    function testValidateDateRange() {
        $dateRange = Text::validateDateRange("01/01/2013|12/31/2014", "m/d/Y", "|", false, "90 days");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("01/01/2013|03/30/2013", "m/d/Y", "|", false, "90 days");
        $this->assertEqual("01/01/2013|03/30/2013", $dateRange);

        $dateRange = Text::validateDateRange("01/01/2013|12/31/2014", "m/d/Y", "|", false, "2 years");
        $this->assertEqual("01/01/2013|12/31/2014", $dateRange);

        $dateRange = Text::validateDateRange("01/01/2013|12/31/2014", "M/d/Y", "|", false, "2 years");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("01/01/201312/31/2014", "m/d/Y", "|", false, "2 years");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("asdasd|12/31/2014", "m/d/Y", "|", false, "2 years");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("12/31/2014|sadsd", "m/d/Y", "|", false, "2 years");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("2013-01-01|2014-12-31", "Y-m-d", "|", false, "2 years");
        $this->assertEqual("2013-01-01|2014-12-31", $dateRange);

        $dateRange = Text::validateDateRange("2013-01-01,12/31/2014", "Y-m-d", ",", false, "2 years");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("2013-01-01,invalid", "Y-m-d", "|", false, "2 years");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("01/01/2013|12/31/2014", "m/d/Y", "|", true, "2 years");
        $this->assertTrue(preg_match("/^[0-9]+\|[0-9]+$/", $dateRange) === 1);
        $dateRangeParts = explode("|", $dateRange);
        $this->assertEqual('01/01/2013 00:00:00', date('m/d/Y H:i:s',$dateRangeParts[0]));
        $this->assertEqual('01/01/2015 00:00:00', date('m/d/Y H:i:s',$dateRangeParts[1]));

        $dateRange = Text::validateDateRange("01/01/2014|12/31/2013", "m/d/Y", "|", false, "2 years");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("01/01/2013|12/31/2016", "m/d/Y", "|", false, "2 years");
        $this->assertNull($dateRange);

        $dateRange = Text::validateDateRange("01/01/2013|12/31/2014", "m/d/Y", "|", false, "2 years");
        $this->assertNotNull($dateRange);
    }
    
    function testGetDateFormatFromDateOrderConfig() {
        $dateFormat = Text::getDateFormatFromDateOrderConfig();
        $this->assertEqual("mm/dd/yyyy", $dateFormat["long"]);
        $this->assertEqual("m/d/Y", $dateFormat["short"]);
        $this->assertEqual("mm/dd/yyyy", $dateFormat["label"]);
        $this->assertEqual(2, $dateFormat["yearOrder"]);
        $this->assertEqual(0, $dateFormat["monthOrder"]);
        $this->assertEqual(1, $dateFormat["dayOrder"]);
    }

    function testGetDateUnitLabels() {
        $dateUnitLabels = Text::getDateUnitLabels();
        $this->assertTrue(count($dateUnitLabels) > 0);
        foreach ($dateUnitLabels as $key => $value) {
            $this->assertEqual($key, $value);
        }
    }
}
