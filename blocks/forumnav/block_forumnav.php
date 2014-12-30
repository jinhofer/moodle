<?php

require_once($CFG->dirroot.'/blocks/forumnav/lib.php');

class block_forumnav extends block_base {

    /**
     * Called before block (not just instance) is deleted from
     * the installation.
     */
    public function before_delete() {
        unset_all_config_for_plugin('block_forumnav');
    }

    public function init() {
        $this->title = get_string('forumnav', 'block_forumnav');
    }

    public function instance_create() {
        global $DB;

        // Default new instance to display on all forum pages. Otherwise,
        // instructor would have to set in every case because the original
        // default would not have it appear on discuss pages where we
        // want to see it. Note that logic in get_content, below,
        // prevents it from appearing in non-edit mode on other pages.
        $blockinstance = new stdClass;
        $blockinstance->id = $this->instance->id;
        $blockinstance->pagetypepattern = 'mod-forum-*';
        $DB->update_record('block_instances', $blockinstance);
    }

    public function applicable_formats() {
        // Could also try adding on the forum page but not displaying anything.
        // That way, an instructor could add it before discussion items are added.
        // Adding to view page causes it to be added to discuss page provided
        // that, in the instance configuration, "Restrict to these page types" is
        // set to "mod-forum-*".
        return array(
            'mod-forum-view'    => true,
            'mod-forum-discuss' => true);
    }

    public function get_content() {
        global $USER;

        // This will cause the block to appear only on the discuss.php pages
        // unless editing is turned on. In that case, it will appear on all
        // of the pages for that forum. We want it available on the main forum
        // page when editing so that the instructor can set it up before
        // any discussion exist.
        if ($this->page->pagetype != 'mod-forum-discuss') {
            return null;
        }

        $currentdiscussionid = optional_param('d', 0, PARAM_INT);
        // Not sure if this would ever happen.
        if ($currentdiscussionid == 0) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $forum = $this->page->activityrecord;
        $istracking = forum_tp_can_track_forums($forum) && forum_tp_is_tracked($forum);

        $forumdiscussions = forumnav_get_discussions($this->page->cm);
        $discussionids = array_keys($forumdiscussions);
        $currentdiscussionindex = array_search($currentdiscussionid, $discussionids);
        $discussionlist = array_values($forumdiscussions);

        list($startindex, $lastindex) = $this->get_start_last_display_indexes($currentdiscussionindex,
                                                                              $discussionlist);
        if ($istracking) {
            $newer_unread_index = $this->get_nearest_newer_unread($currentdiscussionindex, $discussionlist);
            $older_unread_index = $this->get_nearest_older_unread($currentdiscussionindex, $discussionlist);
        }

        $text = '';

        // If we would not display a link for the newest, put it at the top.
        $text .= html_writer::start_tag('ul');
        if ($startindex > 0
            and ! (isset($newer_unread_index) and $newer_unread_index === 0))
        {
            if ($istracking and $discussionlist[0]->unread > 0) {
                $text .= html_writer::start_tag('li', array('class'=>'top notcurrent unreadpost'));
            } else {
                $text .= html_writer::start_tag('li', array('class'=>'top notcurrent'));
            }
            $text .= html_writer::link(new moodle_url('/mod/forum/discuss.php',
                                                      array('d' => $discussionids[0])),
                                       get_string('newest', 'block_forumnav'));
            $text .= html_writer::end_tag('li');
        }

        // If we are displaying no newer unread discussions and some exist, then display one here.
        if (isset($newer_unread_index) and $newer_unread_index < $startindex) {
            $discussion = $discussionlist[$newer_unread_index];
            $text .= html_writer::start_tag('li', array('class'=>'notcurrent unreadpost'));
            $text .= html_writer::link(new moodle_url('/mod/forum/discuss.php',
                                                      array('d' => $discussion->id)),
                                       $discussion->name,
                                       array('title'=>htmlspecialchars_decode($discussion->name)));

            $text .= html_writer::end_tag('li');
            if ($newer_unread_index + 1 != $startindex) {
                $text .= "<li>...</li>";
            }
        }

        for ($i = $startindex; $i <= $lastindex; ++$i) {
            $discussion = $discussionlist[$i];

            if ($i === $currentdiscussionindex) {
                $text .= html_writer::start_tag('li', array('class'=>'current'));
                $text .= '> '.$discussion->name;
            } else {
                if ($istracking and $discussion->unread > 0) {
                    $text .= html_writer::start_tag('li', array('class'=>'notcurrent unreadpost'));
                } else {
                    $text .= html_writer::start_tag('li', array('class'=>'notcurrent'));
                }
                // htmlspecialchars_decode should be okay here because discussion name
                // was cleaned as PARAM_TEXT when entered. TODO: Confirm all secure.
                $text .= html_writer::link(new moodle_url('/mod/forum/discuss.php',
                                                          array('d' => $discussion->id)),
                                           $discussion->name,
                                           array('title'=>htmlspecialchars_decode($discussion->name)));
            }
            $text .= html_writer::end_tag('li');
        }

        // If we are displaying no older unread discussions and some exist, then display one here.
        if (isset($older_unread_index) and $older_unread_index > $lastindex) {

            if ($older_unread_index - 1 != $lastindex) {
                $text .= "<li>...</li>";
            }
            $discussion = $discussionlist[$older_unread_index];
            $text .= html_writer::start_tag('li', array('class'=>'notcurrent unreadpost'));
            $text .= html_writer::link(new moodle_url('/mod/forum/discuss.php',
                                                      array('d' => $discussion->id)),
                                       $discussion->name,
                                       array('title'=>htmlspecialchars_decode($discussion->name)));

            $text .= html_writer::end_tag('li');
        }

        // If not displaying a link for the oldest, add it at the bottom.
        $oldestdiscussion = end($discussionlist);
        if ( $discussionlist[$lastindex]->id != $oldestdiscussion->id
             and ! (isset($older_unread_index) and $older_unread_index === count($discussionlist) - 1 ))
        {
            if ($istracking and $oldestdiscussion->unread > 0) {
                $text .= html_writer::start_tag('li', array('class'=>'bottom notcurrent unreadpost'));
            } else {
                $text .= html_writer::start_tag('li', array('class'=>'bottom notcurrent'));
            }
            $text .= html_writer::link(new moodle_url('/mod/forum/discuss.php',
                                                      array('d' => $oldestdiscussion->id)),
                                       get_string('oldest', 'block_forumnav'));
            $text .= html_writer::end_tag('li');
        }

        $text .= html_writer::end_tag('ul');

        $this->content = new stdClass;
        $this->content->text = $text;

        #$this->content->footer = 'This is the content footer.';

        return $this->content;
    }

    private function get_start_last_display_indexes($currentindex, $discussionlist) {

        $beforeafternum = $this->get_num_before_after_current();

        // We start either at the beginning or the configured number up the list from
        // the current discussion.
        $startindex = $currentindex < $beforeafternum ? 0 : $currentindex - $beforeafternum;

        $lastindex = $currentindex + $beforeafternum;
        $lastindex = min($lastindex, count($discussionlist) - 1);

        return array($startindex, $lastindex);
    }

    private function get_num_before_after_current() {
        global $CFG;

        $beforeafternum = FORUMNAV_DEFAULT_BEFORE_AFTER_NUM;
        // Use instance or block config unless zero or empty.
        if (!empty($this->config->num_before_after)) {
            $beforeafternum = intval($this->config->num_before_after);
        } elseif ($CFG->block_forumnav_num_before_after) {
            $beforeafternum = intval($CFG->block_forumnav_num_before_after);
        }
        return $beforeafternum;
    }

    private function get_nearest_newer_unread($currentdiscussionindex, $discussions) {
        for ($i = $currentdiscussionindex - 1; $i >= 0; --$i) {
            if ($discussions[$i]->unread > 0) {
                return $i;
            }
        }
        return null;
    }

    private function get_nearest_older_unread($currentdiscussionindex, $discussions) {
        $maxindex = count($discussions);
        for ($i = $currentdiscussionindex + 1; $i < $maxindex; ++$i) {
            if ($discussions[$i]->unread > 0) {
                return $i;
            }
        }
        return null;
    }


    /**
     * this block has global config
     * @see block_base::has_config()
     */
    function has_config() {
        return true;
    }
}

