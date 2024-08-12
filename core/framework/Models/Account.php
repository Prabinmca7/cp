<?php

namespace RightNow\Models;

use RightNow\Connect\v1_4 as Connect;

/**
 * Methods for retrieving agent accounts
 */
class Account extends Base
{
    /**
     * Gets details about a certain account given its ID
     *
     * @param int $accountID The account ID to lookup
     * @return Connect\Account|null Account object or null if no account was found
     */
    public function get($accountID)
    {
        if(!\RightNow\Utils\Framework::isValidID($accountID)){
            return $this->getResponseObject(null, null, "Invalid Account ID: " . var_export($accountID, true));
        }
        try{
            $account = Connect\Account::fetch($accountID);
        }
        catch (Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($account);
    }
}