<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Text;

class DeployerSlowTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Deployer';

    // TODO: test dev and staging pages as well.
    //     $host = "--header='Host: " . \RightNow\Utils\Config::getConfig(OE_WEB_SERVER, 'COMMON') . "'";          $host = "--header='Host: " . \RightNow\Utils\Config::getConfig(OE_WEB_SERVER, 'COMMON') . "'";
    //     $devCookie = "--header='Cookie: location=development~" . createCsrfToken(crc32("development"), 0) . "'";            $wgetCommand = "wget $host -q --output-document=- '$searchUrl' 2>&1";
    //     $wgetCommand = "wget $host $devCookie -q --output-document=- '$searchUrl' 2>&1";
    private $host, $fqdn, $validatedLinks;

    function __construct() {
        $this->host = \RightNow\Utils\Config::getConfig(OE_WEB_SERVER, 'COMMON');
        $this->fqdn = "http://{$this->host}";
        $this->validatedLinks = array();
    }

    function testOptimizedCssFilesForHardcodedTimestampPaths() {
        $paths = array(HTMLROOT . FileSystem::getOptimizedAssetsDir());
        if (($stagingPath = OPTIMIZED_FILES . 'staging/staging_01/optimized/') && FileSystem::isReadableDirectory($stagingPath)) {
            $paths[] = $stagingPath . FileSystem::getLastDeployTimestampFromDir($stagingPath);
        }

        foreach($paths as $path) {
            $timestamp = basename($path);
            foreach (FileSystem::listDirectory($path, true, true, array('match', '/^.+\.css$/')) as $cssFile) {
                $this->assertSame(0, preg_match('@/optimized/(\d){10}/@', file_get_contents($cssFile)), "A css file contains a hard-coded reference to optimized/<timestamp> : $cssFile");
            }
        }
    }

    function testOptimizedPagesForHardcodedTimestampPaths() {
        $paths = array(OPTIMIZED_FILES . 'production/optimized/views/headers');
        if (($stagingPath = OPTIMIZED_FILES . 'staging/staging_01/optimized/views/headers') && FileSystem::isReadableDirectory($stagingPath)) {
            $paths[] = $stagingPath;
        }
        foreach ($paths as $path) {
            foreach (FileSystem::listDirectory($path, true, true, array('match', '/^.+\.php$/')) as $page) {
                $this->assertSame(0, preg_match('@(/euf/core/.+/source/assets/)@', file_get_contents($page), $matches), "An optimized page/header contains a hard-coded reference to {$matches[1]} : $page");
            }
        }
    }

    function testLinks() {
        $skip = array(
            'answers/intent.php',
            'agent/guided_assistant.php',
        );
        $pages = FileSystem::getDirectoryTree(CUSTOMER_FILES . 'views/pages', 'php');
        $pages = array_filter(array_keys($pages), function($file) use ($skip) {
            // Skip any dirs, unit test rendering files, pages we've deliberately excluded
            return Text::endsWith($file, '.php') && !Text::beginsWith($file, 'unitTest') && !in_array($file, $skip);
        });

        $numberOfPages = count($pages);
        $counter = 0;

        foreach($pages as $page) {
            $counter++;
            $url = "{$this->fqdn}/app/" . (($page) ? substr($page, 0, -4) : '');
            print("Validating links [$counter of $numberOfPages] for page : $url<br/>");
            $this->validateLinks($this->getLinksFromUrl($url));
        }
    }

    // NOTE: this method commented out as a reminder to investigate using wget instead of curl for a possible performance gain.
    // So far, not able to get wget to fail on broken links in css files.
    //
    // Supporting method for testLinks()
    // function getHtmlFromUrlWget($url, $validateOnly = false) {
    //     $wgetCommand = "/usr/bin/wget --header='Host: {$this->host}' -q --output-document=- '$url' 2>&1";
    //     $handle = popen($wgetCommand, 'r');
    //     if ($validateOnly) {
    //         return fclose($handle);
    //     }

    //     $output = '';
    //     while (!feof($handle)){
    //         $output .= fread($handle, 512);
    //     }
    //     $rv = fclose($handle);
    //     return $output;
    // }

    // Supporting method for testLinks()
    function getHtmlFromUrl($url, $validateOnly = false) {
        //@@@ QA 130104-000076 Use API wrappers to load cURL
        if(!extension_loaded('curl')){
            \RightNow\Api::load_curl();
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ));
        $results = curl_exec($curl);
        if ($results === false || (($httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE)) >= 400)) {
            $errorCode = curl_errno($curl) ?: $httpCode;
            $msg = 'error_code: ' . $errorCode;
            if ($errorMessage = curl_error($curl)) {
                $msg .= ' message: ' . $errorMessage;
            }
            print("$msg<br/>");
            print("<font color='red'>$url</font><br/>");
            if($curl) {
                curl_close($curl);
                $curl = null;
            }
            if ($validateOnly) {
                return false;
            }
            $this->assertTrue(false, "$msg ($url)");
        }
        if($curl)
            curl_close($curl);
        return ($validateOnly) ? true : $results;
    }

    // Supporting method for testLinks()
    function validateLinks($links, $relativePathPrefix = null) {
        $rootDirs = array('/euf/', '/app/', '/ci/', '/rnt/');
        foreach($links as $link) {
            // TODO - hopefully we can trust ourselves later...
            if(Text::beginsWith($link, 'https://rnengage.qaload.lan/api/e/'))
                continue;
            if ($relativePathPrefix && (Text::beginsWith($link, '/../') || Text::beginsWith($link, '../'))) {
               $link = "$relativePathPrefix$link";
            }
            else if (array_filter($rootDirs, function($x) use ($link) {return Text::beginsWith($link, $x);})) {
               $link = "{$this->fqdn}$link";
            }

            if (in_array($link, $this->validatedLinks)) {
                continue;
            }
            $this->validatedLinks[] = $link;
            $this->assertTrue($this->isValidLink($link), "The link '" . var_export($link, true) . "' does not appear to be valid");
            if (Text::endsWith($link, '.css') && !Text::stringContains($link, 'rnt/rnw/yui')) {
                $this->validateLinks($this->getLinksFromUrl($link), dirname($link) . '/');
            }
        }
    }

    // Supporting method for testLinks()
    function isValidLink($link) {
        $skip = array(
            'javascript:void(0);',
            'javascript:void(0)',
            'javascript:window.scrollTo(0, 0)',
            'javascript:window.scrollTo(0, 0);',
            '#rn_MainContent',
            '#',
            'images/favicon.png',
            'adff', // ridiculously bad hack for IE in GuidedAssistant widget to make images within labels clickable
            'https://cloud.oracle.com/service',
        );

        return (in_array($link, $skip) || Text::beginsWith($link, 'data:image/png;base64,')) || $this->getHtmlFromUrl($link, true);
    }

    // Supporting method for testLinks()
    function getLinksFromUrl($url) {
        return $this->getLinksFromHtml($this->getHtmlFromUrl($url));
    }

    // Supporting method for testLinks()
    function getLinksFromHtml($html) {
        $pattern = '@href ?= ?(?:"|\')([^"\']+)(?:"|\')|url\((?:\'|")?([^)\'"]+)(?:\'|")?\)@';
        preg_match_all($pattern, $html, $matches);
        return array_filter(array_merge($matches[1], $matches[2]));
    }

    function testPopulateWidgetArray() {
        $invoke = $this->getMethod('populateWidgetArray');

        $widget = 'standard/search/CombinedSearchResults';
        $result = $invoke(Registry::getWidgetPathInfo($widget));
        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('meta', $result));
        $this->assertTrue(array_key_exists('header', $result));
        $this->assertTrue(array_key_exists('controller_path', $result['meta']));
        $this->assertTrue(array_key_exists('controller_code', $result));
        $this->assertTrue(array_key_exists('view_code', $result));
        $this->assertTrue(array_key_exists('extends_php', $result['meta']));
        $this->assertFalse(array_key_exists('extends_php', $result));

        $widget = 'standard/utils/Blank';
        $result = $invoke(Registry::getWidgetPathInfo($widget));
        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('meta', $result));
        $this->assertTrue(array_key_exists('header', $result));
        $this->assertTrue(array_key_exists('controller_path', $result['meta']));
        $this->assertTrue(array_key_exists('controller_code', $result));
        $this->assertTrue(array_key_exists('view_code', $result));
        $this->assertFalse(array_key_exists('extends_php', $result['meta']));
        $this->assertFalse(array_key_exists('extends_php', $result));
    }

    function testGenerateWidgetViewCode() {
        $invoke = $this->getMethod('generateWidgetViewCode');

        $widget = 'standard/utils/Blank';
        $result = $invoke(Registry::getWidgetPathInfo($widget));
        $this->assertStringContains($result, 'function _standard_utils_Blank_header()');
        $this->assertStringContains($result, 'class Blank extends \RightNow\Libraries\Widget\Base');

        $widget = 'standard/search/CombinedSearchResults';
        $result = $invoke(Registry::getWidgetPathInfo($widget));
        $this->assertStringContains($result, 'function _standard_search_CombinedSearchResults_header()', "Generated code for $widget does not contain a _header function");
        $this->assertStringContains($result, 'class CombinedSearchResults extends Multiline', "Generated code for $widget does not contain a class definition");
    }

    function testCheckAndStripWidgetController(){
        $checkAndStripWidgetController = $this->getMethod('checkAndStripWidgetController');
        $widgetPathInfo = Registry::getWidgetPathInfo('standard/utils/Blank');

        $originalBlankWidgetController = file_get_contents($widgetPathInfo->controller);

        $blankWidgetController = str_replace('class Blank extends \RightNow\Libraries\Widget\Base', 'class Blank extends \RightNow\Libraries\Widget\Base \\comments here', $originalBlankWidgetController);
        file_put_contents($widgetPathInfo->controller, $blankWidgetController);
        $modifiedControllerContent = $checkAndStripWidgetController($widgetPathInfo);
        $this->assertTrue(strlen($modifiedControllerContent) > 0);
        $this->assertFalse(Text::stringContains($modifiedControllerContent, '<?php'));
        $this->assertFalse(Text::stringContains($modifiedControllerContent, '?>'));

        $blankWidgetController = str_replace('class Blank extends \RightNow\Libraries\Widget\Base', 'class Blank extends \RightNow\Libraries\Widget\Base /*this style of comments*/', $originalBlankWidgetController);
        file_put_contents($widgetPathInfo->controller, $blankWidgetController);
        $modifiedControllerContent = $checkAndStripWidgetController($widgetPathInfo);
        $this->assertTrue(strlen($modifiedControllerContent) > 0);
        $this->assertFalse(Text::stringContains($modifiedControllerContent, '<?php'));
        $this->assertFalse(Text::stringContains($modifiedControllerContent, '?>'));

        file_put_contents($widgetPathInfo->controller, $originalBlankWidgetController);
    }

    function testGetWidgetClassDeclarationPattern(){
        $getWidgetClassDeclarationPattern = $this->getMethod('getWidgetClassDeclarationPattern');

        $this->assertIdentical("/class\s+Blank\s+extends\s+([A-Za-z0-9\\\\_]+)[^{]*{/i", $getWidgetClassDeclarationPattern('Blank'));
        $this->assertIdentical("/class\s+BLanK\s+extends\s+([A-Za-z0-9\\\\_]+)[^{]*{/i", $getWidgetClassDeclarationPattern('BLanK'));
        $this->assertIdentical("/class\s+****\s+extends\s+([A-Za-z0-9\\\\_]+)[^{]*{/i", $getWidgetClassDeclarationPattern('****'));
    }

    function getMethod($methodName, $static = false, $instance = null) {
        return parent::getMethod($methodName,
            array(new \RightNow\Internal\Libraries\BasicDeployOptions(get_instance()->_getAgentAccount())),
            $instance);
    }
}
