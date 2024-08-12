<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Sql\Document as Sql;

class DocumentSqlTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Sql\Document';

    //@@@ 140801-000090 : Security Issues
    function testSqlInjection()
    {
        $methodInvoker = $this->getMethod('getFlowInfoFromDatabase');
        $result = $methodInvoker(null, null, null, 'shortcut\' OR \'1\' = \'1', null, null);
        $this->assertTrue($result[0] === null);
    }

    //@@@ 150323-000020 : Survey : Back button does not work when mutiple times back button tried
    function testSurveyNavigation()
    {
        $methodInvoker = $this->getMethod('getFlowInfoFromDatabase');
        $result = $methodInvoker(6, false, true, 'Survey_6_Webpage_2_Doc_38', 11, null);
        $this->assertTrue($result[8] === 'Survey_6_Webpage_2_Doc_38');
    }
}
