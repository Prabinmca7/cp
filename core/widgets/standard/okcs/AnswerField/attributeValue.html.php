<div class='rn_SchemaAttributeValue'>
    <? if ($type === 'FILE') : ?>
    <? if ($this->data['fileType'] === 'NONE') : ?>
        <a target='_blank' href="/ci/okcsFattach/get/<?=$this->data['value'];?>"><?=$this->data['fileName'];?></a>
    <? elseif ($this->data['fileType'] === 'IMAGE') : ?>
        <img src="/ci/okcsFattach/get/<?=$this->data['value'];?>" alt="<?=$this->data['fileName'];?>"></img>
    <? elseif($this->data['fileType'] === 'VIDEO') : ?>
        <video controls>
        <source src="/ci/okcsFattach/get/<?=$this->data['value'];?>">
        <a target='_blank' href="/ci/okcsFattach/get/<?=$this->data['value'];?>"><?=$this->data['fileName'];?></a>
        <?= $this->data['attrs']['label_no_support_video_tag'] ?>
        </video>
    <? elseif ($this->data['fileType'] === 'AUDIO') : ?>
        <audio controls>
        <source src="/ci/okcsFattach/get/<?=$this->data['value'];?>">
        <a target='_blank' href="/ci/okcsFattach/get/<?=$this->data['value'];?>"><?=$this->data['fileName'];?></a>
        <?= $this->data['attrs']['label_no_support_audio_tag'] ?>
        </audio>
     <? else : ?>
        <a target='_blank' href="/ci/okcsFattach/get/<?=$this->data['value'];?>"><?=$this->data['fileName'];?></a>
    <? endif; ?>
    <? elseif($type === 'LIST') : ?>
        <? $listValues = explode(',', $value); ?>
        <? foreach ($listValues as $value): ?>
            <div class="rn_ListOption"><?= $value ?></div>
        <? endforeach; ?>
    <? elseif ($type !== 'CHECKBOX') : ?>
        <?= $value ?>
    <? endif; ?>
</div>