<?php
namespace RightNow\Libraries;

use RightNow\Utils\Config;

require_once CPCORE . 'Libraries/BoundedObjectBase.php';

/**
 * Collection of constants and methods to handle all the various errors that might occuring during the
 * OpenLogin process.
 */
final class OpenLoginErrors extends BoundedObjectBase {
    const USER_REJECTION_ERROR = 1;
    const INVALID_EMAIL_ERROR = 2;
    const COOKIES_REQUIRED_ERROR = 3;
    const AUTHENTICATION_ERROR = 4;
    const TWITTER_API_ERROR = 5;
    const CONTACT_DISABLED_ERROR = 6;
    const FACEBOOK_PROXY_EMAIL_ERROR = 7;
    const CONTACT_LOGIN_ERROR = 8;
    const OPENID_INVALID_PROVIDER_ERROR = 10;
    const OPENID_RESPONSE_INVALID_PROVIDER_ERROR = 11;
    const OPENID_RESPONSE_INSUFFICIENT_DATA_ERROR = 12;
    const OPENID_CONNECT_ERROR = 13;
    const SAML_TOKEN_REQUIRED = 14;
    const SAML_TOKEN_FORMAT_INVALID = 15;
    const FEDERATED_LOGIN_FAILED = 16;
    const SSO_CONTACT_TOKEN_VALIDATE_FAILED = 17;
    const SAML_SUBJECT_INVALID = 18;
    const GOOGLE_NO_OPENID_URL = 19;

    /**
     * Returns an error message that maps to error codes found in framework.php
     * defined in getErrorMessageFromCode which displays errors on the error page
     * @param int $errorCode One of the class constants defined above
     * @return string Error code from getErrorMessageFromCode
     * @internal
     */
    static function mapOpenLoginErrorsToPageErrors($errorCode) {
        return $errorCode === self::COOKIES_REQUIRED_ERROR ? 'saml19' : 'saml18';
    }

    /**
     * Returns an error message corresponding to the error code.
     * @param int $errorCode One of the class constants defined above
     * @return string Error message or empty string if the error code isn't valid
     * @internal
     */
    static function getErrorMessage($errorCode) {
        switch((int) $errorCode){
            case self::USER_REJECTION_ERROR:
                return Config::getMessage(ACC_ACCT_INFO_ORDER_LOGIN_DONT_LBL);
            case self::INVALID_EMAIL_ERROR:
                return Config::getMessage(EMAIL_WAS_PROVIDED_IS_INVALID_MSG);
            case self::COOKIES_REQUIRED_ERROR:
                return Config::getMessage(COOKIES_ENABLED_BROWSER_ORDER_LOG_MSG);
            case self::AUTHENTICATION_ERROR:
                return Config::getMessage(ERR_WERENT_EXPECTING_HAPPENED_SORRY_MSG);
            case self::TWITTER_API_ERROR:
                return Config::getMessage(TWITTER_EXPERIENCING_PLS_TRY_CHOOSE_MSG);
            case self::CONTACT_DISABLED_ERROR:
                return Config::getMessage(SORRY_THERES_ACCT_PLS_CONT_SUPPORT_MSG);
            case self::FACEBOOK_PROXY_EMAIL_ERROR:
                return Config::getMessage(RCVD_FACEBOOK_PROXY_EMAIL_ADDR_REAL_MSG);
            case self::CONTACT_LOGIN_ERROR:
                return Config::getMessage(LOGGING_ACCT_DISABLED_PLEASE_TRY_MSG);
            case self::OPENID_INVALID_PROVIDER_ERROR:
                return Config::getMessage(OPENID_URL_INV_PLS_DOUBLE_CHECK_URL_MSG);
            case self::OPENID_RESPONSE_INVALID_PROVIDER_ERROR:
                return Config::getMessage(COMMUNICATING_OPENID_PROVIDER_PLS_MSG);
            case self::OPENID_RESPONSE_INSUFFICIENT_DATA_ERROR:
                return Config::getMessage(INFO_OPENID_PROVIDER_VERIFY_PLS_MSG);
            case self::OPENID_CONNECT_ERROR:
                return Config::getMessage(CONN_OPENID_PROVIDER_PLS_TRY_CHOOSE_MSG);
            case self::GOOGLE_NO_OPENID_URL:
                return Config::getMessage(LOG_PLS_LG_PR_LG_PLS_BF_TT_GN_NTF_PRVDR_MSG);
            default:
                return '';
        }
    }
}
