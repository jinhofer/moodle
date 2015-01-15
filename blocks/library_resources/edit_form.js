This file is part of Moodle - http://moodle.org/
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
* @package    blocks
* @subpackage library_resources
* @copyright  2013 University of Minnesota
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

M.block_library_resources = { 
};

M.block_library_resources.validate_show_library_search = function(input) {
    //only check if show_library_search is set to yes
    if (document.getElementById('id_config_show_library_search').value == 'no') {
        return true;
    }

    return document.getElementById('id_config_search_use_articlediscovery').value != 'no' ||
           document.getElementById('id_config_search_use_duluthcatalog').value != 'no' ||
           document.getElementById('id_config_search_use_ebscohost').value != 'no' ||
           document.getElementById('id_config_search_use_mncatplus').value != 'no';
};
