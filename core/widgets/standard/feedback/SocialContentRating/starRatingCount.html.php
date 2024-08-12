<span class="rn_StarRateCount">
    <? for($i = 1; $i <= $this->data['js']['ratingScale']; $i++): ?>
    <span class="<?= 'rn_StarButton ' . (($this->data['ratedValue'] < $i) ? (($this->data['ratedValue'] < ($i - 0.5)) ? 'rn_VoteRating' : 'rn_VotedHalfRating') : 'rn_VotedRating') ?>"></span>
    <? endfor; ?>    
    <span class="rn_ScreenReaderOnly" aria-live="polite"><?= \RightNow\Utils\Config::getMessage(STAR_VOTE_COUNT_LBL) . $this->data['ratedValue'] ?></span>
    <span class="rn_StarRateTotal">
        <?= "(" . $this->data['totalVotes'] . " " . ($this->data['totalVotes'] === 1 ? \RightNow\Utils\Config::getMessage(USER_LC_LBL) : \RightNow\Utils\Config::getMessage(USERS_LC_LBL)) . ")" ?>
    </span>
</span>