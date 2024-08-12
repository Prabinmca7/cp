<?php
namespace RightNow\Internal\Api\Resources;
use RightNow\Internal\Api\Structure\Relationship,
    RightNow\Internal\Api\Structure\Data,
    RightNow\Internal\Api\Structure\Document;

require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Relationship.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Base.php';

class IncidentThread extends Base {

    public function __construct() {
        $this->type = "threads";
        $this->attributeMapping = array(
            'osvc' => array(
                'incidentThread'        => array(
                    'body'              => 'Text',
                    'contactId'         => 'Contact',
                    'created'           => 'CreatedTime'
                )
            )
        );
    }
}
