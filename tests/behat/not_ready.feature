@mod @mod_dataform @dataformactivity
Feature: Dataform access

    @javascript
    Scenario: Access not-ready dataform
        Given I start afresh with dataform "Test Dataform"
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "This dataform appears to be new or with incomplete setup"
        And I log out
        
        When I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "This activity is not ready for viewing"
        And I log out
        
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I delete this dataform
