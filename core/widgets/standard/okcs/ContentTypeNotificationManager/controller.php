<?php
namespace RightNow\Widgets;

class ContentTypeNotificationManager extends \RightNow\Libraries\Widget\Base {
    private $contentTypeApiVersion = 'v1';
    private $productCategoryApiVersion = 'v1';

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $this->data['js']['productCategoryApiVersion'] = $this->productCategoryApiVersion;
        $this->data['js']['contentTypes'] = $this->getContentTypes();
        $subscriptionList = $this->CI->model('Okcs')->getContentTypeSubscriptionList();
        $list = array();
        if (!is_null($subscriptionList) && !isset($subscriptionList->error) && isset($subscriptionList->items) && is_array($subscriptionList->items) && count($subscriptionList->items) > 0) {
            foreach ($subscriptionList->items as $document) {
                if($document->subscriptionType === 'SUBSCRIPTIONTYPE_CHANNEL') {
                        $contentTypeId = $document->name;
                        $dateAdded = $this->CI->model('Okcs')->processIMDate($document->dateAdded);
                        $productCategory = $this->getCategories($document);

                        $item = array(
                            'name'             => $document->name,
                            'startDate'        => $dateAdded,
                            'subscriptionID'   => $document->recordId,
                            'contentTypeName'  => $document->contentType->name,
                            'product'          => isset($productCategory['product']) ? $productCategory['product'] : null,
                            'category'         => isset($productCategory['category']) ? $productCategory['category'] : null,
                            'phref'         => isset($productCategory['phref']) ? $productCategory['phref'] : null,
                            'chref'         => isset($productCategory['chref']) ? $productCategory['chref'] : null
                        );
                        array_push($list, $item);
                }
            }
            $this->data['js']['hasMore'] = $subscriptionList->hasMore;
            $this->data['subscriptionList'] = $list;
        }
        else if (is_array($subscriptionList->items) && (count($subscriptionList->items) === 0) && isset($this->data['attrs']['hide_when_no_results']) && $this->data['attrs']['hide_when_no_results']) {
            $this->classList->add('rn_Hidden');
        }
    }

    /**
    * This method returns a list of content-types
    * @return Array|null List of the content-types
    */
    function getContentTypes(){
        $allContentTypes = $this->CI->model('Okcs')->getChannels($this->contentTypeApiVersion);
        $contentType = array();
        if (isset($allContentTypes->error)) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($allContentTypes->error));
            return null;
        }
        if ($allContentTypes->items !== null) {
            foreach ($allContentTypes->items as $item) {
                array_push($contentType, $item);
            }
        }
        return $contentType;
    }

    /**
    * This method returns list of categories of the subscription item
    * @param object $subscription Subscription Object
    * @return Array List of product and categories
    */
    function getCategories($subscription) {
        $prodCat = array();
        if($subscription->categories && count($subscription->categories) > 0) {
            $product = $category = null;
            $prodParam = $detailUrl = '';
            for($i = 0; $i < count($subscription->categories); $i++) {
                $name = $subscription->categories[$i]->name;
                $slugifiedName = \RightNow\Utils\Text::slugify($name);
                if($subscription->categories[$i]->externalType === 'PRODUCT') {
                    $product .= $product === null ? $name : ', ' . $name;
                    $prodParam = '/p/';
                    $detailUrl = \RightNow\Utils\Config::getConfig(CP_PRODUCTS_DETAIL_URL);
                    $phref = '/app' . '/' . $detailUrl . '/categoryRecordID/' . $subscription->categories[$i]->referenceKey . $prodParam . $subscription->categories[$i]->externalId . '/~/' . $slugifiedName;
                }
                if($subscription->categories[$i]->externalType === 'CATEGORY') {
                    $category .= $category === null ? $name : ', ' . $name;
                    $prodParam = '/c/';
                    $detailUrl = \RightNow\Utils\Config::getConfig(CP_CATEGORIES_DETAIL_URL);
                    $chref = '/app' . '/' . $detailUrl . '/categoryRecordID/' . $subscription->categories[$i]->referenceKey . $prodParam . $subscription->categories[$i]->externalId . '/~/' . $slugifiedName;
                }
            }
            if($product !== null) {
                $prodCat['product'] = $product;
                $prodCat['phref'] = $phref;
            }
            if($category !== null) {
                $prodCat['category'] = $category;
                $prodCat['chref'] = $chref;
            }
        }
        return $prodCat;
    }
}
