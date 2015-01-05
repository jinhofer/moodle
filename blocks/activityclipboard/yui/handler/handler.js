

YUI.add('moodle-block_activityclipboard-handler', function(Y) {

var activityclipboardHandler = function() {
    activityclipboardHandler.superclass.constructor.apply(this, arguments);
};

activityclipboardHandler.prototype = {

    restore_targets : new Array(),
    block_root: null,
    block_tree: null,
    folders: null,

    createLink : function (title, href, params) {
        href = href ? this.block_root + href + (params ? "?" + params.join("&")
                                                       : "")
                    : "javascript:void(0);";
        var link = Y.Node.create('<a/>');
        link.set('title', title);
        link.set('href' , href);
        return link;
    },

    createIcon : function (src, alt, cls) {
        var icon = Y.Node.create('<img/>');
        icon.setAttrs({
            'src': M.cfg.wwwroot + "/theme/image.php/clean/core/1420489419/" + src,
            'alt': alt,
            'className': cls});
        return icon;
    },

    createIndent : function (width) {
        var indent = Y.Node.create('<img/>');
        indent.setAttrs({
            'src'   : M.cfg.wwwroot + "/pix/spacer.gif",
            'width' : width,
            'height': 10});
        return indent;
    },

    createHidden : function (name, value) {
        var hidden = Y.Node.create('<input/>');
        hidden.setAttrs({
            'type' : 'hidden',
            'name' : name,
            'value': value});
        return hidden;
    },

    createOption : function (value, text) {
        var option = Y.Node.create('<option/>');
        option.set('value', value);
        option.append(text);
        return option;
    },

    Folders : function (outerthis) {
        var block_tree = outerthis.block_tree;

        this.change = function (folder_li) {

            if (folder_li.hasClass('collapsed')) {
                folder_li.removeClass('collapsed');
            } else {
                folder_li.addClass('collapsed');
            }

            var folder_li_nodes = block_tree.all('.activityclipboard_folder');
            this.setCookie(folder_li_nodes);
        };

        this.setFolderDisplay = function (folder_li, display_collapsed) {
            if (display_collapsed) {
                folder_li.addClass('collapsed');
            } else {
                folder_li.removeClass('collapsed');
            }
        };

        this.setCookie = function (folder_li_nodes) {
            var states = new Array(folder_li_nodes.size());
            var folder_ul = null;

            for (var i = 0; i < states.length; i++) {
                states[i] = folder_li_nodes.item(i).hasClass('collapsed') ? 0 : 1;
            }

            Y.Cookie.set('activityclipboard_folder_state',
                         states.join(','),
                         { expires: new Date() + 30 });
        };

        this.getCookie = function () {
            var folder_li_nodes = block_tree.all('.activityclipboard_folder');
            var val = Y.Cookie.get('activityclipboard_folder_state');
            if (!val) {
                return;
            }
            var states = val.split(',');
            for (var k = 0; k < states.length; k++) {
                if (k >= folder_li_nodes.size()) {
                    break;
                }
                var is_collapsed = parseInt(states[k]) ? false : true;

                this.setFolderDisplay(folder_li_nodes.item(k), is_collapsed);
            }
        };
    },

    a2id : function (a) {
        return parseInt(a.ancestor('li').get('id').split("_").pop());
    },

    toggle : function (e) {
        var folder_li = e.target.ancestor('.activityclipboard_folder');
        this.folders.change(folder_li);
        return false;
    },

    restore : function (e) {

        var restore_targets = this.restore_targets;

        if (restore_targets.length == 0) {
            alert(M.str.block_activityclipboard.notarget);
            return false;
        }

        var notice_id = "activityclipboard_notice";

        var cancel = function ()
        {
            var notice = Y.one('#'+notice_id);
            if (notice) {
                notice.remove();
            }
            for (var i = 0; i < restore_targets.length; i++) {
                var el = restore_targets[i].elm;
                el.get('childNodes').remove();
                el.setStyle('display', 'none');
            }
            return false;
        };
        cancel();

        for (var i = 0; i < restore_targets.length; i++) {
            var link = this.createLink(M.str.block_activityclipboard.copyhere, "restore.php", [
                "id="      + this.a2id(e.target),
                "course="  + this.get('course_id'),
                "section=" + restore_targets[i].sec,
                "return="  + encodeURIComponent(this.get('return_url'))
            ]);
            link.append(this.createIcon("movehere", link.get('title'), "movetarget"));
            restore_targets[i].elm.append(link);
            restore_targets[i].elm.setStyle('display', 'block');
        }

        var cancel_link = this.createLink(M.str.moodle.cancel);
        cancel_link.on('click', cancel);
        cancel_link.append(cancel_link.get('title'));

        var notice = Y.Node.create('<div/>');
        notice.set('id', notice_id);
        notice.append(M.str.block_activityclipboard.notice + ": "
                          + e.target.ancestor('li').one('.activityclipboard_itemtext').get('text')
                          + "  (");
        notice.append(cancel_link);
        notice.append(')');

        var outline = Y.one('h2.outline');
        if (outline) {
            outline.insert(notice, 'after');
        } else {
            var maincontent = Y.one('#maincontent');
            maincontent.insert(notice, 'after');
        }

        return false;
    },

    move : function (e) {

        var move_cancel = function () {
            cancel_li.remove(true);
            move_item_li.setStyle('display', 'block');
            itemlist_ul.all('.share_move_dest').remove(true);
        }

        var a = e.target;
        var move_item_id = this.a2id(a);
        var move_item_li = a.ancestor('li');
        var itemlist_ul = a.ancestor('ul');
        var items = itemlist_ul.all("> li[id^='shared_item_']");

        var indent = 0;

        var spacer = itemlist_ul.one('> li > div > img.spacer');
        var indent = spacer ? spacer.get('width') : 0;

        if (items.size() <= 1) {
            alert('No sibling items to move around.');
            return false;
        }

        var cancel_li = Y.Node.create('<li/>');
        cancel_li.append(this.createIndent(indent));
        var cancel_a = this.createLink(M.str.moodle.cancel);
        cancel_a.append(cancel_a.get('title'));
        cancel_a.on('click', move_cancel);
        cancel_li.append(cancel_a);
        items.item(0).insert(cancel_li, 'before');

        // Hide the item we are moving.
        move_item_li.setStyle('display', 'none');

        var instance = this;

        var make_destination_link = function (before_item_id) {
            var dest_li = Y.Node.create('<li/>');
            dest_li.append(instance.createIndent(indent));

            var link = instance.createLink(M.str.block_activityclipboard.movehere, "move.php", [
                "id="     + move_item_id,
                "to="     + before_item_id,
                "return=" + encodeURIComponent(instance.get('return_url'))
            ]);

            link.append(instance.createIcon("movehere", link.get('title'), "movetarget"));
            dest_li.append(link);
            dest_li.addClass('share_move_dest');
            return dest_li;
        };

        items.each(function (item_li) {
            if (item_li == move_item_li) {
                return;
            }

            // Insert move target before each remaining cart item.
            var dest_id = item_li.get('id').split("_").pop();
            var dest_li = make_destination_link(dest_id);

            item_li.insert(dest_li, 'before');
        });

        itemlist_ul.append(make_destination_link(0));

        return false;
    },

    movedir : function (e) {

        var movedir_cancel = function () {
            if (form) {
                form.remove();
                form = null;
                command_icons_span.setStyle('display', 'block');
            }
        }

        var form = Y.Node.create('<form/>');
        form.set('action', this.block_root + 'movedir.php');
        form.set('method', 'POST');
        form.append(this.createHidden('id'    , this.a2id(e.target)));
        form.append(this.createHidden('return', this.get('return_url')));

        var itemlist_ul = e.target.ancestor('ul');

        var instance = this;

        var list = (function () {
            var select = Y.Node.create('<select/>');
            select.set('name', 'to');
            select.append(instance.createOption('', M.str.block_activityclipboard.rootdir));

            var folder_li_nodes = instance.block_tree.all('.activityclipboard_folder');
            folder_li_nodes.each(function (folder_li) {
                var folder_path = folder_li.get('title');
                var folder_option = instance.createOption(folder_path, folder_path);
                select.append(folder_option);
                if (itemlist_ul.ancestor('li') == folder_li) {
                    folder_option.set('selected', 'selected');
                }
            });

            select.on('change', function () {
                form.submit();
            });
            return select;
        })();
        form.append(list);

        var edit = (function () {
            var link = instance.createLink(M.str.moodle.edit);
            link.on('click', function (e) {
                var folder_input = Y.Node.create('<input/>');
                folder_input.setAttrs({
                    'type' : 'text',
                    'size' : 20,
                    'name' : 'to'
                });

                list.replace(folder_input);
                link.remove();
                folder_input.focus();
                // Prevents docked block from closing.
                e.halt(true);
            });

            link.append(instance.createIcon('t/edit', link.get('title'), 'iconsmall'));
            return link;
        })();
        form.append(edit);

        var hide = (function () {
            var link = instance.createLink(M.str.moodle.cancel);
            link.on('click', movedir_cancel);
            link.append(instance.createIcon('t/delete', link.get('title'), 'iconsmall'));
            return link;
        })();
        form.append(hide);

        form.setStyle('marginTop', 0);

        var command_icons_span = e.target.ancestor('.commands');
        command_icons_span.setStyle('display', 'none');
        command_icons_span.insert(form, 'before');
        list.focus();

        if (list.get('options').size() <= 1) {
            edit.simulate('click');
        }

        return false;
    },

    remove : function (e) {
        if (confirm(M.str.block_activityclipboard.confirm_delete)) {
            location.href = this.block_root + "delete.php?" + [
                "id="     + this.a2id(e.target),
                "return=" + encodeURIComponent(this.get('return_url'))
            ].join("&");
        }
        return false;
    },

    initializer : function(config) {

        this.block_root = M.cfg.wwwroot + "/blocks/activityclipboard/";

        var block_instance_id = this.get('instance_id');
        block_div = Y.one("#inst" + block_instance_id);

        var insert = function (o, section, sec_i) {

            var activities_ul = section.one('ul.section');
            if (activities_ul) {
                var dest_li = Y.Node.create('<li/>');
                dest_li.addClass('activity');
                dest_li.setStyle('display', 'none');
                activities_ul.append(dest_li);
                o.restore_targets.push({sec: sec_i, elm: dest_li});
            } else {
                // no activities - insert before menu
                var menu = section.one('div.section_add_menus');
                if (menu) {
                    var dest = Y.Node.create('<div/>');
                    dest.addClass('activity');
                    dest.setStyle('display', 'none');
                    menu.get('parentNode').insert(dest, 'before');
                    o.restore_targets.push({ sec: sec_i, elm: dest });
                }
            }

            var cmds_nodes = section.all('span.commands');

            // try span.actions (Moodle 2.5 and above) if span.commands are not available
            if (cmds_nodes.size() == 0) {
                cmds_nodes = section.all('span.actions');
            }

            cmds_nodes.each(function (cmds_node) {
                var mod_li = cmds_node.ancestor('li.activity');
                var mod_id = null;
                if (mod_li) {
                    mod_id = mod_li.get('id').split('-')[1];
                } else {
                    // In some locations, the only places to find the module id
                    // are in the command links hrefs.
                    var hide_link = cmds_node.one('a.editing_hide');
                    if (hide_link) {
                        var hide_href = hide_link.get('href');
                        var found = hide_href.match(/hide=(\d+)/);
                        mod_id = found[1];
                    }
                }
                if (! mod_id) {
                    // Unable to find a module id, so don't add backup link.
                    return;
                }
                var backup_link = o.createLink(M.str.block_activityclipboard.backup, "backup.php", [
                    "course="  + o.get('course_id'),
                    "module="  + mod_id,
                    "return="  + encodeURIComponent(o.get('return_url'))
                ]);
                backup_link.on('click', function (e) {
                    if (! confirm(M.str.block_activityclipboard.confirm_backup)) {
                        e.preventDefault();
                    }
                });
                backup_link.append(o.createIcon("i/backup", backup_link.get('title'), "iconsmall"));
                cmds_node.append('&#x0A;');
                cmds_node.append(backup_link);
            });
        };

        var body_node = Y.one(document.body);

        // check for the course format
        if (body_node.hasClass('format-weeks')) {
            for (var i=0, section = null;
                 section = Y.one('#section-'+i);
                 i++)
            {
                insert(this, section, i);
            }
        }
        else if (body_node.hasClass('format-onetopic')) {
            for (var i = 0; i < 100; i++) {
                if (section = Y.one('#section-' + i)) {
                    insert(this, section, i);
                    break;
                }
            }
        }
        else {
            // frontpage
            var menus = Y.all('div.section_add_menus');
            for (var i = 0; i < menus.size(); i++) {
                insert(this, menus.item(i).ancestor('div.content'), i);
            }
        }

        // move block command icons into block header
        var activityclipboard_header = block_div.one('#activityclipboard_header');
        var activityclipboard_header_icons = activityclipboard_header.all('.icon');
        var block_commands = block_div.one('.commands');
        activityclipboard_header_icons.each(function (icon) {
            block_commands.append(icon);
        });
        activityclipboard_header.setStyle('display', 'none');

        var instance = this;
        // block_tree stays the same through docking and undocking. block_div does not.
        var block_tree = block_div.one('.block_tree');
        block_tree.delegate('click', instance.movedir, '.activityclipboard_movedir', instance);
        block_tree.delegate('click', instance.move   , '.activityclipboard_move'   , instance);
        block_tree.delegate('click', instance.remove , '.activityclipboard_remove' , instance);
        block_tree.delegate('click', instance.restore, '.activityclipboard_restore', instance);
        block_tree.delegate('click', instance.toggle , '.activityclipboard_folderhead', instance);

        this.block_tree = block_tree;

        this.folders = new this.Folders(this);
        this.folders.getCookie();

        //if (this.get('candock')) {
        //    this.initialise_block(Y, block_div);
        //}
    }
};

Y.extend(activityclipboardHandler, Y.Base, activityclipboardHandler.prototype, {
    NAME  : 'activityclipboard_handler',
    ATTRS : { instance_id : { value: 0 },
              course_id   : { value: 0 },
              return_url  : { value: ''}
            } //,
            //candock : {
            //    validator : Y.Lang.isBool,
            //    value : false
            //}
});
//if (M.core_dock && M.core_dock.genericblock) {
//    Y.augment(activityclipboardHandler, M.core_dock.genericblock);
//}

M.blocks_activityclipboard = M.blocks_activityclipboard || {};
M.blocks_activityclipboard.init_activityclipboardHandler = function(config) {
    return new activityclipboardHandler(config);
};

}, '@VERSION@', {requires:['base','event','selector-css3','node-event-simulate','cookie']});

