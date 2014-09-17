/*
 * See
 * http://docs.moodle.org/dev/YUI
 * http://docs.moodle.org/dev/JavaScript_guidelines
 * http://docs.moodle.org/dev/How_to_create_a_YUI_3_module
 * http://docs.moodle.org/dev/YUI/Modules
 * http://docs.moodle.org/dev/Javascript/Shifter
 */
YUI.add('moodle-local_course-courserequest', function(Y) {

var courseRequest = function() {
    courseRequest.superclass.constructor.apply(this, arguments);
}

Y.extend(courseRequest, Y.Base, {
    initializer : function (params) {

        var additionalrolesdiv = Y.one('#additionalrolesdiv');
        var additionalroledivs = additionalrolesdiv.all('.additionalrolediv');

        var firstroleselect = additionalroledivs.item(0).one('select');
        if (firstroleselect.get('selectedIndex') == 0) {
            firstroleselect.set('selectedIndex', 1);
        }

        var addadditionalrolerowdiv = Y.one('#addadditionalrolerowdiv');

        addadditionalrolerowdiv.on('click', function () {
            additionalrolesdiv.one('#id_rolediv_add').simulate('click');
        }, this);

        Y.one('#id_requestformfieldset').delegate('change', function (e) {
            additionalroledivs.each(function (div, i, nodelist) {
                if (div.one('select') != e.target) {
                   var options = div.one('select').get('options'); 
                }
            });
        }, 'select');

        //---------------------------------------------

        var categorytree = params['categorytree'];
        var depth1Array = Y.Array(categorytree);

        var depth1 = Y.one('#id_depth1category');
        var depth2 = Y.one('#id_depth2category');
        var depth3 = Y.one('#id_depth3category');
        var depth3div = Y.one('#depth3div');

        depth1.on('change', function(e) {

            var depth1val = depth1.get('value');

            var depth1cat = Y.Array.find(depth1Array, function(cat) {
                                        return cat.id == depth1val;
                                    });

            depth2.setHTML('<option value=""> '+ M.util.get_string('depth2select', 'local_course') +'</option>');
            depth3.setHTML('<option value=""> '+ M.util.get_string('depth3select', 'local_course') +'</option>');
            depth3div.removeClass('hasitems');

            if (! depth1cat) return;

            var children = depth1cat.children;

            if (! children) return;

            for (var i = 0; i < children.length; ++i) {
                var o = Y.Node.create('<option/>');
                o.set('value', children[i].id).set('text', children[i].name);
                depth2.append(o);
            }
        });

        depth2.on('change', function(e) {
            var depth1val = depth1.get('value');
            var depth2val = depth2.get('value');

            depth3.setHTML('<option value=""> '+ M.util.get_string('depth3select', 'local_course') +'</option>');

            var depth1cat = Y.Array.find(depth1Array, function(cat) {
                                        return cat.id == depth1val;
                                    });

            if (! depth1cat) return;

            var depth2cat = Y.Array.find(depth1cat.children, function(cat) {
                                        return cat.id == depth2val;
                                    });

            if (! depth2cat) return;

            var children = depth2cat.children;

            if (!children) {
                depth3div.removeClass('hasitems');
                return;
            }
            depth3div.addClass('hasitems');

            for (var i = 0; i < children.length; ++i) {
                var o = Y.Node.create('<option/>');
                o.set('value', children[i].id).set('text', children[i].name);
                depth3.append(o);
            }
        });

        // The remainder of this function removes unwanted options from depth2 while
        // perserving the UI setting when coming back from server-side user error handling.

        // depth1val should be non-zero only if coming back from user error handling.
        var depth1val = depth1.get('value');

        var depth1cat = Y.Array.find(depth1Array, function(cat) {
                                     return cat.id == depth1val;
                                });

        // Create the array of category ids to keep in depth2.
        var keep = false;
        if (depth1cat) {
            keep = Y.Array.map(depth1cat.children, function(child) { return child.id; });
        }

        // We iterate backwards over the options removing what is not a child of depth1 (which
        // might be selected when coming back from error handling).  Stop before zero to keep top text.
        var depth2node = depth2.getDOMNode();
        var options2 = depth2node.options;
        for (i=options2.length-1; i>0; i--) {
            if (!keep || (Y.Array.indexOf(keep, options2[i].value) < 0)) {
                depth2node.remove(i);
            }
        }
    }
});

M.local_course = M.local_course || {};
M.local_course.init_courseRequest = function(params) {
    return new courseRequest(params);
}

},
'@VERSION@',
{requires:['base','array-extras','node-event-simulate']});

