<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <h2><?=sprintf($this->data['attrs']['label_title'], $this->data['totalComments']);?></h2>
    <rn:block id="preCommentList"/>
    <ul id="rn_<?=$this->instanceID;?>_CommentListing">
    <?
        if(is_array($this->data['commentStack']) && count($this->data['commentStack']))
        {
            $level = 0;
            $isOddRow = true;
            while(is_array($this->data['commentStack']) && count($this->data['commentStack']))
            {
                $currentComment = array_pop($this->data['commentStack']);
                //Sibling
                if($currentComment->level === $level)
                {
                    if($level === 0)
                    {
                        $rowClass = $isOddRow ? 'rn_Odd' : 'rn_Even';
                        echo "<li class='rn_PostContainer $rowClass'>";
                        $isOddRow = !$isOddRow;
                    }
                    else
                    {
                        echo '<li>';
                    }
                }
                //Child comment
                else if($currentComment->level > $level)
                {
                    echo '<ul class="rn_NestedComment"><li>';
                }
                //Parent comment
                else if($currentComment->level < $level)
                {
                    for($i = $level; $i > $currentComment->level; $i--)
                        echo '</li></ul>';
                    echo '</li>';
                    if($currentComment->level === 0)
                    {
                        $rowClass = $isOddRow ? 'rn_Odd' : 'rn_Even';
                        echo "<li class='rn_PostContainer $rowClass'>";
                        $isOddRow = !$isOddRow;
                    }
                    else
                    {
                         echo '<li>';
                    }
                }
                $level = $currentComment->level;
    ?>
        <div id="rn_<?=$this->instanceID;?>_Content<?=$currentComment->id;?>" class="rn_CommentContent">
            <rn:block id="preAuthor"/>
            <div class="rn_CommentAuthor">
            <? if($currentComment->ratingCount > 0):?>
                <rn:block id="preCurrentRating"/>
                <div class="rn_CommentDetails">
                    <?=$this->data['attrs']['label_rating'];?>
                <? if($currentComment->ratingImage):?>
                    <img src="<?=$currentComment->ratingImage;?>" alt=""/>&nbsp;
                <? endif;?>
                    <span class="<?=$currentComment->ratingClass;?>"><?=$currentComment->ratingPercentage;?>%</span> (+<?=$currentComment->ratingUp;?>/-<?=$currentComment->ratingDown;?>)
                </div>
                <rn:block id="postCurrentRating"/>
            <? endif;?>
            <? if($currentComment->createdBy->avatar):?>
                <rn:block id="preAuthorAvatar"/>
                <a id="rn_<?=$this->instanceID;?>_Author" class="rn_AuthorImage" href="<?=$currentComment->createdBy->webUri . ($this->data['attrs']['author_link_base_url'] ? '' : \RightNow\Utils\Url::communitySsoToken());?>">
                    <img src="<?=$currentComment->createdBy->avatar;?>" alt=""/>
                </a>
                <rn:block id="postAuthorAvatar"/>
            <? endif;?>
                <rn:block id="preAuthorName"/>
                <a href="<?=$currentComment->createdBy->webUri . ($this->data['attrs']['author_link_base_url'] ? '' : \RightNow\Utils\Url::communitySsoToken());?>"><?=$currentComment->createdBy->name;?></a><br/>
                <rn:block id="postAuthorName"/>
                <rn:block id="preCommentTime"/>
                <span class="rn_CommentTime">
                    <?=$currentComment->lastEdited;?>
                <? if($currentComment->edited):?>
                    <?=$this->data['attrs']['label_edited'];?>
                <? endif;?>
                </span>
                <rn:block id="postCommentTime"/>
            </div>
            <rn:block id="postAuthor"/>
            <rn:block id="preCommentText"/>
            <p id="rn_<?=$this->instanceID;?>_Text<?=$currentComment->id;?>"><?=$currentComment->value;?></p>
            <rn:block id="postCommentText"/>
        <? if($this->data['contactID'] && !in_array($currentComment->status, array(COMMENT_STATUS_DELETED, COMMENT_STATUS_SUSPENDED, COMMENT_STATUS_PENDING)) && !$this->data['js']['newUser']):?>
            <div class="rn_CommentActions" id="rn_<?=$this->instanceID;?>_Actions<?=$currentComment->id;?>">
                <rn:block id="preActionBar"/>
            <? if($this->data['attrs']['label_reply'] && $this->data['permissions']->commentCreate && ($level + 1 < $this->data['attrs']['max_comment_depth'])):?>
                <a href="javascript:void(0);" data-action="reply" data-commentID="<?=$currentComment->id;?>"><?=$this->data['attrs']['label_reply'];?></a> |
            <? endif;?>
            <? if($this->data['attrs']['label_edit'] && ($this->data['contactID'] === $currentComment->createdBy->guid && $this->data['permissions']->commentEditOwn) || $this->data['permissions']->commentEditAll):?>
                <a href="javascript:void(0);" data-action="edit" data-commentID="<?=$currentComment->id;?>"><?=$this->data['attrs']['label_edit'];?></a> |
                <span id="rn_<?=$this->instanceID;?>_DeleteContainer<?=$currentComment->id;?>">
                    <a href="javascript:void(0);" id="rn_<?=$this->instanceID;?>_DeleteContent<?=$currentComment->id;?>" data-action="delete" data-commentID="<?=$currentComment->id;?>"><?=$this->data['attrs']['label_delete'];?></a>
                </span> |
            <? endif;?>
            <? if($this->data['attrs']['label_flag'] && ($this->data['contactID'] !== $currentComment->createdBy->guid) && $this->data['permissions']->flaggingEnabled):?>
                <span id="rn_<?=$this->instanceID;?>_FlagContainer<?=$currentComment->id;?>">
                <? if($currentComment->flaggedByRequestingUser->created):?>
                    <?=$this->data['attrs']['label_flagged'];?>
                <? else:?>
                    <a href="javascript:void(0);" id="rn_<?=$this->instanceID;?>_FlagContent<?=$currentComment->id;?>" data-action="flag" data-commentID="<?=$currentComment->id;?>"><?=$this->data['attrs']['label_flag'];?></a>
                <? endif;?>
                </span> |
            <? endif;?>
            <? if($this->data['contactID'] !== $currentComment->createdBy->guid && $this->data['permissions']->ratingEnabled):?>
            <? if($currentComment->ratedByRequestingUser->created):?>
                <?=$this->data['attrs']['label_rated'];?>
            <? else:?>
                <?=$this->data['attrs']['label_rate'];?>
            <? endif;?>
                <span id="rn_<?=$this->instanceID;?>_RateContainer<?=$currentComment->id;?>">
                    <span class="rn_Rating" id="rn_<?=$this->instanceID;?>_RateContent<?=$currentComment->id;?>">
                    <? if($currentComment->ratedByRequestingUser->created):?>
                        <span class="<?=($currentComment->ratedByRequestingUser->ratingValue) ? 'rn_ThumbUp' : 'rn_ThumbDown';?> rn_RatingIcon rn_Selected">
                            <span class="rn_ScreenReaderOnly"><?=($currentComment->ratedByRequestingUser->ratingValue) ? $this->data['attrs']['label_rate_up'] : $this->data['attrs']['label_rate_down'];?></span>
                        </span>
                    <? else:?>
                        <a href="javascript:void(0);" class="rn_ThumbUp rn_RatingIcon" data-action="rateUp" data-commentID="<?=$currentComment->id;?>">
                            <span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_rate_up'];?></span>
                        </a>
                        <a href="javascript:void(0);" class="rn_ThumbDown rn_RatingIcon" data-action="rateDown" data-commentID="<?=$currentComment->id;?>" >
                            <span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_rate_down'];?></span>
                        </a>
                    <? endif;?>
                    </span>
                </span>
            <? endif;?>
            <rn:block id="postActionBar"/>
            </div>
            <br/>
        <? endif;?>
        </div>
        <?
                if(is_array($this->data['commentsKeyedByParentId'][$currentComment->id]))
                {
                    $childComments = array_reverse($this->data['commentsKeyedByParentId'][$currentComment->id]);
                    foreach($childComments as $childComment)
                    {
                        $childComment->level = $level + 1;
                        array_push($this->data['commentStack'], $childComment);
                    }
                }
            }
            for($i = $level; $i >= 0; $i--)
            {
                if($i === 0)
                    echo '</li>';
                else
                    echo '</li></ul>';
            }
        }
        else{
        ?>
        <li>
            <p><?=$this->data['attrs']['label_no_comments'];?></p>
        </li>
       <?}?>
    <? if($this->data['contactID']):?>
        <? if($this->data['permissions']->commentCreate):?>
        <rn:block id="preNewComment"/>
        <li class="rn_NewComment">
            <strong><a href="javascript:void(0)" data-action="newComment" data-commentID="0"><?=$this->data['attrs']['label_new_comment'];?></a></strong>
            <div class="rn_CommentDetails" id="rn_<?=$this->instanceID;?>_Actions0"></div>
        </li>
        <rn:block id="postNewComment"/>
        <? elseif($this->data['js']['newUser'] && $this->data['permissions']->ratingEnabled):?>
        <rn:block id="preNewComment"/>
        <li class="rn_NewComment">
            <strong><a href="<?=$this->data['createAccountUrl'];?>"><?=$this->data['attrs']['label_create_account'];?></a></strong>
            <div class="rn_CommentDetails" id="rn_<?=$this->instanceID;?>_Actions0"></div>
        </li>
        <rn:block id="postNewComment"/>
        <? endif;?>
    <? else:?>
        <rn:block id="preNewComment"/>
        <li class="rn_NewComment">
            <strong><a href="javascript:void(0);" id="rn_<?=$this->instanceID;?>_Login"><?=$this->data['attrs']['label_new_comment'];?></a></strong>
            <rn:widget path="login/LoginDialog" trigger_element="#rn:php:'rn_'.$this->instanceID.'_Login'#" append_to_url="/comment/0"/>
        </li>
        <rn:block id="postNewComment"/>
    <? endif;?>
    <rn:block id="postCommentList"/>
    </ul>
    <rn:block id="bottom"/>
</div>
