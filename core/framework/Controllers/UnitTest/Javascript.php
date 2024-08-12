<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Utils\Text,
    RightNow\Utils\Url;

if (IS_HOSTED) {
    exit("Did we ship the unit tests?  That would be sub-optimal.");
}

class Javascript extends \RightNow\Controllers\Admin\Base {

    function __construct() {
        parent::__construct(true, '_phonyLogin');
        umask(0);
    }

    /**
     * Default function when one is not specified. Generates help documentation for JS unit tests
     */
    function index()
    {
        $frameworkFiles = $this->_getListOfTestFiles('framework');
        foreach($frameworkFiles as &$file)
            $file = "<a href=\"/ci/unitTest/javascript/framework/$file\"/>$file</a>";

        $adminFiles = $this->_getListOfTestFiles('admin');
        foreach($adminFiles as &$file)
            $file = "<a href=\"/ci/unitTest/javascript/$file\"/>$file</a>";

        $this->load->view('tests/frameworkJavascript', array(
            'methods'                 => $this->_joinIntoListHtml($this->_getAvailableMethods()),
            'availableFrameworkFiles' => $this->_joinIntoListHtml($frameworkFiles),
            'availableAdminFiles'     => $this->_joinIntoListHtml($adminFiles),
        ));
    }

    /**
     * Executes tests on framework JavaScript code. If a file is specified, it will only run
     * that test, otherwise, all tests files will be run.
     * @param string $inputFile [optional] Single test file to run. Argument should be name of file to execute minus the .test.js extension
     */
    function framework() {
        $this->_runTest(implode('/', func_get_args()), 'framework');
    }

    function admin() {
        $this->_runTest(implode('/', func_get_args()), 'admin');
    }

    private function _runTest($filePath, $group) {

        if ($group === 'framework') {
            $path = Url::getCoreAssetPath('debug-js');
        }
        else if ($group === 'admin') {
            $path = Url::getCoreAssetPath('admin/js');
        }

        $testFile = "$path/$filePath.test.js";
        if (!is_readable(HTMLROOT . $testFile)) {
            throw new \Exception("I can't read $testFile.");
        }

        $pageContent = '';
        if ($group === 'admin') {
            // Some admin tests require server-end components to render views
            // in order for the JS to function properly.
            // In this case, we simply call the designated controller and method.
            // The method should be returning the result of `parent::_render`.
            // When `$_ENV['rn_test_valid'] === '1'` the Admin Base controller
            // knows to just return the rendered view as a string.
            // If your test doesn't need a rendered view, then it shouldn't be named
            // (or exist inside a directory named) with the method name.
            $controller = ucfirst(Text::getSubstringBefore($filePath, "/tests/"));
            $file = CPCORE . "Controllers/Admin/{$controller}.php";
            if (\RightNow\Utils\FileSystem::isReadableFile($file)) {
                require_once $file;
                $className = "\\RightNow\\Controllers\\Admin\\{$controller}";
                $instance = new $className;
                if ($method = $this->getMethodName($filePath, $instance)) {
                    $_ENV['rn_test_valid'] = '1';
                    // Extract segments after the method and pass each one in as a param,
                    // as controller methods expect.
                    $pageContent = call_user_func_array(array($instance, $method),
                        array_filter(explode('/', Text::getSubstringAfter($filePath, $method))));
                }
            }
        }

        $siteBase = Url::convertInsecureUrlToNetworkPathReference(Url::getShortEufBaseUrl());
        $this->load->view('tests/testBoilerplates/core', array(
            'testFile'        => $testFile,
            'pageContent'     => $pageContent,
            'coreAssetPrefix' => Url::getCoreAssetPath(),
            'comboService'    => $siteBase . '/ci/cache/yuiCombo/',
            'group'           => $group
        ));
    }

    /**
     * Returns the method name as determined by $filePath and $instance.
     * @param string $filePath
     * @param Instance $instance
     * @return string|null
     */
    private function getMethodName($filePath, $instance) {
        $method = Text::getSubstringAfter($filePath, "/tests/");
        if (Text::stringContains($method, '/')) {
            $method = Text::getSubstringBefore($method, '/');
        }
        if (method_exists($instance, $method)) {
            return $method;
        }
        if (method_exists($instance, 'index')) {
            return 'index';
        }
    }

    /**
     * Returns the list of test files in the debug-js/test directory which have the .test.js extension. Also
     * optionally strips of the extension
     * @param boolean $removeExtension [optional] Denotes if the .test.js extension should be removed from the filename
     * @return Array The list of test files
     */
    private function _getListOfTestFiles($group) {
        $files = $filteredFiles = array();
        $coreAssetPrefix = Url::getCoreAssetPath();

        if ($group === 'framework') {
            $directoriesToLookIn = array(
                'core'                  => "{$coreAssetPrefix}debug-js",
                'modules/chat'          => "{$coreAssetPrefix}debug-js/modules/chat",
                'modules/widgetHelpers' => "{$coreAssetPrefix}debug-js/modules/widgetHelpers",
            );
        }
        else if ($group === 'admin') {
            $directoriesToLookIn = array(
                'admin' => "{$coreAssetPrefix}admin/js",
            );
        }

        foreach ($directoriesToLookIn as $name => $dir) {
            $files = array_keys(\RightNow\Utils\FileSystem::getDirectoryTree(HTMLROOT . "$dir", array('regex' => '/\.test\.js$/')));
            $files = array_filter($files, function($file) {
                return Text::endsWith($file, '.test.js') && !Text::beginsWith($file, '.');
            });

            foreach ($files as $index => $file) {
                $file = $files[$index] = Text::getSubstringBefore($file, '.test.js');

                if ($name !== 'core') {
                    $files[$index] = "$name/$file";
                }
            }

            $filteredFiles = array_merge($filteredFiles, $files);
        }

        return $filteredFiles;
    }

    private function _getAvailableMethods() {
        return array (
            '/ci/unitTest/javascript/<a href="/ci/unitTest/javascript/index">index</a>' => 'This help message.',
            '/ci/unitTest/javascript/framework[/fileToTest]' => 'Path to the core framework unit test to run. Path is relative to `cp/webfiles/core/debug-js`.',
            '/ci/unitTest/javascript/admin[/fileToTest]' => 'Path to the admin unit test to run. Path is relative to `cp/webfiles/admin/js`.',
        );
    }

    private function _joinIntoListHtml($array) {
        //If it's a numerically indexed array, return a <ul> list
        if($array[0]) {
            $html = "<ul>\n";
            foreach ($array as $definition) {
                $html .= "<li>$definition</li>\n";
            }
            $html .= "</ul>\n";
        }
        //Otherwise use a <dl> list
        else {
            $html = "<dl>\n";
            foreach ($array as $term => $definition) {
                $html .= "<dt>$term</dt>\n<dd>$definition</dd>\n";
            }
            $html .= "</dl>\n";
        }

        return $html;
    }

    protected function _phonyLogin() {
        // Yes, this should do nothing.
    }
}
