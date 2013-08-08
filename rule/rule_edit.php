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
require_once("$CFG->dirroot/mod/dataform/mod_class.php");

$urlparams = new object();
$urlparams->d          = required_param('d', PARAM_INT);    // dataform ID

$urlparams->type       = optional_param('type','' ,PARAM_ALPHA);   // type of a rule to edit
$urlparams->rid        = optional_param('rid',0 ,PARAM_INT);       // rule id to edit

// Set a dataform object
$df = new dataform($urlparams->d);
$df->set_page('rule/rule_edit', array('urlparams' => $urlparams));
require_capability('mod/dataform:managetemplates', $df->context);

$rm = $df->get_rule_manager();

if ($urlparams->rid) {
    $rule = $rm->get_rule_from_id($urlparams->rid, true); // force get
} else if ($urlparams->type) {
    $rule = $rm->get_rule($urlparams->type);
}

$mform = $rule->get_form();

if ($mform->is_cancelled()){
    redirect(new moodle_url('/mod/dataform/rule/index.php', array('d' => $df->id())));

// no submit buttons    
} else if ($mform->no_submit_button_pressed()) {

// process validated    
} else if ($data = $mform->get_data()) { 

   // add new rule
    if (!$rule->id()) {
        $rule->insert_rule($data);
        add_to_log($df->course->id, 'dataform', 'rules add', 'rule_edit.php?d='. $df->id(), '', $df->cm->id);

    // update rule
    } else {
        $data->id = $rule->id();
        $rule->update_rule($data);
        add_to_log($df->course->id, 'dataform', 'rules update', 'rule/index.php?d='. $df->id(). '&amp;id=', $urlparams->rid, $df->cm->id);
    }

    if ($data->submitbutton != get_string('savecontinue', 'dataform')) {
        redirect(new moodle_url('/mod/dataform/rule/index.php', array('d' => $df->id())));
    }
    
    // continue to edit so refresh the form
    $mform = $rule->get_form();
}

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/rule/index.php', array('id' => $df->cm->id)));

// print header
$df->print_header(array('tab' => 'rules', 'nonotifications' => true, 'urlparams' => $urlparams));

$formheading = $rule->id() ? get_string('ruleedit', 'dataform', $rule->name()) : get_string('rulenew', 'dataform', $rule->typename());
echo html_writer::tag('h2', format_string($formheading), array('class' => 'mdl-align'));

// display form
$mform->set_data($rule->to_form());
$mform->display();

$df->print_footer();
