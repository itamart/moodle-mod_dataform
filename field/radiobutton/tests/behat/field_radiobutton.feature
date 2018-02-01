@set_dataform @dataformfield @dataformfield_radiobutton
Feature: Add dataform entries
    In order to work with a dataform activity
    As a teacher
    I need to add dataform entries to a dataform instance

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test radio field"

        ## Field
        And the following dataform "fields" exist:
            | name         | type          | dataform  | param1 |
            | Radio01      | radiobutton   | dataform1 | {RB 01,RB 02,RB 03,RB 04} |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[Radio01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I am in dataform "Test radio field" "Course 1" as "teacher1"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"

        # Required *
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[*Radio01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And I press "Save"
        #Then I see "You must supply a value here."
        Then "field_1_1_selected" "field" exists

        And I set the field with xpath "//input[@type='radio'][@value='4']" to "checked"
        And I press "Save"
        Then I do not see "RB 01"
        And I do not see "RB 02"
        And I do not see "RB 03"
        And I see "RB 04"

        # No edit !
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[!Radio01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And "field_1_1_selected" "field" should not exist
        And I press "Save"
        Then I do not see "RB 01"
        And I do not see "RB 02"
        And I do not see "RB 03"
        And I see "RB 04"

        # No rules
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[Radio01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field with xpath "//input[@type='radio'][@value='2']" to "checked"
        And I set the field with xpath "//input[@type='radio'][@value='1']" to "checked"
        And I press "Save"
        Then I see "RB 01"
        And I do not see "RB 02"
        And I do not see "RB 03"
        And I do not see "RB 04"

    @javascript
    Scenario Outline: Add dataform entry with radiobutton field
        Given I start afresh with dataform "Test Dataform"
        And I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Basic Dataform Management"

        # Add a field
        When I go to manage dataform "fields"
        And I add a dataform field "radiobutton" with "<fielddata>"

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
        #And I set the field "field_1_-1" to "<input>"
        And I press "Save"
        Then I see "<result>"

    Examples:
| input | result | fielddata |
#|    Option 1    |    Option 1    |    Field 01    Field description 01    0    1        Option 1\nOption 2\nOption 3\nOption 4    Option 1    3    1    |
#|    Today    |    Today    |    Field 02    Field description 02    1    0        Yesterday\nToday\nTomorrow    Today    1    1    |
#|    8    |    8    |    Field 03    Field description 03    2    1        1\n2\n3\n4\n5\n6\n7\n8\n9\n10\n11\n12    8    2    1    |
#|    One option    |    One option    |    Field 04    Field description 04    0    0        One option    One option    2    0    |
#|    Two    |    Two    |    Field 05    Field description 05    1    1        Two\nTwo    Two    1    0    |
#|    Useful    |    Useful    |    Field 06    Field description 06    2    0        Useful    Useful    0    0    |
