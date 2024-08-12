<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config;

class ProdCatNotificationManager extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['js'] = array(
            'f_tok'           => \RightNow\Utils\Framework::createTokenWithExpiration(0),
            'duration'        => \RightNow\Utils\Config::getConfig(ANS_NOTIF_DURATION),
            'productsTable'   => HM_PRODUCTS,
            'categoriesTable' => HM_CATEGORIES,
            'notifications'   => array()
        );

        $notificationLists = $this->CI->model('Notification')->get(array('product', 'category'))->result;
        $productNotifications = isset($notificationLists['product']) && $notificationLists['product'] ? $notificationLists['product'] : array();
        $categoryNotifications = isset($notificationLists['category']) && $notificationLists['category'] ? $notificationLists['category'] : array();
        $allNotifications = array_merge($productNotifications, $categoryNotifications);
        $allNotifications = \RightNow\Utils\Framework::sortBy($allNotifications, true, function($n) { return $n->StartTime; });

        foreach($allNotifications as $notification) {
            if(\RightNow\Utils\Connect::isProductNotificationType($notification)) {
                $label = Config::getMessage(PRODUCT_LBL);
                $hierarchyType = 'ProductHierarchy';
                $notificationObject = 'Product';
                $notificationUrl = $this->data['attrs']['prod_page_url'] . "/p/" . $notification->Product->ID;
                $filterType = HM_PRODUCTS;
            }
            else {
                $label = Config::getMessage(CATEGORY_LBL);
                $hierarchyType = 'CategoryHierarchy';
                $notificationObject = 'Category';
                $notificationUrl = $this->data['attrs']['cat_page_url'] . "/c/" . $notification->Category->ID;
                $filterType = HM_CATEGORIES;
            }

            $labelChain = "$label - ";
            if(count($notification->$notificationObject->$hierarchyType)) {
                foreach($notification->$notificationObject->$hierarchyType as $parent) {
                    $labelChain .= $parent->LookupName . ' / ';
                }
            }
            $labelChain .= $notification->$notificationObject->LookupName;

            $this->data['notifications'][] = array(
                'startDate' => \RightNow\Utils\Framework::formatDate($notification->StartTime, 'default', null),
                'label' => $labelChain,
                'url' => $notificationUrl . \RightNow\Utils\Url::sessionParameter(),
                'expiresTime' => ($this->data['js']['duration'] > 0) ? $notification->ExpireTime : null
            );

            $this->data['js']['notifications'][] = array(
                'id' => $notification->$notificationObject->ID,
                'filter_type' => $filterType
            );
        }
    }
}
