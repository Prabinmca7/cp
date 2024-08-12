<h2><?=\RightNow\Utils\Config::getMessage(PAGE_META_TAGS_LBL);?>&nbsp;&nbsp;&lt;rn:meta ... /&gt;</h2>
<p><?=$meta->description;?></p>

<div class="box">
    <b><?=\RightNow\Utils\Config::getMessage(ANS_DET_PG_ANS_S_DETAIL_RN_META_TAG_MSG);?>:</b><br />
    &lt;rn:meta title="#rn:msg:_MY_PAGE_LABEL_#" template="standard.php" answer_details="true" clickstream="answer_view"/&gt;
</div>

<h3><?=\RightNow\Utils\Config::getMessage(ATTRIBUTES_LBL);?></h3>
<?foreach($meta->attributes as $attr):?>
<h4 id="<?= $attr->value ?>"><?=$attr->value;?></h4>
<ul>
    <li><b><?=\RightNow\Utils\Config::getMessage(NAME_LBL);?>:</b> <?=$attr->name?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(TYPE_LBL);?>:</b> <?=$attr->type?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(DESCRIPTION_LBL);?>:</b> <?=$attr->tooltip?></li>
    <?if($attr->default === true):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(TRUE_LBL);?></li>
    <?elseif($attr->default === false):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(FALSE_LBL);?></li>
    <?elseif($attr->default):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=$attr->default?></li>
    <?endif;?>
    <?if(count($attr->options)):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(POSSIBLE_VALUES_LBL);?>:</b>
            <ul>
            <?foreach($attr->options as $option):?>
                <li>
                    <?=$option->value;?>
                    <?if(is_array($option->deprecated)):?>
                        <span class="warning">[<?=sprintf(\RightNow\Utils\Config::getMessage(DEPRECATED_SINCE_PCT_S_LBL), $option->deprecated[0]);?>]</span>
                    <?endif;?>
                </li>
            <?endforeach;?>
            </ul>
        </li>
    <?endif;?>
    <?if($attr->min):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MN_LBL);?>:</b> <?=$attr->min?></li>
    <?endif;?>
    <?if($attr->max):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MAX_LBL);?>:</b> <?=$attr->max?></li>
    <?endif;?>
    <?if($attr->length):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(LENGTH_LBL);?>:</b> <?=$attr->length?></li>
    <?endif;?>
</ul>
<?endforeach;?>
