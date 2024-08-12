<div class="rn_BasicFormStatusDisplay">
<rn:block id="top"/>
<? if($this->data['messages']): ?>
    <rn:block id="preTopHeader"/>
    <hr/><hr/>
    <rn:block id="postTopHeader"/>
    <? if($this->data['attrs']['label']): ?>
        <rn:block id="preLabel"/>
        <div><?=$this->data['attrs']['label']?></div>
        <rn:block id="postLabel"/>
    <? endif; ?>
    <rn:block id="preMessages"/>
    <? foreach($this->data['messages'] as $type => $types): ?>
        <? foreach($types as $field => $items): ?>
            <div class="rn_BasicFormStatusDisplay_<?=$type?>">
            <rn:block id="preField"/>
            <span class="rn_BasicFormStatusDisplay_Field"><?=$field;?></span>
            <rn:block id="postField"/>
            <? foreach($items as $item): ?>
                <rn:block id="preMessage"/>
                <div><?=($field === '') ? $item : " - $item";?></div>
                <rn:block id="postMessage"/>
            <? endforeach; ?>
            <br/></div>
        <? endforeach; ?>
    <? endforeach; ?>
    <rn:block id="postMessages"/>
    <rn:block id="preBottomHeader"/>
    <hr/><hr/>
    <rn:block id="postBottomHeader"/>
<? endif; ?>
<rn:block id="bottom"/>
</div>
