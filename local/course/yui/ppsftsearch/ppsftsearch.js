/*
 * See
 * http://docs.moodle.org/dev/YUI
 * http://docs.moodle.org/dev/JavaScript_guidelines
 * http://docs.moodle.org/dev/How_to_create_a_YUI_3_module
 * http://docs.moodle.org/dev/YUI/Modules
 * http://docs.moodle.org/dev/Javascript/Shifter
 */
YUI.add('moodle-local_course-ppsftsearch', function(Y) {

var ppsftSearch = function() {
    ppsftSearch.superclass.constructor.apply(this, arguments);
}

Y.extend(ppsftSearch, Y.Base, {

    clearSubjectForm : function () {
        var subjecttablebody = Y.one('#subjecttable tbody');
        subjecttablebody.all('tr').each(function (tr, i, nodelist) {
            tr.one('select.term').set('value','');
            tr.one('select.institution').set('value', '');
            tr.one('input.subject').set('value', '');
            tr.one('input.catalog').set('value', '');
        });
    },

    addSubjectRow : function () {
        var tbody = Y.one('#subjecttable tbody');
        var lastrow = tbody.one('tr:last-of-type');

        var newrow = lastrow.cloneNode(true);
        newrow.one('input.subject').set('value', '');
        newrow.one('input.catalog').set('value', '');
        tbody.append(newrow);

        tbody.all('tr').each(function (tr, i, nodelist) {
            var nameprefix = 's['+i+']';
            tr.one('select.term').set('name', nameprefix+'[term]');
            tr.one('select.institution').set('name', nameprefix+'[institution]');
            tr.one('input.subject').set('name', nameprefix+'[subject]');
            tr.one('input.catalog').set('name', nameprefix+'[catalog]');
        });
    },

    addClassNumberRow : function () {
        var tbody = Y.one('#classnumbertable tbody');
        var lastrow = tbody.one('tr:last-of-type');
        var newrow = lastrow.cloneNode(true);
        newrow.one('input.classnumber').set('value', '');
        tbody.append(newrow);

        tbody.all('tr').each(function (tr, i, nodelist) {
            var nameprefix = 's['+i+']';
            tr.one('select.term').set('name', nameprefix+'[term]');
            tr.one('select.institution').set('name', nameprefix+'[institution]');
            tr.one('input.classnumber').set('name', nameprefix+'[clsnbr]');
        });
    },

    populateSubjectForm : function (sp) {
        var tbody = Y.one('#subjecttable tbody');
        while (tbody.all('tr').size() < sp.length) {
            this.addSubjectRow();
        }

        tbody.all('tr').each(function (tr, i, nodelist) {
            s = sp[i];
            tr.one('select.term').set('value', s['term']);
            tr.one('select.institution').set('value', s['institution']);
            tr.one('input.subject').set('value', s['subject']);
            tr.one('input.catalog').set('value', s['catalog']);
        });
    },

    populateClassNumberForm : function (sp) {
        var tbody = Y.one('#classnumbertable tbody');
        while (tbody.all('tr').size() < sp.length) {
            this.addClassNumberRow();
        }

        tbody.all('tr').each(function (tr, i, nodelist) {
            s = sp[i];
            tr.one('select.term').set('value', s['term']);
            tr.one('select.institution').set('value', s['institution']);
            tr.one('input.classnumber').set('value', s['clsnbr']);
        });
    },

    isFormEmpty : function (form) {
        var hasValue = form.all('tbody input, tbody select').some(function (element, i, nodelist) {
            return element.get('value');
        });
        return ! hasValue;
    },

    removeEmptyRows : function (form) {
        var emptyRows = new Y.NodeList();
        form.all('tbody tr').each(function (tr, i, nodelist) {
            var empty = ! tr.all('input,select').some(function (element, i, nodelist) {
                return element.get('value')
            });
            if (empty) { emptyRows.push(tr); }
        });
        emptyRows.remove();
    },

    isMissingValues : function (form) {
        return form.all('tbody tr').some(function (tr, i, nodelist) {
            var empty = ! tr.all('input,select').some(function (element, i, nodelist) {
                return element.get('value');
            });
            if (empty) return false;
            var partial = tr.all('input,select').some(function (element, i, nodelist) {
                return ! element.get('value');
            });
            return partial;
        });
    },

    setErrorMessage : function (container, stringname) {
        message = M.util.get_string(stringname, 'local_course');
        container.one('.errormessage').setHTML(message);
    },

    onSubmitCheck : function (e) {
        if (this.isFormEmpty(e.target)) {
            e.preventDefault();
            this.setErrorMessage(e.target, 'ppsftsearchformempty');
            return;
        }

        this.removeEmptyRows(e.target);

        if (this.isMissingValues(e.target)) {
            e.preventDefault();
            this.setErrorMessage(e.target, 'ppsftsearchformmissingparams');
        }
    },

    initializeSubjectForm : function (params) {
        //this.clearSubjectForm();

        if (this.isFormEmpty(Y.one('#subjectform'))) {

            sp = params['subjectparams'];

            if (sp == null || sp.length < 1) {
                this.addSubjectRow();
                this.addSubjectRow();
            } else {
                this.populateSubjectForm(sp);
            }
        }

        Y.one('#addanothersubjectdiv').on('click', function () {
            this.addSubjectRow();
        }, this);

        Y.one('#subjectform').on('submit', this.onSubmitCheck, this);
    },

    initializeClassNumberForm : function (params) {
        //this.clearSubjectForm();

        if (this.isFormEmpty(Y.one('#classnumberform'))) {

            sp = params['classnumberparams'];

            if (sp == null || sp.length < 1) {
                this.addClassNumberRow();
                this.addClassNumberRow();
            } else {
                this.populateClassNumberForm(sp);
            }
        }

        Y.one('#addanothernumberdiv').on('click', function () {
            this.addClassNumberRow();
        }, this);

        Y.one('#classnumberform').on('submit', this.onSubmitCheck, this);
    },

    /** Expects 'searchparams' parameter. */
    initializer : function (params) {

        this.initializeSubjectForm(params);
        this.initializeClassNumberForm(params);
    }

});

M.ppsft_search = M.ppsft_search || {};
M.ppsft_search.init_ppsftSearch = function(params) {
    return new ppsftSearch(params);
}

},
'@VERSION@',
{requires:['base']});

