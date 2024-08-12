<span class="rn_ButtonRateCount">
    <span>
        <span class="rn_UpvoteButton rn_RateIcon">
            <span class="rn_ScreenReaderOnly" aria-live="polite"><?= \RightNow\Utils\Config::getMessage(UPVOTE_COUNT_LBL) ?></span>
        </span>
        <span class="rn_RatePositive"><?= $this->data['positiveVotes'] ?></span>
        <span class="rn_RateTotal">
            <?= "(" . $this->data['totalVotes'] . " " . ($this->data['totalVotes'] === 1 ? \RightNow\Utils\Config::getMessage(USER_LC_LBL) : \RightNow\Utils\Config::getMessage(USERS_LC_LBL)) . ")" ?>
        </span>
    </span>    
</span>