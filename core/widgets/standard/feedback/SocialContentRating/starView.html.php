<div class="rn_StarVoting">
    <span class="<?= 'rn_ScreenReaderOnly' . ($this->data['js']['alreadyRated'] ? '' : ' rn_Hidden') ?>" aria-live="polite"><?= "$title " . $this->data['js']['userRating'] ?></span>
    <? for($i = $this->data['js']['ratingScale']; $i >= 1; $i--): ?>
    	<button class="<?= 'rn_StarButton ' . (($this->data['js']['alreadyRated'] && $this->data['js']['userRating'] >= $i) ? 'rn_Voted' : 'rn_Vote') ?>" aria-hidden="<?= $disabled ? 'true' : 'false' ?>" title="<?= $title ?>" <?= $disabled ? 'disabled' : '' ?> value="<?= $i ?>">
        	<span class="<?= 'rn_ScreenReaderOnly' . ($this->data['js']['alreadyRated'] ? ' rn_Hidden' : '') ?>" aria-live="polite"><?= !$disabled ? "$title $i" : "$title" ?></span>
    	</button>
    <? endfor; ?>
</div>
