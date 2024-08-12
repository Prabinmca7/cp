<form id="search" onsubmit="return false;">
    <label for="searchTerm" class="screenreader"><?=\RightNow\Utils\Config::getMessage(SEARCH_COLUMNS_CMD);?></label>
    <input type="search" id="searchTerm" placeholder="<?=\RightNow\Utils\Config::getMessage(SEARCH_COLUMNS_CMD);?>"/>
    <label for="searchColumn" class="screenreader"><?=\RightNow\Utils\Config::getMessage(COLUMN_LBL);?></label>
    <select id="searchColumn">
    <? foreach($columns as $column): ?>
        <option value="<?=$column['key'];?>"><?=$column['label'];?></option>
    <? endforeach; ?>
    </select>
    <button><?=\RightNow\Utils\Config::getMessage(GO_CMD);?></button>
</form>
