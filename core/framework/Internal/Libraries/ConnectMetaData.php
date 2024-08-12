<?php

namespace RightNow\Internal\Libraries;

use RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\Utils\Text;

final class ConnectMetaData
{
    /**
     * Contains meta data for all classes defined in the site
     * @var mixed
     */
    private $metaData;

    /**
     * Contains the meta data using dot-notation for each field/property
     * @var mixed
     */
    private $fieldStrings;

    /**
     * List of primary class names
     * @var array
     */
    private $primaryClassNames;

    /**
     * Max number of named values to return for a field
     * @var integer
     */
    private static $maxNamedValues = 100;

    /**
     * A sub-list of standard objects and fields that should be displayed to the user
     * @var array
     */
    private $staticMetaData = array(
        // Answer fields
        "Answer"                                    => array(
            "ID"                                    => array(
                "COM_type"                          => "Answer",
                "field"                             => "ID",
            ),
            "GuidedAssistance"                      => null,
            "Solution"                              => null,
            "Summary"                               => null,
            "Question"                              => null,
            "Products"                              => null,
            "Categories"                            => null,
            "FileAttachments"                       => null,
            "StatusWithType.Status"                 => array(
                "COM_type"                          => "StatusWithType",
                "field"                             => "Status",
            ),
            "Language"                              => null,
            "CreatedTime"                           => null,
            "UpdatedTime"                           => null,
            "AnswerType"                            => null,
            "URL"                                   => null,
        ),

        // Contact fields
        "Contact"                                   => array(
            "ID"                                    => null,
            "Name.First"                            => array(
                "COM_type"                          => "PersonName",
                "field"                             => "First",
            ),
            "Name.Last"                             => array(
                "COM_type"                          => "PersonName",
                "field"                             => "Last",
            ),
            "LookupName"                            => null,
            "NameFurigana.First"                    => array(
                "COM_type"                          => "PersonName",
                "field"                             => "First",
            ),
            "NameFurigana.Last"                     => array(
                "COM_type"                          => "PersonName",
                "field"                             => "Last",
            ),
            "Emails.PRIMARY.Address"                => array(
                "COM_type"                          => "Email",
                "field"                             => "Address",
            ),
            "Emails.ALT1.Address"                   => array(
                "COM_type"                          => "Email",
                "field"                             => "Address",
            ),
            "Emails.ALT2.Address"                   => array(
                "COM_type"                          => "Email",
                "field"                             => "Address",
            ),
            "NewPassword"                           => null,
            "Login"                                 => null,
            "Organization.Login"                    => array(
                "COM_type"                          => "Organization",
                "field"                             => "Login",
            ),
            "Organization.Name"                     => array(
                "COM_type"                          => "Organization",
                "field"                             => "Name",
            ),
            "Organization.NewPassword"              => array(
                "COM_type"                          => "Organization",
                "field"                             => "NewPassword",
            ),
            "Title"                                 => null,
            "Phones.OFFICE.Number"                  => array(
                "COM_type"                          => "Phone",
                "field"                             => "Number",
            ),
            "Phones.MOBILE.Number"                  => array(
                "COM_type"                          => "Phone",
                "field"                             => "Number",
            ),
            "Phones.FAX.Number"                     => array(
                "COM_type"                          => "Phone",
                "field"                             => "Number",
            ),
            "Phones.ASST.Number"                    => array(
                "COM_type"                          => "Phone",
                "field"                             => "Number",
            ),
            "Phones.HOME.Number"                    => array(
                "COM_type"                          => "Phone",
                "field"                             => "Number",
            ),
            "Address.Street"                        => array(
                "COM_type"                          => "Address",
                "field"                             => "Street",
            ),
            "Address.City"                          => array(
                "COM_type"                          => "Address",
                "field"                             => "City",
            ),
            "Address.StateOrProvince"               => array(
                "COM_type"                          => "Address",
                "field"                             => "StateOrProvince",
            ),
            "Address.Country"                       => array(
                "COM_type"                          => "Address",
                "field"                             => "Country",
            ),
            "Address.PostalCode"                    => array(
                "COM_type"                          => "Address",
                "field"                             => "PostalCode",
            ),
            "MarketingSettings.MarketingOptIn"      => array(
                "COM_type"                          => "ContactMarketingSettings",
                "field"                             => "MarketingOptIn",
            ),
            "MarketingSettings.EmailFormat"         => array(
                "COM_type"                          => "ContactMarketingSettings",
                "field"                             => "EmailFormat",
            ),
            "MarketingSettings.SurveyOptIn"         => array(
                "COM_type"                          => "ContactMarketingSettings",
                "field"                             => "SurveyOptIn",
            ),
            "ChannelUsernames.TWITTER.Username"     => array(
                "COM_type"                          => "ChannelUsername",
                "field"                             => "Username",
            ),
            "ChannelUsernames.YOUTUBE.Username"     => array(
                "COM_type"                          => "ChannelUsername",
                "field"                             => "Username",
            ),
            "ChannelUsernames.FACEBOOK.Username"    => array(
                "COM_type"                          => "ChannelUsername",
                "field"                             => "Username",
            ),
            "Disabled"                              => null,
        ),

        // Incident fields
        "Incident"                                  => array(
            "ID"                                    => null,
            "PrimaryContact.ID"                     => array(
                "COM_type"                          => "Contact",
                "field"                             => "ID",
            ),
            "PrimaryContact.Emails.PRIMARY.Address" => array(
                "COM_type"                          => "Email",
                "field"                             => "Address",
            ),
            "PrimaryContact.Name.First"             => array(
                "COM_type"                          => "PersonName",
                "field"                             => "First",
            ),
            "PrimaryContact.Name.Last"              => array(
                "COM_type"                          => "PersonName",
                "field"                             => "Last",
            ),
            "Organization.ID"                       => array(
                "COM_type"                          => "Organization",
                "field"                             => "ID",
            ),
            "StatusWithType.Status"                 => array(
                "COM_type"                          => "StatusWithType",
                "field"                             => "Status",
            ),
            "ReferenceNumber"                       => null,
            "SLAInstance"                           => array(
                "COM_type"                          => "AssignedSLAInstance",
                "field"                             => "NameOfSLA",
            ),
            "Asset.ID"                              => array(
                "COM_type"                          => "Asset",
                 "field"                            => "ID",
            ),
            "Subject"                               => null,
            "Threads"                               => null,
            "Product"                               => null,
            "Category"                              => null,
            "CreatedTime"                           => null,
            "UpdatedTime"                           => null,
            "ClosedTime"                            => null,
            "FileAttachments"                       => null,
        ),

        //Asset fields
        "Asset"                                     => array(
            "ID"                                    => null,
            "Name"                                  => null,
            "SerialNumber"                          => null,
            "Contact.ID"                            => array(
                "COM_type"                          => "Contact",
                 "field"                            => "ID",
            ),
            "Organization.ID"                       => array(
                "COM_type"                          => "Organization",
                "field"                             => "ID",
            ),
            "StatusWithType.Status"                 => array(
                "COM_type"                          => "AssetStatuses",
                "field"                             => "Status",
            ),
            "Product"                               => null,
            "PurchasedDate"                         => null,
            "InstalledDate"                         => null,
            "RetiredDate"                           => null,
            "Description"                           => null,
        ),

        // Product
        "ServiceProduct"                            => array(
            "ID"                                    => null,
            "Name"                                  => null,
            "DisplayOrder"                          => null,
            "LookupName"                            => null,
        ),

        // Category
        "ServiceCategory"                           => array(
            "ID"                                    => null,
            "Name"                                  => null,
            "DisplayOrder"                          => null,
            "LookupName"                            => null,
        ),

        "CommunityQuestion"                            => array(
            "CreatedByCommunityUser"                   => null,
            "BestCommunityQuestionAnswers"             => null,
            "Body"                                  => null,
            "BodyContentType"                       => null,
            "Category"                              => null,
            "ContentLastUpdatedByCommunityUser"        => null,
            "ContentLastUpdatedTime"                => null,
            "CreatedTime"                           => null,
            "FileAttachments"                       => null,
            "ID"                                    => null,
            "Interface"                             => null,
            "Language"                              => null,
            "LastActivityTime"                      => null,
            "LookupName"                            => null,
            "Product"                               => null,
            "RatingAdjustment"                      => null,
            "StatusWithType"                        => null,
            "Subject"                               => null,
            "UpdatedByCommunityUser"                   => null,
            "UpdatedTime"                           => null,
        ),

        "CommunityUser"                                => array(
            "AvatarURL"                             => null,
            "CreatedTime"                           => null,
            "DisplayName"                           => null,
            "ID"                                    => null,
            "LookupName"                            => null,
            "CommunityCategorySubscriptions"           => null,
            "CommunityProductSubscriptions"            => null,
            "CommunityQuestionSubscriptions"           => null,
            "StatusWithType"                        => null,
            "UpdatedTime"                           => null,
        ),

        "CommunityComment"                     => array(
            "CreatedByCommunityUser"                   => null,
            "Body"                                  => null,
            "BodyContentType"                       => null,
            "ContentLastUpdatedByCommunityUser"        => null,
            "ContentLastUpdatedTime"                => null,
            "CreatedTime"                           => null,
            "FileAttachments"                       => null,
            "ID"                                    => null,
            "LookupName"                            => null,
            "Parent"                                => null,
            "RatingAdjustment"                      => null,
            "CommunityQuestion"                        => null,
            "CommunityCommentHierarchy"        => null,
            "StatusWithType"                        => null,
            "Type"                                  => null,
            "UpdatedByCommunityUser"                   => null,
            "UpdatedTime"                           => null,
        ),
    );

    /**
     * Get an instance of ConnectMetaData
     *
     * @return object
     */
    public static function getInstance()
    {
        static $instance;
        if (!$instance)
            $instance = new ConnectMetaData();
        return $instance;
    }

    /**
     * Private constructor, use getInstance() instead
     */
    private function __construct()
    {
        // get the meta data
        $this->fetchPrimaryClasses();
        $this->fetchMetaData();

        // generate dot-notation for the meta data
        $this->buildStaticFieldStrings();
    }

    /**
     * Returns the meta data for the CP specific core objects and any custom objects
     *
     * @return mixed
     */
    public function getMetaData()
    {
        return $this->fieldStrings;
    }

    /**
     * Returns a mapping of the constraint id's with the desired label for the UI
     *
     * @return array
     */
    public function getConstraintMapping()
    {
        return array(
            ConnectPHP\Constraint::Min => Config::getMessage(MINIMUM_LBL),
            ConnectPHP\Constraint::Max => Config::getMessage(MAXIMUM_LBL),
            ConnectPHP\Constraint::MinLength => Config::getMessage(MINIMUM_LENGTH_LBL),
            ConnectPHP\Constraint::MaxLength => Config::getMessage(MAXIMUM_LENGTH_LBL),
            ConnectPHP\Constraint::MaxBytes => Config::getMessage(MAXIMUM_BYTES_LBL),
            ConnectPHP\Constraint::In => Config::getMessage(IN_LBL),
            ConnectPHP\Constraint::Not => Config::getMessage(NOT_LBL),
            ConnectPHP\Constraint::Pattern => Config::getMessage(PATTERN_LBL),
        );
    }

    /**
     * Build a concatenated list of field properties for the statically defined
     * objects and fields
     *
     * @throws \Exception If there is no metadata for one of the fields
     */
    private function buildStaticFieldStrings()
    {
        $this->fieldStrings = array();

        // make sure the meta data is sorted
        ksort($this->staticMetaData);
        foreach ($this->staticMetaData as $className => $fields)
        {
            foreach ($fields as $fieldString => $fieldInfo)
            {
                $comType = $className;
                $fieldName = $fieldString;

                if (is_array($fieldInfo))
                {
                    $comType = $fieldInfo["COM_type"];
                    $fieldName = $fieldInfo["field"];
                }

                if (!isset($this->metaData[$comType][$fieldName]))
                    throw new \Exception(sprintf("%s %s", Config::getMessage(NO_METADATA_FOR_LBL), sprintf("%s.%s", $comType, $fieldName)));

                $metaData = $this->metaData[$comType][$fieldName];
                if ($this->isDate($metaData)) {
                    $metaData = $this->formatDateMeta((object)get_object_vars($metaData));
                }

                $this->fieldStrings[$className][$fieldString] = array(
                    'metaData'      => $metaData,
                    'namedValues'   => $this->getNamedValues($className, $fieldString),
                );
            }

            // get custom field and custom attribute data
            $this->buildFieldStrings($className, "CustomFields", sprintf("%sCustomFields", $className));

            // sort the fields now that we have all of them
            ksort($this->fieldStrings[$className]);
        }
    }

    /**
     * Recursively build a concatenated list of field properties for
     * custom fields and custom attributes
     *
     * @param string $className Name of class
     * @param string $fieldString Name of field
     * @param string $subClass Name of subclass
     */
    private function buildFieldStrings($className, $fieldString, $subClass)
    {
        if (!isset($this->metaData[$subClass]))
            return;

        $getFieldData = function($meta, $namedValues = array()) {
            return array(
                'metaData' => $meta,
                'namedValues' => $namedValues,
            );
        };

        foreach ($this->metaData[$subClass] as $field => $metaData)
        {
            $newFieldString = sprintf("%s.%s", $fieldString, $field);

            //Since we may need to modify some metadata fields, convert it into a generic object.
            $metaData = (object)get_object_vars($metaData);

            if (Text::stringContains($newFieldString, "CustomFields.c."))
            {
                // pull old style custom field data
                $traditionalCustomField = Framework::getCustomField($className, $field);
                if(!$traditionalCustomField['enduser_writable'] && !$traditionalCustomField['enduser_visible']) {
                    continue;
                }
                //Connect doesn't expose visibility for custom fields, so manually change it in the metadata in order to display data correctly
                if ($traditionalCustomField['enduser_visible'] && !$traditionalCustomField['enduser_writable']) {
                    $metaData->is_read_only_for_create = true;
                    $metaData->is_read_only_for_update = true;
                }
                //Connect doesn't expose requiredness for custom fields, so manually change it in the metadata
                if ($traditionalCustomField['required']) {
                    $metaData->is_required_for_create = true;
                    $metaData->is_required_for_update = true;
                }
            }

            if ($this->isDate($metaData)) {
                $metaData = $this->formatDateMeta($metaData);
            }

            // ensure we don't recurse into other standard objects through a custom attribute
            if (isset($this->primaryClassNames[$metaData->COM_type]) && !$metaData->is_menu)
            {
                continue;
            }
            if (Text::beginsWith($metaData->COM_type, "NamedID") || (isset($metaData->is_menu) && $metaData->is_menu))
            {
                $this->fieldStrings[$className][$newFieldString] = $getFieldData($metaData, $this->getNamedValues($className, sprintf("%s.%s", $fieldString, $field)));
            }
            else if (isset($this->metaData[$metaData->COM_type]))
            {
                $this->buildFieldStrings($className, $newFieldString, $metaData->COM_type);
            }
            else
            {
                // only custom objects are formatted as Namespace\ClassName, switch to dot-notation
                if (Text::stringContains($className, "\\"))
                {
                    $className = str_replace("\\", ".", $className);
                }

                $this->fieldStrings[$className][$newFieldString] = $getFieldData($metaData);
            }
        }
    }

    /**
     * Convert date and datetime min and max constraints and default value from epoch to a formatted date string.
     * @param object $metaData A CPHP metadata object
     * @return object The modified metadata
     * @throws \Exception If $metaData isn't a valid date field
     */
    private function formatDateMeta($metaData) {
        $comType = $metaData->COM_type;
        if (!$this->isDate($comType)) {
            throw new \Exception("Not a date type: '$comType'"); // Doesn't need translation
        }

        $formatDate = function($value, $includeTimeZone = true) use ($comType) {
            return Framework::formatDate($value, 'default', ($comType === 'DateTime' ? 'default' : null), $includeTimeZone);
        };

        if (is_array($metaData->constraints)) {
            foreach ($metaData->constraints as $constraint) {
                $value = $constraint->value;
                // @codingStandardsIgnoreStart
                if ($constraint->kind === ConnectPHP\Constraint::Min || $constraint->kind === ConnectPHP\Constraint::Max) {
                    $value = $formatDate($value, false);
                }
                // @codingStandardsIgnoreEnd
                $results[] = (object)array(
                    'kind' => $constraint->kind,
                    'value' => $value
                );
            }
            $metaData->constraints = $results;
        }

        if ($metaData->default) {
            $metaData->default = $formatDate($metaData->default);
        }

        return $metaData;
    }

    /**
     * Get a list of the primary class names, since we will exclude recursing into these
     * when printing out the property strings
     */
    private function fetchPrimaryClasses()
    {
        $this->primaryClassNames = array();
        $classes = ConnectPHP\ConnectAPI::getPrimaryClassNames();
        foreach ($classes as $class)
        {
            $metaData = $class::getMetaData();
            $this->primaryClassNames[$metaData->COM_type] = $class;
        }
    }

    /**
     * Recursively fetches the meta data from the API, to get custom fields and custom attributes
     * @param string|null $className Name of class
     */
    private function fetchMetaData($className=null)
    {
        if ($className === null)
        {
            $this->metaData = array();

            // we need meta data for all classes and recurse into them, as there are
            // some classes not returned by getClassNames() unfortunately
            $classes = ConnectPHP\ConnectAPI::getClassNames();
            foreach ($classes as $class)
            {
                $this->fetchMetaData($class);
            }
        }
        else
        {
            // do not recurse into a class we already have
            $metaData = $className::getMetaData();
            if (isset($this->metaData[$metaData->COM_type]))
                return;

            $this->metaData[$metaData->COM_type] = array();
            if ($metaData)
            {
                foreach ($metaData as $key => $val)
                {
                    if (is_object($val))
                    {
                        $this->metaData[$metaData->COM_type][$key] = $val;
                        if (Text::beginsWith($val->type_name, "RightNow\\Connect"))
                        {
                            $this->fetchMetaData($val->type_name);
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns named values for a given field
     *
     * @param string $className The name of the class
     * @param string $fieldString The field name represented in dot-notation, eg: CustomFields.c.SomeMenuField
     *
     * @return array
     */
    private function getNamedValues($className, $fieldString)
    {
        $namedValues = array();
        try
        {
            if (Text::endsWith($fieldString, ".Country"))
            {
                $countryItems = get_instance()->model('Country')->getAll()->result;
                // we want the full names and not the ISO codes for countries
                if (is_array($countryItems))
                {
                    foreach ($countryItems as $countryItem)
                        $namedValues[] = array('ID' => $countryItem->ID, 'LookupName' => $countryItem->Name);
                }
            }
            else
            {
                // fetch the named values using getNamedValues vs $metaData->named_values -
                // for instance, StatusWithType is used with multiple classes, so named_values won't populate
                // because the API does not know which parent class to use
                $namedValues = ConnectPHP\ConnectAPI::getNamedValues(
                    sprintf("%s\\%s", CONNECT_NAMESPACE_PREFIX, $className), $fieldString);

                // lazy-load any menu items if this is a NamedID
                if(is_array($namedValues)) {
                    for ($i = 0; $i < count($namedValues); ++$i) {
                        $namedValues[$i]->ID;
                        $namedValues[$i]->LookupName;                        
                    }                    
                }
            }

            if (is_array($namedValues) && count($namedValues) > self::$maxNamedValues)
            {
                $namedValues = array_slice($namedValues, 0, self::$maxNamedValues);
                $namedValues[] = array('ID' => "", "LookupName" => sprintf("<i>%s</i>", Config::getMessage(RESULTS_TRUNCATED_LBL)));
            }
        }
        catch (\Exception $ex)
        {
            // ignore exception and return an empty array
        }
        return $namedValues;
    }


    /**
     * Returns true if $fieldData indicates a date field.
     *
     * @param object|string $fieldData A Connect meta object, or the string indicated by meta->COM_type.
     * @return boolean True if is a date.
     */
    private function isDate($fieldData)
    {
        $comType = is_object($fieldData) ? $fieldData->COM_type : $fieldData;
        return ($comType === 'Date' || $comType === 'DateTime' );
    }
}
