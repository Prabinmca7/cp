<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? $post = $this->data['post'];?>
    <? if($this->data['attrs']['show_author']):?>
    <div class="rn_PostAuthorImage">
        <rn:block id="avatar">
        <img src="<?=$post->createdBy->avatar;?>" alt=""/>
        </rn:block>
    </div>
    <div class="rn_PostAuthor">
        <rn:block id="userName">
        <?printf($this->data['attrs']['label_author'], $post->createdBy->name);?>
        </rn:block>
    </div>
    <? endif;?>
    <rn:block id="prePost"/>
    <div class="rn_Post">
        <rn:block id="postTop"/>
        <span class="rn_PostTitle"><?=$post->title;?></span>
        <rn:block id="preBody"/>
        <div class="rn_PostBody">
            <?=$post->fields[1]->value;?>
        </div>
        <rn:block id="postBody"/>
        <? if($this->data['attrs']['show_posted_date']):?>
            <rn:block id="postedDate">
            <span class="rn_PostDate rn_SubTitle"><? printf($this->data['attrs']['label_posted_date'], $post->created);?></span>
            </rn:block>
        <? endif;?>
        <rn:block id="preMeta"/>
        <div class="rn_PostMeta">
            <div class="rn_PostRate">
            <? $additionalPostRatingClass = ($post->ratingCount === 0) ? 'rn_NoRatings' : '';?>
            <? if($post->userRating):?>
                <rn:block id="preUserRating"/>
                <? /* User already rated the post: read-only display */ ?>
                <span class="rn_PostRating <?=$additionalPostRatingClass;?>">
                    <span class="rn_UserRating <?=(($post->userRating->positive) ? 'rn_PositiveRating' : 'rn_NegativeRating');?>"><?=$post->userRating->label;?></span>
                <rn:block id="postUserRating"/>
            <? else:?>
                <? if($this->data['attrs']['post_ratings']):?>
                    <? /* User hasn't rated the post: display rating controls before showing rating info */ ?>
                    <rn:block id="preUserRating"/>
                    <span id="rn_<?=$this->instanceID;?>_PostRatingControls" class="rn_PostRatingControls">
                    <? if(\RightNow\Utils\Framework::isLoggedIn()):?>
                        <? if(!$this->data['js']['newUser']):?>
                        <rn:block id="preUserRatingUp"/>
                        <a class="rn_RateUp" id="rn_<?=$this->instanceID;?>_RateUp" href="javascript:void(0);"><?=$this->data['attrs']['label_positive_rating'];?></a>
                        <rn:block id="postUserRatingUp"/>
                        <rn:block id="preUserRatingDown"/>
                        <a class="rn_RateDown" id="rn_<?=$this->instanceID;?>_RateDown" href="javascript:void(0);"><?=$this->data['attrs']['label_negative_rating'];?></a>
                        <rn:block id="postUserRatingDown"/>
                        <? endif;?>
                    <? else:?>
                        <a href="<?=$this->data['attrs']['login_link_url'];?>" class="rn_LoginRequiredLink"><?=$this->data['attrs']['label_login_link_rate'];?></a>
                    <? endif;?>
                    </span>
                    <rn:block id="postUserRating"/>
                <? endif;?>
                <span id="rn_<?=$this->instanceID;?>_PostRating" class="rn_PostRating <?=$additionalPostRatingClass;?>">
                    <span id="rn_<?=$this->instanceID;?>_UserRating" class="rn_UserRating"></span>
            <? endif;?>
            <? if($this->data['attrs']['show_post_ratings']):?>
                <? if($post->positiveRating):?>
                    <? $label = ($post->positiveRating === 1) ? $this->data['attrs']['label_single_rating_count'] : $this->data['attrs']['label_rating_count'];?>
                    <rn:block id="positiveRating">
                    <span class="rn_PositiveRating"><? printf($label, $post->positiveRating);?></span>
                    </rn:block>
                <? endif;?>
                <? if($post->negativeRating):?>
                    <? $label = ($post->negativeRating === 1) ? $this->data['attrs']['label_single_negative_rating_count'] : $this->data['attrs']['label_negative_rating_count'];?>
                    <rn:block id="negativeRating">
                    <span class="rn_NegativeRating"><? printf($label, $post->negativeRating);?></span>           
                    </rn:block>
                <? endif;?>
            <? endif;?>
                </span>
            </div>
        <? if($this->data['attrs']['show_comment_count'] && $post->commentCount):?>
            <? $label = ($post->commentCount === 1) ? $this->data['attrs']['label_view_single_comment'] : $this->data['attrs']['label_view_comments'];?>
            <rn:block id="preCommentCount">
            <div class="rn_CommentCount" id="rn_<?=$this->instanceID;?>_CommentCount"><a id="rn_<?=$this->instanceID;?>_ShowComments" href="javascript:void(0);"><? printf($label, $post->commentCount);?></a></div>
            </rn:block>
        <? endif;?>
        <? if($this->data['attrs']['post_comments']):?>
            <rn:block id="prePostComment"/>
            <div class="rn_PostComment">
            <? if(\RightNow\Utils\Framework::isLoggedIn()):?>
                <? if($this->data['createAccountURL']):?>
                    <? if($this->data['attrs']['label_create_account']):?>
                    <rn:block id="createAccount">
                    <strong><a href="<?=$this->data['createAccountURL'];?>"><?=$this->data['attrs']['label_create_account'];?></a></strong>
                    </rn:block>
                    <? endif;?>
                <? else:?>
                <rn:block id="prePostCommentForm"/>
                <form onsubmit="return false;">
                    <textarea id="rn_<?=$this->instanceID;?>_Comment" rows="2" cols="20" class="rn_CommentPlaceHolder"><?=$this->data['attrs']['label_comment_placeholder']?></textarea>
                    <span id="rn_<?=$this->instanceID;?>_PostCommentSubmit" class="rn_Hidden">
                        <span class="rn_ScreenReaderOnly"><label for="rn_<?=$this->instanceID;?>_Comment">#rn:msg:COMMENT_LBL#</label></span>
                        <input type="submit" value="#rn:msg:COMMENT_LBL#" id="rn_<?=$this->instanceID;?>_Submit"/>
                    </span>
                </form>
                <rn:block id="postPostCommentForm"/>
                <? endif;?>
            <? elseif($this->data['attrs']['label_login_link_comment']):?>
                <rn:block id="login">
                <a href="<?=$this->data['attrs']['login_link_url'];?>" class="rn_LoginRequiredLink"><?=$this->data['attrs']['label_login_link_comment'];?></a>
                </rn:block>
            <? endif;?>
            </div>
            <rn:block id="postPostComment"/>
        <? endif;?>
        </div>
        <rn:block id="postMeta"/>
        <rn:block id="postBottom"/>
    </div>
    <rn:block id="postPost"/>
    <rn:block id="bottom"/>
</div>
