<?php

namespace RightNow\Internal\Sql;

final class Guidedassistance{
    /**
    * Returns a dttm timestamp value for the specified workflow image id.
    * @param int $imageID Image ID
    * @return int Timestamp of image creation
    */
    public function getWorkflowImageCreation($imageID){
        return sql_get_dttm(sprintf("SELECT created FROM fattach where file_id = %d", $imageID));
    }
}
