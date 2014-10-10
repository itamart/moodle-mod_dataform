@mod @mod_dataform @dataformfield @dataformfield_time
Feature: Add dataform entries

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test time field"

        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test time field"

        ## Field
        And I go to manage dataform "fields"
        And I add a dataform field "time" with "Time 01"

        ## View
        And I go to manage dataform "views"
        And I add a dataform view "aligned" with "View 01"
        And I set "View 01" as default view

        And I follow "Browse"

        # No rules no content
        Then I follow "Add a new entry"
        And I press "Save"
        Then "id_editentry1" "link" should exist        

        # No rules with content
        Then I follow "id_editentry1"
        And the "field_1_1[day]" "select" should be disabled
        And I set the field "id_field_1_1_enabled" to "checked" 
        And I press "Save"
        Then I see "2014"
        
        Then I follow "id_editentry1"
        And I set the field "id_field_1_1_enabled" to "" 
        And I press "Save"
        Then I do not see "2014"
        
        # Required *
        Then I go to manage dataform "views"
        And I follow "Edit View 01"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[Time 01]]" with "[[*Time 01]]"
        And I press "Save changes"
        
        And I follow "Browse"
        
        And I follow "id_editentry1"
        Then the "field_1_1[day]" "select" should be enabled
        And "id_field_1_1_enabled" "checkbox" should not exist                 
        Then I press "Save"        
        Then I see "2014"

        # No edit !
        Then I go to manage dataform "views"
        And I follow "Edit View 01"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[*Time 01]]" with "[[!Time 01]]"
        And I press "Save changes"
        
        And I follow "Browse"
        
        And I follow "id_editentry1"
        Then "id_field_1_1_enabled" "checkbox" should not exist
        And I press "Save"
        Then I see "2014"       
