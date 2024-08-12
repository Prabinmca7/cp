<?use RightNow\Utils\Config;?>
<ul>
    <li>
        <div class="rn_LinksBlock"><a href="/ci/admin/overview/set_cookie/development/basic"><?=Config::getMessage(VIEW_DEVELOPMENT_AREA_CMD);?></a></div>
    </li>
    <? if($stagingAreas):
        $href = array_keys($stagingAreas);
        $href = $href[0];?>
    <li>
        <div class="rn_LinksBlock">
            <a class="rn_LinksBlock" href="/ci/admin/overview/set_cookie/<?=$href;?>/basic"><?=Config::getMessage(VIEW_STAGING_AREA_CMD);?></a>
        </div>
    </li>
    <?endif;?>
    <li>
        <div class="rn_LinksBlock"><a href="/ci/admin/overview/set_cookie/production/basic"><?=Config::getMessage(VIEW_PRODUCTION_AREA_CMD);?></a></div>
    </li>
</ul>
