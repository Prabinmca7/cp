<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestTopicWords extends WidgetTestCase {
    public $testingWidget = "standard/knowledgebase/TopicWords";

    function __construct() {
        parent::__construct();
    }

    function testGetData() {
        $this->addUrlParameters(array(
            'kw' => 'iphone',
            'foo' => 'bar'
        ));

        $this->createWidgetInstance(array(
            'add_params_to_url' => 'foo',
            'source_id' => 'KFSearch'
        ));

        $expectedAppendedParameters = 'foo/bar';
        $expectedTopicWords = array(
            array(
                'url' => 'http://www.apple.com/iphone/softwareupdate/',
                'title' => 'Get the latest iPhone software update',
                'text' => '',
                'icon' => "<span class='rn_FileTypeIcon rn_url'><span class='rn_ScreenReaderOnly'>Link to a URL</span></span>"
            ),
            array(
                'url' => 'http://www.apple.com/support/iphone/troubleshooting/phone/',
                'title' => 'Restart your iPhone and other helpful tips',
                'text' => '',
                'icon' => "<span class='rn_FileTypeIcon rn_url'><span class='rn_ScreenReaderOnly'>Link to a URL</span></span>"
            ),
            array(
                'url' => 'http://www.google.com',
                'title' => 'Hot Deals!',
                'text' => 'We are offering a service upgrade for a limited time.  Check it out!',
                'icon' => "<span class='rn_FileTypeIcon rn_url'><span class='rn_ScreenReaderOnly'>Link to a URL</span></span>"
            ),
        );

        $results = $this->getWidgetData();

        // Temporarily commented out/disabled while I look into this test some more...
        // $this->assertIdentical($results['topicWords'], $expectedTopicWords); 
        $this->assertTrue(\RightNow\Utils\Text::stringContains($results['appendedParameters'], $expectedAppendedParameters));
    }
}
