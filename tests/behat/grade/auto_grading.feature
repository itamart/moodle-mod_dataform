@mod @mod_dataform @set_dataform @dataformgrading
Feature: Auto grading

    @javascript @dataformgrading-autobynumberofentries
    Scenario: Auto grading by number of entries.
        Given a fresh site for dataform scenario

        And the following dataform exists:
            | course                | C1        |
            | idnumber              | dataform1 |
            | name                  | Auto grade by number of entries |
            | intro                 | Auto grade by number of entries |
            | grade                 | 80        |
            | gradeitem 0 ca        | SUM(##numentries##) |

        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student3      |       |               |               |

        Then I log in as "teacher1"
 
        And I am on "Course 1" course gradebook
        And I should see "-" in the "Student 1" "table_row"
        And I should see "-" in the "Student 2" "table_row"

        And the dataform grades are updated

        And I am on "Course 1" course gradebook
        And I should see "4.00" in the "Student 1" "table_row"
        And I should see "2.00" in the "Student 2" "table_row"
    #:Scenario

    @javascript @dataformgrading-autoonsubmission
    Scenario: Auto grading on submission.
        Given a fresh site for dataform scenario

        And the following dataform exists:
            | course                | C1        |
            | idnumber              | dataform1 |
            | name                  | Auto grade on submission |
            | intro                 | Auto grade on submission |
            | grade                 | 80        |
            | gradeitem 0 ca        | SUM(##:select##)        |

        And the following dataform "fields" exist:
            | name     | type      | dataform  | param1 |
            | select   | select    | dataform1 | {7,11}     |
            | Grading  | grading   | dataform1 |        |

        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |

        And view "View 01" in dataform "1" has the following entry template:
        """
            [[select]]||select
            [[Grading]]
            [[Grading:lastupdated]]|Last updated|lastupdated
            [[EAC:edit]]||entryedit
        """

        ## Add an entry.
        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I follow "Auto grade on submission"
        And I follow "Add a new entry"
        And I set the field "id_field_1_-1_selected" to "7"
        And I press "Save"

        ## No grade yet.
        And I navigate to "User report" in the course gradebook
        And the following should exist in the "user-grade" table:
            | Grade item                | Grade     | Range |
            | Auto grade on submission  | -         | 0–80  |

        ## Cron.
        And the Moodle cron is executed

        ## Grade should be updated.
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        And the following should exist in the "user-grade" table:
            | Grade item                | Grade     | Range |
            | Auto grade on submission  | 7.00      | 0–80  |

        ## Edit the entry.
        And I am on homepage
        And I am on "Course 1" course homepage
        And I follow "Auto grade on submission"
        And I click on ".entryedit a" "css_element"
        And I set the field with xpath "//td[@class='select']//select" to "11"
        And I press "Save"

        ## Cron.
        And the Moodle cron is executed

        ## Grade should NOT be updated b/c there is no multi update.
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        And the following should exist in the "user-grade" table:
            | Grade item                | Grade     | Range |
            | Auto grade on submission  | 7.00      | 0–80  |

        ## Enable multi update.
        And the following dataformfield grading exists:
            | dataform      | dataform1 |
            | name          | Grading   |
            | multiupdate   | 1         |

        And I am on "Course 1" course homepage
        And I follow "Auto grade on submission"
        And I click on ".entryedit a" "css_element"
        And I press "Save"

        ## Cron.
        And the Moodle cron is executed

        ## Grade should be updated b/c there multi update is enabled.
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        And the following should exist in the "user-grade" table:
            | Grade item                | Grade     | Range |
            | Auto grade on submission  | 11.00      | 0–80  |

    #:Scenario
