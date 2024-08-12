<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <a id="rn_<?=$this->instanceID;?>_Link" href="javascript:void(0);" title="<?=$this->data['attrs']['label_tooltip']?>">
        <span><?=$this->data['attrs']['label_link'];?></span>
    </a>
    <div id="rn_<?=$this->instanceID;?>_Panel" class="rn_Panel rn_Hidden">
        <rn:block id="preList"/>
        <ul>
        <? for ($i = 0; $i < count($this->data['sites']); $i++): ?>
            <rn:block id="listItem">
            <li class="rn_Link<?=$i + 1;?>">
                <a href="<?=$this->data['sites'][$i]['link'];?>" target="_blank" title="<?=$this->data['sites'][$i]['title'];?>"><?=$this->data['sites'][$i]['name'];?></a>
            </li>
            </rn:block>
        <? endfor; ?>
        </ul>
        <rn:block id="postList"/>
    </div>
    <rn:block id="bottom"/>
</div>
