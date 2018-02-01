@set_dataform @dataformfield @dataformfield_duration @dataformfieldtest
Feature: Add dataform entries

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test duration field"

        ## Field
        And the following dataform "fields" exist:
            | name         | type          | dataform  |
            | Duration    | duration       | dataform1 |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[Duration]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I am in dataform "Test duration field" "Course 1" as "teacher1"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"

        # No rules with content
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1_number" to "61"
        And I press "Save"
        Then I see "61 minutes"

        When I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1_number" to ""
        And I press "Save"
        Then I do not see "61 minutes"

        # Required *
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[*Duration]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1_number" to ""
        And I set the field "id_field_1_1_timeunit" to "minutes"
        #Then I see "You must supply a value here."
        Then "id_field_1_1_number" "field" exists

        And I set the field "id_field_1_1_number" to "53"
        And I press "Save"
        Then I see "53 minutes"

        # No edit !
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[!Duration]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        Then "id_field_1_1_number" "field" should not exist
        And I press "Save"
        Then I see "53 minutes"
