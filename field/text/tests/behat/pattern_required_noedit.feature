@set_dataform @dataformfield @dataformfield_text
Feature: Pattern required noedit

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test text field"

        ## Field
        And the following dataform "fields" exist:
            | name     | type       | dataform  |
            | Text     | text       | dataform1 |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[Text]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I am in dataform "Test text field" "Course 1" as "teacher1"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"

        # Required *
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[*Text]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]|entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And I click on "field_1_1" "field"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "field_1_1" to "This world is required"
        And I press "Save"
        Then I see "This world is required"

        # No edit !
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[!Text]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]|entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        Then "field_1_1" "field" should not exist
        And I press "Save"
        Then I see "This world is required"
