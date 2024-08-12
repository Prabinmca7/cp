<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestOkcsAnswerNotificationList extends WidgetTestCase
{
    public $testingWidget = "standard/okcs/OkcsAnswerNotificationManager";

    function testGetTableFields() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance(array('display_fields' => 'answerID'));
        $getTableFields = $this->getWidgetMethod('getTableFields');
        $tableFields = $getTableFields();
        $expected = array(
            'name'          => 'answerID',
            'label'         => 'answerID',
            'columnID'      => 0
        );
        $this->assertIdentical($tableFields[0], $expected);
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
