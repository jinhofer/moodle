/**
 * @package    blocks
 * @subpackage massaction
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.block_moodlekiosk = {initialized: false};


/**
 * initialize the MoodleKiosk block
 * @param {object} Y, the YUI instance
 * @param {object} data, list of data from server
 *
 */
M.block_moodlekiosk.init = function(Y, data) {
    var self = this;
    this.Y = Y;        // keep a ref to YUI instance

    // skip if already initialized (in case of multiple instances of the block)
    if (self.initialized == true) {
        return true;
    }

    // attach event handler for the controls
    Y.on('submit', function(e) {
        var search_value = Y.one('#block-moodlekiosk-search-value').get('value').trim();

        if (search_value.length < 3) {
            alert(M.util.get_string('entersearchprompt', 'block_moodlekiosk'));
            e.preventDefault();
        }

        return false;
    }, '#block-moodlekiosk-search-form');

    var nacad_ctns = Y.all('div.moodlekiosk-nonacad-ctn');
    var acad_ctns  = Y.all('div.moodlekiosk-acad-ctn');

    // if we are on the block main page, make the course list expandable
    if (typeof data != 'undefined' && typeof data.mini_list_size != 'undefined') {
        var mini_list_size   = parseInt(data.mini_list_size, 10);

        var hiding_tolerance = 2;
        if (typeof data.hiding_tolerance != 'undefined') {
            hiding_tolerance = parseInt(data.hiding_tolerance, 10);
        }

        // ======== process non-acad list =======
        nacad_ctns.each(function(nacad_ctn) {
            nacad_ctn.setData('expanded', true);

            var nacad_lis = nacad_ctn.all('ul.moodlekiosk-course-list > li');

            // reduce the list if there are at least more than threshold and tolerance
            if (mini_list_size > 0 && nacad_lis.size() > mini_list_size + hiding_tolerance) {
                // add a link to show them all
                var nacad_link = Y.Node.create('<a href="javascript:void(0);" class="moodlekiosk-nonacad-tooggle-link"></a>');
                nacad_ctn.append(Y.Node.create('<div class="moodlekiosk-toggle-link-ctn"></div>').append(nacad_link));

                nacad_link.on('click', function(e) {
                    if (nacad_ctn.getData('expanded') == false) {
                        nacad_lis.removeClass('moodlekiosk-course-hidden');

                        // change the link text
                        nacad_link.set('text', M.util.get_string('linkretract', 'block_moodlekiosk'));
                    }
                    else {
                        // hide all LIs after the first "mini_list_size" courses
                        for (var i = mini_list_size; i < nacad_lis.size(); i++) {
                            nacad_lis.item(i).addClass('moodlekiosk-course-hidden');
                        }

                        // change the link text
                        nacad_link.set('text', M.util.get_string('linkexpand', 'block_moodlekiosk', nacad_lis.size()));
                    }

                    nacad_ctn.setData('expanded', !nacad_ctn.getData('expanded'));
                });

                // simulate an initial click on the link
                Y.Event.simulate(nacad_link.getDOMNode(), 'click');
            }
        });

        // ============= process acad list ==============

        acad_ctns.each(function(acad_ctn) {
            acad_ctn.setData('expanded', true);

            var acad_lis = acad_ctn.all('ul.moodlekiosk-course-list > li');

            // reduce the list if there are at least more than threshold and tolerance
            if (mini_list_size > 0 && acad_lis.size() > mini_list_size + hiding_tolerance) {
                // add a link to show them all
                var acad_link = Y.Node.create('<a href="javascript:void(0);" class="moodlekiosk-acad-toggle-link"></a>');

                acad_ctn.append(Y.Node.create('<div class="moodlekiosk-toggle-link-ctn"></div>').append(acad_link));

                acad_link.on('click', function(e) {
                    var terms = acad_ctn.all('div.moodlekiosk-term');

                    if (acad_ctn.getData('expanded') == false) { // expand the list
                        // remove the hidden class from all terms and courses
                        terms.each(function(term_node) {
                           term_node.removeClass('moodlekiosk-term-hidden');
                           term_node.all('ul.moodlekiosk-course-list > li').removeClass('moodlekiosk-course-hidden');
                        });

                        // change the link text
                        acad_link.set('text', M.util.get_string('linkretract', 'block_moodlekiosk'));
                    }
                    else {  // shorten the list
                        var course_count = 0;

                        terms.each(function(term_node) {
                            // hide the term if it's already over the threshold
                            if (course_count >= mini_list_size) {
                                term_node.addClass('moodlekiosk-term-hidden');
                            }
                            else {
                                var course_lis = term_node.all('ul.moodlekiosk-course-list > li');

                                if (course_lis.size() + course_count > mini_list_size) {
                                    for (var i = mini_list_size - course_count; i < course_lis.size(); i++) {
                                        course_lis.item(i).addClass('moodlekiosk-course-hidden');
                                    }
                                }

                                course_count += course_lis.size();
                            }
                        });

                        // change the link text
                        acad_link.set('text', M.util.get_string('linkexpand', 'block_moodlekiosk', acad_lis.size()));
                    }

                    acad_ctn.setData('expanded', !acad_ctn.getData('expanded'));
                });

                // simulate an initial click on the link
                Y.Event.simulate(acad_link.getDOMNode(), 'click');
            }
        });

    }

    // finally show the content after done prepping them
    nacad_ctns.each(function(nacad_ctn) {
        if (nacad_ctn != null) {
            nacad_ctn.removeClass('hidden-course-list');
        }
    });

    acad_ctns.each(function(acad_ctn) {
        if (acad_ctn != null) {
            acad_ctn.removeClass('hidden-course-list');
        }
    });

    self.initialized = true;
};

