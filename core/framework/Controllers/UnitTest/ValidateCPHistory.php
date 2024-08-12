<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Utils\Text,
    RightNow\Internal\Utils\Version;

if (IS_HOSTED) {
    exit("Did we ship the unit tests?  That would be sub-optimal.");
}

/**
* Controller endpoint to validate cpHistory by comparing widget info.yml files in each release to the contents of cpHistory.
*/
final class ValidateCPHistory extends \RightNow\Controllers\Admin\Base {
    private $documentRoot;
    private $errorMessages = array();

    private $widgetVersionBackportWhitelist = array(
        array("path" => "standard/utils/ContactUs", "version" => "1.0.1"),
    );

    function __construct() {
        parent::__construct(true, '_phonyLogin');

        $this->documentRoot = DOCROOT . "/cp";
    }

    /**
     * Only controller end-point to hit
     */
    public function index() {
        $dirs = array(
            array("12.11", "versions/rnw-12-11-phpupgrade/core/", "framework/manifest", "version", "widgets/"),
            array("13.2",  "versions/rnw-13-2-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("13.5",  "versions/rnw-13-5-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("13.8",  "versions/rnw-13-8-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("13.11", "versions/rnw-13-11-phpupgrade/core/", "framework/manifest", "version", "widgets/"),
            array("14.2",  "versions/rnw-14-2-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("14.5",  "versions/rnw-14-5-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("14.8",  "versions/rnw-14-8-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("14.11", "versions/rnw-14-11-phpupgrade/core/", "framework/manifest", "version", "widgets/"),
            array("15.2",  "versions/rnw-15-2-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("15.5",  "versions/rnw-15-5-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("15.8",  "versions/rnw-15-8-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("15.11", "versions/rnw-15-11-phpupgrade/core/", "framework/manifest", "version", "widgets/"),
            array("16.2",  "versions/rnw-16-2-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("16.5",  "versions/rnw-16-5-phpupgrade/core/",  "framework/manifest", "version", "widgets/"),
            array("16.8",  "versions/rnw-16-8-released/core/",    "framework/manifest", "version", "widgets/"),
            array("16.11", "versions/rnw-16-11-released/core/",   "framework/manifest", "version", "widgets/"),
            array("17.2",  "versions/rnw-17-2-released/core/",    "framework/manifest", "version", "widgets/"),
            array("17.5",  "versions/rnw-17-5-released/core/",    "framework/manifest", "version", "widgets/"),
            array("17.8",  "versions/rnw-17-8-released/core/",    "framework/manifest", "version", "widgets/"),
            array("17.11", "versions/rnw-17-11-released/core/",   "framework/manifest", "version", "widgets/"),
            array("18.2",  "versions/rnw-18-2-released/core/",    "framework/manifest", "version", "widgets/"),
            array("18.5",  "versions/rnw-18-5-released/core/",    "framework/manifest", "version", "widgets/"),
            array("18.8",  "versions/rnw-18-8-released/core/",    "framework/manifest", "version", "widgets/"),
            array("18.11", "versions/rnw-18-11-released/core/",   "framework/manifest", "version", "widgets/"),
            array("19.2",  "versions/rnw-19-2-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("19.5",  "versions/rnw-19-5-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("19.8",  "versions/rnw-19-8-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("19.11", "versions/rnw-19-11-fixes/core/",      "framework/manifest", "version", "widgets/"),
            array("20.2",  "versions/rnw-20-2-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("20.5",  "versions/rnw-20-5-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("20.8",  "versions/rnw-20-8-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("20.11", "versions/rnw-20-11-fixes/core/",      "framework/manifest", "version", "widgets/"),
            array("21.2",  "versions/rnw-21-2-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("21.5",  "versions/rnw-21-5-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("21.8",  "versions/rnw-21-8-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("21.11", "versions/rnw-21-11-fixes/core/",      "framework/manifest", "version", "widgets/"),
            array("22.2",  "versions/rnw-22-2-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("22.5",  "versions/rnw-22-5-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("22.8",  "versions/rnw-22-8-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("22.11", "versions/rnw-22-11-fixes/core/",      "framework/manifest", "version", "widgets/"),
            array("23.2",  "versions/rnw-23-2-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("23.5",  "versions/rnw-23-5-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("23.8",  "versions/rnw-23-8-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("23.11", "versions/rnw-23-11-fixes/core/",      "framework/manifest", "version", "widgets/"),
            array("24.2",  "versions/rnw-24-2-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("24.5",  "versions/rnw-24-5-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("24.8",  "versions/rnw-24-8-fixes/core/",       "framework/manifest", "version", "widgets/"),
            array("24.11",  "core/",                              "framework/manifest", "version", "widgets/"),
        );

        $aggregateCPHistory = array();
        // go through each release, aggregating widget information together
        // this process will make sure that each individual info.yml file makes sense and that
        // there aren't any inconsistencies between the 'cpHistory' of a widget from one release to the next
        foreach ($dirs as $versionInfo) {
            list($cxRelease, $path, $frameworkVersionPath, $frameworkKey, $widgetPath) = $versionInfo;
            $currentCPHistory = $this->getCurrentVersionData($versionInfo);
            $aggregateCPHistory = $this->mergeCPHistories($currentCPHistory, $aggregateCPHistory);
        }

        // go through the nano variations of each widget and verify they are consistent (e.g. a later nano version only adds
        // information that is consistent with nano bumps)
        $aggregateCPHistory = $this->combineMajorMinorHistory($aggregateCPHistory);

        // verify that minor bumping a widget at the same time we minor bump the framework results in the new widget not
        //  working with the previous framework version
        $this->validateVersionBumpErrors($aggregateCPHistory);

        // remove 'cxRelease' key since it isn't contained in the cpHistory file
        foreach ($aggregateCPHistory['widgetVersions'] as $widgetPath => $widgetVersions) {
            foreach ($widgetVersions as $version => $widgetInfo) {
                unset($aggregateCPHistory['widgetVersions'][$widgetPath][$version]['cxRelease']);
            }
        }

        $cpHistoryFileContents = @yaml_parse_file("{$this->documentRoot}/core/cpHistory");

        // remove 'widgetInfo' key since it isn't contained in the info.yml files
        unset($cpHistoryFileContents['widgetInfo']);

        // see if the current cpHistory file is the same as the computed cpHistory data
        $this->compareCPHistories($aggregateCPHistory, $cpHistoryFileContents);

        // see if changelogs match cpHistory
        $this->compareChangelogs($cpHistoryFileContents);

        if ($this->errorMessages) {
            echo "There appear to be errors. It's possible this is due to changes made in http://rncodereview.us.oracle.com/changelog/cp/core/cpHistory " .
                "in the branch that are not yet in master or integration. Consult with a CP team member if you are unsure whether you caused the error " .
                "or how to resolve it.<br>\n";
            $this->printErrorMessages();
        }

        echo "Processing complete<br>\n";
        exit($this->errorMessages ? 1 : 0);
    }

    protected function _phonyLogin() {
        // Yes, this should do nothing.
    }

    /**
     * Returns CPHistory-equivalent array of current release
     * @param array $versionInfo Array of data to get information from current release
     *  (e.g. array("13.11", "versions/13.11/", "versionMapping", "framework", "widgets/"))
     * @return array CPHistory-equivalent array of current release
     */
    private function getCurrentVersionData($versionInfo) {
        $cpHistory = array();

        list($cxRelease, $path, $frameworkVersionPath, $frameworkKey, $widgetFilePath) = $versionInfo;
        $frameworkDetails = @yaml_parse_file("{$this->documentRoot}/$path$frameworkVersionPath");
        $frameworkVersion = $frameworkDetails[$frameworkKey];

        $cpHistory['frameworkVersions'] = array($cxRelease => $frameworkVersion);

        $widgetInfoFiles = glob("{$this->documentRoot}/$path$widgetFilePath*/*/*/info.yml");

        $cpHistory['widgetVersions'] = array();

        foreach ($widgetInfoFiles as $widgetInfoFile) {
            $widgetPath = Text::getSubstringBefore(Text::getSubstringAfter($widgetInfoFile, "{$this->documentRoot}/$path$widgetFilePath"), "/info.yml");
            $widgetInfo = @yaml_parse_file($widgetInfoFile);
            $widgetVersion = $widgetInfo['version'];
            $widgetInfo = $this->cleanWidgetInfo($cxRelease, $widgetPath, $widgetInfo);

            // add 'cxRelease' key to each widget to make it easier to correlate errors with releases
            $widgetInfo['cxRelease'] = $cxRelease;

            $cpHistory['widgetVersions'][$widgetPath] = array($widgetVersion => $widgetInfo);
        }

        return $cpHistory;
    }

    /**
     * Examines the widgetInfo array of a widget and provides any cleanup for mistakes.
     * @param string $cxRelease CX Release containing the widget's info (e.g. '12.11')
     * @param string $widgetPath Path of the current widget (e.g. 'standard/input/FormInput')
     * @param array $widgetInfo Array of widget information from the widget's parsed info.yml file
     * @return array The cleaned-up $widgetInfo array
     */
    private function cleanWidgetInfo($cxRelease, $widgetPath, array $widgetInfo) {
        $widgetVersion = $widgetInfo['version'];
        foreach ($widgetInfo as $key => $value) {
            if (!in_array($key, array('requires', 'contains', 'extends')))
                unset($widgetInfo[$key]);
        }

        $frameworkVersions = $widgetInfo['requires']['framework'];
        if (!is_array($frameworkVersions)) {
            $this->addErrorMessage("In $cxRelease, the $widgetPath info.yml ($widgetVersion) didn't specify the framework requirements as an array! I'll fix that for you for the rest of this run.");
            $frameworkVersions = array($frameworkVersions);
        }
        // reset 'requires'
        $widgetInfo['requires'] = array('framework' => $frameworkVersions);

        if ($widgetInfo['extends']) {
            if (!$widgetInfo['extends']['versions']) {
                $this->addErrorMessage("In $cxRelease, the $widgetPath info.yml ($widgetVersion) is bad because it doesn't contain versions for the widget it extends, assuming '1.0' for the rest of this run.");
                $widgetInfo['extends']['versions'] = array("1.0");
            }
            // reset 'extends'
            $widgetInfo['extends'] = array('widget' => $widgetInfo['extends']['widget'], 'versions' => $widgetInfo['extends']['versions']);
        }

        if ($widgetInfo['contains']) {
            foreach ($widgetInfo['contains'] as &$containedWidget) {
                if ($containedWidget['description']) {
                    unset($containedWidget['description']);
                }
                if (!$containedWidget['versions'] && $containedWidget['version']) {
                    $this->addErrorMessage("In $cxRelease, the $widgetPath info.yml ($widgetVersion) is bad and uses contains->version instead of contains->versions! I'll fix that for you for the rest of this run.");
                    $containedWidget['versions'] = $containedWidget['version'];
                    unset($containedWidget['version']);
                }
            }
        }

        return $widgetInfo;
    }

    /**
     * Returns aggregated CPHistory.
     * @param array $currentCPHistory Current CPHistory to add to aggregation
     * @param array $aggregateCPHistory Aggregation of existing CPHistory data
     * @return array Aggregated CPHistory
     */
    private function mergeCPHistories(array $currentCPHistory, array $aggregateCPHistory) {
        if (empty($aggregateCPHistory))
            return $currentCPHistory;
        foreach ($currentCPHistory['frameworkVersions'] as $cxRelease => $frameworkVersion) {
            if (array_key_exists($cxRelease, $aggregateCPHistory['frameworkVersions']))
                $this->addErrorMessage("CX Release $cxRelease is already in aggregation.");
        }
        $aggregateCPHistory['frameworkVersions'] = array_merge($aggregateCPHistory['frameworkVersions'], $currentCPHistory['frameworkVersions']);

        foreach ($currentCPHistory['widgetVersions'] as $widgetPath => $widgetVersions) {
            if (!array_key_exists($widgetPath, $aggregateCPHistory['widgetVersions'])) {
                $aggregateCPHistory['widgetVersions'][$widgetPath] = $widgetVersions;
                continue;
            }

            foreach ($widgetVersions as $widgetVersion => $widgetInfo) {
                if (!array_key_exists($widgetVersion, $aggregateCPHistory['widgetVersions'][$widgetPath])) {
                    $aggregateCPHistory['widgetVersions'][$widgetPath][$widgetVersion] = $widgetInfo;
                    continue;
                }

                $aggregateCPHistory['widgetVersions'][$widgetPath][$widgetVersion] = $this->mergeVersions($widgetPath, $widgetVersion . " [cpHistory]", $widgetVersion . " [info.yml]", $widgetInfo, $aggregateCPHistory['widgetVersions'][$widgetPath][$widgetVersion]);
            }
        }

        return $aggregateCPHistory;
    }

    /**
     * Returns array of aggregated widget data, as well as providing some sanity-checking
     * @param string $widgetPath Path of the current widget (e.g. 'standard/input/FormInput')
     * @param string $previousVersion The previous version of the widget (e.g. '1.0.3')
     * @param string $currentVersion The current version of the widget to add (e.g. '1.0.4')
     * @param array $currentWidgetInfo Current widget to add to aggregation
     * @param array $aggregateWidgetInfo Already aggregated widget data
     * @return array Aggregated widget data
     */
    private function mergeVersions($widgetPath, $previousVersion, $currentVersion, array $currentWidgetInfo, array $aggregateWidgetInfo) {
        foreach ($aggregateWidgetInfo['requires']['framework'] as $validFramework) {
            if (!in_array($validFramework, $currentWidgetInfo['requires']['framework']))
                $this->addErrorMessage("Widget $widgetPath had $validFramework, but it no longer appears ($previousVersion -> $currentVersion) [" . implode(", ", $currentWidgetInfo['requires']['framework']) . "]");
        }
        $aggregateWidgetInfo['requires']['framework'] = $currentWidgetInfo['requires']['framework'];

        if (($currentWidgetInfo['contains'] && !$aggregateWidgetInfo['contains'])
            || (!$currentWidgetInfo['contains'] && $aggregateWidgetInfo['contains']))
            $this->addErrorMessage("Widget $widgetPath once had a contains and now does not ($previousVersion -> $currentVersion).");

        if ($currentWidgetInfo['contains'] && $aggregateWidgetInfo['contains']) {
            $aggregateWidgetInfo['contains'] = $this->verifyIncludedWidgets($widgetPath, 'contains', $previousVersion, $currentVersion, $currentWidgetInfo['contains'], $aggregateWidgetInfo['contains']);
        }

        if (($currentWidgetInfo['extends'] && !$aggregateWidgetInfo['extends'])
            || (!$currentWidgetInfo['extends'] && $aggregateWidgetInfo['extends']))
            $this->addErrorMessage("Widget $widgetPath once had a extends and now does not ($previousVersion -> $currentVersion).");

        if ($currentWidgetInfo['extends'] && $aggregateWidgetInfo['extends']) {
            if ($currentWidgetInfo['extends']['widget'] !== $aggregateWidgetInfo['extends']['widget'])
                $this->addErrorMessage("extended widget paths changed $widgetPath?: ($previousVersion -> $currentVersion) " . var_export($currentWidgetInfo['extends'], true) . " " . var_export($aggregateWidgetInfo['extends'], true));
            $extends = $this->verifyIncludedWidgets($widgetPath, 'extends', $previousVersion, $currentVersion, array($currentWidgetInfo['extends']), array($aggregateWidgetInfo['extends']));
            $aggregateWidgetInfo['extends'] = array_shift($extends);
        }

        return $aggregateWidgetInfo;
    }

    /**
     * Returns array of aggregated widget 'contains' or 'extends' data, as well as providing some sanity-checking
     * @param string $widgetPath Path of the current widget (e.g. 'standard/input/FormInput')
     * @param string $type Either 'contains' or 'extends'
     * @param string $previousVersion The previous version of the widget (e.g. '1.0.3')
     * @param string $currentVersion The current version of the widget to add (e.g. '1.0.4')
     * @param array $currentIncludedWidgets Current widget to add to aggregation
     * @param array $aggregateIncludedWidgets Already aggregated widget 'contains' or 'extends' data
     * @return array Aggregated widget 'contains' or 'extends' data
     */
    private function verifyIncludedWidgets($widgetPath, $type, $previousVersion, $currentVersion, array $currentIncludedWidgets, array $aggregateIncludedWidgets) {
        foreach ($aggregateIncludedWidgets as &$aggregateIncludedWidget) {
            $includedWidgetPath = $aggregateIncludedWidget['widget'];
            $includedWidgetVersions = $aggregateIncludedWidget['versions'];
            $widgetFound = false;
            foreach ($currentIncludedWidgets as $currentIncludedWidget) {
                if ($currentIncludedWidget['widget'] === $includedWidgetPath) {
                    $widgetFound = true;
                    foreach ($includedWidgetVersions as $validVersion) {
                        if (!in_array($validVersion, $currentIncludedWidget['versions']))
                            $this->addErrorMessage("In $widgetPath, it looks like we said it $type $validVersion version of $includedWidgetPath, but it looks like it has disappeared ($previousVersion -> $currentVersion).");
                    }
                    $aggregateIncludedWidget['versions'] = $currentIncludedWidget['versions'];
                    break;
                }
            }

            if (!$widgetFound) {
                $this->addErrorMessage("In $widgetPath, we said that it $type $includedWidgetPath, but looks like it has now disappeared ($previousVersion -> $currentVersion).");
            }
        }

        foreach ($currentIncludedWidgets as $currentIncludedWidget) {
            $includedWidgetPath = $currentIncludedWidget['widget'];
            $widgetFound = false;
            foreach ($aggregateIncludedWidgets as $aggregateIncludedWidgetToCompare) {
                if ($aggregateIncludedWidgetToCompare['widget'] === $includedWidgetPath) {
                    $widgetFound = true;
                    break;
                }
            }

            if (!$widgetFound) {
                $this->addErrorMessage("In $widgetPath, it previously didn't list $includedWidgetPath, but now $type it ($previousVersion -> $currentVersion).");
                $aggregateIncludedWidgets[] = $currentIncludedWidget;
            }

        }

        return $aggregateIncludedWidgets;
    }

    /**
     * Returns aggregated CPHistory so that widget data with the same major.minor version are aggregated.
     * @param array $aggregateCPHistory Aggregation of CPHistory data
     * @return array Aggregated CPHistory with consistent major.minor version data
     */
    private function combineMajorMinorHistory(array $aggregateCPHistory) {
        foreach ($aggregateCPHistory['widgetVersions'] as $widgetPath => &$widgetVersions) {
            $versions = array();
            foreach ($widgetVersions as $version => $widgetInfo) {
                $majorMinorVersion = $this->makeMajorMinor($version);
                if (!$versions[$majorMinorVersion])
                    $versions[$majorMinorVersion] = array();
                $versions[$majorMinorVersion][] = $version;
            }
            foreach ($versions as $majorMinorVersion => $specificVersions) {
                for ($i = 0; $i < count($specificVersions) - 1; $i++) {
                    for ($j = $i + 1; $j < count($specificVersions); $j++) {
                        $widgetVersions[$specificVersions[$i]] = $this->mergeVersions($widgetPath,
                            $specificVersions[$i], $specificVersions[$j],
                            $widgetVersions[$specificVersions[$j]],
                            $widgetVersions[$specificVersions[$i]]);
                    }
                }
            }
        }

        return $aggregateCPHistory;
    }

    /**
     * Determines if any widgets that minor bump with a framework major/minor bump do not
     *  include support of the previous framework version.
     * @param array $aggregateCPHistory Aggregate CPHistory
     */
    private function validateVersionBumpErrors(array $aggregateCPHistory) {
        $bumpBarriers = $this->getBumpBarriers($aggregateCPHistory['frameworkVersions']);

        foreach ($bumpBarriers as $bumpBarrier) {
            list($previousRelease, $previousMajorMinor, $nextRelease, $nextMajorMinor) = $bumpBarrier;
            foreach ($aggregateCPHistory['widgetVersions'] as $widgetPath => $widgetVersionsInfo) {
                $widgetVersions = array_keys($widgetVersionsInfo);
                for ($i = 1; $i < count($widgetVersions); $i++) {
                    // we released some version when we minor bumped
                    if ($widgetVersionsInfo[$widgetVersions[$i]]['cxRelease'] === $nextRelease) {
                        // see if the widget also minor bumped
                        $previousWidgetVersion = $widgetVersions[$i-1];
                        $previousWidgetMajorMinorVersion = $this->makeMajorMinor($previousWidgetVersion);
                        $nextWidgetVersion = $widgetVersions[$i];
                        $nextWidgetMajorMinorVersion = $this->makeMajorMinor($nextWidgetVersion);
                        if ($previousWidgetMajorMinorVersion !== $nextWidgetMajorMinorVersion) {
                            $previousWidgetInfo = $widgetVersionsInfo[$previousWidgetVersion];
                            $nextWidgetInfo = $widgetVersionsInfo[$nextWidgetVersion];

                            if (in_array($nextMajorMinor, $previousWidgetInfo['requires']['framework']) && !$this->isInBackportWhitelist($widgetPath, $previousWidgetVersion)) {
                                $this->addErrorMessage("For $widgetPath, we minor bumped at $nextRelease ($previousWidgetVersion -> $nextWidgetVersion), but $previousWidgetVersion claims support for $nextMajorMinor");
                            }
                            if (in_array($previousMajorMinor, $nextWidgetInfo['requires']['framework']))
                                $this->addErrorMessage("For $widgetPath, we minor bumped at $nextRelease ($previousWidgetVersion -> $nextWidgetVersion), but $nextWidgetVersion claims support for $previousMajorMinor");
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns an array containing the releases where a major/minor version bump of a widget should
     *  force the previous framework version to not be allowed in the new version of the widget
     *  (e.g. array(array('13.2', '3.0', '13.5', '3.1'))).
     * @param array $frameworkVersions Mapping of CX Releases to CP framework versions
     * @return array Array with information regarding the version bumps
     */
    private function getBumpBarriers(array $frameworkVersions) {
        $bumpBarriers = array();
        $cxReleases = array_keys($frameworkVersions);
        $previousRelease = $cxReleases[0];
        $previousVersion = $frameworkVersions[$previousRelease];
        for ($i = 1; $i < count($cxReleases); $i++) {
            $nextRelease = $cxReleases[$i];
            $nextVersion = $frameworkVersions[$nextRelease];

            $previousMajorMinor = $this->makeMajorMinor($previousVersion);
            $nextMajorMinor = $this->makeMajorMinor($nextVersion);

            if ($previousMajorMinor !== $nextMajorMinor) {
                $bumpBarriers[] = array($previousRelease, $previousMajorMinor, $nextRelease, $nextMajorMinor);
            }

            $previousRelease = $nextRelease;
            $previousVersion = $nextVersion;
        }

        return $bumpBarriers;
    }

    private function isInBackportWhitelist($path, $widgetVersion){
        foreach ($this->widgetVersionBackportWhitelist as $backportedWidget) {
            if ($path === $backportedWidget['path'] && $widgetVersion === $backportedWidget['version']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Performs a diff to compare CPHistory based on what the info.yml files contain and the cpHistory file.
     * @param array $aggregateCPHistory Array of aggregated info.yml files
     * @param array $fileCPHistory Array of CPHistory file
     */
    private function compareCPHistories(array $aggregateCPHistory, array $fileCPHistory) {
        $this->diffArray("", $aggregateCPHistory, $fileCPHistory);
    }

    /**
     * Compares two arrays and creates error messages for any differences found.
     * @param string $path Path to display in any error messages
     * @param array $arrayOne First array to examine
     * @param array $arrayTwo Second array to examine
     */
    private function diffArray($path, array $arrayOne, array $arrayTwo) {
        $diffsOneToTwo = array_diff($arrayOne, $arrayTwo);
        $diffsTwoToOne = array_diff($arrayTwo, $arrayOne);

        if (!empty($diffsOneToTwo)) {
            $this->addErrorMessage("Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [$path]: [" . implode(", ", $diffsOneToTwo) . "]");
        }

        if (!empty($diffsTwoToOne)) {
            $this->addErrorMessage("Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [$path]: [" . implode(", ", $diffsTwoToOne) . "]");
        }

        foreach ($arrayOne as $key => $value) {
            if (is_array($value) && is_array($arrayTwo[$key]))
                $this->diffArray($path ? "$path - $key" : $key, $value, $arrayTwo[$key]);
            else if (is_array($value) && !$arrayTwo[$key])
                $this->addErrorMessage("Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [$path - $key]: [" . implode(", ", $value) . "]");
        }

        foreach ($arrayTwo as $key => $value) {
            if (is_array($value) && !$arrayOne[$key])
                $this->addErrorMessage("Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [$path - $key]: [" . implode(", ", $value) . "]");
        }
    }

    /**
     * Performs a diff to compare CPHistory based on what the info.yml files contain and the cpHistory file.
     * @param array $fileCPHistory Array of CPHistory file
     */
    private function compareChangelogs(array $fileCPHistory) {
        $currentRelease = Version::getVersionNumber(MOD_BUILD_VER);
        foreach ($fileCPHistory['widgetVersions'] as $widgetPath => $widgetData) {
            // verify the changelog exists and parses well
            $changelog = @yaml_parse_file("{$this->documentRoot}/core/widgets/$widgetPath/changelog.yml");
            if ($changelog === false) {
                $this->addErrorMessage("Bad changelog entry for $widgetPath?");
                continue;
            }

            // get the latest version of the widget
            end($widgetData);
            $latestVersion = key($widgetData);

            // find the latest version of the widget based on changelog entries,
            // accounting for the fact that there can be changelog entries
            // in the future
            foreach ($changelog as $widgetVersion => $changelogData) {
                $latestVersionFromChangelog = $widgetVersion;
                // keep going through the changelog entries until you find one that matches this release (or earlier)
                if (Version::compareVersionNumbers($currentRelease, $changelogData['release']) >= 0) {
                    break;
                }
            }

            // now we verify that cpHistory and changelog entries are in agreement
            if ($latestVersion !== $latestVersionFromChangelog) {
                $this->addErrorMessage("Differences identified between cpHistory file and changelog entry at [$widgetPath]: [$latestVersion, $latestVersionFromChangelog]");
            }
        }
    }

    /**
     * Returns the major.minor version of $version.
     * @param string $version Version number (e.g. '1.0.3')
     * @return string The major.minor version of the version
     */
    private function makeMajorMinor($version) {
        return substr($version, 0, strrpos($version, '.'));
    }

    /**
     * Checks $errorMessage for known error messages to ignore and otherwise adds the $errorMessage to an array of error messages.
     * @param string $errorMessage Error message
     */
    private function addErrorMessage($errorMessage) {
        static $fullMessagesToIgnore = array(
            // ChatServerConnect - 'version', not 'versions' for ChatHours 'contains'
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/chat/ChatServerConnect - 1.0.1 - contains - 1 - versions]: [1.0]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [widgetVersions - standard/chat/ChatServerConnect - 1.0.1 - contains - 1 - version]: [1.0]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/chat/ChatServerConnect - 1.0.2 - contains - 1 - versions]: [1.0]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [widgetVersions - standard/chat/ChatServerConnect - 1.0.2 - contains - 1 - version]: [1.0]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/chat/ChatServerConnect - 1.0.3 - contains - 1 - versions]: [1.0]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [widgetVersions - standard/chat/ChatServerConnect - 1.0.3 - contains - 1 - version]: [1.0]",

            // ChatAttachFileButton - version 1.8.1 should only extend FileAttachmentUpload 1.9
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [widgetVersions - standard/chat/ChatAttachFileButton - 1.8.1 - extends - versions]: [1.8]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/chat/ChatAttachFileButton - 1.8.1 - extends - versions]: [1.9]",

            // FormInput - forgot to list PasswordInput in 'contains' list in 1.0.1 info.yml
            "In standard/input/FormInput, it previously didn't list standard/input/PasswordInput, but now contains it (1.0.1 -> 1.0.2).",

            // PasswordInput - 1.0.1 shouldn't list 3.1 as a valid framework support in cpHistory
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/input/PasswordInput - 1.0.1 - requires - framework]: [3.1]",

            // SocialFileAttachmentUpload - 1.2.2 shouldn't list 3.7 as a valid framework support in cpHistory
            "For standard/input/SocialFileAttachmentUpload, we minor bumped at 19.8 (1.1.1 -> 1.2.1), but 1.2.1 claims support for 3.7",
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [widgetVersions - standard/input/SocialFileAttachmentUpload - 1.2.1 - requires - framework]: [3.7]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [widgetVersions - standard/input/SocialFileAttachmentUpload - 1.2.2 - requires - framework]: [3.7]",

            // TextInput - 1.0.1/1.0.2 shouldn't list 3.1 as a valid framework support in cpHistory
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/input/TextInput - 1.0.1 - requires - framework]: [3.1]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/input/TextInput - 1.0.2 - requires - framework]: [3.1]",

            // TopicWords - 1.1.1 shouldn't list 3.7 as a valid framework support in cpHistory
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/knowledgebase/TopicWords - 1.1.1 - requires - framework]: [3.7]",

            // LoginDialog - 1.0.1 shouldn't list 3.1 as a valid framework support in cpHistory
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/login/LoginDialog - 1.0.1 - requires - framework]: [3.1]",

            // DiscussionSubscriptionManager - 1.2.1 shouldn't list 3.8 as a valid framework support in cpHistory
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [widgetVersions - standard/notifications/DiscussionSubscriptionManager - 1.2.1 - requires - framework]: [3.8]",

            // Version bumping of ChatLaunchWidget for 3.9 in 23A shouldn't list as error in cpHistory
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [widgetVersions - standard/chat/ChatLaunchButton - 1.6.1 - requires - framework]: [3.9]",
            
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [frameworkVersions]: []",
            "Differences identified between calculated cpHistory and cpHistory file (additions in cpHistory file) at [frameworkVersions]: [3.9.4]",
            
            // Framwork bumping to 3.10.1
            "For standard/output/FieldDisplay, we minor bumped at 14.2 (1.0.1 -> 1.1.1), but 1.1.1 claims support for 3.1",
            "For standard/utils/ProgressBar, we minor bumped at 14.2 (1.0.1 -> 1.1.1), but 1.1.1 claims support for 3.1",
            
            // PHP multi-support in CP 3.11 onwards
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [ - rnPhpVersions]: [Array, Array]",
            "Differences identified between calculated cpHistory and cpHistory file (additions in calculated cpHistory) at [ - rnPhpVersions]: [Array]",

            // Widgets are not supported in the most recent framework version, so the changelog files do not exist
            "Bad changelog entry for standard/knowledgebase/IntentGuideDisplay?",
            "Bad changelog entry for standard/knowledgebase/PreviousAnswers?",
            "Bad changelog entry for standard/utils/RightNowLogo?",
            "Bad changelog entry for standard/feedback/MobileAnswerFeedback?",
            "Bad changelog entry for standard/search/MobileSimpleSearch?",
            "Bad changelog entry for standard/reports/SearchTruncation?",
            "Bad changelog entry for standard/output/ContactNameDisplay?",
            "Bad changelog entry for standard/utils/MobileEmailAnswerLink?",
            "Bad changelog entry for standard/chat/ChatLaunchFormOpen?",
        );

        if (
                in_array($errorMessage, $fullMessagesToIgnore, true)

                // ChatServerConnect v1.0 - 'version', not 'versions' for ChatHours 'contains'
                ||
                (Text::stringContains($errorMessage, ", the standard/chat/ChatServerConnect info.yml (1.0.")
                    && Text::stringContains($errorMessage, "is bad and uses contains->version instead of contains->versions! I'll fix that for you for the rest of this run."))

                // ContactNameInput - framework version was an int, not an array of ints
                ||
                (Text::stringContains($errorMessage, "In 12.11, the standard/input/ContactNameInput info.yml (1.0.")
                    && Text::stringContains($errorMessage, "didn't specify the framework requirements as an array! I'll fix that for you for the rest of this run."))
                ||
                (Text::stringContains($errorMessage, "In 13.2, the standard/input/ContactNameInput info.yml (1.0.")
                    && Text::stringContains($errorMessage, "didn't specify the framework requirements as an array! I'll fix that for you for the rest of this run."))

                // PasswordInput - framework version was an int, not an array of ints
                ||
                (Text::stringContains($errorMessage, "In 12.11, the standard/input/PasswordInput info.yml (1.0.")
                    && Text::stringContains($errorMessage, "didn't specify the framework requirements as an array! I'll fix that for you for the rest of this run."))
                ||
                (Text::stringContains($errorMessage, "In 13.2, the standard/input/PasswordInput info.yml (1.0.")
                    && Text::stringContains($errorMessage, "didn't specify the framework requirements as an array! I'll fix that for you for the rest of this run."))

                // ContactNameDisplay - framework version was an int, not an array of ints
                ||
                (Text::stringContains($errorMessage, "In 12.11, the standard/output/ContactNameDisplay info.yml (1.0.")
                    && Text::stringContains($errorMessage, "didn't specify the framework requirements as an array! I'll fix that for you for the rest of this run."))
                ||
                (Text::stringContains($errorMessage, "In 13.2, the standard/output/ContactNameDisplay info.yml (1.0.")
                    && Text::stringContains($errorMessage, "didn't specify the framework requirements as an array! I'll fix that for you for the rest of this run."))

                // MobileEmailAnswerLink - failed to specify 'versions' in extension
                ||
                (Text::stringContains($errorMessage, "In 12.11, the standard/utils/MobileEmailAnswerLink info.yml (1.0.")
                    && Text::stringContains($errorMessage, "is bad because it doesn't contain versions for the widget it extends, assuming '1.0' for the rest of this run."))
                ||
                (Text::stringContains($errorMessage, "In 13.2, the standard/utils/MobileEmailAnswerLink info.yml (1.0.")
                    && Text::stringContains($errorMessage, "is bad because it doesn't contain versions for the widget it extends, assuming '1.0' for the rest of this run."))
            )
            return;
        $this->errorMessages[] = $errorMessage;
    }

    /**
     * Echo's out all error messages.
     */
    private function printErrorMessages() {
        foreach ($this->errorMessages as $errorMessage) {
            echo $errorMessage . "<br>\n";
        }
    }
}
