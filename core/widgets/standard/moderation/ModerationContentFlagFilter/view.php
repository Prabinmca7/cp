<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <form class="rn_Padding">
            <div class="rn_SocialFilterItem">
                <fieldset class="rn_SocialFilterItem_FieldSet">
                    <h3 id="rn_<?= $this->instanceID ?>_Title"><?= $this->data['attrs']['label_title']; ?></h3>
                    <ul aria-labelledby="rn_<?= $this->instanceID ?>_Title">
                        <?
                        foreach ($this->data['js']['flags'] as $ID => $lookupName):
                        ?>
                            <li>
                                    <input name="rn_<?= $this->instanceID ?>_FlagFilter_<?= $ID; ?>" id="rn_<?= $this->instanceID ?>_FlagFilter_<?= $ID; ?>" type="checkbox" value="<?= $ID; ?>">
                                    <label class="rn_Label" for="rn_<?= $this->instanceID ?>_FlagFilter_<?= $ID; ?>">
                                        <?= $lookupName; ?>
                                    </label>
                            </li>
                        <?
                            endforeach;
                        ?>
                    </ul>
                </fieldset>
            </div>
    </form>
    <rn:block id="bottom"/>
</div>
