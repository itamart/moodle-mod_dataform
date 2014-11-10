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
 * The dataformfield radiobutton default content behat test.
 *
 * @package    dataformfield_radiobutton
 * @copyright  2014 Itamar Tzadok <itamar@substantialmethods.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace dataformfield_radiobutton\test\behat;

defined('MOODLE_INTERNAL') || die();

use Behat\Behat\Context\Step\Given as Given;

class defaultcontent {

    /**
     * Returns a list of steps for testing the field's default content.
     * The list of steps would typically have 6 sections:
     * 1. Add the field with default content.
     * 2. Adjust the default view to include the designated field pattern.
     *    This may require only resetting the view where the designated pattern
     *    is only the main field pattern. In other cases we may need to add patterns
     *    to the view.
     * 3. Add an entry with clearing its content.
     * 4. Add an entry without changing its content.
     * 5. Add an entry with changing its content.
     * 6. Change default setting in the field.
     * 7. Add an entry without changing its content.
     * 8. Remove default content setting in field.
     * 9. Open a new entry form.
     *
     * @param array $options
     * @return array
     */
    public static function steps($options = null) {
        $steps = array();

        if (empty($options['dataformname'])) {
            return $steps;
        }

        if (empty($options['viewname'])) {
            return $steps;
        }

        $dataformname = $options['dataformname'];
        $viewname = $options['viewname'];
        $fieldname = !empty($options['fieldname']) ? $options['fieldname'] : 'radiobuttonfield';
        $fieldid = $options['fieldid'];

        $radiobuttonoptions = "The\nBig\nBang\nTheory";
        $defaultcontent = "Big";
        $newdefaultcontent = "Bang";
        $somecontent = "Theory";
        $somecontentkey = 4;

        // 1. Add a field with default content.
        $steps[] = new Given('I go to manage dataform "fields"');
        $steps[] = new Given('I set the field "Add a field" to "radiobutton"');
        $steps[] = new Given('I expand all fieldsets');
        $steps[] = new Given('I set the field "Name" to "'. $fieldname. '"');
        $steps[] = new Given('I set the field "Options" to "'. $radiobuttonoptions. '"');
        $steps[] = new Given('I press "Save and continue"');
        $steps[] = new Given('I set the field "Default value" to "'. $defaultcontent. '"');
        $steps[] = new Given('I press "Save changes"');

        // 2. Adjust the default view.
        $steps[] = new Given('I go to manage dataform "views"');
        $steps[] = new Given('I click on "Reset" "link" in the "'. $viewname. '" "table_row"');

        $steps[] = new Given('I follow "Browse"');

        // 3. Add an entry with clearing its content.
        // Cannot clear radio button content

        // 4. Add an entry without changing its content.
        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I press "Save"');

        // Outcome: An entry added with the changed content.
        $steps[] = new Given('I see "'. $defaultcontent. '"');

        // 5. Add an entry with changing its content.
        $steps[] = new Given('I follow "Add a new entry"');
        $elementname = 'id_field_'. $fieldid. '_-1_selected_'. $somecontentkey;
        $steps[] = new Given('I set the field "'. $elementname. '" to "'. $somecontent. '"');
        $steps[] = new Given('I press "Save"');

        // Outcome: An entry added with the changed content.
        $steps[] = new Given('I see "'. $somecontent. '"');

        // 6. Change default content setting in field.
        $steps[] = new Given('I go to manage dataform "fields"');
        $steps[] = new Given('I follow "Edit '. $fieldname. '"');
        $steps[] = new Given('I expand all fieldsets');
        $steps[] = new Given('I set the field "Default value" to "'. $newdefaultcontent. '"');
        $steps[] = new Given('I press "Save changes"');

        // 7. Add an entry without changing its content.
        $steps[] = new Given('I follow "Browse"');
        $steps[] = new Given('I follow "Add a new entry"');
        $steps[] = new Given('I press "Save"');

        // Outcome: An entry added with the changed content.
        $steps[] = new Given('I see "'. $newdefaultcontent. '"');

        // 8. Remove default content setting in field.
        $steps[] = new Given('I go to manage dataform "fields"');
        $steps[] = new Given('I follow "Edit '. $fieldname. '"');
        $steps[] = new Given('I expand all fieldsets');
        $steps[] = new Given('I set the field "Default value" to ""');
        $steps[] = new Given('I press "Save changes"');

        // 9. Open a new entry form.
        $steps[] = new Given('I follow "Browse"');
        $steps[] = new Given('I follow "Add a new entry"');

        // Outcome: No default content.
        $steps[] = new Given('the field "id_field_'. $fieldid. '_-1_selected_1" matches value ""');
        $steps[] = new Given('the field "id_field_'. $fieldid. '_-1_selected_2" matches value ""');
        $steps[] = new Given('the field "id_field_'. $fieldid. '_-1_selected_3" matches value ""');
        $steps[] = new Given('the field "id_field_'. $fieldid. '_-1_selected_4" matches value ""');

        $steps[] = new Given('I press "Cancel"');

        return $steps;
    }

}
