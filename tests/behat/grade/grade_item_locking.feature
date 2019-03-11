@mod @mod_dataform @dataformgrading
Feature: Multiple grade items

    @javascript @dataformgrading-gradeitemlocking
    Scenario: Add one grade item.
        Given a fresh site for dataform scenario

        #S: Add activity
        And the following dataform exists:
            | course                | C1        |
            | idnumber              | dataform1 |
            | name                  | Test grade item locking |
            | intro                 | Test grade item locking |
        #:S

        #S: Add view
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |
        #:S

        #S: Add entries
        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student3      |       |               |               |
        #:S

        #S: Add default grade item
        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test grade item locking"

        And I navigate to "Edit settings" in current page administration
        And I set the following fields to these values:
        | Type              | Point     |
        | Maximum grade    | 100        |
        | Grade calculation | ##numentries## + 0   |
        | Locked            | 1    |
        And I press "Save and display"
        And I log out
        #:S

        And the dataform grades are updated

        #S: Student 1 cannot see grades for items.
        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | -     | 0–100   | -       |
            | Course total              | -     | 0–100   | -       |
        And I log out
        #:S

        #S: Teacher unlocks the grade item
        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test grade item locking"
        And I navigate to "Edit settings" in current page administration
        And I set the following fields to these values:
        | Locked            |     |
        And I press "Save and display"
        And I log out
        #:S

        And the dataform grades are updated
        
        #S: Student 1 can see grades for item 0.
        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | 4.00  | 0–100   | 4.00 %   |
            | Course total              | 4.00  | 0–100   | 4.00 %   |
        And I log out
        #:S

        #S: Student 2 can see grades for item 0.
        Then I log in as "student2"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | 2.00  | 0–100   | 2.00 %   |
            | Course total              | 2.00  | 0–100   | 2.00 %   |
        And I log out
        #:S

        #S: Some more entries added
        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student2      |       |               |               |
        #:S
        
        #S: Teacher locks  grade item
        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test grade item locking"
        And I navigate to "Edit settings" in current page administration
        And I set the following fields to these values:
        | Locked            | 1    |
        And I press "Save and display"
        And I log out
        #:S

        And the dataform grades are updated
        
        #S: Student 1 sees same grades for item 0.
        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | 4.00  | 0–100   | 4.00 %   |
            | Course total              | 4.00  | 0–100   | 4.00 %   |
        And I log out
        #:S

        #S: Student 2 sees same grades for item 0.
        Then I log in as "student2"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | 2.00  | 0–100   | 2.00 %   |
            | Course total              | 2.00  | 0–100   | 2.00 %   |
        And I log out
        #:S

        #S: Teacher unlocks  grade item
        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test grade item locking"
        And I navigate to "Edit settings" in current page administration
        And I set the following fields to these values:
        | Locked            |     |
        And I press "Save and display"
        And I log out
        #:S

        And the dataform grades are updated
        
        #S: Student 1 sees updated grades for item 0.
        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | 7.00  | 0–100   | 7.00 %   |
            | Course total              | 7.00  | 0–100   | 7.00 %   |
        And I log out
        #:S

        #S: Student 2 sees updated grades for item 0.
        Then I log in as "student2"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | 6.00  | 0–100   | 6.00 %   |
            | Course total              | 6.00  | 0–100   | 6.00 %   |
        And I log out
        #:S

    #:Scenario

    @javascript @dataformgrading-gradeitemlocking
    Scenario: Add two grade items.
        Given a fresh site for dataform scenario
        Given the following config values are set as admin:
          | dataform_multigradeitems | 1 |

        #S: Add activity
        And the following dataform exists:
            | course                | C1        |
            | idnumber              | dataform1 |
            | name                  | Test grade item locking |
            | intro                 | Test grade item locking |
        #:S

        #S: Add view
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |
        #:S

        #S: Add entries
        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student3      |       |               |               |
        #:S

        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test grade item locking"

        #S: Add default grade item
        And I navigate to "Edit settings" in current page administration
        And I set the following fields to these values:
        | Type              | Point     |
        | Maximum grade    | 4        |
        | Grade calculation | ##numentries## + 0   |
        | Locked            | 1    |
        And I press "Save and display"
        #:S

        #S: Add second grade item
        And I navigate to "Grade items" in current page administration
        And I see "Grade item 0: Test grade item locking"
        And I see "Grade item 1:"
        And I set the following fields to these values:
        | gradeitem[1][itemname]        | Second grade item     |
        | gradeitem[1][modgrade_type]   | Point                 |
        | gradeitem[1][modgrade_point]  | 32        |
        | gradeitem[1][gradecalc]       | ##numentries## * 2   |
        | gradeitem[1][locked]          | 1   |
        And I press "Save changes"
        #:S

        And I log out
        And the dataform grades are updated

        #S: Student 1 cannot see grades for items.
        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | -     | 0–4   | -       |
            | Second grade item         | -     | 0–32  | -         |
            | Course total              | -     | 0–36  | -       |
        And I log out
        #:S

        #S: Teacher unlocks first grade item
        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test grade item locking"
        And I navigate to "Grade items" in current page administration
        And I set the following fields to these values:
        | gradeitem[0][locked]          |    |
        And I press "Save changes"
        And I log out
        #:S

        And the dataform grades are updated
        
        #S: Student 1 can see grades for item 0.
        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | 4.00  | 0–4   | 100.00 %   |
            | Second grade item         | -     | 0–32  | -         |
            | Course total              | 4.00  | 0–4   | 100.00 %   |
        And I log out
        #:S

        #S: Teacher unlocks second grade item
        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Test grade item locking"
        And I navigate to "Grade items" in current page administration
        And I set the following fields to these values:
        | gradeitem[1][locked]          |    |
        And I press "Save changes"
        And I log out
        #:S

        And the dataform grades are updated
        
        #S: Student 1 can see grades for both items.
        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I navigate to "User report" in the course gradebook
        Then the following should exist in the "user-grade" table:
            | Grade item                | Grade | Range | Percentage |
            | Test grade item locking   | 4.00  | 0–4   | 100.00 %   |
            | Second grade item         | 8.00  | 0–32  | 25.00 %  |
            | Course total              | 12.00  | 0–36   | 33.33 %   |
        And I log out
        #:S
    #:Scenario

