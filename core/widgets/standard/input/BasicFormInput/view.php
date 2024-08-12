<rn:block id="top"/>
<? switch ($this->dataType):
    case 'Menu':
    case 'Boolean':
    case 'Country':
    case 'NamedIDLabel':
    case 'NamedIDOptList':
    case 'AssignedSLAInstance':?>
        <rn:widget path="input/BasicSelectionInput" sub_id="selection"/>
        <? break;
    case 'Date':
    case 'DateTime':?>
        <rn:widget path="input/BasicDateInput" sub_id="date"/>
        <? break;
    default: ?>
        <? if ($this->fieldName === 'NewPassword'): ?>
            <rn:widget path="input/BasicPasswordInput" sub_id="password"/>
        <? else: ?>
            <rn:widget path="input/BasicTextInput" sub_id="text"/>
        <? endif; ?>
        <? break;
endswitch;?>
<rn:block id="bottom"/>
