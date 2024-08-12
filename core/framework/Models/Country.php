<?php

namespace RightNow\Models;

use RightNow\Utils\Framework,
    RightNow\Connect\v1_4 as Connect;

/**
 * Methods for handling the retrieval of Country objects.
 */
class Country extends Base
{
    /**
     * Function to return Connect Country object given its ID
     *
     * @param int $countryID Country ID
     * @return Connect\Country|null Connect Country object or null if no country was found or ID was invalid.
     */
    public function get($countryID)
    {
        if(!Framework::isValidID($countryID)){
            return $this->getResponseObject(null, null, "Invalid Country ID: $countryID");
        }
        try{
            $response = Connect\Country::fetch($countryID);
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($response);
    }

    /**
     * Function to return all Connect Country objects within the DB
     *
     * @return array Array of Connect Country objects
     */
    public function getAll()
    {
        $countriesArray = array();
        try{
            $countries = Connect\ROQL::queryObject("SELECT Country FROM Country ORDER BY DisplayOrder")->next();

            while($country = $countries->next()) {
                $countriesArray[] = $country;
            }
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($countriesArray, 'is_array');
    }

    /**
     * Check if the given state and country pair is valid.
     * @param int $stateID The state ID
     * @param int $countryID The country ID
     * @return boolean True if the pair is valid, false otherwise
     */
    public function validateStateAndCountry($stateID, $countryID) {
        if($stateProvinceList = $this->get($countryID)->result) {
            foreach($stateProvinceList->Provinces as $stateOrProvince) {
                if($stateOrProvince->ID === intval($stateID)) {
                    return true;
                }
            }
        }
        return false;
    }
}
