<? if($readOnly && $versionDataSelection === '0'): ?>
    <strong><?=$versionsLabel;?></strong>
    <br>
    <div class="info"><?=$changesNotPushedLabel;?></div>
<? else: ?>
    <strong><?=$phpUpgradesLabel;?></strong>
    <? if ($phpVersionsData): ?>
    <div id="phpVersionContent"><div class="wait"></div></div>
    <br>
    <? else: ?>
    <div class="info"><?= \RightNow\Utils\Config::getMessage(THERE_PHP_VERSION_DIFFERENCES_DISPLAY_MSG) ?></div>
    <? endif; ?>
    
    <strong><?=$frameworkUpgradesLabel;?></strong>
    <? if ($frameworkVersionsData): ?>
    <div id="frameworkVersionContent"><div class="wait"></div></div>
    <br>
    <? else: ?>
    <div class="info"><?= \RightNow\Utils\Config::getMessage(FRAMEWORK_DIFFERENCES_DISPLAY_MSG) ?></div>
    <? endif; ?>
    <strong><?=$widgetUpgradesLabel;?></strong>
    <? if ($widgetVersionsData['source'] && $widgetVersionsData['destination']): ?>
    <div id="widgetVersionsContent"><div class="wait"></div></div>
    <? else: ?>
    <div class="info"><?= \RightNow\Utils\Config::getMessage(WIDGET_DIFFERENCES_DISPLAY_MSG) ?></div>
    <? endif; ?>
    <? if(!$readOnly):?>
    <br>
    <label for="versionAction"><strong><?=$pushChangesLabel;?></strong></label>
    <br>
        <? if($actualVersionChanges):?>
        <select id="versionAction" onchange='versionActionChange(this);'>
            <option value='0'<?= $versionDataSelection === '0' ? ' selected' : '';?>><?=$noLabel;?></option>
            <option value='1'<?= $versionDataSelection === '1' ? ' selected' : '';?>><?=$yesLabel;?></option>
        </select>
        <? else:
            $versionDataSelection = '0';?>
        <select id="versionAction" onchange='versionActionChange(this);' disabled>
            <option value='0'><?=\RightNow\Utils\Config::getMessage(NO_VERSION_UPDATES_TO_DEPLOY_LBL);?></option>
        </select>
        <? endif;?>
    <? endif;?>
    <br>
<? endif; ?>
<script>
var VersionData = <?= json_encode(array(
    'selection'         => $versionDataSelection,
    'framework' => array(
        'source'  => isset($frameworkVersionsData['source']) ? $frameworkVersionsData['source'] : array(),
        'dest'    => isset($frameworkVersionsData['destination']) ? $frameworkVersionsData['destination'] : array(),
        'columns' => $frameworkColumnLabels,
        'summary' => \RightNow\Utils\Config::getMessage(FRAMEWORK_VERSION_DIFFERENCES_LBL),
        'label' => 'frameworkVersion',
    ),
    'php' => array(
        'source'  => isset($phpVersionsData['source']) ? $phpVersionsData['source'] : array(),
        'dest'    => isset($phpVersionsData['destination']) ? $phpVersionsData['destination'] : array(),
        'columns' => $phpColumnLabels,
        'summary' => \RightNow\Utils\Config::getMessage(FRAMEWORK_VERSION_DIFFERENCES_LBL),
        'label'   => 'phpVersion'
    ),
    'widgets' => array(
        'source'  => $widgetVersionsData['source'],
        'dest'    => $widgetVersionsData['destination'],
        'columns' => $widgetColumnLabels,
        'summary' => \RightNow\Utils\Config::getMessage(WIDGET_VERSION_DIFFERENCES_LBL),
    ),
)); ?>;
</script>
