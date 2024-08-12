<? use \RightNow\Utils\Config; ?>
<h2><?=Config::getMessage(WEBDAV_LOGS_LBL);?></h2>

<?if($this->uri->segment(4)):?>
    <?foreach($archived as $log):?>
        <?if($this->uri->segment(4) === $log['name']):?>
            <div id="headline"><h3>
                <?=sprintf(Config::getMessage(ARCHIVED_LOG_FROM_PCT_S_LBL), $log['time']);?>&nbsp;&nbsp;(<a href="/ci/admin/logs/webdav"><?=Config::getMessage(VIEW_CURRENT_LOG_CMD);?></a>)
            </h3></div>
        <?endif;?>
    <?endforeach;?>
<?endif;?>

<br/>
<div><?= isset($searchControls) ? $searchControls : '';?></div>
<br/>
<div id="webdavLogs"></div>

<?if(count($archived)):?>
    <br/><br/>
    <div class="box">
        <h3 class="noMargin"><?=Config::getMessage(VIEW_ARCHIVED_LOGS_CMD);?></h3>
        <div style="font-size:90%"><?=Config::getMessage(DATE_CORRESPONDS_DAY_LOG_ARCHIVED_MSG);?></div>
        <br/>
        <?foreach($archived as $log):?>
            <?if($this->uri->segment(4) === $log['name']):?>
                <?=$log['time'];?><br>
            <?else:?>
                <a href="/ci/admin/logs/webdav/<?=$log['name'];?>"><?=$log['time'];?></a><br>
            <?endif;?>
        <?endforeach;?>
    </div>
<?endif;?>

<script>window.logData = <?= json_encode($table); ?></script>
