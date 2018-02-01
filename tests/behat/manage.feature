@mod @mod_dataform @set_dataform@dataformactivity
Feature: Manage Dataform components
    In order to provide tools for students learning
    As a teacher
    I need to add dataforms to a course

    @javascript
    Scenario: Add update delete
        Given a fresh site with dataform "Basic Dataform Management"
        Then I log in as "teacher1"
        And I am on "Course 1" course homepage
        And I follow "Basic Dataform Management"

        # Views
        Then I go to manage dataform "views"
        And I add a dataform view "aligned" with "View 01"
        And I see "View 01"
        Then I follow "Delete View 01"
        And I press "Continue"
        And I do not see "View 01"
        #And I set dataform view "View Aligned" options to "SL 01\nSL 02\nSL 03\nSL 04"

        # Fields
        Then I go to manage dataform "fields"
        And I add a dataform field "select" with "Field 01"
        And I see "Field 01"
        Then I set dataform field "Field 01" options to "SL 01\nSL 02\nSL 03\nSL 04"
        And I see "Field 01"
        Then I follow "Delete Field 01"
        And I press "Continue"
        And I do not see "Field 01"

        # Filters
        Then I go to manage dataform "filters"
        And I add a dataform filter with "Filter 01"
        And I see "Filter 01"
        Then I follow "Delete Filter 01"
        And I press "Continue"
        And I do not see "Filter 01"

