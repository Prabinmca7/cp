<?php
namespace RightNow\Widgets;

class AdvancedSecurityHeaders extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->setContentTypeOptions();
        $this->setXssProtection();
        $this->setContentSecurityPolicy();
    }

    function setContentTypeOptions() {
        if ($this->data['attrs']['content_type_options'] === "nosniff") {
            header("X-Content-Type-Options: nosniff");
        }
    }

    function setXssProtection() {
        if ($this->data['attrs']['xss_protection'] !== '') {
            header("X-XSS-Protection: " . $this->data['attrs']['xss_protection']);
        }
    }

    function setContentSecurityPolicy() {
        if ($policy = $this->data['attrs']['content_security_policy']) {
            header("Content-Security-Policy: " . $policy);
        }
    }
}
