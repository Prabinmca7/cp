<?php

namespace RightNow\Internal\Sql;

use RightNow\Utils\Framework;

final class Topicbrowse{

    /**
     * Gets cluster tree results
     * @return array Cluster tree
     */
    public static function getClusterResults() {
        $levelQuery = self::getClusterTreeLevels();
        $interfaceID = intf_id();
        $si = sql_prepare("SELECT ct.parent_id, ct.node_id, ct.tot_leaf_cnt, ci.summary, $levelQuery
                           FROM cluster_tree ct
                           JOIN cluster_info ci ON (ci.id = ct.node_id AND ci.interface_id = ct.interface_id)
                           WHERE ct.interface_id = $interfaceID AND ct.ptr_type = 1
                           GROUP BY ct.node_id ORDER BY $levelQuery");
        $j = 1;
        sql_bind_col($si, $j++, BIND_INT, 0);  // parent_id
        sql_bind_col($si, $j++, BIND_INT, 0);  // node_id
        sql_bind_col($si, $j++, BIND_INT, 0);  // tot_leaf_cnt (all answers including subfolders)
        sql_bind_col($si, $j++, BIND_NTS, 255);// cluster summary
        $startLevelBindNumbers = $j;
        for($i = 0; $i < MAXBROWSELEVELS; $i++) {
            sql_bind_col($si, $j++, BIND_INT, 0); //levels binding
        }

        $results = array();
        //order query will get all the parents before children so it is safe to increase
        //counts for all ancestors as we encounter each node
        for($i = 0; $row = sql_fetch($si); $i++) {
            $results[$i] = array(
                'parentID' => $row[0],
                'clusterID' => $row[1],
                'leafCount' => $row[2],
                'level' => 0,
                'summary' => htmlspecialchars($row[3], ENT_QUOTES, 'UTF-8'),
            );
            for($j = 0; $j < MAXBROWSELEVELS; $j++) {
                $bindNumber = $j + $startLevelBindNumbers - 1;
                if ($row[$bindNumber] === $clusterID) {
                    $results[$i]['level'] = $j;
                    break;
                }
            }
        }
        sql_free($si);
        return $results;
    }

    /**
     * Returns an array of all tree items and sub-items
     * @param string $searchQuery Search query to be used for filling weights (optional)
     * @return array List of browse tree items : (clusterID, weight, matchedLeaves, display)
     */
    public static function getSearchBrowseTreeResults($searchQuery) {
        if (!$tempTable = get_keyword_tmptbl($searchQuery, intf_id())) {
            return array();
        }

        $levelQuery = self::getClusterTreeLevels();
        $interfaceID = intf_id();
        //need level that is to be expanded and cluster_id that is to be expanded
        $si = sql_prepare("SELECT ct.node_id, MAX(ifnull(kt.weight,0)), COUNT(kt.weight), SUM(kt.weight), $levelQuery
                           FROM cluster_tree ct
                           JOIN cluster_tree co ON (co.parent_id = ct.node_id AND co.interface_id = ct.interface_id)
                           LEFT OUTER JOIN tmp_rightnow.$tempTable kt ON (co.id = kt.id AND co.ptr_type = 2)
                           WHERE ct.interface_id = $interfaceID AND ct.ptr_type = 1
                           GROUP BY ct.node_id ORDER BY $levelQuery");
        $j = 1;
        sql_bind_col($si, $j++, BIND_INT, 0); // node_id
        sql_bind_col($si, $j++, BIND_INT, 0); // max weight
        sql_bind_col($si, $j++, BIND_INT, 0); // count matches
        sql_bind_col($si, $j++, BIND_INT, 0); // avg of nonzero values
        $levelBindStart = $j - 1;
        for($i = 0; $i < MAXBROWSELEVELS; $i++)
        {
            sql_bind_col($si, $j++, BIND_INT, 0); // levels binding
        }
        //order query will get all the parents before children so it is safe to increase
        //counts for all ancestors as we encounter each node
        $results = $midResults = array();
        for($i = 0; $row = sql_fetch($si); $i++) {
            list($nodeID, $leafMaxWeight, $leafMatchCount, $leafSumWeight) = $row;
            $midResults[$nodeID] = array('nodeID' => $nodeID,
                                         'maxWeight' => $leafMaxWeight,
                                         'sumWeight' => $leafSumWeight,
                                         'weight' => $leafMaxWeight);
            //divide results into what we need only here and what we will eventually return
            //so we do not have to change or copy that later
            $results[$nodeID] = array('clusterID' => $nodeID,
                                      'weight' => $leafMaxWeight,
                                      'matchedLeaves' => $leafMatchCount,
                                      'display' => 'noDisplay');

            for($j = 0; $j < MAXBROWSELEVELS; $j++) {
                $midResults[$nodeID]['levels' . ($j + 1)] = $row[$j + $levelBindStart];
            }

            //go through levels and find first non-null value - that is the current node_id
            $currentLevel = MAXBROWSELEVELS;
            while ($currentLevel > 0) {
                if ($midResults[$nodeID]["levels{$currentLevel}"]) {
                    break;
                }
                $currentLevel--;
            }

            $currentLevel--;
            while ($currentLevel > 0) {
                // this means we do have ancestors
                //start with the leaf and add its counts to all the ancestors
                $ancestorID = $midResults[$nodeID]["levels{$currentLevel}"];
                if ($ancestorID > 0) {
                    $ancestorCount = $results[$ancestorID]['matchedLeaves'];
                    $ancestorSumWeight = $midResults[$ancestorID]['sumWeight'];
                    $ancestorMaxWeight = $midResults[$ancestorID]['maxWeight'];
                    //max of cluster includes all subcluster children in calculation
                    $midResults[$ancestorID]['maxWeight'] = max($ancestorMaxWeight, $leafMaxWeight);
                    //recalculate averages and counts; - include all subclusters in the counts
                    $midResults[$ancestorID]['sumWeight'] = $ancestorSumWeight + $leafSumWeight;
                    $results[$ancestorID]['matchedLeaves'] = $ancestorCount + $leafMatchCount;
                    //averages can be calculated from these values
                }
                $currentLevel--;
            }
        }

        //now calculate display weights and set results
        $runningMax = 0;
        $runningBestClusterId = 0;//mark which one is it to display as best cluster
        foreach($midResults as $topic) {
            $nodeID = $topic['nodeID'];
            if ($results[$nodeID]['matchedLeaves'] > 0) {
                //display matching clusters
                $results[$nodeID]['display'] = 'display';
                $results[$nodeID]['weight'] = $topic['maxWeight'];
                if ($topic['weight'] > $runningMax) {
                    $runningBestClusterId = $nodeID;
                    $runningMax = $topic['maxWeight'];
                }
                else if ($topic['weight'] === $runningMax) {
                    if ($results[$nodeID]['weight'] > $results[$runningBestClusterId]['weight']) {
                        $runningBestClusterId = $nodeID;
                    }
                }
            }
        }
        sql_free($si);
        $results[$runningBestClusterId]['display'] = 'bestMatch';
        return $results;
    }

    /**
     * Returns a string of cluter_tree levels (e.g. ct.lvl1_id) matching MAXBROWSELEVELS.
     *
     * @return string Generated query of cluster_tree levels
     */
    private static function getClusterTreeLevels() {
        $levels = '';
        foreach(range(1, MAXBROWSELEVELS - 1) as $level) {
            $levels .= "ct.lvl{$level}_id, ";
        }
        return substr($levels, 0, -2);
    }
}
