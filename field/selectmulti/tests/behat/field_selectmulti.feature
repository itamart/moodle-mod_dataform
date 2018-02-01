@set_dataform @dataformentry @dataformfield @dataformfield_selectmulti
Feature: Add dataform entries
    In order to work with a dataform activity
    As a teacher
    I need to add dataform entries to a dataform instance


    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test selectmulti field"

        ## Field
        And the following dataform "fields" exist:
            | name         | type          | dataform  | param1 |
            | Selectmulti01| selectmulti   | dataform1 | {SLM 01,SLM 02,SLM 03,SLM 04} |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[Selectmulti01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I am in dataform "Test selectmulti field" "Course 1" as "teacher1"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then I do not see "SLM 01"
        And I do not see "SLM 02"
        And I do not see "SLM 03"
        And I do not see "SLM 04"
        And "Edit" "link" should exist in the "1" "table_row"

        # Required *
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[*Selectmulti01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And I press "Save"
        #Then I see "You must supply a value here."
        Then "id_field_1_1_selected" "field" exists

        And I set the field "id_field_1_1_selected" to "SLM 03"
        And I press "Save"
        Then I do not see "SLM 01"
        And I do not see "SLM 02"
        And I see "SLM 03"
        And I do not see "SLM 04"

        # No edit !
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[!Selectmulti01]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        Then "id_field_1_1_selected" "select" should not exist
        And I press "Save"
        Then I do not see "SLM 01"
        And I do not see "SLM 02"
        And I see "SLM 03"
        And I do not see "SLM 04"


    @javascript
    Scenario Outline: Add dataform entry with selectmulti field
        Given I start afresh with dataform "Test selectmulti field"
        And I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test selectmulti field"

        # Add a field field
        When I go to manage dataform "fields"
        And I add a dataform field "selectmulti" with "<fielddata>"

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
        #And I set the field "field_1_-1" to "<result>"
        And I press "Save"
        Then I see "<result>"

    Examples:
| result | fielddata |
#|    Option 2, Option 4    |    Field 01    Field description 01    0    1        Option 1\nOption 2\nOption 3\nOption 4    Option 2\nOption 4    3    1    |
#|    Yesterday Today Tomorrow    |    Field 02    Field description 02    1    0        Yesterday\nToday\nTomorrow    Yesterday\nToday\nTomorrow    1    1    |
#|    1,3,5,7,9,11    |    Field 03    Field description 03    2    1        1\n2\n3\n4\n5\n6\n7\n8\n9\n10\n11\n12    1\n3\n5\n7\n9\n11    2    1    |
#|    Field 04    |    Field 04    Field description 04    0    0        One option        2    0    |
#|    Two    |    Field 05    Field description 05    1    1        Two\nTwo    Two    1    0    |
#|    Useful    |    Field 06    Field description 06    2    0        Useful    Useful    0    0    |
