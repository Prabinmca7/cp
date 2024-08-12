<?php

namespace RightNow\Controllers;

use RightNow\Internal\Libraries\Encryption,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

require_once(CPCORE .  'Internal/Libraries/Encryption.php');
require_once(CPCORE . 'Internal/Libraries/ConnectMetaData.php');

/**
 * !! -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- IMPORTANT -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- !!
 * !! This controller should NOT be checked in to cp/core/framework/Controllers,    !!
 * !! but instead manually copied in to sites for PTA testing.                      !!
 * !! -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- IMPORTANT -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- !!
 *
 * Diagnostic controller designed to generate contacts for use in PTA tests.
 */
class EncryptionGenerator extends \RightNow\Controllers\Base {
    private $minimal = false;
    private $basename = null;
    private $configs;
    private $encryptConfigs;
    public function __construct() {
        parent::__construct();
        $this->setPtaConfigs();
        $this->encryptConfigs = array(
            'secret_key'         => 'secretKey',
            'encryption_method'  => 'encryptionMethod',
            'encryption_keygen'  => 'keygenMethod',
            'encryption_padding' => 'paddingMethod',
            'encryption_salt'    => 'salt',
            'encryption_iv'      => 'initializationVector',
        );

        // Map Connect fields to their p_{name} PTA parameter.
        // The order of the items below determines their order on the form.
        $this->fieldMappings = array(
            'Login'                   => 'userid',
            'Name.First'              => 'first_name',
            'Name.Last'               => 'last_name',
            'NewPassword'             => 'passwd',
            'Emails.PRIMARY.Address'  => 'email',
            'Emails.ALT1.Address'     => 'email_alt1',
            'Emails.ALT2.Address'     => 'email_alt2',
            'Title'                   => 'title',
            'NameFurigana.First'      => 'alt_first_name',
            'NameFurigana.Last'       => 'alt_last_name',
            'Address.City'            => 'city',
            'Address.Country'         => 'country_id',
            'Address.PostalCode'      => 'postal_code',
            'Address.Street'          => 'street',
            'Address.StateOrProvince' => 'state',
            'Phones.OFFICE.Number'    => 'ph_office',
            'Phones.MOBILE.Number'    => 'ph_mobile',
            'Phones.FAX.Number'       => 'ph_fax',
            'Phones.ASST.Number'      => 'ph_asst',
            'Phones.HOME.Number'      => 'ph_home',
        );

        // 2 for VTBL_CONTACTS and 4 for VIS_ENDUSER_EDIT_RW. Used integers directly
        // as this file doesn't get compiled.
        $this->customFieldItems = Framework::getCustomFieldList(2, 4);
    }

    /**
     * Generate a form containing PTA setting and Contact fields used to generate a PTA token
     *
     * Usage:
     *   /ci/encryptionGenerator/generate/minimal - Generates a form with the minimal contact fields.
     *   /ci/encryptionGenerator/generate/basename/{name} - Generates a form having the contact fields pre-populated with {name.field}
     * @param Boolean $minimal
     */
    public function generate() {
        $minimal = Url::getParameter('minimal');
        // For backwards compatibility, support simply having generate/minimal or generate/minimal/[true|false]
        if ($minimal === 'true' || $minimal === '1' || ($minimal === '' && Url::getParameterString() === '/minimal/')) {
            $this->minimal = true;
        }
        else {
            $this->minimal = false;
        }

        $this->basename = Url::getParameter('basename');

        $this->printPtaConfigs();
        if ($post = $this->getPost()) {
            $this->printTokenDetails($post[0], $post[1]);
        }
        echo "<hr><p/>" . $this->contactForm($this->minimal ? array('email', 'passwd', 'userid') : array());
    }

    /**
     * If you set PTA_ERROR_URL to /ci/encryptionGenerator/error/%error_code%,
     * you can test that various error-generating conditions are handled properly by PTA
     *
     * @param Integer $code
     */
    public function error($code) {
        $out = '';
        $errorStrings = array(
            1 => "No PTA parameter found",
            2 => "Failed pre_pta_decode hook format check",
            3 => "Failed base64 decode",
            4 => "Invalid data format found",
            5 => "No userid parameter found",
            6 => "Incorrect p_li password sent",
            7 => "Unable to login (usually bad password)",
            8 => "PTA not enabled on site",
            9 => "Failed decryption",
            10 => "Unsupported encryption method",
            11 => "Unsupported padding method",
            12 => "Unsupported keygen method",
            13 => "Must use encryption",
            14 => "Failed pre_pta_convert hook format check",
            15 => "Password Length Exceeded",
            16 => "Token Expired",
            17 => "Duplicate Emails Within Contact",
            18 => "Salt Value Too Long",
        );
        $out .= "<h1>Error Code: {$code}</h1>";
        $out .= "<h2>Description: ";
        if (isset($errorStrings[$code])) {
            $out .= $errorStrings[$code];
        } else {
            $out .= "No record of this error number";
        }
        $out .= "</h2>";
        echo $out;
    }

    /**
     * Calculates the input value for the specified Contact field.
     * @param string $field The Contact field name
     * @param mixed $value The Contact field's value
     * @return mixed The value to display in the input field.
     */
    private function getContactFieldValue($field, $value) {
        if ($value === null && ($alias = $this->fieldMappings[$field]) && isset($_POST["p_{$alias}"])) {
            return $_POST["p_{$alias}"];
        }

        if ($this->basename && $value === null) {
            $suffix  = strtolower(str_replace('_', '.', $field));
            if (Text::beginsWith($field, 'Emails.')) {
                return "{$this->basename}@{$suffix}.invalid";
            }

            if ($field === 'Address.PostalCode' || Text::beginsWith($field, 'Phones.')) {
                return rand(10000, 99999);
            }

            if ($field === 'Address.Country') {
                return 1;
            }

            return "{$this->basename}_{$suffix}";
        }

        return $value;
    }

    /**
     * Prints the contact fields.
     * @param Array $limit
     */
    private function contactForm(array $limit = array()) {
        $contact = $this->getCurrentContact();
        $inputs = '';
        foreach ($this->getContactFields($contact) as $field => $value) {
            if (!$limit || in_array($this->convertPtaParameters($field), $limit)) {
                $inputs .= $this->generateInputField($field, $field, $this->getContactFieldValue($field, $value));
            }
        }

        if ($this->getEncryptionMethod() === '') {
            $inputs .= $this->generateInputField('PTA Password (no encryption)', 'li_passwd', '');
        }

        return $this->generateForm($this->getConfigInputs(), $inputs);
    }

    /**
     * Returns input fields for select PTA configs.
     * @returns String
     */
    private function getConfigInputs() {
        $inputs = '';
        foreach(array_keys($this->encryptConfigs) as $key) {
            $slot = 'PTA_' . strtoupper($key);
            $inputs .= $this->generateInputField($slot, $slot,  $this->configs[$key]);
        }
        return $inputs;
    }

    /**
     * Sets PTA configs to $this->configs.
     */
    private function setPtaConfigs() {
        // As this file does not get define-replaced, we hard code the slot numbers below.
        $configs = array(
            'enabled'                    => 372,
            'secret_key'                 => 381,
            'encryption_method'          => 374,
            'encryption_keygen'          => 373,
            'encryption_padding'         => 375,
            'encryption_salt'            => 844,
            'encryption_iv'              => 843,
            'error_url'                  => 376,
            'external_login_url'         => 377,
            'external_logout_script_url' => 378,
            'external_post_logout_url'   => 379,
            'ignore_contact_password'    => 380,
        );
        foreach ($configs as $key => $value) {
            $this->configs[$key] = \RightNow\Utils\Config::getConfig($value);
        }
    }

    /**
     * Prints PTA configs and their values.
     */
    private function printPtaConfigs() {
        $table = '<table>';
        $table .= "<tr><td><strong>PTA CONFIG</strong></td><td><strong>VALUE</strong></td></tr>";
        foreach ($this->configs as $config => $value) {
            $slot = 'PTA_' . strtoupper($config);
            $extra = $value ? $this->getAdditionalConfigInfo($config, $value) : '';
            $table .= "<tr><td>$slot</td><td><strong>{$value}</strong>&nbsp;&nbsp;{$extra}</td></tr>";
        }
        $table .= '</table><hr>';
        echo $table;
    }

    /**
     * Decodes hex $value.
     * $param String $value.
     * $returns String
     */
    private function decodeHexValue($value) {
        return pack("H*", $value);
    }

    /**
     * Returns RANDOM_* or hex decoded value for SALT or IV.
     * @param String $config
     * @param String $value
     * @returns String
     */
    private function getValueForSaltOrIV($config, $value) {
        if (strtolower($value) === 'encoded') {
            // Returning hard-coded values for RSSL_[SALT|IV]STR_RANDOM below as this script does not get define-replaced.
            return $config === 'encryption_salt' ? 'RANDOM_SALT' : 'RANDOM_IV';
        }
        return $this->decodeHexValue($value);
    }

    /**
     * Returns additional info to print next to select configs
     * $param String $config
     * $param Mixed $value
     * $return String|Null
     */
    private function getAdditionalConfigInfo($config, $value) {
        if ($config === 'encryption_salt' || $config === 'encryption_iv') {
            $label = strtolower($value) === 'encoded' ? '' : 'hex decoded: ';
            $value = $this->getValueForSaltOrIV($config, $value);
            return "({$label}{$value})";
        }
        if ($config === 'encryption_keygen') {
            $mapping = array(
                1 => 'RSSL_KEYGEN_PKCS5_V15',
                2 => 'RSSL_KEYGEN_PKCS5_V20',
                3 => 'RSSL_KEYGEN_NONE',
                9 => 'RSSL_KEYGEN_NONE',
            );
            return "(" . $mapping[$value] . ")";
        }
        if ($config === 'encryption_padding') {
            $mapping = array(
                1 => 'RSSL_PAD_PKCS7',
                2 => 'RSSL_PAD_NONE',
                3 => 'RSSL_PAD_ZERO',
                4 => 'RSSL_PAD_ISO10126',
                5 => 'RSSL_PAD_ANSIX923',
            );
            return "(" . $mapping[$value] . ")";
        }
    }

    /**
     * Returns an array where element 0 is the PTA token sent in the POST, and element 1 is the optional redirect.
     * @return Array|Null
     */
    private function getPost() {
        $token = $redirect = '';
        foreach ($_POST as $key => $value) {
            if ($value !== null && $value !== "" && !Text::beginsWith($key, 'PTA_')) {
                if ($key === 'redirect') {
                    $redirect = str_replace('/app/', '', $value);
                    continue;
                }
                if ($token !== '') {
                    $token .= '&';
                }
                if ($value === 'NONE') {
                    $value = '';
                }
                $token .= "$key=$value";
            }
        }
        if ($token && ($secretKey = $this->configs['secret_key'])) {
            $token .= "&p_li_passwd={$secretKey}";
        }

        if ($token) {
            return array($token, $redirect);
        }
    }

    /**
     * Returns pta_encryption_method
     * @returns String
     */
    private function getEncryptionMethod() {
        return isset($_POST['PTA_ENCRYPTION_METHOD']) ? $_POST['PTA_ENCRYPTION_METHOD'] : $this->configs['encryption_method'];
    }

    /**
     * Prints the details and links for $ptaToken and $redirect.
     * @param String $ptaToken
     * @param String $redirct
     */
    private function printTokenDetails($ptaToken, $redirect) {
        echo '<h3>Data passed in [' . Text::getMultibyteStringLength($ptaToken) . " bytes]</h3>$ptaToken";
        if ($encryptionMethod = $this->getEncryptionMethod()) {
            $args = $this->getEncryptArgs($encryptionMethod, $ptaToken);
            printf("<h3>ske_buffer_encrypt args</h3><pre>%s</pre><br>", htmlspecialchars(var_export($args, true)));
            list($ptaToken, $error) = Encryption::apiEncrypt($args);
            if ($error) {
                echo "<br><font color='red'>$error</font>";
            }
            echo "<h3>Data encrpyted (no base64 encoding)</h3>$ptaToken<p>";
        }
        $base64Encoded = base64_encode($ptaToken);
        echo "<h3>PHP base64 encoded</h3>$base64Encoded<p>";
        $safeEncoded = strtr($base64Encoded, array('+' => '_', '/' => '~', '=' => '!'));
        echo "<h3>URL safe encoded</h3>$safeEncoded<p>";
        echo '<h3>Links (/ci/pta/login/redirect)</h3>';
        $link = "<a href='/ci/pta/login/redirect/%s/p_li/$safeEncoded'>%s</a><br/>";
        if ($redirect) {
            printf($link, $redirect, 'Custom Redirect Set on Form');
        }
        printf($link, 'home', 'Home Page');
        printf($link, 'ci/about', 'CP About Page');
    }

    /**
     * Returns arguments to ske_buffer_encrypt.
     * @param String $encryptionMethod
     * @param String $ptaToken
     * @returns Array
     */
    private function getEncryptArgs($encryptionMethod, $ptaToken) {
        $args = Encryption::getApiCryptArgs($encryptionMethod, $ptaToken);
        foreach($this->encryptConfigs as $key => $arg) {
            $slot = 'PTA_' . strtoupper($key);
            if (isset($_POST[$slot])) {
                $value = trim($_POST[$slot]);
                if ($key === 'encryption_salt' || $key === 'encryption_iv') {
                    $value = $this->getValueForSaltOrIV($key, $value);
                }
                else if ($key === 'encryption_keygen' || $key === 'encryption_padding') {
                    $value = intval($value);
                    if ($key === 'encryption_keygen' && $value === 3) {
                        $value = 9; // RSSL_KEYGEN_NONE
                    }
                }
                $args[$arg] = ($value === '' || $value === null) ? null : $value;
            }

        }
        return $args;
    }

    /**
     * Returns an array of contact fields.
     * @param Object $contact
     * @return Array A key-value array of 'field-name' => 'field-value'.
     */
    private function getContactFields($contact) {
        $instance = \RightNow\Internal\Libraries\ConnectMetaData::getInstance();
        $data = $instance->getMetaData();
        $fields = $sorted = array();

        foreach ($data['Contact'] as $field => $meta) {
            $meta = $meta['metaData'];
            if (!$meta->is_read_only_for_update && ($this->fieldMappings[$field] || Text::beginsWith($field, 'CustomFields'))) {
                $fields[$field] = $contact->{$field};
            }
        }

        foreach ($this->fieldMappings as $field => $ptaParam) {
            if (array_key_exists($field, $fields)) {
                $value = $fields[$field];
                unset($fields[$field]);
                $sorted[$field] = $value;
            }

        }

        return  array_merge($sorted, $fields);
    }

    /**
     * Returns a Connect Contact object.
     * @return Object
     */
    private function getCurrentContact() {
        if (Framework::isLoggedIn()) {
            return $this->model('Contact')->get($this->session->getProfileData('c_id'))->result;
        }
        return $this->model('Contact')->getBlank()->result;
    }

    /**
     * Generates the HTML for a table row containing the specified label and value.
     * @param String $label
     * @param String $name
     * @param Mixed $value
     * @return String
     */
    private function generateInputField($label, $name, $value) {
        $ptaParam = $this->convertPtaParameters($name);
        $ptaLabel = '';
        if (!Text::beginsWith($name, 'PTA_')) {
            if ($ptaParam !== $name) {
                $ptaLabel = "&nbsp;&nbsp;<small>(p_{$ptaParam})</small>";
            }
            $ptaParam = "p_{$ptaParam}";
        }

        return "<tr><td><strong>$label</strong>$ptaLabel</td><td><input type=\"text\" name=\"$ptaParam\" value=\"{$value}\" size=\"80\"/></td>";
    }

    /**
     * Search for custom field in customFieldItems array and return its ID.
     * @param string $customField Custom field to search for
     * @return int|boolean Custom field ID if a field is found, false otherwise
     */
    private function _customFieldIDSearch($customField) {
        foreach($this->customFieldItems as $key => $value) {
            if(is_array($value) && $value['col_name'] === ('c$' . substr($customField, strrpos($customField, '.' ) + 1))) {
                return $value['cf_id'];
            }
        }
        return false;
    }

    /**
     * Returns the PTA parameter name for supported fields.
     * @param String $name
     * @return String The mapped parameter name.
     */
    private function convertPtaParameters($name) {
        if ($parameter = $this->fieldMappings[$name]) {
            return $parameter;
        }

        if (Text::beginsWith($name, 'CustomFields') && ($customFieldID = $this->_customFieldIDSearch($name))) {
            return "ccf_{$customFieldID}";
        }

        return $name;
    }

    /**
     * Displays $content in a form.
     * @param String $content
     * @return String An HTML form.
     */
    private function generateForm($configInputs, $contactInputs) {
        $post = $this->minimal ? '/minimal/true' : '';
        $post .= $this->basename ? "/basename/{$this->basename}" : '';
        return <<< END
        <form method='post' action='/ci/encryptionGenerator/generate{$post}'>
            <table>
                <tr><td><strong>Encrypt Args</strong></td><td><small>(Used to optionally pass in different values to ske_buffer_encrypt)</small></td></tr>
                {$configInputs}
                <tr><td colspan="2">&nbsp;</td></tr>
                <tr><td colspan="2"><strong>Contact Fields</strong></td></tr>
                {$contactInputs}
                <tr><td><br/></td><td></td></tr>
                <tr><td></td><td><input type='submit'/></td>
            </table>
        </form>
END;
    }
}
