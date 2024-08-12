<rn:meta title="#rn:msg:RESET_PASSWORD_CMD#" template="mobile.php" login_required="false" force_https="true" />
<?
/**
 * This page is navigated to by following an email link when:
 * user or agent triggers 'reset password' routine
 */
?>
<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:RESET_YOUR_PASSWORD_CMD#</h1>
    </div>
</div>

<div class="rn_PageContent rn_Container">
    <rn:widget path="login/ResetPassword" on_success_url="#rn:php:'/app/account/profile/msg/' . urlencode(\RightNow\Utils\Config::getMessage(YOUR_PASSWORD_HAS_BEEN_CHANGED_MSG))#" />
</div>
