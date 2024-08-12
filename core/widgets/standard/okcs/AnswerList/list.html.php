<? if (is_array($data) && count($data) > 0): ?>
    <? $widgetTitle = 'label_' . $attrs['type'] . '_list_title';
    if ($attrs[$widgetTitle] !== '' && $attrs['show_headers']): ?>
        <h2><?= $attrs[$widgetTitle] ?></h2>
    <? endif; ?>
    <ul class="rn_List">
        <? foreach($data as $article): ?>
            <li>
                <?
                    $href = $url . '/a_id/' . $article->answerId . '/accessType/' . $attrs['access_type'];
                    if(!$article->published) {
                        $href .= '/answer_data/' . $article->encryptedUrl;
                    }
                ?>
                <a href="<?= $href . \RightNow\Utils\Url::sessionParameter(); ?>" class="rn_Title" target="<?= $attrs['target'] ?>"><?= $article->title ?></a>
            </li>
        <? endforeach; ?>
    </ul>
<? else: ?>
    <?=$attrs['label_no_results'];?>
<? endif; ?>
