<?
namespace RightNow\Internal\Libraries\OpenLogin;

require_once CPCORE . 'Internal/Libraries/OpenLogin/User.php';

/**
 * Represents a Twitter user.
 */
class TwitterUser extends User {
    protected $id;
    protected $userName;
    protected $avatarUrl;

    /**
     * Specific fields to save in Connect for Twitter.
     * @return array Connect field name to object with value
     */
    function serviceSpecificFields() {
        return array(
            'Contact.ChannelUsernames.TWITTER.Username'   => (object) array('value' => $this->userName),
            'Contact.ChannelUsernames.TWITTER.UserNumber' => (object) array('value' => $this->id),
        );
    }

    /**
     * Returns a mapping of local properties to set with the values
     * of property data received from Twitter.
     * ({@link https://dev.twitter.com/docs/api/1.1/get/account/verify_credentials})
     * @return array mapping of fields to set
     */
    protected function thirdPartyFieldMapping() {
        return array(
            'id'        => 'id_str',
            'userName'  => 'screen_name',
            'avatarUrl' => 'profile_image_url',
            'firstName' => $this->extractName(0),
            'lastName'  => $this->extractName(1),
            'email'     => 'email'
        );
    }

    /**
     * Returns a function to be used to extract a first or last name from
     * Twitter's single `name` field.
     * @param  number $part Part of the name to be extracted (0 or 1)
     * @return function       Returns a string portion of the name required
     */
    private function extractName($part) {
        return function($userInfo) use ($part) {
            $name = explode(' ', $userInfo->name, 2);
            return $name[$part] ?: $name[0];
        };
    }
}
