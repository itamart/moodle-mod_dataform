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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 
/**
 * @package mod
 * @subpackage dataform
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once('../mod_class.php');

$urlparams = new object();

$urlparams->d          = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id         = optional_param('id', 0, PARAM_INT);            // course module id
$urlparams->fid        = optional_param('fid', 0 , PARAM_INT);          // update filter id

// filters list actions
$urlparams->new        = optional_param('new', 0, PARAM_INT);     // new filter
$urlparams->default    = optional_param('default', 0, PARAM_INT);  // id of filter to default
$urlparams->visible    = optional_param('visible', 0, PARAM_SEQUENCE);     // filter ids (comma delimited) to hide/show
$urlparams->fedit      = optional_param('fedit', 0, PARAM_INT);   // filter id to edit
$urlparams->delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // filter ids (comma delim) to delete
$urlparams->duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // filter ids (comma delim) to duplicate

$urlparams->confirmed  = optional_param('confirmed', 0, PARAM_INT);    

// filter actions
$urlparams->update     = optional_param('update', 0, PARAM_INT);   // update filter
$urlparams->cancel     = optional_param('cancel', 0, PARAM_BOOL);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);
require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('filter/index', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/filter/index.php', array('id' => $df->cm->id)));

$fm = $df->get_filter_manager();

// DATA PROCESSING
if ($urlparams->update and confirm_sesskey()) {  // Add/update a new filter
    $fm->process_filters('update', $urlparams->fid, true);

} else if ($urlparams->duplicate and confirm_sesskey()) {  // Duplicate any requested filters
    $fm->process_filters('duplicate', $urlparams->duplicate, $urlparams->confirmed);

} else if ($urlparams->delete and confirm_sesskey()) { // Delete any requested filters
    $fm->process_filters('delete', $urlparams->delete, $urlparams->confirmed);

} else if ($urlparams->visible and confirm_sesskey()) {    // set filter's visibility
    $fm->process_filters('visible', $urlparams->visible, true);    // confirmed by default

} else if ($urlparams->default and confirm_sesskey()) {  // set filter to default
    if ($urlparams->default == -1) {
        $df->set_default_filter();    // reset
    } else {
        $df->set_default_filter($urlparams->default); 
    }
}

//  Edit a new filter
if ($urlparams->new and confirm_sesskey()) {    
    $filter = $fm->get_filter_from_id($fm::BLANK_FILTER);
    $filterform = $fm->get_filter_form($filter);
    $fm->display_filter_form($filterform, $filter, $urlparams);

// (or) edit existing filter
} else if ($urlparams->fedit and confirm_sesskey()) {  
    $filter = $fm->get_filter_from_id($urlparams->fedit);
    $filterform = $fm->get_filter_form($filter);
    $fm->display_filter_form($filterform, $filter, $urlparams);

// (or) display the filters list
} else {    
    // Any notifications?
    if (!$filters = $fm->get_filters(null, false, true)) {
        $df->notifications['bad'][] = get_string('filtersnoneindataform','dataform');  // nothing in dataform
    }

    // Print header
    $df->print_header(array('tab' => 'filters', 'urlparams' => $urlparams));

    // Print the filter add link
    $fm->print_add_filter();

    // If there are filters print admin style list of them
    if ($filters) {
        $fm->print_filter_list();
    }
}

$df->print_footer();
