<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <span class="rn_ScreenReaderOnly"><?=sprintf($this->data['attrs']['label_screen_reader_progress'], $this->data['attrs']['current_step'], $this->data['totalSteps']);?></span>
    <rn:block id="preList"/>
    <ol class="rn_<?=$this->data['totalSteps'];?>Elements">
    <rn:block id="preLoop"/>
    <? for($i = 1; $i < count($this->data['stepDescriptions']); $i++):
        $statusClass = "rn_IncompleteStep";
        if($i < $this->data['attrs']['current_step']){
            $statusClass = "rn_CompleteStep";
        }
        else if($i == $this->data['attrs']['current_step']){
            $statusClass = "rn_CurrentStep";
        }
        $itemPositionClass = "rn_MiddleStep";
        if ($i === 1)
            $itemPositionClass = "rn_FirstStep";
        else if ($i === $this->data['totalSteps'])
            $itemPositionClass = "rn_LastStep";
        ?>
        <rn:block id="listItem">
        <li class="rn_ProgressStep <?=$statusClass;?> <?=$itemPositionClass;?>">
            <span><?=$this->data['stepDescriptions'][$i];?></span>
        </li>
        </rn:block>
    <? endfor;?>
    <rn:block id="postLoop"/>
    </ol>
    <rn:block id="postList"/>
    <rn:block id="bottom"/>
</div>
