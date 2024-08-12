<?php
namespace RightNow\Widgets;

class AssetCheck extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'serial_number_validate_ajax' => array(
                'method' => 'assetSerialNumberValidation',
                'clickstream' => 'asset_serialnumber_validation',
            ),
        ));
    }

    function getData()
    {
        $this->data['attrs']['add_params_to_url'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Perform serial number validation for an Asset by passing serial number and product id. Echos out JSON encoded result - null|false|integer (ID of the validated asset)
     * @param array $parameters Post parameters
     */
    function assetSerialNumberValidation(array $parameters) {
        $serialNumber = $parameters['serialNumber'];
        $productID = $parameters['productID'];
        $this->renderJSON(($serialNumber !== null && $serialNumber !== '' && $productID !== null && $productID !== '')
            ? $this->CI->model('Asset')->validateSerialNumber($serialNumber, $productID)->result
            : false
        );
    }
}
