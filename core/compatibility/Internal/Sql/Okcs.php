<?php
namespace RightNow\Internal\Sql;
require_once CPCORE . 'Utils/Okcs.php';

final class Okcs{
    
     const TYPE_OKCS = 7;

    /**
     * Returns the Okcs SAML ServiceProvider Details
     * @return array|null array holds details about OKCS Service Provider or 
     * null when no data about OKCS exists or on failure.
     */
    public static function getOkcsServiceProviderDetails()
    {
        $constant = get_called_class();
        $query = sql_prepare("
            SELECT sso_service_applications.service_provider_id, sso_service_applications.attr, sso_service_applications.application_url, 
            sso_service_providers.assertion_consumer_service_url, sso_service_providers.attr FROM sso_service_applications 
            LEFT OUTER JOIN sso_service_providers ON sso_service_providers.service_provider_id = sso_service_applications.service_provider_id 
            WHERE sso_service_applications.type = " . $constant::TYPE_OKCS
        );
        $i = 0;

        sql_bind_col($query, ++$i, BIND_INT, 0);
        sql_bind_col($query, ++$i, BIND_INT, 0);
        sql_bind_col($query, ++$i, BIND_NTS, 255);
        sql_bind_col($query, ++$i, BIND_NTS, 255);
        sql_bind_col($query, ++$i, BIND_INT, 0);
        $spDetails = null;

        if($row = sql_fetch($query)) 
        {
            list($spID, $appAttr, $appUrl, $assertionConsumerServiceUrl, $spAttr ) = $row;
            $spDetails = array(
                'sp_id' => $spID, 
                'sp_enabled' => $spAttr & 1, 
                'sp_acs_url' => $assertionConsumerServiceUrl,
                'app_url' => $appUrl,
                'app_enabled' => $appAttr & 1
            );
        }
        sql_free($query);
        return $spDetails;
    }

    /**
     * Returns the Email Article Link template from the Database
     * @return string|null $emailTemplate Email Article Link template null when no template found
     */
    public static function getOkcsSendEmailTemplate()
    {
        $localeId = str_replace("-", "_", \RightNow\Utils\Okcs::getInterfaceLocale());
        $query = sql_prepare("select templatedata.data from NTFCTN_TEMPLATE_DATA templatedata, NTFCTN_TEMPLATE template where templatedata.templateid=template.recordid and template.tasktype=24 and localeid='" . $localeId . "' and ownersiteid !=-1 union select templatedata.data from NTFCTN_TEMPLATE_DATA templatedata, NTFCTN_TEMPLATE template where templatedata.templateid=template.recordid and template.tasktype=24 and localeid='" . $localeId . "' and ownersiteid =-1");
        $i = 0;
        sql_bind_col($query, ++$i, BIND_NTS, 5000);
        $emailTemplate = null;
        if($row = sql_fetch($query)) {
            $emailTemplate = json_decode(preg_replace("/\s+/", " ", $row[0]))->html;
        }
        sql_free($query);
        return $emailTemplate;
    }
}
