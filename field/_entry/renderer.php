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
 * @subpackage _entry
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield__entry_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $managable = !empty($options['managable']) ? $options['managable'] : false;
        
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
                    case '##entryid##': $str = $entry->id; break;
                    case '##more##': $str = $this->display_more($entry); break;
                    case '##moreurl##': $str = $this->display_more($entry, true); break;
                    case '##anchor##': $str = html_writer::tag('a', '', array('name' => $entry->id)); break;    
                    // Actions
                    case '##select##': $str = html_writer::checkbox('entryselector', $entry->id, false); break;                        
                    case '##edit##': $str = $managable ? $this->display_edit($entry) : ''; break;
                    case '##delete##': $str = $managable ? $this->display_delete($entry): ''; break;
                    case '##export##': $str = $this->display_export($entry); break;
                    case '##duplicate##': $str = $managable ? $this->display_duplicate($entry) : ''; break;
                    default: $str = '';
                }
                $replacements[$tag] = array('html', $str);
            } 
        }
        return $replacements;
    }

    /**
     *
     */
    protected function display_more($entry, $href = false) {
        global $OUTPUT;
        
        $field = $this->_field;
        $params = array(
            'eids' => $entry->id
        );       
        $url = new moodle_url($entry->baseurl, $params);         
        if ($field->df()->data->singleview) {
            $url->param('ret', $url->param('view'));
            $url->param('view', $field->df()->data->singleview);
        }
        $str = get_string('more', 'dataform');
        if (!$href) {
            return html_writer::link($url->out(false), $OUTPUT->pix_icon('i/search', $str));
         } else {
            return $url->out(false);
        }
    }

    /**
     *
     */
    protected function display_edit($entry) {
        global $OUTPUT;

        $field = $this->_field;
        $params = array(
            'editentries' => $entry->id,
            'sesskey' => sesskey()
        );       
        $url = new moodle_url($entry->baseurl, $params);         
        if ($field->df()->data->singleedit) {
            $url->param('view', $field->df()->data->singleedit);
            $url->param('eids', $entry->id);
        }
        $str = get_string('edit');
        return html_writer::link($url->out(false), $OUTPUT->pix_icon('t/edit', $str));
    }

    /**
     *
     */
    protected function display_duplicate($entry) {
        global $OUTPUT;

        $field = $this->_field;
        $params = array(
            'duplicate' => $entry->id,
            'sesskey' => sesskey()
        );       
        $url = new moodle_url($entry->baseurl, $params);         
        if ($field->df()->data->singleedit) {
            $url->param('view', $field->df()->data->singleedit);
        }
        $str = get_string('copy');
        return html_writer::link($url->out(false), $OUTPUT->pix_icon('t/copy', $str));
    }

    /**
     *
     */
    protected function display_delete($entry) {
        global $OUTPUT;

        $field = $this->_field;
        $params = array(
            'delete' => $entry->id,
            'sesskey' => sesskey()
        );       
        $url = new moodle_url($entry->baseurl, $params);         
        $str = get_string('delete');
        return html_writer::link($url->out(false), $OUTPUT->pix_icon('t/delete', $str));
    }

    /**
     *
     */
    protected function display_export($entry) {
        global $CFG, $OUTPUT;

        if (!$CFG->enableportfolios) {
            return '';
        }

        $str = '';
        $canexportentry = $this->_field->df()->user_can_export_entry($entry);
        if ($canexportentry) {
            $field = $this->_field;
            $url = new moodle_url($entry->baseurl, array('export' => $entry->id, 'sesskey' => sesskey()));
            $strexport = get_string('export', 'dataform');
            return html_writer::link($url, $OUTPUT->pix_icon('t/portfolioadd', $strexport));
        }
        return $str;
    }


    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $patterns = array();
        
        // actions
        $actions = get_string('actions', 'dataform');
        $patterns["##edit##"] = array(true, $actions);
        $patterns["##delete##"] = array(true, $actions);
        $patterns["##select##"] = array(true, $actions);
        $patterns["##export##"] = array(true, $actions);
        $patterns["##duplicate##"] = array(true, $actions);
        
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
