@set_dataform @dataformfield @dataformfield_text
Feature: Various inputs

    @javascript
    Scenario: Add dataform entry with text field
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

        ## Some text.
        And I follow "Add a new entry"
        And I set the field "id_field_1_-1" to "Hello world"
        And I press "Save"
        Then I see "Hello world"

        ## No content
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1" to ""
        And I press "Save"
        Then I do not see "Hello world"

        ## Alphanumeric123456
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1" to "Alphanumeric123456"
        And I press "Save"
        Then I see "Alphanumeric123456"

        ## Lettersonly
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1" to "Lettersonly"
        And I press "Save"
        Then I see "Lettersonly"

        ## 123456
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1" to "123456"
        And I press "Save"
        Then I see "123456"

        ## email@email.com
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1" to "email@email.com"
        And I press "Save"
        Then I see "email@email.com"

        ## No punctuation!
        And I click on "Edit" "link" in the "1" "table_row"
        And I set the field "id_field_1_1" to "No punctuation!"
        And I press "Save"
        Then I see "No punctuation!"
