<?
namespace RightNow\Internal\Libraries\OpenLogin;

use RightNow\Utils\Text;

/**
 * Represents a generic OpenLogin user whose properties are intended
 * to be mapped onto Contact and CommunityUser properties.
 */
class User {
    const MAX_CONTACT_NAME_LENGTH = 80; //schema restriction

    /**
     * Email
     */
    protected $email;
    /**
     * Last name
     */
    protected $lastName;
    /**
     * First name
     */
    protected $firstName;
    /**
     * Raw user info
     */
    protected $rawUserInfo;

    /**
     * Convert properties into contact model expected values
     */
    private $contactConversion = array(
        'Contact.Name.First'             => 'firstName',
        'Contact.Name.Last'              => 'lastName',
        'Contact.Emails.PRIMARY.Address' => 'email',
        'Contact.Login'                  => 'email',
    );
    /**
     * Convert properties into social user model expected values
     */
    private $socialUserConversion = array(
        'Communityuser.DisplayName'         => 'userName',
        'Communityuser.AvatarURL'           => 'avatarUrl',
    );

    /**
     * Constructor.
     * @param array|object $userInfo Properties and values to set.
     * The properties that are set are defined by the array returned
     * by #thirdPartyFieldMapping
     */
    function __construct($userInfo = array()) {
        if ($userInfo) {
            $this->rawUserInfo = $userInfo;
            foreach ($this->thirdPartyFieldMapping() as $localPropertyName => $thirdPartyProperty) {
                if (is_callable($thirdPartyProperty)) {
                    $value = $thirdPartyProperty($userInfo);
                }
                else if (is_object($userInfo)) {
                    $value = $userInfo->{$thirdPartyProperty};
                }
                else if(is_array($userInfo) && array_key_exists($thirdPartyProperty, $userInfo)) {
                    $value = $userInfo[$thirdPartyProperty];
                }

                $this->__set($localPropertyName, $value);
            }
        }
    }

    /**
     * Setter. Truncates first and last names if values are longer than what's acceptable.
     * @param  string $name  Property name
     * @param  string $value Property value
     * @return string The newly-set value
     */
    function __set($name, $value) {
        if (($name === 'firstName' || $name === 'lastName') && Text::getMultibyteStringLength($value) > self::MAX_CONTACT_NAME_LENGTH) {
            $value = Text::truncateText($value, self::MAX_CONTACT_NAME_LENGTH, false);
        }

        return $this->{$name} = (string) $value;
    }

    /**
     * Getter.
     * @param  string $name Property name
     * @return string       Property value
     */
    function __get($name) {
        return $this->{$name};
    }

    /**
     * Returns an array of fields that the contact model
     * expects.
     * @return array fields
     */
    function getRawUserInfo() {
        return $this->rawUserInfo;
    }

    /**
     * Returns an array of fields that the contact model
     * expects.
     * @return array fields
     */
    function toContactArray() {
        return $this->arrayWithMappedValues($this->contactConversion) + $this->serviceSpecificFields();
    }

    /**
     * Returns an array of fields that the social user model
     * expects.
     * @return array fields
     */
    function toSocialUserArray() {
        return $this->arrayWithMappedValues($this->socialUserConversion);
    }

    /**
     * Returns an array of Contact fields and values are specific to the
     * service being implemented.
     * Hook method that subclasses should implement.
     * @return array fields
     */
    function serviceSpecificFields() {
        return array();
    }

    /**
     * Returns an array:
     * key names are local property names to set.
     * values are either
     * - string property name on the third-party user info to use
     * - callable function where the third-party user info is sent
     *   to calculate a value to return
     * Hook method that subclasses should implement.
     * @return array mapping
     */
    protected function thirdPartyFieldMapping() {
        return array();
    }

    /**
     * Populates an array whose keys and values correspond to the
     * Connect field names and values that a model expects
     * @param  array $mapping Conversion info
     * @return array          populated info
     */
    private function arrayWithMappedValues($mapping) {
        $populated = array();

        foreach ($mapping as $contactField => $userField) {
            if (isset($this->{$userField})) {
                $populated[$contactField] = (object) array('value' => $this->{$userField});
            }
        }

        return $populated;
    }
}
