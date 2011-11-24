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
 * certain copyrights on the Database module may obtain.
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
 * A template for a standard display of dataform entries and base class for specialized display templates
 * (see view/<view type>/view_class.php)
 *
 */

class dataform_view_base {

    const VISIBLE = 2;      // the view can be seen and used by everyone
    const HIDDEN = 1;       // the view can be used by everyone but seen only by managers
    const DISABLED = 0;     // the view can be used and seen only by managers

    protected $type = 'unknown';      // Subclasses must override the type with their name

    public $view = NULL;            // The view object itself, if we know it

    protected $_df = NULL;           // The dataform object that this view belongs to
    protected $_filter = null;
    
    protected $_editors = array('section');
    protected $_patterns = array();
    protected $_entries = null;

    protected $_baseurl = '';

    /**
     * Constructor
     * View or dataform or both, each can be id or object
     */
    public function __construct($df = 0, $view = 0) {
        global $DB, $CFG, $SESSION;

        if (empty($df)) {
            print_error('Programmer error: You must specify dataform id or object when defining a field class. ');
        } else if (is_object($df)) {
            $this->_df = $df;
        } else {    // dataform id
            $this->_df = new dataform($df);
        }

        // set existing view
        if (!empty($view)) {
            if (is_object($view)) {
                $this->view = $view;  // Programmer knows what they are doing, we hope
            } else if (!$this->view = $DB->get_record('dataform_views', array('id' => $view))) {
                print_error('Bad view ID encountered: '.$view);
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
        $entryids = optional_param('eid', 0, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $this->set_filter($filterid, $entryids, $page);           

        // base url params
        $baseurlparams = array();
        $baseurlparams['d'] = $this->_df->id();
        $baseurlparams['view'] = $this->id();
        $baseurlparams['filter'] = $this->_filter->id;
        if ($this->_filter->page) {
            $baseurlparams['page'] = $this->_filter->page;
        }
        if ($this->_df->currentgroup) {
            $baseurlparams['currentgroup'] = $this->_df->currentgroup;
        }

        $this->_baseurl = new moodle_url('/mod/dataform/view.php', $baseurlparams);

        // TODO: should this be here?
        $this->set_groupby_per_page();

        // TODO
        require_once("$CFG->dirroot/mod/dataform/entries_class.php");
        $this->_entries = new dataform_entries($this->_df, $this, $this->_filter);
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
    public function set_filter($fid = 0, $eids = null, $page = 0) {
        // set filter
        $filter = $this->filter_options();
        if (!$filterid = $filter['filterid']) {
            $filterid = $fid;
        }
        $this->_filter = $this->_df->get_filter_from_id($filterid);
        // set specific entry id, if requested
        $this->_filter->eid = $eids;
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

        $this->_entries->process_data();
        
        return true;
    }

    /**
     *
     */
    public function set_content() {
        // set entries content
        $options = new object;
        // do we need ratings?
        if ($ratingoptions = $this->uses_ratings('entry')) {            
            $options->ratings = $ratingoptions;
        }
        // do we need comments?
        
        $this->_entries->set_content($options);        
    }

    /**
     *
     */
    public function display(array $params = null) {
        global $CFG, $USER;
        
        // set display params
        $displaycontrols = isset($params['controls']) ? $params['controls'] : true;
        $tohtml = isset($params['tohtml']) ? $params['tohtml'] : false;
        $pluginfileurl = isset($params['pluginfileurl']) ? $params['pluginfileurl'] : null;

        // rewrite plugin urls
        foreach ($this->_editors as $key => $editorname) {
            $editor = "e$editorname";
            
            // export with files should provide the file path  
            if ($pluginfileurl) {
                $this->view->{$editor} = str_replace('@@PLUGINFILE@@/', $pluginfileurl, $this->view->{$editor});
            } else {
                $this->view->{$editor} = file_rewrite_pluginfile_urls($this->view->{$editor},
                                                                            'pluginfile.php',
                                                                            $this->_df->context->id,
                                                                            'mod_dataform',
                                                                            'view',
                                                                            $this->id(). $key);
            }
        }

        // set view specific tags
        $options = array();
        $options['entriescount'] = $this->_entries->count();
        $options['entriesfiltercount'] = $this->_entries->filter_count();
        if ($this->_entries->user_is_editing()) {  // adding one or more new entries
            $options['hidenewentry'] = 1;
        }
        $this->set_view_tags($options);
        
        // print view
        if ($tohtml) {
            $html = $displaycontrols ? $this->print_before() : '';
            $html .= $this->_entries->display($params);
            $html .= $displaycontrols ? $this->print_after() : '';
            return $html;
        } else {
            echo ($displaycontrols ? $this->print_before() : '');
            $this->_entries->display($params);
            echo ($displaycontrols ? $this->print_after() : '');
        }

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
     * TODO
     */
    public function get_fields($exclude = null, $menu = false) {
        return $this->_df->get_fields($exclude, $menu);
    }

    /**
     * TODO
     */
    public function get_views($exclude = null, $menu = false) {
        $views = $this->_df->get_views($exclude);
        
        if ($menu) {
            // mark/remove the half and non visible views
            foreach ($views as $vid => $view){
                if ($view->view->visible < self::VISIBLE) {
                    if (has_capability('mod/dataform:managetemplates', $this->_df->context)) {
                        $enclose = $view->view->visible ? '(' : '-';
                        $declose = $view->view->visible ? ')' : '-';
                        $views[$vid] = $enclose. $view->view->name. $declose;
                    } else {
                        unset($views[$vid]);
                    }
                } else {
                    $views[$vid] = $view->view->name;               
                }
            }
        }
        
        return $views;        
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
     *
     */
    public function is_caching() {
        return false;
    }

    /**
     *
     */
    public function general_tags() {
        return $this->patterns();
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
        if ($fields = $this->get_fields()) {
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
    public function get_form() {
        global $CFG;

        $custom_data = array('view' => $this,
                            'df' => $this->_df);
        require_once($CFG->dirroot. '/mod/dataform/view/'. $this->type. '/view_form.php');
        $formclass = 'mod_dataform_view_'. $this->type. '_form';
        $actionurl = new moodle_url('/mod/dataform/view/view_edit.php',
                                    array('d' => $this->_df->id(), 'vedit' => $this->id(), 'type' => $this->type)); 
        return new $formclass($actionurl, $custom_data);
    }

    /**
     * 
     */
    public function generate_default_view() {
    }   

    /**
     *
     */
    public function filter() {
        return $this->view->filter;
    }

    /**
     *
     */
    public function get_filters($exclude = null, $menu = false, $forceget = false) {
        return $this->_df->get_filters($exclude, $menu, $forceget);
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
    public function patterns_exist($text) {
        $usedpatterns = array();
        // all this nasty nesting due to Moodle implementation of nested select
        foreach ($this->patterns() as $patternset) {
            foreach ($patternset as $patterns) {
                foreach ($patterns as $pattern) {
                    if (strpos($text, $pattern)) {
                        $usedpatterns[] = $pattern;
                    }
                }
            }
        }
        
        return $usedpatterns;
    }

    /**
     *
     */
    public function set_view_tags($params) {
        $tags = $this->_patterns['view'];
        $replacements = $this->get_tags_replacements($this->patterns($tags, $params));
        
        $this->view->esection = str_replace($tags, $replacements, $this->view->esection);

    }

    /**
     *
     */
    public function get__patterns($set = null) {
        if (is_null($set)) {
            return $this->_patterns;
        } else if ($set == 'view' or $set == 'field') {
            return $this->_patterns[$set];
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
            if (!empty($this->_patterns['field'])) {
                $fields = $this->_df->get_fields();
                foreach ($this->_patterns['field'] as $fieldid => $tags) {
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
     *
     */
    public function group_entries_definition($entriesset, $name = '') {
        return false;
    }

    /**
     *
     */
    protected function set__editors($data = null) {
        foreach ($this->_editors as $editor) {
            // new view or from DB so add editor fields 
            if (is_null($data)) {
            //echo "$editor: ". strlen($this->view->{$editor}). '<br />';
                if (!empty($this->view->{$editor})) {
                    list($text, $format, $trust) = unserialize($this->view->{$editor});                    
                } else {
                    list($text, $format, $trust) = array('',FORMAT_HTML,0);
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
                
                $this->view->{$editor} = serialize(array($text, $format, $trust));
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
                $this->_patterns = unserialize($this->view->patterns);
            } else {
                $this->_patterns = array('view' => array(), 'field' => array());
            }

        // view from form or editor areas updated
        } else {
            $this->_patterns = array('view' => array(), 'field' => array());
            $text = '';
            foreach ($this->_editors as $editor) {
                $text .= !empty($data->{"e$editor"}) ? ' '. $data->{"e$editor"} : '';
            }

            if (trim($text)) {
                if ($patterns = $this->patterns_exist($text)) {
                    $this->_patterns['view'] = $patterns;
                }
                if ($fields = $this->_df->get_fields()) {
                    foreach ($fields as $fieldid => $field) {
                        if ($patterns = $field->patterns()->search($text)) {
                            $this->_patterns['field'][$fieldid] = $patterns;
                        }
                    }
                }
                
            }
            $this->view->patterns = serialize($this->_patterns);
        }
    }
        
    /**
     * @param array $patterns array of arrays of pattern replacement pairs
     */
    protected function split_tags($patterns, $subject) {
        $delims = implode('|', $patterns);
        // escape [ and ]
        $delims = str_replace(array('[', ']'), array('\[', '\]'), $delims);
        
        $elements = preg_split("/($delims)/", $subject, null, PREG_SPLIT_DELIM_CAPTURE);

        return $elements;
    }

    /**
     * @param array $patterns array of arrays of pattern replacement pairs
     */
    protected function get_tags_replacements($patterns) {
        $replacements = array();
        foreach ($patterns as $tag => $val) {
            $replacements[$tag] = $val;
        }

        return $replacements;
    }

    /**
     *
     */
    protected function patterns($tags = null, $params = null) {
        global $CFG, $OUTPUT;

        $menus = array(
            '##viewsmenu##',
            '##filtersmenu##'
        );
        
        $userpref = array(
            '##quicksearch##',
            '##quickperpage##',
            '##quickreset##'
        );
        
        $generalactions = array(
            '##addnewentry##',
            '##addnewentries##',
            '##selectallnone##',
            '##multiduplicate##',
            '##multiedit##',
            '##multiedit:icon##',
            '##multidelete##',
            '##multidelete:icon##',
            '##multiapprove##',
            '##multiapprove:icon##',
            '##multiexport##',
            '##multiexport:icon##',
            '##multiexport:csv##',
            '##multiimport##',
            '##multiimporty:icon##',
        );
        
        // if no tags are requested, return select menu
        if (is_null($tags)) {
            $patterns = array('menus' => array('menus' => array()),
                                'userpref' => array('userpref' => array()), 
                                'generalactions' => array('generalactions' => array()));
            // TODO use get strings
            foreach ($menus as $pattern) {
                $patterns['menus']['menus'][$pattern] = $pattern;
            }

            foreach ($userpref as $pattern) {
                $patterns['userpref']['userpref'][$pattern] = $pattern;
            }

            foreach ($generalactions as $pattern) {
                $patterns['generalactions']['generalactions'][$pattern] = $pattern;
            }
            
        } else {      
                
            $patterns = array();
            $filter = $this->_filter;
            $baseurl = htmlspecialchars_decode($this->_baseurl. '&sesskey='. sesskey());
            // TODO: move to a view attribute so as to call only once
            $usercanaddentries = $this->_df->user_can_manage_entry();
            
            foreach ($tags as $tag) {
                if (in_array($tag, $menus)) {
                    switch ($tag) {
                        case '##viewsmenu##':
                            $patterns['##viewsmenu##'] = $this->print_views_menu($filter, true);
                            break;
                            
                        case '##filtersmenu##':
                            if ($this->view->filter or (!$filter->id and empty($params['entriescount']))) {
                                $patterns['##filtersmenu##'] = '';
                            } else {
                                $patterns['##filtersmenu##'] = $this->print_filters_menu($filter, true);
                            }                            
                            break;
                    }
                    
                } else if (in_array($tag, $userpref)) {
                    if ($this->view->filter or (!$filter->id and empty($params['entriescount']))) {
                        $patterns[$tag] = '';
                    } else {
                        switch ($tag) {
                            case '##quicksearch##':
                                $patterns['##quicksearch##'] = $this->print_quick_search($filter, true);
                                break;
                                
                            case '##quickperpage##':
                                $patterns['##quickperpage##'] = $this->print_quick_perpage($filter, true);
                                break;
                                
                        }                               
                    }
                    
                } else if (in_array($tag, $generalactions)) {
                    switch ($tag) {
                        case '##addnewentry##':
                        case '##addnewentries##':
                            if (isset($params['hidenewentry']) or !$usercanaddentries) {
                                $patterns[$tag] = '';
                            } else {
                                if ($tag == '##addnewentry##') {
                                    $patterns[$tag] =
                                        html_writer::link($baseurl. '&new=1', get_string('entryaddnew', 'dataform'));
                                } else {
                                    $range = range(1, 20);
                                    $options = array_combine($range, $range);
                                    $select = new single_select(new moodle_url($baseurl), 'new', $options, null, array(0 => get_string('dots', 'dataform')), 'newentries_jump');
                                    $select->set_label(get_string('entryaddmultinew','dataform'). '&nbsp;');
                                    $patterns[$tag] = $OUTPUT->render($select);
                                }                                
                            }
                            break;
                            
                        case '##multiduplicate##':
                            $patterns['##multiduplicate##'] =
                                html_writer::empty_tag('input',
                                                        array('type' => 'button',
                                                                'name' => 'multiduplicate',
                                                                'value' => get_string('multiduplicate', 'dataform'),
                                                                'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44; \'duplicate\')'));
                            break;
                            
                        case '##multiedit##':
                            $patterns['##multiedit##'] =
                                html_writer::empty_tag('input',
                                                        array('type' => 'button',
                                                                'name' => 'multiedit',
                                                                'value' => get_string('multiedit', 'dataform'),
                                                                'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44; \'editentries\')'));
                            break;
                            
                        case '##multiedit:icon##':
                            $patterns['##multiedit:icon##'] = 
                                html_writer::tag('button', 
                                            $OUTPUT->pix_icon('t/edit', get_string('multiedit', 'dataform')), 
                                            array('name' => 'multiedit',
                                                   'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44; \'editentries\')'));
                            break;
                            
                        case '##multidelete##':
                            $patterns['##multidelete##'] =
                                html_writer::empty_tag('input',
                                                        array('type' => 'button',
                                                                'name' => 'multidelete',
                                                                'value' => get_string('multidelete', 'dataform'),
                                                                'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44; \'delete\')'));
                            break;
                            
                        case '##multidelete:icon##':
                            $patterns['##multidelete:icon##'] =
                                html_writer::tag('button', 
                                            $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), 
                                            array('name' => 'multidelete',
                                                   'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44; \'delete\')'));
                            break;
                            
                        case '##multiapprove##':
                        case '##multiapprove:icon##':
                            if ($this->_df->data->approval and has_capability('mod/dataform:approve', $this->_df->context)) {
                                if ($tag == '##multiapprove##') {
                                    $patterns['##multiapprove##'] = 
                                        html_writer::empty_tag('input',
                                                                array('type' => 'button',
                                                                        'name' => 'multiapprove',
                                                                        'value' => get_string('multiapprove', 'dataform'),
                                                                        'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44; \'approve\')'));
                                } else {
                                    $patterns['##multiapprove:icon##'] =                       
                                        html_writer::tag('button',
                                                    $OUTPUT->pix_icon('i/tick_green_big', get_string('multiapprove', 'dataform')), 
                                                    array('name' => 'multiapprove',
                                                           'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44; \'approve\')'));
                                }
                            } else {
                                $patterns[$tag] = '';
                            }
                            break;
                            
                        case '##multiexport##':
                            $buttonval = get_string('multiexport', 'dataform');
                        case '##multiexport:icon##':
                            $buttonval = !isset($buttonval) ? $OUTPUT->pix_icon('t/portfolioadd', get_string('multiexport', 'dataform')) : $buttonval;
                        case '##multiexport:csv##':
                            $format = !isset($format) ? 'spreadsheet' : $format;
                            $buttonval = !isset($buttonval) ? $OUTPUT->pix_icon('f/ods', get_string('multiexport', 'dataform')) : $buttonval;
         
                            if (!empty($CFG->enableportfolios)) {
                                if (!empty($format)) {
                                    $baseurl = "$baseurl&format=$format";
                                }
                                //list(,$ext,) = explode(':', $tag);
                                $patterns[$tag] =                       
                                    html_writer::tag('button', 
                                            $buttonval, 
                                            array('name' => 'multiexport',
                                                   'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44; \'export\'&#44;-1)'));
                            } else {                       
                                $patterns[$tag] = ''; 
                            }
                            break;
                            
                        case '##selectallnone##':
                            $patterns['##selectallnone##'] = 
                                    html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'entry\'&#44;this.checked)'));
                                                                                    
                            break;
                    }        
                }
            }            
        }
        $patterns = array_merge_recursive($patterns, $this->paging_bar_patterns($tags, $params));

        return $patterns;
    }

    /**
     *
     */
    protected function paging_bar_patterns($tags = null,$params = null) {
        global $OUTPUT;
        
        // if no tags are requested, return select menu
        if (is_null($tags)) {
            $patterns = array('pagingbar' => array('pagingbar' => array()));
            // TODO use get strings
            $patterns['pagingbar']['pagingbar']['##pagingbar##'] = '##pagingbar##';
           

        } else {
            $patterns = array();
            $baseurl = htmlspecialchars_decode($this->_baseurl);
            $pagingbar = '';
        
            foreach ($tags as $tag) {
                if ($tag == '##pagingbar##') {
                    $filter = $this->_filter;
                    
                    // typical entry 'more' request. If not single view show return to list instead of paging bar
                    if (!empty($filter->eid) and !empty($filter->perpage) and $filter->perpage != 1) {
                        $page = $filter->page ? '&page='.$filter->page : '';
                        $pagingbar = html_writer::link($baseurl. $page, get_string('viewreturntolist', 'dataform'));

                    // typical groupby, one group per page case. show paging bar as per number of groups
                    } else if (isset($this->_filter->pagenum)) {
                        $pagingbar = new paging_bar($filter->pagenum,
                                                    $filter->page,
                                                    1,
                                                    $this->_baseurl. '&amp;',
                                                    'page',
                                                    '',
                                                    true);
                     // standard paging bar case
                    } else if (isset($params['entriescount']) and isset($params['entriesfiltercount'])
                                and $params['entriescount'] != $params['entriesfiltercount']) {
                        $pagingbar = new paging_bar($params['entriesfiltercount'],
                                                    $filter->page,
                                                    $filter->perpage,
                                                    $this->_baseurl. '&amp;',
                                                    'page',
                                                    '',
                                                    true);
                    }
                    
                    break;
                } 
            }

            if ($pagingbar instanceof paging_bar) {
               $patterns['##pagingbar##'] = $OUTPUT->render($pagingbar);
            } else {
               $patterns['##pagingbar##'] = $pagingbar;
            }
                    
            
            
        }

        return $patterns;
    }

    /**
     * @param array $entriesset entryid => array(entry, edit, editable)
     */
    protected function get_entries_definition($entriesset, $name = '') {
        
        $definition = array();

        if ($name == 'newentry') {
            foreach ($entriesset as $entryid => $entryparams) {
                $definition[$entryid] = $this->new_entry_definition();            
            }
            
        } else {
            foreach ($entriesset as $entryid => $entryparams) {
                list($entry, $editthisone, $editable) = $entryparams;
                $fielddefinitions = $this->get_field_definitions($entry, $editthisone, $editable);
                $definition[$entryid] = $this->entry_definition($fielddefinitions);            
            }
        }
        
        return $definition;
    }

    /**
     *
     */
    protected function get_field_definitions($entry, $editthisone, $editable) {
        $fields = $this->_df->get_fields();
        $entry->baseurl = $this->_baseurl;

        $definitions = array();        
        foreach ($this->_patterns['field'] as $fieldid => $patterns) {
            $field = $fields[$fieldid];
if (!is_object($field->patterns())) {
echo $field->name(). "<br /";
}
            if ($fielddefinitions = $field->patterns()->get_replacements($patterns, $entry, $editthisone, $editable)) {
                $definitions = array_merge($definitions, $fielddefinitions);
                
                // TODO: $replacement[] = highlight($search, $field->display_browse($entry->id, $view));
            }
        }
        
        return $definitions;
    }
    
    /**
     *
     */
    protected function new_entry_definition() {
        return false;
    }

    /**
     *
     */
    protected function entry_definition($fielddefinitions) {
        return false;
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
        
        //foreach ($this->get_fields() as $field) {
        //    $str .= $field->print_after_form();
        //}

        return $str;
    }

    /**
     *
     */
    protected function print_views_menu($filter, $return = false) {
        global $DB, $OUTPUT;

        $viewjump = '';
        
        if ($menuviews = $this->get_views(null, true)) {

            // Display the view form jump list
            $baseurl = '/mod/dataform/view.php';
            $baseurlparams = array('d' => $this->_df->id(),
                                    'sesskey' => sesskey(),
                                    'filter' => $filter->id);
            $viewselect = new single_select(new moodle_url($baseurl, $baseurlparams), 'view', $menuviews, $this->view->id, array(''=>'choosedots'), 'viewbrowse_jump');
            $viewselect->set_label(get_string('viewcurrent','dataform'). '&nbsp;');
            $viewjump = $OUTPUT->render($viewselect);
            //$OUTPUT->help_icon('fieldadd', 'dataform').
        }
        
        if ($return) {
            return $viewjump;
        } else {
            echo $viewjump;
        }       
    }

    /**
     *
     */
    protected function print_filters_menu($filter, $return = false) {
        global $OUTPUT;

        $filterjump = '';
        
        $menufilters = $this->get_filters(null, true);

        // TODO check session 
        $menufilters[dataform::USER_FILTER] = get_string('filteruserpref', 'dataform');
        $menufilters[dataform::USER_FILTER_RESET] = get_string('filteruserreset', 'dataform');

        $baseurl = '/mod/dataform/view.php';
        $baseurlparams = array('d' => $this->_df->id(),
                                'sesskey' => sesskey(),
                                'view' => $this->view->id);
        if ($filter->id) {
            $menufilters[0] = get_string('filtercancel', 'dataform');
        }

        // Display the filter form jump list
        $filterselect = new single_select(new moodle_url($baseurl, $baseurlparams), 'filter', $menufilters, $filter->id, array(''=>'choosedots'), 'filterbrowse_jump');
        $filterselect->set_label(get_string('filtercurrent','dataform'). '&nbsp;');
        $filterjump = $OUTPUT->render($filterselect);
        //$OUTPUT->help_icon('fieldadd', 'dataform').
        
        if ($return) {
            return $filterjump;
        } else {
            echo $filterjump;
        }
    }

    /**
     *
     */
    protected function print_quick_search($filter, $return = false) {
        global $CFG;

        $quicksearchjump = '';

        $baseurl = '/mod/dataform/view.php';
        $baseurlparams = array('d' => $this->_df->id(),
                                'sesskey' => sesskey(),
                                'view' => $this->view->id,
                                'filter' => dataform::USER_FILTER_SET);

        if ($filter->id == dataform::USER_FILTER and $filter->search) {
            $searchvalue = $filter->search;
        } else {
            $searchvalue = '';
        }

        // Display the quick search form
        $label = html_writer::label(get_string('search'), "usersearch");
        $inputfield = html_writer::empty_tag('input', array('type' => 'text',
                                                            'name' => 'usersearch',
                                                            'value' => $searchvalue,
                                                            'size' => 20));

        $button = html_writer::empty_tag('input', array('type' => 'submit',
                                                            'value' => get_string('submit')));

        $formparams = '';
        foreach ($baseurlparams as $var => $val) {
            $formparams .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $var, 'value' => $val));
        }

        $attributes = array('method' => 'post',
                            'action' => new moodle_url($baseurl));

        $qsform = html_writer::tag('form', "$formparams&nbsp;$label&nbsp;$inputfield&nbsp;$button", $attributes);

        // and finally one more wrapper with class
        $quicksearchjump = html_writer::tag('div', $qsform, array('class' => 'singleselect'));

        if ($return) {
            return $quicksearchjump;
        } else {
            echo $quicksearchjump;
        }
    }

    /**
     *
     */
    protected function print_quick_perpage($filter, $return = false) {
        global $CFG, $OUTPUT;

        $perpagejump = '';

        $baseurl = '/mod/dataform/view.php';
        $baseurlparams = array('d' => $this->_df->id(),
                                'sesskey' => sesskey(),
                                'view' => $this->view->id,
                                'filter' => dataform::USER_FILTER_SET);

        if ($filter->id == dataform::USER_FILTER and $filter->perpage) {
            $perpagevalue = $filter->perpage;
        } else {
            $perpagevalue = 0;
        }

        $perpage = array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
           20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);

        // Display the view form jump list
        $select = new single_select(new moodle_url($baseurl, $baseurlparams), 'userperpage', $perpage, $perpagevalue, array(''=>'choosedots'), 'perpage_jump');
        $select->set_label(get_string('filterperpage','dataform'). '&nbsp;');
        $perpagejump = $OUTPUT->render($select);

        if ($return) {
            return $perpagejump;
        } else {
            echo $perpagejump;
        }
    }

    /**
     * 
     */
    protected function uses_ratings($ratingarea) {
        global $USER;
        
        if (!empty($this->_patterns['field'][dataform::_RATING])) {
            // get related tags from view
            $ratingfield = $this->_df->get_field_from_id(dataform::_RATING);
            $ratingoptions = new object;
            $ratingoptions->context = $this->_df->context;
            $ratingoptions->component = 'mod_dataform';
            $ratingoptions->ratingarea = $ratingarea;
            $ratingoptions->aggregate = $ratingfield->patterns()->get_aggregations($this->_patterns['field'][dataform::_RATING]);
            $ratingoptions->scaleid = $ratingfield->get_scaleid($ratingarea);
            $ratingoptions->userid = $USER->id;
            
            return $ratingoptions;
        }
        return null;
    }   

    /**
     *
     */
    protected function set_groupby_per_page() {
        global $CFG;

        // group per page
        if (($fieldid = $this->_filter->groupby) and ($this->_filter->perpage == 0)) {
            // set sorting to begin with this field
            $insort = false;
            $sortdir = 0; // TODO: asc order is arbitrary here and should be determined differently
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

            // set the search criterion for each page
            // that is, get an array of distinct current content of the groupby field
            $field = $this->_df->get_field_from_id($fieldid);
            if ($groupbyvalues = $field->get_distinct_content($sortdir)) {
                if ($this->_filter->page < count($groupbyvalues)) {
                    $val = $groupbyvalues[$this->_filter->page];
                    $searchfields = array();
                    if ($this->_filter->customsearch) {
                        $searchfields = unserialize($this->_filter->customsearch);
                    }
                    $search = array('', '', $val);
                    if (!isset($searchfields[$fieldid]['AND'])) {
                        $searchfields[$fieldid]['AND'] = array($search);
                    } else {
                        array_unshift($searchfields[$fieldid]['AND'], $search);
                    }
                    $this->_filter->customsearch = serialize($searchfields);
                    $this->_filter->pagenum = count($groupbyvalues);
                }
            }
        }
    }

    
}
