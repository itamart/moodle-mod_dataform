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
 * @package dataformview
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A base class for dataform views
 * (see view/<view type>/view_class.php)
 */
class dataformview_base {

    const VISIBLE = 2;      // the view can be seen and used by everyone
    const HIDDEN = 1;       // the view can be used by everyone but seen only by managers
    const DISABLED = 0;     // the view can be used and seen only by managers

    const ADD_NEW_ENTRY = -1;

    protected $type = 'unknown';      // Subclasses must override the type with their name

    public $view = NULL;            // The view object itself, if we know it

    protected $_df = NULL;           // The dataform object that this view belongs to
    protected $_filter = null;
    protected $_patterns = null;

    protected $_editors = array('section');
    protected $_vieweditors = array('section');
    protected $_entries = null;

    protected $_tags = array();
    protected $_baseurl = '';

    protected $_notifications = array('good' => array(), 'bad' => array());

    protected $_editentries = 0;
    protected $_entriesform = null;
    protected $_display_definition = array();
    protected $_returntoentriesform = false;

    /**
     * Constructor
     * View or dataform or both, each can be id or object
     */
    public function __construct($df = 0, $view = 0, $filteroptions = true) {
        global $DB, $CFG;

        if (empty($df)) {
            throw new coding_exception('Dataform id or object must be passed to field constructor.');
        // dataform object
        } else if ($df instanceof dataform) {
            $this->_df = $df;
        // dataform id
        } else {
            $this->_df = new dataform($df);
        }

        // set existing view
        if (!empty($view)) {
            if (is_object($view)) {
                $this->view = $view;  // Programmer knows what they are doing, we hope
            } else if (!$this->view = $DB->get_record('dataform_views', array('id' => $view))) {
                throw new moodle_exception('invalidview', 'dataform', null, null, $view);
            }
        // set defaults for new view
        } else {
            $this->view = new object;
            $this->view->id   = 0;
            $this->view->type   = $this->type;
            $this->view->dataid = $this->_df->id();
            $this->view->name = get_string('pluginname', "dataformview_{$this->type}");
            $this->view->description = '';
            $this->view->visible = 2;
            $this->view->filter = 0;
            $this->view->perpage = 0;
            $this->view->groupby = '';
        }

        // set editors and patterns
        $this->set__editors();
        $this->set__patterns();

        // filter
        $fm = $this->_df->get_filter_manager();
        $options = $filteroptions ? $fm::get_filter_options_from_url() : array();
        $this->set_filter($options);

        // base url params
        $baseurlparams = array();
        $baseurlparams['d'] = $this->_df->id();
        $baseurlparams['view'] = $this->id();
        $baseurlparams['filter'] = $this->_filter->id;
        if (!empty($eids)) {
            $baseurlparams['eids'] = $eids;
        }
        if ($this->_filter->page) {
            $baseurlparams['page'] = $this->_filter->page;
        }
        if ($this->_df->currentgroup) {
            $baseurlparams['currentgroup'] = $this->_df->currentgroup;
        }

        $this->_baseurl = new moodle_url("/mod/dataform/{$this->_df->pagefile()}.php", $baseurlparams);

        // TODO: should this be here?
        $this->set_groupby_per_page();

        // TODO
        require_once("$CFG->dirroot/mod/dataform/entries_class.php");
        $this->_entries = new dataform_entries($this->_df, $this);
    }

    /**
     * Set view
     */
    protected function set_view($data) {
        $this->view->name = $data->name;
        $this->view->description = !empty($data->description) ? $data->description : '';

        $this->view->visible = !empty($data->visible) ? $data->visible : 0;
        $this->view->perpage = !empty($data->perpage) ? $data->perpage : 0;
        $this->view->groupby = !empty($data->groupby) ? $data->groupby : '';
        $this->view->filter = !empty($data->filter) ? $data->filter : 0;
        $this->view->sectionpos = !empty($data->sectionpos) ? $data->sectionpos : 0;

        for ($i=1; $i<=10; $i++) {
            if (isset($data->{"param$i"})) {
                $this->view->{"param$i"} = $data->{"param$i"};
            }
        }

        $this->set__editors($data);
        $this->set__patterns($data);

        return true;
    }

    /**
     *
     */
    protected function set__editors($data = null) {
        foreach ($this->_editors as $editor) {
            // new view or from DB so add editor fields
            if (is_null($data)) {
                if (!empty($this->view->{$editor})) {
                    $editordata = $this->view->{$editor};
                    if (strpos($editordata, 'ft:') === 0
                                and strpos($editordata, 'tr:') === 4
                                and strpos($editordata, 'ct:') === 8) {
                        $format = substr($editordata, 3, 1);
                        $trust = substr($editordata, 7, 1);
                        $text = substr($editordata, 11);
                    } else {
                        list($format, $trust, $text) = array(FORMAT_HTML, 1, $editordata);
                    }
                } else {
                    list($format, $trust, $text) = array(FORMAT_HTML, 1, '');
                }
                $this->view->{"e$editor".'format'} = $format;
                $this->view->{"e$editor".'trust'} = $trust;
                $this->view->{"e$editor"} = $text;                    

            // view from form or editor areas updated
            } else {
                $format = !empty($data->{"e$editor".'format'}) ? $data->{"e$editor".'format'} : FORMAT_HTML;
                $trust = !empty($data->{"e$editor".'trust'}) ? $data->{"e$editor".'trust'} : 1;
                $text = !empty($data->{"e$editor"}) ? $data->{"e$editor"} : '';

                // replace \n in non text format
                if ($format != FORMAT_PLAIN) {
                    $text = str_replace("\n","",$text);
                }

                if (!empty($text)) {
                    $this->view->$editor = "ft:{$format}tr:{$trust}ct:$text";
                } else {
                    $this->view->$editor = null;
                }
            }
        }
    }

    /**
     *
     */
    protected function set__patterns($data = null) {
        // new view or from DB so set the _patterns property
        if (is_null($data)) {
            if (!empty($this->view->patterns)) {
                $this->_tags = unserialize($this->view->patterns);
            } else {
                $this->_tags = array('view' => array(), 'field' => array());
            }

        // view from form or editor areas updated
        } else {
            $this->_tags = array('view' => array(), 'field' => array());
            $text = '';
            foreach ($this->_editors as $editor) {
                $text .= !empty($data->{"e$editor"}) ? ' '. $data->{"e$editor"} : '';
            }

            if (trim($text)) {
                // Dataform View links/content

                // TODO filter links ???
                
                // This view patterns
                if ($patterns = $this->patterns()->search($text)) {
                    $this->_tags['view'] = $patterns;
                }
                // Field patterns
                if ($fields = $this->_df->get_fields()) {
                    foreach ($fields as $fieldid => $field) {
                        if ($patterns = $field->renderer()->search($text)) {
                            $this->_tags['field'][$fieldid] = $patterns;
                        }
                    }
                }

            }
            $this->view->patterns = serialize($this->_tags);
        }
    }

    /**
     *
     */
    public function set_filter($options) {
        $fm = $this->_df->get_filter_manager($this);

        $fid = !empty($options['filterid']) ? $options['filterid'] : 0;
        $afilter = !empty($options['afilter']) ? $options['afilter'] : 0;
        $eids = !empty($options['eids']) ? $options['eids'] : null;
        $users = !empty($options['users']) ? $options['users'] : null;
        $groups = !empty($options['groups']) ? $options['groups'] : null;
        $page = !empty($options['page']) ? $options['page'] : 0;
        $usort = !empty($options['usort']) ? $options['usort'] : null;
        $usearch = !empty($options['usearch']) ? $options['usearch'] : null;
        $csort = !empty($options['csort']) ? $options['csort'] : null;
        $csearch = !empty($options['csearch']) ? $options['csearch'] : null;

        // set filter
        $filter = $this->filter_options();
        if (!$filterid = $filter['filterid']) {
            $filterid = $fid;
        }
        $this->_filter = $fm->get_filter_from_id($filterid, array('view' => $this, 'advanced' => $afilter));
        
        // set specific entry id
        $this->_filter->eids = $eids;
        // set specific user id
        if ($users) {
            $this->_filter->users = is_array($users) ? $users : explode(',', $users);
        }
        // set specific entry id, if requested
        if ($groups) {
            $this->_filter->groups = is_array($groups) ? $groups : explode(',', $groups);
        }
        // add view specific perpage
        if ($filter['perpage']) {
            $this->_filter->perpage = $filter['perpage'];
        }
        // add view specific groupby
        if ($filter['groupby']) {
            $this->_filter->groupby = $filter['groupby'];
        }
        // add page
        $this->_filter->page = !empty($filter['page']) ? $filter['page'] : $page;
        // content fields
        $this->_filter->contentfields = array_keys($this->get__patterns('field'));
        // Append url sort options
        if ($usort) {
            $sortoptions = dataform_filter_manager::get_sort_options_from_query($usort);
            $this->_filter->append_sort_options($sortoptions);
        }
        // Append url search options
        if ($usearch) {
            $searchoptions = dataform_filter_manager::get_search_options_from_query($usearch);
            $this->_filter->append_search_options($searchoptions);
        }
        // Append custom sort options
        if ($csort) {
            $this->_filter->append_sort_options($csort);
        }
        // Append custom search options
        if ($csearch) {
            $this->_filter->append_search_options($csearch);
        }
    }

    ////////////////////////////////////
    // VIEW TYPE
    ////////////////////////////////////

    /**
     * Insert a new view into the database
     * $this->view is assumed set
     */
    public function add($data) {
        global $DB, $OUTPUT;

        $this->set_view($data);

        if (!$this->view->id = $DB->insert_record('dataform_views', $this->view)){
            echo $OUTPUT->notification('Insertion of new view failed!');
            return false;
        }

        // update item id of files area
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->_df->context->id, 'mod_dataform', 'view', 0);
        if (count($files) > 1) {
            foreach ($files as $file) {
                $filerec = new object;
                $filerec->itemid = $this->view->id;
                $fs->create_file_from_storedfile($filerec, $file);
            }
        }
        $fs->delete_area_files($this->_df->context->id, 'mod_dataform', 'view', 0);


        return $this->view->id;
    }

    /**
     * Update a view in the database
     * $this->view is assumed set
     */
    public function update($data = null) {
        global $DB, $OUTPUT;

        if ($data) {
            $this->set_view($data);
        }
        if (!$DB->update_record('dataform_views', $this->view)) {
            echo $OUTPUT->notification('updating view failed!');
            return false;
        }
        return true;
    }

    /**
     * Delete a view from the database
     */
    public function delete() {
        global $DB;

        if (!empty($this->view->id)) {
            $fs = get_file_storage();
            foreach ($this->_editors as $key => $editorname) {
                $editor = "e$editorname";
                $fs->delete_area_files($this->_df->context->id,
                                        'mod_dataform',
                                        'view',
                                        $this->id(). $key);
            }

            return $DB->delete_records('dataform_views', array('id' => $this->view->id));
        }
        // TODO
        return true;
    }

    /**
     *
     */
    public function get_form() {
        global $CFG;

        $formclass = 'dataformview_'. $this->type. '_form';
        $formparams = array(
            'd' => $this->_df->id(),
            'vedit' => $this->id(),
            'type' => $this->type
        );
        $actionurl = new moodle_url('/mod/dataform/view/view_edit.php', $formparams);
                                    
        require_once($CFG->dirroot. '/mod/dataform/view/'. $this->type. '/view_form.php');
        return new $formclass($this, $actionurl);
    }

    /**
     * prepare view data for form
     */
    public function to_form($data = null) {
        $data = $data ? $data : $this->view;
        
        // Prepare view editors
        $data = $this->prepare_view_editors($data);

        return $data;
    }

    /**
     * prepare view data for form
     */
    public function from_form($data) {
        $data = $this->update_view_editors($data);

        return $data;
    }

    /**
     * Prepare view editors for form
     */
    public function prepare_view_editors($data) {
        $editors = $this->editors();

        foreach ($editors as $editorname => $options) {
             $data = file_prepare_standard_editor($data,
                                                "e$editorname",
                                                $options,
                                                $this->_df->context,
                                                'mod_dataform',
                                                "view$editorname",
                                                $this->view->id);
        }
        return $data;
    }

    /**
     * Update view editors from form
     */
    public function update_view_editors($data) {
        if (!$editors = $this->editors()) {
            return $data;
        }

        foreach ($editors as $editorname => $options) {
            $data = file_postupdate_standard_editor($data,
                                                    "e$editorname",
                                                    $options,
                                                    $this->_df->context,
                                                    'mod_dataform',
                                                    "view$editorname",
                                                    $this->view->id);
        } 
        
        return $data;
    }

    /**
     * Subclass may need to override
     */
    public function replace_field_in_view($searchfieldname, $newfieldname) {
        $patterns = array('[['.$searchfieldname.']]','[['.$searchfieldname.'#id]]');
        if (!$newfieldname) {
            $replacements = '';
        } else {
            $replacements = array('[['.$newfieldname.']]','[['.$newfieldname.'#id]]');
        }

        foreach ($this->_editors as $editor) {
            $this->view->{"e$editor"} = str_ireplace($patterns, $replacements, $this->view->{"e$editor"});
        }
        $this->update($this->view);
    }

    /**
     * Returns the name/type of the view
     */
    public function name_exists($name, $viewid) {
        return $this->_df->name_exists('views', $name, $viewid);
    }

    ////////////////////////////////////
    // VIEW DISPLAY
    ////////////////////////////////////
    /**
     *
     */
    public function set_page($page = null) {
    }

    /**
     * process any view specific actions
     */
    public function process_data() {
        global $CFG;

        // proces export requests
        $export = optional_param('export','', PARAM_TAGLIST);  // comma delimited entry ids or -1 for all entries in view
        if ($export and confirm_sesskey()) {
            if (!empty($CFG->enableportfolios)) {
                require_once("$CFG->libdir/portfoliolib.php");
                $exportparams = array(
                    'ca_id' => $this->_df->cm->id,
                    'ca_vid' => $this->id(),
                    'ca_fid' => $this->_filter->id,
                    'ca_eids' => null,
                    'sesskey' => sesskey(),
                    'callbackfile' => '/mod/dataform/locallib.php',
                    'callbackclass' => 'dataform_portfolio_caller',
                    'callerformats' => optional_param('format', 'spreadsheet,richhtml', PARAM_TAGLIST),
                );

                redirect(new moodle_url('/portfolio/add.php', $exportparams));
            }
        }

        // Process entries data
        $processed = $this->process_entries_data();
        if (is_array($processed)) {
            list($strnotify, $processedeids) = $processed;
        } else {
            list($strnotify, $processedeids) = array('', '');
        }
        
        if ($processedeids) {
           $this->_notifications['good']['entries'] = $strnotify;
        } else if ($strnotify) {
           $this->_notifications['bad']['entries'] = $strnotify;
        }

        // With one entry per page show the saved entry
        if ($processedeids and $this->_editentries and !$this->_returntoentriesform) {
            if ($this->_filter->perpage == 1) {
                $this->_filter->eids = $this->_editentries;
            }
            $this->_editentries = '';
        }

        return $processed;
    }

    /**
     *
     */
    public function set_content() {
        if ($this->_returntoentriesform) {
            return;
        }
        
        $options = array();
        
        // check if view is caching
        if ($this->is_caching()) {
            $entriesset = $this->get_cache_content();
            
            $filteroptions = $this->get_cache_filter_options();
            foreach ($filteroptions as $option => $value) {
                $this->_filter->{$option} = $value;
            }
        
            if (!$entriesset) {
                $entriesset = $this->_entries->get_entries();
                $this->update_cache_content($entriesset);
            }
            $options['entriesset'] = $entriesset;
        }
        
        // do we need ratings?
        if ($ratingoptions = $this->is_rating()) {
            $options['ratings'] = $ratingoptions;
        }
        // do we need comments?

        // Hacking here the case of add new entry form that doesn't display any existing entries
        // This would be the case when view perpage is set to 1
        if ($this->_editentries < 0 and $this->view->perpage == 1) {
            return;
        }
        
        // Get the entries
        $this->_entries->set_content($options);
    }

    /**
     *
     */
    public function display(array $options = array()) {
        global $OUTPUT;

        // set display options
        $displayentries = isset($options['entries']) ? $options['entries'] : true;
        $displaycontrols = isset($options['controls']) ? $options['controls'] : true;
        $showentryactions = isset($options['entryactions']) ? $options['entryactions'] : true;
        $notify = isset($options['notify']) ? $options['notify'] : true;
        $tohtml = isset($options['tohtml']) ? $options['tohtml'] : false;
        $pluginfileurl = isset($options['pluginfileurl']) ? $options['pluginfileurl'] : null;      

        // build entries display definition
        $requiresmanageentries = $this->set__display_definition($options);

        // set view specific tags
        $viewoptions = array();
        $viewoptions['pluginfileurl'] = $pluginfileurl;      
        $viewoptions['entriescount'] = $this->_entries->get_count();
        $viewoptions['entriesfiltercount'] = $this->_entries->get_count(true);
        // adding one or more new entries
        if ($this->user_is_editing()) {
            $viewoptions['hidenewentry'] = 1;
        }
        // editing one or more new entries
        if ($requiresmanageentries and $showentryactions) {
            $viewoptions['showentryactions'] = 1;
        }
        $this->set_view_tags($viewoptions);

        // print notifications
        $notifications = '';
        if ($notify) {
            foreach ($this->_notifications['good'] as $notification) {
                $notifications = $OUTPUT->notification($notification, 'notifysuccess');    // good (usually green)
            }
            foreach ($this->_notifications['bad'] as $notification) {
                $notifications = $OUTPUT->notification($notification);    // bad (usually red)
            }
        }

        // print view
        $viewname = 'dataformview-'. str_replace(' ', '_', $this->name());
        if (strpos($this->view->esection, '##entries##') !== false) {
            list($print_before, $print_after) = explode('##entries##', $this->view->esection, 2);
        } else {
            $print_before = $displaycontrols ? $this->process_calculations($this->print_before()) : '';
            $print_after = $displaycontrols ? $this->process_calculations($this->print_after()) : '';
        }
        if ($tohtml) {
            $html = $notifications;
            $html .= $print_before;
            if ($displayentries) {
                $html .= $this->display_entries($options);
            }
            $html .= $print_after;
            return html_writer::tag('div', $html, array('class' => $viewname));
        } else {
            echo html_writer::start_tag('div', array('class' => $viewname));
            echo $notifications;
            echo $print_before;
            if ($displayentries) {
                $this->display_entries($options);
            }
            echo $print_after;
            echo html_writer::end_tag('div');
        }
    }

    /**
     * Just in case a view needs to print something before the whole form
     */
    protected function print_before() {
        $str = '';
        $float = '';
        $blockposition = $this->view->sectionpos;
        // print the general section if not bottom
        if ($blockposition == 1 or $blockposition == 2) { // not on top
            $float = ($blockposition == 1 ? ' style="float:left" ' : ' style="float:right" ');

            $str .= "<div  $float>";
            $str .= $this->view->esection;
            $str .= "</div>";
            $str .= "<div  $float>";

        } else if (!$blockposition) {
            $str .= "<div>";
            $str .= $this->view->esection;
            $str .= "</div>";
            $str .= "<div>";
        }
        return $str;
    }

    /**
     * Just in case a view needs to print something after the whole form
     */
    protected function print_after() {
        $str = '';
        $float = '';
        $blockposition = $this->view->sectionpos;
        // close div
        $str .= "</div>";

        if ($blockposition == 3) { // bottom
            $str .= "<div>";
            $str .= $this->view->esection;
            $str .= "</div>";
        }

        return $str;
    }

    ////////////////////////////////////
    // VIEW ATTRS
    ////////////////////////////////////

    /**
     * Returns the type of the view
     */
    public function id() {
        return $this->view->id;
    }

    /**
     * Returns the type of the view
     */
    public function type() {
        return $this->type;
    }

    /**
     * Returns the type name of the view
     */
    public function typename() {
        return get_string('pluginname', "dataformview_{$this->type}");
    }

    /**
     * Returns the name/type of the view
     */
    public function name() {
        return $this->view->name;
    }

    /**
     * Returns the parent dataform
     */
    public function get_df() {
        return $this->_df;
    }

    /**
     *
     */
    public function get_filter() {
        return $this->_filter;
    }

    /**
     *
     */
    public function get_baseurl() {
        return $this->_baseurl;
    }

    /**
     *
     */
    public function is_active() {
        return (optional_param('view', 0, PARAM_INT) == $this->id());
    }
    
    /**
     *
     */
    public function is_caching() {
        return false;
    }

    /**
     *
     */
    public function is_forcing_filter() {
        return $this->view->filter;
    }

    ////////////////////////////////////
    // HELPERS
    ////////////////////////////////////
    /**
     * TODO
     */
    public function get_view_fields() {
        $viewfields = array();

        if (!empty($this->_tags['field'])) {
            $fields = $this->_df->get_fields();
            foreach (array_keys($this->_tags['field']) as $fieldid) {
                if (array_key_exists($fieldid, $fields)) {
                    $viewfields[$fieldid] = $fields[$fieldid];
                }
            }
        }

        return $viewfields;
    }

    /**
     *
     */
    public function filter_options() {
        $options = array();
        $options['filterid'] = $this->view->filter;
        $options['perpage'] = $this->view->perpage;
        $options['groupby'] = $this->view->groupby;
        return $options;
    }

    /**
     *
     */
    public function field_tags() {
        $patterns = array();
        if ($fields = $this->_df->get_fields()) {
            foreach ($fields as $field) {
                if ($fieldpatterns = $field->renderer()->get_menu()) {
                    $patterns = array_merge_recursive($patterns, $fieldpatterns);
                }
            }
        }

        return $patterns;
    }

    /**
     *
     */
    public function character_tags() {
        $patterns = array('---' => array('---' => array()));
        $patterns['9'] = 'tab';
        $patterns['10'] = 'new line';

        return $patterns;
    }

    /**
     * check the multple existence any tag in a view
     * should be redefined in sub-classes
     * output bool true-valid, false-invalid
     */
    public function tags_check($template) {
        $tagsok = true; // let's be optimistic
        foreach ($this->_df->get_fields() as $field) { // only user fields
            if ($field->id() > 0) {
                $pattern="/\[\[".$field->name()."\]\]/i";
                if (preg_match_all($pattern, $template, $dummy) > 1) {
                    $tagsok = false;
                    notify ('[['.$field->name().']] - '.get_string('multipletags','dataform'));
                }
            }
        }
        // else return true
        return $tagsok;
    }

    /**
     *
     */
    public function generate_default_view() {
    }

    /**
     *
     */
    public function editors() {
        $editors = array();

        $options = array('trusttext' => true,
                            'noclean' => true,
                            'subdirs' => false,
                            'changeformat' => true,
                            'collapsed' => true,
                            'rows' => 5,
                            'style' => 'width:100%',
                            'maxfiles' => EDITOR_UNLIMITED_FILES,
                            'maxbytes' => $this->_df->course->maxbytes,
                            'context'=> $this->_df->context);

        foreach ($this->_editors as $editor) {
            $editors[$editor] = $options;
        }

        return $editors;
    }

    /**
     *
     */
    public function patterns() {
        global $CFG;
        
        if (!$this->_patterns) {
            $viewtype = $this->type;
            
            if (file_exists("$CFG->dirroot/mod/dataform/view/$viewtype/view_patterns.php")) {
                require_once("$CFG->dirroot/mod/dataform/view/$viewtype/view_patterns.php");
                $patternsclass = "dataformview_{$viewtype}_patterns";
            } else {
                require_once("$CFG->dirroot/mod/dataform/view/view_patterns.php");
                $patternsclass = "dataformview_patterns";
            }
            $this->_patterns = new $patternsclass($this);
        }
        return $this->_patterns;
    }

    /**
     *
     */
    public function set_view_tags($options) {
        // rewrite plugin urls
        $pluginfileurl = !empty($options['pluginfileurl']) ? $options['pluginfileurl'] : null;
        foreach ($this->_editors as $editorname) {
            $editor = "e$editorname";

            // export with files should provide the file path
            if ($pluginfileurl) {
                $this->view->$editor = str_replace('@@PLUGINFILE@@/', $pluginfileurl, $this->view->$editor);
            } else {
                $this->view->$editor = file_rewrite_pluginfile_urls($this->view->$editor,
                                                                            'pluginfile.php',
                                                                            $this->_df->context->id,
                                                                            'mod_dataform',
                                                                            "view$editorname",
                                                                            $this->id());
            }
        }

        $tags = $this->_tags['view'];
        $replacements = $this->patterns()->get_replacements($tags, null, $options);
        foreach ($this->_vieweditors as $editor) {
            // Format to apply filters if html
            if ($this->view->{"e$editor".'format'} == FORMAT_HTML) {
                $this->view->{"e$editor"} = format_text($this->view->{"e$editor"}, FORMAT_HTML, array('trusted' => 1));
            }
            
            $this->view->{"e$editor"} = str_replace($tags, $replacements, $this->view->{"e$editor"});
        }
    }

    /**
     *
     */
    public function get__patterns($set = null) {
        if (is_null($set)) {
            return $this->_tags;
        } else if ($set == 'view' or $set == 'field') {
            return $this->_tags[$set];
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function get_pattern_fieldid($pattern) {
        if (!empty($this->_tags['field'])) {
            foreach ($this->_tags['field'] as $fieldid => $patterns) {
                if (in_array($pattern, $patterns)) {
                    return $fieldid;
                }
            }
        }
        return null;
    }

    /**
     *
     */
    public function get_embedded_files($set = null) {
        $files = array();
        $fs = get_file_storage();

        // view files
        if (empty($set) or $set == 'view') {
            foreach ($this->_editors as $key => $editorname) {
                $editor = "e$editorname";
                $files = array_merge($files, $fs->get_area_files($this->_df->context->id,
                                                                'mod_dataform',
                                                                'view',
                                                                $this->id(). $key,
                                                                'sortorder, itemid, filepath, filename',
                                                                false));
            }
        }

        // field files
        if (empty($set) or $set == 'field') {
            // find which fields actually display files/images in the view
            $fids = array();
            if (!empty($this->_tags['field'])) {
                $fields = $this->_df->get_fields();
                foreach ($this->_tags['field'] as $fieldid => $tags) {
                    if (array_intersect($tags, $fields[$fieldid]->renderer()->pluginfile_patterns())) {
                        $fids[] = $fieldid;
                    }
                }
            }
            // get the files from the entries
            if ($this->_entries and !empty($fids)) {  // set_content must have been called
                $files = array_merge($files, $this->_entries->get_embedded_files($fids));
            }
        }

        return $files;
    }

    /**
     * @param array $entriesset entryid => array(entry, edit, editable)
     */
    public function get_entries_definition() {

        $display_definition = $this->_display_definition;
        $groupedelements = array();
        foreach ($display_definition as $name => $entriesset) {
            $definitions = array();
            if ($name == 'newentry') {
                foreach ($entriesset as $entryid => $unused) {
                    $definitions[$entryid] = $this->new_entry_definition($entryid);
                }
            } else {
                foreach ($entriesset as $entryid => $entryparams) {
                    list($entry, $editthisone, $managethisone) = $entryparams;
                    $options = array('edit' => $editthisone, 'managable' => $managethisone);
                    $fielddefinitions = $this->get_field_definitions($entry, $options);
                    $definitions[$entryid] = $this->entry_definition($fielddefinitions);
                }
            }
            $groupedelements[$name] = $this->group_entries_definition($definitions, $name);
        }
        // Flatten the elements
        $elements = array();
        foreach ($groupedelements as $group) {
            $elements = array_merge($elements, $group);
        }
        
        return $elements;
    }

    /**
     *
     */
    protected function group_entries_definition($entriesset, $name = '') {
        return array();
    }

    /**
     *
     */
    protected function new_entry_definition($entryid = -1) {
        return array();
    }

    /**
     *
     */
    protected function entry_definition($fielddefinitions) {
        return array();
    }

    /**
     *
     */
    protected function get_field_definitions($entry, $options) {
        $fields = $this->_df->get_fields();
        $entry->baseurl = $this->_baseurl;

        $definitions = array();
        foreach ($this->_tags['field'] as $fieldid => $patterns) {
            if (!isset($fields[$fieldid])) {
                continue;
            } 
            $field = $fields[$fieldid];
            if ($fielddefinitions = $field->get_definitions($patterns, $entry, $options)) {
                $definitions = array_merge($definitions, $fielddefinitions);
            }
        }
        return $definitions;
    }

    /**
     * @param array $patterns array of arrays of pattern replacement pairs
     */
    protected function split_tags($patterns, $subject) {
        $delims = implode('|', $patterns);
        // escape [ and ] and the pattern rule character *
        // TODO organize this
        $delims = str_replace(array('[', ']', '*', '^'), array('\[', '\]', '\*', '\^'), $delims);

        $elements = preg_split("/($delims)/", $subject, null, PREG_SPLIT_DELIM_CAPTURE);

        return $elements;
    }

    /**
     *
     */
    protected function get_groupby_value($entry) {
        $fields = $this->_df->get_fields();
        $fieldid = $this->_filter->groupby;
        $groupbyvalue = '';

        if (array_key_exists($fieldid, $this->_tags['field'])) {
            // first pattern
            $pattern = reset($this->_tags['field'][$fieldid]);
            $field = $fields[$fieldid];
            /// TODO
            if ($definition = $field->get_definitions(array($pattern), $entry)) {
               $groupbyvalue = $definition[$pattern][1];
            }
        }

        return $groupbyvalue;
    }

    /**
     * Set sort and search criteria for grouping by
     */
    protected function set_groupby_per_page() {
        global $CFG;

        // Get the group by fieldid
        if (empty($this->_filter->groupby)) {
            return;
        }
        
        $fieldid = $this->_filter->groupby;
        // set sorting to begin with this field
        $insort = false;
        // TODO: asc order is arbitrary here and should be determined differently
        $sortdir = 0;
        $sortfields = array();
        if ($this->_filter->customsort) {
            $sortfields = unserialize($this->_filter->customsort);
            if ($insort = in_array($fieldid, array_keys($sortfields))) {
                $sortdir = $sortfields[$fieldid];
                unset($sortfields[$fieldid]);
            }
        }
        $sortfields = array($fieldid => $sortdir) + $sortfields;
        $this->_filter->customsort = serialize($sortfields);

        // Get the distinct content for the group by field
        $field = $this->_df->get_field_from_id($fieldid);
        if (!$groupbyvalues = $field->get_distinct_content($sortdir)) {
            return;
        }

        // Get the displayed subset according to page         
        $numvals = count($groupbyvalues);
        // Calc number of pages
        if ($this->_filter->perpage and $this->_filter->perpage < $numvals) {
            $this->_filter->pagenum = ceil($numvals / $this->_filter->perpage);
            $this->_filter->page = $this->_filter->page % $this->_filter->pagenum;
        } else {
            $this->_filter->perpage = 0;
            $this->_filter->pagenum = 0;
            $this->_filter->page = 0;
        }
        
        if ($this->_filter->perpage) {
            $offset = $this->_filter->page * $this->_filter->perpage;
            $vals = array_slice($groupbyvalues, $offset, $this->_filter->perpage);
        } else {
            $vals = $groupbyvalues;
        }
        
        // Set the filter search criteria
        $search = array('', 'IN', $vals);
        $searchfields = array();
        if ($this->_filter->customsearch) {
            $searchfields = unserialize($this->_filter->customsearch);
        }
        if (!isset($searchfields[$fieldid]['AND'])) {
            $searchfields[$fieldid]['AND'] = array($search);
        } else {
            array_unshift($searchfields[$fieldid]['AND'], $search);
        }
        $this->_filter->customsearch = serialize($searchfields);
    }

    /**
     *
     */
    protected function is_rating() {
        global $USER, $CFG;

        require_once("$CFG->dirroot/mod/dataform/field/_rating/field_class.php");
        
        if (!$this->_df->data->rating or empty($this->_tags['field'][dataformfield__rating::_RATING])) {
            return null;
        }
        
        $ratingfield = $this->_df->get_field_from_id(dataformfield__rating::_RATING);
        $ratingoptions = new object;
        $ratingoptions->context = $this->_df->context;
        $ratingoptions->component = 'mod_dataform';
        $ratingoptions->ratingarea = 'entry';
        $ratingoptions->aggregate = $ratingfield->renderer()->get_aggregations($this->_tags['field'][dataformfield__rating::_RATING]);
        $ratingoptions->scaleid = $ratingfield->get_scaleid('entry');
        $ratingoptions->userid = $USER->id;

        return $ratingoptions;
    }
        
    /**
     *
     */
    protected function is_grading() {
        if (!$this->_df->data->grade) {
            // grading is disabled in this dataform
            return false;
        }

        if (empty($this->view->param1)) {
            // grading is not activated in this view
            return false;
        }
        
        return true;
    }
        
    /**
     *
     */
    protected function get_grading_options() {
        global $USER;

        if (!$this->_df->data->grade) {
            // TODO throw an exception
            return null;
        }

        $gradingoptions = new object;
        $gradingoptions->context = $this->_df->context;
        $gradingoptions->component = 'mod_dataform';
        $gradingoptions->ratingarea = 'activity';
        $gradingoptions->aggregate = array(RATING_AGGREGATE_MAXIMUM);
        $gradingoptions->scaleid = $this->_df->data->grade;
        $gradingoptions->userid = $USER->id;

        return $gradingoptions;
    }

    ////////////////////////////////////
    // VIEW ENTRIES
    ////////////////////////////////////
    /**
     *
     */
    public function display_entries(array $options = null) {
        global $CFG, $OUTPUT;
        
        // set display options
        $displaycontrols = isset($options['controls']) ? $options['controls'] : true;
        $tohtml = isset($options['tohtml']) ? $options['tohtml'] : false;
        $pluginfileurl = isset($options['pluginfileurl']) ? $options['pluginfileurl'] : null;

        $html = '';

        if (!$editing = $this->user_is_editing()) {
            // all _display_definition elements should be html
            $html = $this->definition_to_html();
            
            // Replace pluginfile urls if needed (e.g. in export)
            if ($pluginfileurl) {
                $pluginfilepath = moodle_url::make_file_url("/pluginfile.php", "/{$this->_df->context->id}/mod_dataform/content");
                $pattern = str_replace('/', '\/', $pluginfilepath);
                $pattern = "/$pattern\/\d+\//";
                $html = preg_replace($pattern, $pluginfileurl, $html);
            }                    

        } else {
            // prepare options for form
            $entriesform = $this->get_entries_form();
            $html = $entriesform->html();
        }
        
        // Process calculations if any
        $html = $this->process_calculations($html);
        
        if ($tohtml) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
     *
     */
    protected function process_calculations($text) {
        global $CFG;
        
        if (preg_match_all("/%%F\d*:=[^%]+%%/", $text, $matches)) {
            require_once("$CFG->libdir/mathslib.php");
            sort($matches[0]);
            $replacements = array();
            $formulas = array();
            foreach ($matches[0] as $pattern) {
                $cleanpattern = trim($pattern, '%');
                list($fid, $formula) = explode(':=', $cleanpattern, 2);
                // Process group formulas (e.g. _F1_)
                if (preg_match_all("/_F\d*_/", $formula, $frefs)) {
                    foreach ($frefs[0] as $fref) {
                        $fref = trim($fref, '_');
                        if (isset($formulas[$fref])) {
                            $formula = str_replace("_{$fref}_", implode(',', $formulas[$fref]), $formula);
                        }
                    }
                }
                isset($formulas[$fid]) or $formulas[$fid] = array();
                // Enclose formula in brackets to preserve precedence
                $formulas[$fid][] = "($formula)";
                $replacements[$pattern] = $formula;
            }

            foreach ($replacements as $pattern => $formula) {
                // Number of decimals can be set as ;n at the end of the formula
                $decimals = null;
                if (strpos($formula, ';')) {
                    list($formula, $decimals) = explode(';', $formula);
                }
            
                $calc = new calc_formula("=$formula");
                $result = $calc->evaluate();
                // false as result indicates some problem
                if ($result === false) {
                    // TODO: add more error hints
                    $replacements[$pattern] = html_writer::tag('span', $formula, array('style' => 'color:red;')); //get_string('errorcalculationunknown', 'grades');
                } else {
                    // Set decimals
                    if (is_numeric($decimals)) {
                        $result = sprintf("%4.{$decimals}f", $result);
                    }
                    $replacements[$pattern] = $result;
                }
            }
            $text = str_replace(array_keys($replacements), $replacements, $text);
        }
        return $text;
    }
            
    /**
     *
     */
    public function definition_to_form(&$mform) {
        $elements = $this->get_entries_definition();
        foreach ($elements as $element) {
            if (!empty($element)) {
                list($type, $content) = $element;
                if ($type === 'html') {
                    $mform->addElement('html', $content);
                } else {
                    list($func, $params) = $content;
                    call_user_func_array($func, array_merge(array($mform),$params));
                }
            }
        }
    }

    /**
     *
     */
    public function definition_to_html() {
        $html = '';
        $elements = $this->get_entries_definition();
        foreach ($elements as $element) {
            list(, $content) = $element;
            $html .= $content;
        }

        return $html;
    }

    /**
     *
     */
    protected function get_entries_form() {
        global $CFG;
        
        // prepare params for form
        $actionparams = array(            
            'd' => $this->_df->id(),
            'view' => $this->id(),
            'filter' => $this->_filter->id,
            'page' => $this->_filter->page,
            'eids' => $this->_filter->eids,
            'update' => $this->_editentries
        );
        $actionurl = new moodle_url("/mod/dataform/{$this->_df->pagefile()}.php", $actionparams);
        $custom_data = array(
            'view' => $this,
            'update' => $this->_editentries
        );

        $type = $this->get_entries_form_type();
        $classtype = $type ? "_$type" : '';
        $loctype = $type ? "/$type" : '';
        $formclass = 'dataformview'. $classtype. '_entries_form';
        require_once("$CFG->dirroot/mod/dataform/view". $loctype. '/view_entries_form.php');
        return new $formclass($actionurl, $custom_data);
    }

    /**
     *
     */
    protected function get_entries_form_type() {
        return '';
    }

    /**
     *
     */
    public function process_entries_data() {
        global $CFG;

        // Check first if returning from form
        $update = optional_param('update', '', PARAM_TAGLIST);
        if ($update and confirm_sesskey()) {

            // get entries only if updating existing entries
            if ($update != self::ADD_NEW_ENTRY) {
                // fetch entries
                $this->_entries->set_content();
            }

            // set the display definition for the form
            $this->_editentries = $update;
            $this->set__display_definition();

            $entriesform = $this->get_entries_form();
            
            // Process the form if not cancelled
            if (!$entriesform->is_cancelled()) {
                if ($data = $entriesform->get_data()) {
                    // validated successfully so process request
                    $processed = $this->_entries->process_entries('update', $update, $data, true);

                    if (!empty($data->submitreturnbutton)) {
                        // If we have just added new entries refresh the content
                        // This is far from ideal because this new entries may be
                        // spread out in the form when we return to edit them
                        if ($this->_editentries < 0) {
                            $this->_entries->set_content();
                        }                        

                        // so that return after adding new entry will return the added entry 
                        $this->_editentries = implode(',',$processed[1]);
                        $this->_returntoentriesform = true;
                        return true;
                    } else {
                        // So that we can show the new entries if we so wish
                        if ($this->_editentries < 0) {
                            $this->_editentries = implode(',',$processed[1]);
                        } else {
                            $this->_editentries = '';
                        }
                        $this->_returntoentriesform = false;
                        return $processed;
                    }
                } else {
                    // form validation failed so return to form
                    $this->_returntoentriesform = true;
                    return false;
                }
            }
        }


        // direct url params; not from form
        $new = optional_param('new', 0, PARAM_INT);               // open new entry form
        $editentries = optional_param('editentries', 0, PARAM_SEQUENCE);        // edit entries (all) or by record ids (comma delimited eids)
        $duplicate = optional_param('duplicate', '', PARAM_SEQUENCE);    // duplicate entries (all) or by record ids (comma delimited eids)
        $delete = optional_param('delete', '', PARAM_SEQUENCE);    // delete entries (all) or by record ids (comma delimited eids)
        $approve = optional_param('approve', '', PARAM_SEQUENCE);  // approve entries (all) or by record ids (comma delimited eids)
        $disapprove = optional_param('disapprove', '', PARAM_SEQUENCE);  // disapprove entries (all) or by record ids (comma delimited eids)
        $append = optional_param('append', '', PARAM_SEQUENCE);  // append entries (all) or by record ids (comma delimited eids)

        $confirmed = optional_param('confirmed', 0, PARAM_BOOL);

        $this->_editentries = $editentries;

        // Prepare open a new entry form
        if ($new and confirm_sesskey()) {
            $this->_editentries = -$new;
        // Duplicate any requested entries
        } else if ($duplicate and confirm_sesskey()) {
            return $this->_entries->process_entries('duplicate', $duplicate, null, $confirmed);
        // Delete any requested entries
        } else if ($delete and confirm_sesskey()) {
            return $this->_entries->process_entries('delete', $delete, null, $confirmed);
        // Approve any requested entries
        } else if ($approve and confirm_sesskey()) {
            return $this->_entries->process_entries('approve', $approve, null, true);
        // Disapprove any requested entries
        } else if ($disapprove and confirm_sesskey()) {
            return $this->_entries->process_entries('disapprove', $disapprove, null, true);
        // Append any requested entries to the initiating entry
        } else if ($append and confirm_sesskey()) {
            return $this->_entries->process_entries('append', $append, null, true);
        }

        return true;
    }

    /**
     *
     */
    protected function set__display_definition(array $options = null) {

        $this->_display_definition = array();
        // Indicate if there are managable entries in the display for the current user
        // in which case edit/delete action 
        $requiresmanageentries = false;

        $editentries = null;

        // Display a new entry to add in its own group
        if ($this->_editentries < 0) {
            // TODO check how many entries left to add
            if ($this->_df->user_can_manage_entry()) {
                $this->_display_definition['newentry'] = array();
                for ($i = -1; $i >= $this->_editentries; $i--) {
                    $this->_display_definition['newentry'][$i] = null;
                }
            }
        } else if ($this->_editentries) {
            $editentries = explode(',', $this->_editentries);
        }
        
        // compile entries if any
        if ($entries = $this->_entries->entries()) {
            $groupname = '';
            $groupdefinition = array();

            // If action buttons should be hidden entries should unmanageable
            $displayactions = isset($options['entryactions']) ? $options['entryactions'] : true;
            foreach ($entries as $entryid => $entry) {
               // Is this entry edited
               $editthisone = $editentries ? in_array($entryid, $editentries) : false;
               // Set a flag if we are editing any entries
               $requiresmanageentries = $editthisone ? true : $requiresmanageentries;
               // Calculate manageability for this entry only if action buttons can be displayed and we're not already editing it
               $managable = false;
               if ($displayactions and !$editthisone) {
                    $managable = $this->_df->user_can_manage_entry($entry);
                }

                // Are we grouping?
                if ($this->_filter->groupby) {
                    // TODO assuming here that the groupbyed field returns only one pattern
                    $groupbyvalue = $this->get_groupby_value($entry);
                    if ($groupbyvalue != $groupname) {
                        // Compile current group definitions
                        if ($groupname) {
                            // Add the group entries definitions
                            $this->_display_definition[$groupname] = $groupdefinition;
                            $groupdefinition = array();
                        }
                        // Reset group name
                        $groupname = $groupbyvalue;
                    }
                }

                // add to the current entries group
                $groupdefinition[$entryid] = array($entry, $editthisone, $managable);

            }
            // collect remaining definitions (all of it if no groupby)
            $this->_display_definition[$groupname] = $groupdefinition;
        }
        return $requiresmanageentries;
    }

    /**
     *
     */
    public function user_is_editing() {
        $editing = $this->_editentries;
        //$multiactions = $this->uses_multiactions();

        //if (!$editing and (!$multiactions or ($multiedit and !$this->entriesfiltercount))) {
        //    return false;

        //} else if ($editing) {
        //    return $editing;

        //} else {
        //    return true;
        //}
        return $editing;
    }
}