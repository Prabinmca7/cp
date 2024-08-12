<?php

use RightNow\Connect\v1_4 as Connect;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestAnswerNotificationManager extends WidgetTestCase {
    public $testingWidget = "standard/notifications/AnswerNotificationManager";

    function testGetData() {
        // Add answer notifications and verify that the public ones are added to the view data
        $model = get_instance()->model('Notification');
        $this->logIn();
        $model->add('answer', 55); // Public
        $model->add('answer', 70); // Public

        $this->createWidgetInstance(array('url' => '/app/answers/detail'));
        $data = $this->getWidgetData();
        $viewData = $data['notifications'];

        $this->assertIdentical(count($viewData), 2);
        $this->assertIdentical($viewData[1]['url'], '/app/answers/detail/a_id/55' . \RightNow\Utils\Url::sessionParameter());
        $this->assertIdentical($viewData[1]['id'], 55);
        $this->assertIdentical($viewData[1]['summary'], 'iPhone not recognized in iTunes for Windows');
    }
}
