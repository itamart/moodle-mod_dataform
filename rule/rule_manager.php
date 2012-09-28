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

/**
 * Rule manager class
 */
class dataform_rule_manager {

    protected $_df;
    protected $_predefinedrules;
    protected $_customrules;

    /**
     * constructor
     */
    public function __construct($df) {
        $this->_df = $df;
        $this->_predefinedrules = array();
        $this->_customrules = array();
    }

    /**
     * initialize the predefined rules
     */
    protected function get_predefined_rules() {
        if (!$this->_predefinedrules) {
            $dataid = $this->_df->id();

            // Notification rules
            $notifyrules = array(
                array('owner', 'entry', 'entryadded'),
                array('owner', 'entry', 'entryupdated'),
                array('owner', 'entry', 'entrydeleted'),
                array('owner', 'entry', 'entryapproved'),
                array('owner', 'entry', 'entrydisapproved')
            );
        }
        //$rule = array('dataid' => $dataid, 'type' => '_entry', 'name' => get_string('entry', 'dataform'), //'description' => '', 'visible' => 2, 'predefinedname' => '');
        //$this->_predefinedrules[self::_ENTRY] = $this->get_rule($rule);

        return $this->_predefinedrules;
    }

    /**
     *
     */
    public function get_custom_rules($forceget = false) {
        $this->get_rules(null, false, $forceget);
        return $this->_customrules;
    }

    /**
     * given a rule id return the rule object from get_rules
     * Initializes get_rules if necessary
     */
    public function get_rule_from_id($ruleid, $forceget = false) {
        $rules = $this->get_rules(null, false, $forceget);
        
        if (empty($rules[$ruleid])) {;
            return false;
        } else {
            return $rules[$ruleid];
        }
    }

    /**
     * given a rule type returns the rule object from get_rules
     * Initializes get_rules if necessary
     */
    public function get_rules_by_type($type, $menu = false) {
        $typerules = array();
        foreach  ($this->get_rules() as $ruleid => $rule) {
            if ($rule->type() === $type) {
                if ($menu) {
                    $typerules[$ruleid] = $rule->name();
                } else {
                    $typerules[$ruleid] = $rule;
                }
            }
        }
        return $typerules;
    }

    /**
     * given a rule name returns the rule object from get_rules
     */
    public function get_rule_by_name($name) {
        foreach ($this->get_rules() as $rule) {
            if ($rule->name() === $name) {
                return $rule;
            }
        }
        return false;
    }

    /**
     * returns a subclass rule object given a record of the rule
     * used to invoke plugin methods
     * input: $param $rule record from db, or rule type
     */
    public function get_rule($key) {
        global $CFG;

        if ($key) {
            if (is_object($key)) {
                $type = $key->type;
            } else {
                $type = $key;
                $key = 0;
            }
            require_once($type. '/rule_class.php');
            $ruleclass = 'dataform_rule_'. $type;
            $rule = new $ruleclass($this->_df, $key);
            return $rule;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function get_rules($exclude = null, $menu = false, $forceget = false) {
        global $DB;

        if (!$this->_customrules or $forceget) {
            $this->_customrules = array();
            // collate user rules
            if ($rules = $DB->get_records('dataform_rules', array('dataid' => $this->_df->id()))) {
                foreach ($rules as $ruleid => $rule) {
                    $this->_customrules[$ruleid] = $this->get_rule($rule);
                }
            }
        }

        // collate all rules
        $rules = $this->_customrules + $this->get_predefined_rules();
        if (empty($exclude) and !$menu) {
            return $rules;
        } else {
            $retrules = array();
            foreach ($rules as $ruleid => $rule) {
                if (!empty($exclude) and in_array($ruleid, $exclude)) {
                    continue;
                }
                if ($menu) {
                    $retrules[$ruleid]= $rule->name();
                } else {
                    $retrules[$ruleid]= $rule;
                }
            }
            return $retrules;
        }
    }

    /**
     *
     */
    public function process_rules($action, $rids, $confirmed = false) {
        global $OUTPUT, $DB;
        
        $df = $this->_df;
        
        if (!has_capability('mod/dataform:managetemplates', $df->context)) {
            // TODO throw exception
            return false;
        }

        $dfrules = $this->get_rules();
        $rules = array();
        // collate the rules for processing
        if ($ruleids = explode(',', $rids)) {
            foreach ($ruleids as $ruleid) {
                if ($ruleid > 0 and isset($dfrules[$ruleid])) {
                    $rules[$ruleid] = $dfrules[$ruleid];
                }
            }
        }

        $processedrids = array();
        $strnotify = '';

        if (empty($rules) and $action != 'add') {
            $df->notifications['bad'][] = get_string("rulenoneforaction",'dataform');
            return false;
        } else {
            if (!$confirmed) {
                // print header
                $df->print_header('rules');

                // Print a confirmation page
                echo $OUTPUT->confirm(get_string("rulesconfirm$action", 'dataform', count($rules)),
                        new moodle_url('/mod/dataform/rule/index.php', array('d' => $df->id(),
                                                                        $action => implode(',', array_keys($rules)),
                                                                        'sesskey' => sesskey(),
                                                                        'confirmed' => 1)),
                        new moodle_url('/mod/dataform/rule/index.php', array('d' => $df->id())));

                $df->print_footer();
                exit;

            } else {
                // go ahead and perform the requested action
                switch ($action) {
                    case 'add':     // TODO add new
                        if ($forminput = data_submitted()) {
                            // Check for arrays and convert to a comma-delimited string
                            $df->convert_arrays_to_strings($forminput);

                            // Create a rule object to collect and store the data safely
                            $rule = $this->get_rule($forminput->type);
                            $rule->insert_rule($forminput);
                        }
                        $strnotify = 'rulesadded';
                        break;

                    case 'update':     // update existing
                        if ($forminput = data_submitted()) {
                            // Check for arrays and convert to a comma-delimited string
                            $df->convert_arrays_to_strings($forminput);

                            // Create a rule object to collect and store the data safely
                            $rule = reset($rules);
                            $oldrulename = $rule->rule->name;
                            $rule->update_rule($forminput);
                        }
                        $strnotify = 'rulesupdated';
                        break;

                    case 'enabled':
                        foreach ($rules as $rid => $rule) {
                            // disable = 0; enable = 1
                            $enabled = ($rule->rule->enabled ? 0 : 1);
                            $DB->set_field('dataform_rules', 'enabled', $enabled, array('id' => $rid));

                            $processedrids[] = $rid;
                        }

                        $strnotify = '';
                        break;

                    case 'duplicate':
                        foreach ($rules as $rule) {
                            // set new name
                            while ($df->name_exists('rules', $rule->name())) {
                                $rule->rule->name .= '_1';
                            }
                            $ruleid = $DB->insert_record('dataform_rules', $rule->rule);
                            $processedrids[] = $ruleid;
                        }
                        $strnotify = 'rulesadded';
                        break;

                    case 'delete':
                        foreach ($rules as $rule) {
                            $rule->delete_rule();
                            $processedrids[] = $rule->rule->id;
                        }
                        $strnotify = 'rulesdeleted';
                        break;

                    default:
                        break;
                }

                add_to_log($df->course->id, 'dataform', 'rule '. $action, 'rule/index.php?id='. $df->cm->id, $df->id(), $df->cm->id);
                if ($strnotify) {
                    $rulesprocessed = $processedrids ? count($processedrids) : 'No';
                    $df->notifications['good'][] = get_string($strnotify, 'dataform', $rulesprocessed);
                }
                if (!empty($processedrids)) {
                    $this->get_rules(null, false, true);
                }
                
                return $processedrids;
            }
        }
    }

    /**
     *
     */
    public function print_rule_list(){
        global $OUTPUT;
        
        $df = $this->_df;
        
        $editbaseurl = '/mod/dataform/rule/rule_edit.php';
        $actionbaseurl = '/mod/dataform/rule/index.php';
        $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());

        // table headings
        $strname = get_string('name');
        $strtype = get_string('type', 'dataform');
        $strdescription = get_string('description');
        $stredit = get_string('edit');
        $strduplicate =  get_string('duplicate');
        $strdelete = get_string('delete');
        $strenabled = get_string('enabled', 'dataform');
        $strhide = get_string('hide');
        $strshow = get_string('show');

        // The default value of the type attr of a button is submit, so set it to button so that
        // it doesn't submit the form
        $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'rule\'&#44;this.checked)'));
        $multiactionurl = new moodle_url($actionbaseurl, $linkparams);
        $multidelete = html_writer::tag(
            'button', 
            $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), 
            array('type' => 'button', 'name' => 'multidelete', 'onclick' => 'bulk_action(\'rule\'&#44; \''. $multiactionurl->out(false). '\'&#44; \'delete\')')
        );
        $multiduplicate = html_writer::tag(
            'button', 
            $OUTPUT->pix_icon('t/copy', get_string('multiduplicate', 'dataform')), 
            array('type' => 'button', 'name' => 'multiduplicate', 'onclick' => 'bulk_action(\'rule\'&#44; \''. $multiactionurl->out(false). '\'&#44; \'duplicate\')')
        );

        $table = new html_table();
        $table->head = array($strname, $strtype, $strdescription, $strenabled,
                            $stredit, $multiduplicate, $multidelete, $selectallnone);
        $table->align = array('left','left','left', 'center',
                            'center', 'center', 'center', 'center');
        $table->wrap = array(false, false, false, false,
                            false, false, false, false);
        $table->attributes['align'] = 'center';

        $rules = $this->get_rules();
        foreach ($rules as $ruleid => $rule) {
            // Skip predefined rules
            if ($ruleid < 0) {
                continue;
            }
            
            $rulename = html_writer::link(new moodle_url($editbaseurl, $linkparams + array('rid' => $ruleid)), $rule->name());
            $ruleedit = html_writer::link(new moodle_url($editbaseurl, $linkparams + array('rid' => $ruleid)), $OUTPUT->pix_icon('t/edit', $stredit));
            $ruleduplicate = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('duplicate' => $ruleid)), $OUTPUT->pix_icon('t/copy', $strduplicate));
            $ruledelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('delete' => $ruleid)), $OUTPUT->pix_icon('t/delete', $strdelete));
            $ruleselector = html_writer::checkbox("ruleselector", $ruleid, false);

            $ruletype = $rule->typename();
            $ruledescription = shorten_text($rule->rule->description, 30);

            // enabled
            if ($enabled = $rule->rule->enabled) {
                $enabledicon = $OUTPUT->pix_icon('t/hide', $strhide);
            } else {
               $enabledicon = $OUTPUT->pix_icon('t/show', $strshow);
            }
            $ruleenabled = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('enabled' => $ruleid)), $enabledicon);

            $table->data[] = array(
                $rulename,
                $ruletype,
                $ruledescription,
                $ruleenabled,
                $ruleedit,
                $ruleduplicate,
                $ruledelete,
                $ruleselector
            );
        }
        
        echo html_writer::tag('div', html_writer::table($table), array('class' => 'ruleslist'));
    }

    /**
     *
     */
    public function print_add_rule() {
        global $OUTPUT;
        
        // Display the rule form jump list
        $directories = get_list_of_plugins('mod/dataform/rule/');
        $rulemenu = array();

        foreach ($directories as $directory){
            if ($directory[0] != '_') {
                // Get name from language files
                $rulemenu[$directory] = get_string('pluginname',"dataformrule_$directory");
            }
        }
        //sort in alphabetical order
        asort($rulemenu);

        $popupurl = new moodle_url('/mod/dataform/rule/rule_edit.php', array('d' => $this->_df->id(), 'sesskey' => sesskey()));
        $ruleselect = new single_select($popupurl, 'type', $rulemenu, null, array(''=>'choosedots'), 'ruleform');
        $ruleselect->set_label(get_string('ruleadd','dataform'). '&nbsp;');
        $br = html_writer::empty_tag('br');
        echo html_writer::tag('div', $br. $OUTPUT->render($ruleselect). $br, array('class'=>'ruleadd mdl-align'));
        //echo $OUTPUT->help_icon('ruleadd', 'dataform');
    }

}
