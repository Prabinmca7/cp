<?php

use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\UnitTest\Helper as TestHelper;
TestHelper::loadTestedFile(__FILE__);


class AdminBaseControllerTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Admin\Base';

    //@@@ QA 130417-000141
    function testGarnerResources() {
        require_once(CPCORE . 'Internal/Utils/Admin.php');

        $method = TestHelper::getMethodInvoker(
            $this->testingClass,
            '_garnerResources',
             array(true, '_verifyLoginWithCPEditPermission', false));

        $contains = function($string, $type, $expected) {
            $expected = $type === 'js' ? "<script src='$expected'></script>" : "<link rel='stylesheet' href='$expected'/>";
            return Text::stringContains($string, $expected);
        };

        $this->assertIdentical($method(array()), array(null, null));
        $this->assertIdentical($method(array(), true), array(null, null));

        $options = array(
            'js' => 'versions/widgets',
            'css' => 'versions/widgets',
        );
        $results = $method($options);
        $this->assertIsA($results, 'array');
        $this->assertEqual(2, count($results));
        $this->assertTrue($contains($results[0], 'js', 'admin/js/versions/widgets.js'));
        $this->assertTrue($contains($results[1], 'css', 'admin/css/versions/widgets.css'));

        $results = $method($options, true); // IS_HOSTED
        $this->assertIsA($results, 'array');
        $this->assertEqual(2, count($results));
        $this->assertTrue($contains($results[0], 'js', 'admin/js/versions/widgets.js'));
        $this->assertTrue($contains($results[1], 'css', 'admin/css/versions/widgets.css'));

        // js as an array, plus absolute path
        $options = array(
            'js' => array(Url::getCoreAssetPath('ejs/1.0/ejs-min.js'), 'deploy/filesTable'),
            'css' => 'deploy/deploy',
        );
        $results = $method($options);
        $this->assertIsA($results, 'array');
        $this->assertEqual(2, count($results));
        $this->assertTrue($contains($results[0], 'js', '/euf/core/ejs/1.0/ejs-min.js'));
        $this->assertTrue($contains($results[0], 'js', 'admin/js/deploy/filesTable.js'));
        $this->assertTrue($contains($results[1], 'css', 'admin/css/deploy/deploy.css'));

        $results = $method($options, true); // IS_HOSTED
        $this->assertIsA($results, 'array');
        $this->assertEqual(2, count($results));
        $this->assertTrue($contains($results[0], 'js', '/euf/core/ejs/1.0/ejs-min.js'));
        $this->assertTrue($contains($results[0], 'js', 'admin/js/deploy/filesTable.js'));
        $this->assertTrue($contains($results[1], 'css', 'admin/css/deploy/deploy.css'));

        // thirdParty
        $options = array(
            'js' => 'versions/widgets',
            'css' => array(
                'versions/widgets',
                Url::getCoreAssetPath('thirdParty/css/font-awesome.min.css'),
            ),
        );
        $results = $method($options);
        $this->assertIsA($results, 'array');
        $this->assertEqual(2, count($results));
        $this->assertTrue($contains($results[0], 'js', 'admin/js/versions/widgets.js'));
        $this->assertTrue($contains($results[1], 'css', '/euf/core/thirdParty/css/font-awesome.min.css'));

        $results = $method($options, true); // IS_HOSTED
        $this->assertIsA($results, 'array');
        $this->assertEqual(2, count($results));
        $this->assertTrue($contains($results[0], 'js', 'admin/js/versions/widgets.js'));
        $this->assertTrue($contains($results[1], 'css', '/euf/core/thirdParty/css/font-awesome.min.css'));

        $options = array(
            'js' => 'thirdParty/codemirror/lib/codemirror.js',
            'css' => 'thirdParty/codemirror/lib/codemirror'
        );
        $results = $method($options);
        $this->assertIsA($results, 'array');
        $this->assertEqual(2, count($results));
        $this->assertTrue($contains($results[0], 'js', 'thirdParty/codemirror/lib/codemirror.js'));
        $this->assertTrue($contains($results[1], 'css', 'thirdParty/codemirror/lib/codemirror'));

        $results = $method($options, true); // IS_HOSTED
        $this->assertIsA($results, 'array');
        $this->assertEqual(2, count($results));
        $this->assertTrue($contains($results[0], 'js', 'thirdParty/codemirror/lib/codemirror.js'));
        $this->assertTrue($contains($results[1], 'css', 'thirdParty/codemirror/lib/codemirror.css'));
    }
    
    function testVerifyPostCsrfToken(){
        $base = new \RightNow\Controllers\Admin\Base(true, "_verifyLoginWithCPEditPermission");
        $method = TestHelper::getMethodInvoker(
                        $this->testingClass, '_verifyPostCsrfToken', array(), $base);
        $this->assertTrue($method());

        $_SERVER["REQUEST_METHOD"] = 'POST';
        $this->assertFalse($method());
        $token = \RightNow\Utils\Framework::createAdminPageCsrfToken(0, 1, false);
        $_POST["formToken"] = $token;
        $this->assertFalse($method());
        $token = \RightNow\Utils\Framework::createAdminPageCsrfToken(0, 2, false);
        $_POST["formToken"] = $token;
        $this->assertTrue($method());
    }
}
