@set_dataform @dataformfield @dataformfield_checkbox @dataformfieldtest
Feature: Pattern required noedit

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test field checkbox"

        ## Field
        And the following dataform "fields" exist:
            | name         | type          | dataform  | param1 |
            | Checkbox    | checkbox       | dataform1 | {CB 01,CB 02,CB 03,CB 04} |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[Checkbox]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I am in dataform "Test field checkbox" "Course 1" as "teacher1"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then I do not see "CB 01"
        And I do not see "CB 02"
        And I do not see "CB 03"
        And I do not see "CB 04"
        And "Edit" "link" should exist in the "1" "table_row"

        # No rules with content
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "CB 01" to "checked"
        And I press "Save"
        Then I see "CB 01"

        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "CB 01" to ""
        And I press "Save"
        Then I do not see "CB 01"

        # Required *
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[*Checkbox]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        And I press "Save"
        #Then I see "You must supply a value here."
        Then "CB 01" "checkbox" exists

        And I set the field "CB 01" to "checked"
        And I set the field "CB 02" to "checked"
        And I set the field "CB 03" to "checked"
        And I set the field "CB 02" to ""
        And I press "Save"
        Then I see "CB 01"
        And I do not see "CB 02"
        And I see "CB 03"
        And I do not see "CB 04"

        # No edit !
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]||entryid
            [[!Checkbox]]||entrycontent
            [[EAC:edit]]||entryedit
            [[EAC:delete]]||entrydelete
            """

        And I click on "Edit" "link" in the "1" "table_row"
        Then "CB 01" "checkbox" should not exist
        And "CB 02" "checkbox" should not exist
        And "CB 03" "checkbox" should not exist
        And "CB 04" "checkbox" should not exist
        And I press "Save"
        Then I see "CB 01"
        And I do not see "CB 02"
        And I see "CB 03"
        And I do not see "CB 04"


    @javascript
    Scenario: Add dataform entry with checkbox field
        Given I start afresh with dataform "Test field checkbox"

        ## Field
        And the following dataform "fields" exist:
            | name         | type          | dataform  | param1 |
            | Checkbox    | checkbox       | dataform1 | {Option 1,Option 2,Option 3,Option 4} |

        ## View
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test field checkbox"

        # BROWSE
        ################################
        And I follow "Add a new entry"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"
        And I do not see "Option 1"
        And I do not see "Option 2"
        And I do not see "Option 3"
        And I do not see "Option 4"

        # Edit existing entry field with no content and tick checkboxes
        ################################
        Then I click on "Edit" "link" in the "1" "table_row"
        And I set the field "Option 1" to "checked"
        And I set the field "Option 2" to "checked"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"
        And I see "Option 1"
        And I see "Option 2"
        And I do not see "Option 3"
        And I do not see "Option 4"

        # Edit existing entry with content and change selection
        ################################
        Then I click on "Edit" "link" in the "1" "table_row"
        And I set the field "Option 1" to ""
        And I set the field "Option 3" to "checked"
        And I set the field "Option 4" to "checked"
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"
        And I do not see "Option 1"
        And I see "Option 2"
        And I see "Option 3"
        And I see "Option 4"

        # Edit existing entry with content and clear content
        ################################
        Then I click on "Edit" "link" in the "1" "table_row"
        And I set the field "Option 1" to ""
        And I set the field "Option 2" to ""
        And I set the field "Option 3" to ""
        And I set the field "Option 4" to ""
        And I press "Save"
        Then "Edit" "link" should exist in the "1" "table_row"
        And I do not see "Option 1"
        And I do not see "Option 2"
        And I do not see "Option 3"
        And I do not see "Option 4"
