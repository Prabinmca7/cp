<?
namespace RightNow\Internal\Libraries\OpenLogin;

require_once CPCORE . 'Internal/Libraries/OpenLogin/User.php';

/**
 * Represents an OpenID user.
 */
class OpenIDUser extends User {
    protected $openIDUrl;

    /**
     * Specific fields to save in Connect for OpenID.
     * @return array Connect field name to object with value
     */
    function serviceSpecificFields() {
        return array(
            'Contact.OpenIDAccounts.0.URL' => (object) array('value' => $this->openIDUrl),
        );
    }

    /**
     * Returns a mapping of local properties to set with the values
     * of property data received from Twitter.
     * ({@link https://dev.twitter.com/docs/api/1.1/get/users/show/})
     * @return array mapping of fields to set
     */
    protected function thirdPartyFieldMapping() {
        return array(
            'email'        => 'contact/email',
            'openIDUrl'    => 'openIDUrl',
            'firstName'    => $this->extractName('first'),
            'lastName'     => $this->extractName('last'),
        );
    }

    /**
     * Returns a function to be used to extract a first or last name from
     * the `namePerson` field.
     * @param  string $part Part of the name to be extracted (first or last)
     * @return function       Returns a string portion of the name required
     */
    private function extractName($part) {
        return function($userInfo) use ($part) {
            if ($name = $userInfo["namePerson/{$part}"]) return $name;

            $name = explode(' ', $userInfo['namePerson'] ?: $userInfo['namePerson/friendly'], 2);
            if ($part === 'first') {
                return $name[0];
            }

            return $name[1] ?: $name[0];
        };
    }
}
