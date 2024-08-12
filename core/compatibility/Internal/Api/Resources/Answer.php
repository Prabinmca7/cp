<?php
namespace RightNow\Internal\Api\Resources;
use RightNow\Api\Models\Answer as AnswerModel,
    RightNow\Internal\Api\Response,
    RightNow\Internal\Api\Structure\Document;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/Answer.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Models/KFSearch.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Base.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Response.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Request.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Document.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Meta.php';

class Answer extends Base{

    const ATTACHMENT_TYPE_ANSWER = 3;
    public function __construct() {
        $this->type = "answers";
        $this->attributeMapping = array(
            'kf' => array(
                'answer'        => array(
                    'body'          => 'Solution',
                    'created'       => 'CreatedTime',
                    'description'   => 'Question',
                    'files'         => 'FileAttachments',
                    'language'      => 'Language',
                    'title'         => 'Summary',
                    'type'          => 'AnswerType',
                    'updated'       => 'UpdatedTime',
                    'url'           => 'URL',
                ),
                'answerList'    => array(
                    'created'       => 'CreatedTime',
                    'excerpt'       => 'Excerpt',
                    'title'         => 'Title',
                    'updated'       => 'UpdatedTime',
                ),
                'file'          => array(
                    'created'       => 'CreatedTime',
                    'name'          => 'FileName',
                    'size'          => 'Size',
                    'type'          => 'ContentType',
                    'updated'       => 'UpdatedTime',
                    'url'           => 'URL',
                ),
                'search'        => array(
                    'created'       => 'created',
                    'excerpt'       => 'summary',
                    'title'         => 'text',
                    'updated'       => 'updated',
                )
            )
        );
    }

    /**
     * Fetches KF answer by ID and generates {json-api} document
     * @param array $params Contains queryParamters and uriParameters
     * @return object {json-api} top level document
     */
    public function getAnswer($params) {
        $answerId = $params['uriParams']['answers'];
        if($params['queryParams']['fields']) {
            $attributes = explode(',', $params['queryParams']['fields']['answers']);
        }
        else {
            $attributes = array_keys($this->attributeMapping['kf']['answer']);
        }

        $answer = new AnswerModel();
        $document = new Document();
        if($attribute = $this->validateAttributes($attributes, $this->attributeMapping['kf']['answer'])) {
            $errors = $this->createError(Response::getErrorResponseObject("Invalid attribute: $attribute", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $result = $answer->getById($answerId);

        if($result->errors) {
            $errors = $this->createError($result->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $data = $this->createData($result->result, $attributes);
        $document->setData($data);
        return $document->output();
    }

    /**
     * Fetches KF popular answers, performs search for given keywords, and generates {json-api} document
     * @param array $params Contains queryParamters and uriParameters
     * @return object {json-api} top level document
     */
    public function getAnswerList($params) {
        if(isset($params['queryParams']['filter']['$content']['contains'])) {
            return $this->search($params);
        }

        if($params['queryParams']['fields']) {
            $attributes = explode(',', $params['queryParams']['fields']['answers']);
        }
        else {
            $attributes = array_keys($this->attributeMapping['kf']['answerList']);
        }

        $document = new Document();
        if($attribute = $this->validateAttributes($attributes, $this->attributeMapping['kf']['answerList'])) {
            $errors = $this->createError(Response::getErrorResponseObject("Invalid attribute: $attribute", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $answer = new AnswerModel();
        $result = $answer->getPopular();

        if($result->errors) {
            $errors = $this->createError($result->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $data = $this->createDataCollection($result->result, $attributes);
        $document->setData($data);
        return $document->output();
    }

    /**
     * Searches KF answers for given keywords
     * @param type $params Contains queryParamters and uriParameters
     * @return object {json-api} top level document
     */
    private function search($params) {
        $document = new Document();

        if(empty($params['queryParams']['filter']['$content']['contains'])) {
            $errors = $this->createError(Response::getErrorResponseObject("Parameter filter[\$content][contains] cannot be empty", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        if(!isset($params['queryParams']['searchType'])) {
            $errors = $this->createError(Response::getErrorResponseObject("Missing required parameter: searchType", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        if(!in_array($params['queryParams']['searchType'], array('system', 'user'))) {
            $errors = $this->createError(Response::getErrorResponseObject("Invalid value for parameter: searchType", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        if($params['queryParams']['fields']) {
            $attributes = explode(',', $params['queryParams']['fields']['answers']);
        }
        else {
            $attributes = array_keys($this->attributeMapping['kf']['search']);
        }

        if($attribute = $this->validateAttributes($attributes, $this->attributeMapping['kf']['search'])) {
            $errors = $this->createError(Response::getErrorResponseObject("Invalid attribute: $attribute", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $search = new \RightNow\Api\Models\KFSearch();

        $query = $params['queryParams']['filter']['$content']['contains'];
        $offset = $params['queryParams']['page']['offset'] ? $params['queryParams']['page']['offset'] : $search::DEFAULT_OFFSET;
        $limit = $params['queryParams']['page']['limit'] ? $params['queryParams']['page']['limit'] : $search::DEFAULT_LIMIT;
        $result = $search->execute(array('query' => $query, 'offset' => $offset, 'limit' => $limit));

        if($result->errors) {
            $errors = $this->createError($result->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $data = $this->createSearchDataCollection($result->result, $attributes);
        $meta = $this->createMeta($result->result);
        $document->setData($data);
        $document->setMeta($meta);
        return $document->output();
    }
}
