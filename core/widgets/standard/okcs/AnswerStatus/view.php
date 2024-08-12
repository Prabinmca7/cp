<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
            <div class="rn_AnswerDetail rn_AnswerHeader">
                <div class="rn_AnswerInfo">
                <? foreach ($this->data['customMetadata'] as $dataArray): ?>
                    <rn:widget path="okcs/AnswerField" answer_key="#rn:php:$dataArray['answer_key']#" label="#rn:php:$dataArray['label']#" value="#rn:php:$dataArray['value']#" sub_id="#rn:php:$dataArray['answer_key']#"/>
                <? endforeach; ?>
                </div>
            </div>
    <rn:block id="bottom"/>
</div>