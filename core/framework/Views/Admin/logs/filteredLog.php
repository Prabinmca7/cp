<div id="toggleLink">
    <a href="javascript:void(0);" onClick="showEntireLog();"><?=$viewEntireLogMsg;?></a>
</div>
<br>
<div id="partialLog">
    <pre class="scrollable"><?=$partialLog;?></pre>
</div>
<div id="entireLog" class="hide">
    <pre class="scrollable"><?=$entireLog;?></pre>
</div>

<script type="text/javascript">
var showPartialLog, showEntireLog;
YUI().use('node', function(Y){
    showPartialLog = function() {
        Y.one('#entireLog').addClass('hide');
        Y.one('#partialLog').removeClass('hide');
        Y.one('#toggleLink').set('innerHTML', '<a href="javascript:void(0);" onClick="showEntireLog();"><?=$viewEntireLogMsg?></a>');
    };

    showEntireLog = function() {
        Y.one('#entireLog').removeClass('hide');
        Y.one('#partialLog').addClass('hide');
        Y.one('#toggleLink').set('innerHTML', '<a href="javascript:void(0);" onClick="showPartialLog();"><?=$viewPartialLogMsg;?></a>');
    };
});
</script>
