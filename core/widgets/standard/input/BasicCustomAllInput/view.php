<rn:block id="top"/>
<? foreach($this->data['fields'] as $fieldName):?>
    <rn:widget path="input/BasicFormInput" name="#rn:php:$fieldName#" sub_id='#rn:php:"input_$fieldName"#'/>
<? endforeach;?>
<rn:block id="bottom"/>
