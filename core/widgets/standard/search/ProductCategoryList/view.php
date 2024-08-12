<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>

    <? if($this->data['attrs']['label_title']): ?>
        <h2><?= $this->data['attrs']['label_title'] ?></h2>
    <? endif; ?>

    <div class="rn_ListColumns">
        <? $columnClasses = array('rn_LeftColumn', 'rn_MiddleColumn', 'rn_RightColumn');
           foreach($this->data['results'] as $resultIndex => $resultGroup): ?>
            <ul class="<?= $columnClasses[$resultIndex] ?>">
                <? foreach ($resultGroup as $index => $item): ?>
                    <li class="rn_ProductCategoryItem">
                        <rn:block id="preItem"/>
                        <?= $this->render('item', array('item' => $item)) ?>
                        <rn:block id="postItem"/>
                    </li>
                <? endforeach; ?>
            </ul>
        <? endforeach; ?>
    </div>

    <rn:block id="bottom"/>
</div>
