<?php

namespace RightNow\Internal\Libraries\Widget;

/**
 * A simple class to provide basic dependency information about a widget.
 */
final class DependencyInfo{
    public static $setCookie = true;
    private static $cookieName = 'version_testing';
    private static $filePrefix = 'dependencyInfoTest_';
    private static $fixturePath = 'Internal/Libraries/Widget/tests/fixtures/';

    /**
     * Set the cookie for a test
     * @param string $testName Name of the test file
     * @return string|bool The name of the test file if test name is valid, false otherwise
     */
    public static function setTest($testName)
    {
        if(!$dependencyTests = @json_decode(@file_get_contents(CPCORE . self::$fixturePath . $testName), true))
            return false;
        $testFile = CPCORE . 'Internal/Libraries/Widget/tests/' . self::$filePrefix . time() . rand() . ".json";

        if (self::$setCookie) {
            \RightNow\Utils\Framework::setCPCookie(self::$cookieName, $testFile, 0);
        }
        umask(0);
        file_put_contents($testFile, json_encode($dependencyTests));
        return $testFile;
    }

    /**
     * Remove all existing test files
     */
    public static function removeTests()
    {
        $testFiles = \RightNow\Utils\FileSystem::listDirectory(CPCORE . 'Internal/Libraries/Widget/tests/', true, false, array('match', '/' . self::$filePrefix . '.*\.json/'));
        foreach ($testFiles as $testFile)
            unlink($testFile);

        if (self::$setCookie) {
            \RightNow\Utils\Framework::destroyCookie(self::$cookieName);
        }
    }

    /**
     * Returns the test filename being currently used (blank if not set)
     * @return string Test file
     */
    public static function getTestFile()
    {
        if(isset($_COOKIE[self::$cookieName]) && ($testFile = $_COOKIE[self::$cookieName]) &&
            (\RightNow\Utils\FileSystem::isReadableFile($testFile)) &&
            ((\RightNow\Utils\Text::beginsWith($_SERVER['REQUEST_URI'], '/ci/admin/versions')) || IS_UNITTEST))
                return $testFile;
        return '';
    }

    /**
     * Returns an array containing all available test fixtures.
     * @return array Array keyed by file name whose value is the test's description
     */
    public static function getAllFixtures()
    {
        $files = array();
        $found = \RightNow\Utils\FileSystem::getDirectoryTree(CPCORE . self::$fixturePath, array('regex' => '/Test.*\.json$/'));

        foreach($found as $name => $time)
        {
            $contents = @json_decode(file_get_contents(CPCORE . self::$fixturePath . $name));
            $files[$name] = ($contents && $contents->description) ? $contents->description : "There's a problem with this file!";
        }

        ksort($files);
        return $files;
    }

    /**
    * Returns the mocked framework version.
    * @return string Mocked framework version or the real framework version if the fixture
    *   doesn't specify one
    */
    public static function getCXVersionNumber()
    {
        if (($dependencyInfo = self::loadTestDependencyInfo()) && ($version = $dependencyInfo['cxVersion']))
        {
            return $version;
        }
        return \RightNow\Internal\Utils\Version::getCXVersionNumber();
    }

    /**
     * Indicates whether testing mode is currently set
     * @return bool Whether or not testing mode is currently set
     */
    public static function isTesting()
    {
        $dependencyInfo = self::loadTestDependencyInfo();
        return $dependencyInfo !== null;
    }

    /**
     * Return the current set of dependency information
     * @return mixed Array Current state of dependency data or null if testing is not enabled
     */
    public static function loadTestDependencyInfo()
    {
        if(!$testFile = self::getTestFile())
            return null;
        return json_decode(file_get_contents($testFile), true);
    }

    /**
     * Set the current framework version in testing mode
     * @param array|null $selectedFrameworkVersion Framework version
     * @return bool Whether or not the widget versions were successfully set
     */
    public static function setCurrentFrameworkVersion($selectedFrameworkVersion)
    {
        $dependencyInfo = self::loadTestDependencyInfo();
        if(!$dependencyInfo)
            return false;
        $dependencyInfo['selectedFrameworkVersions']['Development'] = $selectedFrameworkVersion;
        return self::saveTestDependencyInfo($dependencyInfo);
    }

    /**
     * Set the current version for widgets in testing mode
     * @param array|null $selectedWidgetVersions Selected widget versions to set
     * @return bool Whether or not the widget versions were successfully set
     */
    public static function setCurrentWidgetVersions($selectedWidgetVersions)
    {
        $dependencyInfo = self::loadTestDependencyInfo();
        if(!$dependencyInfo)
            return false;
        $dependencyInfo['selectedWidgetVersions']['Development'] = $selectedWidgetVersions;
        return self::saveTestDependencyInfo($dependencyInfo);
    }

    /**
     * Overrides the versions specified in the passed-in framework modes and their versions.
     * @param array $declaredVersions Each mode ('Development', 'Production', etc.) and its version
     * @return array Overridden values from the text fixture or $declaredVersions untouched
     *  if not in testing mode
     */
    public static function overrideAllDeclaredFrameworkVersions(array $declaredVersions) {
        if (!$dependencyInfo = self::loadTestDependencyInfo()) return $declaredVersions;

        foreach ($dependencyInfo['selectedFrameworkVersions'] as $mode => $version) {
            $declaredVersions[$mode] = $version;
        }
        return $declaredVersions;
    }

    /**
     * Return the declared widget versions in each mode, including any test overrides
     * @param array $declaredVersions Currently declared widget versions in each mode
     * @return array Array of declared widget versions
     */
    public static function overrideAllDeclaredWidgetVersions(array $declaredVersions)
    {
        $dependencyInfo = self::loadTestDependencyInfo();
        if(!$dependencyInfo)
            return $declaredVersions;
        foreach(array_keys($dependencyInfo['selectedWidgetVersions']) as $mode)
        {
            $declaredVersions[$mode] = self::fillInBaseMockData($dependencyInfo['selectedWidgetVersions'][$mode], $declaredVersions[$mode]);

            foreach($dependencyInfo['selectedWidgetVersions'][$mode] as $widgetPath => $version)
            {
                $declaredVersions[$mode][$widgetPath] = $version;
            }
        }
        foreach($declaredVersions as $mode => $widgetVersions)
        {
            foreach($widgetVersions as $widgetPath => $version)
            {
                if($version === 'current')
                    $declaredVersions[$mode][$widgetPath] = '1.0';
            }
        }
        return $declaredVersions;
    }

    /**
     * Return $widgetInfo appended with any widget dependency test overrides (currently 'extends' and 'contains').
     * @param array $widgetInfo An array of all widgets and their associated version history.
     * @return array Array of widget data
     */
    public static function overrideAllWidgetDependencyInfo(array $widgetInfo)
    {
        if (!$dependencyInfo = self::loadTestDependencyInfo()) {
            return $widgetInfo;
        }

        $versionsSeen = array();
        foreach($dependencyInfo['widgetVersions'] as $widgetName => $versions) {
            if (!array_key_exists($widgetName, $widgetInfo)) {
                continue;
            }
            foreach ($versions as $threeDigitVersion => $data) {
                $digits = explode('.', $threeDigitVersion);
                $version = $digits[0] . '.' . $digits[1];
                if (!in_array($version, $versionsSeen)) {
                    $versionsSeen[] = $version;
                    if ($data['contains'] || $data['extends']) {
                        $newVersions = array();
                        foreach($widgetInfo[$widgetName]['versions'] as $versionArray) {
                            if ($version === $versionArray['version']) {
                                if ($data['extends']) {
                                    $versionArray['extends'] = $data['extends'];
                                }
                                if ($data['contains']) {
                                    $versionArray['contains'] = $data['contains'];
                                }
                            }
                            $newVersions[] = $versionArray;
                        }
                        $widgetInfo[$widgetName]['versions'] = $newVersions;
                    }
                }
            }
        }

        return $widgetInfo;
    }

    /**
     * Set any dependency-related info, if user is currently in a testing mode
     * @param string $widgetPath Relative path of the widget
     * @param array $widgetInfo Assumed to be the results from calling yaml_parse_file on a manifest file
     * @return array Array widget's manifest data
     */
    public static function overrideWidgetInfo($widgetPath, array $widgetInfo)
    {
        $dependencyInfo = self::loadTestDependencyInfo();
        if(!$dependencyInfo)
            return $widgetInfo;
        if((!$dependencyInfo['selectedWidgetVersions']['Development'][$widgetPath] && !$dependencyInfo['selectedWidgetVersions']['Development']['all'])
            || (!$dependencyInfo['widgetVersions'][$widgetPath] && !$dependencyInfo['widgetVersions']['all']))
            return $widgetInfo;

        $version = $dependencyInfo['selectedWidgetVersions']['Development'][$widgetPath];
        if (!$version)
        {
            $version = $dependencyInfo['selectedWidgetVersions']['Development']['all'];
        }
        $finalVerson = $version . '.0';

        $mockVersionInfo = $dependencyInfo['widgetVersions'][$widgetPath];
        if (!$mockVersionInfo)
        {
            $mockVersionInfo = $dependencyInfo['widgetVersions']['all'];
        }
        foreach (array_keys($mockVersionInfo) as $fullVersion)
        {
            if(\RightNow\Utils\Text::beginsWith($fullVersion, $version)
                && \RightNow\Internal\Utils\Version::compareVersionNumbers($fullVersion, $finalVersion) > 0)
                $finalVersion = $fullVersion;
        }
        $widgetInfo['version'] = $finalVersion;
        return $finalVersion ? ($mockVersionInfo[$finalVersion] + $widgetInfo) : $widgetInfo;
    }

    /**
     * Set any declared development widget versions, if user is currently in a testing mode
     * @param array $versions Current version history
     * @return array Array of modified declared development versions
     */
    public static function overrideDeclaredWidgetVersions(array $versions)
    {
        $dependencyInfo = self::loadTestDependencyInfo();
        if(!$dependencyInfo)
            return $versions;

        $versions = self::fillInBaseMockData($dependencyInfo['selectedWidgetVersions']['Development'], $versions);

        foreach($dependencyInfo['selectedWidgetVersions']['Development'] as $widgetPath => $version)
        {
            $versions[$widgetPath] = $version;
        }
        return $versions;
    }

    /**
     * Set any dependency-related info, if user is currently in a testing mode
     * @param array $versionHistory Current version history
     * @return array Array of modified version history
     */
    public static function overrideVersionHistory(array $versionHistory)
    {
        $dependencyInfo = self::loadTestDependencyInfo();
        if(!$dependencyInfo)
            return $versionHistory;

        $versionHistory['widgetVersions'] = self::fillInBaseMockData($dependencyInfo['widgetVersions'], $versionHistory['widgetVersions']);

        foreach(array_keys($dependencyInfo['widgetVersions']) as $widgetPath)
        {
            // Specific widget data to use
            foreach($dependencyInfo['widgetVersions'][$widgetPath] as $version => $versionData)
            {
                $versionHistory['widgetVersions'][$widgetPath][$version] = $versionData;
            }
        }
        foreach($dependencyInfo['frameworkVersions'] as $release => $frameworkVersion)
        {
            $versionHistory['frameworkVersions'][$release] = $frameworkVersion;
        }
        return $versionHistory;
    }

    /**
     * Returns a mocked changelog array
     * @param string $version Version to use
     * @return array
     */
    public static function getChangelogMockData($version)
    {
        return array(
          $version => array(
            'NEW_FEATURE' => array(
              array(
                'date' => '01/23/2013',
                'description' => 'Bacon ipsum dolor sit amet elit ea pork belly, commodo fugiat velit t-bone ribeye jerky meatball nisi prosciutto qui.',
                'details' => array('Corned beef adipisicing pig swine ut non deserunt esse aute dolore.'),
                'level' => 'nano',
              ),
              array(
                'date' => '01/24/2013',
                'description' => 'Culpa bresaola mollit do drumstick anim aliqua kielbasa deserunt.',
                'details' => array('Culpa jowl nisi, quis ut leberkas tail ribeye brisket ut ham.'),
                'level' => 'nano',
              ),
            ),
            'BUG_FIX' => array(
              array(
                'date' => '10/16/2012',
                'level' => 'major',
                'description' => 'Drumstick esse do non sed boudin chuck consequat venison pariatur ham short loin shank.',
              ),
              array(
                'date' => '01/23/2013',
                'description' => 'Sausage ex in prosciutto ut non qui short loin bresaola minim reprehenderit aliqua ball tip doner voluptate.',
                'details' => array('Anim meatball cow sirloin ad id turkey tongue elit.'),
                'level' => 'nano',
              ),
              array(
                'date' => '01/23/2013',
                'description' => 'Officia nisi tempor, salami ham beef ribs consequat corned beef id shank.',
                'details' => array('Shank rump jerky pastrami culpa capicola swine.'),
                'level' => 'nano',
              ),
            ),
            'API_CHANGE' => array(
              array(
                'date' => '01/23/2013',
                'description' => 'Consectetur beef ribs qui venison aute tri-tip magna deserunt in in velit irure drumstick pork loin.',
                'details' => array(
                    'T-bone eu in tail boudin ullamco, shankle filet mignon laborum cupidatat.',
                    'Est fatback shank ut, chicken tenderloin et eu ham irure minim enim.',
                ),
                'level' => 'nano',
              ),
            ),
            'DEPRECATION' => array(
              array(
                'date' => '01/23/2013',
                'description' => 'Chicken ut capicola salami aliqua jerky, in short ribs esse sirloin.',
                'details' => array('Aliqua filet mignon officia, pariatur salami biltong dolore labore cillum qui duis. Meatball consequat id ham, venison culpa labore nulla duis.'),
                'level' => 'nano',
              ),
            ),
          ),
        );
    }

    /**
     * Return the current set of dependency information
     * @param mixed $dependencyInfo Dependency information to save
     * @return bool Whether or not the dependency information was successfully saved
     */
    private static function saveTestDependencyInfo($dependencyInfo)
    {
        if(!($testFile = $_COOKIE[self::$cookieName])
            || !\RightNow\Utils\FileSystem::isReadableFile($testFile))
            return false;
        umask(0);
        return file_put_contents($testFile, json_encode($dependencyInfo)) !== false;
    }

    /**
     * If an 'all' key exists in the supplied mock data, its value is used for all
     * widgets in the supplied version history data.
     * @param array|null &$mockData Mock data pass-by-reference
     * @param array|null $versionHistory Version data pass-by-reference
     * @return array Also returns $versionHistory for unit testing purposes
     */
    private static function fillInBaseMockData(&$mockData, $versionHistory)
    {
        if (!is_array($versionHistory)) return array();

        if ($baseData = $mockData['all'])
        {
            // Base data to be used for every widget
            foreach ($versionHistory as $widgetPath => $info)
            {
                $versionHistory[$widgetPath] = $baseData;
            }
            unset($mockData['all']);
        }
        return $versionHistory;
    }
}
