<?php

namespace Custom\Libraries;

class Sample
{
    /**
     * This library can be loaded a few different ways depending on where it's being called:
     *
     * From a widget or model: $this->CI->load->library('Sample');
     *
     * From a custom controller: $this->load->library('Sample');
     *
     * Everywhere else, including other libraries: $CI = get_instance();
     *                                             $CI->load->library('Sample')->sampleFunction();
     */
    function __construct(){

    }

    /**
     * Once loaded as described above, this function would be called in the following ways, depending on where it's being called:
     *
     * From a widget or model: $this->CI->sample->sampleFunction();
     *
     * From a custom controller: $this->sample->sampleFunction();
     *
     * Everywhere else, including other libraries: $CI = get_instance();
     *                                             $CI->sample->sampleFunction();
     */
    function sampleFunction()
    {

    }
}
