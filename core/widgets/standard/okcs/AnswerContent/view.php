<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if ($this->data['answer']['docID'] !== null && $this->data['answer']['docType'] !== 'HTML') : ?>
        <div id="content">
            <? foreach ($this->data['answer']['data'] as $data): ?>
                <? $previousDepth = $currentIndex = 1;?>
                <? if ($data['type'] === 'META'): ?>
                    <div class="rn_SectionTitle"></div>
                <? endif; ?>
                <? foreach ($data['content'] as $attribute): ?>
                    <? if ($attribute['depth'] > 0) : ?>
                        <? $value = $attribute['value']; ?>
                        <? if ($attribute['type'] === 'FILE') : ?>
                            <? $value = $attribute['position'] . '#' . $attribute['value']; ?>
                        <? endif; ?>
                        <? if($attribute['depth'] > $previousDepth ) : ?>
                            <div class="rn_Indent">
                        <? endif; ?>
                        <? if($attribute['depth'] < $previousDepth) : ?>
                            <? $diff = $previousDepth - $attribute['depth']; ?>
                            <? for($i = $diff; $i > 0; $i--) : ?>
                                </div>
                            <? endfor; ?>
                        <? endif; ?>
                        <div class="rn_OverflowContent">
                            <rn:widget path="okcs/AnswerField" type="#rn:php:$attribute['type']#" value="#rn:php:$value#" label="#rn:php:$attribute['name']#" xpath="#rn:php:$attribute['xPath']#" sub_id="AnswerField">
                        </div>
                        <? if (count($data['content']) === $currentIndex) : ?>
                            <? for($i = $attribute['depth']; $i > 1; $i--) : ?>
                                </div>
                            <? endfor; ?>
                        <? endif; ?>
                        <? $previousDepth = $attribute['depth']; ?>
                    <? endif; ?>
                    <? $currentIndex++; ?>
                <? endforeach; ?>
            <? endforeach; ?>
        </div>
    <? elseif ($this->data['answer']['content'] !== null) : ?>
        <div class="rn_ExternalContent"><?= $this->data['answer']['content'] ?></div>
    <? else : ?>
        <iframe class="rn_ExternalContent" src="<?= $this->data['answer']['docUrl'] ?>"></iframe>
    <? endif ?>
    <rn:block id="bottom"/>
</div>
