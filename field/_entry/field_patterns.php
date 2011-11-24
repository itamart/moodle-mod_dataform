<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_entry
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

defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/field_patterns.php");

/**
 *
 */
class mod_dataform_field__entry_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $managable = false) {
        // no edit mode
        $replacements = array();
        foreach ($tags as $tag) {
        
            // new entry displays nothing    
            if ($entry->id < 0) {        
                $replacements[$tag] = '';                    
            
            // no edit mode for this field so just return html
            } else {                 
                switch ($tag) {
                    // reference
                    case '##more##':    $str = $this->display_more($entry); break;
                    case '##moreurl##': $str = $this->display_more($entry, true); break;
                    case '##anchor##':  $str = html_writer::tag('a', '', array('name' => $entry->id)); break;    
                    case '##select##':  $str = html_writer::checkbox('entryselector', $entry->id, false); break;                        
                    case '##edit##':    $str = $managable ? $this->display_edit($entry) : ''; break;
                    case '##delete##':  $str = $managable ? $this->display_delete($entry): ''; break;
                    case '##export##':  $str = $this->display_export($entry); break;
                    case '##entryid##': $str = $entry->id; break;
                    default:            $str = $entry->id;
                }
                $replacements[$tag] = array('html', $str);
            } 
        }
        return $replacements;
    }

    /**
     *
     */
    protected function display_more($entry, $url = false) {
        global $OUTPUT;
        $field = $this->_field;
        $baseurl = htmlspecialchars_decode($entry->baseurl);         

        $strmore = get_string('more', 'dataform');
        if ($field->df()->data->singleview) {
            $baseurl = preg_replace('/([\s\S]+)view=\d+([\s\S]*)/', '$1view='. $field->df()->data->singleview. '$2', $baseurl);
        }
        
        $moreurl = $baseurl. '&eid='. $entry->id;
        if (!$url) {
            return html_writer::link($moreurl, $OUTPUT->pix_icon('i/search', $strmore));
         } else {
            return $moreurl;
        }
    }

    /**
     *
     */
    protected function display_edit($entry) {
        global $OUTPUT;

        $field = $this->_field;
        $baseurl = htmlspecialchars_decode($entry->baseurl);         
        if ($field->df()->data->singleedit) {
            $baseurl = preg_replace('/([\s\S]+)view=\d+([\s\S]*)/', '$1view='. $field->df()->data->singleedit. '$2', $baseurl). '&eid='. $entry->id;
        }
        $editurl = $baseurl. '&editentries='. $entry->id. '&sesskey='. sesskey();
        $stredit = get_string('edit');
        return html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', $stredit));
    }

    /**
     *
     */
    protected function display_delete($entry) {
        global $OUTPUT;

        $field = $this->_field;
        $baseurl = htmlspecialchars_decode($entry->baseurl);         
        $strdelete = get_string('delete');
        $deleteurl = $baseurl. '&delete='. $entry->id. '&sesskey='. sesskey();
        return html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', $strdelete));
    }

    /**
     *
     */
    protected function display_export($entry) {
        global $OUTPUT;

        $canexportentry = $this->_field->df()->user_can_export_entry($entry);
        if ($canexportentry) {
            $field = $this->_field;
            $baseurl = htmlspecialchars_decode($entry->baseurl);         
            $strexport = get_string('export', 'dataform');
            $exporturl = $baseurl. '&export='. $entry->id. '&sesskey='. sesskey();
            return html_writer::link($exporturl, $OUTPUT->pix_icon('t/portfolioadd', $strexport));
        } else {
            return '';
        }
    }


    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        global $CFG;

        $patterns = array();
        
        // actions
        $actions = get_string('actions', 'dataform');
        $patterns["##edit##"] = array(true, $actions);
        $patterns["##delete##"] = array(true, $actions);
        $patterns["##select##"] = array(true, $actions);
        if ($CFG->enableportfolios) {
            $patterns["##export##"] = array(true, $actions);
        }
        
        // reference
        $reference = get_string('reference', 'dataform');
        $patterns["##anchor##"] = array(true, $reference);
        $patterns["##more##"] = array(true, $reference);
        $patterns["##moreurl##"] = array(true, $reference);

        // entryinfo
        $entryinfo = get_string('entryinfo', 'dataform');
        $patterns["##entryid##"] = array(true, $entryinfo);

        return $patterns; 
    }
}
