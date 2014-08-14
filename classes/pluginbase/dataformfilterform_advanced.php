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
class dataformfilterform_advanced extends dataformfilterform_standard {
    /*
     *
     */
    public function definition() {

        $filter = $this->_filter;
        $view = $this->_customdata['view'];

        $name = empty($filter->name) ? get_string('filternew', 'dataform') : $filter->name;

        $fields = $view->get_fields(array('exclude' => array(-1)));

        $mform = &$this->_form;

        // -------------------------------------------------------------------------------
        // $mform->addElement('header', 'advancedfilterhdr', get_string('filteradvanced', 'dataform'));

        // name and description
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', $name);

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

        // search
        $mform->addElement('text', 'search', get_string('search'));
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', $filter->search);

        // custom sort
        $this->custom_sort_definition($filter->customsort, $fields, true);

        // custom search
        $this->custom_search_definition($filter->customsearch, $fields, true);

        // Save button
        $grp = array();
        $grp[] = &$mform->createElement('submit', 'savebutton', get_string('savechanges'));
        $grp[] = &$mform->createElement('submit', 'newbutton', get_string('newfilter', 'filters'));
        $grp[] = &$mform->createElement('cancel');
        $mform->addGroup($grp, "afiltersubmit_grp", null, ' ', false);
    }

}
