<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if($this->data['attrs']['label_title']):?>
    <span class="rn_Title"><?=$this->data['attrs']['label_title'];?></span>
    <? endif;?>
    <? $index = 1;
    foreach($this->data['results'] as $key => $value):?>
    <rn:block id="preItem"/>
    <div class="rn_HierList rn_HierList_<?=$key;?>">
    <a href="<?=$this->data['itemLink'] . $value['hierList'];?>"><?=htmlspecialchars($value['label'], ENT_QUOTES, 'UTF-8');?></a>
        <? if(count($value['subItems'])):?>
        <rn:block id="preSubList"/>
        <ul>
        <? for($i = 0; $i < count($value['subItems']); $i++):?>
        <rn:block id="listItem">
        <li><a href="<?=$this->data['itemLink'] . $value['subItems'][$i]['hierList'];?>"><?=htmlspecialchars($value['subItems'][$i]['label'], ENT_QUOTES, 'UTF-8');?></a></li>
        </rn:block>
        <? endfor;?>
        </ul>
        <rn:block id="postSubList"/>
        <? endif;?>
    </div>
    <rn:block id="postItem"/>
    <? $index++;
        endforeach;?>
    <rn:block id="bottom"/>
</div>
