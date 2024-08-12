<?php

class DynamicJavaScriptTest extends CPTestCase
{
    public function testScript()
    {
        // TODO maybe once we're on CC, we can verify this with version numbers
        umask(0);

        $documentRoot = get_cfg_var('doc_root') . "/cp";
        $newJsonFilePath = "$documentRoot/generated/production/optimized/javascript/pages/testDynamicJavaScript.json";
        if (!file_exists(dirname($newJsonFilePath))) {
            mkdir(dirname($newJsonFilePath));
        }
        file_put_contents($newJsonFilePath,
                '[{"path":"\/euf\/core\/js\/min\/widgetHelpers\/EventProvider.js"},{"path":"\/euf\/core\/js\/min\/widgetHelpers\/Form.js"},'
                . '{"path":"\/euf\/core\/js\/min\/widgetHelpers\/Field.js"},{"type":"standard","path":"standard\/input\/SelectionInput","version":""},'
                . '{"type":"standard","path":"standard\/input\/DateInput","version":""},{"type":"standard","path":"standard\/input\/TextInput","version":""}]');
        ob_start(function ($buffer) { return null; });
        RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
        $content = ob_get_contents();
        ob_end_flush();

        $this->assertSame(1, preg_match("#^Starting script\\ncreating new file (.*/pages/testDynamicJavaScript.[a-f0-9]{32}.js)\\nFinished script\\n$#m", $content, $matches));
        $newJavaScriptFile = $matches[1];
        $this->assertTrue(file_exists($newJavaScriptFile));
        unlink($newJsonFilePath);
        unlink($newJavaScriptFile);

        list($result, $content) = $this->returnResultAndContent('getLatestVersion', 'foo', null);
        $this->assertNull($result);
        $this->assertIdentical('Invalid path foo', $content);

        list($result, $content) = $this->returnResultAndContent('getLatestVersion', "$documentRoot/core/framework/", null);
        $this->assertNull($result);
        $this->assertIdentical('Invalid $versionMajorMinor argument sent to getLatestVersion (NULL)', $content);

        list($result, $content) = $this->returnResultAndContent('getLatestVersion', "$documentRoot/core/framework/", '3.0');
        $this->assertNull($result);
        $this->assertIdentical('', $content);

        list($result, $content) = $this->returnResultAndContent('getLatestTimestamp', 'foo');
        $this->assertNull($result);
        $this->assertIdentical('Invalid path foo', $content);

        list($result, $content) = $this->returnResultAndContent('getLatestTimestamp', HTMLROOT . '/euf/generated/optimized/');
        $this->assertSame(1, preg_match('#^[0-9]{10}$#', $result));
        $this->assertIdentical('', $content);
    }
}
