@mod @mod_dataform @dataformfield @dataformfield_file @_file_upload
Feature: Add dataform entries
    In order to work with a dataform activity
    As a teacher
    I need to add dataform entries to a dataform instance

    Background:
        Given a fresh site for dataform scenario

        And the following dataform exists:
            | course                | C1        |
            | idnumber              | dataform1 |
            | name                  | Dataform test file field |
            | intro                 | Dataform test file field |

        And the following dataform "fields" exist:
            | name          | type      | dataform  | editable |
            | File01        | file      | dataform1 | 1        |

        And the following dataform "views" exist:
            | name      | type    | dataform  | default   | visible |
            | View01    | aligned | dataform1 | 1         | 1       |

        And I log in as "teacher1"
        And I follow "Course 1"

    @javascript
    Scenario: Use required or noedit patterns
        # No rules no content
        And view "View01" in dataform "1" has the following entry template:
            """
            [[File01]]
            [[EAC:edit]]||entryedit
            [[EAC:delete]]
            """
        And I follow "Dataform test file field"

        And I follow "Add a new entry"
        And I press "Save"

        # Required *
        And view "View01" in dataform "1" has the following entry template:
            """
            [[*File01]]
            [[EAC:edit]]||entryedit
            [[EAC:delete]]
            """
        And I follow "Dataform test file field"

        And I click on "tbody tr:nth-child(1) .entryedit a" "css_element"
        When I press "Save"
        When I upload "mod/dataform/tests/fixtures/test_dataform_entries.csv" file to "File01" filemanager
        And I press "Save"
        Then I see "test_dataform_entries.csv"

        # No edit !
        And view "View01" in dataform "1" has the following entry template:
            """
            [[!File01]]
            [[EAC:edit]]||entryedit
            [[EAC:delete]]
            """
        And I follow "Dataform test file field"

        And I click on "tbody tr:nth-child(1) .entryedit a" "css_element"
        Then I do not see "Maximum size for new files:"
        And I press "Save"
    #:Scenario
