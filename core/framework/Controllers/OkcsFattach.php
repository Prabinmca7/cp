<?php

namespace RightNow\Controllers;
use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Config,
    RightNow\Utils\Framework;

/**
 * Endpoint for retrieving and displaying file attachments.
 */
class OkcsFattach extends Base {
    const INVALID_CACHE_KEY = 'N';
    private $fileResourcePath;

    public function __construct() {
        parent::__construct();
        require_once CORE_FILES . 'compatibility/Internal/OkcsApi.php';
        $this->okcsApi = new \RightNow\compatibility\Internal\OkcsApi();
    }

    /**
     * Retrieves file attachment given an ID. Sends the file content to the browser.
     * @param int $answerID ID of the file attachment
     * @param object $attachmentData Attachment data to send to the client
     * @param object $answer File data to send to the client
     * @param string|null $fileType Type of file sent to client
    */
    public function get($answerID, $attachmentData = null, $answer = null, $fileType = null) {
        $status = 'PUBLISHED';
        if(is_null($attachmentData)) {
            if(!Text::stringContains($answerID, '_')) 
                Url::redirectToErrorPage(1);

            $position = (int)Text::getSubstringAfter($answerID, '_');
            if($position > 0) {
                $answerID = Text::getSubstringBefore($answerID, '_');
                if(Text::stringContains($answerID, '_')) {
                    $status = 'DRAFT';
                    $answerID = Text::getSubstringBefore($answerID, '_');
                }
                $imContent = $this->model('Okcs')->getAnswerViewData($answerID, null, null, null, 'v1', true);
                if($imContent === '/app/error/error_id/4') {
                    Url::redirectToErrorPage(4);
                    return;
                }

                if(!empty($imContent)) {
                    $answer = $this->getContentArray($imContent);
                    if(!$answer)
                        Url::redirectToErrorPage(1);
                }
                $attachmentUrl = Text::getSubstringAfter($answer[$position], ':~:');
                $fileName = Text::getSubstringBefore($answer[$position], ':~:');
            }
            else {
                Url::redirectToErrorPage(1);
            }
        }
        else {
            if($attachmentData === self::INVALID_CACHE_KEY)
                Url::redirectToErrorPage(1);

            if(Text::stringContains($answerID, '_')) {
                $status = 'DRAFT';
                $answerID = Text::getSubstringBefore($answerID, '_');
            }
            $queryParams = explode('/', Url::getParameterString());
            $searchSession = $queryParams[5];
            $transactionId = $queryParams[6];
            $searchAnswerId = $queryParams[7];
            $this->fileResourcePath = $this->model('Okcs')->getArticle($answerID, $status, 'v1', false)->resourcePath;
            $searchData = array(
                'answerType' => $fileType,
                'searchSession' => $searchSession,
                'transactionID' => $transactionId,
                'priorTransactionId' => $transactionId,
                'answerId' => $searchAnswerId,
                'locale' => str_replace("-", "_", \RightNow\Utils\Okcs::getInterfaceLocale())
            );
            $clickResponse = $this->okcsApi->getHighlightContent($searchData);
            $fileName = $this->model('Okcs')->decodeAndDecryptData($answer);
            if(Text::stringContains($fileName, 'ATTACHMENT:'))
                $fileName = Text::getSubstringAfter($fileName, 'ATTACHMENT:');
            if($fileType === 'PDF' || $fileType === 'TEXT') {
                if(Text::stringContains($fileName, '+')) $fileName = str_replace("+", "%2B", $fileName);
                $attachmentUrl = "/ci/okcsFattach/getFile/{$answerID}/{$fileName}";
                if ( $fileType === 'PDF' )
                    $sharableUrl = Url::getShortEufBaseUrl() . Text::getSubstringBefore($attachmentUrl, '#xml=');
                else
                    $sharableUrl = Url::getShortEufBaseUrl() . $attachmentUrl;
                $viewData = array(
                    'url' => $sharableUrl,
                    'type' => $fileType,
                    'file' => urldecode($attachmentUrl),
                    'copyLinkLable' => Config::getMessage(COPY_SHARABLE_LINK_CMD),
                    'copyClipboardMsg' => Config::getMessage(COPY_TO_CLIPBOARD_CTRL_PLUS_C_ENTER_CMD)
                );
                if(IS_OKCS_REFERENCE) {
                    $viewsPath = $this->load->_ci_view_path;
                    $this->load->_ci_view_path = CPCORESRC . 'views/';
                    $this->_loadView('admin/okcs/answer', $viewData);
                    $this->load->_ci_view_path = $viewsPath;
                }
                else {
                    $this->_loadView('admin/answer', $viewData);
                }
                return;
            }
            else if ($fileType === 'HTML') {
                $preventBrowserDisplay = true;
            }
            $attachmentUrl = $this->fileResourcePath . rawurlencode($fileName);
            $fileName = rawurldecode($fileName);
        }
        $attachmentInfo = array(
            'name'          => $fileName,
            'userFileName'  => $fileName,
            'attachmentUrl' => $attachmentUrl,
            'mimeType'      => $this->getMimeType(pathinfo($fileName, PATHINFO_EXTENSION))
        );
        $this->_sendContent($attachmentInfo, $preventBrowserDisplay);
    }

    /**
     * Retrieves file attachment given an answerID and fileName. Sends the file content to the browser.
     * @param int $answerID AnswerID
     * @param string $fileName File name
     */
    public function getFile($answerID, $fileName) {
        $fileName = str_replace("&#039;", '%27', $fileName);
        $resourcePath = $this->resourcePath ?: $this->model('Okcs')->getArticle($answerID, 'PUBLISHED', 'v1', false)->resourcePath;
        $attachmentUrl = $resourcePath . $fileName;
        $fileName = urldecode($fileName);
        
        $attachmentInfo = array(
            'name'          => $fileName,
            'userFileName'  => $fileName,
            'attachmentUrl' => $attachmentUrl,
            'mimeType'      => $this->getMimeType(pathinfo($fileName, PATHINFO_EXTENSION))
        );
        $this->_sendContent($attachmentInfo);
    }

    /**
     * Retrieves file attachment given an recommendationID. Sends the file content to the browser.
     * @param int $recordId Recommendation recordId
     */
    public function getRecommAttachment($recordId) {
        $response = $this->model('Okcs')->getRecommendationsView($recordId);
        $resourcePath = $response->resourcePath;
        $fileName = $response->fileName;
        $attachmentUrl = $resourcePath . rawurlencode($fileName);
        $fileName = str_replace("&#039;", '%27', $fileName);
        $fileName = urldecode($fileName);
        $attachmentInfo = array( 'name' => $fileName, 'userFileName' => $fileName, 'attachmentUrl' => $attachmentUrl, 'mimeType' => $this->getMimeType(pathinfo($fileName, PATHINFO_EXTENSION)) );
        $this->_sendContent($attachmentInfo);
    }

    /**
     * Sends the file attachment content to the client. Split out mainly to avoid duplication
     * with the InlineImage controller
     * @param array $attachment Attachment to send to the client
     * @param boolean $preventBrowserDisplay Prevent the file from being displayed in a browser
     */
    protected function _sendContent(array $attachment, $preventBrowserDisplay = false) {
        $fileSize = null;
        header("Content-Disposition: {$this->_getContentDisposition($attachment['userFileName'], $fileSize, $preventBrowserDisplay)}; filename=\"" . $attachment['userFileName'] . "\"");
        header(gmdate('Date: %a, %d %b %Y %H:%M:%S GMT'));
        header('Accept-Ranges: none'); // This means we don't allow the client to start up a request for the file in the middle.
        header('Content-Transfer-Encoding: binary');
        Framework::killAllOutputBuffering();
        $response = $this->okcsApi->getAttachment($attachment['attachmentUrl'], $attachment['mimeType']);
        if(Text::beginsWith($response, 'ERROR')) {
            header("Content-Type: text/html");
            $errorCode = Text::getSubstringAfter($response, ':');
            Url::redirectToErrorPage($errorCode);
        }
        else {
            if ($attachment['mimeType'])
                header("Content-Type: {$attachment['mimeType']}");
            echo $response;
        }
    }

    /**
     * Determines the value to send in the Content-Disposition header when sending the file
     * @param string $userFileName Name of the file
     * @param int $fileSize Size of the file in bytes
     * @param boolean $preventBrowserDisplay Prevent the file from being display in a browser
     * @return string Type of header to send, either 'attachment' or 'inline'
     */
    private function _getContentDisposition($userFileName, $fileSize, $preventBrowserDisplay = false) {
        // Fix to get around IE and Excel 07's warning message, IE and Word 07's pop-under behavior.
        if (preg_match("@[.](?:xl[a-z]{1,2}|doc[a-z]?)$@i", $userFileName) ||
            ($fileSize > 20 * 1024 * 1024) ||
            // Force the Content-Disposition to 'attachment' for Android phones.
            Text::stringContainsCaseInsensitive($_SERVER['HTTP_USER_AGENT'], 'Android') ||
            // For security purposes some customers may want to prevent certain attachments from being display in the browser
            $preventBrowserDisplay)
        {
            return 'attachment';
        }
        return 'inline';
    }

    /**
     * Displays headers and the specified view
     * @param string $view Name of view to load
     * @param array $data Data to pass down to the view
     */
    private function _loadView($view, array $data) {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Expires: Mon, 19 May 1997 07:00:00 GMT');
        $this->load->view($view, $data);
    }

    /**
     * Mime Types
     * @param string $ext File extension
     * @return string File mime type
     */
    private function getMimeType($ext = "") {
        $mimes = array(
            'doc'    => 'application/msword',
            'pdf'    => 'application/pdf',
            'xls'    => 'application/vnd.ms-excel',
            'ppt'    => 'application/vnd.ms-powerpoint',
            'gif'    => 'image/gif',
            'jpeg'   => 'image/jpeg',
            'jpg'    => 'image/jpeg',
            'jpe'    => 'image/jpeg',
            'png'    => 'image/png',
            'txt'    => 'text/plain',
            'text'   => 'text/plain',
            'log'    => 'text/plain',
            'rtx'    => 'text/richtext',
            'rtf'    => 'text/rtf',
            'xml'    => 'text/xml',
            'xsl'    => 'text/xml',
            'mpeg'   => 'video/mpeg',
            'mpg'    => 'video/mpeg',
            'mpe'    => 'video/mpeg',
            'doc'    => 'application/msword',
            'word'   => 'application/msword',
            'xl'     => 'application/excel',
            'js'     => 'text/plain',
        );
        return (!isset($mimes[strtolower($ext)])) ? "application/octet-stream" : $mimes[strtolower($ext)];
    }

    /**
     * Returns the content value array from the content data
     * @param array $contentData List of document content data
     * @return array List of content attribute values
     */
    private function getContentArray($contentData) {
        if (!is_array($contentData)) {
            return false;
        }
        $contentArray = array();
        foreach ($contentData['data'] as $data) {
            foreach ($data['content'] as $attribute){
                if ($attribute['position'] > 0) {
                    $contentArray[$attribute['position']] = $attribute['value'] . ':~:' . $attribute['filePath'];
                }
            }
        }
        return $contentArray;
    }
}
