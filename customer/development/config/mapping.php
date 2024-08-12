<?

$globalMapping['p_faqid']  = 'a_id';
$globalMapping['p_iid']    = 'i_id';
$globalMapping['p_userid'] = 'username';
// added mappings for custom pta params
// $globalMapping['custom_login_case']    = 'case';
// $globalMapping['custom_login_id'] = 'id';

$pageMapping['home.php']            = array('new_page' => 'home');
$pageMapping['acct_login.php']      = array('new_page' => 'utils/login_form');
$pageMapping['std_alp.php']         = array('new_page' => 'answers/list');
$pageMapping['std_adp.php']         = array('new_page' => 'answers/detail');
$pageMapping['myq_upd.php']         = array('new_page' => 'account/questions/detail');
$pageMapping['myq_ilp.php']         = array('new_page' => 'account/questions/list');
$pageMapping['myq_idp.php']         = array('new_page' => 'account/questions/detail');
$pageMapping['widx_alp.php']        = array('new_page' => 'answers/list');
$pageMapping['popup_adp.php']       = array('new_page' => 'answers/detail');
$pageMapping['ask.php']             = array('new_page' => 'ask');
$pageMapping['acct_new.php']        = array('new_page' => 'utils/create_account');
$pageMapping['chat.php']            = array('new_page' => 'chat/chat_launch');
$pageMapping['acct_assistance.php'] = array('new_page' => 'utils/account_assistance');

$globalMappingWap['p_faqid']  = 'a_id';
$globalMappingWap['p_iid']    = 'i_id';
$globalMappingWap['p_key']    = array(
    // the 'p' and 'c' logic works for the answer report used in the standard implementation of WAP
    'p'  => array('p_list', '0'),
    'c'  => array('p_list', '1'),
    // the 'kw' logic should work for any report
    'kw' => array('p_type', '3'),
);

$pageMappingWap['/']                   = array('new_page' => 'home');
$pageMappingWap['login.php']           = array('new_page' => 'utils/login_form');
$pageMappingWap['listans.php']         = array('new_page' => 'answers/list');
$pageMappingWap['search.php']          = array('new_page' => 'answers/list');
$pageMappingWap['ans.php']             = array('new_page' => 'answers/detail');
$pageMappingWap['myq.php']             = array('new_page' => 'account/questions/list');
$pageMappingWap['inc.php']             = array('new_page' => 'account/questions/detail');
$pageMappingWap['myq_upd.php']         = array('new_page' => 'account/questions/detail');
$pageMappingWap['myprofile.php']       = array('new_page' => 'account/profile');
$pageMappingWap['ask.php']             = array('new_page' => 'ask');
$pageMappingWap['acct_new.php']        = array('new_page' => 'utils/create_account');
$pageMappingWap['acct_assistance.php'] = array('new_page' => 'utils/account_assistance');
$pageMappingWap['passwd_change.php']   = array('new_page' => 'account/change_password');
$pageMappingWap['passwd_reset.php']    = array('new_page' => 'account/reset_password');
$pageMappingWap['passwd_setup.php']    = array('new_page' => 'account/setup_password');
