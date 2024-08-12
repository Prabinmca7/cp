<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Internal\Utils\Logs;

class LogsTest extends CPTestCase {
    function testGetDeployLogDataKeys() {
        $suffix = ': ';
        $dataKeys = Logs::getDeployLogDataKeys($suffix);
        $this->assertTrue(is_array($dataKeys));
        $expectedKeys = array(
          'date' => null,
          'ipAddress' => null,
          'userAgent' => null,
          'interfaceName' => null,
          'deployType' => null,
        );

        foreach($dataKeys as $key => $label) {
            $this->assertTrue(\RightNow\Utils\Text::endsWith($label, $suffix));
            $this->assertTrue(array_key_exists($key, $dataKeys));
            $this->assertNotNull($label);
            $dataKeys[$key] = true;
        }

        $this->assertFalse(in_array(null, $dataKeys));
    }

    function testGetDeployLogInfo() {
        $path = \RightNow\Api::cfg_path() . '/log/stage9876543210.log';
        $keys = array(
            'date' => '01/20/2011 09:40 AM',
            'name' => '(2) - Administrator LastName',
            'ip' => '172.22.7.238',
            'agent' => 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13',
            'interfaceName' => 'theInterface',
            'deployType' => 'Staging Deployment',
            'comment' => 'And here is a comment',
        );

        $contents = "[INFO]  01/20/2011 09:40 AM
            ----------------------------------------------
            CP Deployment Log - Prepare Deploy Operation
            Date: <rn_log_date>{$keys['date']}</rn_log_date>
            By Account: <rn_log_byAccount>{$keys['name']}</rn_log_byAccount>
            IP Address: <rn_log_ipAddress>{$keys['ip']}</rn_log_ipAddress>
            User Agent: <rn_log_userAgent>{$keys['agent']}</rn_log_userAgent>
            Interface Name: <rn_log_interfaceName>{$keys['interfaceName']}</rn_log_interfaceName>
            Deploy Type: <rn_log_deployType>{$keys['deployType']}</rn_log_deployType>
            Comment: <rn_log_comment>{$keys['comment']}</rn_log_comment>
            ----------------------------------------------


            [DEBUG] 01/20/2011 09:40 AM Preparing Staging Deployment operation
            [DEBUG] 01/20/2011 09:40 AM Validating files
            ";
        file_put_contents($path, $contents);
        $this->assertTrue(\RightNow\Utils\FileSystem::isReadableFile($path));
        $logInfo = Logs::getDeployLogInfo(basename($path));
        unlink($path);
        foreach ($keys as $key) {
            $this->assertEqual($keys[$key], $logInfo[$key]);
        }

        $multiLineComment = "And here
            is a multi-line
            comment";
        $contents = "[INFO]  01/20/2011 09:40 AM
            Comment: <rn_log_comment>$multiLineComment</rn_log_comment>
            ";
        file_put_contents($path, $contents);
        $logInfo = Logs::getDeployLogInfo(basename($path));
        unlink($path);
        $foo = $logInfo['comment'];
        $this->assertEqual($multiLineComment, $logInfo['comment']);

        $twoClosingTagsComment = "And here is a comment";
        $contents = "[INFO]  01/20/2011 09:40 AM
            Comment: <rn_log_comment>$twoClosingTagsComment</rn_log_comment>blah blah</rn_log_comment>
            ";
        file_put_contents($path, $contents);
        $logInfo = Logs::getDeployLogInfo(basename($path));
        unlink($path);
        $foo = $logInfo['comment'];
        $this->assertEqual($twoClosingTagsComment, $logInfo['comment']);

        $emptyComment = '';
        $contents = "[INFO]  01/20/2011 09:40 AM
            Comment: <rn_log_comment>$emptyComment</rn_log_comment>
            ";
        file_put_contents($path, $contents);
        $logInfo = Logs::getDeployLogInfo(basename($path));
        unlink($path);
        $foo = $logInfo['comment'];
        $this->assertEqual($emptyComment, $logInfo['comment']);
    }

    function testGetWebdavLogData() {
        $results = Logs::getWebdavLogData();
        $this->assertIsA($results, 'array');
        $this->assertTrue(array_key_exists('archived', $results));
        $this->assertTrue(array_key_exists('table', $results));
        $this->assertTrue(array_key_exists('columns', $results['table']));
        $this->assertTrue(array_key_exists('data', $results['table']));
    }

    function testGetDebugLogData() {
        $results = Logs::getDebugLogData();
        $this->assertIsA($results, 'array');
        $this->assertTrue(array_key_exists('table', $results));
        $this->assertTrue(array_key_exists('columns', $results['table']));
        $this->assertTrue(array_key_exists('data', $results['table']));
    }

    function testGetDeployLogData() {
        $results = Logs::getDeployLogData();
        $this->assertIsA($results, 'array');
        $this->assertTrue(array_key_exists('table', $results));
        $this->assertTrue(array_key_exists('columns', $results['table']));
        $this->assertTrue(array_key_exists('data', $results['table']));
    }

   function testGetWebDavLogDescriptionFor() {
       $this->assertEqual('Created', Logs::getWebDavLogDescriptionFor());
       $this->assertEqual('Created', Logs::getWebDavLogDescriptionFor(0));
       $this->assertEqual('Copied', Logs::getWebDavLogDescriptionFor(1));
   }
}
