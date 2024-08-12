<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use \RightNow\Utils\Text;

class DocsTest extends CPTestCase 
{
    public $testingClass = 'RightNow\Controllers\Admin\Docs';

    function testGetFolders() 
    {
        $method = $this->getMethod('_getFolders');
        $standardResults = $method('standard');
        $customResults = $method('custom');
        $widgetResults = $method('standard/input');

        $this->checkFolderResults($standardResults);
        $this->checkFolderResults($customResults);
        $this->checkFolderResults($widgetResults);

        $this->assertTrue(count($standardResults) != count($customResults));
        $this->assertTrue(count($standardResults) != count($widgetResults));
        $this->assertTrue(count($customResults) != count($widgetResults));
    }

    private function checkFolderResults($results) 
    {
        if (!is_array($results) || count($results) <= 0) {
            $this->fail();
            return;
        }

        foreach ($results as $result) {
            if (!$result['path'] || !$result['name']) {
                $this->fail();
                continue;
            }

            if (Text::endsWith($result['path'], $result['name'])) {
                $this->pass();
            }
            else {
                $this->fail();
            }
        }
    }

}
