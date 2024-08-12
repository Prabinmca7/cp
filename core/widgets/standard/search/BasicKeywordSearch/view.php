<div class="<?=$this->classList?>">
    <rn:block id="top"/>
    <form method="post" action="<?=$this->data['attrs']['report_page_url'] . $this->data['appendedParameters'] ?>#rn:session#">
        <rn:block id="formTop"/>
        <div>
            <label for="rn_<?=$this->instanceID;?>_Text"><b><?=$this->data['attrs']['label_text'];?></b></label>
            <rn:block id="preInput"/>
            <input id="rn_<?=$this->instanceID;?>_Text" name="kw" type="text" maxlength="255" value="<?=$this->data['js']['initialValue'];?>"/>
            <rn:block id="postInput"/>
            <input type="submit" id="rn_<?=$this->instanceID;?>_Button" value="<?=$this->data['attrs']['label_button']?>"/><br/>
        </div>
        <rn:block id="formBottom"/>
    </form>
    <rn:block id="bottom"/>
</div>
