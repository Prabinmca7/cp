<?php

namespace RightNow\Internal\Sql;

use RightNow\Utils\Framework;

final class Contact
{
    /**
     * Function to check if organization id and password are valid
     *
     * @param string $login The entered organization login
     * @param string $password The entered organization password
     * @return mixed The ID of the organization or false if credentials were invalid
     */
    public static function getOrganizationIDFromCredentials($login, $password)
    {
        $si = sql_prepare(sprintf("SELECT org_id, password_encrypt FROM orgs WHERE login = '%s'", Framework::escapeForSql($login)));

        sql_bind_col($si, 1, BIND_INT, 0);
        sql_bind_col($si, 2, BIND_BIN, 61);

        $row = sql_fetch($si);
        sql_free($si);
        if(!$row || ($row[1] !== pw_rev_encrypt(htmlspecialchars_decode($password, ENT_NOQUOTES))))
            return false;
        return $row[0];
    }

    /**
     * Checks if the contacts existing password was entered correctly
     * Note: repeatedly failing to validate a contact's current password can result in disabling that contact, unless the contact is already logged in
     * Assert: only called when contact is changing password, not resetting.
     * @param int $contactID The ID of the contact record to check
     * @param string $passwordToCheck The password sent in by the user
     * @return boolean True if passwords match
     */
    public static function checkOldPassword($contactID, $passwordToCheck)
    {
        // A non-recoverable internal API error is triggered when the `password_text` value's length is greater than 20.
        // Password values are not allowed to exceed 20 characters, so anything exceeding that length is invalid.
        if (\RightNow\Utils\Text::getMultibyteStringLength($passwordToCheck) > 20) return false;

        return (\RightNow\Internal\Api::contact_password_verify(
            array('c_id' => (int)$contactID, 'password_text' => $passwordToCheck)) === 1);
    }

    /**
     * Returns the password hash stored in contacts.password
     * @param int $contactID Contact ID
     * @return string|null String password hash or Null if no password has been set.
     */
    public static function getPasswordHash($contactID) {
        return sql_get_str(sprintf('SELECT password_hash FROM contacts WHERE c_id = %d', $contactID), 61) ?: null;
    }
}
