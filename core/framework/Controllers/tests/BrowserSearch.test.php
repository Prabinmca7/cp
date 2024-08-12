<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class BrowserSearchTest extends CPTestCase
{
    function testBrowserSearch()
    {
        $outputXML = '';

        // the feed function of the browserSearch controller expects four
        //  parameters: url, title, description, and image path. image path
        //  is optional. 
        //
        $url = \RightNow\Utils\Url::getOriginalUrl(false);
        $title = 'some kind O string with G@|2B|3 --++==)(!@#$%^&*';
        $description = 'your description goes here';
        $image = 'http://something.invalid/image-5.gif';

        $searchUrl = sprintf("/ci/browserSearch/desc/%s/%s/%s/%s",
            urlencode($url),
            urlencode($title),
            urlencode($description),
            urlencode($image)
        );
        $outputXML = \RightNow\UnitTest\Helper::makeRequest($searchUrl);
        $this->assertNotEqual(strlen($outputXML), 0, "Output XML is empty!");

        $stringsToCheck = array(
            '<OpenSearchDescription xmlns="http://a9.com',
            '<ShortName>' . $title . '</ShortName>',
            '<Description>' . $description . '</Description>',
            '<Language>' . LANG_DIR . '</Language>',
            '<SyndicationRight>limited</SyndicationRight>',
            '<OutputEncoding>UTF-8</OutputEncoding>',
            '<InputEncoding>UTF-8</InputEncoding>',
            '<Url template="' . $url . '" type="text/html" method="get"/>'
        );

        foreach($stringsToCheck as $stringToCheck)
        {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($outputXML, $stringToCheck), "Output XML does not contain [" . $stringToCheck . "]" );
        }
    }
}


