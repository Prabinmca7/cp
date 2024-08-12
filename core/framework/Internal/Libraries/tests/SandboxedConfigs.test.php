<?php
use RightNow\Internal\Libraries\SandboxedConfigs;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
require_once(CPCORE . 'Internal/Utils/Admin.php');

class SandboxedConfigsTest extends CPTestCase {
    private $modes;
    function __construct() {
        $this->base_work_dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
        umask(0);
        \RightNow\Utils\FileSystem::mkdirOrThrowExceptionOnFailure($this->base_work_dir, true);

        $this->modes = array_keys(\RightNow\Internal\Utils\Admin::getEnvironmentModes());
        if ($offendingKey = array_search('reference', $this->modes)) {
            unset($this->modes[$offendingKey]);
        }
        $this->configs = array('loginRequired');
        $this->defaultArray = array ('loginRequired' => 0);
        $this->directoryPaths = array(OPTIMIZED_FILES . 'production/optimized/config');
        $this->stagingExists = in_array('staging_01', $this->modes);
        if ($this->stagingExists) {
            $this->directoryPaths[] = OPTIMIZED_FILES . 'staging/staging_01/optimized/config';
        }
        $this->fileName = SandboxedConfigs::FILE_NAME;
    }

    function testConfigurations() {
        $configs = SandboxedConfigs::configurations();
        foreach($this->configs as $config) {
            $this->assertTrue(array_key_exists($config, $configs));
        }
    }

    function testConfigValues() {
        $configs = SandboxedConfigs::configValues();
        foreach($this->configs as $config) {
            $this->assertTrue(array_key_exists($config, $configs));
        }
    }

    function testConfigValue() {
        foreach($this->configs as $config) {
            $this->assertEqual(0, SandboxedConfigs::configValue($config));
        }

        try {
            $configValue = SandboxedConfigs::configValue('someInvalidConfig');
            $this->assertFalse(isset($configValue), 'exception not thrown');
        }
        catch (\Exception $e) {
            // expected
        }
    }

    function testIsValidConfig() {
        foreach($this->configs as $config) {
            $this->assertTrue(SandboxedConfigs::isValidConfig($config));
        }
        $this->assertFalse(SandboxedConfigs::isValidConfig('someInvalidConfig'));
    }

    function testModes() {
        $modes = SandboxedConfigs::modes();
        foreach($this->modes as $mode) {
            $this->assertTrue(array_key_exists($mode, $modes));
        }
    }

    function testConfigValueFromMode() {
        $productionFile = OPTIMIZED_FILES . 'production/optimized/config/' . SandboxedConfigs::FILE_NAME;

        foreach(array_keys(SandboxedConfigs::configurations()) as $config) {
            foreach(array_merge($this->modes, array(null)) as $mode) {
                $result = SandboxedConfigs::configValueFromMode($config, $mode);
                $this->assertIsA($result, 'array');
                $this->assertIdentical(false, $result[0]);
                if ($result[1] instanceof \RightNow\Internal\Libraries\ConfigFileException) {
                    // file doesn't exist yet: since many of the subsequent tests assume it
                    // exists, write it and continue onward
                    $this->assertFalse(\RightNow\Utils\FileSystem::isReadableFile($productionFile));
                    SandboxedConfigs::writeConfigArrayToFile(array('loginRequired' => 0), $productionFile);
                    break(2);
                }
                else {
                    $this->assertNull($result[1]);
                }
            }
        }

        if (\RightNow\Utils\FileSystem::isReadableFile($productionFile)) {
            // test missing sandboxedConfigs file
            rename($productionFile, "{$productionFile}.test");
            list($configValue, $exception) = SandboxedConfigs::configValueFromMode('loginRequired', 'production', false);
            rename("{$productionFile}.test", $productionFile);
            $this->assertTrue($exception instanceof \RightNow\Internal\Libraries\ConfigFileException);

            // test missing config in file
            $contents = file_get_contents($productionFile);
            file_put_contents($productionFile, "a:0:{}\n");
            list($configValue, $exception) = SandboxedConfigs::configValueFromMode('loginRequired', 'production', false);
            file_put_contents($productionFile, $contents);
            $this->assertTrue($exception instanceof \RightNow\Internal\Libraries\ConfigFileException);
        }

        // test invalid config
        try {
            SandboxedConfigs::configValueFromMode('someInvalidConfig', 'development', false);
            $this->fail('exception not thrown');
        }
        catch (\Exception $e) {
            $this->pass();
        }

        // test invalid mode
        try {
            SandboxedConfigs::configValueFromMode('core', 'someInvalidMode', false);
            $this->fail('exception not thrown');
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testWriteConfigArrayToFile() {
        $newConfigs = array('loginRequired' => 4);
        $noExistingFile = false;
        foreach($this->directoryPaths as $path) {
            try {
                $oldConfigs = SandboxedConfigs::configArrayFromFile($path);
            }
            catch (\RightNow\Internal\Libraries\ConfigFileException $e) {
                $oldConfigs = array('loginRequired' => 0);
            }
            SandboxedConfigs::writeConfigArrayToFile($newConfigs, $path);
            $fetchedConfigs = SandboxedConfigs::configArrayFromFile($path, false);
            SandboxedConfigs::writeConfigArrayToFile($oldConfigs, $path);
            $this->assertEqual($newConfigs, $fetchedConfigs);
            SandboxedConfigs::configArrayFromFile($path, false); // reset cache to original values
        }
    }

    function testFilePathFromMode() {
        $this->assertEqual(OPTIMIZED_FILES . "production/optimized/config/{$this->fileName}", SandboxedConfigs::filePathFromMode('production'));
        if ($this->stagingExists) {
            $this->assertEqual(OPTIMIZED_FILES . "staging/staging_01/optimized/config/{$this->fileName}", SandboxedConfigs::filePathFromMode('staging_01'));
        }
        foreach(array('development','someInvalidMode') as $mode) {
            try {
                $filePath = SandboxedConfigs::filePathFromMode($mode);
                $this->fail('exception not thrown');
            }
            catch (\Exception $e) {
                $this->pass();
            }
        }

    }

    function testConfigArrayFromMode() {
        $this->assertEqual($this->defaultArray, SandboxedConfigs::configArrayFromMode('production'));
        if ($this->stagingExists) {
            $this->assertEqual($this->defaultArray, SandboxedConfigs::configArrayFromMode('staging_01'));
        }
        foreach(array('development','someInvalidMode') as $mode) {
            try {
                $array = SandboxedConfigs::configArrayFromMode($mode);
                $this->fail('exception not thrown');
            }
            catch (\Exception $e) {
                $this->pass();
            }
        }
    }

    function testConfigArrayFromFile() {
        foreach($this->directoryPaths as $path) {
            $this->assertEqual($this->defaultArray, SandboxedConfigs::configArrayFromFile($path));
        }

        try {
            $array = SandboxedConfigs::configArrayFromFile('/some/invalid/path');
            $this->fail('exception not thrown');
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testConfigArrayFromDeprecatedFile() {
        $expected = array('loginRequired' => 0);
        $contents = "<?function getDeployedDeprecatedConfigValues(){\n return " . var_export($expected, true) . ";\n}";
        $path = "$this->base_work_dir/_deprecated.php";
        file_put_contents($path, $contents);
        $this->assertEqual($expected, SandboxedConfigs::configArrayFromFile(dirname($path)));
    }
}
