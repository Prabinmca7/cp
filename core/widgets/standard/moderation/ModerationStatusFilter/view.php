<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <form class="rn_Padding">
            <div class="rn_SocialFilterItem">
                <fieldset class="rn_SocialFilterItem_FieldSet">
                    <h3 id="rn_<?= $this->instanceID ?>_Title"><?= $this->data['attrs']['label_title']; ?></h3>
                    <ul aria-labelledby="rn_<?= $this->instanceID ?>_Title">
                        <?
                        $statuses = $this->helper('Social')->getStatusLabels($this->data['attrs']['object_type'], $this->data['hide_status_type_ids']);
                        foreach ($statuses as $statusID => $statusValue):
                        ?>
                            <li>
                                    <input name="rn_<?= $this->instanceID ?>_StatusFilter_<?= $statusID; ?>" id="rn_<?= $this->instanceID ?>_StatusFilter_<?= $statusID; ?>" type="checkbox" value="<?= $statusID; ?>">
                                    <label class="rn_Label" for="rn_<?= $this->instanceID ?>_StatusFilter_<?= $statusID; ?>">
                                        <?= $statusValue; ?>
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
