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
 * @subpackage url
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield_url extends dataformfield_base {
    public $type = 'url';

    /**
     *
     */
    protected function content_names() {
        return array('url', 'alt');
    }
    
    /**
     *
     */
    protected function format_content($entry, array $values = null) {
        $fieldid = $this->field->id;
        $oldcontents = array();
        $contents = array();
        // old contents
        if (isset($entry->{"c{$fieldid}_content"})) {
            $oldcontents[] = isset($entry->{"c$fieldid". '_content'}) ? $entry->{"c$fieldid". '_content'} : null;
            $oldcontents[] = isset($entry->{"c$fieldid". '_content1'}) ? $entry->{"c$fieldid". '_content1'} : null;
        }
        // new contents
        $url = $alttext = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                if ($name) { // update from form
                    switch ($name) {
                        case 'url':
                            if ($value and $value != 'http://') {
                                $url = clean_param($value, PARAM_URL);
                            }
                            break;
                        case 'alt':
                            $alttext = clean_param($value, PARAM_NOTAGS);
                            break;
                    }
                } else { // update from import
                    if (strpos($value, '##') !== false) {
                        $value = explode('##', $value);
                        $url = clean_param($value[0], PARAM_URL);
                        $alttext = clean_param($value[1], PARAM_NOTAGS);
                    } else {
                        $url = clean_param($value, PARAM_URL);
                    }
                    // there should be only one from import, so break
                    break;
                }
            }
        }
        if (!is_null($url)) {
            $contents[] = $url;
            $contents[] = $alttext;
        }
        return array($contents, $oldcontents);
    }
    
    /**
     *
     */
    public function get_content_parts() {
        return array('content', 'content1');
    }

    /**
     *
     */
    public function notemptyfield($value, $name) {
        $names = explode('_',$name);
        $value = clean_param($value, PARAM_URL);
        //clean first
        if ($names[3] == '0') {
            return ($value!='http://' && !empty($value));
        }
        return false;
    }

}
