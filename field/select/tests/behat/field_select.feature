@set_dataform @dataformentry @dataformfield @dataformfield_select
Feature: Add dataform entries
    In order to work with a dataform activity
    As a teacher
    I need to add dataform entries to a dataform instance

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test select field"

        ## Field
        And the following dataform "fields" exist:
            | name         | type          | dataform  | param1 |
            | Select01     | select        | dataform1 | {SL 01,SL 02,SL 03,SL 04} |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[Select01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I am in dataform "Test select field" "Course 1" as "teacher1"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then I do not see "SL 01"
        And I do not see "SL 02"
        And I do not see "SL 03"
        And I do not see "SL 04"
        And "Edit" "link" should exist in the "1" "table_row"

        # Required *
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[*Select01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And I click on "id_field_1_1_selected" "field"
        And I press "Save"
        Then I see "You must supply a value here."

        And I set the field "id_field_1_1_selected" to "SL 03"
        And I set the field "id_field_1_1_selected" to "SL 04"
        And I press "Save"
        Then I do not see "SL 01"
        And I do not see "SL 02"
        And I do not see "SL 03"
        And I see "SL 04"

        # No edit !
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[!Select01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        Then "id_field_1_1_selected" "select" should not exist
        And I press "Save"
        Then I do not see "SL 01"
        And I do not see "SL 02"
        And I do not see "SL 03"
        And I see "SL 04"


    @javascript
    Scenario Outline: Add dataform entry with select field
        Given I start afresh with dataform "Test select field"
        And I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test select field"

        # Add a field field
        When I go to manage dataform "fields"
        And I add a dataform field "select" with "<fielddata>"

        # Add a default view
        When I follow "Views"
        And I add a dataform view "grid" with "View 01"
        Then I see "View 01"
        And I see "Default view is not set."
        When I set "View 01" as default view
        Then I do not see "Default view is not set."

        # Go to browse view
        When I follow "Browse"
        Then I see "Add a new entry"

        # Add an entry with content
        When I follow "Add a new entry"
        #And I set the field "field_1_-1" to "Entry 01"
        And I press "Save"
        Then I see "<result>"

    Examples:
| result | fielddata |
#|    Option 1    |    Field 01    Field description 01    0    1        Option 1\nOption 2\nOption 3\nOption 4    Option 1    1    |
#|    Today    |    Field 02    Field description 02    1    0        Yesterday\nToday\nTomorrow    Today    1    |
#|    8    |    Field 03    Field description 03    2    1        1\n2\n3\n4\n5\n6\n7\n8\n9\n10\n11\n12    8    1    |
#|    One option    |    Field 04    Field description 04    0    0        One option    One option    0    |
#|    Two    |    Field 05    Field description 05    1    1        Two\nTwo    Two    0    |
#|    Useful    |    Field 06    Field description 06    2    0        Useful    Useful    0    |
