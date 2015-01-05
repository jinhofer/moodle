

YUI.add('moodle-block_activityclipboard-bulkdelete', function(Y) {

var activityclipboardBulkDelete = function() {
    activityclipboardBulkDelete.superclass.constructor.apply(this, arguments);
};

Y.extend(activityclipboardBulkDelete, Y.Base,
{
    submit_btn : null,

    initializer : function (config) {

        this.submit_btn = Y.one('#id_submitbutton');

        this.submit_btn.on('click', function(e) {
            if (! confirm(M.str.block_activityclipboard.confirm_delete_selected)) {
                e.preventDefault();
            }
            return false;
        });

        var instance = this;
        var checkboxes_div = Y.one('#bulkdelete_checkboxes');
        checkboxes_div.delegate('click', instance.setSubmitButtonState, '.checkboxgroup1', instance);

        var selectallnone_btn = Y.one('#id_selectallnone');
        selectallnone_btn.on('click', function(e) {
            var selectall = ! instance.isAtLeastOneChecked();
            Y.all('.checkboxgroup1').set('checked', selectall);

            instance.submit_btn.set('disabled', !selectall);
        });

        this.setSubmitButtonState();
    },

    setSubmitButtonState : function() {
        this.submit_btn.set('disabled', ! this.isAtLeastOneChecked()); 
    },

    isAtLeastOneChecked : function() {
        var atleastonechecked = false;
        Y.all('.checkboxgroup1').some(function (chkbx) {
            if (chkbx.get('checked')) {
                atleastonechecked = true;
                return true;;
            }
        });
        return atleastonechecked;
    }
    
},
{
    NAME : 'activityclipboard_bulkdelete'
}
);


M.blocks_activityclipboard = M.blocks_activityclipboard || {};
M.blocks_activityclipboard.init_activityclipboardBulkDelete = function(config) {
    return new activityclipboardBulkDelete(config);
};

}, '0.0.1', {requires:['base']});
