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

require_once("$CFG->dirroot/mod/dataform/mod_class.php");

/**
 * Base class for Dataform Rule Types
 */
abstract class dataform_rule_base {

    public $type = 'unknown';  // Subclasses must override the type with their name

    public $df = null;       // The dataform object that this rule belongs to
    public $rule = null;      // The rule object itself, if we know it

    /**
     * Class constructor
     *
     * @param var $df       dataform id or class object
     * @param var $rule    rule id or DB record
     */
    public function __construct($df = 0, $rule = 0) {

        if (empty($df)) {
            throw new coding_exception('Dataform id or object must be passed to view constructor.');
        } else if ($df instanceof dataform) {
            $this->df = $df;
        } else {    // dataform id/object
            $this->df = new dataform($df);
        }

        if (!empty($rule)) {
            // $rule is the rule record
            if (is_object($rule)) {
                $this->rule = $rule;  // Programmer knows what they are doing, we hope

            // $rule is a rule id
            } else if ($ruleobj = $this->df->get_rule_from_id($rule)) {
                $this->rule = $ruleobj->rule;
            } else {
                throw new moodle_exception('invalidrule', 'dataform', null, null, $rule);
            }
        }

        if (empty($this->rule)) {         // We need to define some default values
            $this->set_rule();
        }
    }

    /**
     * Sets up a rule object
     */
    public function set_rule($forminput = null) {
        $this->rule = new object;
        $this->rule->id = !empty($forminput->id) ? $forminput->id : 0;
        $this->rule->type   = $this->type;
        $this->rule->dataid = $this->df->id();
        $this->rule->name = !empty($forminput->name) ? trim($forminput->name) : '';
        $this->rule->description = !empty($forminput->description) ? trim($forminput->description) : '';
        $this->rule->enabled = isset($forminput->enabled) ? $forminput->enabled : 1;
        for ($i=1; $i<=10; $i++) {
            $this->rule->{"param$i"} = !empty($forminput->{"param$i"}) ? trim($forminput->{"param$i"}) : null;
        }
    }

    /**
     * Insert a new rule in the database
     */
    public function insert_rule($fromform = null) {
        global $DB, $OUTPUT;

        if (!empty($fromform)) {
            $this->set_rule($fromform);
        }

        if (!$this->rule->id = $DB->insert_record('dataform_rules', $this->rule)){
            echo $OUTPUT->notification('Insertion of new rule failed!');
            return false;
        } else {
            return $this->rule->id;
        }
    }

    /**
     * Update a rule in the database
     */
    public function update_rule($fromform = null) {
        global $DB, $OUTPUT;
        if (!empty($fromform)) {
            $this->set_rule($fromform);
        }

        if (!$DB->update_record('dataform_rules', $this->rule)) {
            echo $OUTPUT->notification('updating of rule failed!');
            return false;
        }
        return true;
    }

    /**
     * Delete a rule completely
     */
    public function delete_rule() {
        global $DB;

        if (!empty($this->rule->id)) {
            $DB->delete_records('dataform_rules', array('id' => $this->rule->id));
        }
        return true;
    }

    /**
     *
     */
    public function apply_rule() {
        return true;
    }

    /**
     * Getter
     */
    public function get($var) {
        if (isset($this->rule->$var)) {
            return $this->rule->$var;
        } else {
            // TODO throw an exception if $var is not a property of rule
            return false;
        }
    }

    /**
     * Returns the rule id
     */
    public function id() {
        return $this->rule->id;
    }

    /**
     * Returns the rule type
     */
    public function type() {
        return $this->type;
    }

    /**
     * Returns the name of the rule
     */
    public function name() {
        return $this->rule->name;
    }

    /**
     * Returns the type name of the rule
     */
    public function typename() {
        return get_string('pluginname', "dataformrule_{$this->type}");
    }

    /**
     *
     */
    public function df() {
        return $this->df;
    }

    /**
     *
     */
    public function get_form() {
        global $CFG;

        if (file_exists($CFG->dirroot. '/mod/dataform/rule/'. $this->type. '/rule_form.php')) {
            require_once($CFG->dirroot. '/mod/dataform/rule/'. $this->type. '/rule_form.php');
            $formclass = 'mod_dataform_rule_'. $this->type. '_form';
        } else {
            require_once($CFG->dirroot. '/mod/dataform/rule/rule_form.php');
            $formclass = 'mod_dataform_rule_form';
        }
        $custom_data = array('rule' => $this);
        $actionurl = new moodle_url(
            '/mod/dataform/rule/rule_edit.php',
            array('d' => $this->df->id(), 'rid' => $this->id(), 'type' => $this->type)
        );
        return new $formclass($actionurl, $custom_data);
    }

    /**
     *
     */
    public function to_form() {
        return $this->rule;
    }

    /**
     *
     */
    public function is_enabled() {
        return $this->rule->enabled;
    } 

    /**
     *
     */
    public function get_select_sql() {
        if ($this->rule->id > 0) {
            $id = " c{$this->rule->id}.id AS c{$this->rule->id}_id ";
            $content = $this->get_sql_compare_text(). " AS c{$this->rule->id}_content";
            return " $id , $content ";
        } else {
            return '';
        }
    }

    /**
     *
     */
    public function get_sort_from_sql($paramname = 'sortie', $paramcount = '') {
        $ruleid = $this->rule->id;
        if ($ruleid > 0) {
            $sql = " LEFT JOIN {dataform_contents} c$ruleid ON (c$ruleid.entryid = e.id AND c$ruleid.ruleid = :$paramname$paramcount) ";
            return array($sql, $ruleid);
        } else {
            return null;
        }
    }

    /**
     *
     */
    public function get_sort_sql() {
        return $this->get_sql_compare_text();
    }

}

/**
 * Base class for Dataform notification rule types
 */
abstract class dataform_rule_notification extends dataform_rule_base {
    const SEND_MESSAGE = 1;
    const SEND_EMAIL = 2;

    const RECP_AUTHOR = 1;
    const RECP_USER = 2;
    const RECP_ROLES = 4;
    const RECP_ADMIN = 8;
    const RECP_EMAIL = 16;

    public $type = 'unknown';
    protected $sendmethod;
    protected $sender;
    protected $recipient;
    
    /**
     * Class constructor
     *
     * @param var $df       dataform id or class object
     * @param var $rule    rule id or DB record
     */
    public function __construct($df = 0, $rule = 0) {
        parent::__construct($df, $rule);
        
        $this->sendmethod = $this->rule->param1;
        $this->sender = $this->rule->param2;
        $this->recipient = $this->rule->param3;
    }

    /**
     *
     */
    public function process_notification($mode, $entry) {
        global $CFG, $SITE;
        require_once($CFG->libdir.'/eventslib.php');

        $schedule = $this->rule->param2;

        if ($schedule == 'manual' and $mode != self::ENTRY_SELECTED) {
            return true;
        }
        
        if ($schedule == 'onadd' and $mode != self::ENTRY_NEW) {
            return true;
        }
        
        if ($schedule == 'delay' or $schedule == 'after') {
            // put in queue
            return true;
        }

        if ($recipients = $this->get_recipient_users($entry)) {

            $info = new object;
            $info->sitename = format_string($SITE->shortname);
            $info->sendby = $this->rule->param1;
            $info->sender = $this->get_sender($entry);
            $info->dataforms = get_string('modulenameplural', 'dataform');
            $info->dataform = get_string('modulename', 'dataform');
            $info->activity = format_string($this->df()->name(), true);
            $info->url = "$CFG->wwwroot/mod/dataform/view.php?d=".$this->df()->id();

            $subject = !empty($this->rule->param9) ? $this->rule->param9 : get_string('notification', 'dataformrule_notification');
            $info->subject = $subject; //.': '.$info->sender.' -> '.$info->dataform;
            $info->text = $this->format_notification_text($info);
            $info->html = '';

            foreach ($recipients as $recipient) {
                $info->recipient = $recipient;
                $this->send_notification($info);
            }
        }
        return true;
    } 

    /**
     *
     * @return array user objects
     */
    public function get_recipient_users($event, $items) {
        $recipients = array();
        if ($recp_type = $this->recipient) {
            // author
            if ($recp_type & self::RECP_AUTHOR) {
                if ($users = $this->get_author_user($event, $items)) {
                    $recipients = $users;
                }
            }
            
            // admin
            if ($recp_type & self::RECP_ADMIN) {
                $recipients[] = get_admin();               
            }
            
            // email address
            if ($recp_type & self::RECP_EMAIL) {
                $address = $this->rule->param8;
                if (filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
                    $user = new object;
                    $user->email = $address;    
                    $user->firstname = 'emailuser';    
                    $user->lastname = '';    
                    $user->maildisplay = true;    
                    $user->mailformat = 1;    
                    $recipients[] = $user; 
                }              
            }
        }
        return $recipients;
    }

    /**
     * @return object $user
     */
    protected function get_author_user($event, $items) {
        global $USER;
        
        $users = array();

        // Entries
        switch ($event) {
            case 'entryadded':
            case 'entryupdated':
            case 'entrydeleted':
                // get userids from entries
                foreach ($items as $entry) {
                    if (empty($users[$entry->userid])) {
                        if ($entry->userid == $USER->id) {
                            $user = $USER;
                        } else {
                            $user = new object;
                            foreach (explode(',', user_picture::fields()) as $userfield) {
                                if ($userfield == 'id') {
                                    $user->id = $entry->userid;
                                } else if (isset($entry->{$userfield})) {
                                    $user->{$userfield} = $entry->{$userfield};
                                }
                            }
                        }
                        $users[$entry->userid] = $user;
                    }
                }
        }
        return $users;
    }

    /**
     * @return object $user
     */
    protected function get_sender_user($entry) {
        global $USER;
        
        if ($entry->userid == $USER->id) {
            $user = $USER;
        } else {
            $user = new object;
            foreach (explode(',', user_picture::fields()) as $userfield) {
                if ($userfield == 'id') {
                    $user->id = $entry->userid;
                } else {
                    $user->{$userfield} = $entry->{$userfield};
                }
            }
        }
        return $user;
    }

    /**
     * sends a notification (by message or email) to the recipients
     *
     * @global object
     * @uses FORMAT_PLAIN
     * @return void
     */
    protected function send_notification($info) {
        if ($info->recipient->mailformat == 1) {
            $posthtml = $this->format_notification_html($info);
        }

        $eventdata = new object;
        $eventdata->siteshortname   = $info->sitename;
        $eventdata->component       = 'mod_dataform';
        $eventdata->name            = 'submission';
        $eventdata->userfrom        = $info->sender;
        $eventdata->userto          = $info->recipient;
        $eventdata->subject         = $info->subject;
        $eventdata->fullmessage     = $info->text;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = $info->html;
        $eventdata->smallmessage    = '';
        
        if ($info->sendby == self::SEND_EMAIL) {
            //directly email rather than using the messaging system to ensure its not routed to a popup or jabber
            if (!$mailresult = email_to_user($eventdata->userto,
                                                $eventdata->siteshortname,
                                                $eventdata->subject,
                                                $eventdata->fullmessage,
                                                $eventdata->fullmessagehtml,
                                                null, // attachment
                                                null, // attachname
                                                false, // usetrueaddress
                                                null // $CFG->forum_replytouser
                                                )) {
                // notify something
                echo 'could not email message';
            }
        } else if ($info->sendby == self::SEND_MESSAGE) {
            $res = message_send($eventdata);
        }
    }

    /**
     * format the text-part of the email
     *
     * @param object $info
     * @return string the text you want to post
     */
    protected function format_notification_text($info) {
        $course = $this->df()->course;
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $activity = $info->activity;
        $subject = $info->subject;
        $posttext  = "$courseshortname - $activity: $subject\n";
        return $posttext;
    }

    /**
     * format the html-part of the email
     *
     * @global object
     * @param object $info includes some infos about the dataform you want to send
     * @return string the text you want to post
     */
    protected function format_notification_html($info) {
        global $CFG;
        $course = $this->df()->course;
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $course_url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
        $dataform_all_url = $CFG->wwwroot.'/mod/dataform/index.php?id='.$course->id;
        $dataform_url = $CFG->wwwroot.'/mod/dataform/view.php?id='.$this->df()->id();

        $posthtml = '<p><font face="sans-serif">'.
                '<a href="'.$course_url.'">'.$courseshortname.'</a> ->'.
                '<a href="'.$dataform_all_url.'">'.$info->dataforms.'</a> ->'.
                '<a href="'.$dataform_url.'">'.$info->dataform.'</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        //$posthtml .= '<p>'.get_string('emailteachermailhtml', 'dataform', $info).'</p>';
        $posthtml .= '<p>'.'Schults has submitted'.'</p>';
        $posthtml .= '</font><hr />';
        return $posthtml;
    }
    
}
