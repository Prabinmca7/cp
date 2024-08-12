<section class="rn_AnswerDivAlignment">
    <h2 class="rn_Hidden"><?=$label?></h2>
    <span class="rn_Info rn_Bold"><?=$label ?></span>
    <? $userGroupValues = ''; ?>
    <? if($this->data['attrs']['answer_key'] === 'user_group'): ?>
            <? if(count($value) > 0): ?>
            <? $userGrCount = $this->data['attrs']['usergroup_count']; ?>
            <? for($i = 0; $i < $userGrCount; $i++):?>
                <? $userGroupValues .= $value[$i].', '; ?>
            <? endfor; ?>
            <span  class="rn_AnswerValue">
            <span class="rn_userGroupList"><?= rtrim($userGroupValues, ", "); ?></span>
            <? if(count($value) > $userGrCount) :?>
                <? $userGroupList = ''; ?>
                <? foreach($value as $list) :?>
                    <? $userGroupList .= $list . ', '; ?>
                <? endforeach; ?>
                <span class="rn_userGroupList2"><?= rtrim($userGroupList, ", "); ?></span>
                <a class="rn_userGroupMore" href="JavaScript:void(0);"><?=$this->data['attrs']['label_more'];?></a>
            <?endif; ?>
            <? else : ?>
            <span class="rn_AnswerValue"><?=$this->data['attrs']['label_none_assigned'];?></span>
        <? endif; ?>
        </span>
    <? else : ?>
        <span class="rn_AnswerValue"><?=$value ?></span>
    <? endif; ?>
</section>
