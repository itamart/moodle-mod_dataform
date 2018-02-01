@set_dataform @dataformfield @dataformfield_file @_file_upload
Feature: Add dataform entries
    In order to work with a dataform activity
    As a teacher
    I need to add dataform entries to a dataform instance


    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test file field"

        ## Field
        And the following dataform "fields" exist:
            | name         | type          | dataform  |
            | File01       | file          | dataform1 |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[File01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I am in dataform "Test file field" "Course 1" as "teacher1"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"

        # Required *
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[*File01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        Then I see "Maximum size for new files:"
        When I press "Save"
        Then I do not see "Add a new entry"
        And "Edit" "link" should not exist in the "1" "table_row"

        And I see "Maximum size for new files:"
        When I upload "mod/dataform/tests/fixtures/test_dataform_entries.csv" file to "File01" filemanager
        And I press "Save"
        Then I see "test_dataform_entries.csv"

        # No edit !
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[!File01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        Then I do not see "Maximum size for new files:"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"
