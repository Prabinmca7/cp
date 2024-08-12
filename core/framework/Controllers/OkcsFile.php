<?php

namespace RightNow\Controllers;
use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Config;

/**
 * Endpoint for retrieving and displaying file attachments.
 */
class OkcsFile extends Base {
    const INVALID_CACHE_KEY = 'N';
    public function __construct() {
        parent::__construct();
        require_once CORE_FILES . 'compatibility/Internal/OkcsApi.php';
        $this->okcsApi = new \RightNow\compatibility\Internal\OkcsApi();
    }

    /**
     * Retrieves file attachment given an ID. Sends the file content to the browser.
     * @param int $answerID ID of the file attachment
     * @param object $attachmentData Attachment data to send to the client
     * @param int $priorTxnID Prior Transaction ID of Search
     * @param string|null $fileType Attachment File Type
     * @param int $transactionID Transaction ID for Search
     * @param string $externalURL External URL for Search
    */
    public function get($answerID, $attachmentData = null, $priorTxnID = null, $fileType = null, $transactionID = null, $externalURL = null) {
        if($attachmentData === self::INVALID_CACHE_KEY)
            Url::redirectToErrorPage(1);

        $viewData = $this->_getHtmlData($answerID, $attachmentData, $priorTxnID, $fileType, $transactionID, $externalURL);

        if($fileType === 'PDF' || $fileType === 'HTML' || $fileType === 'TEXT') {
            if(IS_OKCS_REFERENCE) {
                $viewsPath = $this->load->_ci_view_path;
                $this->load->_ci_view_path = CPCORESRC . 'views/';
                $this->_loadView('admin/okcs/answer', $viewData);
                $this->load->_ci_view_path = $viewsPath;
            }
            else {
                $this->_loadView('admin/answer', $viewData);
            }
        }
        else {
            header("Location:" . $viewData['url']);
        }
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
     * Returns HTML Data
     * @param int $answerID ID of the file attachment
     * @param object $attachmentData Attachment data to send to the client
     * @param int $priorTxnID Prior Transaction ID of Search
     * @param int $fileType Attachment File Type
     * @param int $transactionID Transaction ID for Search
     * @param string $externalURL External URL for Search
     */
    private function _getHtmlData($answerID, $attachmentData, $priorTxnID, $fileType, $transactionID, $externalURL) {
        $viewData = array(
            'highlightMsg' => Config::getMessage(YOU_ARE_VIEWING_A_HIGHLIGHTED_MSG),
            'copyLinkLable' => Config::getMessage(COPY_SHARABLE_LINK_CMD),
            'copyClipboardMsg' => Config::getMessage(COPY_TO_CLIPBOARD_CTRL_PLUS_C_ENTER_CMD),
            'viewLabel' => Config::getMessage(VIEW_WITHOUT_HIGHLIGHTING_CMD)
        );

        $searchData = array('answerId' => $answerID, 'searchSession' => $attachmentData, 'prTxnId' => $priorTxnID, 'txnId' => $transactionID , 'ansType' => $fileType);
        $htmlData = $this->model('Okcs')->getHighlightedHTML($searchData);

        $viewData['url'] = $htmlData['url'];
        if($fileType === 'PDF' || $fileType === 'TEXT' || ($fileType === 'HTML' && empty($htmlData['html']))) {
            $viewData['type'] = $fileType;
            $viewData['file'] = $htmlData['url'];
        }
        else {
            $htmlBaseRef = '';
            preg_match_all('/<base [^>]+>/i', $htmlData['html'], $matchRef);
            if($matchRef[0][0] !== null){
                $htmlBaseRef = $matchRef[0][0];
            }
            $viewData['html'] = $htmlData['html'];
            $viewData['htmlBaseRef'] = $htmlBaseRef;
        }
        if($fileType === 'HTML' && empty($htmlData['html'])) {
            $viewData['highlightMsg'] = null;
        }
        if(empty($viewData['html']) && empty($viewData['url'])){
            $viewData['file'] = urldecode($externalURL);
            header("Location:" . $viewData['file']);
        }

        return $viewData;
    }
}
