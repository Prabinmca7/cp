<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?= $this->instanceID ?>_Content" class="rn_Content">
        <div class="rn_OkcsSpellCheckerContainer <?= $this->data['visibilityClass'] ?>"><?= $this->data['attrs']['label_did_you_mean'] ?><a class='rn_OkcsSpellCheckerLink' href="javascript:void(0)"><?= isset($this->data['paraphrase']) ? $this->data['paraphrase'] : null ?></a></div>
    </div>
    <rn:block id="bottom"/> 
</div>