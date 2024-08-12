<rn:block id="top"/>
<? switch ($this->dataType):
    case 'Menu':
    case 'Boolean':
    case 'Country':
    case 'NamedIDLabel':
    case 'NamedIDOptList':
    case 'Status':
    case 'Asset':
    case 'AssignedSLAInstance':?>
        <rn:widget path="input/SelectionInput" sub_id="selection"/>
        <? break;
    case 'Date':
    case 'DateTime':?>
        <rn:widget path="input/DateInput" sub_id="date"/>
        <? break;
    default: ?>
        <? if ($this->fieldName === 'NewPassword'): ?>
            <rn:widget path="input/PasswordInput" sub_id="password"/>
        <? elseif ($this->fieldName === 'DisplayName' && $this->table === 'Communityuser'): ?>
            <rn:widget path="input/DisplayNameInput" sub_id="displayname"/>
        <? else: ?>
            <rn:widget path="input/TextInput" sub_id="text"/>
        <? endif; ?>
<? endswitch;?>
<rn:block id="bottom"/>