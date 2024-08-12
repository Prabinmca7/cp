<?php
namespace RightNow\Api\Models;

use RightNow\Internal\Api\Response,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Utils\Framework;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/Base.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Response.php';
require_once CORE_FILES . 'compatibility/Internal/Sql/Chat.php';

class CustomField extends Base {

    private $dataTypeMap = array(
        EUF_DT_DATE         => "DATE",
        EUF_DT_DATETIME     => "DATETIME",
        EUF_DT_SELECT       => "MENU",
        EUF_DT_INT          => "INTEGER",
        EUF_DT_MEMO         => "TEXTAREA",
        EUF_DT_VARCHAR      => "TEXT",
        EUF_DT_RADIO        => "YES_NO",
    );

    /**
     * Map old data type defines to new data type defines 
     */
    private $oldNewDataTypeMap = array(
        CDT_MENU        => EUF_DT_SELECT,
        CDT_BOOL        => EUF_DT_RADIO,
        CDT_INT         => EUF_DT_INT,
        CDT_DATETIME    => EUF_DT_DATETIME,
        CDT_VARCHAR     => EUF_DT_VARCHAR,
        CDT_MEMO        => EUF_DT_MEMO,
        CDT_DATE        => EUF_DT_DATE,
        CDT_OPT_IN      => EUF_DT_RADIO,
    );
    
    private $table = array(
        "incidents" => TBL_INCIDENTS
    );

    private $visibility = array(
        "chatDisplay"      => VIS_LIVE_CHAT,
        "endUserDisplay"   => VIS_ENDUSER_DISPLAY,
        "endUserEdit"      => VIS_ENDUSER_EDIT_RW
    );

    /**
     * Gets list of custom fields for the given table and visibility
     * @param int $table ID of the table
     * @param string $visibility Visibility of custom field
     * @param string $requestedFields Array of requested custom fields
     * @return array Collection of custom fields
     */
    public function getList($table, $visibility, $requestedFields){
        $table = $this->table[$table];
        $visibility = $this->visibility[$visibility];

        if(!$table) {
            return Response::getErrorResponseObject("Invalid value for type filter", Response::HTTP_BAD_REQUEST);
        }
        if(!$visibility) {
            return Response::getErrorResponseObject("Invalid value for visibility filter", Response::HTTP_BAD_REQUEST);
        }
        $customfields = array();
        $fields = \RightNow\Utils\Framework::getCustomFieldList($table, $visibility);
        $index = 0;

        foreach ($fields as $key => $value) {
            if(in_array($fields[$key]['col_name'], $requestedFields)) {
                $fields[$key]['data_type'] = $this->dataTypeMap[$this->oldNewDataTypeMap[$value['data_type']]];
                if($fields[$key]['data_type'] === 'MENU') {
                    $fields[$key]['menu_items'] = $this->getMenuItems($value['cf_id']);
                }
                $fields[$key]['ID'] = $fields[$key]['cf_id'];
                unset($fields[$key]['cf_id']);
                $fields[$key]['max_val'] = ($fields[$key]['max_val'] !== null) ? (int) $fields[$key]['max_val'] : $fields[$key]['max_val'];
                $fields[$key]['min_val'] = ($fields[$key]['min_val'] !== null) ? (int) $fields[$key]['min_val'] : $fields[$key]['min_val'];
                $fields[$key]['required'] = $fields[$key]['required'] ? true : false;
                $customfields[$index++] = (object) $fields[$key];
                unset($fields[$key]);
            }
        }
        return Response::getResponseObject($customfields, 'is_array');
    }

    /**
     * Fetches menu items for the given custom field
     * @param int $customFieldID ID of the custom field
     * @return array Collection of menu items
     */
    protected function getMenuItems($customFieldID) {
        $cacheKey = 'allCustomFieldMenuItems';
        if (($allMenuItems = Framework::checkCache($cacheKey)) === null) {
            $allMenuItems = \RightNow\Internal\Sql\Chat::getAllMenuItems();
            Framework::setCache($cacheKey, $allMenuItems);
        }
        foreach($allMenuItems[$customFieldID] as $value => $label) {
            $item = new \stdClass();
            $item->label = $label;
            $item->value = $value;
            $menuItems[] = $item;
        }
        return $menuItems;
    }
}
