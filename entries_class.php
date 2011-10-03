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
 *
 */
class dataform_entries {

    const ADD_NEW_ENTRY = -1;
    const ADD_BULK_ENTRIES = -2;
    
    const SELECT_FIRST = 0;
    const SELECT_LAST = -1;
    const SELECT_NEXT = -2;
    const SELECT_RANDOM = -3;
    
    protected $_df = null;      // dataform object
    protected $_view = null;    // view object
    protected $_filter = null;  // filter object

    protected $_entries = null;
    protected $_entriestotalcount = 0;
    protected $_entriesfiltercount = 0;
    protected $_editentries = 0;
    protected $_notifications = array('good' => array(), 'bad' => array());

    protected $_mform = null;
    protected $_display_definition = array();
    protected $_returntoform = false;

    /**
     * Constructor
     * View or dataform or both, each can be id or object
     */
    public function __construct($df, $view, $filter) {
        global $DB, $CFG, $SESSION;

        if (empty($df)) {
            print_error('Programmer error: You must specify dataform id or object when defining a field class. ');
        } else if (is_object($df)) {
            $this->_df = $df;
        } else {    // dataform id
            $this->_df = new dataform($df);
        }

        if (!empty($view) and is_object($view)) {
            $this->_view = $view;
        } else { // get from id or default
            $this->_view = $this->_df->get_view_from_id($view);
        }

        $this->_filter = $filter;
    }

    /**
     *
     */
    public function count() {
        return (!empty($this->_entries) ? count($this->_entries) : 0);
    }

    /**
     *
     */
    public function filter_count() {
        return $this->_entriesfiltercount;
    }

    /**
     *
     */
    public function process_data() {
        global $CFG;

        // check first if returning from form
        $update = optional_param('update', '', PARAM_RAW);
        $cancel = optional_param('cancel', 0, PARAM_RAW);
        if (!$cancel and $update and confirm_sesskey()) {

            // get entries only if updating existing entries
            if ($update != self::ADD_NEW_ENTRY) {
                // TODO optimize to fetch only the edited entries
                $this->set_content();
            }

            // set the display definition for the form
            $this->_editentries = $update;
            $this->set__display_definition();

            // prepare params for form
            $custom_data = array();
            $custom_data['df'] = $this->_df;
            $custom_data['entries'] = $this;
            $custom_data['view'] = $this->_view;
            $custom_data['filter'] = $this->_filter;
            $custom_data['update'] = $update;
            $custom_data['page'] = $this->_filter->page;

            // get form
            require_once("$CFG->dirroot/mod/dataform/entries_form.php");
            $this->_mform = new mod_dataform_entries_form(null, $custom_data);

            // we already know that it isn't cancelled

            if ($data = $this->_mform->get_data()) {
                // validated successfully so process request
                $processedentries = $this->process_entries('update', $update, $data, true);

                // reset editing flag
                $this->_editentries = '';

                $this->_returntoform = false;
                return true;
            } else {
                // form validation failed so return to form
                $this->_returntoform = true;
                return false;
            }
        }


        // direct url params; not from form
        $new = optional_param('new', 0, PARAM_INT);               // open new entry form
        $import = optional_param('import', 0, PARAM_INT);               // import entries
        $editentries = optional_param('editentries', 0, PARAM_SEQUENCE);        // edit entries (all) or by record ids (comma delimited eids)
        $duplicate = optional_param('duplicate', '', PARAM_SEQUENCE);    // duplicate entries (all) or by record ids (comma delimited eids)
        $delete = optional_param('delete', '', PARAM_SEQUENCE);    // delete entries (all) or by record ids (comma delimited eids)
        $approve = optional_param('approve', '', PARAM_SEQUENCE);  // approve entries (all) or by record ids (comma delimited eids)
        $disapprove = optional_param('disapprove', '', PARAM_SEQUENCE);  // disapprove entries (all) or by record ids (comma delimited eids)
        $export = optional_param('export', '', PARAM_SEQUENCE);  // export entries (all) or by record ids (comma delimited eids)

        $confirmed = optional_param('confirmed', 0, PARAM_BOOL);

        $this->_editentries = $editentries;

        // Prepare open a new entry form
        if ($new and confirm_sesskey()) {
            $this->_editentries = -$new;
        // import any requested entries
        } else if ($import and confirm_sesskey()) {
            if ($data = $this->_view->get_import_data($import)) {
                $this->process_entries('update',$data->eids, $data, true);
            }
        // Duplicate any requested entries
        } else if ($duplicate and confirm_sesskey()) {
            $this->process_entries('duplicate', $duplicate, null, $confirmed);
        // Delete any requested entries
        } else if ($delete and confirm_sesskey()) {
            $this->process_entries('delete', $delete, null, $confirmed);
        // Approve any requested entries
        } else if ($approve and confirm_sesskey()) {
            $this->process_entries('approve', $approve, null, true);
        // Disapprove any requested entries
        } else if ($disapprove and confirm_sesskey()) {
            $this->process_entries('disapprove', $disapprove, null, true);
        // Export any requested entries
        } else if ($export and confirm_sesskey()) {
            $this->process_entries('export', $export, null, true);
        // Append any requested entries to the initiating entry
        } else if ($export and confirm_sesskey()) {
            $this->process_entries('append', $append, null, true);
        }

        return true;
    }

    /**
     *
     */
    public function set_content($options = null) {
        global $CFG;

        if (!isset($options->entriesset)) {

            // check if view is caching
            if ($this->_view->is_caching()) {
                if (!$entriesset = $this->_view->get_cache_content()) {
                    $filteroptions = $this->_view->get_cache_filter_options();
                    foreach ($filteroptions as $option => $value) {
                        $this->_filter->{$option} = $value;
                    }
                    $entriesset = $this->get_entries();
                    $this->_view->update_cache_content($entriesset);
                }
            } else {
                $entriesset = $this->get_entries();
            }
        }

        $this->_entries = !empty($entriesset->entries) ? $entriesset->entries : array();
        $this->_entriestotalcount = !empty($entriesset->max) ? $entriesset->max : count($this->_entries);
        $this->_entriesfiltercount = !empty($entriesset->found) ? $entriesset->found : count($this->_entries);

        // add ratings if applicable
        if (!empty($options->ratings)) {
            require_once("$CFG->dirroot/mod/dataform/field/_rating/lib.php");
            $rm = new dataform_rating_manager();
            $options->ratings->items = $this->_entries;
            $this->_entries = $rm->get_ratings($options->ratings);
        }

        if (!$this->_entriestotalcount) {
            $this->_notifications['bad'][] = get_string('entriesfound', 'dataform', get_string('no'));
        } else {
            // notify subset if filtered
            if (($this->_entriesfiltercount != $this->_entriestotalcount) and $this->_filter->id) {
                $strentriesfound = $this->_entriesfiltercount. '/'. $this->_entriestotalcount;
                $this->_notifications['good'][] = get_string('entriesfound', 'dataform', $strentriesfound);
            }
        }
    }

    /**
     *
     */
    public function display($return = false) {
        global $CFG, $OUTPUT;
        
        $html = '';

        if ($this->_returntoform) {
            $this->_mform->set_data(null);
            $html .= $this->_mform->html();
        } else {
            // first notifications
            foreach ($this->_notifications['good'] as $notification) {
                $html .= $OUTPUT->notification($notification, 'notifysuccess');    // good (usually green)
            }
            foreach ($this->_notifications['bad'] as $notification) {
                $html .= $OUTPUT->notification($notification);    // bad (usually red)
            }

            // build definition
            $this->set__display_definition();

            if (!$editing = $this->user_is_editing()) {
                // all _display_definition elements should be html so concat and echo
                $html .= $this->definition_to_html();

            } else {

                // prepare params for form
                $setdata = array();
                $custom_data['df'] = $this->_df;
                $custom_data['entries'] = $this;
                $custom_data['view'] = $this->_view;
                $custom_data['filter'] = $this->_filter;
                $custom_data['update'] = $this->_editentries;
                $custom_data['page'] = $this->_filter->page;

                // get form
                require_once("$CFG->dirroot/mod/dataform/entries_form.php");
                $this->_mform = new mod_dataform_entries_form(null, $custom_data);

                $this->_mform->set_data(null);
                $html .= $this->_mform->html();
            }
        }
        
        if ($return) {
            return $html;
        } else {
            echo  $html;
        }
    }

    /**
     *
     */
    public function definition_to_html() {

        $elements = array();
        foreach ($this->_display_definition as $groupname => $entries_set) {
            $elements = array_merge($elements, $this->_view->group_entries_definition($entries_set, $groupname));
        }

        $html = '';
        // if $mform is null, simply echo/return html string
        foreach ($elements as $element) {
            list(, $content) = $element;
            $html .= $content;
        }

        return html_writer::tag('div', $html, array('class' => 'mdl-align'));
    }

    /**
     *
     */
    public function definition_to_form(&$mform) {

        $elements = array();
        foreach ($this->_display_definition as $groupname => $entries_set) {
            $elements = array_merge($elements, $this->_view->group_entries_definition($entries_set, $groupname));
        }

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
    public function get_entries() {
        global $CFG, $DB, $USER;
        
        $df = &$this->_df;
        $filter = &$this->_filter;
        
        $fields = $df->get_fields();

        // content fields if any
        $contentfields = (isset($filter->contentfields) ? $filter->contentfields : '');

        // sort and search settings
        $perpage = isset($filter->perpage) ? $filter->perpage : 0;
        $groupby = isset($filter->groupby) ? $filter->groupby : 0;
        $customsort = isset($filter->customsort) ? trim($filter->customsort) : '';
        $customsearch = isset($filter->customsearch) ? trim($filter->customsearch) : '';
        $simplesearch = isset($filter->search) ? trim($filter->search) : '';

        // named params array for the sql
        $params = array();        

        // we need to collate content tables for select search and sort
        // the three sets may overlap, but search tables should be joined
        // whereas the others only left joined
        // so start with search, then select and sort

        // SEARCH settings
        $searchfrom = array();
        $searchwhere = array();
        $searchparams = array(); // named params array

        if ($customsearch) {
            $searchfields = unserialize($customsearch);

            $whereand = array();
            $whereor = array();
            foreach($searchfields as $fieldid => $searchfield) {
                // if we got this far there must be some actual search values
                if ($fieldid > 0) { // only for user fields
                    $searchfrom[] = $fieldid;
                }

                $field = $fields[$fieldid];

                // add AND search clauses
                if (!empty($searchfield['AND'])) {
                    foreach ($searchfield['AND'] as $option) {
                        list($fieldsql, $fieldparams) = $field->get_search_sql($option);
                        $whereand[] = $fieldsql;
                        $searchparams = array_merge($searchparams, $fieldparams);
                    }
                }

                // add OR search clause
                if (!empty($searchfield['OR'])) {
                    foreach ($searchfield['OR'] as $option) {
                        list($fieldsql, $fieldparams) = $field->get_search_sql($option);
                        $whereor[] = $fieldsql;
                        $searchparams = array_merge($searchparams, $fieldparams);
                    }
                }
            }

            if ($searchfrom) {
                $searchwhere[] = implode(' AND ', array_map(function($fieldid) {return " c$fieldid.fieldid = $fieldid ";}, $searchfrom));
            }
            if ($whereand) {
                $searchwhere[] = implode(' AND ', $whereand);
            }
            if ($whereor) {
                $searchwhere[] = '('. implode(' OR ', $whereor). ')';
            }
        } else if ($simplesearch) {
            $searchfrom[] = 's';
            $searchwhere[] = ' ('. $DB->sql_like('cs.content', ':search1', false).
                                ' OR '. $DB->sql_like('u.firstname', ':search2', false).
                                ' OR '. $DB->sql_like('u.lastname', ':search3', false).') ';
            $searchparams['search1'] = "%$simplesearch%";
            $searchparams['search2'] = "%$simplesearch%";
            $searchparams['search3'] = "%$simplesearch%";
        }

        // compile sql for search settings
        $searchtables = '';
        if ($searchfrom) {
            foreach ($searchfrom as $fieldid) {
                $searchtables .= " JOIN {dataform_contents} c$fieldid ON c$fieldid.entryid = e.id ";
            }
        }
        $wheresearch = $searchwhere ? ' AND '. implode(' AND ', $searchwhere) : '';

        // SORT settings
        $sorties = array();
        $orderby = array("e.timecreated ASC");
        //$sortcount = '';

        $sortfields = array();
        if ($customsort) {
            $sortfields = unserialize($customsort);

            $orderby = array();
            foreach ($sortfields as $fieldid => $sortdir) {
                $field = $fields[$fieldid];
                $sortname = $field->get_sort_sql();
                if ($fieldid > 0) {
                    // only user fields are added to sorties
                    $sorties[$fieldid] = $sortname;
                    //$sortcount .= ($sortcount ? ', ' : ''). 'c'. $fieldid. '.entryid';
                }
                $orderby[] = "$sortname ". ($sortdir ? 'DESC' : 'ASC');
            }
        }

        // compile sql for sort settings
        $sorttables = '';
        $sortorder = ' ORDER BY '. implode(', ', $orderby). ' ';
        if ($sorties) {
            $sortfrom = array_keys($sorties);
            foreach ($sortfrom as $fieldid) {
                // add only tables which are not already added for searching
                if (empty($searchfrom) or !in_array($fieldid, $searchfrom)) {
                    $sorttables .= " LEFT JOIN {dataform_contents} c$fieldid ON (c$fieldid.entryid = e.id AND c$fieldid.fieldid = $fieldid) ";
                }
            }
        }

        // CONTENT fields if any
        $whatcontent = ' ';
        $contenttables = ' ';
        if ($contentfields) {
            $whatcontent = array();
            $contentfrom = array();
            foreach ($contentfields as $fieldid) {
                // only user fields
                if ($fieldid > 0) {
                    $whatcontent[] = $fields[$fieldid]->get_select_sql();
                    // add content table only if not already used for search or sort
                    if ((!isset($sortfrom) or !in_array($fieldid, $sortfrom))
                            and (empty($searchfrom) or !in_array($fieldid, $searchfrom))) {
                        $contentfrom[$fieldid] = "LEFT JOIN {dataform_contents} c$fieldid ON (c$fieldid.entryid = e.id AND c$fieldid.fieldid = $fieldid) ";
                    }
                }
            }
            $whatcontent = ', '. implode(', ', $whatcontent);
            $contenttables = ' '. implode(' ', $contentfrom);
        }

        // USER filtering
        $whereuser = '';
        if (!$df->user_can_view_all_entries(array('notify' => true))) {
            $whereuser = " AND e.userid = :{$this->sqlparams($params, 'userid', $USER->id)} ";
        }

        // GROUP filtering
        $wheregroup = '';
        if ($df->currentgroup) {
//            $wheregroup = " AND (e.groupid = :{$this->sqlparams($params, 'groupid', $df->currentgroup)}
//                                OR e.groupid = :{$this->sqlparams($params, 'groupid', 0)}) ";
            $wheregroup = " AND e.groupid = :{$this->sqlparams($params, 'groupid', $df->currentgroup)} ";
        }

        // APPROVE filtering
        $whereapprove = '';
        if ($df->data->approval and !has_capability('mod/dataform:manageentries', $df->context)) {
            if (isloggedin()) {
                $whereapprove = " AND (e.approved = :{$this->sqlparams($params, 'approved', 1)} 
                                        OR e.userid = :{$this->sqlparams($params, 'userid', $USER->id)}) ";
            } else {
                $whereapprove = " AND e.approved = :{$this->sqlparams($params, 'approved', 1)} ";
            }
        }

        // RATING (activity) filtering
        // unconditioned just in case activity rating is used and then abandoned
        $whererating = " AND e.grading = :{$this->sqlparams($params, 'grading', 0)} ";


        // sql for fetching the entries
        $what = ' DISTINCT e.id, e.approved, e.timecreated, e.timemodified, e.userid, e.groupid, '.
                    user_picture::fields('u', array('idnumber', 'username'), 'uid '). ', '.
                    'g.name AS groupname, g.description AS groupdesc, g.hidepicture AS grouphidepic, g.picture AS grouppic '.                    
                    $whatcontent;
        $count = ' COUNT(e.id) ';
        $tables = ' {dataform_entries} e
                    JOIN {user} u ON u.id = e.userid 
                    LEFT JOIN {groups} g ON g.id = e.groupid ';
        $wheredfid =  " e.dataid = :{$this->sqlparams($params, 'dataid', $df->id())}  ";

        $fromsql  = " $tables $contenttables $sorttables $searchtables ";
        $wheresql = " $wheredfid $whereuser $wheregroup $whereapprove $whererating $wheresearch";
        $sqlselect  = "SELECT $what FROM $fromsql WHERE $wheresql $sortorder";

        // total number of entries the user is authorized to view (without additional filtering)
        $sqlmax     = "SELECT $count FROM $tables WHERE $wheredfid $whereuser $wheregroup $whereapprove $whererating";
        // number of entries in this particular view call (with filtering)
        $sqlcount   = "SELECT $count FROM $fromsql WHERE $wheresql";

        // base params + search params
        $baseparams = array();
        foreach ($params as $paramset) {
            $baseparams = array_merge($paramset, $baseparams);
        }
        $allparams = array_merge($baseparams, $searchparams);

        // count prospective entries
        if (empty($wheresearch)) {
            $maxcount = $searchcount = $DB->count_records_sql($sqlmax, $baseparams);
        } else {
            if ($maxcount = $DB->count_records_sql($sqlmax, $baseparams)) {
                $searchcount = $DB->count_records_sql($sqlcount, $allparams);
            } else {
                $searchcount = 0;
            }
        }

        // initialize returned object
        $entries = new object();
        $entries->max = $maxcount;
        $entries->found = $searchcount;
        $entries->entries = null;

        if ($searchcount) {
            // if a specific entry requested (eid)
            if (isset($filter->eid) and $filter->eid) {
                $andwhereeid = " AND e.id = :{$this->sqlparams($params, 'eid', $filter->eid)} ";
                
                $sqlselect = "$sqlcount AND e.id <= :{$this->sqlparams($params, 'eid', $filter->eid)} $sortorder";
                $eidposition = $DB->get_records_sql($sqlselect, $allparams + $params['eid']);

                $sqlselect = "SELECT $what $whatcontent                                  
                              FROM $fromsql 
                              WHERE $wheresql $andwhereeid $sortorder";
 
                if ($entries->entries = $DB->get_records_sql($sqlselect, $allparams + $params['eid'])) {
                    // there should be only one
                    $filter->page = key($eidposition) - 1;
                }

            // get perpage subset 
            } else if ($perpage) {
                $page = isset($filter->page) ? $filter->page : 0;
                $numpages = $searchcount > $perpage ? ceil($searchcount / $perpage) : 1;
                
                if (isset($filter->select)) {
                    // first page
                    if ($filter->select == self::SELECT_FIRST) {
                        $page = 0;

                    // last page
                    } else if ($filter->select == self::SELECT_LAST) {
                        $page = $numpages - 1;
                    
                    // next page
                    } else if ($filter->select == self::SELECT_NEXT) {
                        $page = $filter->page = ($page % $numpages);
                    
                    // random page
                    } else if ($filter->select == self::SELECT_RANDOM) {
                        $page = $numpages > 1 ? rand(0, ($numpages - 1)) : 0;
                    }
                }

                $entries->entries = $DB->get_records_sql($sqlselect, $allparams, $page * $perpage, $perpage);

            // get everything
            } else {
                $entries->entries = $DB->get_records_sql($sqlselect, $allparams);
            }

        }
        return $entries;
    }

    /**
     *
     */
    public function update_entry($entry, $params = null, $updatetime = true) {
        global $DB, $USER;

        $df = $this->_df;
        
        if ($params and has_capability('mod/dataform:manageentries', $df->context)) {
            foreach ($params as $key => $value) {
                $entry->{$key} = $value;
                if ($key == 'timemodified') {
                    $updatetime = false;
                }
            }
        } 
        
        // update existing entry
        if ($entry->id > 0) {
            if ($df->user_can_manage_entry($entry)) { // just in case the user opens two forms at the same time
                if (!has_capability('mod/dataform:approve', $df->context)) {
                    $entry->approved = 0;
                }

                if ($updatetime) {
                    $entry->timemodified = time();
                }

                if ($DB->update_record('dataform_entries',$entry)) {
                    return $entry->id;
                } else {
                    return false;
                }
            }

        // add new entry
        } else {
            if ($df->user_can_manage_entry()) { // just in case the user opens two forms at the same time
                $entry->dataid = $df->id();
                if (!isset($entry->userid)) $entry->userid = $USER->id;
                if (!isset($entry->groupid)) $entry->groupid = $df->currentgroup;
                if (!isset($entry->timecreated)) $entry->timecreated = time();
                if (!isset($entry->timemodified)) $entry->timemodified = time();
                $entryid = $DB->insert_record('dataform_entries', $entry);
                return $entryid;
            }
        }

        return false;
    }

    /**
     *
     */
    public function process_entries($action, $eids, $data = null, $confirmed = false) {
        global $DB, $USER, $OUTPUT, $PAGE;

        $df = $this->_df;

        $entries = array();
        if ($eids) {
            // some entries are specified for action
            if ($action == 'update') {
                if (is_array($eids)) {
                    continue;
                } else if ($eids < 0) {
                    $eids = array_reverse(range($eids, -1));
                } else {
                    $eids = explode(',', $eids);
                }
                
                foreach ($eids as $eid) {
                    $entry = new object();
                    if ($eid > 0 and isset($this->_entries[$eid])) { // existing entry from view
                        $entries[$eid] = $this->_entries[$eid];

                    } else if ($eid > 0) {   // existing entry not from view (import)
                        // TODO
                        // need to collect these entries and get them from DB with their content

                    } else if ($eid < 0) {   // new entry
                        $entry->id = 0;
                        $entry->groupid = $df->currentgroup;
                        $entry->userid = $USER->id;
                        $entries[$eid] = $entry;
                    }
                }

            } else {
                $entries = $DB->get_records_select('dataform_entries', "dataid = ? AND id IN ($eids)", array($df->id()));
            }

            if ($entries) {
                foreach ($entries as $eid => $entry) {
                    if (($action == 'approve' or $action == 'disapprove') and !has_capability('mod/dataform:approve', $df->context)) {
                        unset($entries[$eid]);
                    } else if (!$df->user_can_manage_entry($entry)
                                and !has_capability('mod/dataform:manageentries', $df->context)) {
                        unset($entries[$eid]);
                    }
                }
            }
        }

        if (empty($entries)) {
            $this->_notifications['bad'][] = get_string("entrynoneforaction",'dataform');
            return false;
        } else {
            if (!$confirmed) {

                // Print a confirmation page
                echo $OUTPUT->confirm(get_string("entriesconfirm$action", 'dataform', count($entries)),
                                    new moodle_url($PAGE->url, array($action => implode(',', array_keys($entries)),
                                                                    'sesskey' => sesskey(),
                                                                    'confirmed' => true)),
                                    new moodle_url($PAGE->url));

                echo $OUTPUT->footer();
                exit;

            } else {
                $processedeids = array();
                $strnotify = '';

                switch ($action) {
                    case 'update':
                        $strnotify = 'entriesupdated';

                        if (!is_null($data)) {
                            $fields = $df->get_fields();

                            // first parse the data to collate content in an array for each recognized field
                            $entrycontents = array_fill_keys(array_keys($entries), array('info' => array(), 'fields' => array()));
                            $calculations = array();
                            $entryinfo = array(
                                dataform::_ENTRY,
                                dataform::_TIMECREATED,
                                dataform::_TIMEMODIFIED,
                                dataform::_APPROVED,
                                dataform::_USERID,
                                dataform::_GROUP
                            );
                            foreach ($data as $name => $value){
                                if (strpos($name, 'field_') !== false) {   // assuming only field names contain field_
                                    list(, $fieldid, $entryid) = explode('_', $name);
                                    if (array_key_exists($fieldid, $fields)) {
                                        $field = $fields[$fieldid];
                                    } else {
                                        continue;
                                    }
                                    
                                    if (in_array($fieldid, $entryinfo)) {
                                        // TODO
                                        if ($fieldid == dataform::_USERID) {
                                            $entryvar = 'userid';
                                        } else {
                                            $entryvar = $field->get_internalname();
                                        }
                                        $entrycontents[$entryid]['info'][$entryvar] = $value;
                                    
                                    } else if ($field->type() == 'calculated') {
                                        // do calculated field after updating the rest
                                        $calculations[$fieldid] = $field;
 
                                    } else {
                                        if (!array_key_exists($fieldid, $entrycontents[$entryid]['fields'])) {
                                            $entrycontents[$entryid]['fields'][$fieldid] = array();
                                        }
                                        $entrycontents[$entryid]['fields'][$fieldid][$name] = $value;
                                    }
                                }
                            }

                            // now update contents
                            foreach ($entries as $eid => $entry) {
                                if ($entry->id = $this->update_entry($entry, $entrycontents[$eid]['info'])) {
                                    $processedeids[] = $entry->id;
                                    // $eid should be different from $entryid only in new entries
                                    foreach ($entrycontents[$eid]['fields'] as $fieldid => $content) {
                                        $fields[$fieldid]->update_content($entry, $content);
                                    }

                                    // TODO currently does not support calculations on calculated fields
                                    foreach ($calculations as $calculated) {
                                        $calculated->update_content($entry, $entrycontents[$eid]['fields']);
                                    }
                                }
                            }                            
                        }
                        break;

                    case 'duplicate':
                        foreach ($entries as $entrie) {
                            // can user add anymore entries?
                            if (!$df->user_can_manage_entry()) {
                                // TODO: notify something
                                break;
                            }

                            // Get content of entrie to duplicat
                            $contents = $DB->get_records('dataform_contents', array('entryid' => $entrie->id));

                            // Add a duplicated entrie and content
                            $newrec = $entrie;
                            $newrec->userid = $USER->id;
                            $newrec->dataid = $df->id();
                            $newrec->groupid = $df->currentgroup;
                            $newrec->timecreated = $newrec->timemodified = time();

                            if ($df->data->approval and !has_capability('mod/dataform:approve', $df->context)) {
                                $newrec->approved = 0;
                            }
                            $entrieid = $DB->insert_record('dataform_entries',$newrec);

                            foreach ($contents as $content) {
                                $newcontent = $content;
                                $newcontent->entryid = $entrieid;
                                if (!$DB->insert_record('dataform_contents', $newcontent)) {
                                    print_error('cannotinsertrecord', '', '', $entrieid);
                                }
                            }
                            $processedeids[] = $entrieid;
                        }

                        $strnotify = 'entriesduplicated';
                        break;

                    case 'approve':
                        $newentrie = new object();
                        $newentrie->approved = 1;
                        foreach ($entries as $entrie) {
                            if (!$entrie->approved and has_capability('mod/dataform:approve', $df->context)) {
                                $newentrie->id = $entrie->id;
                                $DB->update_record('dataform_entries', $newentrie);
                                $processedeids[] = $entrie->id;
                            }
                        }

                        $strnotify = 'entriesapproved';
                        break;

                    case 'disapprove':
                        $newentrie = new object();
                        $newentrie->approved = 0;
                        foreach ($entries as $entrie) {
                            if ($entrie->approved and has_capability('mod/dataform:approve', $df->context)) {
                                $newentrie->id = $entrie->id;
                                $DB->update_record('dataform_entries', $newentrie);
                                $processedeids[] = $entrie->id;
                            }
                        }

                        $strnotify = 'entriesdisapproved';
                        break;

                    case 'delete':
                        foreach ($entries as $entry) {
                            if (!$df->user_can_manage_entry($entry)) {
                                // TODO: notify something
                                continue;
                            }

                            $fields = $df->get_fields();
                            foreach ($fields as $field) {
                                $field->delete_content($entry->id);
                            }

                            //if ($contents =$DB->get_records('dataform_contents','entryid', $entrie->id)) {
                            //    foreach ($contents as $content) {  // Delete files or whatever else this field allows
                            //        if ($field = $df->get_field_from_id($content->fieldid)) { // Might not be there
                            //            $field->delete_content($content->entryid);
                            //        }
                            //    }
                            //}
                            //$DB->delete_records('dataform_contents', array('entryid' => $entrie->id));

                            $DB->delete_records('dataform_entries', array('id' => $entry->id));
                            $processedeids[] = $entry->id;
                        }

                        $strnotify = 'entriesdeleted';
                        break;


                    case 'append':
         
                        $nodeid = required_param('node', PARAM_INT);
                        $parentid = required_param('parent', PARAM_INT);
                        $siblingid = optional_param('sibling', 0, PARAM_INT);

                        //get the node content of the requested entries
                        list($eids, $params) = $DB->get_in_or_equal(array_keys($entries), SQL_PARAM_NAMED);
                        $params['fieldid'] = $nodeid;
                        $contents = $DB->get_records_select('dataform_contents', "fieldid = :fieldid AND entryid {$eids}", $params);
                        //update node content of the entries
                        foreach ($contents as $content) {
                            if ($content->entryid != $parentid and $content->entryid != $siblingid) {
                                $content->content = $parentid;
                                $content->content1 = $siblingid;
                                $DB->update_record($content);
                                
                                $processedeids[] = $content->entryid;
                            }
                        }

                        $strnotify = 'entriesappended';
                        break;

                    default:
                        break;
                }

                $df->add_to_log($action);
                if ($strnotify) {
                    if ($entriesprocessed = ($processedeids ? count($processedeids) : 0)) {
                       $this->_notifications['good'][] = get_string($strnotify, 'dataform', $entriesprocessed);
                    } else {
                       $this->_notifications['bad'][] = get_string($strnotify, 'dataform', get_string('no'));
                    }
                }
                return $processedeids;
            }
        }
    }

    /**
     *
     */
    public function user_is_editing() {
        $editing = $this->_editentries;
        //$multiactions = $this->_view->uses_multiactions();

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

        $editentries = null;

        // Display a new entry to add in its own group
        if ($this->_editentries < 0) {
            // TODO check how many entries left to add
            if ($this->_df->user_can_manage_entry(0)) {
                // TODO get category name from string and check for plural
                $this->_display_definition['newentry'] = array();
                for ($i = $this->_editentries; $i < 0; $i++) {
                    $this->_display_definition['newentry'][$i] = null;
                }
            }
        } else if ($this->_editentries) {
            $editentries = explode(',', $this->_editentries);
        }

        $fields = $this->_df->get_fields();

        // compile entries if any
        if ($this->_entries) {
            // prepare for groupby
            $groupbyvalue = '';
            $groupdefinition = array();

            foreach ($this->_entries as $entryid => $entry) {   // Might be just one
                $newgroup = '';

                // May we edit this entry? (!$editable hides the entry action tags in _entry field )
                $editthisone = false;
                if ($editable = $this->_df->user_can_manage_entry($entry)) {
                    if ($editentries) {
                        $editthisone = in_array($entryid, $editentries);
                    }
                }

                // the view knows which tags are used in the entry
                // so we can collect their definitions (replacements).
                // The definition for a pattern is either html (when the field is to be browsed)
                //  or a callback (when the field is to edited)

// instead of getting field definitions here I'll just register the entry and editing params
// and the rest will be done inside the view
//                $fielddefinitions = $this->_view->get_field_definitions($entry, $editthisone, $editable);

                // Are we grouping?
                if ($this->_filter->groupby) {
                    $field = $fields[$this->_filter->groupby];
                    // if editing get the pattern for browsing b/c we need the content
                    if ($editthisone) {
                        $fieldpatterns = $field->patterns($entry);
                    }
                    $fieldvalues = current($fieldpatterns);
                    $fieldvalue = count($fieldvalues) ? current($fieldvalues) : '';
                    if ($fieldvalue != $groupbyvalue) {
                        $newgroup = $groupbyvalue;
                        // TODO assuming here that the groupbyed field returns only one pattern
                        $groupbyvalue = $fieldvalue;
                    }
                }

                // check if we need to start a new group of entries
                if ($newgroup) {
                    $this->_display_definition[$newgroup] = $groupdefinitions;
                    $groupdefinitions = array();
                }

                // we have the patterns and their definitions for this entry
                // so complie the entry definition and add to the current entries group

// see above
//                $groupdefinition[$entryid] = $this->_view->entry_definition($fielddefinitions);
                $groupdefinition[$entryid] = array($entry, $editthisone, $editable);

            }
            // collect remaining listbody text (all of it if no groupby)
            $this->_display_definition[$groupbyvalue] = $groupdefinition;
        }
    }

    /**
     *
     */
    protected function get_ratings($entries) {
        global $CFG, $USER;

        $data = $this->df->data;

        if (empty($entries)) {
            return $entries;
        }

        if (!$data->grade and !($data->rating  and $data->grademethod)) {
            return $entries;
        }

        if (!$this->uses_ratings('##ratings##')) {
            return $entries;
        }

        require_once($CFG->dirroot.'/rating/lib.php');
        $options = new object();
        $options->context = $this->df->context;
        $options->component = 'mod_dataform';
        if ($data->grademethod) {
            $options->ratingarea = 'entry';
            $options->aggregate = $data->grademethod;
            $options->scaleid = $data->rating;
        } else {
            $options->ratingarea = 'activity';
            $options->aggregate = RATING_AGGREGATE_MAXIMUM;
            $options->scaleid = $data->grade;
        }
        $options->items = $entries;
        $options->userid = $USER->id;
        $options->returnurl = $CFG->wwwroot.'/mod/dataform/view.php?d='.$this->df->id();
        //$options->assesstimestart = $data->assesstimestart;
        //$options->assesstimefinish = $data->assesstimefinish;

        $rm = new rating_manager();
        return $rm->get_ratings($options);
    }

    /**
     *
     */
    private function sqlparams(&$params, $param, $value) {
        if (!array_key_exists($param, $params)) {
            $params[$param] = array();
        }
        
        $p = count($params[$param]);
        $params[$param][$param. $p] = $value;
        return $param. $p;    
    }
    

}
