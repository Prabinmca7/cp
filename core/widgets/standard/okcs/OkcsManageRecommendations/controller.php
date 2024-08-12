<?php
namespace RightNow\Widgets;
use RightNow\Libraries\Search,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class OkcsManageRecommendations extends \RightNow\Widgets\SourceResultListing {
    private $manageRecommendationsApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        if($this->data['attrs']['source_id']) {
            $search = Search::getInstance($this->data['attrs']['source_id']);
            $this->data['js']['sources'] = $search->getSources();
        }
        $sortColumnID = Url::getParameter('sortColumn');
        $sortDirection = Url::getParameter('sortDirection');
        
        $filter = $this->getFilters();

        $recommendations = $this->CI->model('Okcs')->getRecommendationsSortedBy($filter);

        if (!is_null($recommendations)) {
            if (!isset($recommendations->error)) {
                $this->data['recommendations'] = $recommendations->items;
                $list = array();
                $this->data['fields'] = $this->getTableFields();
                if (!empty($this->data['fields'])) {
                    if(isset($recommendations->items) && is_array($recommendations->items)){
                        foreach ($recommendations->items as $document) {
                            foreach ($this->data['fields'] as $field) {
                                if (!property_exists($document, $field['name']) && $document->$field['name'] === null) {
                                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(RES_OBJECT_PROPERTY_PCT_S_IS_NOT_MSG), $field['name']));
                                    return false;
                                }
                            }
                            $item = array(
                                'priority'          => $document->priority,
                                'dateAdded'         => $document->dateAdded,
                                'dateModified'      => $document->dateModified,
                                'recordID'          => $document->recordId,
                                'title'             => $document->title,
                                'caseNumber'        => $document->referenceNumber,
                                'status'            => $document->status
                            );
                            array_push($list, $item);
                        }
                    }
                    $this->data['recommendations'] = $list;
                }
                else {
                    echo $this->reportError(\RightNow\Utils\Config::getMessage(MISSING_TITLE_IN_DISPFLDS_ADD_LBL));
                    return false;
                }

                Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filter);
                $this->data['js'] = array(
                    'recommendations'  => $this->data['recommendations'],
                    'filters'   => $filter,
                    'viewType'  => 'table',
                    'showHeaders' => $this->data['attrs']['show_headers'],
                    'sources' => $search->getSources()
                );
                
                $this->data['js']['sortColumn'] = $sortColumnID !== null ? $sortColumnID : "dateAdded";
                $this->data['js']['sortDirection'] = $sortDirection !== null ? $sortDirection : "DESC";
                $this->data['js']['headers'] = $this->data['fields'];
                $this->data['js']['dataTypes'] = array('date' => VDT_DATE, 'datetime' => VDT_DATETIME, 'number' => VDT_INT);
                $this->data['js']['manageRecommendationsApiVersion'] = $this->manageRecommendationsApiVersion;
            }
            else {
                echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($recommendations->error));
                return false;
            }
        }
    }

    /**
    * Lists the fields of the table based on the value of the attribute 'display_fields' and extracts the label to use for each field.
    * @return array list of display fields
    */
    private function getTableFields() {
        $fields = array();
        $index = 0;
        $isTitle = false;
        foreach (explode("|", $this->data['attrs']['display_fields']) as $field) {
            if (!$isTitle && $field === 'title')
                $isTitle = true;

            switch ($field) {
                case "title":
                    $labelAttribute = $this->data['attrs']['label_title'];
                    break;
                case "dateModified":
                    $labelAttribute = $this->data['attrs']['label_date_modified'];
                    break;
                case "dateAdded":
                    $labelAttribute = $this->data['attrs']['label_recommend_posted'];
                    break;
                case "priority":
                    $labelAttribute = $this->data['attrs']['label_recommend_priority'];
                    break;
                case "caseNumber":
                    $labelAttribute = $this->data['attrs']['label_recommend_case_number'];
                    break;
                default:
                    $labelAttribute = $this->data['attrs']['label_' . strtolower($field)];
            }

            $item = array(
                'name' => $field,
                'label' => $labelAttribute ?: $field,
                'columnID' => $index
            );
            $index++;
            array_push($fields, $item);
        }
        return (!$isTitle) ? array() : $fields;
    }

    /**
     * Method to get filters array.
     * @return array list of filters
     */
    private function getFilters() {
        return array(
            'limit' => isset($this->data['attrs']['limit']) ? $this->data['attrs']['limit'] : null,
            'pageNumber' => Url::getParameter('browsePage') !== null ? Url::getParameter('browsePage') : 1,
            'pageSize' => $this->data['attrs']['per_page'],
            'sortColumnId' => Url::getParameter('sortColumn'),
            'sortDirection' => Url::getParameter('sortDirection'),
            'manageRecommendationsApiVersion' => $this->manageRecommendationsApiVersion
        );
    }
}
