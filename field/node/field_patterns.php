<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @package field-node
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
class mod_dataform_field_node_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        global $OUTPUT;

        $field = $this->_field;
        $fieldname = $field->name();

        $replacements = array();
        // edit only main tag
        if ($edit) {
            foreach ($tags as $tag) {
                if ($tag != "[[{$fieldname}]]") {
                    $replacements["[[$fieldname]]"] = array('', array(array($this,'display_edit'), array($entry)));
                }
            }

        } else {
            // don't display extra tags in new entries 
            if ($entry->id < 0) {
                foreach ($tags as $tag) {
                    if ($tag != "[[{$fieldname}]]") {
                        $replacements[$tag] = '';
                    }
                }
            } else {
                
                $fieldid = $field->id();
                $entryid = $entry->id;
                list(, $parentid, $siblingid, $depth, $numbering) = array_values((array) $field->get_entry_content($entry));
                $baseurl = htmlspecialchars_decode($entry->baseurl. '&sesskey='. sesskey());
                foreach ($tags as $tag) {
                    // no edit mode for this field so just return html
                    switch ($tag) {
                        case "[[{$fieldname}]]":
                            $replacements[$tag] = '';
                            break;
                        case "[[{$fieldname}:reply]]":
                            $url = new moodle_url($entry->baseurl, array('new' => 1,
                                                                        'parent' => $entryid,
                                                                        'depth' => $depth + 1,
                                                                        'sesskey'=> sesskey()));
                            $iconsrc = $OUTPUT->pix_url('t/addfile');
                            $iconalt = get_string('reply');
                            $icon = html_writer::empty_tag('img', array('src' => $iconsrc,
                                                                        'class' => "iconsmall",
                                                                        'alt' => $iconalt,
                                                                        'title' => $iconalt));
                            $replacements[$tag] = array('html', html_writer::link($url,$icon));
                            break;
                            
                        case "[[{$fieldname}:newchild]]":
                            $url = new moodle_url($entry->baseurl, array('new' => 1,
                                                                        'parent'=> $entryid,
                                                                        'depth' => $depth + 1,
                                                                        'sesskey'=>  sesskey()));
                            $iconsrc = $OUTPUT->pix_url('t/addfile');
                            $iconalt = get_string('newchild', 'dataformfield_node');
                            $icon = html_writer::empty_tag('img', array('src' => $iconsrc,
                                                                        'class' => "iconsmall",
                                                                        'alt' => $iconalt,
                                                                        'title' => $iconalt));
                            $replacements[$tag] = array('html', html_writer::link($url,$icon));
                            break;
                            
                        case "[[{$fieldname}:newsibling]]":
                            $url = new moodle_url($entry->baseurl, array('new' =>  1,
                                                                        'parent' => $parentid,
                                                                        'sibling' => $entryid,
                                                                        'depth' => $depth,
                                                                        'sesskey'=>sesskey()));
                            $iconsrc = $OUTPUT->pix_url('t/adddir');
                            $iconalt = get_string('newsibling', 'dataformfield_node');
                            $icon = html_writer::empty_tag('img', array('src' => $iconsrc,
                                                                        'class' => "iconsmall",
                                                                        'alt' => $iconalt,
                                                                        'title' => $iconalt));
                            $replacements[$tag] = array('html', html_writer::link($url,$icon));
                            break;
                            
                        case "[[{$fieldname}:appendchildren]]":
                            $baseurl = $baseurl. '&node='. $fieldid. '&parent='. $entryid. '&depth='. $depth + 1;
                            $onclick = 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44;\'append\')';
                            $replacements[$tag] = array('html',
                                ''
    //                                html_writer::empty_tag('input',
    //                                                        array('type' => 'button',
    //                                                                'name' => 'appendchildren',
    //                                                                'value' => get_string('appendchildren', 'dataformfield_node'),
    //                                                                'onclick' => $onclick))
                            );
                            break;
                            
                        case "[[{$fieldname}:appendsiblings]]":
                            $baseurl = $baseurl. '&node='. $fieldid. '&parent='. $parentid. '&sibling='. $entryid. '&depth='. $depth;
                            $onclick = 'bulk_action(\'entry\'&#44; \''. $baseurl. '\'&#44;\'append\')';
                            $replacements[$tag] = array('html',
                                ''
    //                                html_writer::empty_tag('input',
    //                                                        array('type' => 'button',
    //                                                                'name' => 'appendsiblings',
    //                                                                'value' => get_string('appendsiblings', 'dataformfield_node'),
    //                                                                'onclick' => $onclick))
                            );
                            break;
                            
                        case "[[{$fieldname}:indent]]":
                            $replacements[$tag] = array('html', ($depth * 50));
                            break;
                            
                        case "[[{$fieldname}:ho]]":
                            $level = $depth + 1;
                            $replacements[$tag] = array('html', html_writer::start_tag("h{$level}"));
                            break;
                            
                        case "[[{$fieldname}:hc]]":
                            $level = $depth + 1;
                            $replacements[$tag] = array('html', html_writer::end_tag("h{$level}"));
                            break;
                            
                        case "[[{$fieldname}:1]]":
                        case "[[{$fieldname}:A]]":                                                
                            $replacements[$tag] = array('html', $numbering);
                            break;
                            
                    }
                }
            }
        }

        return $replacements;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {

        $entryid = $entry->id;
        $fieldid = $this->_field->id();
        $fieldname = "field_{$fieldid}_{$entryid}";

        if ($entryid > 0){
            $content = $entry->{"c{$fieldid}_content"};
            $content1 = $entry->{"c{$fieldid}_content1"};
            $content2 = $entry->{"c{$fieldid}_content2"};
        } else {
            $content = optional_param('parent', 0, PARAM_INT);
            $content1 = optional_param('sibling', 0, PARAM_INT);
            $content2 = optional_param('depth', 0, PARAM_INT);
        }
        $mform->addElement('hidden', "{$fieldname}_parent", $content);
        $mform->addElement('hidden', "{$fieldname}_sibling", $content1);
        $mform->addElement('hidden', "{$fieldname}_depth", $content2);

    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = array();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname}:reply]]"] = array(true);
        $patterns["[[$fieldname}:newchild]]"] = array(true);
        $patterns["[[$fieldname}:newsibling]]"] = array(true);
        $patterns["[[$fieldname}:appendchildren]]"] = array(true);
        $patterns["[[$fieldname}:appendsiblings]]"] = array(true);
        $patterns["[[$fieldname}:indent]]"] = array(true);
        $patterns["[[$fieldname}:ho]]"] = array(false);
        $patterns["[[$fieldname}:hc]]"] = array(false);
        $patterns["[[$fieldname}:1]]"] = array(false);
        $patterns["[[$fieldname}:A]]"] = array(false);

        return $patterns; 
    }
}
