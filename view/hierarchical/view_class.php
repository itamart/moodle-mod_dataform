<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/
 *
 * @package mod-dataform
 * @subpackage view-hierarchical
 * @author Itamar Tzadok
 * @copyright 2011 Moodle contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's standard Database activity module. To the extent that the
 * Dataform code corresponds to the Database code (1.9.11+ (20110323)),
 * certain copyrights on certain files may obtain.
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
 
require_once("$CFG->dirroot/mod/dataform/view/block/view_class.php");

/**
 * uses:
 * param1 toc view id
 * param2 repeated entry template
 * param4 toc cache
 * param5 node field id
 * param6 entries layout
 * param7 numbering auto-update
 */
class dataform_view_hierarchical extends dataform_view_block {

    protected $type = 'hierarchical';
    protected $nodefield = null;

    const LAYOUT_INDENTED = 1;
    const LAYOUT_TREEVIEW = 2;
    
    /**
     *
     */
    public function set_page($page) {
        global $PAGE;
        
        $tocview = $this->view->param1;
        $layout = $this->view->param6;
        
        if ($page == 'view' and ($tocview or $layout == self::LAYOUT_TREEVIEW)) {
            $options = new object;
            
            $module = array(
                'name' => 'M.dataform_treeview',
                'fullpath' => '/mod/dataform/view/hierarchical/hierarchical.js',
                'requires' => array('yui2-yahoo-dom-event', 'yui2-treeview'));

            $PAGE->requires->js_init_call('M.dataform_treeview.init', array($options), true, $module);
            $PAGE->requires->css('/lib/yui/2.8.2/build/treeview/assets/skins/sam/treeview.css');
        }
    }

    /**
     *
     */
    public function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;

        // update numbering
        $autonumbering = $this->view->param7;
        if ($autonumbering and $nodefield = $this->get_nodefield()) {
            $nodefield->update_numbering();
        }
        
        $elements = array();

        // sort the entries by node
        $entriesset = $this->sort_entries_by_node($entriesset);
        
        // elements of the main view
        $layout = $this->view->param6;
        if (!$layout) {
            $elements = parent::group_entries_definition($entriesset, $name);
        } else if ($layout == self::LAYOUT_INDENTED) {
            $elements = $this->group_entries_definition_indented($entriesset, $name);
        } else if ($layout == self::LAYOUT_TREEVIEW) {
            $elements = $this->group_entries_definition_treeview($entriesset, $name);
        }

        // toc
        if ($tocid = $this->view->param1) {
            $tocview =  $this->_df->get_view_from_id($tocid);
            // auto update if needed
            //if ($autoupdate) {
            //    $tocview->update_toc();
            //}
            
            // add toc element
            $toc = html_writer::tag('div',
                                    $tocview->display(true),
                                    array('class' => 'mdl-left',
                                        'style' => 'float:left;width:20%;padding:5px;margin:5px;border:2px solid #dddddd;'));    
            $mainviewopen = html_writer::start_tag('div',
                                                    array('class' => 'mdl-left',
                                                        'style' => 'float:left;width:70%;padding:5px;margin:5px;border:2px solid #dddddd;'));    
            $mainviewclose = html_writer::end_tag('div');
            $wrapperopen = html_writer::start_tag('div', array('class' => 'mdl-align'));
            $wrapperclose = html_writer::end_tag('div');


            array_unshift($elements, array('html', $wrapperopen. $toc. $mainviewopen));
            array_push($elements, array('html', $mainviewclose. $wrapperclose));
        }

        return $elements;
    }

    /**
     *
     */
    public function process_data() {

    }
    
    /**
     *
     */
    protected function group_entries_definition_treeview($entriesset, $name = '') {
        global $CFG, $OUTPUT;
        
        $entries_set = $this->get_entries_definition($entriesset, $name);

        // generate the hierarchical structure of the entries
        $opentreediv = html_writer::start_tag('div',
                                        array('id' => 'my_tree_markup',
                                            'class' => 'mdl-align'));
        $closetreediv = html_writer::end_tag('div');

        $nested = $this->generate_tree_set($entriesset, 3); // entrydepth = entriesset[3]
        $listbody = $this->build_definitions_from_tree($nested, $entries_set);

        $elements = array();
        $elements[] = array('html', html_writer::start_tag('div', array('class' => 'entriesview')));
        $elements[] = array('html', $opentreediv);
        if ($name) {
            $name = ($name == 'newentry') ? get_string('entrynew', 'dataform') : $name;
            $elements[] = array('html', $OUTPUT->heading($name, 3, 'main'));
        }
        $elements = array_merge($elements, $listbody);
        $elements[] = array('html', $closetreediv);
        $elements[] = array('html', html_writer::end_tag('div'));
        return $elements;
    }

    /**
     *
     */
    protected function group_entries_definition_indented($entriesset, $name = '') {
        global $OUTPUT;
        
        $entries_set = $this->get_entries_definition($entriesset, $name);

        // flatten the set to a list of elements
        $listbody = array();
        foreach ($entries_set as $entryid => $entry_definitions) {
            $entrydepth = (!empty($entriesset[$entryid][3]) ? $entriesset[$entryid][3] : 0) * 40;
            array_unshift($entry_definitions, array('html', html_writer::start_tag('div', array('style' => "margin-left:{$entrydepth}px"))));
            array_push($entry_definitions, array('html', html_writer::end_tag('div')));
            $listbody = array_merge($listbody, $entry_definitions);
        }

        $elements = array();
        $elements[] = array('html', html_writer::start_tag('div', array('class' => 'entriesview')));
        if ($name) {
            $name = ($name == 'newentry') ? get_string('entrynew', 'dataform') : $name;
            $elements[] = array('html', $OUTPUT->heading($name, 3, 'main'));
        }
        $elements = array_merge($elements, $listbody);
        $elements[] = array('html', html_writer::end_tag('div'));
        return $elements;
    }


    /**
     * sort the entriesset by the deginated node
     * @param array $entriesset entryid => array(entry, edit, editable)
     *
     * @return array entryid => array(entry, edit, editable) sorted
     */
    protected function sort_entries_by_node($entriesset) {   
        if ($nodefield = $this->get_nodefield()) {
            $entriesset = $nodefield->sort_set($entriesset);
        }        
        
        return $entriesset;
    }

    /**
     *
     */
    protected function build_definitions_from_tree($tree, $titles) {

        $definitions = array();
        $definitions[] = array('html', html_writer::start_tag('ul'));
        foreach ($tree as $key => $branch) {
            $definitions[] = array('html', html_writer::start_tag('li'));
            foreach ($entrydefs = $titles[$key] as $entrydef) {
                $definitions[] = $entrydef;
            }
            if (!empty($branch)) {
                $branchdefs = $this->build_definitions_from_tree($branch, $titles);
                foreach ($branchdefs as $branchdef) {
                    $definitions[] = $branchdef;
                }
            }
            $definitions[] = array('html', html_writer::end_tag('li'));
        }
        $definitions[] = array('html', html_writer::end_tag('ul'));

        return $definitions;
    }

    /**
     *
     */
    protected function generate_tree_set(&$set, $depthcol, $depth = 0) {
        $tree = array();

        foreach ($set as $entryid => $entry) {
            $entrydepth = !empty($entry[$depthcol]) ? $entry[$depthcol] : 0;
            if ($entrydepth == $depth) {
                unset($set[$entryid]);
                $tree[$entryid] = $this->generate_tree_set($set, $depthcol, $depth + 1);
            } else {
                reset($set);
                break;
            }
        }
        
        return $tree;
    }

    /**
     *
     */
    protected function patterns($tags = null, $params = null) {
        global $OUTPUT;

        $patterns = parent::patterns($tags, $params);
        
        $generalactions = array(
            '##updatetoc##',
        );
        
        // if no tags are requested, return select menu
        if (is_null($tags)) {
            foreach ($generalactions as $pattern) {
                $patterns['generalactions']['generalactions'][$pattern] = $pattern;
            }
            
        } else {      
                
            $filter = $this->_filter;
            $baseurl = htmlspecialchars_decode($this->_baseurl. '&sesskey='. sesskey());
            
            foreach ($tags as $tag) {
                switch ($tag) {
                    case '##updatetoc##':
                        if (!has_capability('mod/dataform:managetemplates', $this->_df->context)) {
                            $patterns[$tag] = '';
                        } else {
                            $patterns[$tag] =
                                html_writer::link($baseurl. '&updatetoc=1', get_string('tocupdate', 'dataformview_hierarchical'));
                        }
                        break;
                }
           }            
        }

        return $patterns;
    }

    /**
     *
     */
    protected function get_nodefield() {   
        if (!$this->nodefield and $this->view->param5) {
            $this->nodefield = $this->_df->get_field_from_id($this->view->param5);
        }
        return $this->nodefield;
    }

    /**
     *
     */
    protected function update_toc() {
        if ($nodefield = $this->get_nodefield()) {
            $tree = $nodefield->get_nodeids_nested();
            
            // replace tree keys with toc label content
            //

            $this->view->param4 = serialize($tree);
            //$updateview = new object;
            //$updateview->id = $this->view->id;
            //$updateview->param4 = $this->view->param4;
            //$this->update_view($updateview);
        }
    }

    /**
     *
     */
    protected function print_toc() {
        $tocstr = '';   
        if ($this->view->param4) {
            $toctree = unserialize($this->view->param4);
            $tocstr = $this->generate_toc_markup($toctree);          
        }
        return $tocstr;
    }

    /**
     *
     */
    protected function generate_toc_markup(array $tree) {
        $lis = array();
        foreach ($tree as $label => $branch) {
            if (!empty($branch)) {
                $lis[] = html_writer::tag('li', $label. $this->generate_toc_markup($branch));
            } else {
                $lis[] = html_writer::tag('li', $label);
            }
        }
        $ul = html_writer::tag('ul', implode('', $lis));
        return $ul;
    }

}
