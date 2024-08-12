<?php

use RightNow\Libraries\Widget\Helper,
    RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class WidgetBaseHelperTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\Widget\Helper';

    function testEscape () {
        $helper = new Helper;
        $this->assertIdentical('aSdf', $helper->escape('aSdf'));
        $this->assertIdentical('%2f', $helper->escape('%2f'));
        $this->assertIdentical('test@example.com', $helper->escape('test@example.com'));
        $this->assertIdentical('!@#$%^*()_-+=|\{[]}', $helper->escape('!@#$%^*()_-+=|\{[]}'));

        $this->assertIdentical('&amp;', $helper->escape('&'));
        $this->assertIdentical('&gt;', $helper->escape('>'));
        $this->assertIdentical('&lt;', $helper->escape('<'));
        $this->assertIdentical('&quot;', $helper->escape('"'));
        $this->assertIdentical('&#039;', $helper->escape("'"));
        $this->assertIdentical('', $helper->escape(""));

        $this->assertNull($helper->escape(null));
        $this->assertFalse($helper->escape(false));
        $this->assertIdentical(array(), $helper->escape(array()));
        $this->assertIdentical((object)array(), (object)$helper->escape(array()));
        $this->assertIdentical(1, $helper->escape(1));

        //Double encoding tests
        $this->assertIdentical('&amp;amp;', $helper->escape('&amp;'));
        $this->assertIdentical('&amp;amp;', $helper->escape('&amp;', true));
        $this->assertIdentical('&amp;amp;', $helper->escape('&amp;', 1));
        $this->assertIdentical('&amp;', $helper->escape('&amp;', false));
        $this->assertIdentical('&amp;lt;', $helper->escape('&lt;', true));
        $this->assertIdentical('&lt;', $helper->escape('&lt;', false));
        $this->assertIdentical('&amp;gt;', $helper->escape('&gt;', true));
        $this->assertIdentical('&gt;', $helper->escape('&gt;', false));
    }

    function testAppendSession () {
        $helper = new Helper;
        $sessionData = $helper->appendSession('');

        if($sessionData !== '') {
            $this->assertPattern('/(\/session\/[a-zA-Z0-9]+(=)*)?/', $sessionData);
        }
        else {
            $this->assertIdentical('', $sessionData);
        }

        $this->assertTrue(Text::beginsWith($helper->appendSession('bananas'), 'bananas'));

        $session = new \ReflectionClass('\RightNow\Libraries\Session');
        $canSetSessionCookie = $session->getProperty('canSetSessionCookie');
        $canSetSessionCookie->setAccessible(true);
        $canSetSessionCookieValue = $canSetSessionCookie->getValue($this->CI->session);
        $canSetSessionCookie->setValue($this->CI->session, false);

        $rnow = new \ReflectionClass('Rnow');
        $isSpider = $rnow->getProperty('isSpider');
        $isSpider->setAccessible(true);
        $isSpiderValue = $isSpider->getValue($this->CI->rnow);
        $isSpider->setValue($this->CI->rnow, false);

        $this->assertPattern('/bananas\/session\/[a-zA-Z0-9]+(=)*/', $helper->appendSession('bananas'));

        $canSetSessionCookie->setValue($this->CI->session, $canSetSessionCookieValue);
        $isSpider->setValue($this->CI->session, $isSpiderValue);
    }
}
