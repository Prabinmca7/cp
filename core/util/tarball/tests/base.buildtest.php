<?php

use RightNow\Utils\FileSystem;

require_once(DOCROOT . '/cp/core/util/tarball/Versioning.php');

/**
 * A collection of tests to verify the CP build (rake all) process.
 */
class buildTest extends CPTestCase {
    function __construct() {
        $this->target = VALIDATE_BUILD_CP_PATH; // This is set from the ValidateBuild controller
        // Example
        // $this->target = '/home/{user}/tmp/cp'

        $this->versions = yaml_parse_file("{$this->target}/versions/.versions");
        // Example
        // $this->versions = array (
        //   'rnw-12-11-fixes' => array ('cpVersion' => '3.0.1'),
        //   ...
        // )

        $history = yaml_parse_file("{$this->target}/core/cpHistory");
        $frameworkVersions = $history['frameworkVersions'];
        // Example
        // $frameworkVersions = array (
        //   '12.11' => '3.0.1',
        //   '13.2' => '3.0.2',
        // ...
        // )

        $this->cpVersion = end($frameworkVersions);
        // Example
        //$this->cpVersion = '3.2.6'

        $matches = glob("{$this->target}/build/cp-{$this->cpVersion}_linux_mysql_build_*");
        $this->buildDir = $matches[0];
        // Example
        // $this->buildDir = '/home/{user}/tmp/cp/build/cp-3.2.6_linux_mysql_build_10001h'

        $this->cpDir = "{$this->buildDir}/cgi-bin/rightnow.cfg/scripts/cp";
        // Example
        // $this->cpDir = '/home/{user}/tmp/cp/build/cp-3.4.1_linux_mysql_build_123h/cgi-bin/rightnow.cfg/scripts/cp'

        $this->cxVersion = key($frameworkVersions);
        // Example
        // $this->cxVersion = '15.5'

        $this->coreVersions = Versioning::getHighestNanoVersions();
        // Example
        // $this->coreVersions = array (
        //   'framework' => array('3.0.2', '3.1.3', '3.2.6'),
        //   'standard/knowledgebase/RelatedAnswers' => array('1.0.1', '1.1.1'),
        //   ...
        // )
    }

    function assertPathExists($pathOrPaths, $type = 'dir', $orNot = false) {
        $paths = is_string($pathOrPaths) ? array($pathOrPaths) : $pathOrPaths;
        if ($orNot) {
            $assertion = 'assertFalse';
            $errorMessage = "Path does exist: '%s'";
        }
        else {
            $assertion = 'assertTrue';
            $errorMessage = "Path does not exist: '%s'";
        }
        foreach($paths as $path) {
            $exists = ($type === 'dir') ? FileSystem::isReadableDirectory($path) : FileSystem::isReadableFile($path);
            $this->$assertion($exists, sprintf($errorMessage, $path));
        }
    }

    function getMajorMinor($version) {
        list($major, $minor) = explode('.', $version);
        return "{$major}.{$minor}";
    }

    function testKeyDirectoriesExist() {
        $this->assertPathExists(array(
            $this->target,
            "{$this->target}/versions",
            "{$this->target}/build",
        ));
    }

    function testVersionDirectories() {
        $this->assertIsA($this->versions, 'array');
        foreach($this->versions as $version => $versionInfo) {
            $this->assertPathExists("{$this->target}/versions/{$version}");
        }
    }

    function testBuildDirectories() {
        $dirs = array($this->buildDir);
        $files = array();
        $notDirs = array();
        $notFiles = array();
        $frameworkVersions = $this->coreVersions['framework'];
        $ci3Versions = array('3.10.1', '3.10.2', '3.10.3', '3.11.1', '3.11.2');
        $this->assertIsA($frameworkVersions, 'array');

        $cpDir = $this->cpDir;

        foreach($frameworkVersions as $version) {
            $dirs[] = "$cpDir/core/framework/$version";
            $dirs[] = "$cpDir/src/core/framework/$version";

            if ($version >= '3.2.7' || in_array($version, $ci3Versions)) {
                $dirs[] = "{$this->buildDir}/doc_root/euf/core/" . $this->getMajorMinor($version);
            }
            else {
                 $dirs[] = "{$this->buildDir}/doc_root/euf/core/$version";
            }

            $dirs[]  = "{$this->buildDir}/doc_root/euf/core/static";
            $files[] = "{$this->buildDir}/doc_root/euf/core/static/RightNow.Compatibility.MarketingFeedback.js";

            $files[]    = "$cpDir/src/core/framework/$version/widgetVersions";
            $notFiles[] = "$cpDir/core/framework/$version/widgetVersions";

            $dirs[]    = "$cpDir/src/core/framework/$version/views";
            $notDirs[] = "$cpDir/src/core/framework/$version/Views";

            $notDirs[] = "$cpDir/core/framework/$version/views";
            $dirs[]    = "$cpDir/core/framework/$version/Views";

            $dirs[]    = "$cpDir/src/core/framework/$version/views/pages";
            $dirs[]    = "$cpDir/src/core/framework/$version/views/templates";
            $dirs[]    = "$cpDir/src/core/framework/$version/views/admin";

            $dirs[]     = "$cpDir/core/widgets/standard/input/DateInput";
            $notFiles[] = "$cpDir/core/widgets/standard/input/DateInput/info.yml";
            $dirs[]     = "$cpDir/core/widgets/standard/input/FormInput";
            $notFiles[] = "$cpDir/core/widgets/standard/input/FormInput/info.yml";
            $dirs[]     = "$cpDir/src/core/widgets/standard/input/DateInput";
            $notFiles[] = "$cpDir/src/core/widgets/standard/input/DateInput/info.yml";
            $dirs[]     = "$cpDir/src/core/widgets/standard/input/FormInput";
            $notFiles[] = "$cpDir/src/core/widgets/standard/input/FormInput/info.yml";

            $dirs[]    = "$cpDir/src/core/framework/$version/views/pages/mobile";
            $files[]    = "$cpDir/src/core/framework/$version/views/templates/mobile.php";
            if ($version !== '3.0.2') {
                $dirs[]    = "$cpDir/src/core/framework/$version/views/pages/basic";
                $files[]    = "$cpDir/src/core/framework/$version/views/templates/basic.php";
            }
        }

        $notDirs[] = "$cpDir/customer/development/views/pages/mobile";
        $notFiles[] = "$cpDir/customer/development/views/templates/mobile.php";
        $notDirs[] = "$cpDir/customer/development/views/pages/basic";
        $notFiles[] = "$cpDir/customer/development/views/templates/basic.php";

        $this->assertPathExists($dirs);
        $this->assertPathExists($files, 'file');
        $this->assertPathExists($notDirs, 'dir', true);
        $this->assertPathExists($notFiles, 'file', true);
    }

    function testThemePackageSourceDirectoriesExist() {
        $cpDir = $this->cpDir;

        foreach($this->coreVersions['standard/input/TextInput'] as $widgetVersion) {
            $this->assertPathExists("$cpDir/src/core/widgets/standard/input/TextInput/$widgetVersion/themesPackageSource");
        }

        foreach($this->coreVersions['standard/input/BasicDateInput'] as $widgetVersion) {
            $this->assertPathExists("$cpDir/src/core/widgets/standard/input/BasicDateInput/$widgetVersion/themesPackageSource");
        }
    }

    function testScssAssetsExists() {
        $buildDir = $this->buildDir;

        $versions = array();
        foreach($this->coreVersions['framework'] as $version) {
            $versions []= $this->getMajorMinor($version);
        }

        // versions 3.0 to 3.2 (the first three values of $versions) don't need these tests
        $versions = array_slice($versions, 3);

        foreach($versions as $frameworkVersion) {
            foreach (array('standard', 'mobile') as $theme) {
                $versionPath = "$buildDir/doc_root/euf/core/$frameworkVersion/default/themes/$theme";

                $this->assertPathExists("$versionPath/site.css", 'file');
                $this->assertPathExists("$versionPath/okcs.css", 'file');
                $this->assertPathExists("$versionPath/scss");
                $this->assertPathExists("$versionPath/scss/font-awesome");
                $this->assertPathExists("$versionPath/scss/font-awesome/_variables.scss", 'file');

                $this->assertPathExists("$versionPath/scss");
                $this->assertPathExists("$versionPath/scss/bitters");
                $this->assertPathExists("$versionPath/scss/bourbon");
                $this->assertPathExists("$versionPath/scss/neat");
                $this->assertPathExists("$versionPath/bitters", 'dir', true);
                $this->assertPathExists("$versionPath/bourbon", 'dir', true);
                $this->assertPathExists("$versionPath/neat", 'dir', true);

                // we missed adding the license in CPv3.3, so skipping the checks for license.txt files
                if ($frameworkVersion !== '3.3') {
                    $this->assertPathExists("$versionPath/scss/bitters/license.txt", 'file');
                    $this->assertPathExists("$versionPath/scss/bourbon/license.txt", 'file');
                    $this->assertPathExists("$versionPath/scss/neat/license.txt", 'file');

                }

                $variablesScssContent = file_get_contents("$versionPath/scss/font-awesome/_variables.scss");
                $this->assertTrue(\RightNow\Utils\Text::stringContains($variablesScssContent, '$fa-font-path:'));
            }
        }
    }

    function testScssCustomerAssetsExists() {
        $buildDir = $this->buildDir;
        foreach (array('standard', 'mobile') as $theme) {
            $assetPath = "$buildDir/doc_root/euf/assets/themes/$theme";

            $this->assertPathExists("$assetPath/scss");
            $this->assertPathExists("$assetPath/scss/bitters");
            $this->assertPathExists("$assetPath/scss/bitters/license.txt", 'file');
            $this->assertPathExists("$assetPath/scss/bourbon");
            $this->assertPathExists("$assetPath/scss/bourbon/license.txt", 'file');
            $this->assertPathExists("$assetPath/scss/neat");
            $this->assertPathExists("$assetPath/scss/neat/license.txt", 'file');
            $this->assertPathExists("$assetPath/bitters", 'dir', true);
            $this->assertPathExists("$assetPath/bourbon", 'dir', true);
            $this->assertPathExists("$assetPath/neat", 'dir', true);
        }
    }

    function testFontAwesomePathInCss() {
        $siteCSS = "{$this->buildDir}/doc_root/euf/assets/themes/standard/site.css";
        $fp = fopen($siteCSS, 'r');
        $expected = '/euf/core/' . $this->getMajorMinor($this->cpVersion) . '/thirdParty/fonts/fontawesome';
        $pathsWereFound = false;
        while (($line = fgets($fp)) !== false) {
            if (\RightNow\Utils\Text::stringContains($line, 'thirdParty/fonts/fontawesome')) {
                $pathsWereFound = true;
                $this->assertStringContains($line, $expected);
            }
        }
        fclose($fp);
        $this->assertTrue($pathsWereFound);
    }
}
