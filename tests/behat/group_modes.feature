@mod @dataform_no_mod @dataformactivity
Feature: Group modes

    @javascript
    Scenario: Group modes
        Given I start afresh with dataform "Test Dataform"

        And the following dataform "fields" exist:
            | type  | dataform  | name        |
            | text  | dataform1 | Field Text  |

        And the following dataform "views" exist:
            | type      | dataform  | name    | default |
            | aligned   | dataform1 | View 01 | 1       |

        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  | Field Text                |
            | dataform1 | student1      | G1    |               |               | 1 Entry by Student 01     |
            | dataform1 | student2      | G2    |               |               | 2 Entry by Student 02     |
            | dataform1 | student3      | G1    |               |               | 3 Entry by Student 03     |
            | dataform1 | teacher1      |       |               |               | 4 Entry by Teacher 01     |

        # Visible groups
        #================
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Set separate participants
        #---------------------------
        When I follow "Edit settings"
        And I expand all fieldsets
        And I set the field "Group mode" to "Visible groups"
        And I press "Save and display"

        # Teacher's view
        #---------------------------
        # All participants
        Then I see "1 Entry by Student 01"
        And I see "2 Entry by Student 02"
        And I see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        # Group 1
        Then I set the field "Visible groups" to "Group 1"
        Then I see "1 Entry by Student 01"
        And I do not see "2 Entry by Student 02"
        And I see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        # Group 2
        Then I set the field "Visible groups" to "Group 2"
        Then I do not see "1 Entry by Student 01"
        And I see "2 Entry by Student 02"
        And I do not see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        And I log out

        # Student 1's view
        #---------------------------
        When I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Group 1
        Then I see "1 Entry by Student 01"
        And I do not see "2 Entry by Student 02"
        And I see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        # Group 2
        Then I set the field "Visible groups" to "Group 2"
        Then I do not see "1 Entry by Student 01"
        And I see "2 Entry by Student 02"
        And I do not see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        # All participants
        Then I set the field "Visible groups" to "All participants"
        Then I see "1 Entry by Student 01"
        And I see "2 Entry by Student 02"
        And I see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        And I log out

        # Separate groups
        #================
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Set separate participants
        #---------------------------
        When I follow "Edit settings"
        And I expand all fieldsets
        And I set the field "Group mode" to "Separate groups"
        And I press "Save and display"

        # Teacher's view
        #---------------------------
        # All participants
        Then I see "1 Entry by Student 01"
        And I see "2 Entry by Student 02"
        And I see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        # Group 1
        Then I set the field "Separate groups" to "Group 1"
        Then I see "1 Entry by Student 01"
        And I do not see "2 Entry by Student 02"
        And I see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        # Group 2
        Then I set the field "Separate groups" to "Group 2"
        Then I do not see "1 Entry by Student 01"
        And I see "2 Entry by Student 02"
        And I do not see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        And I log out

        # Student 1's view
        #---------------------------
        When I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Only Group 1 is available
        Then I see "Separate groups: Group 1"
        And I see "1 Entry by Student 01"
        And I do not see "2 Entry by Student 02"
        And I see "3 Entry by Student 03"
        And I see "4 Entry by Teacher 01"

        And I log out


        # Clean up
        #---------------------------------------------
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I delete this dataform