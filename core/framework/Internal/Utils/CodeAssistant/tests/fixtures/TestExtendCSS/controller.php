<?php

class TestExtend extends WidgetBase
{
    function __construct()
    {
        parent::__construct();
        $this->attrs['test_attribute'] = new Attribute(getMessage(ALWAYS_SHOW_MASK_LBL), 'BOOL', getMessage(SET_TRUE_FLD_MASK_VAL_EXPECTED_MSG), false);
    }

    function generateWidgetInformation()
    {
        parent::generateWidgetInformation();
        $this->info['notes'] =  getMessage(WDGT_ALLWS_USRS_SET_FLD_VALS_DB_MSG);
    }

    function getData()
    {
        $this->data['test'] = 'test';
    }
}
