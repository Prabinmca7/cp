<rn:block id="top"/>
<?
if($this->data['attrs']['initial_focus_on_first_field']):
   $firstValue = true;
endif;
?>
<? foreach($this->data['fields'] as $channelField => $channelFieldName): ?>
   <?
    if($firstValue):
        $initialFocus = 'true';
        $firstValue = false;
    else:
        $initialFocus = 'false';
    endif;
    $name = "Contact.ChannelUsernames.{$channelField}.Username";
    $label = sprintf($this->data['attrs']['label_input'], $channelFieldName);
    ?>
    <rn:widget path="input/TextInput" name="#rn:php:$name#" initial_focus="#rn:php:$initialFocus#" label_input="#rn:php:$label#" sub_id='#rn:php:"input_$channelField"#'/>
<? endforeach; ?>
<rn:block id="bottom"/>
