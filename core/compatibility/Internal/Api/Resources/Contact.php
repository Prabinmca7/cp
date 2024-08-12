<?php
namespace RightNow\Internal\Api\Resources;
use RightNow\Internal\Api\Structure\Relationship;

require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Relationship.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Base.php';

class Contact extends Base {

    public function __construct() {
        $this->type = "contacts";
        $this->attributeMapping = array(
            'osvc' => array(
                'contact'   => array(
                    "login"     => "Login",
                    "created"   => "CreatedTime",
                    "updated"   => "UpdatedTime"
                ),
            )
        );
    }
}
