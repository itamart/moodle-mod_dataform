@mod @mod_dataform @dataformfield @dataformfield_time
Feature: Adding entries with field

    Background:
        Given I start afresh with dataform "Test time field"

        ## Field
        And the following dataform "fields" exist:
            | name      | type  | dataform  |
            | Time 01   | time  | dataform1 |

        ## View
        And the following dataform "views" exist:
            | name     | type    | dataform  | default   |
            | View 01  | aligned | dataform1 | 1         |


    @javascript
    Scenario: The data/time selector is disabled by default.
        Given I am in dataform "Test time field" "Course 1" as "teacher1"
        When I follow "Add a new entry"
        Then the "field_1_-1[day]" "select" should be disabled
    #:Scenario

    @javascript
    Scenario: Required field is enabled by default and cannot be disabled.
        Given I am in dataform "Test time field" "Course 1" as "teacher1"
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[EAU:picture]]
            [[EAU:name]]
            [[*Time 01]]
            [[EAC:edit]]
            [[EAC:delete]]
            """

        When I follow "Add a new entry"

        Then the "field_1_-1[day]" "select" should be enabled
        And "id_field_1_-1_enabled" "checkbox" should not exist
    #:Scenario

    @javascript
    Scenario: Noedit field does not display input elements in editing mode.
        Given I am in dataform "Test time field" "Course 1" as "teacher1"
        And view "View 01" in dataform "1" has the following entry template:
            """
            [[EAU:picture]]
            [[EAU:name]]
            [[!Time 01]]
            [[EAC:edit]]
            [[EAC:delete]]
            """

        When I follow "Add a new entry"

        Then "field_1_1[day]" "select" should not exist
    #:Scenario

    @javascript
    Scenario: Noedit field displays content in editing mode.
        Given view "View 01" in dataform "1" has the following entry template:
            """
            [[EAU:picture]]
            [[EAU:name]]
            [[!Time 01]]
            [[EAC:edit]]
            [[EAC:delete]]
            """
        And the following dataform "entries" exist:
            | dataform  | user           | Time 01           |
            | dataform1 | student1       | 2014-12-05 08:00  |
        And I am in dataform "Test time field" "Course 1" as "teacher1"

        When I follow "Edit Entry 1"

        Then I see "December 2014"
    #:Scenario

    @javascript
    Scenario: Teacher adds entry without content.
        Given I am in dataform "Test time field" "Course 1" as "teacher1"
        When I follow "Add a new entry"
        And I press "Save"
        Then "id_editentry1" "link" should exist
    #:Scenario

    @javascript
    Scenario: Teacher adds entry with content.
        Given I am in dataform "Test time field" "Course 1" as "teacher1"
        When I add a dataform entry with:
            | field_1_-1[enabled] | checked |
            | field_1_-1[year]    | 2013    |
        Then I see "2013"
    #:Scenario
