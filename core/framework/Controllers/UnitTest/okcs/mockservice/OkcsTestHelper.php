<?php

namespace RightNow\Controllers\UnitTest\okcs\mockservice;

use \RightNow\Utils\Url;
use \RightNow\Api;
use \RightNow\Utils;

class OkcsTestHelper
{
    /*
        This method returns the headers
    */
    public static function getHeaderInfo(){
        $headers = array();
        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }
    
    /*
        This method returns the complete url
    */
    public static function getActualCall(){
        return Url::getOriginalUrl(true);
    }
    
    /*
        This method returns the payload data
    */
    public static function getPayloadInfo(){
        $input = file_get_contents('php://input');
        return $input;
    }
    
    /*
        This method checks if the user is authenticated
    */
    public static function isUserAuthenticated(){
        $headerInfo = self::getHeaderInfo();
        $kmAuthToken = json_decode($headerInfo['Kmauthtoken'],true);
        if(is_array($kmAuthToken) && array_key_exists('userToken',$kmAuthToken) && ($kmAuthToken['userToken'])!=null){
            return true;
        }
        else{
            return false;
        }
        
    }
    
    /*
        This method returns the url parameters
    */
    public static function getParameterString(){
        return Url::getParameterString();
    }
    
    /*
        This method returns the Query parameters
    */
    public static function getQueryParameters(){
        return $_GET;
    }
    
    public static function getFileContents($fileName){
        $queryParameterArray = explode('/', OkcsTestHelper :: getParameterString());
        $userState = self::isUserAuthenticated() ? 'authenticated' : 'guest';
        if(file_exists(CPCORE . 'Controllers/UnitTest/okcs/mockservice/km/api/' . $queryParameterArray[1] . '/')){
            $filePath = CPCORE . 'Controllers/UnitTest/okcs/mockservice/km/api/' . $queryParameterArray[1] . '/' . $userState. $fileName;
            if(file_exists ($filePath)){
                self::html_response_code(200);
                header('Content-Type: application/json');
                $fileContents = fopen($filePath, "r") or exit("Unable to open file!");
                $response = fgets($fileContents);
                fclose($fileContents);
                return $response;
            }
        }
        else{
            self::html_response_code(500);
            echo 'Mock data unavailable for the API version';
        }
    }
    
    /*
        This method returns appropriate html response
        codes for any errors
    */
    public static function html_response_code($code){
        switch($code) {
            case 0:
                header(' ',true,0);
                echo '<h1>API Request Timed Out</h1>';
                break;
            case 200:
                header(' ',true,200);
                header("Content-Type:application/json", false);
                break;
            case 204:
                header(' ',true,204);
                header("Content-Type:application/json", false);
                break;
            case 400:
                header(' ',true,400);
                header("Content-Type:application/json", false);
                echo '{"statusCode":"400","error":{"message":"Bad Request"}}'; 
                break;
            case 401:
                header(' ',true,401);
                echo '<h1>Unauthorized</h1>';
                break;
            case 402:
                header(' ',true,402);
                echo '<h1>Payment Required</h1>';
                break;
            case 403:
                header(' ',true,403);
                echo '<h1>Forbidden</h1>';
                break;
            case 404:
                header(' ',true,404);
                header("Content-Type:application/json", false);
                echo '{"statusCode":"404","error":{"message":"Expected object not found. Cause: Key not found","code":"OK-GEN0002","type":"VALIDATION"}}';
                break;
            case 405:
                header(' ',true,405);
                header("Content-Type:application/json", false);
                echo '{"statusCode":"405","error":{"message":"Method not allowed","code":"OK-GEN0002","type":"APPLICATION"}}';
                break;
            case 406:
                header(' ',true,406);
                echo '<h1>Not Acceptable</h1>';
                break;
            case 407:
                header(' ',true,407);
                echo '<h1>Proxy Authentication Required</h1>';
                break;
            case 408:
                header(' ',true,408);
                echo '<h1>Request Time-out</h1>';
                break;
            case 409:
                header(' ',true,409);
                echo '<h1>Conflict</h1>';
                break;
            case 410:
                header(' ',true,410);
                echo '<h1>Gone</h1>';
                break;
            case 411:
                header(' ',true,411);
                echo '<h1>Length Required</h1>';
                break;
            case 412:
                header(' ',true,412);
                echo '<h1>Precondition Failed</h1>';
                break;
            case 413:
                header(' ',true,413);
                echo '<h1>Request Entity Too Large</h1>';
                break;
            case 414:
                header(' ',true,414);
                echo '<h1>Request-URI Too Large</h1>';
                break;
            case 415:
                header(' ',true,415);
                echo '<h1>Unsupported Media Type</h1>';
                break;
            case 500:
                header(' ',true,500);
                header("Content-Type:application/json", false);
                echo '{"statusCode":"500","error":{"message":"Internal Server Error"}}';
                break;
            case 501:
                header(' ',true,501);
                echo '<h1>Not Implemented</h1>';
                break;
            case 502:
                header(' ',true,502);
                echo '<h1>Bad Gateway</h1>';
                break;
            case 503:
                header(' ',true,503);
                echo '<h1>Service Unavailable</h1>';
                break;
            case 504:
                header(' ',true,504);
                echo '<h1>Gateway Time-out</h1>';
                break;
            case 505:
                header(' ',true,505);
                echo '<h1>HTTP Version not supported</h1>';
                break;
             default:
                exit('Unknown http status code "' . htmlentities($code) . '"');
                break;
        }
    }
    
    public static function updateMockAnswerResults($okcsImApiUrl,$rangeArray){
        if (!extension_loaded('curl') && !@Api::load_curl())
            return null;
        foreach($rangeArray as $i){
               
                $url = $okcsImApiUrl.'content/answers/' . $i . '?mode=FULL';
                echo $url;
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $url,
                    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
                ));
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'kmauthtoken: {"localeId":"en_US","knowledgeInteractionId":null,"billableSessionId":null,"appId":null,"siteName":"okcs__ok152b1__t2","interfaceId":null,"requiresBillable":null,"captureAnalytics":null,"integrationUserToken":"4t8nEPK/H013JYKO8fXhY01btVGfiIgHmRLZ4EkjdWpAT46UmlqhKxMr6sHZ9R4vfOVmwlwkJsCmSDu33MHzOMpxUjyej0K7DtfbBycFxfGniGPUejdgSO6K/Fgm+h1w","userToken":"q0EqGu4iu3eWP6dY7WuV2kt7wGYYWP8DdVwzf+W6YJKoaOyT14yVsWwjHUsHvx/kGylOgCjzVkk0eCRR9WDj296ASWs8F89WqxfW/Eow2GOZkxo4D1MiSxzlQ1Vq93QWjCMej9H//0yxKl2q6NkHSQ==","referrer":null,"querySource":null}',
                    'Accept: application/json'
                ));
                $resp = curl_exec($curl);
                $FilePath = CPCORE.'Controllers/UnitTest/okcs/'.$i.'.txt';
                echo $FilePath;
                $handle = fopen($FilePath, "wb") or exit('Cannot open file:  '.$my_file);
                fwrite($handle, $resp); 
                fclose($handle);
                curl_close($curl);
        }
    }
    
    /**
    * This Method returns decoded and decrypted data
    * @param string $data Encoded and encrypted data
    * @return string Decoded and decrypted answer data
    */
    public function decodeAndDecryptData($data) {
        // @codingStandardsIgnoreStart
        $decodedData = Api::decode_base64_urlsafe($data);
        return Api::ver_ske_decrypt($decodedData);
        // @codingStandardsIgnoreEnd
    }
}
