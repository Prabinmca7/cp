<rn:block id="top"/>
<? foreach($this->data['fields'] as $channelField => $channelFieldName):
    $name = "Contact.ChannelUsernames.{$channelField}.Username";
    $label = sprintf($this->data['attrs']['label_input'], $channelFieldName);?>
    <rn:block id="preField"/>
    <rn:widget path="output/DataDisplay" name="#rn:php:$name#" label="#rn:php:$label#" sub_id='#rn:php:"display_$channelField"#'/>
    <rn:block id="postField"/>
<? endforeach; ?>
<rn:block id="bottom"/>
