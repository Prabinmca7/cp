<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Utils;

class OkcsMockFAS extends \RightNow\Controllers\Base {
    private $cacheData;
    private $cache;
    private $apiVersion;
    private $helper;

    function __construct(){
        parent::__construct(true, '_phonyLogin');
        umask(0);
        require_once CPCORE . 'Controllers/UnitTest/okcs/mockservice/OkcsTestHelper.php';
        $this->helper = new \RightNow\Controllers\UnitTest\okcs\mockservice\OkcsTestHelper();
    }

    public function index(){
        //Use this function to test this controller
        $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.ods';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    public function resources(){
        $queryUrl = $this->helper->getParameterString();
        $queryParameters = explode("/", $queryUrl);
        $fileType = $this->getFileType($queryParameters[1]);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        switch($fileType){
            case "PDF" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.pdf';
                break;
            case "HTML" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.html';
                break;
            case "DOC" :
            case "DOCX" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.docx';
                break;
            case "XLS" :
            case "XLSX" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.xlsx';
                break;
            case "PPT" :
            case "PPTX" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.pptx';
                break;
            case "TXT" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.txt';
                break;
            case "XML" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.xml';
                break;
            case "RTF" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.rtf';
                break;
            case "CMS-XML" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.xml';
                break;
            case "ODT" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.odt';
                break;
            case "ODP" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.odp';
                break;
            case "ODS" :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.ods';
                break;
            default :
                $file = CPCORE . 'Controllers/UnitTest/okcs/mockservice/Files/Mock.pdf';
        }
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    private function getFileType($fileName){
        return strtoupper(end(explode('.', $fileName)));
    }
}
