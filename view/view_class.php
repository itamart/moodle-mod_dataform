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
class dataform_view_base {

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
    public function __construct($df = 0, $view = 0) {
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
            $this->view->groupby = 0;
        }

        // set editors and patterns
        $this->set__editors();
        $this->set__patterns();

        // filter
        $filterid = optional_param('filter', 0, PARAM_INT);
        $eids = optional_param('eids', 0, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $this->set_filter($filterid, $eids, $page);

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
        $this->view->groupby = !empty($data->groupby) ? $data->groupby : 0;
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

        $formclass = 'mod_dataform_view_'. $this->type. '_form';
        $formparams = array(
            'd' => $this->_df->id(),
            'vedit' => $this->id(),
            'type' => $this->type
        );
        $actionurl = new moodle_url('/mod/dataform/view/view_edit.php', $formparams);
        $custom_data = array('view' => $this, 'df' => $this->_df);
                                    
        require_once($CFG->dirroot. '/mod/dataform/view/'. $this->type. '/view_form.php');
        return new $formclass($actionurl, $custom_data);
    }

    /**
     * prepare view data for form
     */
    public function to_form() {
        $data = $this->view;
        $editors = $this->editors();

        $i = 0;
        foreach ($editors as $editorname => $options) {
             $data = file_prepare_standard_editor($data,
                                                "e$editorname",
                                                $options,
                                                $this->_df->context,
                                                'mod_dataform',
                                                'view',
                                                $this->view->id. $i);
            $i++;
        }

        return $data;
    }

    /**
     * prepare view data for form
     */
    public function from_form($data) {
        $editors = $this->editors();

        $i = 0;
        foreach ($editors as $editorname => $options) {
            $data = file_postupdate_standard_editor($data,
                                                    "e$editorname",
                                                    $options,
                                                    $this->_df->context,
                                                    'mod_dataform',
                                                    'view',
                                                    $this->view->id. $i);
            $i++;
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

        if ($processedeids and $this->_editentries and !$this->_returntoentriesform) {
            $this->_filter->eids = $this->_editentries;
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
    public function display(array $params = null) {
        global $OUTPUT;

        // set display params
        $displayentries = isset($params['entries']) ? $params['entries'] : true;
        $displaycontrols = isset($params['controls']) ? $params['controls'] : true;
        $notify = isset($params['notify']) ? $params['notify'] : true;
        $tohtml = isset($params['tohtml']) ? $params['tohtml'] : false;
        $pluginfileurl = isset($params['pluginfileurl']) ? $params['pluginfileurl'] : null;

        // rewrite plugin urls
        foreach ($this->_editors as $key => $editorname) {
            $editor = "e$editorname";

            // export with files should provide the file path
            if ($pluginfileurl) {
                $this->view->$editor = str_replace('@@PLUGINFILE@@/', $pluginfileurl, $this->view->$editor);
            } else {
                $this->view->$editor = file_rewrite_pluginfile_urls($this->view->$editor,
                                                                            'pluginfile.php',
                                                                            $this->_df->context->id,
                                                                            'mod_dataform',
                                                                            'view',
                                                                            $this->id(). $key);
            }
        }

        // build entries display definition
        $requiresmanageentries = $this->set__display_definition();

        // set view specific tags
        $options = array();
        $options['entriescount'] = $this->_entries->get_count();
        $options['entriesfiltercount'] = $this->_entries->get_count(true);
        // adding one or more new entries
        if ($this->user_is_editing()) {
            $options['hidenewentry'] = 1;
        }
        // editing one or more new entries
        if ($requiresmanageentries) {
            $options['showentryactions'] = 1;
        }
        $this->set_view_tags($options);

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
        if ($tohtml) {
            $html = $notifications;
            $html .= $displaycontrols ? $this->print_before() : '';
            if ($displayentries) {
                $html .= $this->display_entries($params);
            }
            $html .= $displaycontrols ? $this->print_after() : '';
            return html_writer::tag('div', $html, array('class' => $viewname));
        } else {
            echo html_writer::start_tag('div', array('class' => $viewname));
            echo $notifications;
            echo ($displaycontrols ? $this->print_before() : '');
            if ($displayentries) {
                $this->display_entries($params);
            }
            echo ($displaycontrols ? $this->print_after() : '');
            echo html_writer::end_tag('div');
        }
    }

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
    public function get_views($exclude = null, $menu = false) {
        $views = $this->_df->get_views($exclude);

        $visible = array();
        $halfvisible = array();
        $hidden = array();
        if ($menu) {
            // mark/remove the half and non visible views
            foreach ($views as $vid => $view){
                if ($view->view->visible < self::VISIBLE) {
                    if (has_capability('mod/dataform:managetemplates', $this->_df->context)) {
                        if ($view->view->visible) {
                            $halfvisible[$vid] = "({$view->view->name})";
                        } else {
                            $hidden[$vid] = "-{$view->view->name}-";
                        }
                    }
                } else {
                    $visible[$vid] = $view->view->name;
                }
            }
            empty($visible) or asort($visible);
            empty($halfvisible) or asort($halfvisible);
            empty($hidden) or asort($hidden);
            $views = $visible + $halfvisible + $hidden;
        }

        return $views;
    }

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
    public function is_caching() {
        return false;
    }

    /**
     *
     */
    public function set_filter($fid = 0, $eids = null, $page = 0) {
        // set filter
        $filter = $this->filter_options();
        if (!$filterid = $filter['filterid']) {
            $filterid = $fid;
        }
        $this->_filter = $this->_df->get_filter_manager()->get_filter_from_id($filterid);
        // set specific entry id, if requested
        $this->_filter->eids = $eids;
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
    }

    /**
     *
     */
    public function is_forcing_filter() {
        return $this->view->filter;
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
    public function get_filters($exclude = null, $menu = false, $forceget = false) {
        return $this->_df->get_filter_manager()->get_filters($exclude, $menu, $forceget);
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
    public function get_baseurl() {
        return $this->_baseurl;
    }

    /**
     *
     */
    public function field_tags() {
        $patterns = array();
        if ($fields = $this->_df->get_fields()) {
            foreach ($fields as $field) {
                if ($fieldpatterns = $field->patterns()->get_menu()) {
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
        if (!$this->_patterns) {
            require_once('view_patterns.php');
            $this->_patterns = new mod_dataform_view_patterns($this);
        }
        return $this->_patterns;
    }

    /**
     *
     */
    public function set_view_tags($options) {
        $tags = $this->_tags['view'];
        $replacements = $this->patterns()->get_replacements($tags, null, $options);
        $this->view->esection = str_replace($tags, $replacements, $this->view->esection);
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
                    if (array_intersect($tags, $fields[$fieldid]->patterns()->pluginfile_patterns())) {
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
    public function get_entries_definition($display_definition) {

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
                        list($format, $trust, $text) = array(FORMAT_HTML, 0, $editordata);
                    }
                } else {
                    list($format, $trust, $text) = array(FORMAT_HTML, 0, '');
                }
                $this->view->{"e$editor"} = $text;
                $this->view->{"e$editor".'format'} = $format;
                $this->view->{"e$editor".'trust'} = $trust;

            // view from form or editor areas updated
            } else {
                $text = !empty($data->{"e$editor"}) ? $data->{"e$editor"} : '';
                $format = !empty($data->{"e$editor".'format'}) ? $data->{"e$editor".'format'} : FORMAT_HTML;
                $trust = !empty($data->{"e$editor".'trust'}) ? $data->{"e$editor".'trust'} : 0;

                // replace \n in non text format
                if ($format != FORMAT_PLAIN) {
                    $text = str_replace("\n","",$text);
                }

                if (!empty($text)) {
                    $this->view->{$editor} = "ft:{$format}tr:{$trust}ct:$text";
                } else {
                    $this->view->{$editor} = null;
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
                if ($patterns = $this->patterns()->search($text)) {
                    $this->_tags['view'] = $patterns;
                }
                if ($fields = $this->_df->get_fields()) {
                    foreach ($fields as $fieldid => $field) {
                        if ($patterns = $field->patterns()->search($text)) {
                            $this->_tags['field'][$fieldid] = $patterns;
                        }
                    }
                }

            }
            $this->view->patterns = serialize($this->_tags);
        }
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
            if ($definition = $field->get_definitions(array($pattern), $entry)) {
               $groupbyvalue = $definition[$pattern][1];
            }
        }

        return $groupbyvalue;
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

    /**
     * Set sort and search criteria for grouping by
     */
    protected function set_groupby_per_page() {
        global $CFG;

        // Get the group by fieldid
        if (!$fieldid = $this->_filter->groupby) {
            return;
        }
        
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
        global $USER;

        if (!$this->_df->data->rating or empty($this->_tags['field'][dataform::_RATING])) {
            return null;
        }
        
        $ratingfield = $this->_df->get_field_from_id(dataform::_RATING);
        $ratingoptions = new object;
        $ratingoptions->context = $this->_df->context;
        $ratingoptions->component = 'mod_dataform';
        $ratingoptions->ratingarea = 'entry';
        $ratingoptions->aggregate = $ratingfield->patterns()->get_aggregations($this->_tags['field'][dataform::_RATING]);
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

    /**
     *
     */
    public function display_entries(array $params = null) {
        global $CFG, $OUTPUT;
        
        // set display params
        $displaycontrols = isset($params['controls']) ? $params['controls'] : true;
        $tohtml = isset($params['tohtml']) ? $params['tohtml'] : false;
        $pluginfileurl = isset($params['pluginfileurl']) ? $params['pluginfileurl'] : null;

        $html = '';

        if (!$editing = $this->user_is_editing()) {
            // all _display_definition elements should be html
            $html = $this->definition_to_html();
            
            // replace pluginfile urls if needed (e.g. in export)
            if ($pluginfileurl) {
                $pluginfilepath = new moodle_url("/pluginfile.php/{$this->_df->context->id}/mod_dataform/content");
                $pattern = str_replace('/', '\/', $pluginfilepath);
                $pattern = "/$pattern\/\d+\//";
                $html = preg_replace($pattern, $pluginfileurl, $html);
            }                    

        } else {
            // prepare params for form
            $entriesform = $this->get_entries_form();
            $html = $entriesform->html();
        }
        
        if ($tohtml) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
     *
     */
    public function definition_to_form(&$mform) {
        $elements = $this->get_entries_definition($this->_display_definition);

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
        $elements = $this->get_entries_definition($this->_display_definition);
        // if $mform is null, simply echo/return html string
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
        );
        $actionurl = new moodle_url("/mod/dataform/{$this->_df->pagefile()}.php", $actionparams);
        $custom_data = array(
            'view' => $this,
            'update' => $this->_editentries
        );

        $type = $this->get_entries_form_type();
        $classtype = $type ? "_$type" : '';
        $loctype = $type ? "/$type" : '';
        $formclass = 'mod_dataform_view'. $classtype. '_entries_form';
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

        // check first if returning from form
        $update = optional_param('update', '', PARAM_TAGLIST);
        $cancel = optional_param('cancel', 0, PARAM_BOOL);
        if (!$cancel and $update and confirm_sesskey()) {

            // get entries only if updating existing entries
            if ($update != self::ADD_NEW_ENTRY) {
                // fetch entries
                $this->_entries->set_content();
            }

            // set the display definition for the form
            $this->_editentries = $update;
            $this->set__display_definition();

            $entriesform = $this->get_entries_form();
            // we already know that it isn't cancelled
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

    /**
     *
     */
    protected function set__display_definition() {

        $this->_display_definition = array();
        // Indicate if there managable entries in the display for the current user
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

            foreach ($entries as $entryid => $entry) {
               $editthisone = false;
               if ($managable = $this->_df->user_can_manage_entry($entry)) {
                    if ($editentries) {
                        $requiresmanageentries = true;
                        $editthisone = in_array($entryid, $editentries);
                    }
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
}