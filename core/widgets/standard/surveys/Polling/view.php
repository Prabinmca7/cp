<?$this->addJavaScriptInclude($this->data['ma_js_location']);?>

<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <rn:block id="top"/>
    <?if (!$this->data['attrs']['modal']):?>
    <rn:block id="preTitleDiv" />
    <div id="rn_<?=$this->instanceID;?>_PollTitle" class="rn_PollTitle">
        <rn:block id="prePollHeader" />
        <h2 id="rn_<?=$this->instanceID;?>_PollTitleHeading">
            <rn:block id="prePollTitle" />
            <?=$this->data['title'];?>
            <rn:block id="postPollTitle" />
        </h2>
        <rn:block id="postPollHeader" />
    </div>
    <rn:block id="postTitleDiv" />
    <?endif;?>
    <rn:block id="prePollForm" />
    <form id="rn_<?=$this->instanceID;?>_QuestionForm" name="rn_<?=$this->instanceID;?>_QuestionForm">
        <rn:block id="prePollFieldset" />
        <fieldset class="rn_PollFieldset">
            <? if (isset($this->data['js']['element_type']) && ($this->data['js']['element_type'] === "list" || $this->data['js']['element_type'] === "menu")): ?>  
            <label id="rn_<?=$this->instanceID;?>_PollQuestion" for="q_<?=$this->data['js']['question_id'];?>">
                <span class="rn_ScreenReaderOnly"><?=$this->data['js']['dialog_description'];?></span>
                <rn:block id="prePollQuestion" />
                <?=$this->data['question']?>
                <rn:block id="postPollQuestion" />
            </label>
            <? else: ?>
            <rn:block id="prePollLegend" />
            <legend id="rn_<?=$this->instanceID;?>_PollQuestion" class="rn_PollQuestion">
                <span class="rn_ScreenReaderOnly"><?=$this->data['js']['dialog_description'];?></span>
                <rn:block id="prePollQuestion" />
                <?=$this->data['question']?>
                <rn:block id="postPollQuestion" />
            </legend>
            <rn:block id="postPollLegend" />
            <? endif; ?>
        <rn:block id="preFlipArea" />
        <div id="rn_<?=$this->instanceID;?>_FlipArea" class="rn_FlipArea" >
            <rn:block id="preErrorMessage" />
            <div id="rn_<?=$this->instanceID;?>_ErrorMessage"></div>
            <rn:block id="postErrorMessage" />
            <rn:block id="preAnswerArea" />
            <div id="rn_<?=$this->instanceID;?>_PollAnswerArea" class="rn_PollAnswerArea">
                <rn:block id="answerAreaTop" />
                <?=$this->data['answer_area']?>
                <rn:block id="answerAreaBottom" />
            </div>
            <rn:block id="postAnswerArea" />
            <?if ($this->data['show_results_link'] && $this->data['js']['question_type'] === "choice" && $this->data['js']['show_chart']):?>
            <rn:block id="preViewResultsDiv" />
            <div id="rn_<?=$this->instanceID;?>_ViewResults" class="rn_ViewResults rn_Hidden">
                <rn:block id="preViewResultsLink" />
                <a href="javascript:void();" class="rn_ViewResultsLink" id="rn_<?=$this->instanceID;?>_ViewResultsLink" onclick="return false"><?=$this->data['view_results_label']?></a>
                <rn:block id="postViewResultsLink" />
            </div>
            <rn:block id="postViewResultsDiv" />
            <?endif;?>
            <?if (!$this->data['attrs']['modal']):?>
            <rn:block id="preSubmitDiv" />
            <div id="rn_<?=$this->instanceID;?>_PollSubmit" class="rn_PollSubmit">
                <rn:block id="preSubmitButton" />
                <input id="rn_<?=$this->instanceID;?>_Submit" type="submit" disabled="disabled" onclick="return false" value="<?=$this->data['submit_button_label']?>"/>
                <rn:block id="postSubmitButton" />
            </div>
            <rn:block id="postSubmitDiv" />
            <?endif;?>
        </div>
        <rn:block id="postFlipArea" />
        </fieldset>
        <rn:block id="postPollFieldset" />
    </form>
    <rn:block id="postPollForm" />
    <rn:block id="preTotalVotesDiv" />
    <div id="rn_<?=$this->instanceID;?>_TotalVotes" class="rn_TotalVotes rn_Hidden">
        <rn:block id="preTotalVotesParagraph" />
        <p id="rn_<?=$this->instanceID;?>_TotalVotesParagraph">
            <rn:block id="preTotalVotesLabel" />
            <?=$this->data['total_votes_label'];?>
            <rn:block id="postTotalVotesLabel" />
        </p>
        <rn:block id="postTotalVotesParagraph" />
    </div>
    <rn:block id="postTotalVotesDiv" />
    <rn:block id="bottom"/>
</div>

