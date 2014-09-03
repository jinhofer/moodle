<?php

/**
 * Use this as the target for the passive Shibboleth login attempt from local/login/shibpassive.
 */

require('../../config.php');

require_once('lib.php');

// If the shibboleth user attribute is set, redirect to the page that will silently
// login the user.
$shibpluginconfig   = get_config('auth/shibboleth');
if (!empty($_SERVER[$shibpluginconfig->user_attribute])) {
    error_log("Shibboleth user: ".$_SERVER[$shibpluginconfig->user_attribute]);
    // TODO: Consider implementing necessary logic from auth/shibboleth/index.php here.
    redirect($CFG->wwwroot.'/auth/shibboleth/index.php');
}

// This uses the custom on_shib_empty hook to log in with LDAP credentials.  This
// is here primarily to test the bypass.
$postdata = data_submitted();
if ($postdata and
    isset($postdata->username) and
    isset($postdata->password) and
    isset($postdata->ldap))
{
    $auth = $DB->get_field('user', 'auth', array('username'=>$postdata->username));
    if ($auth == 'shibboleth') {
        $user = authenticate_user_login($postdata->username, $postdata->password);
        if ($user) {
            complete_user_login($user);
        }
        echo $postdata->username;
        exit;
    }
}

// If no Shibboleth session, log user in as guest unless we know that wantsurl
// does not allow guests.  In that case, we send the user to shib login.

$wantsurl_guest_ok = local_login\is_wantsurl_possibly_ok_for_guest();

if ($wantsurl_guest_ok and $guest = get_complete_user_data('id', $CFG->siteguest)) {
    complete_user_login($guest);

    $urltogo = $SESSION->wantsurl;
    unset($SESSION->wantsurl);
    redirect($urltogo);
}

// If site guest is misconfigured or not a valid $wantsurl value,
// go to shib login (non-passive).
if ($wantsurl_guest_ok) {
    error_log("site guest user not correctly configured");
}
redirect($CFG->wwwroot.'/auth/shibboleth/index.php');

