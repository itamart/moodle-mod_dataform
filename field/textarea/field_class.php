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
 * @subpackage dataformfield-textarea
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/repository/lib.php');

class dataform_field_textarea extends dataform_field_base {

    public $type = 'textarea';

    protected $editoroptions;

    public function __construct($df = 0, $field = 0) {
        parent::__construct($df, $field);

        $trust = !empty($this->field->param4) ? $this->field->param4 : 0;
        $maxbytes = !empty($this->field->param5) ? $this->field->param5 : 0;
        $maxfiles = !empty($this->field->param6) ? $this->field->param6 : -1;
        
        $this->editoroptions = array();
        $this->editoroptions['context'] = $this->df->context;
        $this->editoroptions['trusttext'] = $trust;
        $this->editoroptions['maxbytes'] = $maxbytes;
        $this->editoroptions['maxfiles'] = $maxfiles;
        $this->editoroptions['subdirs'] = false;
        $this->editoroptions['changeformat'] = 0;
        $this->editoroptions['forcehttps'] = false;
        $this->editoroptions['noclean'] = false;
    }

    /**
     *
     */
    public function is_editor() {
        return !empty($this->field->param1);
    }

    /**
     *
     */
    public function editor_options() {
        return $this->editoroptions;
    }

    /**
     * Override parent to set key as the content name
     */
    public function get_content_from_data($entryid, $data) {
        $fieldid = $this->field->id;
        $content = array();
        $contentname = "field_{$fieldid}_$entryid";
        if (isset($data->$contentname)) {
            $content[$contentname] = $data->$contentname;
        }
        return $content;
    }

    /**
     *
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        $entryid = $entry->id;
        $fieldid = $this->field->id;

        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;

        if (empty($values)) {
            return true;
        }

        $rec = new object;
        $rec->fieldid = $fieldid;
        $rec->entryid = $entryid;

        if (!$rec->id = $contentid) {
            $rec->id = $DB->insert_record('dataform_contents', $rec);
        }        
        
        // Editor content
        if ($this->is_editor()) {
            $data = (object) $values;
            // check if the content is from a new entry
            // in which case entry id in the data is < 0
            $names = explode('_',key($values));
            if ((int) $names[2] < 0) {
                $adjustedfieldname = "field_{$fieldid}_{$names[2]}";
            } else {
                $adjustedfieldname = "field_{$fieldid}_{$entry->id}";
            }        

            $data = file_postupdate_standard_editor($data, $adjustedfieldname, $this->editoroptions, $this->df->context, 'mod_dataform', 'content', $rec->id);

            $rec->content = $data->{$adjustedfieldname};
            $rec->content1 = $data->{"{$adjustedfieldname}format"};

        // Text area content
        } else {
            $value = reset($values);           
            if (is_array($value)) {
                // Import: One value as array of text,format,trust, so take the text
                $value = reset($value);
            }                
            $rec->content = clean_param($value, PARAM_NOTAGS);
        }            
        
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
    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
        $fieldid = $this->field->id;
   
        parent::prepare_import_content($data, $importsettings, $csvrecord, $entryid);
         
        if (isset($data->{"field_{$fieldid}_{$entryid}"})) {
            $valuearr = explode('##', $data->{"field_{$fieldid}_{$entryid}"});
            $content = array();
            $content['text'] = !empty($valuearr[0]) ? $valuearr[0] : null;
            $content['format'] = !empty($valuearr[1]) ? $valuearr[1] : FORMAT_MOODLE;
            $content['trust'] = !empty($valuearr[2]) ? $valuearr[2] : $this->editoroptions['trusttext'];
            $data->{"field_{$fieldid}_{$entryid}_editor"} = $content;
            unset($data->{"field_{$fieldid}_{$entryid}"});
        }
        
        return true;
    }

}
