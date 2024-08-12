<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text;

class OpensearchTest extends CPTestCase {

    public $testingClass = '\RightNow\Controllers\Opensearch';

    function testIndex() {
        $response = $this->makeRequest("/ci/opensearch");
        $this->assertTrue(Text::stringContains($response, "No function specified"));
    }

    function testFeed() {
        $response = $this->makeRequest("/ci/opensearch/feed");
        $this->assertTrue(Text::stringContains($response, "<title>OpenSearch RSS</title>"));
        $this->assertTrue(Text::stringContains($response, "Answer ID"));

        $response = $this->makeRequest("/ci/opensearch/feed/kw/iphone");
        $this->assertTrue(Text::stringContains($response, "<title>OpenSearch RSS</title>"));
        $this->assertTrue(Text::stringContains($response, "Answer ID"));
    }

    function testDesc() {
        $response = $this->makeRequest("/ci/opensearch/desc");
        $this->assertTrue(Text::stringContains($response, "xml"));
        $this->assertTrue(Text::stringContains($response, "OpenSearch RSS"));
    }

    //@@@ QA 130319-000086 Ensure answer links and titles are correctly parsed
    function testToRssXml(){
        list ($class, $toXml, $headers) = $this->reflect('method:_toRssXml', 'headers');
        $instance = $class->newInstance();
        $headers->setValue($instance, array(array(
            'heading' => 'link',
            'std' => true,
            ),
            array(
                'heading' => 'title',
                'std' => true,
            ),
            array(
                'heading' => 'pubDate',
                'std' => true,
            ),
            array(
                'heading' => 'score',
                'std' => true,
            ),
            array(
                'heading' => 'Answer ID',
            )
        ));

        $data = array('data'=>array(array(
                0 => '<a href=\'/app/answers/detail/a_id/52\'>52</a>',
                1 => 'Enabling MMS on iPhone 3G and iPhone 3GS',
                2 => 1269411000,
                3 => 100,
                4 => 52,
                )
            ),
            'per_page' => 10,
            'total_num' => 19,
            'start_num' => 1,
            'spelling' => '',
            'not_dict' => '',
            'ss_data' => NULL,
            'topic_words' => array(array(
                'url' => 'http://www.google.com',
                'title' => 'Hot Deals!',
                'text' => 'We are offering a service upgrade for a limited time.  Check it out!',
                'icon' => '<span class=\'rn_FileTypeIcon rn_url\'><span class=\'rn_ScreenReaderOnly\'>File Type url</span></span>',
            ))
        );

        $results = $toXml->invoke($instance, $data);

        $this->assertTrue(is_string($results));
        $this->assertTrue(Text::beginsWith($results, '<?xml version="1.0" encoding="utf-8"?>'));
        $this->assertTrue(Text::stringContains($results, '<opensearch:totalResults>19</opensearch:totalResults>'));
        $this->assertTrue(Text::stringContains($results, '<opensearch:startIndex>1</opensearch:startIndex>'));
        $this->assertTrue(Text::stringContains($results, '<opensearch:itemsPerPage>10</opensearch:itemsPerPage>'));
        $this->assertTrue(Text::stringContains($results, '<related:topic>'));
        $this->assertTrue(Text::stringContains($results, '<title>Hot Deals!</title>'));
        $this->assertTrue(Text::stringContains($results, '/app/answers/detail/a_id/52</link>'));
        $this->assertTrue(Text::stringContains($results, '<title>Enabling MMS on iPhone 3G and iPhone 3GS</title>'));
        $this->assertTrue(Text::stringContains($results, '<related:field name="Answer ID">52</related:field>'));

        $data = array('data'=>array(array(
                0 => '<a href="/foo/bar/baz">link</a>',
                1 => "Answer's summary & description",
                2 => 1269411000,
                3 => 100,
                4 => 1,
                )
            ),
            'per_page' => 2,
            'total_num' => 10,
            'start_num' => 9,
            'spelling' => 'stuff',
            'ss_data' => array('key' => 'value'),
        );

        $results = $toXml->invoke($instance, $data);

        $this->assertTrue(is_string($results));
        $this->assertTrue(Text::stringContains($results, '<opensearch:totalResults>10</opensearch:totalResults>'));
        $this->assertTrue(Text::stringContains($results, '<opensearch:startIndex>9</opensearch:startIndex>'));
        $this->assertTrue(Text::stringContains($results, '<opensearch:itemsPerPage>2</opensearch:itemsPerPage>'));
        $this->assertTrue(Text::stringContains($results, '<opensearch:Query role="correction" searchTerms="stuff"/>'));
        $this->assertTrue(Text::stringContains($results, '<opensearch:Query role="related" searchTerms="value"/>'));
        $this->assertTrue(Text::stringContains($results, '<related:field name="Answer ID">1</related:field>'));
        $this->assertTrue(Text::stringContains($results, '<opensearch:itemsPerPage>2</opensearch:itemsPerPage>'));
        $this->assertTrue(Text::stringContains($results, '/foo/bar/baz</link>'));
        $this->assertTrue(Text::stringContains($results, '<title>Answer&apos;s summary &amp; description</title>'));
    }
}

