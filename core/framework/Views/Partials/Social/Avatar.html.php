<span class="rn_UserAvatar" title="<?= $title ?>">
    <? if ($profileUrl && (!$hideDisplayName || $size !== '0')): ?>
        <a itemprop="url" href="<?= $profileUrl ?>"<?= isset($target) && $target ? ' target="' . $target . '"' : '' ?>>
    <? endif; ?>
    <? if ($size !== '0'): ?>
    <span class="rn_Avatar <?= ($className ?: 'rn_Medium') ?> <?= ($avatarUrl && $fileExists) ? 'rn_Image' : 'rn_Placeholder' ?>">
        <? if ($avatarUrl && $fileExists): ?>
            <img itemprop="image" src="<?= $avatarUrl ?>" height="<?= $size ?>" width="<?= $size ?>" alt=""/>
        <? else: ?>
            <span class="rn_Default rn_DefaultColor<?= $defaultAvatar['color'] ?>" aria-hidden="true" role="presentation">
                <span class="rn_Liner"><?= $defaultAvatar['text'] ?></span>
            </span>
        <? endif; ?>
    </span>
    <? endif; ?>
    <? if ($displayName && !$hideDisplayName): ?>
        <span class="rn_DisplayName<?= $profileUrl ? '' : ' rn_DisplayNameDisabled' ?>" itemprop="name"><?= $displayName ?></span>
    <? elseif($displayName && $hideDisplayName): ?>
        <span class="rn_ScreenReaderOnly"><?= $displayName . ' ' . \RightNow\Utils\Config::getMessage(AVATAR_LBL) ?></span>
    <? endif; ?>
    <? if ($profileUrl && (!$hideDisplayName || $size !== '0')): ?>
        </a>
    <? endif; ?>
</span>
