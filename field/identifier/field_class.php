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
 * @subpackage identifier
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield_identifier extends dataformfield_base {

    public $type = 'identifier';

    public static function get_salt_options() {
        global $CFG;
        
        $options = array(
            '' => get_string('none'),
            'random' => get_string('random', 'dataform'),
        );
        if (!empty($CFG->passwordsaltmain)) {
            $options[] = get_string('system', 'dataformfield_identifier');
        }
        return $options;
    }

    /**
     *
     */
    protected function format_content($entry, array $values = null) {
        $fieldid = $this->field->id;
        $oldcontents = array();
        $contents = array();
        // old content (should not exist if we get here, as update should be triggered only when no content)
        if (isset($entry->{"c{$fieldid}_content"})) {
            $oldcontent = $entry->{"c{$fieldid}_content"};
        } else {
            $oldcontent = null;
        }
        // Just to make sure that we come from the form where it is requested (a value of 1)
        if (!empty($values)) {
            $content = $this->generate_identifier_key($entry);
        } else {
            $content = null;
        }
        return array(array($content), array($oldcontent));
    }

    /**
     *
     */
    protected function generate_identifier_key($entry) {
        global $CFG, $USER;
        
        $identifierkey = $this->get_hash_string($entry);
        $uniqueness = !empty($this->field->param4) ? $this->field->param4 : false;       
        if ($uniqueness) {
            // We check against stored idenitifiers in this field
            // To prevent this from going forever under certain configurations
            // after 10 times force random salt we should allow it to end at some point
            $count = 0;
            while (!$this->is_unique_key($identifierkey)) {
                $count++;
                $forcerandomsalt = ($count > 10 ? true : false);
                $identifierkey = $this->get_hash_string($entry, $forcerandomsalt);
            }
        }

        return $identifierkey;
    }

    /**
     *
     */
    protected function get_hash_string($entry, $forcerandomsalt = false) {
        global $CFG, $USER;
        
        if ($forcerandomsalt) {
            $salt = 'random';
        } else {
            $salt = !empty($this->field->param1) ? $this->field->param1 : '';
        }
        $fieldsaltsize = !empty($this->field->param2) ? $this->field->param2 : 10;
        // Entry identifiers
        $entryid = $entry->id;
        $timeadded = (!empty($entry->timecreated) ? $entry->timecreated : time());
        $userid = (!empty($entry->userid) ? $entry->userid : $USER->id);

        // Collate elements for hashing
        $elements = array();
        $elements[] = $entryid;

        // Salt
        switch ($salt) {
            case '':
                $elements[] = $timeadded;
                $elements[] = $userid;
                break;
            case 'system':
                if (!empty($CFG->passwordsaltmain)) {
                    $elements[] = $CFG->passwordsaltmain;
                } else {
                    $elements[] = $timeadded;
                    $elements[] = $userid;
                }
                break;
            case 'random':
                $elements[] = complex_random_string($fieldsaltsize);
                break;
        }

        // Generate and return the hash
        return md5(implode('_', $elements));
    }

    /**
     *
     */
    protected function is_unique_key($key) {
        global $DB;

        return $DB->record_exists('dataform_contents', array('fieldid' => $this->fieldid, 'content' => $key));
    }
}

