<?
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Libraries\ThirdParty\MarkdownFilter;

class MarkdownFormatterTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\ThirdPary\MarkdownFilter';

    function testLinksAreNoFollowed () {
        $cases = array(
            array(
                '<http://placesheen.com/1/1>',
                '<a rel="nofollow" href="http://placesheen.com/1/1">http://placesheen.com/1/1</a>',
            ),
            array(
                '[happy](mailto:happy@people.co.uk)',
                '<a rel="nofollow" href="mailto:happy@people.co.uk">happy</a>',
            ),
            array(
                '[old](#haunts)',
                '<a rel="nofollow" href="#haunts">old</a>',
            ),
            array(
                '[new](https://placesheen.com/1/1)',
                '<a rel="nofollow" href="https://placesheen.com/1/1">new</a>',
            ),
            array(
                "[new][cities]\n\n[cities]: http://placesheen.com/1/1",
                '<a rel="nofollow" href="http://placesheen.com/1/1">new</a>',
            ),
        );

        foreach ($cases as $case) {
            list($input, $expected) = $case;
            $this->assertSame("<p>$expected</p>\n", MarkdownFilter::toHTML($input));
        }
    }

    function testNoMarkupOption () {
        $input = "<p>Mouth of the Cave.</p><pre>Belly of the Cavern</pre>";
        $expected = str_replace('<', '&lt;', $input);
        $this->assertSame("<p>$expected</p>\n", MarkdownFilter::toHTML($input));
    }

    function testEmptyElementSuffixOption () {
        $input = "****";
        $this->assertSame("<hr>\n", MarkdownFilter::toHTML($input));
    }
}
