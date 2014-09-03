<?php

// SDLC-82172 20110423 Colin.  This file gets invoked through
//      the on_shib_empty hook in auth/shibboleth/auth.php.
//      This implementation attempts LDAP authentication.
//      It's intended use is for third-party plugin desktop
//      clients that cannot use Shibboleth.

error_log("In shib_ldap_fallback.php with user $username");

// Strip off the '@' and everything after.  This includes any garbage
// characters that a client application might add.
if ($pos = strpos($username, '@')) {
    $uid = substr($username, 0, $pos);
} else {
    $uid = $username;
}

$ldapauth = get_auth_plugin('ldap');
if ($ldapauth->user_login($uid, $password)) {
    error_log("Successfully authenticated $uid in LDAP.");
    $result = true;
} else {
    error_log("Failed to authenticate $uid in LDAP.");
}

