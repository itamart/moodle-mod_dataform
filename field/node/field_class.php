<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-node
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on Database module may obtain.
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

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field_node extends dataform_field_base {

    public $type = 'node';

    /**
     *
     */
    public function get_depth($entry) {
        $fieldid = $this->field->id;
        $depth = !empty($entry->{"c$fieldid". '_content2'}) ? $entry->{"c$fieldid". '_content2'} : 0;

        return $depth;
    }                    

    /**
     *
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        $fieldid = $this->field->id;
        
        list($content, $content1, $content2) = $this->format_content($values);
        list($contentid, $oldcontent, $oldcontent1, $oldcontent2, ) = array_values((array) $this->get_entry_content($entry));
        
        $rec = new object();
        $rec->fieldid = $fieldid;
        $rec->entryid = $entry->id;
        $rec->content = $content;
        $rec->content1 = $content1;
        $rec->content2 = $content2;

        if (!empty($contentid)) {
            if ($content != $oldcontent or $content1 != $oldcontent1 or $content2 != $oldcontent2) {
                $rec->id = $contentid; // MUST_EXIST              
                return $DB->update_record('dataform_contents', $rec);
            }
        } else {
            // new child or sibling
            $contentid = $DB->insert_record('dataform_contents', $rec);
            // adjust siblings
            $params['entryid'] = $entry->id;
            $params['fieldid'] = $fieldid;
            $params['content'] = $content;
            $params['content1'] = $content1;
            $sqlcomparecontent = $DB->sql_compare_text('content');
            $sqlcomparecontent1 = $DB->sql_compare_text('content1');
            if ($adjust = $DB->get_record_select('dataform_contents',
                                                    "fieldid = :fieldid
                                                     AND entryid != :entryid
                                                     AND $sqlcomparecontent = :content 
                                                     AND $sqlcomparecontent1 = :content1", 
                                                    $params)) {
                $adjust->content1 = $entry->id;
                $DB->update_record('dataform_contents', $adjust);
            }
            return $contentid;
        }
        return true;
    }

    /**
     *
     */
    public function format_content(array $values = null) {
        $parent = $sibling = $depth = 0;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                if ($name) { // update from form
                    $names = explode('_', $name);
                    if (!empty($names[3])) {
                        ${$names[3]} = $value;
                    }
                }
            }
        }
        return array($parent, $sibling, $depth);
    }
    
    /**
     *
     */
    public function get_select_sql() {
        $id = " c{$this->field->id}.id AS c{$this->field->id}_id ";
        $content = $this->get_sql_compare_text(). " AS c{$this->field->id}_content";
        $content1 = " c{$this->field->id}.content1 AS c{$this->field->id}_content1";
        $content2 = " c{$this->field->id}.content2 AS c{$this->field->id}_content2";
        $content3 = " c{$this->field->id}.content3 AS c{$this->field->id}_content3";
        return " $id , $content , $content1 , $content2 , $content3 ";
    }

    /**
     *
     */
    public function get_sql_compare_text() {
        global $DB;

        return $DB->sql_compare_text("c{$this->field->id}.content");
    }

    /**
     *
     */
    public function sort_set($entriesset) {
        global $CFG, $OUTPUT;
        
        // generate content set (entryid => (contentid, parent, sibling, depth)
        $contentset = array();
        foreach ($entriesset as $entryid => $entry) {
            list($entry, ,) = $entry;
            $contentset[$entryid] = $this->get_entry_content($entry);
        }

        // sort contentset by parent and then sibling
        uasort($contentset, function($a, $b) {
                                if ($a->content == $b->content) {
                                    if ($a->content1 == $b->content1) {
                                        return 0;
                                    }
                                    return ($a->content1 < $b->content1) ? -1 : 1;
                                }
                                return ($a->content < $b->content) ? -1 : 1;
                            });
                             
        // generate a sorted list of entry ids
        $sortedset = $this->build_sorted_list($contentset);

        // rebuild the entriesset and add entry depths
        foreach ($sortedset as $entryid => $depth) {
            if ($entryarr = $entriesset[$entryid]) {
                array_push($entryarr, $depth);
                $sortedset[$entryid] = $entryarr;
            }
        }
        
        return $sortedset;
    }


    /**
     *
     */
    public function update_numbering() {
        global $DB;
        // get all field contents ordered by ->content and ->content1
        $contents = $DB->get_records('dataform_contents',
                                    array('fieldid' => $this->field->id),
                                    'content ASC, content1 ASC',
                                    'entryid, id, content, content1, content2');
        
        // make an associative array entryid => array(contentid, content, content1, content2)
        $nodes = array();
        foreach ($contents as $entryid => $content) {
            unset($content->entryid);
            $nodes[$entryid] = $content;
        }

        // sort list
        $nodes = $this->build_sorted_list($nodes);
        
        // generate number for each node        
        $ordinals = array();
        $numbering = array();
        
        foreach ($nodes as $entryid => $depth) {
            if (!isset($numbering[$depth])) {
                $numbering[$depth] = array();
            }
            $numbering[$depth][] = '';
            $deepest = count($numbering) - 1;
            if ($depth < $deepest) {
                for ($i = $depth + 1; $i <= $deepest; $i++) {
                    unset($numbering[$i]);
                }
            }

            $seq = array();
            foreach ($numbering as $number) {
                $seq[] = count($number);
            }
            
            $contentid = $contents[$entryid]->id;
            $ordinals[$contentid] = implode('.', $seq);
        }
        
        // update node numbers in DB
        $ids = implode(',', array_keys($ordinals));
        $sql = "UPDATE {dataform_contents} SET content3 = CASE id ";
        foreach ($ordinals as  $id => $ordinal) {
            $sql .= " WHEN $id THEN '$ordinal' ";
        }
        $sql .= " END WHERE id IN ($ids) ";
        $DB->execute($sql);        
    }

    /**
     *
     */
    public function get_nodeids_nested($depth = 0, $forceget = false) {
        global $DB;
        
        // get (once) all field contents ordered by ->content and ->content1
        static $entryiddepths = null;                
        if (is_null($entryiddepths) or $forceget) {
            $contents = $DB->get_records('dataform_contents',
                                        array('fieldid' => $this->field->id),
                                        'content ASC, content1 ASC',
                                        'entryid, content, content1, content2, content3');
            // generate a sorted list of entry ids
            $entryiddepths = $this->build_sorted_list($contents);
        }
        
        $nested = array();

        if ($entryiddepths) {
            foreach ($entryiddepths as $entryid => $entrydepth) {
                if ($entrydepth == $depth) {
                    unset($entryiddepths[$entryid]);
                    $nested[$entryid] = $this->get_nodeids_nested($depth + 1);
                } else {
                    reset($entryiddepths);
                    break;
                }
            }
        }
        
        return $nested;
    }

    /**
     *
     */
    public function get_entry_content($entry) {
        $fieldid = $this->field->id;
        
        $node = new object;
        $node->id = isset($entry->{"c$fieldid". '_id'}) ? $entry->{"c$fieldid". '_id'} : null;
        $node->content = !empty($entry->{"c$fieldid". '_content'}) ? $entry->{"c$fieldid". '_content'} : 0;
        $node->content1 = !empty($entry->{"c$fieldid". '_content1'}) ? $entry->{"c$fieldid". '_content1'} : 0;
        $node->content2 = !empty($entry->{"c$fieldid". '_content2'}) ? $entry->{"c$fieldid". '_content2'} : 0;
        $node->content3 = !empty($entry->{"c$fieldid". '_content3'}) ? $entry->{"c$fieldid". '_content3'} : '';

        return $node; 
    }                    

    /**
     *
     */
    protected function build_sorted_list(&$set, $parent = 0) {
        $tree = array();

        // slice the child nodes of parent from set
        $childnodes = array();
        foreach ($set as $key => $node) {
            if ($node->content == $parent) {
                $childnodes[$key] = $node;
                unset($set[$key]);
            }
        }
        
        if ($childnodes) {
            // sort the child nodes by sibling
            if (count($childnodes) > 2) {               
                $siblingid = 0; // first sibling should have sibling id 0
                while ($childnodes) {
                    $before = count($childnodes);
                    foreach ($childnodes as $entryid => $node) {
                        if ($node->content1 == $siblingid) {
                            $tmpnodes[$entryid] = $node;
                            $siblingid = $entryid;
                            unset($childnodes[$entryid]);
                            break;
                        }
                    }
                    if (count($childnodes) == $before) {
                        break;
                    }
                }
                $childnodes = $tmpnodes;
            }    
        
            foreach ($childnodes as $entryid => $node) {
                // add to tree with entry's depth
                $tree[$entryid] = $node->content2;
                $tree = $tree + $this->build_sorted_list($set, $entryid);
            }
        }
        
        return $tree;
    }                    

}

