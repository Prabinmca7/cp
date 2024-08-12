<?php

namespace RightNow\Utils;

/**
 * Methods for retrieving and checking both config settings and messagebase values.
 */
final class Config extends \RightNow\Internal\Utils\Config
{
    /**
     * Returns a messagebase value given its slot ID
     *
     * @param int $id The messagebase ID
     * @return string The messagebase value.
     */
    public static function getMessage($id)
    {
        return \Rnow::getMessage($id);
    }

    /**
     * Temporary wrapper for getMessage() to allow backporting of code from the new "gettext"
     * style calls in the Dynamic Upgrades project, currently slated for v12.2.
     *
     * @param string $message The actual message string.
     * @param string $context The old msgbase define as a string. e.g. 'OPEN_LBL' or 'OPEN_LBL:RNW'
     * @return string
     * @internal
     */
    public static function msg($message, $context = null) {
        list($define) = explode(':', $context);
        if (defined($define) && $defineValue = constant($define)) {
            $value = self::getMessage($defineValue);
            //value equals null means, the custom messagebase no more exists
            if($value === null ){
                return sprintf(self::getMessage(UNKNOWN_MESSAGE_SLOT_PCT_S_LBL), $define);
            }
            return $value;
        }
        return $message;
    }

    /**
     * Temporary wrapper for getMessage() to allow backporting of code from the new "gettext"
     * style calls in the Dynamic Upgrades project, currently slated for v12.2.
     *
     * @param string $message The actual message string.
     * @param string $context The old msgbase define as a string. e.g. 'OPEN_LBL' or 'OPEN_LBL:RNW'
     * @return string
     * @internal
     */
    public static function msgJS($message, $context = null) {
        return Text::escapeStringForJavaScript(self::msg($message, $context));
    }

    /**
     * Returns a messagebase value, escaped for JavaScript, given its slot ID.
     *
     * @param int $id The messagebase ID
     * @return string The messagebase value.
     */
    public static function getMessageJS($id)
    {
        return Text::escapeStringForJavaScript(self::getMessage($id));
    }

    /**
     * Placeholder function used to mark strings that need to be
     * translated. This function merely returns the string that is
     * passed in.
     *
     * @param string $string The message base value
     * @return string The message base value
     * @internal
     */
    public static function ASTRgetMessage($string)
    {
        return $string;
    }

    /**
     * Placeholder function used to mark strings that need to be
     * translated. This function merely returns the string that is
     * passed in, escaped for JavaScript.
     *
     * @param string $string The message base value
     * @return string The message base value
     * @internal
     */
    public static function ASTRgetMessageJS($string)
    {
        return Text::escapeStringForJavaScript($string);
    }

    /**
     * Attempts to retrieve a message from the given message base.  The message
     * can be specified by a number or a string.
     *
     * @param int $id The numeric id/name of the message base
     * @param string $name The name of the custom message base with hyphens instead of underscores
     * @return string The message from the message base
     * @internal
     */
    public static function msgGetFrom($id, $name = null)
    {
        if (is_numeric($id))
            $numericID = $id;
        else
            $numericID = defined($id) ? constant($id) : null;

        //Attempts to determine if the slot doesn't exist, but can be easily fooled.
        if ($numericID === null || $numericID < 1)
            return sprintf(self::getMessage(UNKNOWN_MESSAGE_SLOT_PCT_S_LBL), $id);

        $value = self::getMessage($numericID);
        //value equals null means, the custom messagebase no more exists
        if($value === null ){
            return sprintf(self::getMessage(UNKNOWN_MESSAGE_SLOT_PCT_S_LBL), ($name !== null) ? str_replace("-", "_", $name) : $id);
        }
        return $value;
    }

    /**
     * Retrieves a config base value given the slot name and config
     * base file. This function will automatically return the value
     * in the correct data type.
     *
     * @param int $id The config base ID
     * @return mixed The value of the config slot name
     */
    public static function getConfig($id)
    {
        return \Rnow::getConfig($id);
    }

    /**
     * Attempts to retrieve a config from the specified number or string.
     * Attempts to determine if the slot doesn't exist, but can be easily fooled.
     *
     * @param int $id ID number of the config
     * @return mixed The config from the config base
     * @internal
     */
    public static function configGetFrom($id)
    {
        if (is_numeric($id))
            $numericID = $id;
        else
            $numericID = defined($id) ? constant($id) : null;

        if ($numericID === null || $numericID < 1)
            return sprintf(self::getMessage(UNKNOWN_CONFIG_SLOT_PCT_S_LBL), $id);

        return self::getConfig($numericID);
    }

    /**
     * Retrieves a config base value given the slot name and escapes it for JavaScript.
     * This function will automatically return the value in the correct data type.
     *
     * @param int $id The config base ID
     * @return mixed The value of the config slot name
     */
    public static function getConfigJS($id)
    {
        $configValue = self::getConfig($id);
        if(is_string($configValue))
            return Text::escapeStringForJavaScript($configValue);
        return $configValue;
    }

    /**
     * Returns whether or not the CP_CONTACT_LOGIN_REQUIRED config setting is enabled. This function returns
     * the correct value depending on the mode we're currently in since this config is sand-boxed between development
     * and production.
     * @return bool Boolean indicating if config is enabled or disabled depending on the mode the site is in
     */
    public static function contactLoginRequiredEnabled()
    {
        return parent::getSandboxedConfig('loginRequired');
    }

    /**
     * Returns the minimum year to display in date fields.
     * Currently hard-coded to 1970 as our API doesn't handle anything earlier than epoch time.
     *
     * @return int The current minimum year setting for the framework
     */
    public static function getMinYear() {
        return intval(Text::getSubStringBefore(MIN_DATE, '-'));
    }

    /**
     * Returns the maximum year to display in date fields.
     * If $year not specified, use the value from the EU_MAX_YEAR config.
     *
     * @param mixed $year If year is null, the current year is returned. If year begins with '-' or '+', the remaining
     *               integer is treated as an offset from the current year. If year is an integer between 1970 and 2100, that value is returned.
     * @throws \Exception If $year does not meet one of the above criteria, an exception will be thrown
     * @return int
     */
    public static function getMaxYear($year = null) {
        if ($year === null) {
            $year = self::getConfig(EU_MAX_YEAR);
        }

        $year = str_replace(' ', '', $year);
        $currentYear = (int) date('Y');

        if ($year === '' || $year == $currentYear) {
            return $currentYear;
        }
        if (($offset = ((int) Text::getSubstringAfter($year, '-'))) && is_int($offset)) {
            $yearInteger = $currentYear - $offset;
        }
        else if (($offset = ((int) Text::getSubstringAfter($year, '+'))) && is_int($offset)) {
            $yearInteger = $currentYear + $offset;
        }
        else {
            $yearInteger = (int) $year;
        }

        $minYear = self::getMinYear();
        $maxYear = intval(Text::getSubStringBefore(MAX_DATE, '-'));
        if ($minYear <= $yearInteger && $yearInteger <= $maxYear) {
            return $yearInteger;
        }

        throw new \Exception(sprintf(self::getMessage(ARG_SUPPLIED_PCT_S_EVALUATE_INT_PCT_MSG), $year, $minYear, $maxYear));
    }
}