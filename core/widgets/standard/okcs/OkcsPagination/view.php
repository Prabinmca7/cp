<rn:block id="listBottom">
    <? $okcsMinimumPage = $this->data['js']['okcsAction'] === 'browse' ? 1 : 0; ?>
    <? if (($this->data['js']['currentPage'] > $okcsMinimumPage && $this->data['js']['size']) || ($this->data['js']['currentPage'] > $okcsMinimumPage && $this->data['js']['okcsAction'] === 'browse')): ?>
        <li>
        <?= $this->render('pageLink', array(
            'className' => 'rn_PreviousPage',
            'rel'       => 'previous',
            'iconClass' => 'fa fa-chevron-left',
            'label'     => $this->data['attrs']['label_previous_page_link'],
            'href'      => $this->helper('Pagination')->pageLink($this->data['js']['currentPage'] - 1, $this->data['js']['filter']),
        )) ?>
        </li>
    <? endif; ?>
    <? if ($this->data['js']['numberOfPages'] > 1): ?>
        <? for ($i = 1; $i <= $this->data['js']['numberOfPages']; $i++): ?>
        <li>
            <a data-rel="<?= $i ?>" class="rn_Page <?= ($i === $this->data['js']['currentPage']) ? 'rn_CurrentPage' : '' ?>" href="<?= $this->helper('Pagination')->pageLink($i, $this->data['js']['filter']) ?>">
                <?= $i ?>
            </a>
        </li>
        <? endfor; ?>
    <? endif; ?>
    <? $flag = $this->data['js']['pageMore'] > 0 ? true : false; ?>
    <? $flag = $this->data['js']['okcsAction'] === 'browse' ? true : $flag; ?>
    <? if (($this->data['js']['total'] > $this->data['js']['size'] && $this->data['js']['offset'] + $this->data['js']['size'] < $this->data['js']['total']) && $flag): ?>
        <li>
        <?= $this->render('pageLink', array(
            'className' => 'rn_NextPage',
            'rel'       => 'next',
            'iconClass' => 'fa fa-chevron-right',
            'label'     => $this->data['attrs']['label_next_page_link'],
            'href'      => $this->helper('Pagination')->pageLink($this->data['js']['currentPage'] + 1, $this->data['js']['filter']),
        )) ?>
        </li>
    <? endif; ?>
</rn:block>
