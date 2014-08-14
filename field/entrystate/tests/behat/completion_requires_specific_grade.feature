@mod @dataform_no_mod @dataformfield @dataformfield_entrystate @dataformfield_entrystate_completion
Feature: Completion

    @javascript
    Scenario: Completion requires specific grade
        Given a fresh site with dataform "Dataform completion requires specific grade"

        # Site completion enabling
        Then I log in as "admin"
        And I set the following administration settings values:
            | Enable completion tracking | 1 |
            | Enable conditional access | 1 |
        And I log out

        # Course completion enabling
        Then I log in as "teacher1"
        And I follow "Course 1"
        And I turn editing mode on
        And I click on "Edit settings" "link" in the "Administration" "block"
        And I set the field "Enable completion tracking" to "Yes"
        And I press "Save changes"

        # Dataform grade
        Then I follow "Dataform completion requires specific grade"
        And I follow "Edit settings"
        And I expand all fieldsets
        And I set the field "id_modgrade_type" to "Point"
        And I set the field "id_modgrade_point" to "10"
        And I set the field "Calculation" to "SUM(##2:State##)/2"
        And I press "Save and display"

        # Dataform completion enabling
        Then I follow "Dataform completion requires specific grade"
        And I follow "Edit settings"
        And I expand all fieldsets
        And I set the field "Completion tracking" to "Show activity as complete when conditions are met"
        And I set the field "completionspecificgradeenabled" to "1"
        And I set the field "completionspecificgrade" to "3"
        And I press "Save and display"

        # Add a field with  Submitted and Approved  states
        Given the following dataform "fields" exist:
            | type         | dataform  | name  |
            | entrystate   | dataform1 | State |
        Then I go to manage dataform "fields"
        And I follow "State"
        And I expand all fieldsets
        And I set the field "States" to
            """
            Draft
            Submitted
            Approved
            """
        And I press "Save changes"

        # Add a default view
        Given the following dataform "views" exist:
            | type      | dataform  | name    | default |
            | aligned   | dataform1 | View 01 | 1       |

        # Add some entries
        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student3      |       |               |               |
            | dataform1 | teacher1      |       |               |               |

        Then I follow "Browse"
        Then I log out

        # Student 1 not yet completed
        Then I log in as "student1"
        And I follow "Course 1"
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Not completed: Dataform completion requires specific grade']" "xpath_element"
        And I log out

        # Teacher updates entries
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Dataform completion requires specific grade"
        And I follow "entrystate_1_2"
        And I follow "entrystate_2_2"
        And I follow "entrystate_3_2"
        And I follow "entrystate_5_2"
        And I follow "entrystate_6_1"
        And I log out

        # Student 1 completed
        And I log in as "student1"
        And I follow "Course 1"
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Completed: Dataform completion requires specific grade']" "xpath_element"
        And I log out

        # Teacher reverts approval
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Dataform completion requires specific grade"
        And I follow "View 01"
        And I follow "entrystate_3_0"
        And I log out

        # Student 1 not yet completed
        Then I log in as "student1"
        And I follow "Course 1"
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Not completed: Dataform completion requires specific grade']" "xpath_element"
        And I log out

        # Teacher approves another one for Student 1
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Dataform completion requires specific grade"
        And I follow "View 01"
        And I follow "entrystate_4_2"
        And I log out

        # Student 1 completed
        And I log in as "student1"
        And I follow "Course 1"
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Completed: Dataform completion requires specific grade']" "xpath_element"
        And I log out

        # Clean up
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Dataform completion requires specific grade"
        And I delete this dataform
