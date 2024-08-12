<?php

use RightNow\Helpers\OkcsWhiteList;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsWhiteListTest extends CPTestCase {
    public $testingClass = 'RightNow\Helpers\OkcsWhiteList';
    
   function testGetUpdatedEndPointUrlDataWhenDataArrayIsEmpty() {
        $okcsWhiteList = new \RightNow\Helpers\OkcsWhiteListHelper();
        $loadEndPointUrl = $okcsWhiteList->loadEndPointUrl();
        $apiUrlData = $okcsWhiteList->getUpdatedEndPointUrlData('subscriptionsBsdOnUsrRecordIdAndSubscriptionId');
        $this->assertIsA($apiUrlData, 'array');
        $this->assertIdentical('userRecordId', $apiUrlData['pathParameter']);
    }
    
    function testGetUpdatedEndPointUrlData() {
        $okcsWhiteList = new \RightNow\Helpers\OkcsWhiteListHelper();
        $loadEndPointUrl = $okcsWhiteList->loadEndPointUrl();
        $dataArray = array('userRecordId' => '00008998', 'subscriptionId' => '0001322345');
        $apiUrlData = $okcsWhiteList->getUpdatedEndPointUrlData('subscriptionsBsdOnUsrRecordIdAndSubscriptionId', $dataArray);
        $this->assertIsA($apiUrlData, 'array');
        $this->assertNull($apiUrlData['pathParameter']);
        $this->assertIdentical('/users/00008998/subscriptions/0001322345', $apiUrlData['apiEndPointUrl']);        
    }
}