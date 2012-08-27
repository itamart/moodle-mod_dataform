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
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

require_once('filter/filter_class.php');

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
    const _RATINGAVG = -141;
    const _RATINGCOUNT = -142;
    const _RATINGMAX = -143;
    const _RATINGMIN = -144;
    const _RATINGSUM = -145;

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

    protected $pagefile = 'view';
    protected $fields = array();
    protected $views = array();
    protected $filtermanager = null;
    protected $rules = array();
    protected $_currentview = null;

    // internal fields
    protected $internalfields = array();

    // internal group modes
    protected $internalgroupmodes = array(
            'separateparticipants' => -1
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
                throw new moodle_exception('invaliddataform', 'dataform', null, null, "Dataform id: $d");
            }
            if (!$this->course = $DB->get_record('course', array('id' => $this->data->course))) {
                throw new moodle_exception('invaliddataform', 'dataform', null, null, "Course id: {$this->data->course}");
            }
            if (!$this->cm = get_coursemodule_from_instance('dataform', $this->id(), $this->course->id)) {
                throw new moodle_exception('invaliddataform', 'dataform', null, null, "Cm id: {$this->id()}");
            }
        // initialize from course module id
        } else if ($id) {
            if (!$this->cm = get_coursemodule_from_id('dataform', $id)) {
                throw new moodle_exception('invaliddataform', 'dataform', null, null, "Cm id: $id");
            }
            if (!$this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
                throw new moodle_exception('invaliddataform', 'dataform', null, null, "Course id: {$this->cm->course}");
            }
            if (!$this->data = $DB->get_record('dataform', array('id' => $this->cm->instance))) {
                throw new moodle_exception('invaliddataform', 'dataform', null, null, "Dataform id: {$this->cm->instance}");
            }
        }

        // get context
        $this->context = context_module::instance($this->cm->id);

        // set groups
        if ($this->cm->groupmode and in_array($this->cm->groupmode, $this->internalgroupmodes)) {
            $this->groupmode = $this->cm->groupmode;
        } else {
            $this->groupmode = groups_get_activity_groupmode($this->cm);
            $this->currentgroup = groups_get_activity_group($this->cm, true);
        }
        
        // set fields manager
        
        // set views manager
        
        // set filters manager
        $this->_filtermanager = new dataform_filter_manager($this);
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
    public function pagefile() {
        return $this->pagefile;
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
    public function get_filter_manager() {
        return $this->_filtermanager;
    }

    /**
     *
     */
    public function get_entriescount($type, $user = 0) {
        global $DB;
        
        switch ($type) {
            case self::COUNT_ALL:
                $count = $DB->count_records_sql('SELECT COUNT(e.id) FROM {dataform_entries} e WHERE e.dataid = ?', array($this->id()));
                break;
        
            case self::COUNT_APPROVED:
                $count = '---';
                 break;
        
            case self::COUNT_UNAPPROVED:
                $count = '---';
                break;
        
            case self::COUNT_LEFT:
                $count = '---';
                break;
                
            default: 
                $count = '---';
        
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
     * TODO complete cleanup
     */
    protected function renew() {
        global $DB;

        // files
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'mod_dataform');

        // delete fields and their content
        if ($fields = $this->get_fields()) {
            foreach ($fields as $field) {
                $field->delete_field();
            }
            // reset this fields
            $this->get_fields(null, false, true);
        }
            
        // delete views
        if ($views = $this->get_views()) {
            foreach ($views as $view) {
                $view->delete();
            }
            $this->get_views(null, false, true);
        }

        // delete filters
        $DB->delete_records('dataform_filters', array('dataid'=>$this->data->id));
        
        // delete entries
        $DB->delete_records('dataform_entries', array('dataid'=>$this->data->id));

        // delete ratings
        
        // delete comments

        // cleanup gradebook
        dataform_grade_item_delete($this->data);


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

        $this->pagefile = $page;
        $thisid = $this->id();
        
        // guest auto login
        $autologinguest = false;
        if ($page == 'view' or $page == 'embed') {
            $autologinguest = true;
        }
        
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
        $urlparams['d'] = $thisid;

        $manager = has_capability('mod/dataform:managetemplates', $this->context);

        // renew if requested
        if ($manager and !empty($urlparams['renew']) and confirm_sesskey()) {
            $this->renew();
        }

        // if dataform activity closed don't let students in
        if (!$manager) {
            $timenow = time();
            if (!empty($this->data->timeavailable) and $this->data->timeavailable > $timenow) {
                throw new moodle_exception('notopenyet', 'dataform', '', userdate($this->data->timeavailable));
            }
        }

        // Is user editing
        $urlparams['edit'] = optional_param('edit', 0, PARAM_BOOL);
        $PAGE->set_url("/mod/dataform/$page.php", $urlparams);

        // RSS
        if (!empty($params->rss) and
                !empty($CFG->enablerssfeeds) and
                !empty($CFG->dataform_enablerssfeeds) and
                $this->data->rssarticles > 0) {
            require_once("$CFG->libdir/rsslib.php");
            $rsstitle = format_string($this->course->shortname) . ': %fullname%';
            rss_add_http_header($this->context, 'mod_dataform', $this->data, $rsstitle);
        }
        
        $fs = get_file_storage();
        
        // CSS
        if (!empty($params->css)) {
            // js includes from the js template
            if ($this->data->cssincludes) {
                foreach (explode("\n", $this->data->cssincludes) as $cssinclude) {
                    $cssinclude = trim($cssinclude);
                    if ($cssinclude) {
                        $PAGE->requires->css(new moodle_url($cssinclude));
                    }
                }
            }
            // Uploaded css files
            if ($files = $fs->get_area_files($this->context->id, 'mod_dataform', 'css', 0, 'sortorder', false)) {
                $path = "/pluginfile.php/{$this->context->id}/mod_dataform/css/0";
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $PAGE->requires->css("$path/$filename");
                }
            }                
            // css code from the css template
            if ($this->data->css) {
                $PAGE->requires->css("/mod/dataform/css.php?d=$thisid");
            }
        }
        
        // JS
        if (!empty($params->js)) {
            // js includes from the js template
            if ($this->data->jsincludes) {
                foreach (explode("\n", $this->data->jsincludes) as $jsinclude) {
                    $jsinclude = trim($jsinclude);
                    if ($jsinclude) {
                        $PAGE->requires->js(new moodle_url($jsinclude));
                    }
                }
            }
            // Uploaded js files
            if ($files = $fs->get_area_files($this->context->id, 'mod_dataform', 'js', 0, 'sortorder', false)) {
                $path = "/pluginfile.php/{$this->context->id}/mod_dataform/js/0";
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $PAGE->requires->js("$path/$filename");
                }
            }                
            // js code from the js template
            if ($this->data->js) {
                $PAGE->requires->js("/mod/dataform/js.php?d=$thisid");
            }
        }
        
        // MOD JS
        if (!empty($params->modjs)) {
            $PAGE->requires->js('/mod/dataform/dataform.js');
        }
        
        // COMMENTS
        if (!empty($params->comments)) {
            require_once("$CFG->dirroot/comment/lib.php");
            comment::init();
        }

        // editing button (omit in embedded dataforms)
        if ($page != 'embed' and $PAGE->user_allowed_editing()) {
             // teacher editing mode
            if ($urlparams['edit'] != -1) {
                $USER->editing = $urlparams['edit'];
            }

            $buttons = '<table><tr><td><form method="get" action="'. $PAGE->url. '"><div>'.
                '<input type="hidden" name="d" value="'.$thisid.'" />'.
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
        if (!empty($params->pagelayout)) {
            $PAGE->set_pagelayout($params->pagelayout);
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
            $views = $this->get_views();
            if (!$views) {
                $this->notifications['bad']['getstarted'] = get_string('getstarted','dataform');
                $linktopackages = html_writer::link(new moodle_url('packages.php', array('d' => $thisid)), get_string('packages', 'dataform'));
                $this->notifications['bad']['getstartedpackages'] = get_string('getstartedpackages','dataform', $linktopackages);
                $linktofields = html_writer::link(new moodle_url('fields.php', array('d' => $thisid)), get_string('fields', 'dataform'));
                $this->notifications['bad']['getstartedfields'] = get_string('getstartedfields','dataform', $linktofields);
                $linktoviews = html_writer::link(new moodle_url('views.php', array('d' => $thisid)), get_string('views', 'dataform'));
                $this->notifications['bad']['getstartedviews'] = get_string('getstartedviews','dataform', $linktoviews);
            } else if (!$this->data->defaultview) {
                $linktoviews = html_writer::link(new moodle_url('views.php', array('d' => $thisid)), get_string('views', 'dataform'));
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

        // print intro
        if (!empty($params->heading)) {
            echo $OUTPUT->heading(format_string($this->name()));
        }

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
            $returnurl = new moodle_url("/mod/dataform/{$this->pagefile}.php", 
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
    public function set_content() {
        if (!empty($this->_currentview)) {
            $this->_currentview->process_data();
            $this->_currentview->set_content();
        }
    }

    /**
     * 
     */
    public function display() {
        if (!empty($this->_currentview)) {
            add_to_log($this->course->id, 'dataform', 'view', $this->pagefile. '.php?id='. $this->cm->id, $this->id(), $this->cm->id);
            $this->_currentview->display();
        }
    }

/**********************************************************************************
 * FIELDS
 *********************************************************************************/

    /**
     * initialize the internal fields
     */
    protected function get_internal_fields() {
        if (!$this->internalfields) {
            $dataid = $this->data->id;
            
            $field = (object) array('id' => self::_ENTRY, 'dataid' => $dataid, 'type' => '_entry', 'name' => get_string('entry', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => '');
            $this->internalfields[self::_ENTRY] = $this->get_field($field);
            
            $field = (object) array('id' => self::_TIMECREATED, 'dataid' => $dataid, 'type' => '_time', 'name' => get_string('timecreated', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'timecreated');
            $this->internalfields[self::_TIMECREATED] = $this->get_field($field);

            $field = (object) array('id' => self::_TIMEMODIFIED, 'dataid' => $dataid, 'type' => '_time', 'name' => get_string('timemodified', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'timemodified');
            $this->internalfields[self::_TIMEMODIFIED] = $this->get_field($field);

            $field = (object) array('id' => self::_APPROVED, 'dataid' => $dataid, 'type' => '_approve', 'name' => get_string('approved', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'approved');
            $this->internalfields[self::_APPROVED] = $this->get_field($field);

            $field = (object) array('id' => self::_GROUP, 'dataid' => $dataid, 'type' => '_group', 'name' => get_string('group', 'dataformfield__group'), 'description' => '', 'visible' => 2, 'internalname' => 'groupid');
            $this->internalfields[self::_GROUP] = $this->get_field($field);

            $field = (object) array('id' => self::_USERID, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userid', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'id');
            $this->internalfields[self::_USERID] = $this->get_field($field);

            $field = (object) array('id' => self::_USERNAME, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('username', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'name');
            $this->internalfields[self::_USERNAME] = $this->get_field($field);

            $field = (object) array('id' => self::_USERFIRSTNAME, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userfirstname', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'firstname');
            $this->internalfields[self::_USERFIRSTNAME] = $this->get_field($field);

            $field = (object) array('id' => self::_USERLASTNAME, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userlastname', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'lastname');
            $this->internalfields[self::_USERLASTNAME] = $this->get_field($field);

            $field = (object) array('id' => self::_USERUSERNAME, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userusername', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'username');
            $this->internalfields[self::_USERUSERNAME] = $this->get_field($field);

            $field = (object) array('id' => self::_USERIDNUMBER, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('useridnumber', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'idnumber');
            $this->internalfields[self::_USERIDNUMBER] = $this->get_field($field);

            $field = (object) array('id' => self::_USERPICTURE, 'dataid' => $dataid, 'type' => '_user', 'name' => get_string('userpicture', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'picture');
            $this->internalfields[self::_USERPICTURE] = $this->get_field($field);

            $field = (object) array('id' => self::_COMMENT, 'dataid' => $dataid, 'type' => '_comment', 'name' => get_string('comments', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'comments');
            $this->internalfields[self::_COMMENT] = $this->get_field($field);

            $field = (object) array('id' => self::_RATING, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratings', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'ratings');
            $this->internalfields[self::_RATING] = $this->get_field($field);

            $field = (object) array('id' => self::_RATINGAVG, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingsavg', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'avgratings');
            $this->internalfields[self::_RATINGAVG] = $this->get_field($field);

            $field = (object) array('id' => self::_RATINGCOUNT, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingscount', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'countratings');
            $this->internalfields[self::_RATINGCOUNT] = $this->get_field($field);

            $field = (object) array('id' => self::_RATINGMAX, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingsmax', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'maxratings');
            $this->internalfields[self::_RATINGMAX] = $this->get_field($field);

            $field = (object) array('id' => self::_RATINGMIN, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingsmin', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'minratings');
            $this->internalfields[self::_RATINGMIN] = $this->get_field($field);

            $field = (object) array('id' => self::_RATINGSUM, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingssum', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'sumratings');
            $this->internalfields[self::_RATINGSUM] = $this->get_field($field);
        }
        return $this->internalfields;
    }

    /**
     *
     */
    public function get_user_defined_fields($forceget = false) {
        $this->get_fields(null, false, $forceget);
        return $this->fields;
    }

    /**
     * given a field id return the field object from get_fields
     * Initializes get_fields if necessary
     */
    public function get_field_from_id($fieldid, $forceget = false) {
        $fields = $this->get_fields(null, false, $forceget);
        
        if (empty($fields[$fieldid])) {;
            return false;
        } else {
            return $fields[$fieldid];
        }
    }

    /**
     * given a field type returns the field object from get_fields
     * Initializes get_fields if necessary
     */
    public function get_fields_by_type($type, $menu = false) {
        $typefields = array();
        foreach  ($this->get_fields() as $fieldid => $field) {
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

    /**
     * given a field name returns the field object from get_fields
     */
    public function get_field_by_name($name) {
        foreach ($this->get_fields() as $field) {
            if ($field->name() === $name) {
                return $field;
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
            // collate user fields
            if ($fields = $DB->get_records('dataform_fields', array('dataid' => $this->id()))) {
                foreach ($fields as $fieldid => $field) {
                    $this->fields[$fieldid] = $this->get_field($field);
                }
            }
        }

        // collate all fields
        $fields = $this->fields + $this->get_internal_fields();

        if (empty($exclude) and !$menu) {
            return $fields;
        } else {
            $retfields = array();
            foreach ($fields as $fieldid => $field) {
                if (!empty($exclude) and in_array($fieldid, $exclude)) {
                    continue;
                }
                if ($menu) {
                    $retfields[$fieldid]= $field->name();
                } else {
                    $retfields[$fieldid]= $field;
                }
            }
            return $retfields;
        }
    }

    /**
     *
     */
    public function process_fields($action, $fids, $confirmed = false) {
        global $OUTPUT, $DB;

        if (!has_capability('mod/dataform:managetemplates', $this->context)) {
            // TODO throw exception
            return false;
        }

        $dffields = $this->get_fields();
        $fields = array();
        // collate the fields for processing
        if ($fieldids = explode(',', $fids)) {
            foreach ($fieldids as $fieldid) {
                if ($fieldid > 0 and isset($dffields[$fieldid])) {
                    $fields[$fieldid] = $dffields[$fieldid];
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

                    case 'visible':
                        foreach ($fields as $fid => $field) {
                            // hide = 0; (show to owner) = 1; show to everyone = 2
                            $visible = (($field->field->visible + 1) % 3);
                            $DB->set_field('dataform_fields', 'visible', $visible, array('id' => $fid));

                            $processedfids[] = $fid;
                        }

                        $strnotify = '';
                        break;

                    case 'editable':
                        foreach ($fields as $fid => $field) {
                            // lock = 0; unlock = -1;
                            $editable = $field->field->edits ? 0 : -1;
                            $DB->set_field('dataform_fields', 'edits', $editable, array('id' => $fid));

                            $processedfids[] = $fid;
                        }

                        $strnotify = '';
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
    public function get_views_by_type($type, $menu = false, $forceget = false) {
        if (!$views = $this->get_views(null, false, $forceget)) {;
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
            throw new moodle_exception('Failed to update the database');
        }
        $this->data->defaultview = $viewid;
    }

    /**
     *
     */
    public function set_default_filter($filterid = 0) {
        global $DB;

        $rec = new object();
        $rec->id = $this->id();
        $rec->defaultfilter = $filterid;
        if (!$DB->update_record('dataform', $rec)) {
            throw new moodle_exception('Failed to update the database');
        }
        $this->data->defaultfilter = $filterid;
    }

    /**
     *
     */
    public function set_single_edit_view($viewid = 0) {
        global $DB;

        $rec = new object();
        $rec->id = $this->id();
        $rec->singleedit = $viewid;
        if (!$DB->update_record('dataform', $rec)) {
            throw new moodle_exception('Failed to update the database');
        }
        $this->data->singleedit = $viewid;
    }

    /**
     *
     */
    public function set_single_more_view($viewid = 0) {
        global $DB;

        $rec = new object();
        $rec->id = $this->id();
        $rec->singleview = $viewid;
        if (!$DB->update_record('dataform', $rec)) {
            throw new moodle_exception('Failed to update the database');
        }
        $this->data->singleview = $viewid;
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

        if (!has_capability('mod/dataform:managetemplates', $this->context)) {
            // TODO throw exception
            return false;
        }
        
        if ($vids) { // some views are specified for action
            $views = array();
            $viewobjs = $this->get_views();
            foreach (explode(',', $vids) as $vid) {
                if (!empty($viewobjs[$vid])) {
                    $views[$vid] = $viewobjs[$vid];
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
                        foreach ($views as $vid => $view) {
                            if ($vid == $this->data->defaultview) {
                                // TODO: notify something
                                continue;
                            } else {
                                $updateview->id = $vid;
                                $updateview->visible = (($view->view->visible + 1) % 3);  // hide = 0; (show) = 1; show = 2
                                $DB->update_record('dataform_views', $updateview);

                                $processedvids[] = $vid;
                            }
                        }

                        $strnotify = '';
                        break;

                    case 'filter':
                        $updateview = new object();
                        $filterid = optional_param('fid', 0, PARAM_INT);
                        foreach ($views as $vid => $view) {
                            if ($filterid != $view->view->filter) {
                                $updateview->id = $vid;
                                if ($filterid == -1) {
                                    $updateview->filter = 0;
                                } else {
                                    $updateview->filter = $filterid;
                                }
                                $DB->update_record('dataform_views', $updateview);
                                $processedvids[] = $vid;
                            }
                        }

                        $strnotify = 'viewsupdated';
                        break;

                    case 'reset':
                        foreach ($views as $vid => $view) {
                            // generate default view and update
                            $view->generate_default_view();                            

                            // update view
                            $view->update($view->view);
                            
                            $processedvids[] = $vid;
                        }

                        $strnotify = 'viewsupdated';
                        break;

                    case 'duplicate':
                        foreach ($views as $vid => $view) {
                            // TODO: check for limit

                            // set name
                            while ($this->name_exists('views', $view->name())) {
                                $view->view->name = 'Copy of '. $view->name();
                            }
                            // reset id
                            $view->view->id = 0;
                            
                            $viewid = $view->add($view->view);

                            $processedvids[] = $viewid;
                        }

                        $strnotify = 'viewsadded';
                        break;

                    case 'delete':
                        foreach ($views as $vid => $view) {
                            $view->delete();
                            $processedvids[] = $vid;

                            // reset default view if needed
                            if ($view->id() == $this->data->defaultview) {
                                $this->set_default_view();
                            }
                        }
                        $strnotify = 'viewsdeleted';
                        break;

                    case 'default':
                        foreach ($views as $vid => $view) { // there should be only one
                            if ($view->view->visible != 2) {
                                $updateview = new object();
                                $updateview->id = $vid;
                                $updateview->visible = 2;
                                $DB->update_record('dataform_views', $updateview);
                            }

                            $this->set_default_view($vid);
                            $processedvids[] = $vid;
                            break;
                        }
                        $strnotify = '';
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
 * RULES
 *********************************************************************************/


/**********************************************************************************
 * USER
 *********************************************************************************/

    /**
     *
     */
    public function get_gradebook_users(array $userids = null) {
        global $DB, $CFG;

        // get the list of users by gradebook roles
        if (!empty($CFG->gradebookroles)) {
            $gradebookroles = explode(",", $CFG->gradebookroles);

        } else {
            $gradebookroles = '';
        }

        if (!empty($CFG->enablegroupings) and $this->cm->groupmembersonly) {
            $groupingsusers = groups_get_grouping_members($this->cm->groupingid, 'u.id', 'u.id');
            $gusers = $groupingsusers ? array_keys($groupingsusers) : null;
        }
        
        if (!empty($userids)) {
            if (!empty($gusers)) {
                $gusers = array_intersect($userids, $gusers);
            } else {
                $gusers = $userids;
            }
        }           
                    
        if (isset($gusers)) {
            if (!empty($gusers)) {
                list($inuids, $params) = $DB->get_in_or_equal($gusers);
                return get_role_users(
                    $gradebookroles,
                    $this->context,
                    true,
                    user_picture::fields('u'),
                    'u.lastname ASC', 
                    true,
                    $this->currentgroup,
                    '',
                    '',
                    "u.id $inuids",
                    $params
                );
            } else {
                return null;
            }
        } else {
            return get_role_users(
                $gradebookroles,
                $this->context,
                true,
                'u.id, u.lastname, u.firstname',
                'u.lastname ASC', 
                true,
                $this->currentgroup
            );
        }
    }

    /**
     * has a user reached the max number of entries?
     * if interval is set then required entries, max entrie etc. are relative to the current interval
     * @return boolean
     */
    public function user_at_max_entries($perinterval = false) {
        if ($this->data->maxentries < 0 or has_capability('mod/dataform:manageentries', $this->context)) {
            return false;
        } else if ($this->data->maxentries == 0) {
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
    public function user_can_export_entry($entry = null) {
        global $CFG, $USER;
        // we need portfolios for export
        if (!empty($CFG->enableportfolios)) {

            // can export all entries
            if (has_capability('mod/dataform:exportallentries', $this->context)) {
                return true;
            }
            
            // for others, it depends on the entry
            if (isset($entry->id) and $entry->id > 0) {
                if (has_capability('mod/dataform:exportownentry', $this->context)) {
                    if (!$this->data->grouped and $USER->id == $entry->userid) {
                        return true;
                    } else if ($this->data->grouped and groups_is_member($entry->groupid)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     *
     */
    public function user_can_manage_entry($entry = null) {
        global $USER, $CFG;

        // teachers can always manage entries
        if (has_capability('mod/dataform:manageentries',$this->context)) {
            return true;
        }

        // anonymous/guest can only add entries if enabled
        if ((!isloggedin() or isguestuser())
                    and empty($entry->id)
                    and $CFG->dataform_anonymous
                    and $this->data->anonymous) {
            return true;
        }
        
        // for others, it depends ...
        if (has_capability('mod/dataform:writeentry', $this->context)) {
            $timeavailable = $this->data->timeavailable;
            $timedue = $this->data->timedue;
            $allowlate = $this->data->allowlate;
            $now = time();

            // activity time frame
            if ($timeavailable and !($now >= $timeavailable)
                    or ($timedue and !($now < $timedue) and !$allowlate)) {
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
            if (!empty($entry->id)) {
                // entry owner
                // TODO groups_is_member queries DB for each entry!
                if (empty($USER->id)
                            or (!$this->data->grouped and $USER->id != $entry->userid)
                            or ($this->data->grouped and !groups_is_member($entry->groupid))) {
                    return false;   // who are you anyway???
                }

                // ok owner, what's the time (limit)?
                if ($this->data->timelimit != -1) {
                    $timelimitsec = ($this->data->timelimit * 60);
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
    public function user_num_entries($perinterval = false) {
        global $USER, $CFG, $DB;

        static $numentries = null;
        static $numentries_intervaled = null;

        if (!$perinterval and !is_null($numentries)) {
            return $numentries;
        }
        
        if ($perinterval and !is_null($numentries_intervaled)) {
            return $numentries_intervaled;
        }        

        $params = array();
        $params['dataid'] = $this->id();

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
            $params['endtime'] = $intervalendtime;

        }

        $sql = "SELECT COUNT(*)
                FROM {dataform_entries}
                WHERE dataid = :dataid $and_whereuserorgroup $and_whereinterval";
        $entriescount = $DB->count_records_sql($sql, $params);
        
        if (!$perinterval) {
            $numentries = $entriescount;
        } else {
            $numentries_intervaled = $entriescount;
        }        

        return $entriescount;
        
    }


/**********************************************************************************
 * PACKAGES
 *********************************************************************************/

    /**
     * Returns an array of the shared packages (in moodledata) the user is allowed to access
     * @param in $packagearea  PACKAGE_COURSEAREA/PACKAGE_SITEAREA
     */
    public function get_user_packages($packagearea) {
        global $USER;

        $packages = array();
        $course_context = context_course::instance($this->course->id);

        $fs = get_file_storage();
        if ($packagearea == 'course_packages') {
            $files = $fs->get_area_files($course_context->id, 'mod_dataform', $packagearea);
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
                $package->shortname = pathinfo($package->name, PATHINFO_FILENAME);
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
    public function print_packages_list($targetpage, $localpackages, $sharedpackages) {
        global $CFG, $OUTPUT;
        
        if ($localpackages or $sharedpackages) {

            $linkparams = array('d' => $this->id(), 'sesskey' => sesskey());
            $actionurl = htmlspecialchars_decode(new moodle_url($targetpage, $linkparams));
            
            // prepare to make file links
            require_once("$CFG->libdir/filelib.php");

            /// table headings
            $strname = get_string('name');
            $strdescription = get_string('description');
            $strscreenshot = get_string('screenshot');
            $strapply = get_string('packageapply', 'dataform');
            $strmap = get_string('packagemap', 'dataform');
            $strdownload = get_string('download', 'dataform');
            $strdelete = get_string('delete');
            $strshare = get_string('packageshare', 'dataform');

            $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'package\'&#44;this.checked)'));
            
            $multidownload = html_writer::tag('button', $OUTPUT->pix_icon('t/download', get_string('multidownload', 'dataform')), array('name' => 'multidownload', 'onclick' => 'bulk_action(\'package\'&#44; \''. $actionurl. '\'&#44; \'download\')'));
            
            $multidelete = html_writer::tag('button', $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), array('name' => 'multidelete', 'onclick' => 'bulk_action(\'package\'&#44; \''. $actionurl. '\'&#44; \'delete\')'));
            
            $multishare = html_writer::tag('button', $OUTPUT->pix_icon('i/group', get_string('multishare', 'dataform')), array('name' => 'multishare', 'onclick' => 'bulk_action(\'package\'&#44; \''. $actionurl. '\'&#44; \'share\')'));

            $table = new html_table();
            $table->head = array($strname, $strdescription, $strscreenshot, $strapply, $multidownload, $multishare, $multidelete, $selectallnone);
            $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
            $table->wrap = array(false, false, false, false, false, false, false, false);
            $table->attributes['align'] = 'center';

            // print local packages
            if ($localpackages) {
                // headingg
                $lpheadingcell = new html_table_cell();
                $lpheadingcell->text = html_writer::tag('h4', get_string('packageavailableincourse', 'dataform'));
                $lpheadingcell->colspan = 9;
                
                $lpheadingrow = new html_table_row();
                $lpheadingrow->cells[] = $lpheadingcell;

                $table->data[] = $lpheadingrow;

                foreach ($localpackages as $package) {

                    $packagename = $package->shortname;
                    $packagedescription = '';
                    $packagescreenshot = '';
                    //if ($package->screenshot) {
                    //    $packagescreenshot = '<img width="150" class="packagescreenshot" src="'. $package->screenshot. '" alt="'. get_string('screenshot'). '" />';
                    //}
                    $packageapply = html_writer::link(new moodle_url($targetpage, $linkparams + array('apply' => $package->id)),
                                    $OUTPUT->pix_icon('t/switch_whole', $strapply));
                    //$packageapplymap = html_writer::link(new moodle_url($targetpage, $linkparams + array('applymap' => $package->id)),
                    //                $OUTPUT->pix_icon('t/switch_plus', $strapply));
                    $packagedownload = html_writer::link(
                        moodle_url::make_file_url("/pluginfile.php", "/$package->contextid/mod_dataform/course_packages/$package->itemid/$package->name"),
                        $OUTPUT->pix_icon('t/download', $strdownload)
                    );
                    $packageshare = '';
                    if (has_capability('mod/dataform:packagesviewall', $this->context)) {
                        $packageshare = html_writer::link(new moodle_url($targetpage, $linkparams + array('share' => $package->id)),
                                    $OUTPUT->pix_icon('i/group', $strshare));
                    }
                    $packagedelete = html_writer::link(new moodle_url($targetpage, $linkparams + array('delete' => $package->id)),
                                    $OUTPUT->pix_icon('t/delete', $strdelete));
                    $packageselector = html_writer::checkbox("packageselector", $package->id, false);

                    $table->data[] = array(
                        $packagename,
                        $packagedescription,
                        $packagescreenshot,
                        $packageapply,
                        $packagedownload,
                        $packageshare,
                        $packagedelete,
                        $packageselector
                   );
                }
                
            }

            // print shared packages
            if ($sharedpackages) {
                // heading
                $lpheadingcell = new html_table_cell();
                $lpheadingcell->text = html_writer::tag('h4', get_string('packageavailableinsite', 'dataform'));
                $lpheadingcell->colspan = 9;
                
                $lpheadingrow = new html_table_row();
                $lpheadingrow->cells[] = $lpheadingcell;

                $table->data[] = $lpheadingrow;
                
                $linkparams['area'] = dataform::PACKAGE_SITEAREA;

                foreach ($sharedpackages as $package) {

                    $packagename = $package->shortname;
                    $packagedescription = '';
                    $packagescreenshot = '';
                    $packageapply = html_writer::link(new moodle_url($targetpage, $linkparams + array('apply' => $package->id)),
                                    $OUTPUT->pix_icon('t/switch_whole', $strapply));
                    //$packageapplymap = html_writer::link(new moodle_url($targetpage, $linkparams + array('applymap' => $package->id)),
                    //                $OUTPUT->pix_icon('t/switch_plus', $strapply));
                    $packagedownload = html_writer::link(
                        moodle_url::make_file_url("/pluginfile.php", "/$package->contextid/mod_dataform/site_packages/$package->itemid/$package->name"),
                        $OUTPUT->pix_icon('t/download', $strdownload)
                    );
                    $packageshare = '';
                    $packagedelete = '';
                    if (has_capability('mod/dataform:managepackages', $this->context)) {            
                        $packagedelete = html_writer::link(new moodle_url($targetpage, $linkparams + array('delete' => $package->id)),
                                        $OUTPUT->pix_icon('t/delete', $strdelete));
                    }                
                    $packageselector = html_writer::checkbox("packageselector", $package->id, false);

                    $table->data[] = array(
                        $packagename,
                        $packagedescription,
                        $packagescreenshot,
                        $packageapply,
                        $packagedownload,
                        $packageshare,
                        $packagedelete,
                        $packageselector
                   );
                }
            }
            
            echo html_writer::table($table);
            echo html_writer::empty_tag('br');           
        }
    }

    /**
     *
     */
    public function process_packages($targetpage, $params) {
        global $CFG;
        
        require_once('packages_form.php');

        $mform = new mod_dataform_packages_form(new moodle_url($targetpage, array('d' => $this->id(), 'sesskey' => sesskey(), 'add' => 1)));
        // add packages
        if ($data = $mform->get_data()) { 
            // package this dataform
            if ($data->package_source == 'current') {
                $this->create_package_from_backup($data->package_data);

            // upload packages
            } else if ($data->package_source == 'file') {
                $this->create_package_from_upload($data->uploadfile);
            }
        // apply a package
        } else if ($params->apply and confirm_sesskey()) {    // apply package
            $this->apply_package($params->apply);
            // rebuild course cache to show new dataform name on the course page
            rebuild_course_cache($this->course->id);
            
        // download (bulk in zip)
        } else if ($params->download and confirm_sesskey()) {
            $this->download_packages($params->download);

        // share packages
        } else if ($params->share and confirm_sesskey()) {  // share selected packages
            $this->share_packages($params->share);

        // delete packages
        } else if ($params->delete and confirm_sesskey()) { // delete selected packages
            $this->delete_packages($params->delete);
        }
    }

    /**
     *
     */
    public function create_package_from_backup($userdata) {
        global $CFG, $USER, $SESSION;
        
        require_once("$CFG->dirroot/backup/util/includes/backup_includes.php");
        
        $users = 0;
        $anon = 0;
        switch ($userdata) {
            case 'dataanon':
                $anon = 1;
            case 'data':
                $users = 1;
        }
        
        // store package settings in $SESSION
        $SESSION->{"dataform_{$this->cm->id}_package"} = "$users $anon";

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $this->cm->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);

        // clear package settings from $SESSION
        unset($SESSION->{"dataform_{$this->cm->id}_package"});

        // set users and anon in plan
        $bc->get_plan()->get_setting('users')->set_value($users);        
        $bc->get_plan()->get_setting('anonymize')->set_value($anon);
        $bc->set_status(backup::STATUS_AWAITING);

        $bc->execute_plan();
        $bc->destroy();
        
        $fs = get_file_storage();
        if ($users and !$anon) {
            $contextid = $this->context->id;
            $files = $fs->get_area_files($contextid, 'backup', 'activity', 0, 'timemodified', false);
        } else {
            $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
            $contextid = $usercontext->id;
            $files = $fs->get_area_files($contextid, 'user', 'backup', 0, 'timemodified', false);
        }
        if (!empty($files)) {
            $course_context = context_course::instance($this->course->id);
            foreach ($files as $file) {
                if ($file->get_contextid() != $contextid) {
                    continue;
                }
                $package = new object;
                $package->contextid = $course_context->id;
                $package->component = 'mod_dataform';
                $package->filearea = dataform::PACKAGE_COURSEAREA;
                $package->filepath = '/';
                $package->filename = clean_filename(str_replace(' ', '_', $this->data->name).
                                    '-dataform-package-'.
                                    gmdate("Ymd_Hi"). '-'.
                                    str_replace(' ', '-', get_string("package$userdata", 'dataform')). '.mbz');

                $fs->create_file_from_storedfile($package, $file);
                $file->delete();
                return true;
            }
        }
        return false;
    }

    /**
     *
     */
    public function create_package_from_upload($draftid) {
        global $USER;

        $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
        $fs = get_file_storage();
        if ($file = reset($fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'sortorder', false))) {
            $course_context = context_course::instance($this->course->id);
            $package = new object;
            $package->contextid = $course_context->id;
            $package->component = 'mod_dataform';
            $package->filearea = dataform::PACKAGE_COURSEAREA;
            $package->filepath = '/';
            
            $ext = pathinfo($file->get_filename(), PATHINFO_EXTENSION);            
            if ($ext == 'mbz') {
                $package->filename = $file->get_filename();
                $fs->create_file_from_storedfile($package, $file);
            } else if ($ext == 'zip') {
                // extract files to the draft area
                $zipper = get_file_packer('application/zip');
                $file->extract_to_storage($zipper, $usercontext->id, 'user', 'draft', $draftid, '/');
                $file->delete();

                if ($files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'sortorder', false)) {
                    foreach ($files as $file) {
                        $ext = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
                        if ($ext == 'mbz') {
                            $package->filename = $file->get_filename();
                            $fs->create_file_from_storedfile($package, $file);
                        }
                    }
                }
            }
            $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftid);
            return true;
        }
        return false;
    }


    /**
     *
     */
    public function apply_package($userpackage) {
        global $DB, $CFG, $USER;
        
        // extract the backup file to the temp folder
        $folder = $this->context->id. '-'. time();
        $backuptempdir = make_temp_directory("backup/$folder");
        $zipper = get_file_packer('application/zip');
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($userpackage);
        $file->extract_to_pathname($zipper, $backuptempdir);           
        
        require_once("$CFG->dirroot/backup/util/includes/restore_includes.php");

        // anonymous users cleanup
        $DB->delete_records_select('user', $DB->sql_like('firstname', '?'), array('%anonfirstname%'));
        
        $transaction = $DB->start_delegated_transaction();
        $rc = new restore_controller($folder,
                                    $this->course->id,
                                    backup::INTERACTIVE_NO,
                                    backup::MODE_GENERAL,
                                    $USER->id,
                                    backup::TARGET_CURRENT_ADDING);

        $rc->execute_precheck();

        // get the dataform restore activity task
        $tasks = $rc->get_plan()->get_tasks();
        $dataformtask = null;
        foreach ($tasks as &$task) {
            if ($task instanceof restore_dataform_activity_task) {
                $dataformtask = &$task;
                break;
            }
        }

        if ($dataformtask) {
            $dataformtask->set_activityid($this->id());
            $dataformtask->set_moduleid($this->cm->id);
            $dataformtask->set_contextid($this->context->id);
            $dataformtask->set_ownerid($USER->id);

            //$rc->set_status(backup::STATUS_AWAITING);
            $rc->execute_plan();
            
            $transaction->allow_commit();
            // rc cleanup
            $rc->destroy();
            // anonymous users cleanup
            $DB->delete_records_select('user', $DB->sql_like('firstname', '?'), array('%anonfirstname%'));
            
            redirect(new moodle_url('/mod/dataform/view.php', array('d' => $this->id())));        
        } else {
            $rc->destroy();
        }        
    }

    /**
     *
     */
    public function download_packages($packageids) {
        global $CFG;
        
        if (headers_sent()) {
            throw new moodle_exception('headerssent');
        }

        if (!$pids = explode(',', $packageids)) {
            return false;
        }

        $packages = array();
        $fs = get_file_storage();

        // try first course area
        $course_context = context_course::instance($this->course->id);
        $contextid = $course_context->id;

        if ($files = $fs->get_area_files($contextid, 'mod_dataform', dataform::PACKAGE_COURSEAREA)) {
            foreach ($files as $file) {
                if (empty($pids)) break;
                
                if (!$file->is_directory()) {
                    $key = array_search($file->get_id(), $pids);
                    if ($key !== false) {
                        $packages[$file->get_filename()] = $file;
                        unset($pids[$key]);
                    }
                }
            }
        }

        // try site area
        if (!empty($pids)) {
            if ($files = $fs->get_area_files(dataform::PACKAGE_SITECONTEXT, 'mod_dataform', dataform::PACKAGE_SITEAREA)) {
                foreach ($files as $file) {
                    if (empty($pids)) break;
                    
                    if (!$file->is_directory()) {
                        $key = array_search($file->get_id(), $pids);
                        if ($key !== false) {
                            $packages[$file->get_filename()] = $file;
                            unset($pids[$key]);
                        }
                    }
                }
            }            
        }

        $downloaddir = make_temp_directory('download');
        $filename = 'packages.zip';
        $downloadfile = "$downloaddir/$filename";
        
        $zipper = get_file_packer('application/zip');
        $zipper->archive_to_pathname($packages, $downloadfile);

        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
        header('Pragma: public');
        $downloadhandler = fopen($downloadfile, 'rb');
        print fread($downloadhandler, filesize($downloadfile));
        fclose($downloadhandler);
        unlink($downloadfile);
        exit(0);
    }

    /**
     *
     */
    public function share_packages($packageids) {
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

        foreach (explode(',', $packageids) as $pid) {
            $fs->create_file_from_storedfile($filerecord, $pid);
        }
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
    public function delete_packages($packageids) {
        if (!$pids = explode(',', $packageids)) {
            return false;
        }
        
        if (!has_capability('mod/dataform:managepackages', $this->context)) {
            return false;
        }
                    
        $fs = get_file_storage();

        // try first course area
        $course_context = context_course::instance($this->course->id);
        $contextid = $course_context->id;

        if ($files = $fs->get_area_files($contextid, 'mod_dataform', dataform::PACKAGE_COURSEAREA)) {
            foreach ($files as $file) {
                if (empty($pids)) break;
                
                if (!$file->is_directory()) {
                    $key = array_search($file->get_id(), $pids);
                    if ($key !== false) {
                        $file->delete();
                        unset($pids[$key]);
                    }
                }
            }
        }

        // try site area
        if (!empty($pids)) {
            if ($files = $fs->get_area_files(dataform::PACKAGE_SITECONTEXT, 'mod_dataform', dataform::PACKAGE_SITEAREA)) {
                foreach ($files as $file) {
                    if (empty($pids)) break;
                    
                    if (!$file->is_directory()) {
                        $key = array_search($file->get_id(), $pids);
                        if ($key !== false) {
                            $file->delete();
                            unset($pids[$key]);
                        }
                    }
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

        $params = array(
            $this->id(),
            $name,
            $id
        );
        
        $where = " dataid = ? AND name = ? AND id <> ? ";
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
        add_to_log($this->course->id, 'dataform', 'entry '. $action, $this->pagefile. '.php?id='. $this->cm->id, $this->id(), $this->cm->id);
    }
    
    

}
