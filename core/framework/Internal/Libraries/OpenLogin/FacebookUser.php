<?
namespace RightNow\Internal\Libraries\OpenLogin;

require_once CPCORE . 'Internal/Libraries/OpenLogin/User.php';

/**
 * Represents a Facebook user.
 */
class FacebookUser extends User {
    const FB_PROFILE_PIC_URL = 'https://graph.facebook.com/%s/picture?return_ssl_resources=1&type=large';

    public $id;
    public $userName;
    public $avatarUrl;

    /**
     * Specific fields to save in Connect for Facebook.
     * @return array Connect field name to object with value
     */
    function serviceSpecificFields() {
        return array(
            'Contact.ChannelUsernames.FACEBOOK.Username'   => (object) array('value' => $this->userName ?: $this->email),
            'Contact.ChannelUsernames.FACEBOOK.UserNumber' => (object) array('value' => $this->id),
        );
    }

    /**
     * Returns a mapping of local properties to set with the values
     * of property data received from Facebook.
     * ({@link http://developers.facebook.com/docs/reference/api/user/})
     * @return array mapping of fields to set
     */
    protected function thirdPartyFieldMapping() {
        return array(
            'id'        => 'id',
            'email'     => 'email',
            'userName'  => 'name',
            'firstName' => 'first_name',
            'lastName'  => 'last_name',
            'avatarUrl' => function($userInfo) {
                return sprintf(FacebookUser::FB_PROFILE_PIC_URL, $userInfo->id);
            },
        );
    }

}
