<div class="rn_UpDownVoting">
    <span class="<?= 'rn_ScreenReaderOnly' . ($this->data['js']['alreadyRated'] ? '' : ' rn_Hidden') ?>" aria-live="polite"><?= "$title " . (($this->data['js']['userRating'] >= 1) ? \RightNow\Utils\Config::getMessage(UP_LBL) : \RightNow\Utils\Config::getMessage(DOWN_LBL)) ?></span>
    <button class="<?= 'rn_ThumbsUpButton ' . (($this->data['js']['alreadyRated'] && $this->data['js']['userRating'] >= 1) ? 'rn_Voted' : 'rn_Vote') ?>" aria-hidden="<?= $disabled ? 'true' : 'false' ?>" title="<?= $title ?>" <?= $disabled ? 'disabled' : '' ?> value="2">
        <span class="<?= 'rn_ScreenReaderOnly' . ($this->data['js']['alreadyRated'] ? ' rn_Hidden' : '') ?>" aria-live="polite"><?= !$disabled ? "$title " . \RightNow\Utils\Config::getMessage(UP_LBL) : $title ?></span>
    </button>
    <button class="<?= 'rn_ThumbsDownButton ' . (($this->data['js']['alreadyRated'] && $this->data['js']['userRating'] < 1) ? 'rn_Voted' : 'rn_Vote') ?>" aria-hidden="<?= $disabled ? 'true' : 'false' ?>" title="<?= $title ?>" <?= $disabled ? 'disabled' : '' ?> value="0.4">
        <span class="<?= 'rn_ScreenReaderOnly' . ($this->data['js']['alreadyRated'] ? ' rn_Hidden' : '') ?>" aria-live="polite"><?= !$disabled ? "$title " . \RightNow\Utils\Config::getMessage(DOWN_LBL) : $title ?></span>
    </button>
</div>
