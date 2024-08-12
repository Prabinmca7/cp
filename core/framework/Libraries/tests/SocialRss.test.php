<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Libraries\SocialRss,
    RightNow\Controllers\UnitTest,
    RightNow\Utils\Text,
    RightNow\Utils\Config;

class SocialRssTest extends CPTestCase {

    private $parsedData = array();
    private $parentTag = null;
    private $currentTag = null;
    private $currentData = '';
    private $tagCount = 0;
    public $testingClass = '\RightNow\Libraries\SocialRss';

    function testFeed () {
        // get the feed data
        $output = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getFeed");

        // attempt to parse it
        $parser = xml_parser_create();
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, "startElement", "endElement");
        xml_set_character_data_handler($parser, "characterData");

        if (!xml_parse($parser, $output, null)) {
            $this->fail();
        }
        xml_parser_free($parser);

        // validate the format of the parsed output
        if (isset($this->parsedData['channel'])) {
            // there should only be one set of channel data
            $this->assertIdentical(count($this->parsedData['channel']), 1);
            $channelData = $this->parsedData['channel'][0];

            $this->assertTrue(isset($channelData['title']));
            $this->assertTrue(isset($channelData['link']));
            $this->assertTrue(isset($channelData['description']));
            $this->assertTrue(isset($channelData['ttl']));

            $this->assertSame($channelData['title'], Config::getMessage(OPENSEARCH_RSS_SOCIAL_LBL));
            $this->assertSame($channelData['link'], \RightNow\Utils\Url::getShortEufAppUrl(false, Config::getConfig(CP_HOME_URL)));
            $this->assertSame($channelData['description'], Config::getMessage(RIGHTNOW_SESS_FEED_RSS_SOCIAL_QUESTIONS_LBL));
        }
        else {
            $this->fail();
        }

        if (isset($this->parsedData['item']) && is_array($this->parsedData['item']) && count($this->parsedData['item']) > 0) {
            foreach ($this->parsedData['item'] as $item) {
                // make sure each item has these values
                $this->assertTrue(isset($item['title']));
                $this->assertTrue(isset($item['link']));
                $this->assertTrue(isset($item['description']));
                $this->assertTrue(isset($item['pubdate']));
            }
        }
        else {
            $this->fail();
        }
    }

    function testToRssXml () {
        $toXml = $this->getMethod('_toRssXml');

        $results = $toXml(array('data' => array(array(
                    0 => 1,
                    1 => 'Is IPhone working?',
                    2 => '<a href=\'/app/social/questions/detail/qid/1\'>1</a>',
                    3 => 'My IPhone is not working after recent upgrade',
                    4 => 1330732342,
                    5 => 1330732342,
                    6 => 2,
                    7 => 'Robert',
                    9 => 'IPhone',
        ))));

        $this->assertTrue(is_string($results));
        $this->assertTrue(Text::beginsWith($results, '<?xml version="1.0" encoding="utf-8"?>'));
        $this->assertTrue(Text::stringContains($results, '<title>Is IPhone working?</title>'));
        $this->assertTrue(Text::stringContains($results, '/app/social/questions/detail/qid/1</link>'));
        $this->assertTrue(Text::stringContains($results, '<description>My IPhone is not working after recent upgrade</description>'));
        $this->assertTrue(Text::stringContains($results, '<author>Robert</author>'));
        $this->assertTrue(Text::stringContains($results, '<category>IPhone</category>'));
    }

    function getFeed () {
        $instance = new SocialRss();
        $this->dump($instance->feed());
    }

    function startElement ($parser, $name, $attrs = array()) {
        $name = strtolower($name);
        switch ($name) {
            case "rss":
                if (isset($attrs["VERSION"]) && $attrs["VERSION"] === "2.0")
                    $this->pass();
                else
                    $this->fail();
                break;
            case "channel":
            case "item":
                $this->parentTag = $name;
                $this->currentTag = null;

                $tagCount = count($this->parsedData[$name]);
                $this->tagCount = ($tagCount > 0) ? ++$tagCount : 0;
                break;
            default:
                $this->currentTag = $name;
                $this->currentData = '';
                break;
        }
    }

    function characterData ($parser, $data) {
        if ($this->currentTag !== null)
            $this->currentData .= trim($data);
    }

    function endElement ($parser, $name) {
        if ($this->currentTag !== null) {
            $this->parsedData[$this->parentTag][$this->tagCount][$this->currentTag] = $this->currentData;
        }
    }

}