@mod @mod_dataform @dataformfield @dataformfield_url
Feature: Add dataform entries
    In order to work with a dataform activity
    As a teacher
    I need to add dataform entries to a dataform instance
    

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test Dataform"
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Add fields
        When I go to manage dataform "fields"
        And I add a dataform field "url" with "Url 01"        

        # Add a default view
        When I follow "Views"
        And I add a dataform view "aligned" with "View 01"        
        Then I see "View 01"
        And I see "Default view is not set."
        When I set "View 01" as default view
        Then I do not see "Default view is not set."

        # No rules no content
        When I follow "Browse"
        And I follow "Add a new entry"
        And I press "Save"
        Then "id_editentry1" "link" should exist        

        # No rules with content
        And I follow "id_editentry1"
        And I set the field "id_field_1_1" to "www.substantialmethods.com"
        And I press "Save"
        Then I see "www.substantialmethods.com"
        
        When I follow "id_editentry1"
        And I set the field "id_field_1_1" to ""
        And I press "Save"
        Then I do not see "www.substantialmethods.com"
        
        # Required *
        When I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I fill textarea "Entry template" with "[[*Url 01]]\n[[EAC:edit]]\n[[EAC:delete]]"
        And I press "Save changes"
        
        And I follow "Browse"
        And I follow "id_editentry1"
        # Should not save but does not show message because of the http://
        When I press "Save"
        Then "id_field_1_1" "field" should exist
        # Remove http:// to show error message
        When I set the field "id_field_1_1" to ""
        Then I see "You must supply a value here."
        When I set the field "id_field_1_1" to "www.substantialmethods.com"
        And I press "Save"
        Then I see "www.substantialmethods.com"

        # No edit !
        When I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I fill textarea "Entry template" with "[[!Url 01]]\n[[EAC:edit]]\n[[EAC:delete]]"
        And I press "Save changes"
        And I follow "Browse"
        And I follow "id_editentry1"
        Then "id_field_1_1" "field" should not exist
        And I press "Save"
        Then I see "www.substantialmethods.com"       

        #Clean up
        And I delete this dataform