<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain, including:
 * @copyright 2005 Moodle Pty Ltd http://moodle.com
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Dataform class
 */
class dataform {

    const _ENTRY = -1;
    const _TIMECREATED = -2;
    const _TIMEMODIFIED = -3;
    const _APPROVED = -4;
    const _GROUP = -5;
    const _USERID = -6;
    const _USERNAME = -7;
    const _USERFIRSTNAME = -8;
    const _USERLASTNAME = -9;
    const _USERUSERNAME = -10;
    const _USERIDNUMBER = -11;
    const _USERPICTURE = -12;
    const _COMMENT = -13;
    const _RATING = -14;

    const PACKAGE_COURSEAREA = 'course_packages';
    const PACKAGE_SITEAREA = 'site_packages';
    const PACKAGE_SITECONTEXT = SYSCONTEXTID;

    const USER_FILTER = -1;
    const USER_FILTER_SET = -2;
    const USER_FILTER_RESET = -3;
 
    const COUNT_ALL = 0;
    const COUNT_APPROVED = 1;
    const COUNT_UNAPPROVED = 2;
    const COUNT_LEFT = 3;

    public $cm = NULL;       // The course module
    public $course = NULL;   // The course record
    public $data = NULL;     // The dataform record
    public $context = NULL;  //

    public $groupmode = 0;
    public $currentgroup = 0;    // current group id

    public $notifications = array('bad' => array(), 'good' => array());

    protected $fields = array();
    protected $views = array();
    protected $filters = array();
    protected $_currentview = null;

    // internal fields
    protected $internalfields = array();

    // internal group modes
    protected $internalgroupmodes = array(
            'separateparticipants' => -1
    );

    protected $locks = array(
            'approval'   => 1,
            'comments'   => 2,
            'ratings'   => 4
    );

    /**
     * constructor
     */
    public function __construct($d = 0, $id = 0, $autologinguest = false) {
        global $DB;

        // initialize from dataform id or object
        if ($d) {
            if (is_object($d)) { // try object first
                $this->data = $d;
            } else if (!$this->data = $DB->get_record('dataform', array('id' => $d))) {
                print_error("Invalid Dataform ID: $d");
            }
            if (!$this->course = $DB->get_record('course', array('id' => $this->data->course))) {
                print_error('Course is misconfigured');
            }
            if (!$this->cm = get_coursemodule_from_instance('dataform', $this->id(), $this->course->id)) {
                print_error('Course Module ID was incorrect');
            }
        // initialize from course module id
        } else if ($id) {
            if (!$this->cm = get_coursemodule_from_id('dataform', $id)) {
                print_error('Course Module ID was incorrect');
            }
            if (!$this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
                print_error('Course is misconfigured');
            }
            if (!$this->data = $DB->get_record('dataform', array('id' => $this->cm->instance))) {
                print_error('Course module is incorrect');
            }
        }

        // initialize the internal fields
        $dataid = $this->data->id;
        $this->internalfields[dataform::_ENTRY] = (object) array('id' => dataform::_ENTRY, 'dataid' => $dataid, 'type' => '_entry', 'name' => get_string('entry', 'dataform'), 'description' => '' , 'internalname' => '');
        $this->internalfields[dataform::_TIMECREATED] =(object) array('id' => dataform::_TIMECREATED, 'dataid' => $dataid, 'type' => '_time', 'name' => get_string('timecreated', 'dataform'), 'description' => '' , 'internalname' => 'timecreated');
        $this->internalfields[dataform::_TIMEMODIFIED] =(object) array('id' => dataform::_TIMEMODIFIED, 'dataid' => $dataid, 'type' => '_time', 'name' => get_string('timemodified', 'dataform'), 'description' => '' , 'internalname' => 'timemodified');
        $this->internalfields[dataform::_APPROVED] =(object) array('id' => dataform::_APPROVED, 'dataid' => $dataid, 'type' => '_approve', 'name' => get_string('approved', 'dataform'), 'description' => '' , 'internalname' => 'approved');
        $this->internalfields[dataform::_GROUP] =(object) array('id' => dataform::_GROUP, 'dataid' => $dataid, 'type' => '_group', 'name' => get_string('group', 'dataformfield__group'), 'description' => '' , 'internalname' => 'groupid');
        $this->internalfields[dataform::_USERID] =(object) array('id' => dataform::_USERID, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userid', 'dataform'), 'description' => '' , 'internalname' => 'id');
        $this->internalfields[dataform::_USERNAME] =(object) array('id' => dataform::_USERNAME, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('username', 'dataform'), 'description' => '' , 'internalname' => 'name');
        $this->internalfields[dataform::_USERFIRSTNAME] =(object) array('id' => dataform::_USERFIRSTNAME, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userfirstname', 'dataform'), 'description' => '' , 'internalname' => 'firstname');
        $this->internalfields[dataform::_USERLASTNAME] =(object) array('id' => dataform::_USERLASTNAME, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userlastname', 'dataform'), 'description' => '' , 'internalname' => 'lastname');
        $this->internalfields[dataform::_USERUSERNAME] =(object) array('id' => dataform::_USERUSERNAME, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userusername', 'dataform'), 'description' => '' , 'internalname' => 'username');
        $this->internalfields[dataform::_USERIDNUMBER] =(object) array('id' => dataform::_USERIDNUMBER, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('useridnumber', 'dataform'), 'description' => '' , 'internalname' => 'idnumber');
        $this->internalfields[dataform::_USERPICTURE] =(object) array('id' => dataform::_USERPICTURE, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userpicture', 'dataform'), 'description' => '' , 'internalname' => 'picture');
        $this->internalfields[dataform::_COMMENT] =(object) array('id' => dataform::_COMMENT, 'dataid' => $dataid, 'type' => '_comment', 'name' => get_string('comments', 'dataform'), 'description' => '' , 'internalname' => 'comments');
        $this->internalfields[dataform::_RATING] = (object) array('id' => dataform::_RATING, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratings', 'dataform'), 'description' => '' , 'internalname' => 'ratings');
        //$this->internalfields[dataform::_GRADE] = (object) array('id' => dataform::_GRADE, 'dataid' => $dataid, 'type' => '_grade', 'name' => get_string('grade', 'grades'), 'description' => '' , 'internalname' => 'grading');

        // get context
        $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);

        // set groups
        if ($this->cm->groupmode and in_array($this->cm->groupmode, $this->internalgroupmodes)) {
            $this->groupmode = $this->cm->groupmode;
        } else {
            $this->groupmode = groups_get_activity_groupmode($this->cm);
            $this->currentgroup = groups_get_activity_group($this->cm, true);
        }
    }

    /**
     *
     */
    public function id() {
        return $this->data->id;
    }

    /**
     *
     */
    public function name() {
        return $this->data->name;
    }

    /**
     *
     */
    public function internal_group_modes() {
        return $this->internalgroupmodes;
    }

    /**
     *
     */
    public function locks($type) {
        if (array_key_exists($type, $this->locks)) {
            return $this->locks[$type];
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function get_entriescount($type, $user = 0) {
        global $DB;
        
        switch ($type) {
            case self::COUNT_ALL:
                $count = $DB->count_records_sql('SELECT COUNT(e.id) FROM {dataform_entries} e WHERE e.dataid = ? AND e.grading <> 1', array($dataform->id));
                break;
        
            case self::COUNT_APPROVED:
                break;
        
            case self::COUNT_UNAPPROVED:
                break;
        
            case self::COUNT_LEFT:
                break;
        
        }

        return $count;
    }

    /**
     *
     */
    public function update($params, $notify = '') {
        global $DB;

        if ($params) {
            $updatedf = false;
            foreach ($params as $key => $value) {
                $oldvalue = !empty($this->data->{$key}) ? $this->data->{$key} : null; 
                $newvalue = !empty($value) ? $value : null; 
                if ($newvalue != $oldvalue) {
                    $this->data->{$key} = $value;
                    $updatedf = true;
                }
            }
            if ($updatedf) {
                if (!$DB->update_record('dataform', $this->data)) {
                    if ($notify === true) {
                        $this->notifications['bad'][] = get_string('dfupdatefailed', 'dataform');
                    } else if ($notify) {
                        $this->notifications['bad'][] = $notify;
                    }
                    return false;
                } else {
                    if ($notify === true) {
                        //$this->notifications['good'][] = get_string('dfupdatefailed', 'dataform');
                    } else if ($notify) {
                        $this->notifications['good'][] = $notify;
                    }
                }
            }
        }
        return true;
    }

    /**
     * sets the dataform page
     *
     * @param string $page current page
     * @param array $params 
     */
    public function set_page($page = 'view', $params = null) {
        global $CFG, $PAGE, $USER;

        // auto gues login
        $autologinguest = $page == 'view' ? true : false;
        
        // require login
        require_login($this->course->id, $autologinguest, $this->cm);

        $params = (object) $params;
        $urlparams = array();
        if (!empty($params->urlparams)) {
            foreach ($params->urlparams as $param => $value) {
                if ($value != 0 and $value != '') {
                    $urlparams[$param] = $value;
                }
            }
        }

        // make sure there is at least dataform id param
        $urlparams['d'] = $this->id();

        $manager = has_capability('mod/dataform:managetemplates', $this->context);

        // if dataform activity closed don't let students in
        if (!$manager) {
            $timenow = time();
            if (!empty($this->data->timeavailable) and $this->data->timeavailable > $timenow) {
                print_error('notopenyet', 'dataform', null, userdate($this->data->timeavailable));
            }
        }

        // Is user editing
        $urlparams['edit'] = optional_param('edit', 0, PARAM_BOOL);

        $PAGE->set_url("/mod/dataform/$page.php", $urlparams);

        // RSS and CSS and JS
        if (!empty($params->rss) and !empty($CFG->enablerssfeeds) && !empty($CFG->dataform_enablerssfeeds) && $df->data->rssarticles > 0) {
            require_once($CFG->libdir . '/rsslib.php');
            $rsstitle = format_string($this->course->shortname) . ': %fullname%';
            rss_add_http_header($this->context, 'mod_dataform', $this->data, $rsstitle);
        }
        if (!empty($params->css) and $this->data->css) {
            $PAGE->requires->css('/mod/dataform/css.php?d='.$this->id());
        }
        if (!empty($params->js) and $this->data->js) {
            $PAGE->requires->js('/mod/dataform/js.php?d='.$this->id(), true);
        }
        if (!empty($params->modjs)) {
            $PAGE->requires->js('/mod/dataform/dataform.js', true);
        }
        if (!empty($params->comments)) {
            require_once("$CFG->dirroot/comment/lib.php");
            comment::init();
        }

        // editing button
        if ($PAGE->user_allowed_editing()) {
            if ($urlparams['edit'] != -1) { // teacher editing mode
                $USER->editing = $urlparams['edit'];
            }

            //$buttons = '<table><tr><td><form method="get" action="'. "$page.php". '"><div>'.
            $buttons = '<table><tr><td><form method="get" action="'. $PAGE->url. '"><div>'.
                '<input type="hidden" name="id" value="'.$this->cm->id.'" />'.
                '<input type="hidden" name="edit" value="'.($PAGE->user_is_editing()?0:1).'" />'.
                '<input type="submit" value="'.get_string($PAGE->user_is_editing()?'blockseditoff':'blocksediton').'" /></div></form></td></tr></table>';
            $PAGE->set_button($buttons);
        }

        // TODO
        //if ($mode == 'asearch') {
        //    $PAGE->navbar->add(get_string('search'));
        //}

        // Mark as viewed
        if (!empty($params->completion)) {
            require_once($CFG->libdir . '/completionlib.php');
            $completion = new completion_info($this->course);
            $completion->set_module_viewed($this->cm);
        }

        // auto refresh
        if (!empty($urlparams['refresh'])) {
           $PAGE->set_periodic_refresh_delay($urlparams['refresh']);
        }

        // page layout
        if (!empty($urlparams['pagelayout'])) {
            $PAGE->set_pagelayout($urlparams['pagelayout']);
        }
        
        $PAGE->set_title($this->name());
        $PAGE->set_heading($this->course->fullname);

        // set current view and view's page requirements
        $currentview = !empty($urlparams['view']) ? $urlparams['view'] : 0;
        if ($this->_currentview = $this->get_view_from_id($currentview)) {
            $this->_currentview->set_page($page);
        }
        
        // if a new dataform or incomplete design, direct manager to manage area
        if ($manager) {
            $fields = $this->get_fields();;
            $views = $this->get_views();
            if (!$fields or !$views) {
                $this->notifications['bad']['getstarted'] = get_string('getstarted','dataform');
            }
            if (!$fields and !$views) {
                $linktopackages = html_writer::link(new moodle_url('packages.php', array('d' => $this->id())), get_string('packages', 'dataform'));
                $this->notifications['bad']['getstartedpackages'] = get_string('getstartedpackages','dataform', $linktopackages);
            }
            if (!$fields)  {
                $linktofields = html_writer::link(new moodle_url('fields.php', array('d' => $this->id())), get_string('fields', 'dataform'));
                $this->notifications['bad']['getstartedfields'] = get_string('getstartedfields','dataform', $linktofields);
            }
            if (!$views)  {
                $linktoviews = html_writer::link(new moodle_url('views.php', array('d' => $this->id())), get_string('views', 'dataform'));
                $this->notifications['bad']['getstartedviews'] = get_string('getstartedviews','dataform', $linktoviews);
            } else if (!$this->data->defaultview) {
                $linktoviews = html_writer::link(new moodle_url('views.php', array('d' => $this->id())), get_string('views', 'dataform'));
                $this->notifications['bad']['defaultview'] = get_string('viewnodefault','dataform', $linktoviews);
            }
        }

        return true;
    }

    /**
     * prints the header of the current dataform page
     *
     * @param array $params
     */
    public function print_header($params = null) {
        global $OUTPUT;

        $params = (object) $params;

        echo $OUTPUT->header();        
        echo $OUTPUT->heading(format_string($this->name()));

        // print intro
        if (!empty($params->intro) and $params->intro) {
            $this->print_intro();
        }

        // print the tabs
        if (!empty($params->tab)) {
            $currenttab = $params->tab;
            include('tabs.php');
        }

        // print groups menu if needed
        if (!empty($params->groups)) {
            $this->print_groups_menu($params->urlparams->view, $params->urlparams->filter);
        }

        // TODO: explore letting view decide whether to print rsslink and intro
        //$df->print_rsslink();

        // print any notices
        if (empty($params->nonotifications)) {
            foreach ($this->notifications['good'] as $notification) {
                if (!empty($notification)) {
                    echo $OUTPUT->notification($notification, 'notifysuccess');    // good (usually green)
                }
            }
            foreach ($this->notifications['bad'] as $notification) {
                if (!empty($notification)) {
                    echo $OUTPUT->notification($notification);    // bad (usually red)
                }
            }
        }

    }

    /**
     * prints the footer of the current dataform page
     *
     * @param array $params
     */
    public function print_footer($params = null) {
        global $OUTPUT;

        echo $OUTPUT->footer();
    }

    /**
     * TODO: consider moving into the view
     */
    public function print_groups_menu($view, $filter) {
        if ($this->groupmode and !in_array($this->groupmode, $this->internalgroupmodes)) {
            $returnurl = new moodle_url('/mod/dataform/view.php', 
                                        array('d' => $this->id(),
                                                'view' => $view,
                                                'filter' => $filter));
            groups_print_activity_menu($this->cm, $returnurl.'&amp;');
        }
    }

    /**
     * TODO: consider moving into the view
     */
    public function print_rsslink() {
        // Link to the RSS feed
        if (!empty($CFG->enablerssfeeds) && !empty($CFG->dataform_enablerssfeeds) && $this->data->rssarticles > 0) {
            echo '<div style="float:right;">';
            rss_print_link($this->course->id, $USER->id, 'dataform', $this->id(), get_string('rsstype'));
            echo '</div>';
            echo '<div style="clear:both;"></div>';
        }
    }

    /**
     * TODO: consider moving into the view
     */
    public function print_intro() {
        global $OUTPUT;
        // TODO: make intro stickily closable
        // display the intro only when there are on pages: if ($this->data->intro and empty($page)) {
        if ($this->data->intro) {
            $options = new stdClass();
            $options->noclean = true;
            echo $OUTPUT->box(format_module_intro('dataform', $this->data, $this->cm->id), 'generalbox', 'intro');
        }
    }
    
    /**
     * 
     */
    public function display() {
        if (!empty($this->_currentview)) {
            $this->_currentview->display();

            add_to_log($this->course->id, 'dataform', 'view', 'view.php?id='. $this->cm->id, $this->id(), $this->cm->id);
        }
    }

/**********************************************************************************
 * FIELDS
 *********************************************************************************/

    /**
     * given a field id return the field object from $this->fields
     * Initializes $this->fields if necessary
     */
    public function get_field_from_id($fieldid, $forceget = false) {
        if (!$fields = $this->get_fields(null, false, $forceget) or empty($fields[$fieldid])) {;
            return false;
        } else {
            return $fields[$fieldid];
        }
    }

    /**
     * given a field type returns the field object from $this->fields
     * Initializes $this->fields if necessary
     */
    public function get_fields_by_type($type, $menu = false) {
        if (!$fields = $this->get_fields()) {;
            return false;
        } else {
            $typefields = array();
            foreach  ($fields as $fieldid => $field) {
                if ($field->type() === $type) {
                    if ($menu) {
                        $typefields[$fieldid] = $field->name();
                    } else {
                        $typefields[$fieldid] = $field;
                    }
                }
            }
            return $typefields;
        }
    }

    /**
     * given a field name returns the field object from $this->fields
     * Initializes $this->fields if necessary
     */
    public function get_field_by_name($name) {
        if ($fields = $this->get_fields()) {;
            foreach ($fields as $field) {
                if ($field->name() === $name) {
                    return $field;
                }
            }
        }
        return false;
    }

    /**
     * returns a subclass field object given a record of the field
     * used to invoke plugin methods
     * input: $param $field record from db, or field type
     */
    public function get_field($key) {
        global $CFG;

        if ($key) {
            if (is_object($key)) {
                $type = $key->type;
            } else {
                $type = $key;
                $key = 0;
            }
            require_once('field/'. $type. '/field_class.php');
            $fieldclass = 'dataform_field_'. $type;
            $field = new $fieldclass($this, $key);
            return $field;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function get_fields($exclude = null, $menu = false, $forceget = false) {
        global $DB;

        if (!$this->fields or $forceget) {
            $this->fields = array();
            if ($fields = $DB->get_records('dataform_fields', array('dataid' => $this->id()))) {
                // collate user fields
                foreach ($fields as $fieldid => $field) {
                    $this->fields[$fieldid] = $this->get_field($field);
                }

                // collate internalfields only if there are user fields
                foreach ($this->internalfields as $fieldid => $field) {
                    $this->fields[$fieldid] = $this->get_field($field);
                }
            }
        }

        if ($this->fields) {
            if (empty($exclude) and !$menu) {
                return $this->fields;
            } else {
                $fields = array();
                foreach ($this->fields as $fieldid => $field) {
                    if (!empty($exclude) and in_array($fieldid, $exclude)) {
                        continue;
                    }
                    if ($menu) {
                        $fields[$fieldid]= $field->name();
                    } else {
                        $fields[$fieldid]= $field;
                    }
                }
                return $fields;
            }
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function process_fields($action, $fids, $confirmed = false) {
        global $OUTPUT, $DB;

        $dffields = $this->get_fields();
        $fields = array();
        if ($fieldids = explode(',', $fids)) { // some fields are specified for action
            foreach ($fieldids as $fieldid) {
                if ($fieldid > 0 and isset($dffields[$fieldid])) {
                    // Must be from this dataform and user can manage entries
                    if ($dffields[$fieldid]->field->dataid == $this->id() and has_capability('mod/dataform:managetemplates', $this->context)) {
                        $fields[$fieldid] = $dffields[$fieldid];
                    }
                }
            }
        }

        $processedfids = array();
        $strnotify = '';

        if (empty($fields) and $action != 'add') {
            $this->notifications['bad'][] = get_string("fieldnoneforaction",'dataform');
            return false;
        } else {
            if (!$confirmed) {
                // print header
                $this->print_header('fields');

                // Print a confirmation page
                echo $OUTPUT->confirm(get_string("fieldsconfirm$action", 'dataform', count($fields)),
                        new moodle_url('/mod/dataform/fields.php', array('d' => $this->id(),
                                                                        $action => implode(',', array_keys($fields)),
                                                                        'sesskey' => sesskey(),
                                                                        'confirmed' => 1)),
                        new moodle_url('/mod/dataform/fields.php', array('d' => $this->id())));

                echo $OUTPUT->footer();
                exit;

            } else {
                // go ahead and perform the requested action
                switch ($action) {
                    case 'add':     // TODO add new
                        if ($forminput = data_submitted()) {
                            // Check for arrays and convert to a comma-delimited string
                            $this->convert_arrays_to_strings($forminput);

                            // Create a field object to collect and store the data safely
                            $field = $this->get_field($forminput->type);
                            $field->insert_field($forminput);
                        }
                        $strnotify = 'fieldsadded';
                        break;

                    case 'update':     // update existing
                        if ($forminput = data_submitted()) {
                            // Check for arrays and convert to a comma-delimited string
                            $this->convert_arrays_to_strings($forminput);

                            // Create a field object to collect and store the data safely
                            $field = reset($fields);
                            $oldfieldname = $field->field->name;
                            $field->update_field($forminput);

                            // Update the views
                            if ($oldfieldname != $field->field->name) {
                                $this->replace_field_in_views($oldfieldname, $field->field->name);
                            }
                        }
                        $strnotify = 'fieldsupdated';
                        break;

                    case 'duplicate':
                        foreach ($fields as $field) {
                            // set new name
                            while ($this->name_exists('fields', $field->name())) {
                                $field->field->name .= '_1';
                            }
                            $fieldid = $DB->insert_record('dataform_fields', $field->field);
                            $processedfids[] = $fieldid;
                        }
                        $strnotify = 'fieldsadded';
                        break;

                    case 'delete':
                        foreach ($fields as $field) {
                            $field->delete_field();
                            $processedfids[] = $field->field->id;
                            // Update views
                            $this->replace_field_in_views($field->field->name, '');
                        }
                        $strnotify = 'fieldsdeleted';
                        break;

                    default:
                        break;
                }

                add_to_log($this->course->id, 'dataform', 'field '. $action, 'fields.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if ($strnotify) {
                    $fieldsprocessed = $processedfids ? count($processedfids) : 'No';
                    $this->notifications['good'][] = get_string($strnotify, 'dataform', $fieldsprocessed);
                }
                return $processedfids;
            }
        }
    }

/**********************************************************************************
 * VIEWS
 *********************************************************************************/

    /**
     * TODO there is no need to instantiate all viewds!!!
     * this function creates an instance of the particular subtemplate class   *
     */
    public function get_view_from_id($viewid = 0) {

        if ($views = $this->get_views()) {
            if ($viewid and isset($views[$viewid])) {
                return $views[$viewid];

            // if can't find the requested, try the default
            } else if ($viewid = $this->data->defaultview  and isset($views[$viewid])) {
                return $views[$viewid];
            }
        }

        return false;
    }

    /**
     * returns a view subclass object given a view record or view type
     * invoke plugin methods
     * input: $param $vt - mixed, view record or view type
     */
    public function get_view($vt) {
        global $CFG;

        if ($vt) {
            if (is_object($vt)) {
                $type = $vt->type;
            } else {
                $type = $vt;
                $vt = 0;
            }
            require_once($CFG->dirroot. '/mod/dataform/view/'. $type. '/view_class.php');
            $viewclass = 'dataform_view_'. $type;
            $view = new $viewclass($this, $vt);
            return $view;
        }
    }

    /**
     * given a view type returns the view object from $this->views
     * Initializes $this->views if necessary
     */
    public function get_views_by_type($type, $menu = false) {
        if (!$views = $this->get_views()) {;
            return false;
        } else {
            $typeviews = array();
            foreach  ($views as $viewid => $view) {
                if ($view->type() === $type) {
                    if ($menu) {
                        $typeviews[$viewid] = $view->name();
                    } else {
                        $typeviews[$viewid] = $view;
                    }
                }
            }
            return $typeviews;
        }
    }

    /**
     *
     */
    public function get_views($exclude = null, $menu = false, $forceget = false) {
        global $DB;

        if (empty($this->views) or $forceget) {
            $this->views = array();
            if ($views = $DB->get_records('dataform_views', array('dataid' => $this->id()))) {
                // collate user views
                foreach ($views as $viewid => $view) {
                    $this->views[$viewid] = $this->get_view($view);
                }
            }
        }

        if ($this->views) {
            if (empty($exclude) and !$menu) {
                return $this->views;
            } else {
                $views = array();
                foreach ($this->views as $viewid => $view) {
                    if (!empty($exclude) and in_array($viewid, $exclude)) {
                        continue;
                    }
                    if ($menu) {
                        $views[$viewid]= $view->view->name;
                    } else {
                        $views[$viewid]= $view;
                    }
                }
                return $views;
            }
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function set_default_view($viewid = 0) {
        global $DB;

        $rec = new object();
        $rec->id = $this->id();
        $rec->defaultview = $viewid;
        if (!$DB->update_record('dataform', $rec)) {
            print_error('There was an error updating the database');
        }
        $this->data->defaultview = $viewid;
    }

    /**
     * Search for a field name and replaces it with another one in all the *
     * form templates. Set $newfieldname as '' if you want to delete the   *
     * field from the form.
     */
    public function replace_field_in_views($searchfieldname, $newfieldname) {
        if ($views = $this->get_views()) {
            foreach ($views as $view) {
                $view->replace_field_in_view($searchfieldname, $newfieldname);
            }
        }
    }

    /**
     *
     */
    public function process_views($action, $vids, $confirmed = false) {
        global $DB, $OUTPUT;

        $views = array();
        if ($vids) { // some views are specified for action
            if ($candidates = $DB->get_records_select('dataform_views', "id IN ($vids)")) {
                foreach ($candidates as $vid => $view) {
                    // Must be from this dataform and owned by current user or user can manage entries
                    if ($view->dataid == $this->id() and has_capability('mod/dataform:manageentries', $this->context)) {
                        $views[$vid] = $view;
                    }
                }
            }
        }

        $processedvids = array();
        $strnotify = '';

        if (empty($views)) {
            $this->notifications['bad'][] = get_string("viewnoneforaction",'dataform');
            return false;
        } else {
            if (!$confirmed) {
                // print header
                $this->print_header('views');

                // Print a confirmation page
                echo $OUTPUT->confirm(get_string("viewsconfirm$action", 'dataform', count($views)),
                        new moodle_url('/mod/dataform/views.php', array('d' => $this->id(),
                                                                        $action => implode(',', array_keys($views)),
                                                                        'sesskey' => sesskey(),
                                                                        'confirmed' => 1)),
                        new moodle_url('/mod/dataform/views.php', array('d' => $this->id())));

                echo $OUTPUT->footer();
                exit;

            } else {
                // go ahead and perform the requested action
                switch ($action) {
                    case 'visible':
                        $updateview = new object();
                        foreach ($views as $view) {
                            if ($view->id == $this->data->defaultview) {
                                // TODO: notify something
                                continue;
                            } else {
                                $updateview->id = $view->id;
                                $updateview->visible = (($view->visible + 1) % 3);  // hide = 0; (show) = 1; show = 2
                                $DB->update_record('dataform_views', $updateview);

                                $processedvids[] = $view->id;
                            }
                        }

                        $strnotify = 'viewsupdated';
                        break;

                    case 'hide':
                        $updateview = new object();
                        $updateview->visible = 0;
                        foreach ($views as $view) {
                            if ($view->id == $this->data->defaultview) {
                                // TODO: notify something
                                continue;
                            } else {
                                $updateview->id = $view->id;
                                $DB->update_record('dataform_views', $updateview);
                                $processedvids[] = $view->id;
                            }
                        }

                        $strnotify = 'viewsupdated';
                        break;

                    case 'filter':
                        $updateview = new object();
                        $filterid = optional_param('fid', 0, PARAM_INT);
                        foreach ($views as $view) {
                            if ($filterid != $view->filter) {
                                $updateview->id = $view->id;
                                $updateview->filter = $filterid;
                                $DB->update_record('dataform_views', $updateview);
                                $processedvids[] = $view->id;
                            }
                        }

                        $strnotify = 'viewsupdated';
                        break;

                    case 'reset':
                        foreach ($views as $viewid => $viewrec) {
                            // get view object
                            $view = $this->get_view($viewrec);

                            // generate default view and update
                            $view->generate_default_view();                            

                            // update view
                            $view->update_view($view->view);
                            
                            $processedvids[] = $viewid;
                        }

                        $strnotify = 'viewsupdated';
                        break;

                    case 'duplicate':
                        foreach ($views as $view) {
                            // TODO: check for limit

                            // set name
                            while ($this->name_exists('views', $view->name)) {
                                $view->name = 'Copy of '. $view->name;
                            }
                            $viewid = $DB->insert_record('dataform_views',$view);

                            $processedvids[] = $viewid;
                        }

                        $strnotify = 'viewsadded';
                        break;

                    case 'delete':
                        foreach ($views as $view) {
                            // TODO: delete filters
                            //delete_records('dataform_filters', array('viewid', $view->id));
                            $DB->delete_records('dataform_views', array('id' => $view->id));
                            $processedvids[] = $view->id;

                            // reset default view if needed
                            if ($view->id == $this->data->defaultview) {
                                $this->set_default_view();
                            }
                        }
                        $strnotify = 'viewsdeleted';
                        break;

                    case 'default':
                        foreach ($views as $view) { // there should be only one
                            if ($view->visible != 2) {
                                $updateview = new object();
                                $updateview->id = $view->id;
                                $updateview->visible = 2;
                                $DB->update_record('dataform_views', $updateview);
                            }

                            $this->set_default_view($view->id);
                            // TODO: shouldn't produced this notification
                            $processedvids[] = $view->id;
                            break;
                        }
                        $strnotify = 'viewsupdated';
                        break;

                    default:
                        break;
                }

                add_to_log($this->course->id, 'dataform', 'view '. $action, 'views.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if ($strnotify) {
                    $viewsprocessed = $processedvids ? count($processedvids) : 'No';
                    $this->notifications['good'][] = get_string($strnotify, 'dataform', $viewsprocessed);
                }
                return $processedvids;
            }
        }
    }

/**********************************************************************************
 * FILTERS
 *********************************************************************************/

    /**
     *
     */
    public function get_filter_from_id($filterid = 0) {
        global $DB;

        if ($filterid == dataform::USER_FILTER_SET) {  // set user preferences
            set_user_preference('dataform_'. $this->id(). '_perpage', optional_param('userperpage', get_user_preferences('dataform_'. $this->id(). '_perpage', 0), PARAM_INT));
            set_user_preference('dataform_'. $this->id(). '_groupby', optional_param('usergroupby', get_user_preferences('dataform_'. $this->id(). '_groupby', 0), PARAM_INT));
            set_user_preference('dataform_'. $this->id(). '_search', optional_param('usersearch', get_user_preferences('dataform_'. $this->id(). '_search', ''), PARAM_NOTAGS));
            set_user_preference('dataform_'. $this->id(). '_customsort', optional_param('usercustomsort', get_user_preferences('dataform_'. $this->id(). '_customsort', $this->data->defaultsort), PARAM_RAW));
            set_user_preference('dataform_'. $this->id(). '_customsearch', optional_param('usercustomsearch', get_user_preferences('dataform_'. $this->id(). '_customsearch', ''), PARAM_RAW));
            $filterid = dataform::USER_FILTER;
        
        } else if ($filterid == dataform::USER_FILTER_RESET) {  // reset user preferences
            unset_user_preference('dataform_'. $this->id(). '_perpage');
            unset_user_preference('dataform_'. $this->id(). '_groupby');
            unset_user_preference('dataform_'. $this->id(). '_search');
            unset_user_preference('dataform_'. $this->id(). '_customsort');
            unset_user_preference('dataform_'. $this->id(). '_customsearch');
            $filterid = 0;
        }
        
        if ($filterid == 0) {  // df default sort
            $filter = new object();
            $filter->id = 0;
            $filter->dataid = $this->id();
            $filter->perpage = 0;
            $filter->groupby = 0;
            $filter->customsort = $this->data->defaultsort;
            $filter->customsearch = '';
            $filter->search = '';

        } else if ($filterid == dataform::USER_FILTER) {  // user preferences
            $filter = new object();
            $filter->id = $filterid;
            $filter->dataid = $this->id();
            $filter->perpage = get_user_preferences('dataform_'. $this->id(). '_perpage', 0);
            $filter->groupby = get_user_preferences('dataform_'. $this->id(). '_groupby', 0);
            $filter->search = trim(get_user_preferences('dataform_'. $this->id(). '_search', ''));
            $filter->customsort = trim(get_user_preferences('dataform_'. $this->id(). '_customsort', $this->data->defaultsort));
            $filter->customsearch = trim(get_user_preferences('dataform_'. $this->id(). '_customsearch', ''));

        } else {
            // TODO check that from this dataform
            $filter = $DB->get_record('dataform_filters', array('id' => $filterid));
        }

        return $filter;
    }

    /**
     *
     */
    public function get_filter_from_form($formdata) {
        $filter = new object();
        $filter->id = $formdata->fid;
        $filter->dataid = $this->id();
        $filter->name = $formdata->name;
        $filter->description = $formdata->description;
        $filter->perpage = $formdata->perpage;
        $filter->groupby = $formdata->groupby;
        $filter->search = isset($formdata->search) ? $formdata->search : '';
        $filter->customsort = $this->get_sort_options_from_form($formdata);
        $filter->returntoform = false;
        $filter->customsearch = $this->get_search_options_from_form($formdata, $filter->returntoform);

        if ($filter->customsearch) {
            $filter->search = '';
        }

        return $filter;
    }

    /**
     *
     */
    public function process_filters($action, $fids, $confirmed = false) {
        global $CFG, $DB, $OUTPUT;

        $filters = array();
        // TODO may need new roles
        if (has_capability('mod/dataform:managetemplates', $this->context)) {
            // don't need record from database for filter form submission
            if ($fids) { // some filters are specified for action
                $filters = $DB->get_records_select('dataform_filters', "id IN ($fids)");
            } else if ($action == 'update') {
                $filters[] = $this->get_filter_from_id();
            }
        }

        $processedfids = array();
        $strnotify = '';

        // TODO update should be roled
        if (empty($filters)) {
            $this->notifications['bad'][] = get_string("filternoneforaction", 'dataform');
            return false;
        } else {
            if (!$confirmed) {
                // print header
                $this->print_header('filters');

                // Print a confirmation page
                echo $OUTPUT->confirm(get_string("filtersconfirm$action", 'dataform', count($filters)),
                        new moodle_url('/mod/dataform/filters.php', array('d' => $this->id(),
                                                                        $action => implode(',', array_keys($filters)),
                                                                        'sesskey' => sesskey(),
                                                                        'confirmed' => 1)),
                        new moodle_url('/mod/dataform/filters.php', array('d' => $this->id())));

                echo $OUTPUT->footer();
                exit;

            } else {
                // go ahead and perform the requested action
                switch ($action) {
                    case 'update':     // add new or update existing
                        $filter = reset($filters);
                        require_once($CFG->dirroot. '/mod/dataform/filter_form.php');
                        $mform = new mod_dataform_filter_form(null, array('df' => $this, 'filter' => $filter));

                        if ($mform->is_cancelled()){
                            // clean up  customsearch if needed
                            if ($filter->id and $filter->customsearch) {
                                $needupdate = false;
                                $searchfields = unserialize($filter->customsearch);
                                foreach ($searchfields as $fieldid => $searchfield) {
                                    if ($searchfield) { // there are some andor options
                                        foreach ($searchfield as $andorskey => $andors) {
                                            foreach ($andors as $optionkey => $option) {
                                                list(, , $value) = $option;
                                                if (!$value) {
                                                    $needupdate = true;
                                                    unset($andors[$optionkey]);
                                                }
                                            }
                                            // if all options removed, remove this andors
                                            if (!$andors) {
                                                unset($searchfield[$andorskey]);
                                            }
                                        }
                                        // if all andors removed, remove this searchfield
                                        if (!$searchfield) {
                                            unset($searchfields[$fieldid]);
                                        }
                                    } else {
                                        unset($searchfields[$fieldid]);
                                    }
                                }
                                if ($needupdate) {
                                    $updatefilter = new object();
                                    $updatefilter->id = $filter->id;
                                    if ($searchfields) {
                                        $updatefilter->customsearch = serialize($searchfields);
                                    } else {
                                        $updatefilter->customsearch = '';
                                    }
                                    $DB->update_record('dataform_filters', $updatefilter);
                                }
                            }

                        // process validated
                        } else if ($formdata = $mform->get_data()) {
                            $filter = $this->get_filter_from_form($formdata);
                            if ($filter->id) {
                                $DB->update_record('dataform_filters', $filter);
                                $processedfids[] = $filter->id;
                                $strnotify = 'filtersupdated';
                            } else {
                                $filter->id = $DB->insert_record('dataform_filters', $filter, true);
                                $processedfids[] = $filter->id;
                                $strnotify = 'filtersadded';
                            }
                            // return to form if need to add search criteria
                            if ($filter->returntoform) {
                                $this->display_filter_form($filter);
                            }
                        }

                        break;

                    case 'duplicate':
                        if (!empty($filters)) {
                            foreach ($filters as $filter) {
                                // TODO: check for limit
                                // set new name
                                while ($this->name_exists('filters', $filter->name)) {
                                    $filter->name = 'Copy of '. $filter->name;
                                }
                                $filterid = $DB->insert_record('dataform_filters', $filter);

                                $processedfids[] = $filterid;
                            }
                        }
                        $strnotify = 'filtersadded';
                        break;

                    case 'show':
                        $updatefilter = new object();
                        $updatefilter->visible = 1;
                        foreach ($filters as $filter) {
                            $updatefilter->id = $filter->id;
                            $DB->update_record('dataform_filters', $updatefilter);

                            $processedfids[] = $filter->id;
                        }

                        $strnotify = 'filtersupdated';
                        break;

                    case 'hide':
                        $updatefilter = new object();
                        $updatefilter->visible = 0;
                        foreach ($filters as $filter) {
                            $updatefilter->id = $filter->id;
                            $DB->update_record('dataform_filters', $updatefilter);

                            $processedfids[] = $filter->id;
                        }

                        $strnotify = 'filtersupdated';
                        break;

                    case 'delete':
                        foreach ($filters as $filter) {
                            $DB->delete_records('dataform_filters', array('id' => $filter->id));
                            $processedfids[] = $filter->id;
                        }
                        $strnotify = 'filtersdeleted';
                        break;

                    default:
                        break;
                }

                add_to_log($this->course->id, 'dataform', 'filter '. $action, 'filters.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if (!empty($strnotify)) {
                    $filtersprocessed = $processedfids ? count($processedfids) : 'No';
                    $this->notifications['good'][] = get_string($strnotify, 'dataform', $filtersprocessed);
                }
                return $processedfids;
            }
        }
    }

    /**
     *
     */
    public function get_filters($exclude = null, $menu = false, $forceget = false) {
        global $DB;

        if (!$this->filters or $forceget) {
            if (!$this->filters = $DB->get_records('dataform_filters', array('dataid' => $this->id()))) {
                $this->filters = array();
             }
        }

        if ($this->filters) {
            if (empty($exclude) and !$menu) {
                return $this->filters;
            } else {
                $filters = array();
                foreach ($this->filters as $filterid => $filter) {
                    if (!empty($exclude) and in_array($filterid, $exclude)) {
                        continue;
                    }
                    if ($menu) {
                        if ($filter->visible or has_capability('mod/dataform:managetemplates', $this->context)) {
                            $filters[$filterid] = $filter->name;
                        }
                    } else {
                        $filters[$filterid]= $filter;
                    }
                }
                return $filters;
            }
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function display_filter_form($filter) {
        global $CFG, $OUTPUT;

        require_once($CFG->dirroot. '/mod/dataform/filter_form.php');

        $mform = new mod_dataform_filter_form(null, array('df' => $this, 'filter' => $filter));

        //$mform->data_preprocessing($filter);
        $mform->set_data($filter);

        $this->print_header('filters', array('tab' => 'filters'));
        $mform->display();
        echo $OUTPUT->footer();

        exit;
    }

    /**
     *
     */
    function get_sort_options_from_form($formdata) {
        $sortfields = array();
        $i = 0;
        while (isset($formdata->{"sortfield$i"})) {
            if ($sortfieldid = $formdata->{"sortfield$i"}) {
                $sortfields[$sortfieldid] = $formdata->{"sortdir$i"};
            }
            $i++;
        }
        // TODO should we add the groupby field to the customsort now?
        if ($sortfields) {
            return serialize($sortfields);
        } else {
            return '';
        }
    }

    /**
     *
     */
    function get_search_options_from_form($formdata, &$returntoform) {
        if ($fields = $this->get_fields()) {
            $searchfields = array();
            $i = 0;
            while (isset($formdata->{"searchandor$i"})) {
                // check if trying to define a search criterion
                if ($searchandor = $formdata->{"searchandor$i"}) {
                    if ($searchfieldid = $formdata->{"searchfield$i"}) {
                        if (!isset($searchfields[$searchfieldid])) {
                            $searchfields[$searchfieldid] = array();
                        }
                        if (!isset($searchfields[$searchfieldid][$searchandor])) {
                            $searchfields[$searchfieldid][$searchandor] = array();
                        }
                        $parsedvalue = $fields[$searchfieldid]->parse_search($formdata, $i);
                        if ($parsedvalue === false) {
                            $returntoform = true; // the search criteria fields need to be added
                        }

                        $not = isset($formdata->{"searchnot$i"}) ? 'NOT' : '';
                        $operator = isset($formdata->{"searchoperator$i"}) ? $formdata->{"searchoperator$i"} : '';
                        $searchvalue = array($not, $operator, $parsedvalue);

                        $searchfields[$searchfieldid][$searchandor][] = $searchvalue;
                    }
                }
                $i++;
            }
        }

        if ($searchfields) {
            return serialize($searchfields);
        } else {
            return '';
        }
    }

/**********************************************************************************
 * USER
 *********************************************************************************/

    /**
     *
     */
    public function get_gradebook_users() {
        global $CFG;

        // get the list of users by gradebook roles
        if (!empty($CFG->gradebookroles)) {
            $gradebookroles = explode(",", $CFG->gradebookroles);

        } else {
            $gradebookroles = '';
        }

        $users = get_role_users($gradebookroles, $this->context, true, '', 'u.lastname ASC', true, $this->currentgroup);
        if ($users) {
            $users = array_keys($users);
            if (!empty($CFG->enablegroupings) and $this->cm->groupmembersonly) {
                $groupingusers = groups_get_grouping_members($this->cm->groupingid, 'u.id', 'u.id');
                if ($groupingusers) {
                    $users = array_intersect($users, array_keys($groupingusers));
                }
            }
        }
        return $users;
    }

    /**
     * has a user reached the max number of entries?
     * if interval is set then required entries, max entrie etc. are relative to the current interval
     * @return boolean
     */
    public function user_at_max_entries($perinterval = false) {
        if ($this->data->maxentries < 0 or has_capability('mod/dataform:manageentries', $this->context)) {
            return false;
        } else if (!$this->data->maxentries) {
            return true;
        } else {
            return ($this->user_num_entries($perinterval) >= $this->data->maxentries);
        }
    }

    /**
     *
     * output bool
     */
    public function user_can_view_all_entries($options = null) {
        global $OUTPUT;
        if (has_capability('mod/dataform:manageentries', $this->context)) {
            return true;
        } else {
            // Check the number of entries required against the number of entries already made
            $numentries = $this->user_num_entries();
            if ($this->data->entriesrequired and $numentries < $this->data->entriesrequired) {
                $entriesleft = $this->data->entriesrequired - $numentries;
                if (!empty($options['notify'])) {
                    echo $OUTPUT->notification(get_string('entrieslefttoadd', 'dataform', $entriesleft));
                }
            }

            // check separate participants group
            if ($this->groupmode == $this->internalgroupmodes['separateparticipants']) {
                return false;
            } else {
                // Check the number of entries required before to view other participant's entries against the number of entries already made (doesn't apply to teachers)
                if ($this->data->entriestoview and $numentries < $this->data->entriestoview) {
                    $entrieslefttoview = $this->data->entriestoview - $numentries;
                    if (!empty($options['notify'])) {
                        echo $OUTPUT->notification(get_string('entrieslefttoaddtoview', 'dataform', $entrieslefttoview));
                    }
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

    /**
     *
     */
    public function user_can_manage_entry($entry = 0) {
        global $USER;

        // teachers can always manage entries
        if (has_capability('mod/dataform:manageentries',$this->context)) {
            return true;
        // for others, it depends ...
        } else if (has_capability('mod/dataform:writeentry', $this->context)) {
            $timeavailable = $this->data->timeavailable;
            $timedue = $this->data->timedue;
            $allowlate = $this->data->allowlate;
            $now = time();

            // activity time frame
            if ($timeavailable and !($now >= $timeavailable)
                    or ($timedue and (!($now < $timedue) or !$allowlate))) {
                return false;
            }

            // group access
            if ($this->groupmode
                        and !in_array($this->groupmode, $this->internalgroupmodes)
                        and !has_capability('moodle/site:accessallgroups', $this->context)
                        and (   ($this->currentgroup and !groups_is_member($this->currentgroup))
                            or (!$this->currentgroup and $this->groupmode == VISIBLEGROUPS))) {
                return false;   // for members only
            }

            // managing a certain entry
            if ($entry) {
                // entry owner
                // TODO groups_is_member queries DB for each entry!
                if (empty($USER->id)
                            or (!$this->data->grouped and $USER->id != $entry->userid)
                            or ($this->data->grouped and !groups_is_member($entry->groupid))) {
                    return false;   // who are you anyway???
                }

                // ok owner, what's the time (limit)?
                if ($timelimitsec = ($this->data->timelimit * 60)) {
                    $elapsed = $now - $entry->timecreated;
                    if ($elapsed > $timelimitsec) {
                        return false;    // too late ...
                    }
                }

                // phew, within time limit, but wait, are we still in the same interval?
                if ($timeinterval = $this->data->timeinterval) {
                    $elapsed = $now - $timeavailable;
                    $currentintervalstarted = (floor($elapsed / $timeinterval) * $timeinterval) + $timeavailable;
                    if ($entry->timecreated < $currentintervalstarted) {
                        return false;  // nop ...
                    }
                }

                // same interval but the entrie may be locked ...
                if ($locks = $this->data->locks) {
                    if (($locks & $this->locks['approval']) and $entry->approved) {
                        return false;
                    }
                    if (($locks & $this->locks['comments']) and $DB->count_records('dataform_comments', 'entryid', $entry->id)) {
                        return false;
                    }
                    if (($locks & $this->locks['ratings']) and $DB->count_records('dataform_ratings', 'entryid', $entry->id)) {
                        return false;
                    }
                }

            // trying to add an entry
            } else if ($this->user_at_max_entries(true)) {
                return false;    // no more entries for you (come back next interval or so)
            }

            // if you got this far you probably deserve to do something ... go ahead
            return true;
        }

        return false;
    }

    /**
     * returns the number of entries already made by this user; defaults to all entries
     * @param global $CFG, $USER
     * @param boolean $perinterval
     * output int
     */
    protected function user_num_entries($perinterval = false) {
        global $USER, $CFG, $DB;

        $params = array();
        $params['dataid'] = $this->id();
        $params['grading'] = 0;

        $and_whereuserorgroup = '';
        $and_whereinterval = '';
        
        // go by user
        if (!$this->data->grouped) {
            $and_whereuserorgroup = " AND userid = :userid ";
            $params['userid'] = $USER->id;            
        // go by group
        } else {
            $and_whereuserorgroup = " AND groupid = :groupid ";
            // if user is trying add an entry and got this far
            //  the user should belong to the current group
            $params['groupid'] = $this->currentgroup;                    
        }
        
        // time interval
        if ($timeinterval = $this->data->timeinterval and $perinterval) {
            $timeavailable = $this->data->timeavailable;
            $elapsed = time() - $timeavailable;
            $intervalstarttime = (floor($elapsed / $timeinterval) * $timeinterval) + $timeavailable;
            $intervalendtime = $intervalstarttime + $timeinterval;
            $and_whereinterval = " AND timecreated >= :starttime AND timecreated < :endtime ";
            $params['starttime'] = $intervalstarttime;
            $params['endtime'] = $intervalstarttime;

        }

        $sql = "SELECT COUNT(*)
                FROM {dataform_entries}
                WHERE dataid = :dataid AND grading = :grading $and_whereuserorgroup $and_whereinterval";
        return $DB->count_records_sql($sql, $params);
    }


/**********************************************************************************
 * PACKAGES
 *********************************************************************************/

    /**
     * Returns an array of the course local packages from the course files
     */
    public function get_plugin_packages() {
        global $CFG;
        $packages = array();

        $packagespath = 'mod/dataform/package/';
        if ($handle = opendir("$CFG->dirroot/$packagespath")) {
            while (false !== ($packagefile = readdir($handle))) {
                if ($packagefile != "." && $packagefile != "..") {
                    $package = new object;
                    $package->userid = 0;
                    $package->name = str_replace('.mbz', '', $packagefile);
                    $package->shortname = $package->name;
                    $packages[] = $package;
                }
            }
            closedir($handle);
        }

        return $packages;
    }

    /**
     * Returns an array of the shared packages (in moodledata) the user is allowed to access
     * @param in $packagearea  PACKAGE_COURSEAREA/PACKAGE_SITEAREA
     */
    public function get_user_packages($packagearea) {
        global $USER;

        $packages = array();

        $fs = get_file_storage();
        if ($packagearea == 'course_packages') {
            $files = $fs->get_area_files($this->context->id, 'mod_dataform', $packagearea);
        } else if ($packagearea == 'site_packages') {
            $files = $fs->get_area_files(dataform::PACKAGE_SITECONTEXT, 'mod_dataform', $packagearea);
        }
        $canviewall = has_capability('mod/dataform:packagesviewall', $this->context);
        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file->is_directory() || ($file->get_userid() != $USER->id and !$canviewall)) {
                    continue;
                }
                $package = new object;
                $package->contextid = $file->get_contextid();
                $package->path = $file->get_filepath();
                $package->name = $file->get_filename();
                $package->shortname = basename($package->name);
                $package->userid = $file->get_userid();
                $package->itemid = $file->get_itemid();
                $package->id = $file->get_id();
                $packages[] = $package;
            }
        }

        return $packages;
    }

    /**
     *
     */
    public function apply_package($packageid, $fieldmapping = false) {
        global $CFG;

        require_once($CFG->libdir.'/uploadlib.php');
        require_once($CFG->libdir.'/xmlize.php');
        require_once('restorelib.php');

        // make user draft area

        // unzip the package to the draft area

        // get content of package file and delete draft files area

        // try to apply the package
        if ($parsedxml = xmlize($packagexml)) {
            // get current user fields
            $currentfields = $this->get_fields();
            // delete records of current fields views and filters
            $DB->delete_records('dataform_fields', array('dataid' => $this->id()));
            $DB->delete_records('dataform_views', array('dataid' => $this->id()));
            $DB->delete_records('dataform_filters', array('dataid' => $this->id()));

            // restore package from array
            $params = new object();
            $params->courseid = $this->course->id;
            $params->destdataformid = $this->id();
            restore_dataform_package($parsedxml, $params);

            // at this stage new fields, views, filters should be created
            // old fields if any and there content should still exit for mapping

            if (!empty($currentfields)) {
                // get new fields
                $newfields = $this->get_fields(null, false, true);
                // mapping preferences form
                if ($fieldmapping and !empty($newfields)) {
                    $strblank = get_string('blank', 'dataform');
                    $strcontinue = get_string('continue');
                    $strwarning = get_string('mappingwarning', 'dataform');
                    $strfieldmappings = get_string('fieldmappings', 'dataform');
                    $strnew = get_string('new');

                    echo '<div style="text-align:center"><form action="packages.php?d='. $this->id(). '" method="post">';
                    echo '<fieldset class="invisiblefieldset">';
                    echo '<input type="hidden" name="map" value="1" />';
                    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

                    echo "<h3>$strfieldmappings ";
                    //echo helpbutton('fieldmappings', '', 'dataform');
                    echo '</h3><table cellpadding="5">';

                    foreach ($currentfields as $cid => $currentfield) {
                        if ($cid > 0) {
                            echo '<tr><td><label for="id_'.$currentfield->name().'">'.$currentfield->name().'</label></td>';
                            echo '<td><select name="field_'.$cid.'_'.$currentfield->type().'" id="id_'.$currentfield->name().'">';

                            $selected = false;
                            foreach ($newfields as $nid => $newfield) {
                                if ($nid > 0) {
                                    if ($newfield->type() == $currentfield->type()) {
                                        if ($newfield->name() == $currentfield->name()) {
                                            echo '<option value="'.$nid.'" selected="selected">'.$newfield->name().'</option>';
                                            $selected=true;
                                        }
                                        else {
                                            echo '<option value="'.$nid.'">'.$newfield->name().'</option>';
                                        }
                                    }
                                }
                            }

                            if ($selected)
                                echo '<option value="-1">-</option>';
                            else
                                echo '<option value="-1" selected="selected">-</option>';
                            echo '</select></td></tr>';
                        }
                    }
                    echo '</table>';
                    echo "<p>$strwarning</p>";

                    echo '<input type="submit" value="'.$strcontinue.'" /></fieldset></form></div>';
                } else {
                    foreach ($currentfields as $field) {
                        $field->delete_content();
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function create_package_from_backup() {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'backup', 'activity');
        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                $package = new object;
                $package->contextid = $this->context->id;
                $package->component = 'mod_dataform';
                $package->filearea = dataform::PACKAGE_COURSEAREA;
                $package->filepath = '/';
                $package->filename = clean_filename(str_replace(' ', '_', $this->data->name). '-dataform-package-'. gmdate("Ymd_Hi"));

                $fs->create_file_from_storedfile($package, $file);
                return true;
            }
        }
        return false;
    }

    /**
     *
     */
    public function share_packages($packageid) {
        global $CFG, $USER;

        if (!has_capability('mod/dataform:packagesviewall', $this->context)) {
            return false;
        }
                    
        $fs = get_file_storage();

        $filerecord = new object;
        $filerecord->contextid = dataform::PACKAGE_SITECONTEXT;
        $filerecord->component = 'mod_dataform';
        $filerecord->filearea = dataform::PACKAGE_SITEAREA;
        $filerecord->filepath = '/';

        $fs->create_file_from_storedfile($filerecord, $packageid);
        return true;
    }

    /**
     *
     */
    public function plug_in_packages($idorpath, $delete = false) {
        global $CFG, $USER;

        if (!has_capability('mod/dataform:managepackages', $this->context)) {
            return false;
        }
                    
        if ($delete) {
            return unlink("$CFG->dirroot/mod/dataform/package/{$idorpath}");

        } else {
            $fs = get_file_storage();
            $file = $fs->get_file_by_id($idorpath);
            $filename = $file->get_filename();
            return $file->copy_content_to("$CFG->dirroot/mod/dataform/package/{$filename}");
        }    
    }

    /**
     *
     */
    public function delete_packages($packagearea = dataform::PACKAGE_COURSEAREA, $packageid) {
        if (!has_capability('mod/dataform:managepackages', $this->context)) {
            return false;
        }
                    
        if ($packagearea == dataform::PACKAGE_COURSEAREA) {
            $contextid = $this->context->id;
        } else {
            $contextid = dataform::PACKAGE_SITECONTEXT;
        }
        
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'mod_dataform', $packagearea);
        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                if ($file->get_id() == $packageid) {
                    $file->delete();
                    break;
                }
            }
        }
        return true;        
    }

/**********************************************************************************
 * UTILITY
 *********************************************************************************/

    /**
     *
     */
    public function name_exists($table, $name, $id=0) {
        global $DB;

        $params = array();
        $params['name'] = $name;
        $params['dataid'] = $this->id();
        $params['id'] = $id;
        
        $where = " dataid = :dataid AND name = :name AND id <> :id ";
        return $DB->record_exists_select("dataform_{$table}", $where, $params);
    }

    /**
     * // TODO
     */
    public function settings_navigation() {
    }

    /**
     *
     */
    public function convert_arrays_to_strings(&$fieldinput) {
        foreach ($fieldinput as $key => $val) {
            if (is_array($val)) {
                $str = '';
                foreach ($val as $inner) {
                    $str .= $inner . ',';
                }
                $str = substr($str, 0, -1);
                $fieldinput->$key = $str;
            }
        }
    }
    
    /**
     * 
     */
    public function add_to_log($action) {
        add_to_log($this->course->id, 'dataform', 'entry '. $action, 'view.php?id='. $this->cm->id, $this->id(), $this->cm->id);
    }
    
    

}
