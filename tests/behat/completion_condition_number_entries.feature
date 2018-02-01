@mod @mod_dataform @set_dataform@dataformactivity
Feature: Set a certain number of entries as a completion condition for a dataform
    In order to ensure students are participating in the activity.
    As a teacher
    I need to set a minimum number of entries to mark the forum activity as completed

    @javascript
    Scenario: Set X number of entries as a condition

        Given a fresh site with dataform "Dataform completion condition number entries"

        ## Field
        And the following dataform "fields" exist:
            | name          | type          | dataform  |
            | Field Text    | text          | dataform1 |

        ## View
        And the following dataform "views" exist:
            | name          | type      | dataform  | default   |
            | View Aligned  | aligned   | dataform1 | 1         |

        # Site completion enabling
        And the following config values are set as admin:
          | enablecompletion | 1 |
          | enableavailability | 1 |

        # Course completion enabling
        And the following config values are set as admin:
          | enablecompletion | 1 | moodlecourse |

        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I turn editing mode on
        And I navigate to "Edit settings" in current page administration
        And I expand all fieldsets
        And I set the following fields to these values:
          | Enable completion tracking | Yes |
        And I press "Save and display"

        # Dataform completion enabling

        Then I follow "Dataform completion condition number entries"
        And I navigate to "Edit settings" in current page administration
        And I set the following fields to these values:
          | Completion tracking | Show activity as complete when conditions are met |
          | completionentriesenabled | 1 |
          | completionentries | 2 |
        And I press "Save and display"

        And I log out

        # Student not yet completed

        Then I log in as "student1"
        And I am on "Course 1" course homepage
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Not completed: Dataform completion condition number entries']" "xpath_element"

        # Student adding first entry

        Then I follow "Dataform completion condition number entries"
        And I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 01"
        And I press "Save"

        # Student not yet completed

        And I am on "Course 1" course homepage
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Not completed: Dataform completion condition number entries']" "xpath_element"

        # Student adding second entry

        Then I follow "Dataform completion condition number entries"
        And I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 02"
        And I press "Save"

        # Student completed

        And I am on "Course 1" course homepage
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Completed: Dataform completion condition number entries']" "xpath_element"

        # Student deleting second entry

        Then I follow "Dataform completion condition number entries"
        And I click on "Delete" "link" in the "Entry 02" "table_row"
        And I press "Continue"

        # Student not yet completed

        And I am on "Course 1" course homepage
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Not completed: Dataform completion condition number entries']" "xpath_element"

        # Student adding second entry

        Then I follow "Dataform completion condition number entries"
        And I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 02"
        And I press "Save"

        # Student completed

        And I am on "Course 1" course homepage
        And I hover "//li[contains(concat(' ', normalize-space(@class), ' '), ' modtype_dataform ')]/descendant::img[@alt='Completed: Dataform completion condition number entries']" "xpath_element"

