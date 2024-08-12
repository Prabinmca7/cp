<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Libraries\Rss,
    RightNow\Controllers\UnitTest,
    RightNow\Utils\Text,
    RightNow\Utils\Config;

class RssTest extends CPTestCase
{
    private $parsedData = array();
    private $parentTag = null;
    private $currentTag = null;
    private $currentData = '';
    private $tagCount = 0;
    public $testingClass = '\RightNow\Libraries\Rss';

    function testFeed()
    {
        // get the feed data
        $output = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getFeed");

        // attempt to parse it
        $parser = xml_parser_create();
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, "startElement", "endElement");
        xml_set_character_data_handler($parser, "characterData");

        if (!xml_parse($parser, $output, null))
        {
            $this->fail();
        }
        xml_parser_free($parser);

        // validate the format of the parsed output
        if (isset($this->parsedData['channel']))
        {
            // there should only be one set of channel data
            $this->assertIdentical(count($this->parsedData['channel']), 1);
            $channelData = $this->parsedData['channel'][0];

            $this->assertTrue(isset($channelData['title']));
            $this->assertTrue(isset($channelData['link']));
            $this->assertTrue(isset($channelData['description']));
            $this->assertTrue(isset($channelData['ttl']));

            $this->assertSame($channelData['title'], Config::getConfig(EU_SYNDICATION_TITLE));
            $this->assertSame($channelData['link'],
                \RightNow\Utils\Url::getShortEufAppUrl(false, Config::getConfig(CP_HOME_URL)));
            $this->assertSame($channelData['description'], Config::getConfig(EU_SYNDICATION_DESCRIPTION));
        }
        else
        {
            $this->fail();
        }

        if (isset($this->parsedData['item']) && is_array($this->parsedData['item']) && count($this->parsedData['item']) > 0)
        {
            foreach ($this->parsedData['item'] as $item)
            {
                // make sure each item has these values
                $this->assertTrue(isset($item['title']));
                $this->assertTrue(isset($item['link']));
                $this->assertTrue(isset($item['description']));
                $this->assertTrue(isset($item['pubdate']));
            }
        }
        else
        {
            $this->fail();
        }
    }

    //@@@ QA 130319-000086 Ensure answer links and titles are correctly parsed
    function testToRssXml(){
        $toXml = $this->getMethod('_toRssXml');
        $results = $toXml(array('data' => array(array(
              0 => 69,
              1 => 'Public answer with conditional sections',
              2 => '<a href=\'/app/answers/detail/a_id/69\'>69</a>',
              3 => 'Answer title',
              4 => 1330732342,
              5 => 1330732342,
        ))));

        $this->assertTrue(is_string($results));
        $this->assertTrue(Text::beginsWith($results, '<?xml version="1.0" encoding="utf-8"?>'));
        $this->assertTrue(Text::stringContains($results, '<title>Public answer with conditional sections</title>'));
        $this->assertTrue(Text::stringContains($results, '/app/answers/detail/a_id/69</link>'));
        $this->assertTrue(Text::stringContains($results, '<description>Answer title</description>'));

        $results = $toXml(array('data' => array(array(
              0 => 69,
              1 => 'Public & answer',
              2 => '<a href="/app/answers/detail/a_id/69">Answer ID 69</a>',
              3 => "Answer's title",
              4 => 1330732342,
              5 => 1330732342,
        ))));

        $this->assertTrue(Text::stringContains($results, '<title>Public &amp; answer</title>'));
        $this->assertTrue(Text::stringContains($results, '/app/answers/detail/a_id/69</link>'));
        $this->assertTrue(Text::stringContains($results, '<description>Answer&apos;s title</description>'));

        $results = $toXml(array('data' => array(array(
              0 => 69,
              1 => '',
              2 => '<a href="/foo/bar/baz">asdf</a>',
              3 => "",
              4 => 1330732342,
              5 => 1330732342,
        ))));
        $this->assertTrue(Text::stringContains($results, '<title/>'));
        $this->assertTrue(Text::stringContains($results, '/foo/bar/baz</link>'));
        $this->assertTrue(Text::stringContains($results, '<description/>'));
    }

    function getFeed()
    {
        $instance = new Rss();
        $this->dump($instance->feed());
    }

    function startElement($parser, $name, $attrs=array())
    {
        $name = strtolower($name);
        switch ($name)
        {
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

    function characterData($parser, $data)
    {
        if ($this->currentTag !== null)
            $this->currentData .= trim($data);
    }

    function endElement($parser, $name)
    {
        if ($this->currentTag !== null)
        {
            $this->parsedData[$this->parentTag][$this->tagCount][$this->currentTag] = $this->currentData;
        }
    }
}
