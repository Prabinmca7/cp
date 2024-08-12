<?php

namespace RightNow\Helpers;

use RightNow\Utils\Config,
    RightNow\Utils\Text;

/**
 * Functions to help deal with social interactions
 */
class SocialHelper {
    /**
     * The data types of report filter values
     * @var array
     */
    protected static $reportFilterDataTypes = array("date" => 4, "list" => 1);
    protected static $avatarSizes = array(
        'none'   => array('className' => '',          'size' => '0'),
        'small'  => array('className' => 'rn_Small',  'size' => '24'),
        'medium' => array('className' => 'rn_Medium', 'size' => '48'),
        'large'  => array('className' => 'rn_Large',  'size' => '96'),
        'xlarge' => array('className' => 'rn_XLarge', 'size' => '160'),
    );

    /**
     * Create a user's default data for displaying the default
     * displayname initials avatar.
     * @param string $displayName User's display name
     * @param bool $isActive If false, indicate the user's non-active status with a gray avatar and an exclamation mark (!) for the text.
     * @return array Array that contains user's default avatar information:
     *                     'text': string initials to display for the avatar
     *                     'color': background color to use for the avatar
     */
    function getDefaultAvatar($displayName, $isActive = true) {
        $text = '?';
        if ($displayName) {
            $displayName = \RightNow\Utils\Text::unescapeHtml($displayName);
            $displayName = preg_match("/[\p{L}0-9]+/u", $displayName, $matches) ? $matches[0] : $displayName; //use only alphanumeric charcters to generate default avatar if exist
            $text = $isActive ? strtoupper(\RightNow\Utils\Text::getMultibyteSubstring($displayName, 0, 1)) : '!';
        }

        return array(
            'text'  => $text,
            'color' => $isActive && isset($displayName) && strlen($displayName) > 0 ? strlen($displayName) % 5 : 5,
        );
    }

    /**
     * Returns the public profile url for the specified user.
     * @param int $userID The id of the user.
     * @return String The public profile url for the specified user.
     */
    function userProfileURL($userID) {
        // Note: replace hard-coded path below when new config in place (140407-000089).
        return '/app/' . \RightNow\Utils\Config::getConfig(CP_PUBLIC_PROFILE_URL) . "/user/$userID/" . \RightNow\Utils\Url::sessionParameter();
    }

    /**
     * Returns the default arguments to render the Social.Avatar view partial.
     * @param Object $user The social user object as returned from Connect or from a tabular query.
     * @param array $overrides An array of key-value pairs that will override the defaults.
     *                         'size':        String 'small', 'medium', 'large', 'xlarge'
     *                         'title':       String Tooltip when hovering over the avatar (defaults to $user->DisplayName)
     *                         'isActive':    Boolean Indicates whether the user is active
     *                         'avatarUrl': String URL to avatar image (defaults to $user->AvatarURL)
     *                         'profileUrl':  String URL to user's profile page
     *                         'displayName': String Display name (defaults to $user->DisplayName)
     *                         'defaultAvatar': Associative array
     *                             'text': String text for the default avatar (defaults to first initial of display name)
     *                             'color': Int 0-5 corresponding to background colors defined in site.css for rn_DefaultColor0 - rn_DefaultColor5
     * @param boolean $hideDisplayName Whether the display name should be hidden or not
     * @return array An associative array of arguments expected by the Social.Avatar view partial.
     */
    function defaultAvatarArgs($user, array $overrides = array(), $hideDisplayName = false) {
        $isActive = !$this->userIsSuspendedOrDeleted($user);

        if (isset($user->ID) && $user->ID && $isActive) {
            $displayName = $title = $user->DisplayName;
            $avatarUrl = $user->AvatarURL;
            $profileUrl = $this->userProfileUrl($user->ID);
        }
        else if (!isset($user->ID) || !$user->ID) {
            $title = Config::getMessage(UNKNOWN_LWR_LBL);
            $displayName = "[$title]";
            $avatarUrl = $profileUrl = null;
        }
        else {
            $title = Config::getMessage(INACTIVE_LC_LBL);
            $displayName = "[$title]";
            $avatarUrl = $profileUrl = null;
        }
        $fileExists = $this->checkIfFileExists($avatarUrl);
        return array_merge(array(
            'avatarUrl'     => $avatarUrl,
            'defaultAvatar' => isset($user) && isset($user->DisplayName) ? $this->getDefaultAvatar($user->DisplayName, $isActive) : $this->getDefaultAvatar(null, $isActive),
            'displayName'   => $displayName,
            'title'         => $title,
            'profileUrl'    => $profileUrl,
            'isActive'      => $isActive,
            'hideDisplayName' => $hideDisplayName,
            'fileExists'    => $fileExists
        ), $overrides, $this->avatarSizes($overrides['size']));
    }

    /**
     * Get the available flags as associative array
     * @param string $socialObjectName Name of the Social object
     * @return array Associative array which has flag ID as KEY and LookupName as VALUE
     */
    function getFlagTypeLabels ($socialObjectName) {
        $flags = get_instance()->model($socialObjectName)->getFlagTypes();
        $flagArray = array();
        foreach ($flags as $flag) {
            $flagArray[$flag['ID']] = $flag['LookupName'];
        }
        return $flagArray;
    }

    /**
     * Get the available statuses as associative array
     * @param string $socialObjectName Name of the Social object
     * @param array $hideStatusTypeIDs Status type ids to be hidden
     * @return array Associative array which has status ID as KEY and StatusLookupName as VALUE
     */
    function getStatusLabels ($socialObjectName, array $hideStatusTypeIDs = array()) {
        $statusesTypes = get_instance()->model($socialObjectName)->getMappedSocialObjectStatuses()->result;
        $statusArray = array();
        if ($statusesTypes) {
            $deletedStatuses = array(
                'CommunityQuestion' => STATUS_TYPE_SSS_QUESTION_DELETED,
                'CommunityComment' => STATUS_TYPE_SSS_COMMENT_DELETED,
                'CommunityUser' => STATUS_TYPE_SSS_USER_DELETED
            );
            array_push($hideStatusTypeIDs, $deletedStatuses[$socialObjectName]);
            foreach ($statusesTypes as $statusTypeID => $statuses) {
                if (!in_array($statusTypeID, $hideStatusTypeIDs)) {
                    foreach ($statuses as $statusID => $label) {
                        $statusArray[$statusID] = $label['StatusLookupName'];
                    }
                }
            }
        }
        return $statusArray;
    }

    /**
     * Parse and create associative array from Comma-separated list of values
     * @param string $attributeData Comma-separated list of keys and values. E.g. format: 1 > Last 24 hours, 2 > Last 7 days, 3 > Last 30 days
     * @return array Associative array e.g array( 1 => Last 24 hours, 2 => Last 7 days, 3 => Last 30 days)
     */
    function formatListAttribute ($attributeData) {
        $formattedData = array();
        if ($attributeData) {
            //get comma-separated key > value pair
            $attributes = explode(',', $attributeData);
            foreach ($attributes as $value) {
                $splitPosition = strrpos($value, ' > ');
                if ($splitPosition === false) {
                    return array();
                }
                $formattedData[trim(substr($value, 0, $splitPosition))] = trim(substr($value, $splitPosition + 2));
            }
        }
        return $formattedData;
    }

    /**
     * Returns an array containing the Flag IDs for the user-specified
     * flag types multioptions.
     * @param  array $flagTypeAttributeValues Multioption attribute value
     * @return array                 Contains Flag IDs or an empty array
     *                                        if all flags are specified
     */
    function mapFlagTypeAttribute (array $flagTypeAttributeValues) {
        $mapping = array(
            'inappropriate' => FLAG_INAPPROPRIATE,
            'spam' => FLAG_SPAM,
            'miscategorized' => FLAG_MISCATEGORIZED,
            'redundant' => FLAG_REDUNDANT,
        );
        if (!empty($flagTypeAttributeValues)) {
            $validFlagTypes = array_intersect(array_map('strtolower', $flagTypeAttributeValues), array_keys($mapping));
            return array_map(function ($flagName) use ($mapping) {
                return $mapping[$flagName];
            }, $validFlagTypes);
        }
        return $flagTypeAttributeValues;
    }

    /**
     * Returns the $socialObject->Body formatted per the $socialObject->BodyContentType ('text/html' or 'text/x-markdown').
     * @param Object $socialObject A social object having 'Body' and 'BodyContentType' properties.
     * @param Boolean $highlight Whether to highlight content matching 'kw' URL parameter
     * @return String The formatted body text.
     */
    function formatBody($socialObject, $highlight = false) {
        return \RightNow\Libraries\Formatter::formatTextEntry($socialObject->Body, $socialObject->BodyContentType->LookupName, $highlight);
    }

    /**
     * Parses the default report filter value based on the data type
     *
     * @param string $value Report filter default value
     * @param integer $dataType Report filter data type. One of the values of self::$reportFilterDataTypes array
     * @param array $options Array of extra details like dateformat required for parsing
     * @return array Parsed default values
     */
    function parseReportDefaultFilterValue($value, $dataType, array $options = array()) {
        if (isset($options["filterName"]) && ($options["filterName"] === "p" || $options["filterName"] === "c")) {
            $filterName = ($options["filterName"] === 'p') ? 'Product' : 'Category';
            $reportProdCatValue = explode(".", $value);
            return get_instance()->model("Prodcat")->getFormattedChain($filterName, $reportProdCatValue[count($reportProdCatValue) - 1], true)->result;
        }
        if ($dataType === self::$reportFilterDataTypes["date"]) {
            $dateRangeParts = explode("|", $value);
            if (!empty($dateRangeParts[0]) && empty($dateRangeParts[1])) {
                $dateExprParts = explode(",", $dateRangeParts[0]);
                $defaultDateOption = ($dateExprParts[1] && $dateExprParts[2]) ? "last_" . (-1 * trim($dateExprParts[1])) . "_" . strtolower(trim($dateExprParts[2])) : $defaultForInvalid;
                return $options["allowedOptions"][$defaultDateOption] ? $defaultDateOption : null;
            }
            if (!empty($dateRangeParts[0]) && !empty($dateRangeParts[1])) {
                $defaultDateRange = date($options["dateFormat"], $dateRangeParts[0]) . "|" . date($options["dateFormat"], $dateRangeParts[1]);
                return Text::validateDateRange($defaultDateRange, $options["dateFormat"], "|", false, $this->data['attrs']['max_date_range_interval']) ? $defaultDateRange : $defaultForInvalid;
            }
            return null;
        }
        if ($dataType === self::$reportFilterDataTypes["list"]) {
            $value = str_replace("~any~", "", $value);
            return explode(";", $value);
        }
        return $value;
    }

    /**
     * Returns array of parsed date parts if MaxDateInterval is in allowed format else null
     * @param string $maxDateRangeInterval Date interval in string format. For ex: 2 years, 3 months etc
     * @return array|null Array of date parts or null
     */
    function validateModerationMaxDateRangeInterval($maxDateRangeInterval){
        static $validMaxDateUnits; 
        $validMaxDateUnits ?: $validMaxDateUnits = array("days", "day", "month", "months", "year", "years");
        $parts = explode(" ", $maxDateRangeInterval);
        return ($parts && count($parts) === 2 && ($unit = (int)$parts[0]) && $unit > 0 
                && in_array($parts[1], $validMaxDateUnits)) ? $parts : null;
    }
    
    /**
     * Returns the validation functions for Moderation date filters
     * 
     * @param string $maxDateRangeInterval PHP date expression
     * @return array Array of filter expression and validation functions
     */
    function getModerationDateRangeValidationFunctions($maxDateRangeInterval) {
        if(!$this->validateModerationMaxDateRangeInterval($maxDateRangeInterval)){
            return array();
        }
        $validationFunction = function ($filterValue) use($maxDateRangeInterval) {
            if(!Text::stringContains($filterValue, "|")){
                return $filterValue;
            }
            $dateFormatObj = Text::getDateFormatFromDateOrderConfig();
            $dateValue = Text::validateDateRange($filterValue, $dateFormatObj["short"], "|", false, $maxDateRangeInterval);
            return $dateValue;
        };
        return array("questions.updated" => $validationFunction,
            "comments.updated" => $validationFunction);
    }
    
    /**
     * Returns excluded status types for 'CommunityQuestion' or 'CommunityComment'
     * @param string $objectName One of 'CommunityQuestion' or 'CommunityComment'
     * @return array|null An array of excluded status types or null
     */
    function getExcludedStatuses($objectName = 'CommunityQuestion') {
        static $statuses;

        $statuses = $statuses ?: array(
            'CommunityQuestion'        => array(STATUS_TYPE_SSS_QUESTION_SUSPENDED, STATUS_TYPE_SSS_QUESTION_DELETED, STATUS_TYPE_SSS_QUESTION_PENDING),
            'CommunityComment' => array(STATUS_TYPE_SSS_COMMENT_SUSPENDED, STATUS_TYPE_SSS_COMMENT_DELETED, STATUS_TYPE_SSS_COMMENT_PENDING),
        );

        return array_key_exists($objectName, $statuses) ? $statuses[$objectName] : null;
    }
    
    /**
     * Converts the author_roleset_callout attribute of widgets QuestionComments and QuestionDetail to an array and filters valid values
     * @param string $rolesetsWithLabels Comma separated string containing author roleset IDs with associated text to be displayed
     * E.g. 5|Posted by Admin; 2,4,8|Posted by Moderator
     * @return array Array containing valid author roleset IDs as keys and their associated labels as values
     * E.g. array(5 => 'Posted by Admin', 2 => 'Posted by Moderator', 4 => 'Posted by Moderator', 8 => 'Posted by Moderator')
     */
    
    function filterValidRoleSetIDs($rolesetsWithLabels) {
        $rolesets = array();

        $rolesetLabels = explode(";", $rolesetsWithLabels);
        foreach($rolesetLabels as $idsWithAssociatedText) {
            $rolelabels = explode("|", $idsWithAssociatedText);
            //$rolelabels must be in the form of array([0] => <number(s)>, [1] => <text>) for us to process. Values like "5|Posted|By|Moderator" will be ignored
            if(count($rolelabels) !== 2) {
                continue;
            }
            $ids = array_values(array_map('intval', array_filter(explode(',', $rolelabels[0]), function ($v) {
                return $v > 0;
            })));
            //Add to the previous array, without overwriting already entered values, giving preference to values in the left side
            $rolesets = $rolesets + array_fill_keys($ids, $rolelabels[1]);
        }
        return $rolesets;
    }

    /**
     * Generates an array containing names of RoleSet Styles based on how many unique labels are present. These names will be used as CSS classes.
     * @param array $rolesetsWithLabels Array containing valid author roleset IDs as keys and their associated labels as values
     * E.g. 5|Posted by Admin; 2,4,8|Posted by Moderator
     * @return array Array with keys as the string to be displayed and the value is the generated css classname for that roleset
     * E.g. array("Posted by Admin" => "rn_AuthorStyle_1", "Posted by Moderator" => "rn_AuthorStyle_2")`
     */
    function generateRoleSetStyles($rolesetsWithLabels) {
        $labels = array_count_values($rolesetsWithLabels);
        $retArr = array();
        $classPrefix = "rn_AuthorStyle";
        $i = 1;
        foreach($labels as $key => $value) {
            $retArr[$key] = $classPrefix . "_" . strval($i);
            $i++;
        }
        return $retArr;
    }

    /**
     * Returns which roleset ID should the highlighting of content posted by an author be done for, -1 if nothing is to be done for this user
     * @param Int $userID User id
     * @param array $authorRoleSets Array containing Role Set IDs for the Authors whose content should be highlighted with the associated text
     * E.g. array([5] => "Posted by a moderator", [2] => "Posted by an admin", [3] => "Posted by an admin"
     * @return Int Role Set ID of the user for which the content is to be highlighted
     */
    function highlightAuthorContent($userID, array $authorRoleSets = array()) {
        static $authors = array();
        if($authors[$userID] !== null) {
            return $authors[$userID];
        }
        //give preference left to right to the given roleset id array
        if($socialUser = get_instance()->model('CommunityUser')->get($userID)->result) {
            foreach($authorRoleSets as $authorRoleSetID => $label) {
                foreach($socialUser->RoleSets as $index => $roleSet) {
                    if($roleSet->ID === $authorRoleSetID) {
                        $authors[$userID] = $authorRoleSetID;
                        return $authorRoleSetID;
                    }
                }
            }
        }
        return $authors[$userID] = -1;
    }

    /**
     * Produces an associative array corresponding to the indicated size of the
     * avatar.
     * @param  string $size Avatar sizes: 'small', 'medium', 'large', 'xlarge'
     * @return array       Contains size and className keys
     */
    protected function avatarSizes($size = 'medium') {
        return isset(self::$avatarSizes[$size]) ? self::$avatarSizes[$size] : self::$avatarSizes['medium'];

    }

    /**
     * Determine if the $user is suspended or deleted.
     * @param Object $user The social user object as returned from Connect or from a tabular query.
     * @return bool True if $user is suspended or deleted.
     */
    private function userIsSuspendedOrDeleted($user) {
        if (isset($user->SocialPermissions) && $user->SocialPermissions) {
            return $user->SocialPermissions->isSuspended() || $user->SocialPermissions->isDeleted();
        }

        if (isset($user->StatusWithType) && is_object($user->StatusWithType) && is_object($user->StatusWithType->StatusType)) {
            $statusTypeID = intval($user->StatusWithType->StatusType->ID);
            return ($statusTypeID === STATUS_TYPE_SSS_USER_SUSPENDED || $statusTypeID === STATUS_TYPE_SSS_USER_DELETED);
        }

        return false;
    }

    /**
     * Determine if the filename specified in the $url exists or not.
     * @param string $url The url of the avatar.
     * @return bool True if the filename specified in the $url exists.
     */
    function checkIfFileExists($url) {
        if (!\RightNow\Utils\Url::isExternalUrl($url)) {
            $avatarPath = parse_url($url, PHP_URL_PATH);
        }
        return isset($avatarPath) && $avatarPath ? is_readable(HTMLROOT . $avatarPath) : true;
    }
}
