<div class="<?=$this->classList?>" id="rn_<?=$this->instanceID?>">
    <rn:block id="top"/>
    <? if($this->data['label_tests_fail']): ?>
    <noscript>
        <rn:block id="noscriptTop"/>
        <div class="MessageContainer MessageContainerFailure">
            <?= $this->data['label_tests_fail'] ?>
        </div>
        <rn:block id="noscriptBottom"/>
    </noscript>
    <? endif; ?>
    <div id="rn_<?=$this->instanceID?>_MessageContainer">
    </div>
    <rn:block id="preScript"/>
    <? /* hack the script and comment tags so that rn:blocks can be used */ ?>
    <?='<' . 'script type="text/javascript"><!' . "-- \n /*<![" . 'CDATA[*/'?>

(function () {
    var runJSTests = function() {
        <rn:block id="scriptTop"/>

        var messageContainerDiv = document.getElementById('rn_<?= $this->instanceID ?>_MessageContainer');
        function showFailure() {
            <rn:block id="showFailureTop"/>
            <? if($this->data['label_tests_fail']): ?>
            messageContainerDiv.className = 'MessageContainer MessageContainerFailure';
            messageContainerDiv.innerHTML = '<?= str_replace("'", "\\'", $this->data['label_tests_fail']) ?>';
            <? endif; ?>
            <? if($this->data['automatically_redirect']): ?>
            window.location = '<?= $this->data['link_tests_fail'] ?>';
            <? endif; ?>
            <rn:block id="showFailureBottom"/>
        }

        <rn:block id="preJSTests"/>
        <? if($this->data['runJSTests']): ?>
        <rn:block id="preXhr"/>
        <? if($this->data['attrs']['display_if_no_xhr_object']): ?>
        function xhrTestFails() {
            return !window.XMLHttpRequest;
        }
        if(typeof RightNowTesting !== 'undefined' && RightNowTesting._isTesting) {
            RightNowTesting._xhrTestFails = xhrTestFails;
        }
        if (xhrTestFails()) {
            showFailure();
            return;
        }
        <? endif; ?>
        <rn:block id="postXhr"/>
        <? endif; ?>
        <rn:block id="postJSTests"/>

        <rn:block id="prePass"/>
        <? if($this->data['showSuccessMessage']): ?>
        messageContainerDiv.className = 'MessageContainer MessageContainerSuccess';
        messageContainerDiv.innerHTML = '<?= str_replace("'", "\\'", $this->data['label_tests_pass']) ?>';
        <? endif; ?>
        <rn:block id="postPass"/>

        <rn:block id="scriptBottom"/>
    }
    if(typeof RightNowTesting !== 'undefined' && RightNowTesting._isTesting) {
        RightNowTesting._runJSTests = runJSTests;
    }
    runJSTests();
})();
    <?='/*]' . ']>*/ // --></' . 'script>'?>
    <rn:block id="postScript"/>
    <rn:block id="bottom"/>
</div>
