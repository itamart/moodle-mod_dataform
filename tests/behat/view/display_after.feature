@mod @mod_dataform @set_dataform@dataformview-displayafter
Feature: Display after submission.

    Background:
        Given I start afresh with dataform "Test the display-after setting"
        And the following dataform "fields" exist:
            | name         | type          | dataform  |
            | Text Field   | text          | dataform1 |

        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |
            | View 02  | aligned   | dataform1 |           |

        And view "View 01" in dataform "1" has the following view template:
        """
        <p>##viewlink:View 02##</p>
        ##entries##
        """

        And view "View 02" in dataform "1" has the following view template:
        """
        <p>##viewlink:View 01##</p>
        ##entries##
        """

        And the following dataform "entries" exist:
            | dataform  | user          | Text Field   |
            | dataform1 | teacher1      | Entry 01     |
            | dataform1 | teacher1      | Entry 02     |
            | dataform1 | teacher1      | Entry 03     |


    @javascript
    Scenario: Display after disabled (default).
        #Section:
        And view "View 02" in "dataform1" has the following submission settings:
            | savebuttonenable          | 1         |
            | cancelbuttonenable        | 1         |
            | submissionredirect        | View 01   |

        When I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test the display-after setting"
        And I click on "Edit" "link" in the "Entry 01" "table_row"
        And I press "Save"

        Then I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"

        And I follow "View 02"
        And I click on "Edit" "link" in the "Entry 01" "table_row"
        And I press "Save"

        Then I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        #:Section

    @javascript
    Scenario: Display after enabled.
        #Section:
        And view "View 02" in "dataform1" has the following submission settings:
            | savebuttonenable          | 1         |
            | cancelbuttonenable        | 1         |
            | submissionredirect        | View 01   |
            | submissiondisplayafter    | 1         |

        When I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test the display-after setting"

        # Save without redirection.
        And I click on "Edit" "link" in the "Entry 02" "table_row"
        And I press "Save"

        Then I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"

        # Save with redirection.
        And I follow "View 02"
        And I click on "Edit" "link" in the "Entry 02" "table_row"
        And I press "Save"

        Then I do not see "Entry 01"
        And I see "Entry 02"
        And I do not see "Entry 03"

        # Cancel with redirection.
        And I follow "View 02"
        And I click on "Edit" "link" in the "Entry 03" "table_row"
        And I press "Cancel"

        Then I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        #:Section
