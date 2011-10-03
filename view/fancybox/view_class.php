<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/
 *
 * @package mod-dataform
 * @subpackage view-fancybox
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

class dataform_view_fancybox extends dataform_view_block {

    const SHOW_ALL = 0;
    const SHOW_FIRST = 1;
    const SHOW_RANDOM = 2;
    const SHOW_NONE = 3;

    const IMPORT_ENTRIES_FROM_IMAGES = 1;

    protected $type = 'fancybox';
    
    /**
     *
     */
    public function set_page($page = 'view') {
        global $PAGE;
        
        if ($page == 'view') {
            //view/fancybox/js/jqeury-1.4.3.min.js
            //view/fancybox/js/jqeury.fancybox-1.3.4.pack.js
            //view/fancybox/js/jqeury.easing-1.4.pack.js
            //view/fancybox/js/jqeury.mousewheel-3.0.4.pack.js
            $PAGE->requires->js('/mod/dataform/view/fancybox/js/dataform_fancybox.js');
            $fancyboxcss = new moodle_url('/mod/dataform/view/fancybox/js/jquery.fancybox-1.3.4.css',
                                            array('media' => "screen"));
            $PAGE->requires->css($fancyboxcss);
                                    

        }
    }

    /**
     *
     */
    public function generate_default_view() {
        // get all the fields for that database
        if (!$fields = $this->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        $table = new html_table();
        $table->attributes['align'] = 'center';
        $table->attributes['cellpadding'] = '2';

        // first row: menus
        $row1 = new html_table_row();
        $viewsmenu = new html_table_cell('##viewsmenu##');
        $seperator = new html_table_cell('     ');
        $filtersmenu = new html_table_cell('##filtersmenu##');
        $quicksearch = new html_table_cell('##quicksearch##');
        $quickperpage = new html_table_cell('##quickperpage##');
        $row1->cells = array($viewsmenu, $seperator, $filtersmenu, $quicksearch, $quickperpage);
        foreach ($row1->cells as $cell) {
            $cell->style = 'border:0 none;';
        }

        // second row: add entries 
        $row2 = new html_table_row();
        $addentries = new html_table_cell('##addnewentry##'.html_writer::empty_tag('br').'##addentriesfromimages##');
        $addentries->colspan = 5;
        $row2->cells = array($addentries);
        foreach ($row2->cells as $cell) {
            $cell->style = 'border:0 none;';
        }
        
        // third row: paging bar
        $row3 = new html_table_row();
        $pagingbar = new html_table_cell('##pagingbar##');
        $pagingbar->colspan = 5;
        $row3->cells = array($pagingbar);
        foreach ($row3->cells as $cell) {
            $cell->style = 'border:0 none;';
        }
        
        $table->data = array($row1, $row2, $row3);
        
        $sectiondefault = html_writer::table($table);
        
        // set views and filters menus and quick search
        $this->view->esection = html_writer::tag('div', $sectiondefault, array('class' => 'mdl-align'));

        $entrydefault = html_writer::tag('div', '[[Image:tn]]').
                        html_writer::tag('div', '&nbsp;[[Image:alt]]').
                        html_writer::empty_tag('br').
                        html_writer::tag('div', '##edit## ##delete## [[Image:url]]');
                        
        $this->view->eparam2 = html_writer::tag('div', $entrydefault, array('class' => 'mdl-align'));
        $this->view->param1 = 3;
    }

    /**
     *
     */
    public function group_entries_definition($entriesset, $name = '') {
        $elements = array();

        $hideitems = $this->view->param3;
        if (empty($hideitems)) {
            $elements = $this->group_entries_definition_tabled($entriesset, $name);
        } else {
            $showitem = 0;
            if (!empty($entriesset)) {
                if ($hideitems == self::SHOW_FIRST) {
                    $showitem = key($entriesset);
                } else if ($hideitems == self::SHOW_RANDOM) {
                    $showitem = array_rand($entriesset);
                }
            }
            $elements = $this->group_entries_definition_hidden($entriesset, $name, $showitem);
        }
        
        return $elements;
    }

    /**
     *
     */
    public function entry_definition($fielddefinitions) {
        global $OUTPUT;
        
        $elements = array();
        
        // split the entry template to tags and html
        $tags = array_keys($fielddefinitions);
        $parts = $this->split_tags($tags, $this->view->eparam2);
                        
        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($part == '[[Image:url]]') {
                    list(,$imageurl) = $fielddefinitions['[[Image:url]]'];
                    list(,$imagecaption) = $fielddefinitions['[[Image:alt]]'];
                    $imagerel = html_writer::link($imageurl,
                                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/search'),
                                                                        'class' => "icon",
                                                                        'alt' => '',
                                                                        'title' => '')),
                                    array('class' => "grouped_elements", 
                                            'rel' => "group2", 
                                            'title' => $imagecaption));

                    $elements[] = array('html', $imagerel);
            
                } else if ($def = $fielddefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = array('html', $part);
            }
        }

        return $elements;      
    }

    /**
     *
     */
    protected function patterns($tags = null, $params = null) {
        global $OUTPUT;

        $patterns = parent::patterns($tags, $params);
        
        $generalactions = array('##addentriesfromimages##');
        
        // if no tags are requested, return select menu
        if (is_null($tags)) {
            $patterns['generalactions']['generalactions']['##addentriesfromimages##'] = '##addentriesfromimages##';

        } else {      
                
            $baseurl = htmlspecialchars_decode($this->_baseurl. '&sesskey='. sesskey());
            
            foreach ($tags as $tag) {
                if ($tag == '##addentriesfromimages##') {
                    if (isset($params['hidenewentry']) or $this->_df->user_at_max_entries(true)) {            // TODO: move to a view attribute so as to call the function only once
                        $patterns[$tag] = '';
                    } else {
                        if (!optional_param('importentries', 0, PARAM_BOOL)) {
                            $patterns[$tag] = 
                                html_writer::link($baseurl. '&importentries=1', get_string('entriesimport', 'dataform'));
                        } else {
                            $patterns[$tag] = $this->print_entries_import();
                        }
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
    public function print_entries_import() {
        global $PAGE, $OUTPUT;
        
        $itemid = file_get_unused_draft_itemid();

        $options = new object;
        $options->maxbytes  = $this->_df->course->maxbytes;
        $options->maxfiles  = 1;
        $options->maxbytes  = $this->_df->course->maxbytes;
        $options->itemid    = $itemid;
        $options->accepted_types = array('zip');
        $options->context = $PAGE->context;

        $fp = new file_picker($options);

        $module = array('name'=>'dataform_filepicker', 'fullpath'=>'/mod/dataform/dataform.js', 'requires'=>array('core_filepicker'));
        $PAGE->requires->js_init_call('M.dataform_filepicker.init', array($fp->options), true, $module);

        $filepicker = $OUTPUT->render($fp);
        $button = html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('entriesimport', 'dataform')));
        $br = html_writer::empty_tag('br');
        
        $baseurl = '/mod/dataform/view.php';
        $baseurlparams = array('d' => $this->_df->id(),
                                'sesskey' => sesskey(),
                                'view' => $this->id(),
                                'filter' => $this->_filter->id,
                                'import' => self::IMPORT_ENTRIES_FROM_IMAGES,
                                'draftitemid' => $itemid);

        $formparams = array();
        foreach ($baseurlparams as $var => $val) {
            $formparams[] = html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $var, 'value' => $val));
        }

        $attributes = array('method' => 'post', 'action' => new moodle_url($baseurl));

        $fpform = html_writer::tag('form', implode('', $formparams). "$filepicker $br $button", $attributes);

        // and finally one more wrapper with class
        $str = html_writer::tag('div', $fpform, array('class' => 'singleselect', 'style' => 'max-width:350px;float:left;'));
        $sider = html_writer::tag('div', '&nbsp;', array('style' => 'width:30%;float:left;'));

        return $sider.$str.$sider;
    }

    /**
     *
     */
    public function get_import_data($importtype) {
        global $USER;
    
        // get the uploaded images file
        $imagesfile = null;
        $draftitemid = optional_param('draftitemid', 0, PARAM_INT);
        $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
        if (count($files) > 1) {
            foreach ($files as $file) {
                if (!$file->is_directory()) {
                    $imagesfile = $file;
                    break;
                }
            }
        }
                             
        // extract images to the draft area
        $zipper = get_file_packer('application/zip');
        if ($imagesfile) {
            $imagesfile->extract_to_storage($zipper, $usercontext->id, 'user', 'draft', $draftitemid, '/');
            $imagesfile->delete();
        }
        
        // get the view's Image field id
        if (!$imagefield = $this->_df->get_field_by_name('Image')) {
            return null;
        }

        $data = new object;
        $data->eids = array();
        
        // move image to its own area and add info to data
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
        if (count($files) > 1) {
            $rec = new object;
            $rec->contextid = $usercontext->id;
            $rec->component = 'user';
            $rec->filearea = 'draft';

            $i = 0;
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    // $get unused draft area
                    $itemid = file_get_unused_draft_itemid();
                    // move image to the new draft area 
                    $rec->itemid = $itemid;
                    $fs->create_file_from_storedfile($rec, $file);
                    // add info to data
                    $i--;
                    $fieldname = "field_{$imagefield->id()}_$i";
                    $data->{"{$fieldname}_filemanager"} = $itemid;
                    $data->{"{$fieldname}_alttext"} = $file->get_filename();
                    $data->eids[] = $i;
                }
            }
            $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftitemid);
        }
        
        return $data;        
    }

    /**
     *
     */
    protected function group_entries_definition_hidden($entriesset, $name = '', $showitem = 0) {
        global $OUTPUT;
        
        $entries_set = $this->get_entries_definition($entriesset, $name);

        // flatten the set to a list of elements
        $listbody = array();
        foreach ($entries_set as $entryid => $entry_definitions) {
            if ($entryid != $showitem) {
                array_unshift($entry_definitions, array('html', html_writer::start_tag('div', array('style' => "display:none;"))));
                array_push($entry_definitions, array('html', html_writer::end_tag('div')));
            }
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
     *
     */
    protected function group_entries_definition_tabled($entriesset, $name = '') {
        global $OUTPUT;
        
        $entries_set = $this->get_entries_definition($entriesset, $name);

        $table = $this->make_table(count($entries_set));
                
        $grouphtml = '<div class="entriesview">'. $OUTPUT->heading($name, 3, 'main'). html_writer::table($table). '</div>';
        // now split $tablehtml to cells by ##begintablecell##
        $cells = explode('##begintablecell##', $grouphtml);

        $elements = array();
        // the first part is everything before first cell
        $elements[] = array('html', array_shift($cells));

        // the rest (excluding the last) are cells so in each place an item

        // add entry definitions and then a cell
        foreach ($entries_set as $entry_definitions) {

            $elements = array_merge($elements, $entry_definitions);
            $elements[] = array('html', array_shift($cells));

        }

        // add remaining cells
        $elements[] = array('html', implode('', $cells));
 
        return $elements;
    }

    /**
     *
     */
    protected function make_table($entriescount) {
        // calc table rows and cols
        $cols = !empty($this->view->param1) ? $this->view->param1 : 3;
        if ($entriescount < $cols) {
            $cols = $entriescount;
            $rows = 1;
        } else {
            $rows = ceil($entriescount/$cols);
        }
                
        // make a table
        $table = new html_table();
        $table->align = array_fill(0, $cols, 'center');
        //$table->wrap = array_fill(0, $cols, 'false');
        $table->attributes['align'] = 'center';
        for ($r = 0; $r < $rows; $r++) {
            $row = new html_table_row();
            for ($c = 0; $c < $cols; $c++) {
                $cell = new html_table_cell();
                $cell->text = '##begintablecell##';
                $cell->style = 'vertical-align:bottom';
                $row->cells[] = $cell;
            }
            $table->data[] =  $row;
        }
        
        return $table;
    }

}
