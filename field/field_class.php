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
 * certain copyrights on Database module may obtain, including:
 * @copyright 1999 Martin Dougiamas {@link http://moodle.com}
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

require_once("$CFG->dirroot/mod/dataform/mod_class.php");

/**
 * Base class for Dataform Field Types
 * (see field/<field type>/field_class.php)
 */

class dataform_field_base {

    public $type = 'unknown';  // Subclasses must override the type with their name
    public $df = null;       // The dataform object that this field belongs to
    public $field = null;      // The field object itself, if we know it

    protected $_patterns = null;      // The field object itself, if we know it

    /**
     * Class constructor
     *
     * @param var $df       dataform id or class object
     * @param var $field    field id or DB record
     */
    public function __construct($df = 0, $field = 0) {

        if (empty($df)) {
            print_error('Programmer error: You must specify dataform id or object when defining a field class. ');
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
                print_error('Bad field ID encountered: '.$field);
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
        for ($i=1; $i<=10; $i++) {
            $this->field->{'param'.$i} = !empty($forminput->{'param'.$i}) ? trim($forminput->{'param'.$i}) : null;
        }
        return true;
    }

    /**
     * Insert a new field in the database
     */
    public function insert_field($fromform = null) {
        global $DB, $OUTPUT;

        if (!empty($fromform)) {
            $this->set_field($fromform);
        }

        if (empty($this->field)) {
            print_error('Programmer error: Field has not been defined yet!  See define_field()');
            return false;
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
     * 
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        $fieldid = $this->field->id;
        $content = $this->format_content($values);
        
        $oldcontent = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        
        $rec = new object();
        $rec->fieldid = $this->field->id;
        $rec->entryid = $entry->id;
        $rec->content = $content;

        if (!is_null($oldcontent)) {
            if ($content !== $oldcontent) {
                if (is_null($content) or $content === '') {
                    $this->delete_content($entry->id);
                } else {
                    $rec->id = $contentid; // MUST_EXIST
                    return $DB->update_record('dataform_contents', $rec);
                 }
            }
        } else {
            if (!is_null($content) and $content !== '') {
                return $DB->insert_record('dataform_contents', $rec);
            }
        }
        return true;
    }

    /**
     *
     */
    public function format_content(array $values = null) {
        if (!empty($values)) {
            return clean_param(reset($values), PARAM_NOTAGS);
        } else {
            return null;
        }
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
/*
        $fieldid = $this->field->id;
        $sortdir = $sortdir ? 'DESC' : 'ASC';
        $content = $DB->sql_compare_text("c$fieldid.". $this->get_sort_field());
        $contentfull = $this->get_sort_sql($content);
        $sql = "SELECT DISTINCT $contentfull
                    FROM {dataform_contents} c$fieldid
                    WHERE c$fieldid.fieldid = $fieldid AND $contentfull IS NOT NULL
                    ORDER BY $contentfull $sortdir";

        $distinctvalues = array();
        if ($options = $DB->get_records_sql($sql)) {
            foreach ($options as $data) {
                $value = $data->content;
                if ($value === '') {
                    continue;
                }
                $distinctvalues[] = $value;
            }
        }
        return $distinctvalues;
*/
        return array();
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

        $custom_data = array('field' => $this,
                            'df' => $this->df);
        require_once($CFG->dirroot. '/mod/dataform/field/'. $this->type. '/field_form.php');
        $formclass = 'mod_dataform_field_'. $this->type. '_form';
        $actionurl = new moodle_url('/mod/dataform/field/field_edit.php',
                                    array('d' => $this->df->id(), 'id' => $this->id())); 
        return new $formclass($actionurl, $custom_data);
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
    public function patterns() {
        global $CFG;
        
        if (!$this->_patterns) {
            $patternsclass = "mod_dataform_field_{$this->type}_patterns";
            require_once("$CFG->dirroot/mod/dataform/field/{$this->type}/field_patterns.php");
            $this->_patterns = new $patternsclass($this);
        }
        return $this->_patterns;
    }

    /**
     * Check if a field from an add form is empty
     */
    public function notemptyfield($value, $name) {
        return !empty($value);
    }

    /**
     *
     */
    public function get_select_sql() {
        if ($this->field->id > 0) {
            $id = " c{$this->field->id}.id AS c{$this->field->id}_id ";
            $content = $this->get_sql_compare_text(). " AS c{$this->field->id}_content";
            return " $id , $content ";
        } else {
            return '';
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
    public function get_search_sql($search) {
        global $DB;

        list($not, $operator, $value) = $search;

        static $i=0;
        $i++;
        $name = "df_{$this->field->id}_{$i}";

        $varcharcontent = $this->get_sql_compare_text();

        if ($operator === 'IN') {
            $terms = preg_split("/\s*,\s*/", trim($value));
            $searchvalue = implode("','", $terms);
            return array(" $varcharcontent $not IN (:$name) ", array($name => $searchvalue));
        } else if (in_array($operator, array('LIKE', 'BETWEEN', ''))) {
            $params = array($name => "%$value%");
            if ($not) {
                return array($DB->sql_like($varcharcontent, ":$name", false, true, true), $params);
            } else {
                return array($DB->sql_like($varcharcontent, ":$name", false), $params);
            }
        } else {
            return array(" $not $varcharcontent $operator :$name ", array($name => "'$value'"));
        }
    }

    /**
     *
     */
    public function get_sql_compare_text() {
        global $DB;

        return $DB->sql_compare_text("c{$this->field->id}.content");
    }

    /**
     * 
     */
    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
        $fieldid = $this->field->id;
        $fieldname = $this->name();
        $csvname = $importsettings[$fieldname]['name'];
        
        if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
            $data->{"field_{$fieldid}_{$entryid}"} = $csvrecord[$csvname];
        }
    
        return true;
    }

    /**
     *
     */
    public function filearea($suffix = null) {
        if (!empty($suffix)) {
            return 'field-'. str_replace(' ', '_', $suffix);
        } else if (!empty($this->field->name)) {
            return 'field-'. str_replace(' ', '_', $this->field->name);
        } else {
            return false;
        }
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
     * @param string $relativepath
     * @return bool false
     */
    public static function file_ok($relativepath) {
        return false;
    }
}
