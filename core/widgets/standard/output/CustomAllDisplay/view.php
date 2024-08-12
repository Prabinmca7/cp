<rn:block id="top"/>
<? foreach($this->data['fields'] as $fieldName): ?>
    <rn:block id="preField"/>
    <rn:widget path="output/DataDisplay" name="#rn:php:$fieldName#" sub_id='#rn:php:"display_$fieldName"#'/>
    <rn:block id="postField"/>
<? endforeach; ?>
<rn:block id="bottom"/>
