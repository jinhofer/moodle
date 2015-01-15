<?php

class block_library_resources_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $PAGE;

        // Item visibility settings
        $mform->addElement('header', 'visibility_header', get_string('edit_visibility_header', 'block_library_resources'));

        $yesno = array('yes'=>get_string('yes'), 'no'=>get_string('no'));

        foreach (block_library_resources::$display_items as $item => $default) {
            if ($item == '-----') {
                $mform->addElement('html', '<br><br>');    // separator
            }
            else {
                $mform->addElement('select', "config_show_{$item}", get_string("edit_{$item}", 'block_library_resources'), $yesno);

                $setting_name = 'show_' . $item;

                if (empty($this->block->config->$setting_name)) {
                    $mform->getElement("config_{$setting_name}")->setSelected($default ? 'yes' : 'no');
                }
                else {
                    $mform->getElement("config_{$setting_name}")->setSelected($this->block->config->$setting_name);
                }
            }
        }

        $campuses = array('no'  => get_string('no'),
                          'cr'  => get_string('crookston','block_library_resources'),
                          'dl'  => get_string('duluth','block_library_resources'),
                          'mr'  => get_string('morris','block_library_resources'),
                          'tc'  => get_string('twincities','block_library_resources'));

        // personal account
        $mform->addElement('select', "config_personal_account",
                get_string('edit_personal_account', 'block_library_resources'), $campuses);

        // transfer previous setting of show_personal_account as needed
        if (!isset($this->block->config->personal_account)) {
            if (isset($this->block->config->show_personal_account) && $this->block->config->show_personal_account == 'yes') {
                $mform->getElement('config_personal_account')->setSelected('tc');
            }
        }

        // librarian chat
        $mform->addElement('select', "config_librarian_chat",
                            get_string('edit_librarian_chat', 'block_library_resources'), $campuses);

        // transfer previous setting of show_librarian_chat as needed
        if (!isset($this->block->config->librarian_chat)) {
            if (isset($this->block->config->show_librarian_chat) && $this->block->config->show_librarian_chat == 'yes') {
                $mform->getElement('config_librarian_chat')->setSelected('tc');
            }
        }

        // library homepage
        $mform->addElement('select', 'config_show_homepage',
                get_string('edit_library_homepage', 'block_library_resources'), $campuses);

        // check for previous value of library homepage
        if (!isset($this->block->config->show_homepage)) {
            foreach (array('mr', 'dl', 'cr', 'tc') as $campus) {
                $setting_name = 'show_' . $campus . '_lib';

                if (isset($this->block->config->$setting_name) && $this->block->config->$setting_name == 'yes') {
                    $mform->getElement('config_show_homepage')->setSelected($campus);
                }
            }
        }

        // catalog
        $mform->addElement('select', "config_show_catalog",
                get_string('edit_catalog', 'block_library_resources'), $campuses);

        // check for previous value of catalog
        if (!isset($this->block->config->show_catalog)) {
            foreach (array('mr', 'dl', 'cr', 'tc') as $campus) {
                $setting_name = 'show_' . $campus . '_cat';

                if (isset($this->block->config->$setting_name) && $this->block->config->$setting_name == 'yes') {
                    $mform->getElement('config_show_catalog')->setSelected($campus);
                }
            }
        }

        // research guides
        $campuses_no_tc = $campuses;
        unset($campuses_no_tc['tc']);
        $mform->addElement('select', "config_show_research_guide",
                get_string('edit_research_guide', 'block_library_resources'), $campuses_no_tc);


        // search engine options
        $mform->addElement('header', 'search_engine_header', get_string('edit_search_engine_header', 'block_library_resources'));

        foreach (block_library_resources::$search_engines as $engine => $options) {
            if ($engine == 'articlediscovery') {
                // process article discovery separately
                $setting_name = 'search_use_articlediscovery';

                $mform->addElement('select',
                                   "config_{$setting_name}",
                                   get_string("edit_se_{$engine}", 'block_library_resources'),
                                   $campuses);

                if (empty($this->block->config->$setting_name)) {
                    if (isset($options['default_state']) && $options['default_state'] == 'off') {
                        $mform->getElement("config_{$setting_name}")->setSelected('no');
                    }
                    else {
                        $mform->getElement("config_{$setting_name}")->setSelected('tc');    // default to TC
                    }
                }
                else {
                    $mform->getElement("config_{$setting_name}")->setSelected($this->block->config->$setting_name);
                }
            }
            else if ($engine == 'catalog') {
                // process catalog separately
                $setting_name = 'search_use_catalog';

                $mform->addElement('select',
                                   "config_{$setting_name}",
                                   get_string("edit_se_{$engine}", 'block_library_resources'),
                                   $campuses);

                if (empty($this->block->config->$setting_name)) {
                    // find the first "yes" option
                    $old_engine_map = array(
                            'crookstoncatalog' => 'cr',
                            'duluthcatalog'    => 'dl',
                            'morriscatalog'    => 'mr',
                            'mncatplus'        => 'tc');

                    // get the first "yes" of the old four campuses
                    foreach ($old_engine_map as $old_setting => $campus) {
                        $old_setting = 'search_use_'.$old_setting;
                        if (isset($this->config->$old_setting) && $this->config->$old_setting == 'yes') {
                            $mform->getElement("config_{$setting_name}")->setSelected($campus);
                            break;
                        }
                    }
                }
                else {
                    $mform->getElement("config_{$setting_name}")->setSelected($this->block->config->$setting_name);
                }
            }
            //STRY0010378 20140606 mart0969 - Add PubMed search
            else if ($engine == 'pubmed') {
                // process PubMed separately
                $setting_name = 'search_use_pubmed';

                $pubmed_options = array('no'  => get_string('no'),
                          'gen'  => get_string('general','block_library_resources'),
                          'dl'  => get_string('duluth','block_library_resources'),
                          'tc'  => get_string('twincities','block_library_resources'));
                $mform->addElement('select',
                                   "config_{$setting_name}",
                                   get_string("edit_se_{$engine}", 'block_library_resources'),
                                   $pubmed_options);

                if (empty($this->block->config->$setting_name)) {
                    $mform->getElement("config_{$setting_name}")->setSelected('no');
                }
                else {
                    $mform->getElement("config_{$setting_name}")->setSelected($this->block->config->$setting_name);
                }
            }
            else {
                $mform->addElement('select', "config_search_use_{$engine}", get_string("edit_se_{$engine}", 'block_library_resources'), $yesno);

                $setting_name = 'search_use_' . $engine;

                if (empty($this->block->config->$setting_name)) {
                    $selected = 'yes';

                    // check option
                    if (isset($options['default_state']) && $options['default_state'] == 'off') {
                        $selected = 'no';
                    }

                    $mform->getElement("config_{$setting_name}")->setSelected($selected);
                }
                else {
                    $mform->getElement("config_{$setting_name}")->setSelected($this->block->config->$setting_name);
                }
            }
        }

        $mform->addRule('config_show_library_search',
                        get_string('no_engine_error', 'block_library_resources'),
                        'callback',
                        'M.block_library_resources.validate_show_library_search',
                        'client');

        $PAGE->requires->js('/blocks/library_resources/edit_form.js');
    }
}
