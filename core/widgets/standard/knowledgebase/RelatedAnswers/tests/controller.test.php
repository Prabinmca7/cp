<?php
use RightNow\Connect\v1_4 as Connect;
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);


class TestRelatedAnswers extends WidgetTestCase
{
    public $testingWidget = "standard/knowledgebase/RelatedAnswers";

    function testGetData()
    {
        $this->createWidgetInstance();

        ob_start();
        $this->getWidgetData();
        $error = ob_get_contents();
        ob_end_clean();

        $this->addUrlParameters(array("a_id" => 48));
        $data = $this->getWidgetData();
        $this->restoreUrlParameters();
        $this->assertEqual(gettype($data['relatedAnswers']),"object");
        $this->assertEqual(get_class($data['relatedAnswers']),"RightNow\\Connect\\Knowledge\\v1\\SummaryContentArray");
        $this->assertEqual(get_class($data['relatedAnswers'][0]),"RightNow\\Connect\\Knowledge\\v1\\AnswerSummaryContent");
        $this->assertEqual($data['relatedAnswers'][0]->ID,49);
        $oldSummary = $data['relatedAnswers'][0]->Title;

        $this->addUrlParameters(array("a_id" => 48 , "kw" => "ribs"));
        $data = $this->getWidgetData();

        $answer = Connect\Answer::fetch(49);
        $answer->Summary="Bacon ipsum dolor sit amet leberkas pork prosciutto pork loin brisket biltong pig. Prosciutto tail beef ball tip, tongue beef ribs pork loin sausage chicken. Ham bresaola doner ball tip tenderloin chuck fatback";
        $answer->save();
        Connect\ConnectAPI::commit();

        $data = $this->getWidgetData();
        $this->assertEqual($data['relatedAnswers'][0]->Title,"Bacon ipsum dolor sit amet leberkas pork prosciutto pork loin brisket biltong pig. Prosciutto tail beef ball tip, tongue beef ribs pork loin sausage chicken. Ham bresaola doner ball tip tenderloin chuck fatback");

        $this->restoreUrlParameters();

        $answer = Connect\Answer::fetch(49);
        $answer->Summary = $oldSummary;
        $answer->save();
        Connect\ConnectAPI::commit();
    }

    function testGetDataBad()
    {
        $widgetInstance = $this->createWidgetInstance();

        $this->addUrlParameters(array("a_id" => 1));
        $this->assertFalse($widgetInstance->getData());
        $this->restoreUrlParameters();

        $this->addUrlParameters(array("a_id" => 48));
        $this->assertNull($widgetInstance->getData());
        $this->restoreUrlParameters();

        $this->addUrlParameters(array("a_id" => 123));
        $this->assertFalse($widgetInstance->getData());
        $this->restoreUrlParameters();
    }
}
