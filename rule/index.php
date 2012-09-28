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
 * @package dataformrule
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once('../mod_class.php');

$urlparams = new object();

$urlparams->d          = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id         = optional_param('id', 0, PARAM_INT);            // course module id
$urlparams->rid        = optional_param('rid', -1 , PARAM_INT);          // update rule id

// rules list actions
$urlparams->new        = optional_param('new', 0, PARAM_INT);     // new rule

$urlparams->enabled    = optional_param('enabled', 0, PARAM_INT);     // rule enabled/disabled flag
$urlparams->redit     = optional_param('redit', 0, PARAM_SEQUENCE);   // ids (comma delimited) of rules to delete
$urlparams->delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of rules to delete
$urlparams->duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of rules to duplicate

$urlparams->confirmed    = optional_param('confirmed', 0, PARAM_INT);    

// rule actions
$urlparams->update     = optional_param('update', 0, PARAM_INT);   // update rule
$urlparams->cancel     = optional_param('cancel', 0, PARAM_BOOL);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);
require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('rule/index', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/rule/index.php', array('id' => $df->cm->id)));

$rm = $df->get_rule_manager();

// DATA PROCESSING
if ($urlparams->duplicate and confirm_sesskey()) {  // Duplicate any requested rules
    $rm->process_rules('duplicate', $urlparams->duplicate, $urlparams->confirmed);

} else if ($urlparams->delete and confirm_sesskey()) { // Delete any requested rules
    $rm->process_rules('delete', $urlparams->delete, $urlparams->confirmed);

} else if ($urlparams->enabled and confirm_sesskey()) {    // set rule to enabled/disabled
    $rm->process_rules('enabled', $urlparams->enabled, true);    // confirmed by default

} else if ($urlparams->update and confirm_sesskey()) {  // Add/update a new rule
    $rm->process_rules('update', $urlparams->rid, true);
}

// any notifications?
if (!$rules = $rm->get_rules()) {
    $df->notifications['bad'][] = get_string('rulesnoneindataform','dataform');  // nothing in dataform
}

// print header
$df->print_header(array('tab' => 'rules', 'urlparams' => $urlparams));

// print the rule add link
$rm->print_add_rule();

// if there are rules print admin style list of them
if ($rules) {
    $rm->print_rule_list();
}

$df->print_footer();