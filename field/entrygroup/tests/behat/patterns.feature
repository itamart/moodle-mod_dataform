@set_dataform @dataformfield @dataformfield_entrygroup
Feature: Patterns

    Background:
        Given I start afresh with dataform "Test entry group field"

        ## View
        And the following dataform "views" exist:
            | name     | type    | dataform  | default   |
            | View 01  | aligned | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
            """
            [[ENT:id]]
            [[EGR:name]]|Name|groupname
            [[EGR:idnumber]]|ID number|groupidnumber
            [[EGR:members:count]]|Members count|groupmemberscount
            [[EGR:members:list]]|Members list|groupmemberslist
            [[EAC:edit]]
            """

        And the following "group members" exist:
            | user     | group  |
            | student2 | G1 |

    @javascript
    Scenario: The field patterns display the group info in browse and edit modes.
        Given the following dataform "entries" exist:
            | dataform  | group    |
            | dataform1 | G1       |

        And I am in dataform "Test entry group field" "Course 1" as "teacher1"

        Then I should see "Group 1" in the "td.groupname" "css_element"
        And I should see "G1" in the "td.groupidnumber" "css_element"
        And I should see "2" in the "td.groupmemberscount" "css_element"
        And I should see "Student 1, Student 2" in the "td.groupmemberslist" "css_element"

        When I click on "Edit" "link" in the "1" "table_row"

        Then I should see "Group 1" in the "td.groupname" "css_element"
        And I should see "G1" in the "td.groupidnumber" "css_element"
        And I should see "2" in the "td.groupmemberscount" "css_element"
        And I should see "Student 1, Student 2" in the "td.groupmemberslist" "css_element"
    #:Scenario
