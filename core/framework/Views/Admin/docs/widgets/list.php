<h2><?=\RightNow\Utils\Config::getMessage(WIDGETS_LBL);?></h2>

<?$this->load->view('Admin/docs/widgets/breadcrumb'); ?>
<? if(isset($topfolders) && is_array($topfolders) && count($topfolders)):
      sort($topfolders); ?>
    <ul>
        <?foreach($topfolders as $folder):?>
            <li>
                <a href='<?= "/ci/admin/docs/{$folder['path']}" ?>'><?=$folder['name'];?></a>
                <?if(isset($folder['warnMessage']) && $folder['warnMessage']):?>
                    <span class="warning">[<?=$folder['warnMessage'];?>]</span>
                <?endif;?>
            </li>
        <?endforeach;?>
    </ul>
<? elseif($isRoot): ?>
    <h3><a href="/ci/admin/docs/widgets/standard"><?=\RightNow\Utils\Config::getMessage(STANDARD_WIDGETS_LBL);?></a></h3>
    <p><?=\RightNow\Utils\Config::getMessage(CONT_INFO_PRE_BUILT_WIDGETS_CUST_MSG);?></p>
    <h3><a href="/ci/admin/docs/widgets/custom"><?=\RightNow\Utils\Config::getMessage(CUSTOM_WIDGETS_LBL);?></a></h3>
    <p><?=\RightNow\Utils\Config::getMessage(CNT_INFO_CUST_WIDGETS_BUILT_AVAIL_MSG);?></p>
<? else: ?>
    <p><?= sprintf(\RightNow\Utils\Config::getMessage(WIDGETS_DIRECTORY_COMPATIBLE_MSG), CP_FRAMEWORK_VERSION); ?></p>
<? endif; ?>