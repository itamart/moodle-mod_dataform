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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_dataform
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = array_merge(
    mod_dataform_capabilities_dataform(),
    mod_dataform_capabilities_dataform_view(),
    mod_dataform_capabilities_dataform_entry(),
    mod_dataform_capabilities_dataform_entry_early(),
    mod_dataform_capabilities_dataform_entry_late(),
    mod_dataform_capabilities_dataform_entry_own(),
    mod_dataform_capabilities_dataform_entry_group(),
    mod_dataform_capabilities_dataform_entry_any(),
    mod_dataform_capabilities_dataform_entry_anonymous(),
    mod_dataform_capabilities_dataform_preset(),
    mod_dataform_capabilities_dataform_depracated()
);

/**
 * Returns the list of dataform capabilities for an activity
 *
 * @return array
 */
function mod_dataform_capabilities_dataform() {
    return array(
        // Add instance
        'mod/dataform:addinstance' => array(
            'riskbitmask' => RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'moodle/course:manageactivities'
        ),

        // View Dataforms index (administration link and page)
        'mod/dataform:indexview' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // View profile messaging (administration link)
        'mod/dataform:messagingview' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage templates: do anything in the dataform
        'mod/dataform:managetemplates' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage views
        'mod/dataform:manageviews' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage fields
        'mod/dataform:managefields' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage filters
        'mod/dataform:managefilters' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage access
        'mod/dataform:manageaccess' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage notifications
        'mod/dataform:managenotifications' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage css
        'mod/dataform:managecss' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage js
        'mod/dataform:managejs' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Manage tools
        'mod/dataform:managetools' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities for a view
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_view() {
    return array(
        // Access view
        'mod/dataform:viewaccess' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'guest' => CAP_ALLOW,
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Access hidden views
        'mod/dataform:viewaccesshidden' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Access views before activity available from
        'mod/dataform:viewaccessearly' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Access views after activity is due
        'mod/dataform:viewaccesslate' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Apply filters to views with view-filter
        'mod/dataform:viewfilteroverride' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities for an entry
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_entry() {
    return array(
        // manage entries: view, write, delete, export etc.
        'mod/dataform:manageentries' => array(

            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities for an entry in an early dataform
 * - View
 * - Add
 * - Update
 * - Delete
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_entry_early() {
    return array(
        // View
        'mod/dataform:entryearlyview' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Add
        'mod/dataform:entryearlyadd' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
        ),

        // Update
        'mod/dataform:entryearlyupdate' => array(

            'riskbitmask' => RISK_SPAM,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Delete
        'mod/dataform:entryearlydelete' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities for an entry in a past due dataform
 * - View
 * - Add
 * - Update
 * - Delete
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_entry_late() {
    return array(
        // View
        'mod/dataform:entrylateview' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Add
        'mod/dataform:entrylateadd' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
        ),

        // Update
        'mod/dataform:entrylateupdate' => array(

            'riskbitmask' => RISK_SPAM,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Delete
        'mod/dataform:entrylatedelete' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities for an entry authored by the user
 * - View
 * - Export
 * - Add
 * - Update
 * - Delete
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_entry_own() {
    return array(
        // View
        'mod/dataform:entryownview' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Export
        'mod/dataform:entryownexport' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:exportownentry'
        ),

        // Add
        'mod/dataform:entryownadd' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:writeentry'
        ),

        // Update
        'mod/dataform:entryownupdate' => array(

            'riskbitmask' => RISK_SPAM,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Delete
        'mod/dataform:entryowndelete' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities for an entry in the group of the user
 * - View
 * - Export
 * - Add
 * - Update
 * - Delete
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_entry_group() {
    return array(
        // View
        'mod/dataform:entrygroupview' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Export
        'mod/dataform:entrygroupexport' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:exportownentry'
        ),

        // Add
        'mod/dataform:entrygroupadd' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Update
        'mod/dataform:entrygroupupdate' => array(
            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Delete
        'mod/dataform:entrygroupdelete' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities for an entry other than own, group, anonymous
 * - View
 * - Export
 * - Add
 * - Update
 * - Delete
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_entry_any() {
    return array(
        // View
        'mod/dataform:entryanyview' => array(
            'riskbitmask' => RISK_PERSONAL,

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'guest' => CAP_ALLOW,
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:viewentry'
        ),

        // Export
        'mod/dataform:entryanyexport' => array(
            'riskbitmask' => RISK_PERSONAL,

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'guest' => CAP_ALLOW,
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:exportallentries'
        ),

        // Add
        'mod/dataform:entryanyadd' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:manageentries'
        ),

        // Update
        'mod/dataform:entryanyupdate' => array(
            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:manageentries'
        ),

        // Delete
        'mod/dataform:entryanydelete' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:manageentries'
        ),
    );
}

/**
 * Returns the list of dataform capabilities for an anonymous entry
 * - View
 * - Export
 * - Add
 * - Update
 * - Delete
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_entry_anonymous() {
    return array(
        // View
        'mod/dataform:entryanonymousview' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'guest' => CAP_ALLOW,
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:viewanonymousentry'
        ),

        // Export
        'mod/dataform:entryanonymousexport' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Add
        'mod/dataform:entryanonymousadd' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Update
        'mod/dataform:entryanonymousupdate' => array(
            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // Delete
        'mod/dataform:entryanonymousdelete' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities for managing presets
 * - Manage
 * - View all
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_preset() {
    return array(
        // manage user presets
        'mod/dataform:managepresets' => array(

            'riskbitmask' => RISK_SPAM | RISK_XSS,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // view all user presets
        'mod/dataform:presetsviewall' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),
    );
}

/**
 * Returns the list of dataform capabilities which have been depracated
 *
 * @return array
 */
function mod_dataform_capabilities_dataform_depracated() {
    return array(

    // DEPRACATED

        // view entries
        'mod/dataform:viewentry' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'frontpage' => CAP_ALLOW, // needed for dataforms on the frontpage
                'guest' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // write entries
        'mod/dataform:writeentry' => array(

            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // view anonymous entries
        'mod/dataform:viewanonymousentry' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // export entries
        'mod/dataform:exportentry' => array(

            'riskbitmask' => RISK_PERSONAL,

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
            )
        ),

        // export own entries
        'mod/dataform:exportownentry' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'student' => CAP_ALLOW,
            )
        ),

        // export all entries
        'mod/dataform:exportallentries' => array(

            'riskbitmask' => RISK_PERSONAL,

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
            )
        ),

        // Approve an entry
        'mod/dataform:approve' => array(

            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // mangae comments
        'mod/dataform:managecomments' => array(

            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // comment on entries
        'mod/dataform:comment' => array(

            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // mangae ratings
        'mod/dataform:manageratings' => array(

            'riskbitmask' => RISK_SPAM,

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // rate entries
        'mod/dataform:rate' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // view entry ratings
        'mod/dataform:ratingsview' => array(

            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        // ???
        'mod/dataform:ratingsviewany' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:ratingsview'
        ),

        // ???
        'mod/dataform:ratingsviewall' => array(

            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'mod/dataform:ratingsview'
        ),
    );
}
