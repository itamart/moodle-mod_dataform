<?php
// This file is part of Moodle - http://moodle.org/.
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
 * @package mod_dataform
 * @category filter
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dataform\pluginbase;

defined('MOODLE_INTERNAL') or die;

/*
 *
 */
class dataformfilterform_standard extends dataformfilterform {

    /*
     *
     */
    public function definition() {

        $filter = $this->_filter;
        $name = empty($filter->name) ? get_string('filternew', 'dataform') : $filter->name;
        $description = empty($filter->description) ? '' : $filter->description;
        $visible = !isset($filter->visible) ? 1 : $filter->visible;

        $df = \mod_dataform_dataform::instance($filter->dataid);
        $fields = $df->field_manager->get_fields(array('exclude' => array(-1)));
        $mform = &$this->_form;

        $mform->addElement('html', get_string('filterurlquery', 'dataform'). ': '. $this->get_url_query($fields));

        // buttons
        // -------------------------------------------------------------------------------
        $this->add_action_buttons(true);

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name and description
        $mform->addElement('text', 'name', get_string('name'));
        $mform->addElement('textarea', 'description', get_string('description'));
        $mform->setType('name', PARAM_TEXT);
        $mform->setType('description', PARAM_TEXT);
        $mform->setDefault('name', $name);
        $mform->setDefault('description', $description);

        // visibility
        $mform->addElement('selectyesno', 'visible', get_string('visible'));
        $mform->setDefault('visible', 1);

        // -------------------------------------------------------------------------------
        // $mform->addElement('header', 'filterhdr', get_string('viewfilter', 'dataform'));
        // $mform->setExpanded('filterhdr');

        // entries per page
        $options = array(
            0 => get_string('choose'),
            1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 15 => 15,
            20 => 20, 30 => 30, 40 => 40, 50 => 50,
            100 => 100, 200 => 200, 300 => 300, 400 => 400, 500 => 500, 1000 => 1000
        );
        $mform->addElement('select', 'perpage', get_string('viewperpage', 'dataform'), $options);
        $mform->setDefault('perpage', $filter->perpage);

        // selection method
        // $options = array(0 => get_string('filterbypage', 'dataform'), 1 => get_string('random', 'dataform'));
        // $mform->addElement('select', 'selection', get_string('filterselection', 'dataform'), $options);
        // $mform->setDefault('selection', $filter->selection);
        // $mform->disabledIf('selection', 'perpage', 'eq', '0');

        // group by
        // $mform->addElement('select', 'groupby', get_string('filtergroupby', 'dataform'), $fieldoptions);
        // $mform->setDefault('groupby', $filter->groupby);

        // Sort options
        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'customsorthdr', get_string('filtercustomsort', 'dataform'));
        $mform->setExpanded('customsorthdr');

        $this->custom_sort_definition($filter->customsort, $fields, true);

        // Search options
        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'customsearchhdr', get_string('filtercustomsearch', 'dataform'));
        $mform->setExpanded('customsearchhdr');

        // General search
        $mform->addElement('text', 'search', get_string('search'));
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', $filter->search);

        // Custom search
        $this->custom_search_definition($filter->customsearch, $fields, true);

        // buttons
        // -------------------------------------------------------------------------------
        $this->add_action_buttons(true);
    }

    /**
     *
     */
    public function add_action_buttons($cancel = true, $submit = null) {
        $mform = &$this->_form;

        $buttonarray = array();
        // Save changes
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        // Continue
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton_continue', get_string('continue'));
        $mform->registerNoSubmitButton('submitbutton_continue');
        // Cancel
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
        $mform->closeHeaderBefore('buttonar');
    }

    /*
     *
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $filter = $this->_filter;
        $df = \mod_dataform_dataform::instance($filter->dataid);

        // validate unique name
        if (empty($data['name']) or $df->name_exists('filters', $data['name'], $filter->id)) {
            $errors['name'] = get_string('invalidname', 'dataform', get_string('filter', 'dataform'));
        }

        return $errors;
    }
}
