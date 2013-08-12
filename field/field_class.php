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
 * @package dataformfield
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__). '/../mod_class.php');

/**
 * Base class for Dataform Field Types
 */
abstract class dataformfield_base {

    const VISIBLE_NONE = 0;
    const VISIBLE_OWNER = 1;
    const VISIBLE_ALL = 2;

    public $type = 'unknown';  // Subclasses must override the type with their name

    public $df = null;       // The dataform object that this field belongs to
    public $field = null;      // The field object itself, if we know it

    protected $_renderer = null;
    protected $_distinctvalues = null;
    
    /**
     * Class constructor
     *
     * @param var $df       dataform id or class object
     * @param var $field    field id or DB record
     */
    public function __construct($df = 0, $field = 0) {

        if (empty($df)) {
            throw new coding_exception('Dataform id or object must be passed to view constructor.');
        } else if ($df instanceof dataform) {
            $this->df = $df;
        } else {    // dataform id/object
            $this->df = new dataform($df);
        }

        if (!empty($field)) {
            // $field is the field record
            if (is_object($field)) {
                $this->field = $field;  // Programmer knows what they are doing, we hope

            // $field is a field id
            } else if ($fieldobj = $this->df->get_field_from_id($field)) {
                $this->field = $fieldobj->field;
            } else {
                throw new moodle_exception('invalidfield', 'dataform', null, null, $field);
            }
        }

        if (empty($this->field)) {         // We need to define some default values
            $this->set_field();
        }
    }

    /**
     * Sets up a field object
     */
    public function set_field($forminput = null) {
        $this->field = new object;
        $this->field->id = !empty($forminput->id) ? $forminput->id : 0;
        $this->field->type   = $this->type;
        $this->field->dataid = $this->df->id();
        $this->field->name = !empty($forminput->name) ? trim($forminput->name) : '';
        $this->field->description = !empty($forminput->description) ? trim($forminput->description) : '';
        $this->field->visible = isset($forminput->visible) ? $forminput->visible : 2;
        $this->field->edits = isset($forminput->edits) ? $forminput->edits : -1;
        $this->field->label = !empty($forminput->label) ? $forminput->label : '';
        for ($i=1; $i<=10; $i++) {
            $this->field->{"param$i"} = !empty($forminput->{"param$i"}) ? trim($forminput->{"param$i"}) : null;
        }
    }

    /**
     * Insert a new field in the database
     */
    public function insert_field($fromform = null) {
        global $DB, $OUTPUT;

        if (!empty($fromform)) {
            $this->set_field($fromform);
        }

        if (!$this->field->id = $DB->insert_record('dataform_fields', $this->field)){
            echo $OUTPUT->notification('Insertion of new field failed!');
            return false;
        } else {
            return $this->field->id;
        }
    }

    /**
     * Update a field in the database
     */
    public function update_field($fromform = null) {
        global $DB, $OUTPUT;
        if (!empty($fromform)) {
            $this->set_field($fromform);
        }

        if (!$DB->update_record('dataform_fields', $this->field)) {
            echo $OUTPUT->notification('updating of field failed!');
            return false;
        }
        return true;
    }

    /**
     * Delete a field completely
     */
    public function delete_field() {
        global $DB;

        if (!empty($this->field->id)) {
            if ($filearea = $this->filearea()) {
                $fs = get_file_storage();
                $fs->delete_area_files($this->df->context->id, 'mod_dataform', $filearea);
            }
            $this->delete_content();
            $DB->delete_records('dataform_fields', array('id' => $this->field->id));
        }
        return true;
    }

    /**
     * Getter
     */
    public function get($var) {
        if (isset($this->field->$var)) {
            return $this->field->$var;
        } else {
            // TODO throw an exception if $var is not a property of field
            return false;
        }
    }

    /**
     * Returns the field id
     */
    public function id() {
        return $this->field->id;
    }

    /**
     * Returns the field type
     */
    public function type() {
        return $this->type;
    }

    /**
     * Returns the name of the field
     */
    public function name() {
        return $this->field->name;
    }

    /**
     * Returns the type name of the field
     */
    public function typename() {
        return get_string('pluginname', "dataformfield_{$this->type}");
    }

    /**
     * Prints the respective type icon
     */
    public function image() {
        global $OUTPUT;

        $image = $OUTPUT->pix_icon(
                            'icon',
                            $this->type,
                            "dataformfield_{$this->type}");

        return $image;

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

        if (file_exists($CFG->dirroot. '/mod/dataform/field/'. $this->type. '/field_form.php')) {
            require_once($CFG->dirroot. '/mod/dataform/field/'. $this->type. '/field_form.php');
            $formclass = 'dataformfield_'. $this->type. '_form';
        } else {
            require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');
            $formclass = 'dataformfield_form';
        }
        $actionurl = new moodle_url(
            '/mod/dataform/field/field_edit.php',
            array('d' => $this->df->id(), 'fid' => $this->id(), 'type' => $this->type)
        );
        return new $formclass($this, $actionurl);
    }

    /**
     *
     */
    public function to_form() {
        return $this->field;
    }

    /**
     *
     */
    public function renderer() {
        global $CFG;

        if (!$this->_renderer) {
            $rendererclass = "dataformfield_{$this->type}_renderer";
            require_once("$CFG->dirroot/mod/dataform/field/{$this->type}/renderer.php");
            $this->_renderer = new $rendererclass($this);
        }
        return $this->_renderer;
    }

    /**
     * Check if a field from an add form is empty
     */
    public function notemptyfield($value, $name) {
        return !empty($value);
    }

    // CONTENT MANAGEMENT
    /**
     *
     */
    public function get_definitions($patterns, $entry, $options = array()) {
        global $USER;

        $userid = 0;
        if (!empty($entry->userid)) {
            $userid = $entry->userid;
        } else if ($entry->id < 0) {
            $userid = $USER->id;
        }
        
        if (!$this->is_visible($userid)) {
            return array_fill_keys($patterns, '');
        }
        
        if (!$this->is_editable()) {
            $options['edit'] = false;
        }
        
        return $this->renderer()->get_replacements($patterns, $entry, $options);
    }
       
    /**
     *
     */
    public static function is_internal() {
        false;
    }
    
    /**
     *
     */
    protected function is_visible($entryowner) {
        global $USER;

        $visibility = $this->field->visible;
        if ($visibility == self::VISIBLE_ALL) {
            return true;
        } 

        if ($canmanageentries = has_capability('mod/dataform:manageentries', $this->df()->context)) {
            return true;
        }
        
        $isowner = ($USER->id == $entryowner);
        if ($visibility == self::VISIBLE_OWNER and $isowner) {
            return true;
        }
        
        return false;
    }

    /**
     *
     */
    protected function is_editable() {
        if (empty($this->field->edits)
                    and !has_capability('mod/dataform:manageentries', $this->df()->context)) {
            return false;
        }
        return true;
    }

    /**
     *
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        if (!$this->is_editable()) {
            return false;
        }

        $fieldid = $this->field->id;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        list($contents, $oldcontents) = $this->format_content($entry, $values);

        $rec = new object();
        $rec->fieldid = $this->field->id;
        $rec->entryid = $entry->id;
        foreach ($contents as $key => $content) {
            $c = $key ? $key : '';
            $rec->{"content$c"} = $content;
        }

        // insert only if no old contents and there is new contents
        if (is_null($contentid) and !empty($contents)) {
            return $DB->insert_record('dataform_contents', $rec);
        }

        // delete if old content but not new
        if (!is_null($contentid) and empty($contents)) {
            return $this->delete_content($entry->id);
        }

        // update if new is different from old
        if (!is_null($contentid)) {
            foreach ($contents as $key => $content) {
                if (!isset($oldcontents[$key]) or $content !== $oldcontents[$key]) {
                    $rec->id = $contentid; // MUST_EXIST
                    return $DB->update_record('dataform_contents', $rec);
                }
            }
        }

        return true;
    }

    /**
     * delete all content associated with the field
     */
    public function delete_content($entryid = 0) {
        global $DB;

        if ($entryid) {
            $params = array('fieldid' => $this->field->id, 'entryid' => $entryid);
        } else {
            $params = array('fieldid' => $this->field->id);
        }

        $rs = $DB->get_recordset('dataform_contents', $params);
        if ($rs->valid()) {
            $fs = get_file_storage();
            foreach ($rs as $content) {
                $fs->delete_area_files($this->df->context->id, 'mod_dataform', 'content', $content->id);
            }
        }
        $rs->close();

        return $DB->delete_records('dataform_contents', $params);
    }

    /**
     * transfer all content associated with the field
     */
    public function transfer_content($tofieldid) {
        global $CFG, $DB;

        if ($contents = $DB->get_records('dataform_contents', array('fieldid' => $this->field->id))) {
            if (!$tofieldid) {
                return false;
            } else {
                foreach ($contents as $content) {
                    $content->fieldid = $tofieldid;
                    $DB->update_record('dataform_contents', $content);
                }

                // rename content dir if exists
                $path = $CFG->dataroot.'/'.$this->df->course->id.'/'.$CFG->moddata.'/dataform/'.$this->df->id();
                $olddir = "$path/". $this->field->id;
                $newdir = "$path/$tofieldid";
                file_exists($olddir) and rename($olddir, $newdir);
                return true;
            }
        }
        return false;
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        global $DB;

        if (is_null($this->_distinctvalues)) {
            $this->_distinctvalues = array();
            $fieldid = $this->field->id;
            $sortdir = $sortdir ? 'DESC' : 'ASC';
            $contentname = $this->get_sort_sql();
            $sql = "SELECT DISTINCT $contentname
                        FROM {dataform_contents} c$fieldid
                        WHERE c$fieldid.fieldid = $fieldid AND $contentname IS NOT NULL
                        ORDER BY $contentname $sortdir";

            if ($options = $DB->get_records_sql($sql)) {
                foreach ($options as $data) {
                    $value = $data->content;
                    if ($value === '') {
                        continue;
                    }
                    $this->_distinctvalues[] = $value;
                }
            }
        }
        return $this->_distinctvalues;
    }

    /**
     *
     */
    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
        $fieldid = $this->field->id;
        $fieldname = $this->name();
        // TODO
        // Ugly hack for internal fields
        if ($this->is_internal()) {
            $setting = reset($importsettings);
            $csvname = $setting['name'];
        } else {
            $csvname = $importsettings[$fieldname]['name'];
        }

        if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
            $data->{"field_{$fieldid}_{$entryid}"} = $csvrecord[$csvname];
        }

        return true;
    }

    /**
     *
     */
    public function get_content_from_data($entryid, $data) {
        $fieldid = $this->field->id;
        $content = array();
        foreach ($this->content_names() as $name) {
            $delim = $name ? '_' : '';
            $contentname = "field_{$fieldid}_$entryid". $delim. $name;
            if (isset($data->$contentname)) {
                $content[$name] = $data->$contentname;
            }
        }
        return $content;
    }

    /**
     *
     */
    protected function content_names() {
        return array('');
    }
    
    /**
     *
     */
    protected function format_content($entry, array $values = null) {
        $fieldid = $this->field->id;
        $oldcontents = array();
        $contents = array();
        // old content
        if (isset($entry->{"c{$fieldid}_content"})) {
            $oldcontent = $entry->{"c{$fieldid}_content"};
        } else {
            $oldcontent = null;
        }
        // new content
        if (!empty($values)) {
            $content = reset($values);
            $content = (string) clean_param($content, PARAM_NOTAGS);
        } else {
            $content = null;
        }
        return array(array($content), array($oldcontent));
    }

    /**
     *
     */
    public function get_content_parts() {
        return array('content');
    }

    /**
     *
     */
    public function get_select_sql() {
        if ($this->field->id > 0) {
            $arr = array();
            $arr[] = " c{$this->field->id}.id AS c{$this->field->id}_id ";
            foreach ($this->get_content_parts() as $part) {
                $arr[] = $this->get_sql_compare_text($part). " AS c{$this->field->id}_$part";
            }
            $selectsql = implode(',', $arr);
            return " $selectsql ";
        } else {
            return '';
        }
    }

    /**
     *
     */
    public function get_sort_from_sql($paramname = 'sortie', $paramcount = '') {
        $fieldid = $this->field->id;
        if ($fieldid > 0) {
            $sql = " LEFT JOIN {dataform_contents} c$fieldid ON (c$fieldid.entryid = e.id AND c$fieldid.fieldid = :$paramname$paramcount) ";
            return array($sql, $fieldid);
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

    /**
     *
     */
    public function get_search_from_sql() {
        $fieldid = $this->field->id;
        if ($fieldid > 0) {
            return " JOIN {dataform_contents} c$fieldid ON c$fieldid.entryid = e.id ";
        } else {
            return '';
        }
    }

    /**
     *
     */
    public function get_search_sql($search) {
        global $DB;

        list($not, $operator, $value) = $search;

        static $i=0;
        $i++;
        $fieldid = $this->field->id;
        $name = "df_{$fieldid}_{$i}";

        // For all NOT criteria except NOT Empty, exclude entries
        // which don't meet the positive criterion
        // because some fields may not have content records
        // and the respective entries may be filter out 
        // despite meeting the criterion
        $excludeentries = (($not and $operator !== '') or (!$not and $operator === ''));
        
        if ($excludeentries) {
            $varcharcontent = $DB->sql_compare_text('content');
        } else {
            $varcharcontent = $this->get_sql_compare_text();
        }

        if ($operator === '') {
            list($sql, $params) = $DB->get_in_or_equal('', SQL_PARAMS_NAMED, "df_{$fieldid}_", false);
            $sql = " $varcharcontent $sql ";
        } else if ($operator === '=') {
            $searchvalue = trim($value);
            list($sql, $params) = $DB->get_in_or_equal($searchvalue, SQL_PARAMS_NAMED, "df_{$fieldid}_");
            $sql = " $varcharcontent $sql ";
        } else if ($operator === 'IN') {
            $searchvalue = array_map('trim', $value);
            list($sql, $params) = $DB->get_in_or_equal($searchvalue, SQL_PARAMS_NAMED, "df_{$fieldid}_");
            $sql = " $varcharcontent $sql ";
        } else if (in_array($operator, array('LIKE', 'BETWEEN', ''))) {
            $params = array($name => "%$value%");
            $sql = $DB->sql_like($varcharcontent, ":$name", false);
        } else {
            $params = array($name => "'$value'");
            $sql = " $varcharcontent $operator :$name ";
        }
        // 
        if ($excludeentries) {
            // Get entry ids for entries that meet the criterion
            if ($eids = $this->get_entry_ids_for_content($sql, $params)) {
                // Get NOT IN sql 
                list($notinids, $params) = $DB->get_in_or_equal($eids, SQL_PARAMS_NAMED, "df_{$fieldid}_", false);
                $sql = " e.id $notinids ";
                return array($sql, $params, false);
            } else {
                return null;
            }
        } else {
            return array($sql, $params, true);
        }
    }

    /**
     *
     */
    protected function get_entry_ids_for_content($sql, $params) {
        global $DB;
        
        $sql = " fieldid = :fieldid AND $sql ";
        $params['fieldid'] = $this->id();
        return $DB->get_records_select_menu('dataform_contents', $sql, $params, '', 'id,entryid');
    }
    
    /**
     *
     */
    public function parse_search($formdata, $i) {
        $fieldid = $this->field->id;
        if (!empty($formdata->{"f_{$i}_$fieldid"})) {
            return $formdata->{"f_{$i}_$fieldid"};
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function format_search_value($searchparams) {
        list($not, $operator, $value) = $searchparams;
        return $not. ' '. $operator. ' '. $value;
    }

    /**
     *
     */
    public function get_search_value($value) {
        return $value;
    }

    /**
     *
     */
    protected function get_sql_compare_text($column = 'content') {
        global $DB;

        return $DB->sql_compare_text("c{$this->field->id}.$column");
    }

    /**
     * Whether this field provides join sql for fetching content
     *
     * @return bool
     */
    public function is_joined() {
        return false;
    }

    /**
     * Whether this field content resides in dataform_contents
     *
     * @return bool
     */
    public function is_dataform_content() {
        return true;
    }

    /**
     * Validate form data in entries form
     */
    public function validate($eid, $patterns, $formdata) {
        return $this->renderer()->validate_data($eid, $patterns, $formdata);
    }

    /**
     *
     */
    protected function filearea($suffix = null) {
        if (!empty($suffix)) {
            return 'field-'. str_replace(' ', '_', $suffix);
        } else if (!empty($this->field->name)) {
            return 'field-'. str_replace(' ', '_', $this->field->name);
        } else {
            return false;
        }
    }

}

/**
 * Base class for Dataform field types that require no content
 */
abstract class dataformfield_no_content extends dataformfield_base {
    public function update_content($entry, array $values = null) {
        return true;
    }

    public function delete_content($entryid = 0) {
        return true;
    }

    public function transfer_content($tofieldid) {
        return true;
    }

    public function get_distinct_content($sortdir = 0) {
        return array();
    }

//    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
//        return true;
//    }

    public function get_select_sql() {
        return '';
    }

    public function get_sort_sql() {
        return '';
    }

    /**
     *
     */
    public function is_dataform_content() {
        return false;
    }

    /**
     * TODO
     */
    protected function filearea($suffix = null) {
        return false;
    }
}
