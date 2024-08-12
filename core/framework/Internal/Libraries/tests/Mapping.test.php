<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
class MappingClassTest extends CPTestCase
{
    function __construct()
    {
    }

    function getMapping()
    {
        $mapping = new \RightNow\Internal\Libraries\Mapping();
        $mapping->createPathOut("home");
        $parameterMapping = array('p_cred' => 'cred', 'session' => 'session', 'p_search_text' => 'kw', 'p_prods' => 'p', 'p_cats' => 'c',
            'p_key' => array('p'  => array('p_list', '0'), 'c'  => array('p_list', '1'), 'kw' => array('p_type', '3')));
        $mapping->createParameterMapping($parameterMapping, null);
        return $mapping;
    }
    
    function testMappingGetPath()
    {
        $mapping = $this->getMapping();
        $mapping->createPathOut("home");
        $this->assertEqual("/app/home", $mapping->getPath());
        $mapping->createPathOut("/app/home");
        $this->assertEqual("/app/home", $mapping->getPath());
        $mapping->createPathOut("/ci/controller1");
        $this->assertEqual("/ci/controller1", $mapping->getPath());
        $mapping->createPathOut("/cc/controller2");
        $this->assertEqual("/cc/controller2", $mapping->getPath());
    }
    
    function testParameterMapping()
    {
        $mapping = $this->getMapping();
        $mapping->createParamOut("p_search_text=1", null);
        $this->assertEqual("/app/home/kw/1/search/1", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("p_search_text=1&p_prods=2&p_cats=3", null);
        $this->assertEqual("/app/home/kw/1/p/2/c/3/search/1", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("garbage=1&p_prods=2&p_cats=3", null);
        $this->assertEqual("/app/home/p/2/c/3/search/1", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("garbage=1", null);
        $this->assertEqual("/app/home", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("p_type=3&p_key=phone", null);
        $this->assertEqual("/app/home/kw/phone/search/1", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("p_type=3&amp;p_key=phone", null);
        $this->assertEqual("/app/home/kw/phone/search/1", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("p_list=0&p_key=1:4:160", null);
        $this->assertEqual("/app/home/p/160/search/1", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("p_list=1&p_key=71:77", null);
        $this->assertEqual("/app/home/c/77/search/1", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("garbage=1&trash=2", function($key) { return $key === 'garbage';});
        $this->assertEqual("/app/home/garbage/1", $mapping->getPath());

        $mapping = $this->getMapping();
        $mapping->createParamOut("garbage=1&trash=2", function($key) { return $key === 'trash';});
        $this->assertEqual("/app/home/trash/2", $mapping->getPath());
    }
}
