<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect;
class SurveyTest extends CPTestCase {
    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\Survey();
    }
    
    function testValidBuildSurveyURL() {
        $slink = $this->model->buildSurveyURL(1);
        $this->assertEqual("/ci/documents/detail/5/1/12/d20f5cee1379622717570b0dd5ba13012e07435c", $slink);

        $slink = $this->model->buildSurveyURL(4);
        $this->assertEqual("/ci/documents/detail/5/4/12/ef711c3c152a653de162256e41b2a2312e1d5f67", $slink);

        $slink = $this->model->buildSurveyURL(5);
        $this->assertEqual("/ci/documents/detail/5/5/12/0364af5d0b33420eaedbf19cd07417819701fdd6", $slink);

    }
}
