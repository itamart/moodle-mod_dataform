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
 * @package mod-dataform
 * @subpackage dataformfield_coursegroup
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Category coursegroups loader
 */
M.dataformfield_coursegroup_load_course_groups = {};

M.dataformfield_coursegroup_load_course_groups.init = function(Y, options) {
    YUI().use('node-base', 'event-base', 'io-base', function(Y) {
        // get field name from options
        var coursefield = options.coursefield;
        var groupfield = options.groupfield;
        var groupidfield = options.groupfield+'id';
        var actionurl = options.acturl;

        Y.on('change', function(e) {

            // get group select
            var group = Y.Node.one('#id_' + groupfield);

            // get the courseid
            var courseid = this.get('options').item(this.get('selectedIndex')).get('value');

            // remove options (but first choose) from group select
            var optionchoose = group.get('options').item(0);
            group.setContent(optionchoose);
            group.set('selectedIndex', 0);           

            // load groups from course
            if (courseid != 0) {

                Y.io(actionurl, {
                    method: 'POST',
                    data: 'courseid='+courseid,
                    on: {
                        success: function (id, o) {
                            if (o.responseText != '') {
                                // add options
                                var groupoptions = group.get('options');
                                var respoptions = o.responseText.split(',');
		                        for (var i=0;i<respoptions.length;++i) {
		                            var arr = respoptions[i].trim().split(' ');
                                    var qid = arr.shift();
                                    var qname = arr.join(' ');
		                            group.append(Y.Node.create('<option value="'+qid+'">'+qname+'</option>'));
	                            }
                            }
                        },
                        failure: function (id, o) {
		                    // do something
                        }
                    }
                });

            }
        }, '#id_'+ coursefield);


        Y.on('change', function(e) {

            // get groupid field
            var group = Y.Node.one('#id_' + groupidfield);

            // get the selected group from group
            var gid = this.get('options').item(this.get('selectedIndex')).get('value');

            // assign to groupid
            group.set('value', gid);
        }, '#id_'+ groupfield);
    });        
};