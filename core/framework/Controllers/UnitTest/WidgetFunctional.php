<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Utils\Text;

require_once __DIR__ . '/PhpFunctional.php';

if (IS_HOSTED) {
    exit('Did we ship the unit tests?  That would be sub-optimal.');
}

class WidgetFunctional extends PhpFunctional {
    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');

        $this->baseUri = "{$_SERVER['SERVER_NAME']}/ci/unitTest/widgetFunctional";
        $this->basePath = "widgets/";

        if (!array_key_exists('cp_session', $_COOKIE)) {
            // Set temporary login cookie if not being run from a browser, such as when run in Cruise Control environment.
            $_COOKIE = array('cp_login_start' => 1);
        }

        $this->testHelper = new \RightNow\UnitTest\Helper;
    }

    public function index() {
        $this->load->view('tests/widgetFunctional', array(
            'dirs' => $this->_getWidgetDirs(),
        ));
    }

    /**
     * Run all controller.test.php tests, each as its own suite.
     */
    public function all() {
        $this->_requireSimpleTestFiles();

        $testFiles = $this->testHelper->getTestFiles('test', $this->basePath);

        $this->load->view('tests/testBoilerplates/allPhpFunctional', array(
            'type'  => 'widget',
            'tests' => $testFiles,
            'total' => count($testFiles),
        ));
    }

    protected function _requireSimpleTestFiles() {
        parent::_requireSimpleTestFiles();
        require_once(__DIR__ . '/classes/WidgetTestCase.php');
    }

    protected function _getWidgetDirs() {
        $files = array();
        $basePath = \RightNow\Internal\Libraries\Widget\Registry::getBaseStandardWidgetPath() . 'standard/';

        foreach (\RightNow\Utils\FileSystem::getSortedListOfDirectoryEntries($basePath) as $category) {
            $files[$category] = array();
            // Get all widget dirs within each widget category; omit dot files.
            foreach(\RightNow\Utils\FileSystem::getSortedListOfDirectoryEntries("$basePath/$category", null, array('not match', '/^\..+$/')) as $file) {
                if (\RightNow\Utils\Filesystem::isReadableFile("$basePath/$category/$file/tests/controller.test.php"))
                    $files[$category][]= $file;
            }

            if (count($files[$category]) === 0)
                unset($files[$category]);
        }
        return $files;
    }
}
