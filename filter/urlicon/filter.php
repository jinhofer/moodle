<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Filter converting URLs in the text to HTML links
 *
 * @package    filter
 * @subpackage urlicon
 * @copyright  University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class filter_urlicon extends moodle_text_filter {

    /**
     * @var array
     */
    protected $icon_defs = array();


    /**
     *
     */
    public function __construct($context, $localconfig) {
       global $OUTPUT;

       parent::__construct($context, $localconfig);

       $this->icon_defs = array(
           'FLIPGRID'    =>  '<img class="iconlarge activityicon" alt="FlipGrid" src="'.$OUTPUT->pix_url('flipgrid', 'filter_urlicon').'"></img>',
           'GOOGLEDOC'   =>  '<img class="iconlarge activityicon" alt="GoogleDoc" src="'.$OUTPUT->pix_url('gdoc', 'filter_urlicon').'"></img>',
           'GOOGLESPREADSHEET'  =>  '<img class="iconlarge activityicon" alt="GoogleSpreadsheet" src="'.$OUTPUT->pix_url('gss', 'filter_urlicon').'"></img>'
       );
    }

    /**
     * @var array global configuration for this filter
     *
     * This might be eventually moved into parent class if we found it
     * useful for other filters, too.
     */
    protected static $globalconfig;

    /**
     * Apply the filter to the text
     *
     * @see filter_manager::apply_filter_chain()
     * @param string $text to be processed by the text
     * @param array $options filter options
     * @return string text after processing
     */
    public function filter($text, array $options = array()) {
        if (!isset($options['originalformat'])) {
            // if the format is not specified, we are probably called by {@see format_string()}
            // in that case, it would be dangerous to replace URL with the link because it could
            // be stripped. therefore, we do nothing
            return $text;
        }

        $this->add_icon_to_urls($text);

        return $text;
    }

    ////////////////////////////////////////////////////////////////////////////
    // internal implementation starts here
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the global filter setting
     *
     * If the $name is provided, returns single value. Otherwise returns all
     * global settings in object. Returns null if the named setting is not
     * found.
     *
     * @param mixed $name optional config variable name, defaults to null for all
     * @return string|object|null
     */
    protected function get_global_config($name=null) {
        $this->load_global_config();
        if (is_null($name)) {
            return self::$globalconfig;

        } elseif (array_key_exists($name, self::$globalconfig)) {
            return self::$globalconfig->{$name};

        } else {
            return null;
        }
    }

    /**
     * Makes sure that the global config is loaded in $this->globalconfig
     *
     * @return void
     */
    protected function load_global_config() {
        if (is_null(self::$globalconfig)) {
            self::$globalconfig = get_config('filter_urlicon');
        }
    }

    /**
     * Find particular URLs in the given text
     *
     * @param string $text Passed in by reference. The string to be searched for urls.
     */
    protected function add_icon_to_urls(&$text) {
        global $OUTPUT;

        // Check if we support unicode modifiers in regular expressions. Cache it.
        // TODO: this check should be a environment requirement in Moodle 2.0, as far as unicode
        // chars are going to arrive to URLs officially really soon (2010?)
        // Original RFC regex from: http://www.bytemycode.com/snippets/snippet/796/
        // Various ideas from: http://alanstorm.com/url_regex_explained
        // Unicode check, negative assertion and other bits from Moodle.
        static $unicoderegexp;

        if (!isset($unicoderegexp)) {
            $unicoderegexp = @preg_match('/\pL/u', 'a'); // This will fail silently, returning false,
        }

        // process FlipGrid
        $urlstart = '(http(s)?://|(?<!://)(www\.))';
        $domain = '(flipgrid\.com)';

        // add the icon to the links
        $regex = "(<a[^>]*href=[\"']){$urlstart}{$domain}(/?[^\"'<>]*[\"'][^>]*>)([^<]*)(</a>)";

        if ($unicoderegexp) {
            $regex = '#' . $regex . '#ui';
        }
        else {
            $regex = '#' . preg_replace(array('\pLl', '\PL'), 'a-z', $regex) . '#i';
        }

        $text = preg_replace($regex, '$1$2$3$4$5$6'.$this->icon_defs['FLIPGRID'].'$7$8', $text);


        // process Google Document
        $urlstart = '(?:http(?:s)?://|(?<!://)(www\.))';
        $domain = '(?:docs\.google\.com/(a/[^/]*/)?document/d/)';
        $fileid = '(?:[-\w]{25,})';

        // add the icon to the links
        $regex = "(<a[^>]*href=[\"']{$urlstart}{$domain}{$fileid}[^\"'<>]*[\"'][^>]*>)([^<]*)(</a>)";

        if ($unicoderegexp) {
            $regex = '#' . $regex . '#ui';
        }
        else {
            $regex = '#' . preg_replace(array('\pLl', '\PL'), 'a-z', $regex) . '#i';
        }

        $text = preg_replace($regex, '$1'.$this->icon_defs['GOOGLEDOC'].'$2$4$5', $text);


        // process Google Spreadsheet, re-using Google Document definitions
        $domain = '(?:docs\.google\.com/(a/[^/]*/)?spreadsheet/)';
        $fileid = '(?:ccc\?key=[-\w]{25,})';

        // add the icon to the links
        $regex = "(<a[^>]*href=[\"']{$urlstart}{$domain}{$fileid}[^\"'<>]*[\"'][^>]*>)([^<]*)(</a>)";

        if ($unicoderegexp) {
            $regex = '#' . $regex . '#ui';
        }
        else {
            $regex = '#' . preg_replace(array('\pLl', '\PL'), 'a-z', $regex) . '#i';
        }

        $text = preg_replace($regex, '$1'.$this->icon_defs['GOOGLESPREADSHEET'].'$2$4$5', $text);

    }
}