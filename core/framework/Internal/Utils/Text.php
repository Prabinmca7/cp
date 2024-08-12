<?php

namespace RightNow\Internal\Utils;

/**
 * Internal methods for dealing with text comparisons, manipulation, etc.
 */
class Text{
    /**
     * Return an array of pairs where pair[0] is the individual character and pair[1] is the corresponding mask.
     * @param mixed $value The value
     * @param string $mask The mask
     * @param boolean $requireParity If true, an exception is thrown if the characters in $value do not map evenly with the $mask characters.
     * @return array Array mapping of characters to mask symbols.
     * @throws \Exception if $mask not a string having an even number of characters, or value characters do not evenly map to the mask and $requireParity specified.
     */
    public static function mapCharactersToMask($value, $mask, $requireParity = true) {
        static $cache = array();
        $value = trim("$value");
        $key = "$value-$mask-$requireParity";
        if (!(isset($cache[$key]) && $cachedValue = $cache[$key])) {
            $symbols = self::getMaskPairs($mask);
            $characters = \RightNow\Utils\Text::getMultibyteCharacters($value);
            if ($requireParity && count($symbols) !== count($characters)) {
                throw new \Exception(sprintf(\RightNow\Utils\Config::getMessage(MASK_LNG_PCT_S_MATCH_VAL_LNG_PCT_LBL), count($symbols), count($characters)));
            }
            $cachedValue = $cache[$key] = array_map(function($c, $s) {return array($c, $s);}, $characters, $symbols);
        }
        return $cachedValue;
    }

    /**
     * Return an array of 2 character mask symbols
     * @param string $mask The mask
     * @return array Array of mask characters.
     * @throws \Exception if $mask not a string having an even number of characters.
     */
    public static function getMaskPairs($mask) {
        if (!is_string($mask) || (!$mask = trim($mask)) || (strlen($mask) % 2 !== 0)) {
            throw new \Exception(\RightNow\Utils\Config::getMessage(MASK_CONTAIN_CHARACTERS_MSG));
        }
        return str_split($mask, 2);
    }
}
