<?php
namespace RightNow\Widgets;

use RightNow\Utils\Connect,
    RightNow\Utils\Config;

class SelectionInput extends \RightNow\Libraries\Widget\Input {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (parent::getData() === false) return false;

        if(!$this->isValidField()) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_MENU_YES_SLASH_FIELD_MSG), $this->fieldName));
            return false;
        }
        if($this->fieldName === 'SLAInstance' && !\RightNow\Utils\Framework::isLoggedIn()){
            return false;
        }

        $this->data['readOnly'] = $this->data['js']['readOnly'] = $this->data['readOnly'] || $this->data['attrs']['read_only'];

        if(!Connect::isCustomField($this->fieldMetaData)) {
            //standard field
            if($this->table === 'Incident' && $this->fieldName === 'Status') {
                if (!\RightNow\Utils\Url::getParameter('i_id')) {
                    //Status field shouldn't be shown if there is not an incident ID on the page
                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_FLD_DISPLAYED_PG_I_ID_PARAM_MSG), $this->data['attrs']['name']));
                    return false;
                }
                $this->data['menuItems'] = array(\RightNow\Utils\Config::getMessage(YES_PLEASE_RESPOND_TO_MY_QUESTION_MSG), \RightNow\Utils\Config::getMessage(I_DONT_QUESTION_ANSWERED_LBL));
                $this->data['hideEmptyOption'] = true;
                $this->data['displayType'] = 'Select';
            }
        }

        if($this->dataType === 'Boolean') {
            if($this->data['attrs']['display_as_checkbox']) {
                $this->data['displayType'] = 'Checkbox';
                $this->data['constraints']['isCheckbox'] = true;
            }
            else {
                $this->data['displayType'] = 'Radio';
                $this->classList->add('rn_Radio');
            }
            $this->data['radioLabel'] = array(\RightNow\Utils\Config::getMessage(NO_LBL), \RightNow\Utils\Config::getMessage(YES_LBL));
            //find the index of the checked value
            if(in_array($this->data['value'], array(true, 'true', '1'), true))
                $this->data['checkedIndex'] = 1;
            else if(in_array($this->data['value'], array(false, 'false', '0'), true))
                $this->data['checkedIndex'] = 0;
            else
                $this->data['checkedIndex'] = -1;
        }
        else if (!isset($this->data['menuItems']) || !$this->data['menuItems']) {
            $this->data['displayType'] = 'Select';
            $this->data['menuItems'] = $this->getMenuItems();
        }
    }

    /**
     * Validates the field type
     * @return bool True if the field type is valid.
     */
    function isValidField() {
        return in_array($this->dataType, array('Menu', 'Boolean', 'Country', 'NamedIDLabel', 'NamedIDOptList', 'AssignedSLAInstance', 'Status', 'Asset', 'Product'));
    }

    /**
    * Used by the view to output an option's selected attribute.
    * @param int $key Key of the item
    * @return mixed String selected string or null
    */
    public function outputSelected($key) {
        if ($this->table === 'Incident' && $this->fieldName === 'Status' && $this->data['displayType'] === 'Select') {
            $selected = ($key === 0);
        }
        else if ($this->dataType === 'Menu') {
            // Note: double-equal comparison intentional here and below.
            $selected = ($key == $this->data['value']->ID);
        }
        else {
            $selected = ($key == $this->data['value']);
        }
        return $selected ? 'selected="selected"' : null;
    }

    /**
    * Used by the view to output a checkbox / radio input's checked attribute.
    * @param int $currentIndex Index of the loop
    * @return string|null
    */
    public function outputChecked($currentIndex) {
        if ($this->data['checkedIndex'] === $currentIndex) {
            return 'checked="checked"';
        }
    }

    /**
     * Populate the list of options in the select box. Attempts to get the list from the metadata
     * if available and if not, falls back to getting the named ID list from Connect. Also handles
     * the special case of filtering StateOrProvince values based on what Country has been selected
     */
    protected function getMenuItems(){
        $menuItems = array();
        // Select box
        if (!($items = $this->fieldMetaData->named_values)) {
            if($this->fieldName === 'StateOrProvince'){
                //CPHP doesn't appear to have a way to get all of the state/province values
                //given it's parent country (e.g. the getNamedValues function will return all
                //possible state/province values, not just ones off the selected country). Therefore
                //we need to get the country value so we can get the trimmed down list.
                if($this->data['value'] !== null){
                    list($countryValue) = Connect::getObjectField(array('Contact', 'Address', 'Country', 'ID'));
                    // If the value was sent in POST data, use that. Otherwise, use the value from Connect. If neither of those exist look for a URL param.
                    $countryValue = $this->CI->input->post('Contact_Address_Country') ?: $countryValue ?: \RightNow\Utils\Url::getParameter('Contact.Address.Country');
                    if($countryValue && ($stateProvinceList = $this->CI->model('Country')->get($countryValue)->result)){
                        $items = $stateProvinceList->Provinces;
                    }
                }
            }
            else if($this->fieldName === 'Country'){
                // meta data isn't populated for Country
                $items = array();
                $countryItems = $this->CI->model('Country')->getAll()->result;
                // we want the full names and not the ISO codes for countries
                foreach ($countryItems as $countryItem)
                    $items[] = (object)array('ID' => $countryItem->ID, 'LookupName' => $countryItem->Name);
            }
            else if($this->fieldName === 'SLAInstance'){
                //Populate the SLA instances from the Contact record
                $contact = $this->CI->model('Contact')->get()->result;
                //The contact can either have their own SLAs or their org can, but not both
                $contactSlas = ($contact->Organization && $contact->Organization->ID) ? $contact->Organization->ServiceSettings->SLAInstances : $contact->ServiceSettings->SLAInstances;
                $items = array();
                if(Connect::isArray($contactSlas)){
                    foreach($contactSlas as $slaInstance){
                        if($this->isValidSla($slaInstance)){
                            $items[] = $slaInstance->NameOfSLA;
                        }
                    }
                }
            }
            else if($this->table === 'Asset' && $this->fieldName === 'Status'){
                $items = $this->CI->model('Asset')->getAssetStatuses()->result;
            }
            else if($this->table === 'Incident' && $this->fieldName === 'Asset'){
                $items = $this->CI->model('Asset')->getAssets()->result;
            }
            else{
                // meta data isn't populated w/ named values for certain fields
                $field = explode('.', $this->data['inputName']);
                $object = array_shift($field);
                $items = Connect::getNamedValues($object, implode('.', $field));
            }
        }
        if($items){
            foreach ($items as $item) {
                $menuItems[$item->ID] = (isset($item->LookupName) && $item->LookupName !== null) ? $item->LookupName : $item->Name;
            }
        }
        return $menuItems;
    }

    /**
     * Determines if the passed in SLA instance is valid. Checks if the SLA is active, within
     * supported dates, and has remaining web incidents left.
     * @param object $slaInstance Instance of SLA to check
     * @return bool True if the SLA instance is valid; False if it is not.
     */
    protected function isValidSla($slaInstance){
        $currentTime = time();
        return ($slaInstance->StateOfSLA->ID === SLAI_ACTIVE &&
                $slaInstance->ActiveDate <= $currentTime &&
               ($slaInstance->ExpireDate === null || $slaInstance->ExpireDate > $currentTime) &&
               ($slaInstance->RemainingFromWeb === null || $slaInstance->RemainingFromWeb > 0) &&
               ($slaInstance->RemainingTotal === null || $slaInstance->RemainingTotal > 0));
    }
}