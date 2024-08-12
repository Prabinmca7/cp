<?php
namespace RightNow\Widgets;
use RightNow\Utils\Url;

class ForumList extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData() {
        $dataType = ucfirst($this->data['attrs']['type']);
        
        $prodCatHierarchy = $userProducts = $commentCount = $questionCount = array();
        $prodCatValue = $this->data['attrs']['show_sub_items_for'];

        $this->data['productHeader'] = $this->data['attrs']['label_forum'];
        foreach ($this->data['attrs']['show_columns'] as $metadata) {
            $this->data['tableHeaders'][$metadata] = $this->data['attrs']['label_'.$metadata];
        }
        if($prodCatValue !== 0){
            $defaultHierMap = $this->CI->model('Prodcat')->getFormattedTree($dataType, array($prodCatValue))->result;
            $prodCatHierarchy = $prodCatValue ? $defaultHierMap[$prodCatValue] : $defaultHierMap[0];
            if (!$prodCatHierarchy)
                return false;
        }
        $userProducts = $this->getPermissionedForums($dataType, $prodCatHierarchy);

        if ($this->data['attrs']['specify_forums']) {
            $this->data['attrs']['sort_order'] = 'no_sort';
            $userProducts = array_slice(($userProducts), 0, $this->data['attrs']['max_forum_count'], true);
        }
        else if ($this->data['attrs']['sort_order'] === 'prodcat_id') {
            ksort($userProducts);
            $userProducts = array_intersect_key(array_replace(array_flip(explode(',', $this->data['attrs']['sticky_forums'])), $userProducts), $userProducts);
            $userProducts = array_slice(($userProducts), 0, $this->data['attrs']['max_forum_count'], true);
        }

        if (!$this->data['attrs']['specify_forums'] && $this->data['attrs']['sticky_forums']) {
            $userProducts = array_intersect_key(array_replace(array_flip(explode(',', $this->data['attrs']['sticky_forums'])), $userProducts), $userProducts);
        }

        if ($this->data['attrs']['sort_order'] !== 'comment_count') {
            $questionCount = $this->CI->model('CommunityQuestion')->getQuestionCountByProductCategory($dataType, array_keys($userProducts), $this->data['attrs']['show_columns'], $this->data['attrs']['max_forum_count'], $this->data['attrs']['sort_order'])->result;

            if (in_array('comment_count', $this->data['attrs']['show_columns']) && $questionCount) {
                $commentCount = $this->CI->model('CommunityComment')->getCommentCountByProductCategory($dataType, array_keys($questionCount['questionCount']), $this->data['attrs']['max_forum_count'])->result;
            }
        }
        else {
            $commentCount = $this->CI->model('CommunityComment')->getCommentCountByProductCategory($dataType, array_keys($userProducts), $this->data['attrs']['max_forum_count'], $this->data['attrs']['sort_order'])->result;

            if (count($commentCount) === $this->data['attrs']['max_forum_count']) {
                $count = $this->data['attrs']['max_forum_count'];
                $prodCats = array_keys($commentCount);
                $sortOrder = 'comment_count';
            }
            else {
                $count = null;
                $prodCats = array_keys($userProducts);
                $sortOrder = 'question_count';
            }

            $questionCount = $this->CI->model('CommunityQuestion')->getQuestionCountByProductCategory($dataType, $prodCats, $this->data['attrs']['show_columns'], $count, $sortOrder)->result;
            $fetchedResult = array_keys($commentCount);
            $questionProdCats = array_keys($questionCount['questionCount']);
            for ($i = 0; (count($commentCount) < $this->data['attrs']['max_forum_count']) && ($i < count($questionProdCats)); $i++) {
                if (!in_array($questionProdCats[$i], $fetchedResult)) {
                    $commentCount[$questionProdCats[$i]] = null;
                    $commentCount[$questionProdCats[$i]] = null;
                }
            }
        }

        $this->data['question_count'] = $questionCount['questionCount'];
        $this->data['last_activity'] = $questionCount['lastActivity'];
        $this->data['comment_count'] = $commentCount;
        $this->data['prodcat_id'] = $this->data['no_sort'] = $userProducts;
        if ($this->data['attrs']['sort_order'] !== 'prodcat_id' && !$this->data['attrs']['specify_forums'] && (count($this->data[$this->data['attrs']['sort_order']]) < $this->data['attrs']['max_forum_count'])) {
            $fetchedResult = array_keys($this->data[$this->data['attrs']['sort_order']]);
            $permissionedProdCats = array_keys($userProducts);
            for ($i = 0; (count($this->data[$this->data['attrs']['sort_order']]) < $this->data['attrs']['max_forum_count']) && ($i < count($permissionedProdCats)); $i++){
                if (!in_array($permissionedProdCats[$i], $fetchedResult)) {
                    $this->data[$this->data['attrs']['sort_order']][$permissionedProdCats[$i]] = null;
                    $this->data[$this->data['attrs']['sort_order']][$permissionedProdCats[$i]] = null;
                }
            }
        }

        if ($this->data['attrs']['sticky_forums']) {
            $this->data[$this->data['attrs']['sort_order']] = array_intersect_key(array_replace(array_flip(explode(',', $this->data['attrs']['sticky_forums'])), $this->data[$this->data['attrs']['sort_order']]), $userProducts);
        }
    }

    /**
     * Returns the permissioned forums (products or categories) for the logged-in user.
     * @param bool $dataType Type of hierarchy: 'Product' or 'Category'
     * @param array $prodCatHierarchy  Map containing the default products/categories the user will have access to
     * @return array Updated map of products/categories the user will have access to
     */
    function getPermissionedForums($dataType, array $prodCatHierarchy) {
        $isProduct = ($dataType === 'Product');
        $userPermissionedForums = $prodcatList = array();
        $stickyForumIDs = explode(',', $this->data['attrs']['sticky_forums']);

        if($this->data['attrs']['specify_forums']) {
            $specifyForumIDs = array_flip(explode(',', $this->data['attrs']['specify_forums']));
            $prodcatList = $this->data['attrs']['sticky_forums'] ? array_replace(array_flip($stickyForumIDs), $specifyForumIDs) : $specifyForumIDs;
        }
        else {
            if(!empty($prodCatHierarchy)) {
                $prodcatList = array_flip(array_map(function ($item) {
                        return $item['id'];
                }, $prodCatHierarchy));
            }
        }

        if(empty($prodCatHierarchy)){
            $permissionedHierarchy = $this->CI->model('Prodcat')->getPermissionedListSocialQuestionRead($isProduct)->result;
            $prodCatByID = $this->CI->model('Prodcat')->getProdCatByIDs($dataType, array_keys($prodcatList), $this->data['attrs']['show_forum_description'])->result;
            $specifiedForums = $this->data['attrs']['specify_forums'] ? array_replace(array_intersect_key($specifyForumIDs, $prodCatByID), $prodCatByID) : $prodCatByID;
            $prodcatList = $this->data['attrs']['sticky_forums'] ? array_replace(array_intersect_key(array_flip($stickyForumIDs), $prodCatByID), $specifiedForums) : $specifiedForums;
        }
        else {
            $prodcatList = ($this->data['attrs']['sticky_forums'] && !$this->data['attrs']['specify_forums']) ? array_replace(array_flip($stickyForumIDs), $prodcatList) : $prodcatList;
            $permissionedHierarchy = $this->CI->model('Prodcat')->getPermissionedListSocialQuestionRead($isProduct)->result;
        }

        if($permissionedHierarchy) {
            if(is_array($permissionedHierarchy)) {
                $permissionedProducts = array_reduce($permissionedHierarchy, function ($result, $item) {
                    $result[$item['ID']] = $item['Label'];
                    return $result;
                }, array());
                $userPermissionedForums = array_intersect_key($prodcatList, $permissionedProducts);
            }
            else {
                $userPermissionedForums = $prodcatList;
            }

            if(!empty($prodCatHierarchy)){
                $result = $this->CI->model('Prodcat')->getProdCatByIDs($dataType, array_keys($userPermissionedForums), $this->data['attrs']['show_forum_description'])->result;
                $userPermissionedForums = $result ? array_replace(array_intersect_key($userPermissionedForums, $result), $result) : $result;
            }
        }
        return $userPermissionedForums;
    }
}
