<span class="rn_UpDownRateCount">
    <span>        
        <span class="rn_ThumbsUpButton rn_RateIcon">
            <span class="rn_ScreenReaderOnly" aria-live="polite"><?= \RightNow\Utils\Config::getMessage(UPVOTE_COUNT_LBL) ?></span>
        </span>
        <span class="rn_UpDownRatePositive"><?= $this->data['positiveVotes'] ?></span>
        <span class="rn_ThumbsDownButton rn_RateIcon">
            <span class="rn_ScreenReaderOnly" aria-live="polite"><?= \RightNow\Utils\Config::getMessage(DOWNVOTE_COUNT_LBL) ?></span>
        </span>
        <span class="rn_UpDownRateNegative"><?= $this->data['negativeVotes'] ?></span>
        <span class="rn_UpDownRateTotal">
            <?= "(" . $this->data['totalVotes'] . " " . ($this->data['totalVotes'] === 1 ? \RightNow\Utils\Config::getMessage(USER_LC_LBL) : \RightNow\Utils\Config::getMessage(USERS_LC_LBL)) . ")" ?>
        </span>
    </span>
</span>