<?
namespace RightNow\Internal\Libraries\OpenLogin;

require_once CPCORE . 'Internal/Libraries/OpenLogin/OpenIDUser.php';

/**
 * Represents a Google user.
 */
class GoogleUser extends OpenIDUser {
    protected $id;
    protected $avatarUrl;

    /**
     * Returns a mapping of local properties to set with the values
     * of property data received from Google.
     * ({@link https://developers.google.com/accounts/docs/OAuth2LoginV1})
     * @return array mapping of fields to set
     */
    protected function thirdPartyFieldMapping() {
        return array(
            'email'        => 'email',
            'openIDUrl'    => 'profile',
            'firstName'    => 'given_name',
            'lastName'     => 'family_name',
            'avatarUrl'    => 'picture',
            'id'           => 'sub'
        );
    }
}
