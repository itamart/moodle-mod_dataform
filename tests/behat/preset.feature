@mod @mod_dataform @dataformpreset
Feature: Manage Dataform presets

    @javascript
    Scenario: Add presets
        # N steps

        Given a fresh site with dataform "Preset Dataform"
            | type  | dataform  | name        |
            | text  | dataform1 | Field Text  |

        And the following dataform "views" exist:
            | type      | dataform  | name    | default |
            | aligned   | dataform1 | View 01 | 1		|

        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  | Field Text                |
            | dataform1 | student1      |       |               |               | 1 Entry by Student 01     |
            | dataform1 | student2      |       |               |               | 2 Entry by Student 02     |
            | dataform1 | student3      |       |               |               | 3 Entry by Student 03     |
        
		Then I log in as "admin"
        And I follow "Course 1"
        And I follow "Preset Dataform"
        And I follow "Manage"

        # Presets
        Then I follow "Presets"
		And I expand all fieldsets
		And I set the field "id_preset_data" to "with user data"
		And I press "id_add"
		And I should see "Preset_Dataform-dataform-preset" in the "table.coursepresets" "css_element"
		And I should see "-with-user-data" in the "table.coursepresets" "css_element"
		And I should not see "Preset_Dataform-dataform-preset" in the "table.sitepresets" "css_element"
		
		Then I click on "img[title=Share]" "css_element" in the "Preset_Dataform-dataform-preset" "table_row"
		And I should see "Preset_Dataform-dataform-preset" in the "table.sitepresets" "css_element"
		And I should see "-with-user-data" in the "table.sitepresets" "css_element"
		
        # Clean up
        And I delete this dataform
