<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

Use \RightNow\UnitTest\Helper,
    RightNow\Utils\Config,
    RightNow\Internal\Api\Utils;
class UtilsApiTest extends CPTestCase {
    
    public $testingClass = 'RightNow\Internal\Api\Utils';

    function testGetSHA2Hash() {
        $method = $this->getMethod('getSHA2Hash');
        $this->assertIdentical($method('abc.jpg12345table'), 'aea01dec7da53fe359c07a891b872fd4bc54d81b1672fa53489135e3a4332803');
    }

    public function testEscapeHtml(){
        $this->assertIdentical('aSdf', Utils::escapeHtml('aSdf'));
        $this->assertIdentical('%2f', Utils::escapeHtml('%2f'));
        $this->assertIdentical('test@example.com', Utils::escapeHtml('test@example.com'));
        $this->assertIdentical('!@#$%^*()_-+=|\{[]}', Utils::escapeHtml('!@#$%^*()_-+=|\{[]}'));

        $this->assertIdentical('&amp;', Utils::escapeHtml('&'));
        $this->assertIdentical('&gt;', Utils::escapeHtml('>'));
        $this->assertIdentical('&lt;', Utils::escapeHtml('<'));
        $this->assertIdentical('&quot;', Utils::escapeHtml('"'));
        $this->assertIdentical('&#039;', Utils::escapeHtml("'"));
        $this->assertIdentical('', Utils::escapeHtml(""));

        $this->assertNull(Utils::escapeHtml(null));
        $this->assertFalse(Utils::escapeHtml(false));
        $this->assertIdentical(array(), Utils::escapeHtml(array()));
        $this->assertIdentical((object)array(), (object)Utils::escapeHtml(array()));
        $this->assertIdentical(1, Utils::escapeHtml(1));

        //Double encoding tests
        $this->assertIdentical('&amp;amp;', Utils::escapeHtml('&amp;'));
        $this->assertIdentical('&amp;amp;', Utils::escapeHtml('&amp;', true));
        $this->assertIdentical('&amp;amp;', Utils::escapeHtml('&amp;', 1));
        $this->assertIdentical('&amp;', Utils::escapeHtml('&amp;', false));
        $this->assertIdentical('&amp;lt;', Utils::escapeHtml('&lt;', true));
        $this->assertIdentical('&lt;', Utils::escapeHtml('&lt;', false));
        $this->assertIdentical('&amp;gt;', Utils::escapeHtml('&gt;', true));
        $this->assertIdentical('&gt;', Utils::escapeHtml('&gt;', false));
    }

    function testDefaultAnswerUrl() {
        $expected = '/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/1';

        $this->assertStringContains(Utils::defaultAnswerUrl(1), $expected);

        $url = \RightNow\Utils\Url::addParameter(Utils::defaultAnswerUrl(1), 'kw', 'phone');
        $this->assertStringContains($url, "/kw/phone");
        $this->restoreUrlParameters();
    }
}
