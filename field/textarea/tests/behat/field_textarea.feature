@set_dataform @dataformfield @dataformfield_textarea
Feature: Add dataform entries

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test textarea field"

        ## Field
        And the following dataform "fields" exist:
            | name     | type       | dataform  |
            | Textarea | textarea   | dataform1 |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[Textarea]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I am in dataform "Test textarea field" "Course 1" as "teacher1"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"

        # No rules with content
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1" to "Hello world"
        And I press "Save"
        Then I see "Hello world"

        When I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1" to ""
        And I press "Save"
        Then I do not see "Hello world"

        # Required *
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[*Textarea]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And I click on "field_1_1" "field"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_1" to "This world is required"
        And I press "Save"
        Then I see "This world is required"

        # No edit !
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[!Textarea]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        Then "id_field_1_1" "field" does not exist
        And I see "This world is required"
        And I press "Save"
        Then I see "This world is required"
