<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestTwitterPosts extends WidgetTestCase {
    public $testingWidget = "standard/utils/TwitterPosts";
    
    function testGetData() {
        $this->createWidgetInstance(array('fetch_tweets_using' => 'account', 'twitter_account' => 'OracleServCloud'));
        $data = $this->getWidgetData();

        $this->assertIdentical($data['twitter_link'], 'https://twitter.com/OracleServCloud');
        
        $instance = $this->createWidgetInstance(array('fetch_tweets_using' => 'hashtag', 'twitter_hashtag' => 'OracleServCloud', 'twitter_widget_id' => '813415919850385408'));
        $data = $this->getWidgetData($instance);
        $this->assertIdentical($data['twitter_link'], 'https://twitter.com/hashtag/OracleServCloud');
        
        $this->createWidgetInstance(array('fetch_tweets_using' => 'hashtag', 'twitter_hashtag' => 'OracleServCloud'));
        $getData = $this->getWidgetMethod('getData');
        list($result, $content) = $this->returnResultAndContent($getData);
        
        $this->assertTrue(RightNow\Utils\Text::stringContains($content, 'Widget Error: standard/utils/TwitterPosts - Twitter widget ID is required'));
    }
}
