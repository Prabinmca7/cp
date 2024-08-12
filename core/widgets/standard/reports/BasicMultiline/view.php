<div class="<?=$this->classList?>">
    <? if(empty($this->data['reportData']['data'])): ?>
        <?=$this->data['attrs']['label_no_results'];?>
    <? else: ?>
        <ul>
        <?  $reportColumns = count($this->data['reportData']['headers']);
            foreach($this->data['reportData']['data'] as $value): ?>
            <li>
                <span><?=$value[0];?></span><br/>
                <? if($value[1]): ?>
                <span><?=$value[1];?></span><br/>
                <? endif; ?>
                <? if($value[2]): ?>
                <span><?=$value[2];?></span><br/>
                <? endif; ?>
                <? for ($i = 3; $i < $reportColumns; $i++): ?>
                    <? $header = $this->data['reportData']['headers'][$i]; ?>
                    <? if ($this->showColumn($value[$i], $header)): ?>
                    <span><?=$this->getHeader($header);?></span>
                    <span><?=$value[$i];?></span><br/>
                    <? endif; ?>
                <? endfor; ?>
            </li>
        <? endforeach; ?>
        </ul>
    <? endif; ?>
</div>

