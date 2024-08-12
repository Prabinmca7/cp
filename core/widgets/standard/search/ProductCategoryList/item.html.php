<a class="rn_ListItemLink" href="<?= $this->data['itemLink'] . $item['hierList'] ?>">
    <?= \RightNow\Utils\Text::escapeHtml($item['label']) ?>
</a>

<? if (count(isset($item['subItems']) && $item['subItems'] ? $item['subItems'] : array())): ?>
    <rn:block id="preSubList"/>
    <ul class="rn_SubItemList">
    <? foreach ($item['subItems'] as $subItem): ?>
        <rn:block id="subListItem">
        <li class="rn_ProductCategorySubItem">
            <a href="<?= $this->data['itemLink'] . $subItem['hierList'] ?>">
                <?= \RightNow\Utils\Text::escapeHtml($subItem['label']) ?>
            </a>
        </li>
        </rn:block>
    <? endforeach; ?>
    </ul>
    <rn:block id="postSubList"/>
<? endif; ?>
