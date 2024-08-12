<?php
namespace RightNow\Widgets;

class CoBrowseLink extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $customUI = $this->data['attrs']['custom_ui'];
        $logoID = $this->data['attrs']['logo_id'];
        $siteID = $this->data['attrs']['site_id'];
        $langVal = $this->getCoBrowseLang();

        $parameters = array();

        if($customUI !== '')
            $parameters[] = "CustomUI=$customUI";

        if($logoID !== '')
            $parameters[] = "logo=$logoID";

        if($siteID !== '')
            $parameters[] = "siteID=$siteID";

        $parameters[] = "lang=$langVal";

        $this->data['screen_sharing_url'] = \RightNow\Utils\Config::getConfig(COBROWSE_URL) . '/' .
                                            \RightNow\Utils\Config::getConfig(COBROWSE_CONSUMER_PAGE) . '?' .
                                            implode('&amp;', $parameters);
    }

    /**
     * Determines the locale for CoBrowse based on CX interface language in use
     * @return string CoBrowse locale
     */
    protected function getCoBrowseLang()
    {
        $langValue = \RightNow\Utils\Text::getLanguageCode();

        switch($langValue)
        {
            // Languages supported by our default cobrowser livelook.
            // Also Turkish(tr-TR) is supported but we don't support it.
            case "zh-TW":
            case "de-DE":
            case "el-GR":
            case "fr-FR":
            case "it-IT":
            case "nl-NL":
            case "pt-BR":
            case "zh-CN":
            case "pt-PT":
            case "fr-CA":
                return $langValue;
                // es-ES is not supported by livelook with the ISO standard
                // so we use just es to support that language.
            case "es-ES":
                $langValue = 'es';
                break;
            default:
                // Always return english because the language is not supported.
                $langValue = 'en-US';
                break;
        }
        return $langValue;
    }
}
