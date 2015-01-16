YUI.add('moodle-mod_lesson-lesson', function(Y) {

    // Define a name space to call
    M.mod_lesson = M.mod_lesson || {};
    M.mod_lesson.lesson = {
        init: function() {
            // make the FlowViewer tab open in a new browser tab/window
            Y.one('.nav-tabs li a[href*=flowviewer]').setAttribute('target', '_blank');
        }
    };
}, '@VERSION@', {
    requires: ['node']
});

