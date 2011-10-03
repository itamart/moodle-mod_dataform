<?php

/**
 * This file is part of the Dataform module for Moodle
 *
 * @copyright 2011 Moodle contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod-dataform
 *
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field__entry extends dataform_field_base {

    public $type = '_entry';
    
    /**
     * 
     */
    public function update_content($entryid, array $values = null) {
        return true;
    }

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $enabled = false) {
        global $OUTPUT;

        $actions = array(
                    '##edit##',
                    '##delete##',
                    '##select##');
        
        $reference = array( 
                    '##anchor##',
                    '##more##',
                    '##moreurl##');
        
        $entryinfo = array(
                    '##entryid##');


        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns = array('actions' => array('actions' => array()),
                               'reference' => array('reference' => array()),
                               'entryinfo' => array('entryinfo' => array()));
                               
            // TODO use get strings
            foreach ($actions as $pattern) {
                $patterns['actions']['actions'][$pattern] = $pattern;
            }

            foreach ($reference as $pattern) {
                $patterns['reference']['reference'][$pattern] = $pattern;
            }

            foreach ($entryinfo as $pattern) {
                $patterns['entryinfo']['entryinfo'][$pattern] = $pattern;
            }
            
        } else {
        
            $patterns = array();
            
            foreach ($tags as $tag) {
            
                if ($entry->id < 0) { // new entry displays nothing            
                    $patterns[$tag] = '';                    
                
                // no edit mode for this field so just return html
                } else { 
                    
                    switch ($tag) {
                        // reference
                        case '##more##':
                        case '##moreurl##':
                            $strmore = get_string('more', 'dataform');
                            if ($this->df->data->singleview) {
                                $baseurl = preg_replace('/([\s\S]+)view=\d+([\s\S]*)/', '$1view='. $this->df->data->singleview. '$2', $entry->baseurl);
                            } else {
                                $baseurl = $entry->baseurl;
                            }
                            $moreurl = $baseurl. '&amp;eid='. $entry->id;
                            if ($tag == '##more##') {
                                $patterns['##more##'] = array('html', '<a href="' . $moreurl . '">'.
                                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/search'), 'class' => "iconsmall", 'alt' => $strmore, 'title' => $strmore)).
                                            '</a>');
                            } else {
                                $patterns['##moreurl##'] = array('html', $moreurl);
                            }
                            break;

                        case '##anchor##':
                            $patterns['##anchor##'] = array('html',
                                    html_writer::tag('a', '', array('name' => $entry->id)));

                        case '##select##':
                            // TODO: should allow selecting for duplicating purposes
                            $patterns['##select##'] = !$enabled ? '' : array('html', html_writer::checkbox('entry_selector', $entry->id, false));
                            break;
                            
                        case '##edit##':
                            $stredit = get_string('edit');
                            if ($this->df->data->singleedit) {
                                $baseurl = preg_replace('/([\s\S]+)view=\d+([\s\S]*)/', '$1view='. $this->df->data->singleedit. '$2', $entry->baseurl). '&amp;eid='. $entry->id;
                            } else {
                                $baseurl = $entry->baseurl;
                            }
                            $editurl = $baseurl. '&amp;editentries='. $entry->id. '&amp;sesskey='. sesskey();
                            $patterns['##edit##'] = !$enabled ? '' : 
                                array('html', '<a href="'. $editurl. '">'.
                                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'), 'class' => "iconsmall", 'alt' => $stredit, 'title' => $stredit)).
                                '</a>');
                            break;

                        case '##delete##':
                            $strdelete = get_string('delete');
                            $patterns['##delete##'] = !$enabled ? '' : 
                                array('html', '<a href="'. $entry->baseurl. '&amp;delete='. $entry->id. '&amp;sesskey='. sesskey(). '">'.
                                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'class' => "iconsmall", 'alt' => $strdelete, 'title' => $strdelete)).
                                '</a>');
                            break;

                        case '##entryid##':
                            $patterns['##entryid##'] = array('html', $entry->id);
                            break;
                    }
                } 
            }
            
        }       
            
        return $patterns;
    }
            
    /**
     * 
     */
    public function display_search($mform, $i) {
        return '';
    }
    
    /**
     * 
     */
    public function get_search_sql($search) {
        return array(" ", array());
    }

    /**
     * 
     */
    public function parse_search($formdata, $i) {
        return '';
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return '';
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        return false;
    }

    /**
     *
     */
    public function export_text_value($entry) {
        return $entry->id;
    }

}
