<label for="commentEnter"><?=$label;?> <span id="maxLengthWarning"></span></label><br>
<textarea <?=$disabled;?> name="comment" id="commentEnter" maxlength="4000" rows="4" onchange="return enforceMaxLength(this)"></textarea>

<script>
(function() {
    var obj = document.getElementById('commentEnter'),
        span = document.getElementById('maxLengthWarning'),
        maxLength = parseInt(obj.getAttribute('maxlength'), 10);

    this.enforceMaxLength = function() {
        if (obj.value.length >= maxLength) {
            span.innerHTML = "<span class=\"warning\"><?=\RightNow\Utils\Text::escapeStringForJavaScript($maxLengthWarning);?>" + maxLength + "</span>";
            obj.value = obj.value.substring(0, maxLength);
        }
        else if (span.innerHTML) {
            span.innerHTML = '';
        }
    };
})();
</script>
