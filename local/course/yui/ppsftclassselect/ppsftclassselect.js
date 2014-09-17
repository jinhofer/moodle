


YUI.add('moodle-local_course-ppsftclassselect', function(Y) {

var ppsftClassSelect = function() {
    ppsftClassSelect.superclass.constructor.apply(this, arguments);
}

Y.extend(ppsftClassSelect, Y.Base, {

    setErrorMessage : function (container, stringname) {
        message = M.util.get_string(stringname, 'local_course');
        container.one('.errormessage').setHTML(message);
    },

    isSomeClassSelected : function (form) {
        return form.all('tbody input[type="checkbox"]').some(function (element, i, nodelist) {
            var value = element.get('checked');
            return value;
        });
    },

    onSubmitCheck : function (e) {
    
        if (! this.isSomeClassSelected(e.target)) {
            e.preventDefault();
            this.setErrorMessage(e.target, 'ppsftsearchresultnotselected');
        }
    },

    initializer : function (params) {

        Y.one('#classselectform').on('submit', this.onSubmitCheck, this);
    }

});

M.ppsft_class_select = M.ppsft_class_select || {};
M.ppsft_class_select.init_ppsftClassSelect = function(params) {
    return new ppsftClassSelect(params);
}

},
'@VERSION@',
{requires:['base']});
