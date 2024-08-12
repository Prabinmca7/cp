<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <rn:block id="preLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Loading"></div>
    <rn:block id="postLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_Content">
        <rn:block id="topContent"/>
        <? if (is_array($this->data['reportData']['data']) && count($this->data['reportData']['data']) > 0): ?>
        <rn:block id="preResultList"/>
        <? if ($this->data['reportData']['row_num']): ?>
            <ol start="<?=$this->data['reportData']['start_num'];?>">
        <? else: ?>
            <ul>
        <? endif; ?>
        <rn:block id="topResultList"/>
        <? $reportColumns = count($this->data['reportData']['headers']);
           foreach ($this->data['reportData']['data'] as $value): ?>
            <rn:block id="resultListItem">
            <li>
                <? for ($i = 0; $i < $reportColumns; $i++): ?>
                    <? $header = $this->data['reportData']['headers'][$i]; ?>
                    <? if ($this->showColumn($value[$i], $header)):
                        if ($i < 3):
                            if ($i === 0): ?>
                                <div class="rn_Element<?=$i + 1?>"><h3><?=$value[$i];?></h3></div>
                            <? else: ?>
                                <span class="rn_Element<?=$i + 1?>"><?=$value[$i];?></span>
                            <? endif; ?>
                        <? else: ?>
                            <span class="rn_ElementsHeader"><?=$this->getHeader($header);?></span>
                            <span class="rn_ElementsData"><?=$value[$i];?></span>
                        <? endif; ?>
                    <? endif; ?>
                <? endfor; ?>
            </li>
            </rn:block>
        <? endforeach; ?>
        <rn:block id="bottomResultList"/>
        <? if ($this->data['reportData']['row_num']): ?>
            </ol>
        <? else: ?>
            </ul>
        <? endif; ?>
        <rn:block id="postResultList"/>
        <? endif; ?>
        <rn:block id="bottomContent"/>
    </div>
    <rn:block id="bottom"/>
</div>
