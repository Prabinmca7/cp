<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use \RightNow\Utils\Text;

class ExplorerTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Admin\Explorer';

    function testQuery() {
        $url = "/ci/admin/explorer/query/?query=select%20*%20from%20Incident%20where%20incident.id%20%3E%2010%20LIMIT%2025&limit=25&page=0";

        $result = $this->makeRequest($url, array('admin' => true));
        $decoded = json_decode(trim(trim($result, ')'), '('));

        $this->assertTrue($decoded->total > 0);
        $this->assertSame('Incident', $decoded->objectName);
        $this->assertSame("select * from Incident where incident.id > 10 LIMIT 25", $decoded->query);
        $this->assertTrue(count($decoded->columns) > 0);
    }

    function testXssQuery() {
        $url = "/ci/admin/explorer/query/?query=%26lt%3Bimg%20src%3D%22%22%20onerror%3D%22alert(1)%3B%22%26gt%3B&limit=25&page=0&callback=YUI.Env.DataSource.callbacks.yui_3_17_2_4_1444892757834_746";

        $result = $this->makeRequest($url, array('admin' => true));
        preg_match('/^.*\((.*)\)/', $result, $matches);
        $decoded = json_decode($matches[1]);

        $this->assertNotNull($decoded->error);
        $this->assertSame("Invalid query: ''", $decoded->error);
    }

    function testSanitizeContent() {
      $sanitizeContent = $this->getMethod('sanitizeContent');
      $result = $sanitizeContent(array('+test'));
      $this->assertSame(array('\'+test'), $result);
    }
}
