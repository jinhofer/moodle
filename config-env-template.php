<?PHP

// This file is intended to provide a start for developers setting up
// a development environment.
/*
 * Instructions
 *
 * In this file, the placeholder '<developer>' refers to your Internet ID/username.
 * If you have not already, save this file as config-env-<developer>.php, then
 * proceed to modify it.
 *
 * Replace all instances of <developer> with your Internet ID/username. If you are
 * using Vim or GVim, use the following command:
 *
 * :%s/<developer>/<actual Internet ID>/g
 *
 * For example, if I want to replace all instances of <developer> with the Internet
 * ID of 'captKangaroo', then the command above would look like this:
 * :%s/<developer>/captKangaroo/g
 *
 * As a reminder, you will need to ask one of the senior developers for the passwords
 * to use in this file (or simply consult their config-env file). This is because we
 * are no longer checking files in that contain passwords. There are reminders to
 * request this data on the relevant lines. As well, remember not to check your
 * personal copy of this file (named config-env-<developer>.php) in because we do not
 * want to commit passwords to the repository!
 */

$CFG->dbhost    = 'mysql-dev1.oit.umn.edu';
$CFG->dbname    = 'moodle28_<developer>';
$CFG->dbuser    = 'moodledev28';
$CFG->dbpass    = '<password>'; // Ask for the password to use

$CFG->wwwroot   = 'https://<developer>.moodledev-28.oit.umn.edu';

$CFG->dataroot  = '/nfs/moodle/<developer>/28dev/moodledata';

// These two lines are only for development environments that
// use reverse proxying.
$CFG->reverseproxy = true;
$CFG->sslproxy = true;

$CFG->noemailever = true;
$CFG->divertallemailsto = '<developer>@umn.edu';

$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '/nfs/moodle/<developer>/28phpu/moodledata';
$CFG->phpunit_directorypermissions = 0777;

$CFG->behat_prefix = 'behat_';
$CFG->behat_dataroot = '/nfs/moodle/<developer>/28behat/moodledata';
$CFG->behat_wwwroot = 'https://behat<developer>.moodledev-28.oit.umn.edu';

$CFG->forced_plugin_settings['auth/ldap']['bind_pw'] = '<password>'; // Ask for the password to use

$CFG->forced_plugin_settings['auth/shibboleth']['logout_handler']
                = 'https://<developer>.moodledev-28.oit.umn.edu/Shibboleth.sso/Logout';

$CFG->forced_plugin_settings['auth/shibboleth']['logout_return_url']
                = 'https://idp-test.shib.umn.edu/idp/LogoutUMN';

$CFG->alternateloginurl = '/local/login/shibpassive.php';
$CFG->shibboleth_login_handler = 'https://<developer>.moodledev-28.oit.umn.edu/Shibboleth.sso/Login';

$CFG->ppsft_dbpass = '<password>'; // Ask for the password to use

$CFG->xsendfile = 'X-Sendfile';           // Apache {@see https://tn123.org/mod_xsendfile/}

