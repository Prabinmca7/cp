<?php
require_once DOCROOT.'/ma/util.phph';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?=getMessage(SEND_TO_FRIEND_HDG) ?></title>
<?
require DOCROOT.'/ma/cci/head.phph';
//for upgrade reasons we need to keep size at 75 for PC mode
$size = 75;
if ($this->isMobile)
{
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    //for mobile mode 75 is far too large so we go with 30
    $size = 30;
    $submitURLmobilePart = '/mobile/1';
}
?>
<style type="text/css">
.topTwentyPadding
{
    padding-top: 20px;
}
#submitCell
{
    text-align: right;
    padding-top: 5px
}
</style>
<script type="text/javascript">
var submitAccepted = false;

function trimValue(value){
    try{
        return value.replace(/^\s+|\s+$/g, "");
    }
    catch(e){
        return value;
    }
}

function doSubmit(form) {
    var emailExpression = new RegExp("<?= addslashes(getConfig(DE_VALID_EMAIL_PATTERN)) ?>");
    var fromEmail = trimValue(form.p_from_email.value);
    var toEmail = trimValue(form.p_addresses.value);
    var subject = trimValue(form.p_subject.value);

    if (submitAccepted)
        return(false);

    if (fromEmail.length === 0) {
        alert("<?=getMessage(PLEASE_ENTER_YOUR_EMAIL_ADDRESS_MSG) ?>");
        return (false);
    }

    if (!emailExpression.test(fromEmail)) {
        alert('\'' + fromEmail + '\' ' + "<?=getMessage(EMAIL_IS_INVALID_MSG) ?>");
        return (false);
    }

    if (toEmail.length === 0) {
        alert("<?=getMessage(PLEASE_ENTER_A_RECIPIENT_MSG) ?>");
        return (false);
    }

    if (subject.length === 0) {
        alert("<?=getMessage(PLEASE_ENTER_A_SUBJECT_MSG) ?>");
        return (false);
    }

    var toAddresses = [];
    if (toEmail.match(',') || toEmail.match(';')) {
        toAddresses = toEmail.split(/[,;]+/);
    }
    else {
        toAddresses[0] = toEmail;
    }

    for (var i = 0; i < toAddresses.length; i++) {
        toAddresses[i] = trimValue(toAddresses[i]);
        if (!emailExpression.test(toAddresses[i])) {
            alert('\'' + toAddresses[i] + '\' ' + "<?=getMessage(EMAIL_IS_INVALID_MSG) ?>");
            return (false);
        }
    }

    submitAccepted = true;

    return(true);
}
</script>
</head>

<body class="bgcolor">
<? require DOCROOT.'/ma/cci/top.phph' ?>

<form class="block" name="_main" method="post" action="/ci/friend/submit<?=$submitURLmobilePart?>" onsubmit="return(doSubmit(this));">
<input type="hidden" name="track" value="<?=$this->parameters[MA_QS_ENCODED_PARM]?>" />
<input type="hidden" name="sc" value="<?=$this->parameters[MA_QS_WF_SHORTCUT_PARM]?>" />

<table cellpadding="0" cellspacing="0">
<tr><td class="label"><?=getMessage(YOUR_EMAIL_ADDR_LWR_LBL) ?>:</td></tr>
<? if (strlen($this->emailAddress) > 0): ?>
<tr><td><input type="text" readonly size="<?=$size?>" name="p_from_email" maxlength="255" value="<?=$this->emailAddress?>" /></td></tr>
<? else: ?>
<tr><td><input type="text" size="<?=$size?>" name="p_from_email" maxlength="255" value="<?=$this->emailAddress?>" /></td></tr>
<? endif; ?>

<tr><td class="label topTwentyPadding"><?=getMessage(SEND_THIS_ITEM_TO_LBL) ?>:</td></tr>
<tr><td><input type="text" size="<?=$size?>" name="p_addresses" /></td></tr>

<tr><td class="label topTwentyPadding"><?=getMessage(SUBJECT_LBL) ?>:</td></tr>
<tr><td><input type="text" name="p_subject" readonly size="<?=$size?>" value="<?= $this->subject ?>"/></td></tr>

<tr><td id="submitCell"><input type="submit" class="btn" value="<?=getMessage(EMAIL_SEND_CMD) ?>" /></td></tr>
</table>
</form>
<? require DOCROOT.'/ma/cci/bottom.phph' ?>
</body>
</html>
