<?php

namespace RightNow\Models;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Internal\Sql\Asset as Sql,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Utils\Config;

/**
 * Methods for handling the retrieval, creation, and updating of Assets.
 */
class Asset extends PrimaryObjectBase {

    /**
     * Constrctor
     */
    public function __construct(){
        parent::__construct('Asset');
    }

    /**
     * Returns an empty Asset structure. Used to be able to access Asset fields without having an Asset ID.
     *
     * @return Connect\Asset An instance of the Connect Asset object
     */
    public function getBlank() {
        return $this->getResponseObject(parent::getBlank());
    }

    /**
     * Returns a Connect Asset object for the specified asset ID.
     * @param int $assetID The ID of the asset
     * @return Connect\Asset|null Asset object on success, else null.
     */
    public function get($assetID) {
        if(!Framework::isLoggedIn()) {
            return $this->getResponseObject(null, null, Config::getMessage(SESSION_EXP_PLEASE_LOGIN_CONTINUE_MSG));
        }

        $asset = parent::get($assetID);
        if(!is_object($asset)){
            return $this->getResponseObject(null, null, $asset);
        }
        if(!$this->isContactAllowedToReadAsset($asset)) {
            return $this->getResponseObject(null, null, Config::getMessage(ACC_DENIED_MSG));
        }
        return $this->getResponseObject($asset);
    }

    /**
     * Creates an Asset. In order to create an asset, a contact must be logged-in.
     * Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Asset.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     * @param int $productID Used in case of non-serialized asset.
     * @param array $formData Form fields to update the asset with.
     * @return Connect\Asset|null Created asset object or null if there are error messages and the asset wasn't created
     */
    public function create($productID, array $formData) {
        if(!Framework::isValidID($productID)) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_ID_SALES_PRODUCT_COLON_LBL));
        }

        $resultSet = Connect\ROQL::queryObject(sprintf("SELECT SalesProduct FROM SalesProduct WHERE ID = %d And Disabled != 1 And Attributes.IsServiceProduct = 1 And Attributes.HasSerialNumber != 1 And AdminVisibleInterfaces.ID = curInterface()", $productID))->next();
        if(!$salesProduct = $resultSet->next()) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_ID_SALES_PRODUCT_COLON_LBL));
        }

        $asset = $this->getBlank()->result;

        if ($contact = $this->getContact()) {
            $asset->Contact = $contact->ID;
        }
        else {
            return $this->getResponseObject(null, null, Config::getMessage(PRODUCT_REGISTERED_PLEASE_LOG_TRY_MSG));
        }

        if($asset->Contact->Organization) {
            $asset->Organization = $asset->Contact->Organization->ID;
        }

        $errors = $warnings = array();
        foreach ($formData as $name => $field) {
            if(!\RightNow\Utils\Text::beginsWith($name, 'Asset')){
                continue;
            }
            $fieldName = explode('.', $name);
            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (\Exception $e) {
                $warnings[] = $e->getMessage();
                continue;
            }

            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors)) {
                $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
                if($setFieldError = $this->setFieldValue($asset, $name, $field->value)) {
                    $errors[] = $setFieldError;
                }
            }
        }
        if($productID !== null && ($setFieldError = $this->setFieldValue($asset, "Asset.Product", $productID))) {
            $errors[] = $setFieldError;
        }
        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        try{
            $asset = parent::createObject($asset, SRC2_EU_ASSET);
        }
        catch(\Exception $e){
            $asset = $e->getMessage();
        }
        if(!is_object($asset)){
            return $this->getResponseObject(null, null, $asset);
        }

        return $this->getResponseObject($asset, 'is_object', null, $warnings);
    }

    /**
     * Updates the specified asset with the given form data. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Asset.Name)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param int $assetID ID of the asset to update
     * @param array $formData Form fields to update the asset with
     * @param string $serialNumber Serial number of product for validation
     * @return Connect\Asset|null Updated asset object containing the asset or error messages if the asset wasn't updated
     */
    public function update($assetID, array $formData, $serialNumber = null) {
        if(!Framework::isLoggedIn()) {
            if($serialNumber !== null) {
                return $this->getResponseObject(null, null, Config::getMessage(PRODUCT_REGISTERED_PLEASE_LOG_TRY_MSG));
            }
            return $this->getResponseObject(null, null, Config::getMessage(REGISTERED_PROD_UPD_PLS_LOG_TRY_MSG));
        }
        $asset = $this->get($assetID);
        if (!$asset->result) {
            // Error: return the ResponseObject
            return $asset;
        }
        $asset = $asset->result;

        if ($serialNumber !== null) {
            $response = $this->validateSerialNumber($serialNumber, $asset->Product->ID); // Revalidating the serial number
            //asset registration
            if(intval($response->result) === intval($assetID)) {
                if ($contact = $this->getContact()) {
                    $asset->Contact = $contact->ID;
                }
                else {
                    return $this->getResponseObject(null, null, Config::getMessage(ASSETS_NEED_BE_ASSOCIATED_CONTACT_MSG));
                }

                if($asset->Contact->Organization) {
                    $asset->Organization = $asset->Contact->Organization->ID;
                }
            }
            else if($response->result === false && ($serialNumber !== null || !$this->isContactAllowedToUpdateAsset($assetID))) {
                return $this->getResponseObject(null, null, Config::getMessage(ACC_DENIED_MSG));
            }
        }

        $errors = $warnings = array();

        foreach ($formData as $name => $field) {
            $fieldName = explode('.', $name);
            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $warnings[] = $e->getMessage();
                continue;
            }

            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors)) {
                $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
                if($setFieldError = $this->setFieldValue($asset, $name, $field->value)) {
                    $errors[] = $setFieldError;
                }
            }
        }
        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        try{
            $asset = parent::updateObject($asset, SRC2_EU_ASSET);
        }
        catch(\Exception $e){
            $asset = $e->getMessage();
        }
        if(!is_object($asset)){
            return $this->getResponseObject(null, null, $asset);
        }
        return $this->getResponseObject($asset, 'is_object', null, $warnings);
    }

    /**
     * Utility method to set the value on the Asset object. Handles more complex types such as Status and Sales Product entries.
     * @param Connect\RNObject $asset Current asset object that is being created/updated
     * @param string $fieldName Name of the field we're setting
     * @param mixed $fieldValue Value of the field.
     * @return null|string Returns null upon success or an error message from parent::setFieldValue upon error.
     */
    protected function setFieldValue(Connect\RNObject $asset, $fieldName, $fieldValue) {
        if($fieldName === 'Asset.StatusWithType.Status') {
            if($fieldValue === null) {
                $asset->StatusWithType->Status->ID = ASSET_ACTIVE; //Set it to ACTIVE Status
            }
            else {
                $asset->StatusWithType->Status->ID = intval($fieldValue);
            }
        }
        else if($fieldName === 'Asset.Product') {
            $asset->Product = Connect\SalesProduct::fetch(intval($fieldValue));
        }
        else {
            return parent::setFieldValue($asset, $fieldName, $fieldValue);
        }
    }

    /**
     * Utility method to get all the Asset statuses (Status of type Unregistered are excluded)
     * @return array|string Returns array of asset status upon success or an error message upon error.
     */
    public function getAssetStatuses() {
        $filteredAssetStatuses = array();
        try{
            $assetStatuses = Connect\ROQL::queryObject("SELECT AssetStatus FROM AssetStatus WHERE AssetStatus.StatusType.ID != " . STATUS_TYPE_ASSET_UNREGISTERED)->next();
            while($assetStatus = $assetStatuses->next()) {
                $filteredAssetStatuses[] = $assetStatus;
            }
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($filteredAssetStatuses, 'is_array');
    }

    /**
     * Utility method to get list of all the assets that are either registered with the contact|organization|subsidiaries depending on Config verb setting.
     * @return array|string Returns array of assets upon success or an error message upon error.
     */
    public function getAssets() {
        $assetArray = array();
        $contact = $this->getContact();
        if(!$contact) {
            return $this->getResponseObject(null, null, Config::getMessage(CONTACT_IS_NOT_LOGGED_IN_MSG));
        }
        try {
            $orgLevelFromConfig = Config::getConfig(MYQ_VIEW_ORG_ASSETS);
            if ($orgLevelFromConfig === 1) {
                $assets = Connect\ROQL::queryObject(sprintf("SELECT Asset FROM Asset WHERE Asset.StatusWithType.StatusType  != " . STATUS_TYPE_ASSET_UNREGISTERED . " AND Organization.ID = %d ORDER BY Name", $contact->Organization->ID))->next();
            }
            else if($orgLevelFromConfig === 2) {
                $orgID = $contact->Organization->ID;
                //Currently there is no better way to fetch the list of assets that belong to contact's org and its subsidiaries. Currently we don't have the information about the depth/levels of an org's subsidiaries. Hence we are forced to check for all possible levels.
                $assets = Connect\ROQL::queryObject(sprintf("SELECT Asset FROM Asset WHERE Asset.StatusWithType.StatusType  != " . STATUS_TYPE_ASSET_UNREGISTERED . " AND
                	                                         (ParentOrganization.ID = $orgID OR
                	                                         ParentOrganization.Parent.ID = $orgID OR
                	                                         ParentOrganization.Parent.Level1 = $orgID OR
                	                                         ParentOrganization.Parent.Level2 = $orgID OR
                	                                         ParentOrganization.Parent.Level3 = $orgID OR
                	                                         ParentOrganization.Parent.Level4 = $orgID OR
                	                                         ParentOrganization.Parent.Level5 = $orgID OR
                	                                         ParentOrganization.Parent.Level6 = $orgID OR
                	                                         ParentOrganization.Parent.Level7 = $orgID OR
                	                                         ParentOrganization.Parent.Level8 = $orgID OR
                	                                         ParentOrganization.Parent.Level9 = $orgID OR
                	                                         ParentOrganization.Parent.Level10 = $orgID) ORDER BY Name"))->next();
            }
            else {
                $assets = Connect\ROQL::queryObject(sprintf("SELECT Asset FROM Asset WHERE Asset.StatusWithType.StatusType  != " . STATUS_TYPE_ASSET_UNREGISTERED . " AND Contact.ID = %d ORDER BY Name", $contact->ID))->next();
            }

            while($asset = $assets->next()) {
                $assetArray[] = $asset;
            }
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($assetArray, 'is_array');
    }

    /**
     * Utility method which performs serial number validation for an Asset
     * Conditions for validations to passP
     * A. An Asset with the serial number should be present in the system
     * B. The Asset should not be assigned to any contact and org.
     * C. Asset should in Unregistered status
     * @param String $serialNumber Serial Number of Sales Product
     * @param String $productID ID of Sales Product
     * @return int|boolean Returns id of asset upon success or false for an invalid serial number.
     */
    public function validateSerialNumber($serialNumber, $productID) {
        try {
            $query = Connect\ROQL::query(sprintf("SELECT Asset.ID, Asset.Contact, Asset.Organization, Asset.StatusWithType.StatusType FROM Asset WHERE Asset.SerialNumber='%s' AND Asset.Product=%d", Connect\ROQL::escapeString($serialNumber), intval($productID)))->next();
            while ($asset = $query->next()) {
                $statusType = $asset['StatusType'];
                if($asset['Contact'] === null && $asset['Organization'] === null) {
                    if(intval($statusType) === STATUS_TYPE_ASSET_UNREGISTERED) {
                        return $this->getResponseObject(intval($asset['ID']), 'is_int');
                    }
                }
            }
            return $this->getResponseObject(false, 'is_bool');
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
    }

    /**
     * Utility function to verify asset viewing based on contact ID
     * @param Connect\Asset $asset A Connect Asset object.
     * @return bool True if contact is allowed to read the asset, false otherwise
     */
    protected function isContactAllowedToReadAsset(Connect\Asset $asset) {
        if (!($contactID = $this->getContact()))
            return false;
        //$asset->Contact === null will be true for unregistered assets
        if (($asset->Contact === null) || $asset->Contact->ID === $contactID->ID) {
            return true;
        }

        $organizationID = $this->CI->session->getProfileData('orgID');
        if (!Framework::isValidID($asset->Organization->ID) || !Framework::isValidID($organizationID)) {
            return false;
        }

        $organizationIDsMatch = ($asset->Organization->ID === $organizationID);
        $orgLevelFromConfig = Config::getConfig(MYQ_VIEW_ORG_ASSETS);
        if ($orgLevelFromConfig === 1) {
            return $organizationIDsMatch;
        }

        if ($orgLevelFromConfig === 2) {
            if ($organizationIDsMatch) {
                return true;
            }
            if (($this->CI->session->getProfileData('orgLevel')) !== false) {
                foreach ($asset->Organization->OrganizationHierarchy as $parentOrg) {
                    if ($organizationID === $parentOrg->ID) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Utility function to verify asset updation based on contact ID
     * @param String $assetID ID of Asset
     * @return bool True if contact is allowed to update the asset, false otherwise
     */
    public function isContactAllowedToUpdateAsset($assetID) {
        $asset = $this->get($assetID)->result;

        if(!is_object($asset)){
            return false;
        }

        return (is_object($asset) && ($contact = $this->getContact()) && $asset->Contact->ID === $contact->ID);
    }
}
