<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-calculated
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

require_once("$CFG->dirroot/mod/dataform/field/number/field_class.php");

class dataform_field_calculated extends dataform_field_number {

    public $type = 'calculated';

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        $patterns = parent::patterns($tags, $entry, $edit, $editable);
        
        $fieldname = $this->field->name;
        $extrapatterns = array( 'C' => "[[{$fieldname}]]",
                                'op' => "[[{$fieldname}:op]]",
                                'neg' => "[[{$fieldname}:neg]]",
                                'lb' => "[[{$fieldname}:lb]]",
                                'rb' => "[[{$fieldname}:rb]]",
                                'V' => "[[{$fieldname}:mark]]");
        
        // if no tags requested, return select menu
        if (is_null($tags)) {
            foreach ($extrapatterns as $pattern) {
                $patterns['fields']['fields'][$pattern] = $pattern;
            }

        } else {
            foreach ($tags as $tag) {
                if ($edit and $tag == "[[{$fieldname}]]") {
                    continue;
                }
                if ($t = array_search($tag, $extrapatterns)) {
                    $patterns[$tag] = array('html', $this->display_browse($entry, array('t' => $t)));
                }
            }
        }

        return $patterns;
    }

    /**
     *
     */
    public function format_content(array $values = null) {

        // $values array of fieldid => values (name => value)         
        if (!empty($values)) {

            $negation = $this->field->param1;
            $operator = $this->field->param3;
            $operand1 = $operand2 = '';
            // operand 1
            if ($operand1id = $this->field->param2) {
                if (array_key_exists($operand1id, $values)) {
                    $operand1 = reset($values[$operand1id]);
                }
                $operand1 = $operand1 ? $operand1 : 0;
                
                // only if operand1 and operator, proceed to operand2
                if ($operator and $operand2id = $this->field->param4) {
                    if (array_key_exists($operand2id, $values)) {
                        $operand2 = reset($values[$operand2id]);
                    }
                    $operand2 = $operand2 ? $operand2 : 0;
  
                    switch ($operator) {
                        case '+':
                            $value = $operand1 + $operand2;
                            break;
                        
                        case '-':
                            $value = $operand1 - $operand2;
                            break;
                        
                        case '*':
                            $value = $operand1 * $operand2;
                            break;
                        
                        case '/':
                            $value = $operand1 / $operand2;
                            break;
                        
                        case '%':
                            $value = $operand1 % $operand2;
                            break;
                    }
                } else {
                    $value = $operand1;
                }
                
                // add negation    
                if ($negation) {
                    $value = -$value;
                }
            }
            return $value;

        } else {
            return null;
        }
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {
        $entryid = $entry->id;
        $fieldid = $this->field->id;
        $fieldname = "field_{$fieldid}_$entryid";

        if (isset($entry->{"c$fieldid". '_content'})) {
            $content = $entry->{"c$fieldid". '_content'};
        } else {
            $content = null;
        }

        $mform->addElement('hidden', $fieldname, $content);
        if (is_numeric($this->field->param6)) {
            $mform->addElement('html', $this->field->param6);
        } else if (!is_null($content)) {
            $mform->addElement('html', $content);
        }

    }

    /**
     * 
     */
    public function display_browse($entry, $params = null) {
        global $OUTPUT;
        
        $str = '';
        
        switch ($params['t']) {
            case 'neg':
                if ($this->field->param1) {
                    $str = '-';
                }
                break;

            case 'op':
                if ($this->field->param3) {
                    $str = $this->field->param3;
                }
                break;
                            
            case 'lb':
                if ($this->field->param1) {
                    $str = '(';
                }
                break;
                
            case 'rb':
                if ($this->field->param1) {
                    $str = ')';
                }
                break;
                
            case 'V':
            case 'C':
                $fieldid = $this->field->id;

                if (isset($entry->{"c$fieldid". '_content'})) {
                    $content = $entry->{"c$fieldid". '_content'};
                } else {
                    $content = null;
                }
                    
                if (is_numeric($this->field->param6)) {
                    if ($params['t'] == 'C') {
                       $str = $this->field->param6;
                    } else { 
                        if (!is_null($content)) {
                            if ((float) $content == $this->field->param6) {
                                $str = $OUTPUT->pix_icon('i/tick_green_big', get_string('correct', 'dataform'));
                            } else {
                                $str = $OUTPUT->pix_icon('i/cross_red_big', get_string('incorrect', 'dataform'));
                            }
                        }
                    }                           
                } else {
                    if ($params['t'] == 'C') {
                       $str = $content;
                    }
                }
                    
                break;
                
            default:
                $str = parent::display_browse($entry, $params);
                break;
        }
        
        return $str;
    }
}
