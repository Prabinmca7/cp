<?php

namespace RightNow\Utils;

use \RightNow\Internal\Libraries\OpenLogin as OpenLoginLibrary;

require_once CPCORE . 'Controllers/Openlogin.php';

/**
 * Methods for retrieving data using third-party access tokens
 */
final class OpenLoginUserInfo
{
    const FB_CONTACT_API_URL = 'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name';

    /**
     * Retrieve user's info from Facebook's API.
     * @param string $accessToken A valid Facebook access token
     * @return object Object containing the user's profile info and email.
     */
    public static function getFacebookUserInfo($accessToken){
        $client = new \RightNow\Controllers\Client();

        //This is an object containing the user's id, firstName, lastName, email, and other info
        $userInfo = json_decode($client->get(self::FB_CONTACT_API_URL . '&access_token=' . $accessToken));

        return new OpenLoginLibrary\FacebookUser($userInfo);
    }

}
