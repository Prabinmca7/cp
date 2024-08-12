<?
use \RightNow\Utils\Config;

function getMajorMinorVersion($version) {
    if (isset($version) && ($versionParts = explode('.', $version)) && count($versionParts) === 3) {
        array_pop($versionParts);
        $version = implode('.', $versionParts);
    }
    return $version;
}

/**
 *  Generates a title and class name for a given widget. The title reflects
 *  the status of the widget (up-to-date, out-of-date, out-of-date + requires framework update)
 *  similarly the class name reflects these escalations
 */
function getWidgetMetaInfo($name, $info, $widgetCategories, $declaredVersions, $currentFramework) {
    $title = $class = $tagClass = '';
    $versionInDevMode = getMajorMinorVersion(isset($declaredVersions['Development'][$name]) ? $declaredVersions['Development'][$name] : '');

    $class = ($versionInDevMode)
        ? 'inuse'
        : 'disabled';

    if ($class !== 'disabled') {
        $latest = end($info['versions']);
        if (is_array($latest) && (!isset($latest['error']) || !$latest['error'])) {
            if ($versionInDevMode === $latest['version']) {
                $class .= ' uptodate';
            }
            else {
                $tagClass .= ' tag-outofdate';
                $class .= ' outofdate';
                $title = Config::getMessage(NEW_VERSIONS_WIDGET_ARE_AVAILABLE_MSG);

                //The current version is out of date and the latest version isn't even on this framework
                if ($latest['framework'] && !in_array($currentFramework, $latest['framework'])) {
                    $tagClass .= ' tag-woefully';
                    $class .= ' woefully';
                    $title = Config::getMessage(FRAMEWORK_UPD_REQD_VERSION_WIDGET_MSG);
                }
            }
        }
    }

    foreach($info['category'] as $category) {
        foreach($widgetCategories as $widgetCategoryName => $widgetCategoryHash) {
            if($widgetCategoryName === $category)
                $class .= " {$widgetCategoryHash}";
        }
    }

    return array(
        'title' => $title,
        'class' => $class,
        'tagClass' => $tagClass
    );
}

/**
 * Determine what tag to apply to each framework version. Only apply out of date tags if
 * one of their site modes is on that framework.
 */
function getFrameworkTagClass($framework, $maxMajorVersion, $maxFramework, $activeFrameworks) {
    $tagClass = '';

    $activeFrameworks = array_map(function($version) {
        return getMajorMinorVersion($version);
    }, array_values($activeFrameworks));

    if ($framework['version'] !== $maxFramework && in_array($framework['version'], $activeFrameworks)) {
        $tagClass = 'tag-outofdate';
        if ($maxMajorVersion !== substr($framework['version'], 0, strpos($framework['version'], '.'))) {
            $tagClass .= ' tag-woefully';
        }
    }

    return $tagClass;
}

/**
 * Return a scaling class for the timeline. As the number or versions increase, reduce the amount of whitespace between versions.
 */
function getScalingClassName($numberOfVersions) {
    if ($numberOfVersions < 5) return 'sparse';
    if ($numberOfVersions > 10) return 'dense';
    return 'medium';
}

$modeLabels = array('Development' => Config::getMessage(DEVELOPMENT_LBL), 'Staging' => Config::getMessage(STAGING_LBL), 'Production' => Config::getMessage(PRODUCTION_LBL));

/**
 *  Create a comma separated list of the different modes the widget is used in
 *  @return String|false - e.g. Development, Production, Staging
 */
function getModesInUse($modeLabels, $frameworks, $version, $declaredVersions, $name = null, $useLabels = true) {
    $modes = array();
    foreach (array_keys($declaredVersions) as $mode) {
        // ignore declared versions in modes that are still in 2.0
        if ($frameworks[$mode] === '2.0')
            continue;
        $versionInMode = $declaredVersions[$mode];
        if ($name) {
            $versionInMode = isset($versionInMode[$name]) ? $versionInMode[$name] : null;
        }
        $versionInMode = getMajorMinorVersion($versionInMode);
        if ($versionInMode === $version) {
            if ($useLabels)
                $mode = $modeLabels[$mode];
            $modes []= $mode;
        }
    }
    if (!$modes) {
        return false;
    }
    return implode(', ', $modes);
}

function truncateName($name) {
    foreach (array('standard', 'custom') as $type) {
        if ($widgetInfo['displayName'] = \RightNow\Utils\Text::getSubstringAfter($name, "$type/")) {
            $lastSlashIndex = strrpos($widgetInfo['displayName'], '/');
            $widgetInfo['widgetDirectory'] = $lastSlashIndex ? substr($widgetInfo['displayName'], 0, $lastSlashIndex) : "";
            $widgetInfo['widgetName'] = $lastSlashIndex ? substr($widgetInfo['displayName'], $lastSlashIndex + 1) : $widgetInfo['displayName'];
            return $widgetInfo;
        }
    }
}

/* Calculate which indexes we are going to insert the timeline emphasis at and return it.
 * @param $versions array All the possible versions of a particular widget
 * @param $currentFramework string The major.minor framework version number in Development
 * @return array The index number for inserting the old and new emphasis
 */
function getIndexOfTimelineEmphasis($versions, $currentFramework) {
    $oldVersionMarker = $newVersionMarker = -1;
    foreach($versions as $index => $info) {
        $supportedFrameworks = $info['framework'];
        if($supportedFrameworks)
            usort($supportedFrameworks, "\RightNow\Internal\Utils\Version::compareVersionNumbers");

        //This version isn't supported with their current framework and is older, emphasize it
        if($supportedFrameworks && !in_array($currentFramework, $supportedFrameworks)) {
            //The largest supported framework of this widget version is smaller then the currentFramework
            if(\RightNow\Internal\Utils\Version::compareVersionNumbers(end($supportedFrameworks), $currentFramework) === -1) {
                $oldVersionMarker++;
            }
            //The lowest supported framework of this widget version is larger then the currentFramework
            else if(\RightNow\Internal\Utils\Version::compareVersionNumbers($supportedFrameworks[0], $currentFramework) === 1) {
                if($newVersionMarker === -1)
                    $newVersionMarker = $index;
            }
        }
    }
    return array($oldVersionMarker, $newVersionMarker);
}

/**
 * Returns the calculated percentage for each of the timeline pins
 */
function getPinPosition($current, $total) {
    return (($current + 1) / ($total + 1)) * 100;
}

/**
 * Returns the calculated percentage for the emphasis (old and new)
 */
function getEmphasisPosition($index, $total, $isOld) {
    if($isOld)
        return 100 - getPinPosition($index, $total);
    return getPinPosition($index, $total);
}

?>
<div class="loading bigwait"></div>
<div id="togglePanel" class="panelHeader">
  <button>↓</button>
</div>
<div id="mainTabs" class="yui3-tabview yui3-tabview-content">
    <ul class="yui3-tabview-list">
        <li class="yui3-tab yui3-widget yui3-tab-selected yui3-tab-focused"><a class="yui3-tab-label yui3-tab-content" href="#widgetVersions"><?= Config::getMessage(WIDGETS_LBL) ?></a></li>
        <li class="yui3-tab yui3-widget"><a class="yui3-tab-label yui3-tab-content" href="#frameworkVersions"><?= Config::getMessage(FRAMEWORK_LBL) ?></a></li>
        <li id="historyTab" class="yui3-tab yui3-widget"><a class="yui3-tab-label yui3-tab-content" href="#history"><?= Config::getMessage(RECENT_CHANGES_LBL) ?></a></li>
    </ul>
    <div class="yui3-tabview-panel">
        <div id="widgetVersions" class="">
            <div class="col leftPanel">
                <div id="filter">
                    <div class="panelHeader">
                        <form>
                            <input id="widgetsSearch" type="search" placeholder="<?= Config::getMessage(SEARCH_BY_NAME_CMD) ?>"/>
                        </form>
                    </div>
                    <div id="widgetsFilter">
                        <a href="javascript:void(0);" id="viewOptions" role="button"><?= Config::getMessage(VIEW_OPTIONS_CMD) ?> <i class="fa fa-chevron-down"></i></a>
                        <ul id="widgetsFilterDropdown" class="hide dropdown abs">
                            <li class="subLabel"><?= Config::getMessage(WDGETS_LBL) ?>
                                <ul>
                                    <li><a class="selected" href="javascript:void(0);" data-filter-value="both" data-filter-group="widgetType"><?= Config::getMessage(ALL_LBL) ?></a></li>
                                    <li><a href="javascript:void(0);" data-filter-value="custom" data-filter-group="widgetType"><?= Config::getMessage(CUSTOM_LBL) ?></a></li>
                                    <li><a href="javascript:void(0);" data-filter-value="standard" data-filter-group="widgetType"><?= Config::getMessage(STANDARD_LBL) ?></a></li>
                                </ul>
                            </li>
                            <li class="subLabel"><?= Config::getMessage(STATUS_COLON_LBL) ?>
                                <ul>
                                    <li><a class="selected" href="javascript:void(0);" data-filter-value="all" data-filter-group="widgetStatus"><?= Config::getMessage(ALL_LBL) ?></a></li>
                                    <li><a href="javascript:void(0);" data-filter-value="inuse" data-filter-group="widgetStatus"><?= Config::getMessage(ACTIVE_LBL) ?></a></li>
                                    <li><a href="javascript:void(0);" data-filter-value="notinuse" data-filter-group="widgetStatus"><?= Config::getMessage(INACTIVE_LBL) ?></a></li>
                                    <li><a href="javascript:void(0);" data-filter-value="uptodate" data-filter-group="widgetStatus"><?= Config::getMessage(UP_TO_DATE_LBL) ?></a></li>
                                    <li><a href="javascript:void(0);" data-filter-value="outofdate" data-filter-group="widgetStatus"><?= Config::getMessage(OUT_OF_DATE_LBL) ?></a></li>
                                </ul>
                            </li>
                            <li class="subLabel"><?= Config::getMessage(CATEGORIES_LBL) ?>
                                <ul>
                                    <li><a class="selected" href="javascript:void(0);" data-filter-value="any" data-filter-group="widgetCategory"><?= Config::getMessage(ANY_LBL) ?></a></li>
                                    <? foreach($widgetCategories as $widgetCategoryName => $widgetCategoryHash): ?>
                                    <li><a href="javascript:void(0);" data-filter-value="<?= $widgetCategoryHash ?>" data-filter-group="widgetCategory"><?= $widgetCategoryName ?></a></li>
                                    <? endforeach; ?>
                                </ul>
                            </li>
                        </ul>
                        <div class="appliedFilters">
                            <div data-indicate="widgetType" class="appliedFilter">
                                <span class="label"><?= Config::getMessage(WDGETS_LBL) ?></span>
                                <span class="value"><?= Config::getMessage(ALL_LBL) ?></span>
                            </div>
                            <div data-indicate="widgetStatus" class="appliedFilter">
                                <span class="label"><?= Config::getMessage(STATUS_COLON_LBL) ?></span>
                                <span class="value"><?= Config::getMessage(ALL_LBL) ?></span>
                            </div>
                            <div data-indicate="widgetCategory" class="appliedFilter">
                                <span class="label"><?= Config::getMessage(CATEGORY_COLON_LBL) ?></span>
                                <span class="value"><?= Config::getMessage(ANY_LBL) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="widgets" class="listing" role="listbox">
                    <? foreach ($allWidgets as $widgetName => $widgetInfo): ?>
                        <?
                            $versionInfo = getWidgetMetaInfo($widgetName, $widgetInfo, $widgetCategories, $declaredVersions, $currentFramework['Development']);
                            $widgetNamePathInfo = truncateName($widgetName);
                        ?>
                        <div role="option" class="listing-item widget <?=$versionInfo['class'];?>" data-name="<?= $widgetName ?>" data-display-name="<?= $widgetNamePathInfo['displayName'] ?>" data-type="<?= $widgetInfo['type'] ?>">
                            <div class="main">
                                <div class="meta"><?= $widgetInfo['type'] ?><?= $widgetNamePathInfo['widgetDirectory'] ? "/" : "" ?><?= $widgetNamePathInfo['widgetDirectory'] ?></div>
                                <div class="title"><?= $widgetNamePathInfo['widgetName'] ?></div>
                                <? if($versionInfo['tagClass']): ?>
                                    <div data-tooltip="<?= $versionInfo['title'] ?>" class="<?= $versionInfo['tagClass'] ?>"><?= Config::getMessage(OUT_OF_DTE_LBL) ?></div>
                                <? endif;?>
                            </div>
                            <div class="hide">
                                <a class="thumbnail" href="javascript:void(0);" tabindex="0"></a>
                                <div class="timeline">
                                <? if (isset($widgetInfo['versions'][0]['error']) && $widgetInfo['versions'][0]['error']): ?>
                                    <div class="notice"><?=$widgetInfo['versions'][0]['error']?></div>
                                <? else: ?>
                                    <? $numberOfAvailableVersions = count($widgetInfo['versions']); ?>
                                    <? if ($numberOfAvailableVersions === 1): ?>
                                        <?
                                            $info = $widgetInfo['versions'][0];
                                            $version = $info['version'];
                                            $modes = getModesInUse($modeLabels, $currentFramework, $version, $declaredVersions, $widgetName, false);
                                        ?>
                                        <div class="notice"><?= Config::getMessage(THIS_IS_THE_NEWEST_VERSION_MSG) ?></div>
                                        <div class="label hide" data-version="<?= $version ?>" data-framework="<?= is_array($info['framework']) ? implode(', ', $info['framework']) : '' ?>" data-inuse="<?= $modes ?>"><?= $version ?></div>
                                    <? else: ?>
                                        <?
                                            $scaleClass = getScalingClassName($numberOfAvailableVersions);
                                            list($firstIndex, $lastIndex) = getIndexOfTimelineEmphasis($widgetInfo['versions'], $currentFramework['Development']);
                                            $hasEmphasized = $beyondCurrent = false;
                                        ?>
                                        <div class="line <?= $scaleClass ?>">
                                            <? foreach ($widgetInfo['versions'] as $index => $info): ?>
                                                <?
                                                    $modes = '';
                                                    $version = $info['version'];
                                                    $pinPosition = getPinPosition($index, $numberOfAvailableVersions);
                                                    $oldFrameworkPosition = getEmphasisPosition($firstIndex, $numberOfAvailableVersions, true);
                                                    $newFrameworkPosition = getEmphasisPosition($lastIndex, $numberOfAvailableVersions, false);
                                                ?>
                                            <? if($firstIndex !== -1 && !$hasEmphasized): ?>
                                                <? $hasEmphasized = true ?>
                                                <div class="version region oldFrameworkVersion" title="<?= Config::getMessage(REQUIRES_OLDER_FRAMEWORK_VERSION_LBL) ?>" style="right: <?= $oldFrameworkPosition ?>%"></div>
                                            <? endif; ?>
                                            <? if($lastIndex === $index): ?>
                                                <div class="version region newFrameworkVersion" title="<?= Config::getMessage(REQUIRES_A_NEWER_FRAMEWORK_VERSION_LBL) ?>" style="left: <?= $newFrameworkPosition ?>%"></div>
                                            <? endif; ?>
                                            <? if ($modes = getModesInUse($modeLabels, $currentFramework, $version, $declaredVersions, $widgetName, false)): ?>
                                                <? $modesWithLabels = getModesInUse($modeLabels, $currentFramework, $version, $declaredVersions, $widgetName); ?>
                                                <? if(\RightNow\Utils\Text::stringContains($modes, 'Development')): ?>
                                                    <? $beyondCurrent = true; ?>
                                                <? endif; ?>
                                                <div class="version currentVersion" title="<? printf(Config::getMessage(CURRENT_PCT_S_VERSION_LBL), $modesWithLabels) ?>" style="left:<?= $pinPosition ?>%;">
                                            <? elseif ($beyondCurrent): ?>
                                                <? if ($index === $numberOfAvailableVersions - 1): ?>
                                                    <div class="version newestVersion" title="<?= Config::getMessage(NEWEST_VERSION_LBL) ?>" style="left:<?= $pinPosition ?>%;">
                                                <? else: ?>
                                                    <div class="version newerVersion" title="<?= Config::getMessage(NEWER_VERSION_LBL) ?>" style="left:<?= $pinPosition ?>%;">
                                                <? endif; ?>
                                            <? else: ?>
                                                <div class="version" style="left:<?= $pinPosition ?>%;">
                                            <? endif; ?>
                                                    <div class="circle" data-inuse="<?= $modes ?>"></div>
                                                    <div class="label" data-version="<?= $version ?>" data-framework="<?= is_array($info['framework']) ? implode(', ', $info['framework']): '' ?>" data-inuse="<?= $modes ?>"><?= $version ?></div>
                                                </div>
                                            <? endforeach; ?>
                                        </div>
                                    <? endif; ?>
                                <? endif; ?>
                                </div>
                                <div class="category hide"><b><?= \RightNow\Utils\Config::getMessage(CATEGORY_LBL) ?>:</b></div>
                                <div class="versions"></div>
                                <div class="views"></div>
                                <div class="changes"></div>
                            </div>
                        </div>
                    <? endforeach; ?>
                </div>
                <div id="extras" class="panelHeader">
                    <button id="updateAll"><i aria-hidden="true" role="presentation" class="fa fa-refresh"></i> <?= Config::getMessage(UPDATE_ALL_CMD) ?></button>
                </div>
            </div>
            <div class="col rightPanel">
                <div id="widgetDetails">
                    <div id="widgetHeader" class="panelHeader">
                        <div id="widgetName" class="panelTitle"></div>
                        <div class="controls hide">
                            <select name="updateVersion" id="updateVersion"></select>
                            <a id="updateWidgetButton" href="javascript:void(0);" class="button"><?= Config::getMessage(ACTIVATE_THIS_VERSION_CMD); ?></a>
                        </div>
                    </div>
                    <div id="widgetPanel" class="panelContent">
                        <div id="details" class="empty">
                            <div class="emptyMessage"><?= Config::getMessage(SELECT_A_WIDGET_LBL) ?></div>
                        </div>
                        <div id="tabs"></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="frameworkVersions" class="hide">
            <div class="col leftPanel">
                <div id="frameworks" class="listing" role="listbox">
                <? $maxMajorVersion = substr($maxFramework, 0, strpos($maxFramework, '.')); ?>
                <? while($framework = array_pop($availableFrameworks)): ?>
                    <? $tagClass = getFrameworkTagClass($framework, $maxMajorVersion, $maxFramework, $currentFramework); ?>
                    <div role="option" class="listing-item" data-name="<?= $framework['version'] ?>">
                        <div class="main">
                            <div class="title">
                                <?= $framework['version'] ?>
                                <span><?= getModesInUse($modeLabels, $currentFramework, $framework['version'], $currentFramework) ?></span>
                            </div>
                            <div class="meta"><?= $framework['displayableRelease'] ?></div>
                            <? if ($tagClass): ?>
                                <div data-tooltip="<?= Config::getMessage(THIS_FRAMEWORK_IS_OUT_OF_DATE_MSG) ?>" class="<?= $tagClass ?>"><?= Config::getMessage(OUT_OF_DTE_LBL) ?></div>
                            <? endif; ?>
                        </div>
                        <div class="hide"></div>
                    </div>
                <? endwhile; ?>
                </div>
            </div>
            <div class="col rightPanel">
                <div id="frameworkHeader" class="panelHeader">
                    <div id="frameworkName" class="panelTitle"></div>
                    <div class="controls hide">
                        <a id="updateFrameworkButton" href="javascript:void(0);" class="button"><?= Config::getMessage(START_VERSION_X2192_WIN_X_HK) ?></a>
                    </div>
                </div>
                <div id="frameworkPanel" class="panelContent">
                    <div id="frameworkDetails" class="empty">
                        <div class="emptyMessage"><?= Config::getMessage(SELECT_A_FRAMEWORK_VERSION_LBL) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="history" class="hide">
            <div class="col fullPanel">
                <div id="historyHeader" class="panelHeader"><div class="panelTitle"><?= Config::getMessage(ALL_RECENT_CHANGES_LBL) ?></div></div>
                <div id="historyPanel" class="panelContent">
                    <div class="loading bigwait"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
var allWidgets = <?= json_encode($allWidgets) ?>,
    widgetNames = <?= json_encode($widgetNames) ?>,
    currentVersions = <?= json_encode($declaredVersions) ?>,
    currentFramework = "<?= getMajorMinorVersion($currentFramework['Development']) ?>",
    RightNowCoreAssets = "<?= \RightNow\Utils\Url::getCoreAssetPath() ?>",
    encounteredErrors = <?= $errors ? 'true' : 'false' ?>,
    messages = <?= json_encode(array(
        'allFrameworksSupported'        => Config::getMessage(WIDGET_SUPPORTS_FRAMEWORK_VERSIONS_LBL),
        'attributesLcLabel'             => Config::getMessage(ATTRIBUTES_LC_LBL),
        'availableVersions'             => Config::getMessage(AVAILABLE_VERSIONS_LBL),
        'cancel'                        => Config::getMessage(CANCEL_LBL),
        'categories'                    => array('NEW_FEATURE' => Config::getMessage(NEW_FEATURES_LBL), 'BUG_FIX' => Config::getMessage(BUG_FIXES_LBL), 'API_CHANGE' => Config::getMessage(API_CHANGES_LBL), 'DEPRECATION' => Config::getMessage(REMOVALS_LBL), 'OTHER' => Config::getMessage(OTHER_LBL)),
        'changelogLegendLabel'          => Config::getMessage(CHANGELOG_ENTRY_TYPES_LBL),
        'children'                      => Config::getMessage(IS_THE_PARENT_OF_MSG),
        'close'                         => Config::getMessage(CLOSE_CMD),
        'contains'                      => Config::getMessage(CONTAINS_UC_LBL),
        'currentVersion'                => Config::getMessage(CURRENT_PCT_S_VERSION_LBL),
        'deactivate'                    => Config::getMessage(DEACTIVATE_LBL),
        'deactivateChildren'            => Config::getMessage(FOLLOWING_EXTENDED_WIDGETS_MSG),
        'deactivateConfirm'             => Config::getMessage(YOU_WISH_DEACTIVATE_WIDGET_MSG),
        'deactivateContinue'            => Config::getMessage(WIDGET_YOU_DEACTIVATE_MSG),
        'deactivateFailure'             => Config::getMessage(DEACTIVATING_WIDGET_MSG),
        'deactivateSuccess'             => Config::getMessage(WIDGET_SUCCESSFULLY_DEACTIVATED_MSG),
        'deactivateThisWidget'          => Config::getMessage(DEACTIVATE_THIS_WIDGET_ELLIPSIS_MSG),
        'deactivateWidget'              => Config::getMessage(DEACTIVATE_PCT_S_LBL),
        'delete'                        => Config::getMessage(DELETE_CMD),
        'deleteChildren'                => Config::getMessage(FOLLOWING_EXTENDED_WIDGETS_BROKEN_MSG),
        'deleteConfirm'                 => Config::getMessage(YOU_WISH_DELETE_WIDGET_MSG),
        'deleteContinue'                => Config::getMessage(WIDGET_YOU_DELETE_MSG),
        'deleteExplanation'             => Config::getMessage(DEL_WIDGET_WIDGET_REM_FILE_SYS_MSG),
        'deleteFailure'                 => Config::getMessage(THERE_WAS_PROBLEM_DELETING_WIDGET_MSG),
        'deleteSuccess'                 => Config::getMessage(WIDGET_SUCCESSFULLY_DELETED_MSG),
        'deleteSuccessFiles'            => Config::getMessage(FOLLOWING_FILES_HAVE_BEEN_DELETED_LBL),
        'deleteThisWidget'              => Config::getMessage(DELETE_THIS_WIDGET_ELLIPSIS_CMD),
        'deleteWidget'                  => Config::getMessage(DELETE_PCT_S_CMD),
        'dependencies'                  => Config::getMessage(DEPENDENCIES_LBL),
        'dependencyProvides'            => Config::getMessage(WHICH_REQUIRES_LBL),
        'dependencyRequires'            => Config::getMessage(AND_REQUIRES_LBL),
        'dependencyVersion'             => Config::getMessage(PCT_S_PCT_S_VERSION_PCT_S_COLON_LBL),
        'dependencyVersionPlural'       => Config::getMessage(PCT_S_PCT_S_VERSIONS_PCT_S_COLON_LBL),
        'development'                   => 'Development',
        'displayContainingViews'        => Config::getMessage(DISPLAY_CONTAINING_VIEWS_CMD),
        'displayType'                   => array('custom' => Config::getMessage(CUSTOM_WIDGETS_LBL), 'standard' => Config::getMessage(WIDGET_VIEWS_CONTAINING_WIDGET_LBL), 'view' => Config::getMessage(PAGES_TEMPLATES_CONTAINING_WIDGET_LBL)),
        'documentation'                 => Config::getMessage(DOCUMENTATION_LBL),
        'extends'                       => Config::getMessage(EXTENDS_FROM_UC_LBL),
        'failure'                       => Config::getMessage(AN_ERROR_OCCURRED_LBL),
        'frameworkUpdated'              => Config::getMessage(FRAMEWORK_UPDATED_MSG),
        'frameworkUpdatedWithoutWidgets'=> Config::getMessage(FRMEWORK_UPD_PLS_MAN_UPD_OUTDATED_MSG),
        'inUse'                         => Config::getMessage(CURRENTLY_IN_USE_LBL),
        'lastCheckTime'                 => Config::getMessage(LAST_CHECKED_ON_PCT_S_LBL),
        'less'                          => Config::getMessage(LESS_LC_LBL),
        'levels'                        => array('major' => Config::getMessage(LIKELY_TO_IMPACT_CUSTOM_CODE_LBL), 'minor' => Config::getMessage(MAY_IMPACT_CUSTOM_CODE_LBL), 'nano' => Config::getMessage(NO_IMPACT_TO_CUSTOM_CODE_LBL)),
        'migrationSteps'                => Config::getMessage(MIGRATION_STEPS_LBL),
        'minorUpdates'                  => Config::getMessage(MINOR_UPDATES_LBL),
        'modeLabels'                    => $modeLabels,
        'more'                          => Config::getMessage(MORE_LC_LBL),
        'newUpdates'                    => Config::getMessage(NEW_UPDATES_ARE_AVAILABLE_WIDGET_MSG),
        'newerVersion'                  => Config::getMessage(NEWER_VERSION_LBL),
        'newestVersion'                 => Config::getMessage(NEWEST_VERSION_LBL),
        'next'                          => Config::getMessage(NEXT_LBL),
        'noChangelog'                   => Config::getMessage(CHANGELOG_ENTRIES_VERSION_MSG),
        'noPreview'                     => Config::getMessage(NO_PREVIEW_AVAILABLE_LBL),
        'noRecentChanges'               => Config::getMessage(RECENT_CHANGES_DISPLAY_MSG),
        'noVersionChanges'              => Config::getMessage(NO_RECENT_VERSION_CHANGES_LBL),
        'notActivated'                  => Config::getMessage(THIS_WIDGET_IS_NOT_ACTIVATED_MSG),
        'nothingToUpdate'               => Config::getMessage(WDGETS_RECENT_VERSION_FRAMEWORK_MSG),
        'ok'                            => Config::getMessage(OK_LBL),
        'outOfDate'                     => Config::getMessage(OUT_OF_DTE_LBL),
        'outOfDateTooltip'              => Config::getMessage(NEW_VERSIONS_WIDGET_ARE_AVAILABLE_MSG),
        'previous'                      => Config::getMessage(PREVIOUS_LBL),
        'print'                         => Config::getMessage(PRINT_CMD),
        'recentVersionChanges'          => Config::getMessage(RECENT_VERSION_CHANGES_LBL),
        'requiresFramework'             => Config::getMessage(REQUIRES_FRAMEWORK_PCT_S_LBL),
        'revealedDispInfoMsg'           => Config::getMessage(REVEALED_DISP_TB_DD_OP_ADDTL_T_EXPOSED_MSG),
        'success'                       => Config::getMessage(SUCCESS_LBL),
        'tagGallery'                    => Config::getMessage(TAG_GALLERY_LBL),
        'updateAll'                     => Config::getMessage(UPDATE_ALL_WIDGETS_CMD),
        'updateAllDisclaimer'           => Config::getMessage(UPD_WIDGETS_VERSIONS_FRAMEWORK_MSG),
        'updateFailure'                 => Config::getMessage(ERROR_OCCURED_ATT_UPDATE_WIDGET_MSG),
        'updateFramework'               => Config::getMessage(UPDATE_THE_FRAMEWORK_CMD),
        'updateFrameworkMessage'        => Config::getMessage(FRMEWORK_UPD_ORDER_VERSION_WIDGET_MSG),
        'updateFrameworkWithWidgets'    => Config::getMessage(WIDGET_VERSIONS_CHANGED_COMPATIBLE_MSG),
        'updateFrameworkWithoutWidgets' => Config::getMessage(FOLLOWING_WIDGETS_CLAIM_SUPPORT_MSG),
        'updateToFramework'             => Config::getMessage(UPDATE_FRAMEWORK_VERSION_PCT_S_CMD),
        'updateWidgetsAndFramework'     => Config::getMessage(UPDATE_WIDGETS_AND_FRAMEWORK_CMD),
        'versionNotSupported'           => Config::getMessage(WIDGET_CLAIM_SUPPORT_FRAMEWORK_LBL),
        'viewCodeSnippets'              => Config::getMessage(VIEW_CODE_SNIPPET_LBL),
        'viewCodeSnippetsHeader'        => Config::getMessage(CODE_SNIPPETS_FROM_PCT_S_LBL),
        'viewsUsedOn'                   => Config::getMessage(WIDGET_USAGE_LBL),
        'widgetDependency'              => Config::getMessage(PCT_S_FOLLOWING_DEPENDENCIES_LBL),
        'widgetDependencyWarning'       => Config::getMessage(WIDGET_MAY_ALSO_NEED_BE_UPDATED_MSG),
        'widgetUnusedInView'            => Config::getMessage(WIDGET_DOESNT_APPEAR_ANY_VIEWS_LBL),
        'widgetsAlreadyUpdated'         => Config::getMessage(WIDGETS_RECENT_VERSION_FRAMEWORK_MSG),
        'widgetsNotUpdated'             => Config::getMessage(UPDATING_WIDGETS_PLEASE_TRY_MSG),
        'widgetsUpdated'                => Config::getMessage(WDGTS_RECENT_VERSION_FRAMEWORK_MSG),
        'widgetsUpdatedWithIncompatible'=> Config::getMessage(COMPATIBLE_WIDGETS_RECENT_VERSION_MSG),
        'woefullyTooltip'               => Config::getMessage(FRAMEWORK_UPD_REQD_VERSION_WIDGET_MSG),
    )) ?>;
</script>
