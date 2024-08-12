<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>

    <rn:block id="preUpvoteButton"/>
    <div class="rn_RatingButtons">
        <? $ratingView = ($this->data['attrs']['rating_type'] === 'upvote') ? 'buttonView' : $this->data['attrs']['rating_type'].'View'; ?>
        <?= $this->render($ratingView, array(
                'disabled' => $disabled = (!$this->data['js']['canRate'] || $this->data['js']['alreadyRated']),
                'title' => $disabled ? ($this->data['js']['canRate'] ? $this->data['attrs']['label_upvote_thanks'] : $this->data['attrs']['label_upvote_disabled_tooltip']) : $this->data['attrs']['label_upvote_hint'],
            ))
        ?>
    </div>
    <rn:block id="postUpvoteButton"/>
    <button class="rn_ResetButton rn_Hidden" title="<?= $this->data['attrs']['label_vote_reset_title'] ?>">
        <span class="rn_ScreenReaderOnly" aria-live="polite"><?= $this->data['attrs']['label_vote_reset_title'] ?></span>
    </button>
    <rn:block id="preRatingValue"/>
    <? if(!empty($this->data['ratingStr'])): ?>
    	<span class="rn_Separator"></span>
    	<span class="rn_RatingValue" itemprop="<?= $this->data['attrs']['rating_type'] ?>Count" title="<?= $this->data['ratingValueTitle'] ?>">
        	<? if($this->data['attrs']['rating_count_format'] === 'numerical'): ?>
        		<span class="rn_RatingValueNumerical">
                    		<?= $this->data['ratingStr'] ?>
                	</span>
        	<? else: ?>
			<span class="rn_RatingValueGraphical">
                		<span class="<?= 'rn_RateGraphNoVotes ' . (($this->data['js']['ratingValue'] !== 0) ? 'rn_Hidden' : null) ?>">
        	        		<? if($this->data['js']['ratingValue'] === 0): ?>
                        			<?= $this->data['ratingStr'] ?>
                            		<? endif; ?>
                    		</span>
                    		<span class="<?= 'rn_RateGraphVoted ' . (($this->data['js']['ratingValue'] === 0) ? 'rn_Hidden' : null) ?>">
                        		<?= \RightNow\Utils\Config::getMessage(RATING_LBL) ?>
                        		<? $ratingFormat = ($this->data['attrs']['rating_type'] === 'upvote') ? 'buttonRatingCount' : $this->data['attrs']['rating_type'].'RatingCount'; ?>
                        		<?= $this->render($ratingFormat, array()) ?>
                    		</span>
			</span>
            	<? endif; ?>
        </span>
    <? endif; ?>
    <rn:block id="postRatingValue"/>

    <rn:block id="bottom"/>
</div>
