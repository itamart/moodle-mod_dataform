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
    public $df = NULL;       // The dataform object that this field belongs to
    public $field = NULL;      // The field object itself, if we know it

    public $iconwidth = 16;    // Width of the icon for this fieldtype
    public $iconheight = 16;   // Width of the icon for this fieldtype

    public function __construct($df = 0, $field = 0) {   // Field or dataform or both, field can be id or object, dataform id or df

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

        if (!empty($oldcontent)) {
            if ($content != $oldcontent) {
                if (empty($content)) {
                    $this->delete_content($entry->id);
                } else {
                    $rec->id = $contentid; // MUST_EXIST
                    return $DB->update_record('dataform_contents', $rec);
                 }
            }
        } else {
            if (!empty($content)) {
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

        $src = $OUTPUT->pix_url('icon', "dataformfield_{$this->type}");
 
        return html_writer::empty_tag('img',
                    array('src' => $src,
                        'height' => $this->iconheight,
                        'width' => $this->iconwidth,
                        'alt' => $this->type,
                        'title' => $this->type));

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
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        $fieldname = $this->field->name;
        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns = array('fields' => array('fields' => array()));
            $patterns['fields']['fields']["[[$fieldname]]"] = "[[$fieldname]]";

        } else {
            // there is only one possible tag here so no check
            $patterns = array();

            if ($edit) {
                $patterns["[[$fieldname]]"] = array('', array(array($this,'display_edit'), array($entry)));
            } else {
                $patterns["[[$fieldname]]"] = array('html', $this->display_browse($entry));
            }
        }

        return $patterns;
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
     * Per default, it is assumed that the field supports text exporting. Override this (return false) on fields not supporting text exporting.
     */
    public function export_text_supported() {
        return true;
    }

    /**
     * 
     */
    public function prepare_import_content(&$data, $entryid, $value) {
        $fieldid = $this->field->id;
        
        $data->{"field_{$fieldid}_{$entryid}"} = $value;
    
        return true;
    }

    /**
     * Per default, return the record's text value only from the "content" field.
     * Override this in user fields class if necesarry.
     * Override in internal fields class.
     */
    public function export_text_value($content) {
        if ($this->export_text_supported()) {
            return $content->content;
        }
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
    public function display_edit(&$mform, $entry = null) {
    }

    /**
     *
     */
    public function display_browse($entry, $params = null) {

        $fieldid = $this->field->id;

        if (isset($entry->{"c$fieldid". '_content'})) {
            $content = $entry->{"c$fieldid". '_content'};

            $options = new object();
            $options->para = false;

            $format = FORMAT_PLAIN;
            if ($this->field->param1 == '1') {  // We are autolinking this field, so disable linking within us
                $content = '<span class="nolink">'. $content .'</span>';
                $format = FORMAT_HTML;
                $options->filter=false;
            }

            $str = format_text($content, $format, $options);
        } else {
            $str = '';
        }
        
        return $str;
    }

    /**
     * Just in case a field needs to print something before the whole form
     */
    public function print_before_form() {
        return '';
    }

    /**
     * Just in case a field needs to print something after the whole form
     */
    public function print_after_form() {
        return '';
    }

    /**
     *
     */
    public function display_search($mform, $i = 0, $value = '') {
        $mform->addElement('text', 'f_'. $i. '_'. $this->field->id, null, array('size'=>'32'));
        $mform->setType('f_'. $i. '_'. $this->field->id, PARAM_NOTAGS);
        $mform->setDefault('f_'. $i. '_'. $this->field->id, $value);
    }

    /**
     *
     */
    public function parse_search($formdata, $i) {
        if (!empty($formdata->{'f_'. $i. '_'. $this->field->id})) {
            return $formdata->{'f_'. $i. '_'. $this->field->id};
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
    static public function file_ok($relativepath) {
        return false;
    }
}


/**
 *
 */
abstract class dataform_field_single_menu extends dataform_field_base {

    public $type = '';
    
    protected $_cats = array();

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        $patterns = parent::patterns($tags, $entry, $edit, $editable);

        $fieldname = $this->field->name;
        
        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns['fields']['fields']["[[$fieldname:cat]]"] = "[[$fieldname:cat]]";

        } else {
            // no edit mode for these tags
            foreach ($tags as $tag) {
                switch ($tag) {
                    case "[[$fieldname:cat]]":
                        if ($edit) {
                            $patterns[$tag] = array('', array(array($this,'display_edit'), array($entry)));
                        } else {    
                            $patterns[$tag] = array('html', $this->display_category($entry));
                        }
                        break;
                }
            }
        }

        return $patterns;
    }

    /**
     * 
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        $fieldid = $this->field->id;
        
        $selected = $newvalue = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                $names = explode('_', $name);
                if (!empty($names[3]) and !empty($value)) {
                    ${$names[3]} = $value;
                }
            }
        }

        if ($newvalue = s($newvalue)) {
            $options = $this->options_menu();
            if (!$selected = (int) array_search($newvalue, $options)) {
                $selected = count($options) + 1;
                $this->field->param1 = trim($this->field->param1). "\n$newvalue";
                $this->update_field();
            }
        }

        $oldcontent = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        
        $rec = new object();
        $rec->fieldid = $this->field->id;
        $rec->entryid = $entry->id;
        $rec->content = $selected;

        if (!empty($oldcontent)) {
            if ($selected != $oldcontent) {
                if (empty($selected)) {
                    $this->delete_content($entry->id);
                } else {
                    $rec->id = $contentid; // MUST_EXIST
                    return $DB->update_record('dataform_contents', $rec);
                 }
            }
        } else {
            if (!empty($selected)) {
                return $DB->insert_record('dataform_contents', $rec);
            }
        }
        return true;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {

        $entryid = $entry->id;
        $options = $this->options_menu();
        $selected = 0;
        
        if ($entryid > 0){
            $selected = (int) $entry->{'c'. $this->field->id. '_content'};
        }
        
        // check for default value
        if (!$selected and $this->field->param2) {
            $selected = (int) array_search($this->field->param2, $options);
        }

        $fieldname = "field_{$this->field->id}_$entryid";
        $this->render($mform, "{$fieldname}_selected", $options, $selected);

        // add option
        if ($this->field->param4 or has_capability('mod/dataform:managetemplates', $this->df->context)) {
            $mform->addElement('text', "{$fieldname}_newvalue", get_string('newvalue', 'dataform'));
            $mform->disabledIf("{$fieldname}_newvalue", "{$fieldname}_selected", 'neq', 0);
        }
    }

    /**
     *
     */
    public function display_browse($entry, $params = null) {

        $fieldid = $this->field->id;
        $str = '';

        if (isset($entry->{"c$fieldid". '_content'})) {
            $selected = (int) $entry->{"c$fieldid". '_content'};

            $options = $this->options_menu();
            if ($selected and $selected <= count($options)) {
                $str = $options[$selected];
            }
        }
        
        return $str;
    }

    /**
     *
     */
    public function display_category($entry, $params = null) {
        $fieldid = $this->field->id;
        if (!isset($this->_cats[$fieldid])) {
            $this->_cats[$fieldid] = null;
        }

        $str = '';
        if (isset($entry->{"c$fieldid". '_content'})) {
            $selected = (int) $entry->{"c$fieldid". '_content'};
            
            $options = $this->options_menu();
            if ($selected and $selected <= count($options) and $selected != $this->_cats[$fieldid]) {
                $this->_cats[$fieldid] = $selected;
                $str = $options[$selected];
            }
        }
        
        return $str;
    }

    /**
     * 
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        $options = $this->options_menu();
        $selected = (int) array_search($value, $options);
        $fieldname = "f_{$i}_{$this->field->id}";
        $this->render($mform, $fieldname, $options, $selected);
    }

    /**
     * 
     */
    function get_sql_compare_text() {
        global $DB;
        return $DB->sql_compare_text("c{$this->field->id}.content", 255);
    }

    /**
     * 
     */
    protected function options_menu() {
        $rawoptions = explode("\n",$this->field->param1);
        $options = array();
        $key = 1;
        foreach ($rawoptions as $option) {
            $option = trim($option);
            if ($option) {
                $options[$key] = $option;
                $key++;
            }
        }
        return $options;
    }


    /**
     * 
     */
    protected abstract function render(&$mform, $fieldname, $options, $selected);
}

/**
 *
 */
abstract class dataform_field_multi_menu extends dataform_field_base {

    public $type = '';
    public $separators = array(
            array('name' => 'New line', 'chr' => '<br />'),
            array('name' => 'Space', 'chr' => '&#32;'),
            array('name' => ',', 'chr' => '&#44;'),
            array('name' => ', (with space)', 'chr' => '&#44;&#32;')
    );

    
    /**
     *
     */
    public function display_edit(&$mform, $entry) {

        $entryid = $entry->id;
        $options = $this->options_menu();
        $selected = array();

        if ($entryid > 0){
            if ($content = s($entry->{"c{$this->field->id}_content"})) {
                $selected = explode('#', $content);
            }
        }
        
        // check for default values
        if (!$selected and $this->field->param2) {
            $selected = $this->default_values();
        }

        $fieldname = "field_{$this->field->id}_$entryid";
        $this->render($mform, "{$fieldname}_selected", $options, $selected);

        // add option
        if ($this->field->param4 or has_capability('mod/dataform:managetemplates', $this->df->context)) {
            $mform->addElement('text', "{$fieldname}_newvalue", get_string('newvalue', 'dataform'));
            $mform->disabledIf("{$fieldname}_newvalue", "{$fieldname}_selected", 'neq', 0);
        }

    }

    /**
     *
     */
    public function display_browse($entry, $params = null) {

        $fieldid = $this->field->id;

        if (isset($entry->{"c$fieldid". '_content'})) {
            $content = $entry->{"c$fieldid". '_content'};

            $options = $this->options_menu();
            $optionscount = count($options);

            $contents = explode('#', $content);

            $str = array();           
            foreach ($contents as $cont) {
                if (!$cont = (int) $cont or $cont > $optionscount) {
                    // hmm, looks like somebody edited the field definition
                    continue;
                }
                $str[] = $options[$cont];
            }

            $str = implode($this->separators[(int) $this->field->param3]['chr'], $str);;
        } else {
            $str = '';
        }
        
        return $str;
    }
    
    /**
     *
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        
        if (is_array($value)){
            $selected     = $value['selected'];
            $allrequired = $value['allrequired'] ? 'checked = "checked"' : '';
        } else {
            $selected     = array();
            $allrequired = '';
        }

        $options = $this->options_menu();

        $fieldname = "f_{$i}_{$this->field->id}";
        $this->render($mform, $fieldname, $options, $selected);
        
        $mform->addElement('checkbox', "{$fieldname}_allreq", null, ucfirst(get_string('requiredall', 'dataform')));
        $mform->setDefault("{$fieldname}_allreq", $allrequired);
    }

    /**
     *
     */
    public function format_search_value($searchparams) {
        list($not, $operator, $value) = $searchparams;
        if (is_array($value)){
            $selected = implode(', ', $value['selected']);
            $allrequired = '('. ($value['allrequired'] ? get_string('requiredall') : get_string('requirednotall')). ')';
            return $not. ' '. $operator. ' '. $selected. ' '. $allrequired;
        } else {
            return false;
        }
    }  

    /**
     *
     */
    public function get_search_sql($search) {
        global $DB;
        
        list($not, , $value) = $search;

        static $i=0;
        $i++;
        $name = "df_{$this->field->id}_{$i}_";
        $params = array();

        $allrequired = $value['allrequired'];
        $selected    = $value['selected'];
        $content = "c{$this->field->id}.content";
        $varcharcontent = $DB->sql_compare_text($content, 255);

        if ($selected) {
            $conditions = array();
            foreach ($selected as $key => $sel) {
                $xname = $name. $key;
                $likesel = str_replace('%', '\%', $sel);
                $likeselsel = str_replace('_', '\_', $likesel);

                $conditions[] = "({$varcharcontent} = :{$xname}a".
                                   ' OR '. $DB->sql_like($content, ":{$xname}b").
                                   ' OR '. $DB->sql_like($content, ":{$xname}c").
                                   ' OR '. $DB->sql_like($content, ":{$xname}d"). ")";
                                   
                $params[$xname.'a'] = $sel;
                $params[$xname.'b'] = "$likesel#%";
                $params[$xname.'c'] = "%#$likesel";
                $params[$xname.'d'] = "%#$likesel#%";
            }
            if ($allrequired) {
                return array(" $not (".implode(" AND ", $conditions).") ", $params);
            } else {
                return array(" $not (".implode(" OR ", $conditions).") ", $params);
            }
        } else {
           return array(" ", $params);
        }
    }

    /**
     * 
     */
    protected function options_menu() {
        $rawoptions = explode("\n",$this->field->param1);

        $options = array();
        $key = 1;
        foreach ($rawoptions as $option) {
            $option = trim($option);
            if ($option) {
                $options[$key] = $option;
                $key++;
            }
        }
        return $options;
    }

    /**
     * 
     */
    protected function default_values() {
        $rawdefaults = explode("\n",$this->field->param2);
        $options = $this->options_menu();

        $defaults = array();
        foreach ($rawdefaults as $default) {
            $default = trim($default);
            if ($default and $key = array_search($default, $options)) {
                $defaults[] = $key;
            }
        }
        return $defaults;
    }

    /**
     *
     */
    public function format_content($content) {
        if (!empty($content)) {
            // content from form
            if (is_array($content)) {
                $content = $this->get_content($content);
                $optionscount = count(explode("\n", $this->field->param1));

                $vals = array();
                foreach ($content as $key => $val) {
                    if ($key === 'xxx') {
                        continue;
                    }
                    if ((int) $val > $optionscount) {
                        continue;
                    }
                    $vals[] = $val;
                }

                if (empty($vals)) {
                    return null;
                } else {
                    return implode('#', $vals);
                }
            
            // content from import
            } else {
                return $content;
            }
        } else {
            return null;
        }
    }

    /**
     *
     */
    protected function get_content($content) {
        return $content;
    }

    /**
     * 
     */
    protected abstract function render(&$mform, $fieldname, $options, $selected);
}

