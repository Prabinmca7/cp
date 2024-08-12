<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <? if ($this->data['answer']['docID'] !== null && $this->data['docType'] !== 'HTML') : ?>
            <div role="alert" tabindex="-1" class="rn_DocIdMsg rn_Hidden" id="rn_DocIdMsg">
                <?= $this->data['attrs']['label_doc_id_redirect_msg'] ?> 
                <a href="/app/results/kw/<?= $this->data['answer']['docID'] ?>/nlpsearch/true/loc/<?= $this->data['answerLocale'] ?>" class="rn_DocIdResults">#rn:msg:CLICK_HERE_UC_CMD#</a>
                <?= $this->data['attrs']['label_see_search_results'] ?> 
                <button class="rn_hideBanner" aria-label="#rn:msg:CLOSE_CMD#">X</button>
            </div>
            <div style="word-break: break-word;">
                <div class="rn_AnswerDetail rn_AnswerHeader">
                    <h1 id="rn_Summary"><?=$this->data['answer']['title']?></h1>
                </div>
            </div>
        <? endif; ?>

    <rn:block id="bottom"/>
</div>
