<rn:meta title="#rn:msg:FINISH_ACCOUNT_CREATION_CMD#" template="mobile.php" login_required="false" redirect_if_logged_in="account/overview" force_https="true" />
<?
/**
 * This page is navigated to by following an email link when:
 * (A) An account is automatically created and an email is sent [techmail/chat]
 * (B) An existing user doesn't have a login and attempts to recover it
 */
?>
<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:FINISH_ACCOUNT_CREATION_CMD#</h1>
    </div>
</div>
<div class="rn_PageContent rn_Container">
    <rn:widget path="login/ResetPassword" label_heading="#rn:msg:CREATE_A_USERNAME_AND_PASSWORD_CMD#" on_success_url="#rn:php:'/app/account/profile/msg/' . urlencode(\RightNow\Utils\Config::getMessage(SUCC_ACTIVATED_ACCT_PLS_COMP_MSG))#" />
</div>
