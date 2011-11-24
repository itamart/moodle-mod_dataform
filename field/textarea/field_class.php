<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-textarea
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on Database module may obtain, including:
 * @copyright 1999 Moodle Pty Ltd http://moodle.com
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

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/repository/lib.php');

class dataform_field_textarea extends dataform_field_base {

    public $type = 'textarea';

    protected $editoroptions;

    public function __construct($df = 0, $field = 0) {
        parent::__construct($df, $field);

        $this->editoroptions = array();
        $this->editoroptions['context'] = $this->df->context;
        $this->editoroptions['trusttext'] = $this->field->param4;
        $this->editoroptions['maxbytes'] = $this->field->param5;
        $this->editoroptions['maxfiles'] = $this->field->param6;
        $this->editoroptions['subdirs'] = false;
        $this->editoroptions['changeformat'] = 0;
        $this->editoroptions['forcehttps'] = false;
        $this->editoroptions['noclean'] = false;
    }

    /**
     * Sets up a field object
     */
    public function set_field($forminput = null) {
        parent::set_field($forminput);

        // sets some defaults
        if (is_null($forminput)) {
            // is editor
            $this->field->param1 = 1;
            // cols
            $this->field->param2 = 40;
            // rows
            $this->field->param3 = 35;
            // trust text
            $this->field->param4 = 0;
            // max files
            $this->field->param6 = -1;
        }

        return true;
    }

    /**
     *
     */
    public function is_editor() {
        return $this->field->param1;
    }

    /**
     *
     */
    public function editor_options() {
        return $this->editoroptions;
    }

    /**
     *
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        $entryid = $entry->id;
        $fieldid = $this->field->id;

        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;

        $fieldname = "field_{$fieldid}_{$entry->id}";

        if (!empty($values)) {
            $data = (object) $values;
        } else {
            return true;
        }

        $rec = new object;
        $rec->fieldid = $fieldid;
        $rec->entryid = $entryid;

        if (!$contentid) {
            $contentid = $DB->insert_record('dataform_contents', $rec);
        }

        // check if the content is from a new entry
        // in which case entry id in the data is < 0
        $names = explode('_',key($values));
        if ((int) $names[2] < 0) {
            $adjustedfieldname = "field_{$fieldid}_{$names[2]}";
        } else {
            $adjustedfieldname = "field_{$fieldid}_{$entry->id}";
        }        

        $data = file_postupdate_standard_editor($data, $adjustedfieldname, $this->editoroptions, $this->df->context, 'mod_dataform', 'content', $contentid);

        $rec->content = $data->{$adjustedfieldname};
        $rec->content1 = $data->{"{$adjustedfieldname}format"};
        $rec->id = $contentid;
        return $DB->update_record('dataform_contents', $rec);
    }

    /**
     *
     */
    public function get_select_sql() {
        $id = " c{$this->field->id}.id AS c{$this->field->id}_id ";
        $content = $this->get_sql_compare_text(). " AS c{$this->field->id}_content";
        $content1 = " c{$this->field->id}.content1 AS c{$this->field->id}_content1";
        return " $id , $content , $content1 ";
    }

    /**
     * 
     */
    public function prepare_import_content(&$data, $entryid, $value) {
        $fieldid = $this->field->id;
        
        $valuearr = explode('##', $value);
        $content = array();
        $content['text'] = !empty($valuearr[0]) ? $valuearr[0] : null;
        $content['format'] = !empty($valuearr[1]) ? $valuearr[1] : FORMAT_MOODLE;
        $content['trust'] = !empty($valuearr[2]) ? $valuearr[2] : $this->field->param4;
        $data->{"field_{$fieldid}_{$entryid}_editor"} = $content;
    
        return true;
    }

}
