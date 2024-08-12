<?php

require_once CORE_WIDGET_FILES . 'standard/searchsource/SourceResultListing/viewHelper.php';

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestSocialResultListingHelper extends CPTestCase {
    public $testingWidget = "RightNow/Helpers/SocialResultListingHelper";

    function __construct($label = null) {
        parent::__construct($label);
        $this->helper = new \RightNow\Helpers\SocialResultListingHelper;
    }

    function testGetMetadataElements() {
    	$commentCount = array(
                    'elementName' => 'commentCount',
                    'labelForSingleElement' => COMMENT_LC_LBL,
                    'labelForMultipleElements' => COMMENTS_LC_LBL
                );
    	$bestAnswers = array(
                    'elementName' => 'bestAnswerCount',
                    'labelForSingleElement' => BEST_ANS_LBL,
                    'labelForMultipleElements' => BEST_ANSWERS_LC_LBL
                );
    	$this->assertSame(array($commentCount,$bestAnswers), $this->helper->getMetadataElements(array('comment_count','best_answers')));
    	$this->assertSame(array($bestAnswers,$commentCount), $this->helper->getMetadataElements(array('best_answers','comment_count')));
    	$this->assertSame(array($commentCount), $this->helper->getMetadataElements(array('comment_count')));
    	$this->assertSame(array($bestAnswers), $this->helper->getMetadataElements(array('best_answers')));
    }
}