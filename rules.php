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
 * @package mod-dataform
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('mod_class.php');

$urlparams = new object();

$urlparams->d          = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id         = optional_param('id', 0, PARAM_INT);            // course module id
$urlparams->rid        = optional_param('rid', -1 , PARAM_INT);          // update rule id

// rules list actions
$urlparams->new        = optional_param('new', 0, PARAM_INT);     // new rule

$urlparams->show       = optional_param('show', 0, PARAM_INT);     // rule show/hide flag
$urlparams->hide       = optional_param('hide', 0, PARAM_INT);     // rule show/hide flag
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

$df->set_page('rules', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/rules.php', array('id' => $df->cm->id)));

// DATA PROCESSING
if ($urlparams->duplicate and confirm_sesskey()) {  // Duplicate any requested rules
    $df->process_rules('duplicate', $urlparams->duplicate, $urlparams->confirmed);

} else if ($urlparams->delete and confirm_sesskey()) { // Delete any requested rules
    $df->process_rules('delete', $urlparams->delete, $urlparams->confirmed);

} else if ($urlparams->show and confirm_sesskey()) {    // set rule to visible
    $df->process_rules('show', $urlparams->show, true);    // confirmed by default

} else if ($urlparams->hide and confirm_sesskey()) {   // set rule to visible
    $df->process_rules('hide', $urlparams->hide, true);    // confirmed by default

} else if ($urlparams->update and confirm_sesskey()) {  // Add/update a new rule
    $df->process_rules('update', $urlparams->rid, true);
}

//  edit a new rule
if ($urlparams->new and confirm_sesskey()) {    
    $rule = $df->get_rule_from_id();
    $ruleform = $df->get_rule_form($rule);
    $df->display_rule_form($ruleform, $rule, $urlparams);

// (or) edit existing rule
} else if ($urlparams->redit and confirm_sesskey()) {  
    $rule = $df->get_rule_from_id($urlparams->redit);
    $ruleform = $df->get_rule_form($rule);
    $df->display_rule_form($ruleform, $rule, $urlparams);

// (or) display the rules list
} else {    
    // any notifications?
    if (!$rules = $df->get_rules()) {
        $df->notifications['bad'][] = get_string('rulesnoneindataform','dataform');  // nothing in dataform
    }

    // print header
    $df->print_header(array('tab' => 'rules', 'urlparams' => $urlparams));

    // display the rule add link
    echo html_writer::empty_tag('br');
    echo html_writer::start_tag('div', array('class'=>'fieldadd mdl-align'));
    echo html_writer::link(new moodle_url('/mod/dataform/rules.php', array('d' => $df->id(), 'sesskey' => sesskey(), 'new' => 1)), get_string('ruleadd','dataform'));
    //echo $OUTPUT->help_icon('ruleadd', 'dataform');
    echo html_writer::end_tag('div');
    echo html_writer::empty_tag('br');

    // if there are rules print admin style list of them
    if ($rules) {

        $rulebaseurl = '/mod/dataform/rules.php';
        $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());
                        
        // table headings
        $strrules = get_string('name');
        $strdescription = get_string('description');
        $strperpage = get_string('ruleperpage', 'dataform');
        $strcustomsort = get_string('rulecustomsort', 'dataform');
        $strcustomsearch = get_string('rulecustomsearch', 'dataform');
        $strvisible = get_string('visible');
        $strhide = get_string('hide');
        $strshow = get_string('show');
        $stredit = get_string('edit');
        $strdelete = get_string('delete');
        $strduplicate =  get_string('duplicate');

        $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'rule\'&#44;this.checked)'));
        $multidelete = html_writer::tag('button', 
                                    $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), 
                                    array('name' => 'multidelete',
                                            'onclick' => 'bulk_action(\'rule\'&#44; \''. htmlspecialchars_decode(new moodle_url($rulebaseurl, $linkparams)). '\'&#44; \'delete\')'));
    

        $table = new html_table();
        $table->head = array($strrules, $strdescription, $strperpage, 
                            $strcustomsort, $strcustomsearch, $strvisible, 
                            $stredit, $strduplicate, $multidelete, $selectallnone);
        $table->align = array('left', 'left', 'center', 'left', 'left', 'center', 'center', 'center', 'center', 'center');
        $table->wrap = array(false, false, false, false, false, false, false, false, false, false);
        $table->attributes['align'] = 'center';
        
        foreach ($rules as $ruleid => $rule) {
            $rulename = html_writer::link(new moodle_url($rulebaseurl, $linkparams + array('redit' => $ruleid, 'rid' => $ruleid)), $rule->name);
            $ruledescription = shorten_text($rule->description, 30);
            $ruleedit = html_writer::link(new moodle_url($rulebaseurl, $linkparams + array('redit' => $ruleid, 'rid' => $ruleid)),
                            $OUTPUT->pix_icon('t/edit', $stredit));
            $ruleduplicate = html_writer::link(new moodle_url($rulebaseurl, $linkparams + array('duplicate' => $ruleid)),
                            $OUTPUT->pix_icon('t/copy', $strduplicate));
            $ruledelete = html_writer::link(new moodle_url($rulebaseurl, $linkparams + array('delete' => $ruleid)),
                            $OUTPUT->pix_icon('t/delete', $strdelete));
            $ruleselector = html_writer::checkbox("ruleselector", $ruleid, false);

            // visible
            if ($rule->visible) {
                $visibleicon = $OUTPUT->pix_icon('t/hide', $strhide);
                $visible = html_writer::link(new moodle_url($rulebaseurl, $linkparams + array('hide' => $ruleid)), $visibleicon);
            } else {
                $visibleicon = $OUTPUT->pix_icon('t/show', $strshow);
                $visible = html_writer::link(new moodle_url($rulebaseurl, $linkparams + array('show' => $ruleid)), $visibleicon);
            }

            $sortoptions = '---';
            $searchoptions = $rule->search ? $rule->search : '---';
            
            // parse custom settings
            if ($rule->customsort or $rule->customsearch) {
                // parse rule sort settings
                $sortfields = array();
                if ($rule->customsort) {
                    $sortfields = unserialize($rule->customsort);
                }
                
                // parse rule search settings
                $searchfields = array();
                if ($rule->customsearch) {
                    $searchfields = unserialize($rule->customsearch);
                }

                // get fields objects
                $fields = $df->get_fields();
                
                if ($sortfields) {
                    foreach ($sortfields as $sortieid => $sortdir) {
                        // check if field participates in default sort
                        $strsortdir = $sortdir ? 'Descending' : 'Ascending';
                        $sortoptions = $OUTPUT->pix_icon('t/'. ($sortdir ? 'down' : 'up'), $strsortdir);
                        $sortoptions .= ' '. $fields[$sortieid]->field->name. '<br />';
                    }
                }
            
                if ($searchfields) {
                    $searcharr = array();
                    foreach ($searchfields as $fieldid => $searchfield) {
                        $fieldoptions = array();
                        if (isset($searchfield['AND']) and $searchfield['AND']) {
                            //$andoptions = array_map("$fields[$fieldid]->format_search_value", $searchfield['AND']);
                            $options = array();
                            foreach ($searchfield['AND'] as $option) {
                                if ($option) {
                                    $options[] = $fields[$fieldid]->format_search_value($option);
                                }
                            }
                            $fieldoptions[] = 'AND <b>'. $fields[$fieldid]->field->name. '</b>:'. implode(',', $options);
                        }
                        if (isset($searchfield['OR']) and $searchfield['OR']) {
                            //$oroptions = array_map("$fields[$fieldid]->format_search_value", $searchfield['OR']);
                            $options = array();
                            foreach ($searchfield['OR'] as $option) {
                                if ($option) {
                                    $options[] = $fields[$fieldid]->format_search_value($option);
                                }
                            }
                            $fieldoptions[] = 'OR <b>'. $fields[$fieldid]->field->name. '</b>:'. implode(',', $options);
                        }
                        if ($fieldoptions) {
                            $searcharr[] = implode('<br />', $fieldoptions);
                        }
                    }
                    if ($searcharr) {
                        $searchoptions = implode('<br />', $searcharr);
                    }
                }
            }

            $table->data[] = array(
                $rulename,
                $ruledescription,
                $rule->perpage,
                $sortoptions,
                $searchoptions,
                $visible,
                $ruleedit,
                $ruleduplicate,
                $ruledelete,
                $ruleselector
            );
        }
                 
        echo html_writer::table($table);
    }
}

$df->print_footer();
