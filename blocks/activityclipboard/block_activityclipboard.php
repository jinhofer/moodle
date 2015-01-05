<?php

require_once __DIR__.'/lib.php';

class activityclipboard_Exception extends Exception {}
class activityclipboard_CourseException extends activityclipboard_Exception {}

class block_activityclipboard extends block_base {

    function init() {
        $this->title   = get_string('pluginname', 'block_activityclipboard');
    }

    function applicable_formats() {
        return array(
            'all' => true,
            'mod' => false,
            'my'  => false);
    }


    /**
     * no need to have multiple blocks to perform the same functionality
     */
    function instance_allow_multiple() {
        return false;
    }


    function get_content() {
        global $CFG, $USER, $COURSE,$DB,$PAGE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return $this->content = '';
        }

        if (empty($USER->id)) {
            return $this->content = '';
        }

        $course = $COURSE;

        $context = context_course::instance($course->id);
        $editing = $PAGE->user_is_editing() && has_capability('moodle/course:manageactivities', $context);

        if (!$editing) {
            return $this->content = '';
        }
        $tree = array();

        if ($shared_items = $DB->get_records('block_activityclipboard', array("userid" => $USER->id))) {
            foreach ($shared_items as $shared_item) {
                $node   =& self::path_to_node($tree, explode('/', $shared_item->tree));
                $node[] = array(
                    'id'   => $shared_item->id,
                    'path' => $shared_item->tree,
                    'icon' => activityclipboard_get_icon($shared_item->name,
                                                         $shared_item->icon),
                    'text' => $shared_item->text,
                    'sort' => $shared_item->sort,
                    );
        }
            self::sort_tree($tree);
        }

        $text = '<ul class="block_tree list">'.self::render_tree($tree).'</ul> <!-- End block_tree ul -->';

        $this->page->requires->string_for_js('movehere', 'moodle');
        $this->page->requires->string_for_js('edit'    , 'moodle');
        $this->page->requires->string_for_js('cancel'  , 'moodle');
        $this->page->requires->string_for_js('rootdir'       , 'block_activityclipboard');
        $this->page->requires->string_for_js('notarget'      , 'block_activityclipboard');
        $this->page->requires->string_for_js('copyhere'      , 'block_activityclipboard');
        $this->page->requires->string_for_js('backup'        , 'block_activityclipboard');
        $this->page->requires->string_for_js('notice'        , 'block_activityclipboard');
        $this->page->requires->string_for_js('confirm_backup', 'block_activityclipboard');
        $this->page->requires->string_for_js('confirm_delete', 'block_activityclipboard');

        $this->page->requires->yui_module('moodle-block_activityclipboard-handler',
                                          'M.blocks_activityclipboard.init_activityclipboardHandler',
                                          array(array('instance_id' => $this->instance->id,
                                                      'course_id'   => $course->id,
                                                      'return_url'  => $_SERVER['REQUEST_URI'])));

        $footer_html = '';
        if (!empty($footers)) {
            foreach ($footers as &$footer) {
                $footer = '<div style="margin-top:4px; border-top:1px dashed; padding-top:1px;">'
                        . $footer
                        . '</div>';
            }
            $footer_html = implode('', $footers);
        }

        $this->content         = new stdClass;
        $this->content->text   = $text;
        $this->content->footer = '<div id="activityclipboard_header">'
                               . self::get_header_icons()
                               /*. helpbutton('activityclipboard', $this->title, 'block_activityclipboard', true, false, '', true)*/
                               . '</div>'
                               . $footer_html;

        return $this->content;
    }

/** Internal **/

    /**
     * header icons
     */
    private static function get_header_icons() {
        global $CFG, $COURSE;

        $alt = get_string('bulkdelete', 'block_activityclipboard');
        $dir = $CFG->wwwroot.'/blocks/activityclipboard';
        $url = $dir.'/bulkdelete.php?course='.$COURSE->id;
        return '<a class="icon" title="'.$alt.'" href="'.$url.'">'.
                   '<img src="'.$dir.'/pix/bulkdelete.gif" alt="'.$alt.'" />'.
               '</a>';
    }
    /**
     * path string ("foo/bar/baz") -> tree (["foo"]["bar"]["baz"])
     */
    private static function & path_to_node(&$tree, $path) {
        $i = array_shift($path);
        if (!isset($tree[$i]))
            $tree[$i] = array();
        if ($i == '')
            return $tree[$i];
        return self::path_to_node($tree[$i], $path);
    }
    /**
     * sort tree
     */
    private static function sort_tree(&$node) {
        foreach ($node as $k => &$v) {
            if (!is_numeric($k))
                self::sort_tree($v);
        }
        uksort($node, array(__CLASS__, 'sort_tree_cmp'));
    }
    private static function sort_tree_cmp($lhs, $rhs) {
        // directory first
        if ($lhs == '') return +1;
        if ($rhs == '') return -1;
        return strnatcasecmp($lhs, $rhs);
    }
    /**
     * sort item
     */
    private static function sort_item(&$node) {
        usort($node, array(__CLASS__, 'sort_item_cmp'));
    }
    private static function sort_item_cmp($lhs, $rhs) {
        // by activityclipboard->sort field
        if ($lhs['sort'] < $rhs['sort']) return -1;
        if ($lhs['sort'] > $rhs['sort']) return +1;
        // or by text
        return strnatcasecmp($lhs['text'], $rhs['text']);
    }
    /**
     * render tree as HTML
     */
    private static function render_tree($tree) {
        if (empty(self::$str_cache)) {
            self::$str_cache          = new stdClass;
            self::$str_cache->move    = get_string('move');
            self::$str_cache->delete  = get_string('delete');
            self::$str_cache->restore = get_string('restore', 'block_activityclipboard');
            self::$str_cache->movedir = get_string('movedir', 'block_activityclipboard');
        }
        $text = array();
        self::render_node($text, $tree);
        return implode('', $text);
    }
    private static function render_node(&$text, $node, $id = 0, $dir = array()) {
        foreach ($node as $name => $leaf) {
            if ($name != '') {
                $path = array_merge($dir, array($name));
                self::render_diropen($text, $name, $id, $path);
                $id = self::render_node($text, $leaf, $id + 1, $path);
                self::render_dirclose($text, $name, $id, $path);
            } else {
                self::sort_item($leaf);
                foreach ($leaf as $item) {
                    self::render_item($text, $item);
                }
            }
        }
        return $id;
    }

    private static function render_item(&$text, $item) {
      global $CFG,$OUTPUT;

        $text[] = '<li id="shared_item_'.$item['id'].'">';

        $text[] = '<div class="shared_item">';

        if (!empty($item['icon'])) {
            $text[] = $item['icon'];
        }
        $text[] = '<span class="activityclipboard_itemtext">'.$item['text'].'</span> ';

        $text[] = '<span class="commands">';

        $text[] = '<a title="'.self::$str_cache->movedir.'" class="activityclipboard_movedir" >'
                . '<img src="'.$OUTPUT->pix_url('t/right').'" class="iconsmall"'
                . ' alt="'.self::$str_cache->movedir.'" />'
                . '</a>';

        $text[] = '<a title="'.self::$str_cache->move.'" class="activityclipboard_move" >'
                . '<img src="'.$OUTPUT->pix_url('t/move').'" class="iconsmall"'
                . ' alt="'.self::$str_cache->move.'" />'
                . '</a>';

        $text[] = '<a title="'.self::$str_cache->delete.'" class="activityclipboard_remove" >'
                . '<img src="'.$OUTPUT->pix_url('t/delete').'" class="iconsmall"'
                . ' alt="'.self::$str_cache->delete.'" />'
                . '</a>';

        $text[] = '<a title="'.self::$str_cache->restore.'" class="activityclipboard_restore" >'
                . '<img src="'.$OUTPUT->pix_url('i/restore').'" class="iconsmall"'
                . ' alt="'.self::$str_cache->restore.'" />'
                . '</a>';

        $text[] = '</span>';

        $text[] = '</div>';

        $text[] = "</li>\n";
    }

    private static function render_diropen(&$text, $name, $id, $path) {
        global $CFG, $OUTPUT;

        $path_string = htmlspecialchars(implode('/', $path));

        $text[] = '<li class="activityclipboard_folder" title="'.$path_string.'">';
        $text[] = '<p title="'.$path_string
                   .'" class="activityclipboard_folderhead tree_item branch">'
                   .htmlspecialchars($name).'</p>';
        $text[] = '<ul id="activityclipboard_'.$id.'_item" class="list">'."\n";
    }

    private static function render_dirclose(&$text, $name, $id, $path) {
        $text[] = "</ul></li> <!-- End folder $name li -->\n";
    }

    private static $str_cache;
}
