/**
 * STRY0010016 20130805 kerzn002
 * This entire file is part of the development for single-user creation from
 * the UMN directory. JavaScript for adding users via LDAP at a selector screen.
 * This file is based on user/selector/module.js in Moodle core.
 */

// Define the core_user namespace if it has not already been defined
M.core_user = M.core_user || {};
// Define an ldap_searchers array in the core_user namespace
M.core_user.ldap_searchers = [];

/**
 * Retrieves an instantiated ldap searcher or null if there isn't one by the requested name
 * @param {string} name The name of the searcher to retrieve
 * @return bool
 */
M.core_user.get_ldap_searcher = function (name) {
    return this.ldap_searchers[name] || null;
}

// same general structure as from module.js.  Can't declare this there otherwise the
// M.core_user declaration becomes problematic.
M.core_user.init_ldap_search = function(Y, name, hash, extrafields, lastsearch) {
    var ldap_searcher = {
        name : name,
        extrafields : extrafields,
        ldapfield : Y.one('#'+name+'_ldapsearchtext'),
        ldapbutton : Y.one('#'+name+'_ldapsearchbutton'),
        ldapcourseid : Y.one('#'+name+'_ldapcourseid'),
        listbox : Y.one('#'+this.name),

        init : function() {
            this.ldapbutton.on('click', this.handle_ldapsearch_click, this);
            this.ldapfield.on('click', this.handle_ldapfield_click, this);
        },

        handle_ldapsearch_click : function() {
            var internet_id = this.get_ldap_text();
            var course_id = this.get_course_id();

            // Set the value of ldapfield again. get_ldap_text() converts to lowercase.
            this.ldapfield.set('value', internet_id);

            var iotrans = Y.io(M.cfg.wwwroot +  '/local/user/add_from_ldap.php', {
                method: 'POST',
                data: 'search='+internet_id+'&courseid='+course_id+'&sesskey='+M.cfg.sesskey,
                on: {
                    success: this.handle_response,
                    failure: this.handle_failure
                },
                context: this
            });

            return;
        },

        get_ldap_text : function() {
            return this.ldapfield.get('value').toString().replace(/^ +| +$/, '').toLowerCase();
        },

        get_course_id: function() {
            return this.ldapcourseid.get('value').toString();
        },

        handle_ldapfield_click : function () {
            if (this.ldapfield.hasClass('error')) {
                this.ldapfield.removeClass('error');
            }
            return;
        },

        handle_response : function(requestid, response) {
            var data = Y.JSON.parse(response.responseText);
            // If something went wrong, make the text in the directory search
            // box turn red instead of making a popup.
            if (data.results.status == 'EX') {
                //I wish there was a more verbose way of doing this.
                this.ldapfield.addClass('error');
            }
            else if(data.results.status == 'OK') {
                //set the UI as the user expects
                Y.one('#'+this.name + '_searchtext').set('value', data.results.user);
                Y.one('#'+this.name + '_searchtext').focus();

                //and update the listbox
                var s = M.core_user.get_user_selector(name);
                s.send_query(true);
            }
        }
    }

    Y.augment(ldap_searcher, Y.EventTarget, null, null, {});
    ldap_searcher.init();
    this.ldap_searchers[name] = ldap_searcher;

    return ldap_searcher;
}
