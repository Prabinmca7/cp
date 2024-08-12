<? use \RightNow\Utils\Config; ?>
<div id="container">
    <h2 class="title"><?=Config::getMessage(CODE_ASSISTANT_BACKUPS_LBL)?></h2>
    <p><?=sprintf(Config::getMessage(CONTENTS_PCT_S_DIRECTORY_CR_CODE_MSG), sprintf((!$useBackupUrl) ? '%s' : '<a href="/dav/cp/generated/temp_backups" target="_blank">%s</a>', 'cp/generated/temp_backups'));?></p>
    <div class="note">
        <?=Config::getMessage(NOTE_BACKUPS_SIX_MONTHS_AUTO_REM_MSG);?>
    </div>
    <? if(count($backups)): ?>
        <ul class="collapsible-list enabled">
        <? foreach($backups as $backup): ?>
            <li class="item">
                <div class="header corner">
                    <div class="title">
                        <a href="javascript:void(0)"><?= $backup['prefixMessage'] . ' - ' . $backup['directory']; ?></a>
                        <span class="fa fa-plus"></span>
                    </div>
                </div>
                <div class="description">
                <ul>
                    <? foreach($backup['files'] as $file): ?>
                        <li><a href="/dav/<?=$file['davPath'];?>" target="_blank"><?=$file['visibleText'];?></a></li>
                    <? endforeach; ?>
                </ul>
                </div>
            </li>
        <? endforeach; ?>
        </ul>
    <? else: ?>
        <p><?=Config::getMessage(NO_BACKUPS_AVAILABLE_LBL);?></p>
    <? endif; ?>
</div>
