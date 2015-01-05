<?php

/**
 * Helper functions for MoodleKiosk block
 *
 * @package    block_moodlekiosk
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class moodlekiosk_service {
    /**
     *
     */
    public static function search_course($filters) {
        global $USER;

        // form the URL and parameters
        $url = get_config('block_moodlekiosk', 'course_search_url');

        if (empty($url)) {
            throw new Exception('No remote server URL defined');
        }

        $search_token = get_config('block_moodlekiosk', 'search_token');

        if (empty($search_token)) {
            throw new Exception('No API key defined');
        }

        $stamp = time();
        $signature = substr(sha1($search_token.$USER->username.$search_token.$stamp.$search_token),5,30);

        $url = trim($url, ' /')."/{$USER->username}/?stamp={$stamp}&sig={$signature}";

        foreach ($filters as $key => $value) {
            $url .= '&'.$key.'='.rawurlencode($value);
        }

        // query remote server
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if (!empty($error)) {
            throw new Exception($error);
        }

        return json_decode($result);
    }


    public function display_preferences($instance_config) {
        global $OUTPUT;

        $url = new moodle_url('/my/index.php');
        $output = $OUTPUT->box_start('notice');

        // Get block configuration
        $site_config = get_config('block_moodlekiosk');

        // "non-acad first" pref
        $options = array('yes' => get_string('yes'),
                         'no'  => get_string('no'),
                         'site_setting' => get_string('site_setting', 'block_moodlekiosk'));

        $non_acad_first = get_user_preferences('kiosk_nonacadfirst', $site_config->non_acad_first);

        $select = new single_select($url, 'kiosk_nonacadfirst', $options, $non_acad_first, array());
        $select->set_label(get_string('non_acad_first', 'block_moodlekiosk'));
        $output .= $OUTPUT->render($select);

        // mini-list size pref
        $list_sizes = array('site_setting' => get_string('site_setting', 'block_moodlekiosk'));
        for ($i = 2; $i <= 15; $i++) {
            $list_sizes[$i] = $i;
        }
        $list_sizes['-1'] = get_string('all');

        $list_size = get_user_preferences('kiosk_listsize', $site_config->mini_list_size);

        $select = new single_select($url, 'kiosk_listsize', $list_sizes, $list_size, array());
        $select->set_label(get_string('mini_list_size', 'block_moodlekiosk'));
        $output .= $OUTPUT->render($select);

        $output .= $OUTPUT->box_end();
        return $output;
    }


    /**
     * format and return the HTML to display the courses
     * @param JSON object $result search result from calling search_course()
     * @param array $options
     *     'initial_hide'    => bool, hide the courses by default (to be showed by Javascript)
     * @return string
     */
    public function display_courses($result, $instance_config, $options = array()) {

        // if nothing to display, return empty string
        if (isset($result->courses) && count($result->courses) == 0) {
            return '';
        }

        $courses = array('na' => array());

        // iterate and group the courses into terms
        foreach ($result->courses as $course) {
            if (is_null($course->term)) {
                $courses['na'][] = $course;
            }
            else {
                if (!isset($courses[$course->term])) {
                    $courses[$course->term] = array();
                }

                $courses[$course->term][] = $course;
            }
        }

        // sort the terms
        $terms = array();
        foreach ($result->terms as $code => $name) {
            $terms[$code] = $name;
        }

        krsort($terms);    // sort terms from most recent

        $hidden_class = (isset($options['initial_hide']) && $options['initial_hide']) ? 'hidden-course-list' : '';

        // display non-academic courses
        $nacad_content = '';
        if (count($courses['na']) > 0) {

            $nacad_content .= '<div class="moodlekiosk-nonacad-ctn '.$hidden_class.'">'.
                                  '<h2 class="title moodlekiosk-ctn-label">Non-academic courses</h2>'.
                                  '<ul class="moodlekiosk-course-list">';

            foreach ($courses['na'] as $course) {
                $hidden = $course->visible == '1' ? '' : 'dimmed_text';

                $nacad_content .= '<li><div class="moodlekiosk-course '.$hidden.'">'.
                                      '<a href="'.$course->url.'" target="_blank">'.$course->fullname.'</a>'.
                                  '</div></li>';
            }

            $nacad_content .= '</ul></div>';
        }

        // display the terms and courses for academic course
        $acad_content = '';
        if (count($terms) > 0) {
            $acad_content .= '<div class="moodlekiosk-acad-ctn '.$hidden_class.'">'.
                                 '<h2 class="title moodlekiosk-ctn-label">Academic courses</h2>';                                        ;

            foreach ($terms as $term_code => $term_name) {
                $acad_content .= '<div class="moodlekiosk-term">'.
                                     '<h3 class="moodlekiosk-term-name">'.$term_name.'</h3>'.
                                     '<ul class="moodlekiosk-course-list">';

                foreach ($courses[$term_code] as $course) {
                    $hidden = $course->visible == '1' ? '' : 'dimmed_text';

                    $acad_content .= '<li><div class="moodlekiosk-course '.$hidden.'">'.
                                         '<a href="'.$course->url.'" target="_blank">'.$course->fullname.'</a>'.
                                     '</div></li>';
                }

                $acad_content .= '</ul></div>';
            }

            $acad_content .= '</div>';
        }

        // take into account the user and site config
        $site_config = get_config('block_moodlekiosk');

        $non_acad_first = get_user_preferences('kiosk_nonacadfirst', $site_config->non_acad_first ? 'yes' : 'no');

        if ($non_acad_first == 'yes' || ($non_acad_first == 'site_setting' && $site_config->non_acad_first)) {
            return $nacad_content . $acad_content;
        }
        else {
            return $acad_content . $nacad_content;
        }
    }


    /**
     * add the needed JS and return the form HTML code
     *
     * @var int $block_id
     * @return string
     */
    public function get_search_form($block_id) {
        global $CFG;

        // add the search box
        $search_label = get_string('searchlabel', 'block_moodlekiosk');

        $form = <<< EOB
<form id="block-moodlekiosk-search-form" name="block-moodlekiosk-search-form" action="{$CFG->wwwroot}/blocks/moodlekiosk/action.php" method="GET">
	<input type="hidden" id="block-moodlekiosk-action" name="action" value="search">
	<input type="hidden" id="block-moodlekiosk-id" name="id" value="{$block_id}">
    {$search_label}
    <input type="text" id="block-moodlekiosk-search-value" name="search_value" value="">
	<input type="submit" value="Search">
</form>
EOB;

        return $form;
    }


    /**
     * return the array used for js_init_call()
     * @return array
     */
    public function get_jsmodule() {
        return array(
            'name'         => 'block_moodlekiosk',
            'fullpath'     => '/blocks/moodlekiosk/module.js',
            'requires'     => array('base', 'io', 'node', 'json', 'event', 'event-simulate'),
            'strings'	   => array(
                    array('entersearchprompt',     'block_moodlekiosk'),
                    array('linkretract',           'block_moodlekiosk'),
                    array('linkexpand',            'block_moodlekiosk')
            )
        );
    }
}
