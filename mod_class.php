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
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

/**
 * Dataform class
 */
class dataform {

    const NOTIFICATION_ENTRY_ADDED = 1;
    const NOTIFICATION_ENTRY_UPDATED = 2;
    const NOTIFICATION_ENTRY_DELETED = 4;
    const NOTIFICATION_COMMENT_ADDED = 8;

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
    protected $_filtermanager = null;
    protected $_rulemanager = null;
    protected $_presetmanager = null;
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
        //$this->_fieldmanager = new dataform_field_manager($this);        

        // set views manager
        //$this->_viewmanager = new dataform_view_manager($this);

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
        // set filters manager
        if (!$this->_filtermanager) { 
            require_once('filter/filter_class.php');
            $this->_filtermanager = new dataform_filter_manager($this);
        }
        return $this->_filtermanager;
    }

    /**
     *
     */
    public function get_rule_manager() {
        // set rules manager
        if (!$this->_rulemanager) { 
            require_once('rule/rule_manager.php');
            $this->_rulemanager = new dataform_rule_manager($this);
        }
        return $this->_rulemanager;
    }

    /**
     *
     */
    public function get_preset_manager() {
        // set preset manager
        if (!$this->_presetmanager) { 
            require_once('preset/preset_manager.php');
            $this->_presetmanager = new dataform_preset_manager($this);
        }
        return $this->_presetmanager;
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
        global $CFG, $PAGE, $USER, $OUTPUT;
        
        $this->pagefile = $page;
        $thisid = $this->id();
        
        $params = (object) $params;
        $urlparams = array();
        if (!empty($params->urlparams)) {
            foreach ($params->urlparams as $param => $value) {
                if ($value != 0 and $value != '') {
                    $urlparams[$param] = $value;
                }
            }
        }

        if (empty($params->nologin)) {
            // guest auto login
            $autologinguest = false;
            if ($page == 'view' or $page == 'embed' or $page == 'external') {
                $autologinguest = true;

            }
            
            // require login
            require_login($this->course->id, $autologinguest, $this->cm);
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

        // RSS
        if (!empty($params->rss) and
                !empty($CFG->enablerssfeeds) and
                !empty($CFG->dataform_enablerssfeeds) and
                $this->data->rssarticles > 0) {
            require_once("$CFG->libdir/rsslib.php");
            $rsstitle = format_string($this->course->shortname) . ': %fullname%';
            rss_add_http_header($this->context, 'mod_dataform', $this->data, $rsstitle);
        }
        
        // COMMENTS
        if (!empty($params->comments)) {
            require_once("$CFG->dirroot/comment/lib.php");
            comment::init();
        }

        $fs = get_file_storage();

        /////////////////////////////////////
        // PAGE setup for activity pages only
        
        if ($page != 'external') {
            // Is user editing
            $urlparams['edit'] = optional_param('edit', 0, PARAM_BOOL);
            $PAGE->set_url("/mod/dataform/$page.php", $urlparams);
            
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

            // auto refresh
            if (!empty($urlparams['refresh'])) {
               $PAGE->set_periodic_refresh_delay($urlparams['refresh']);
            }

            // page layout
            if (!empty($params->pagelayout)) {
                $PAGE->set_pagelayout($params->pagelayout);
            }
            
            // Mark as viewed
            if (!empty($params->completion)) {
                require_once($CFG->libdir . '/completionlib.php');
                $completion = new completion_info($this->course);
                $completion->set_module_viewed($this->cm);
            }

            $PAGE->set_title($this->name());
            $PAGE->set_heading($this->course->fullname);
            
            // Include blocks dragdrop when editing
            if ($PAGE->user_is_editing()) {
                $params = array(
                    'courseid' => $this->course->id,
                    'cmid' => $this->cm->id,
                    'pagetype' => $PAGE->pagetype,
                    'pagelayout' => $PAGE->pagelayout,
                    'regions' => $PAGE->blocks->get_regions(),
                );
                $PAGE->requires->yui_module('moodle-core-blocks', 'M.core_blocks.init_dragdrop', array($params), null, true);
            }            
        }

        ////////////////////////////////////
        // PAGE setup for dataform content anywhere
        
        // Use this to return css if this df page is set after header 
        $output = '';

        // CSS (cannot be required after head)
        $cssurls = array();
        if (!empty($params->css)) {
            // js includes from the js template
            if ($this->data->cssincludes) {
                foreach (explode("\n", $this->data->cssincludes) as $cssinclude) {
                    $cssinclude = trim($cssinclude);
                    if ($cssinclude) {
                        $cssurls[] = new moodle_url($cssinclude);
                    }
                }
            }
            // Uploaded css files
            if ($files = $fs->get_area_files($this->context->id, 'mod_dataform', 'css', 0, 'sortorder', false)) {
                $path = "/{$this->context->id}/mod_dataform/css/0";
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $cssurls[] = moodle_url::make_file_url('/pluginfile.php', "$path/$filename");
                }
            }                
            // css code from the css template
            if ($this->data->css) {
                $cssurls[] = new moodle_url('/mod/dataform/css.php', array('d' => $thisid));
            }
        }
        if ($PAGE->state == moodle_page::STATE_BEFORE_HEADER) {
            foreach ($cssurls as $cssurl) {
                $PAGE->requires->css($cssurl);
            }
        } else {
            $attrs = array('rel' => 'stylesheet', 'type' => 'text/css');
            foreach ($cssurls as $cssurl) {
                $attrs['href'] = $cssurl;
                $output .= html_writer::empty_tag('link', $attrs). "\n";
                unset($attrs['id']);
            }
        }

        // JS
        $jsurls = array();
        if (!empty($params->js)) {
            // js includes from the js template
            if ($this->data->jsincludes) {
                foreach (explode("\n", $this->data->jsincludes) as $jsinclude) {
                    $jsinclude = trim($jsinclude);
                    if ($jsinclude) {
                        $jsurls[] = new moodle_url($jsinclude);
                    }
                }
            }
            // Uploaded js files
            if ($files = $fs->get_area_files($this->context->id, 'mod_dataform', 'js', 0, 'sortorder', false)) {
                $path = "/{$this->context->id}/mod_dataform/js/0";
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $jsurls[] = moodle_url::make_file_url('/pluginfile.php', "$path/$filename");
                }
            }                
            // js code from the js template
            if ($this->data->js) {
                $jsurls[] = new moodle_url('/mod/dataform/js.php', array('d' => $thisid));
            }
        }
        foreach ($jsurls as $jsurl) {
            $PAGE->requires->js($jsurl);
        }
       
        // MOD JS
        if (!empty($params->modjs)) {
            $PAGE->requires->js('/mod/dataform/dataform.js');
        }
        
        // TODO
        //if ($mode == 'asearch') {
        //    $PAGE->navbar->add(get_string('search'));
        //}

        // set current view and view's page requirements
        $currentview = !empty($urlparams['view']) ? $urlparams['view'] : 0;
        if ($this->_currentview = $this->get_view_from_id($currentview)) {
            $this->_currentview->set_page($page);
        }
        
        // if a new dataform or incomplete design, direct manager to manage area
        if ($manager) {
            $views = $this->get_views();
            if (!$views) {
                if ($page == 'view' or $page == 'embed') {
                    $getstarted = new object;
                    $getstarted->presets = html_writer::link(new moodle_url('preset/index.php', array('d' => $thisid)), get_string('presets', 'dataform'));
                    $getstarted->fields = html_writer::link(new moodle_url('field/index.php', array('d' => $thisid)), get_string('fields', 'dataform'));
                    $getstarted->views = html_writer::link(new moodle_url('view/index.php', array('d' => $thisid)), get_string('views', 'dataform'));

                    $this->notifications['bad']['getstarted'] = html_writer::tag('div', get_string('getstarted', 'dataform', $getstarted), array('class' => 'mdl-left'));
                }
            } else if (!$this->data->defaultview) {
                $linktoviews = html_writer::link(new moodle_url('view/index.php', array('d' => $thisid)), get_string('views', 'dataform'));
                $this->notifications['bad']['defaultview'] = get_string('viewnodefault','dataform', $linktoviews);
            }
        }

        return $output;
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
    public function get_user_defined_fields($forceget = false, $sort = '') {
        $this->get_fields(null, false, $forceget, $sort);
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
    public function get_fields($exclude = null, $menu = false, $forceget = false, $sort = '') {
        global $DB;

        if (!$this->fields or $forceget) {
            $this->fields = array();
            // collate user fields
            if ($fields = $DB->get_records('dataform_fields', array('dataid' => $this->id()), $sort)) {
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
                        new moodle_url('/mod/dataform/field/index.php', array('d' => $this->id(),
                                                                        $action => implode(',', array_keys($fields)),
                                                                        'sesskey' => sesskey(),
                                                                        'confirmed' => 1)),
                        new moodle_url('/mod/dataform/field/index.php', array('d' => $this->id())));

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

                add_to_log($this->course->id, 'dataform', 'field '. $action, 'field/index.php?id='. $this->cm->id, $this->id(), $this->cm->id);
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
    public function get_views($exclude = null, $menu = false, $forceget = false, $sort = '') {
        global $DB;

        if (empty($this->views) or $forceget) {
            $this->views = array();
            if ($views = $DB->get_records('dataform_views', array('dataid' => $this->id()), $sort)) {
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
                        new moodle_url('/mod/dataform/view/index.php', array('d' => $this->id(),
                                                                        $action => implode(',', array_keys($views)),
                                                                        'sesskey' => sesskey(),
                                                                        'confirmed' => 1)),
                        new moodle_url('/mod/dataform/view/index.php', array('d' => $this->id())));

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

                add_to_log($this->course->id, 'dataform', 'view '. $action, 'view/index.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if ($strnotify) {
                    $viewsprocessed = $processedvids ? count($processedvids) : 'No';
                    $this->notifications['good'][] = get_string($strnotify, 'dataform', $viewsprocessed);
                }
                return $processedvids;
            }
        }
    }


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
    
    /**
     * 
     */
    public function events_trigger($event, $data) {

        $users = array();
        // Get capability users if notifications for the event are enabled
        $notificationtypes = self::get_notification_types();
        if ($this->data->notification & $notificationtypes[$event]) {
            $capability = "mod/dataform:notify$event";
            $users = get_users_by_capability($this->context, $capability, 'u.id,u.auth,u.suspended,u.deleted,u.lastaccess,u.emailstop');
        }

        // Get event notificataion rule users
        $rm = $this->get_rule_manager();
        if ($rules = $rm->get_rules_by_type('eventnotification')) {
            foreach ($rules as $rule) {
                if ($rule->is_enabled() and in_array($event, $rule->get_selected_events())) {
                    $users = array_merge($users, $rule->get_recipient_users($event, $data->items));
                }
            }
        }

        if (empty($users)) {
            return;
        }    
       
        $data->users = $users;       
        $data->coursename = $this->course->shortname;
        $data->dataformname = $this->name();
        $data->context = $this->context->id;
        $data->event = $event;
        $data->notification = 1;
        events_trigger("dataform_$event", $data);
    }
    
    /**
     * 
     */
    public static function get_notification_types() {
        return array(
            'entryadded' => self::NOTIFICATION_ENTRY_ADDED,
            'entryupdated' => self::NOTIFICATION_ENTRY_UPDATED,
            'entrydeleted' => self::NOTIFICATION_ENTRY_DELETED,
            'commentadded' => self::NOTIFICATION_COMMENT_ADDED,
        );
    }
            
}
