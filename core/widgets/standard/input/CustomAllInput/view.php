<rn:block id="top"/>
<?$initialFocus = ($this->data['attrs']['initial_focus_on_first_field']) ? 'true' : 'false';?>
<? foreach($this->data['fields'] as $fieldName):?>
    <rn:widget path="input/FormInput" name="#rn:php:$fieldName#" initial_focus="#rn:php:$initialFocus#" sub_id='#rn:php:"input_$fieldName"#'/>
    <? $initialFocus = 'false';
endforeach;?>
<rn:block id="bottom"/>
