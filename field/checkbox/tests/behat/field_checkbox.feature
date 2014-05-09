@mod @mod_dataform @dataformfield @dataformfield_checkbox
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
        And I add a dataform field "checkbox" with "Checkbox 01"        
        And I set dataform field "Checkbox 01" options to "CB 01\nCB 02\nCB 03\nCB 04"        

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
        Then I do not see "CB 01"
        And I do not see "CB 02"
        And I do not see "CB 03"
        And I do not see "CB 04"
        And "id_editentry1" "link" should exist        

        # No rules with content
        And I follow "id_editentry1"
        And I set the field "CB 01" to "checked"
        And I press "Save"
        Then I see "CB 01"

        And I follow "id_editentry1"
        And I set the field "CB 01" to ""
        And I press "Save"
        Then I do not see "CB 01"
        
        # Required *
        When I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I fill textarea "Entry template" with "[[*Checkbox 01]]\n[[EAC:edit]]\n[[EAC:delete]]"
        And I press "Save changes"
        And I follow "Browse"
        And I follow "id_editentry1"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "CB 01" to "checked"
        And I set the field "CB 02" to "checked"
        And I set the field "CB 03" to "checked"
        And I set the field "CB 02" to ""
        And I press "Save"
        Then I see "CB 01"
        And I do not see "CB 02"
        And I see "CB 03"
        And I do not see "CB 04"

        # No edit !
        When I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I fill textarea "Entry template" with "[[!Checkbox 01]]\n[[EAC:edit]]\n[[EAC:delete]]"
        And I press "Save changes"
        And I follow "Browse"
        And I follow "id_editentry1"
        Then "CB 01" "checkbox" should not exist
        And "CB 02" "checkbox" should not exist
        And "CB 03" "checkbox" should not exist
        And "CB 04" "checkbox" should not exist
        And I press "Save"
        Then I see "CB 01"
        And I do not see "CB 02"
        And I see "CB 03"
        And I do not see "CB 04"

        #Clean up
        And I delete this dataform

    
    @javascript
    Scenario: Add dataform entry with checkbox field

        # SET THE TEST DATAFORM
        #################################
        Given I start afresh with dataform "Test Dataform"
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Add a field
        When I go to manage dataform "fields"
        And I set the field "Add a field" to "checkbox"
        
        # Ensure defaults
        
        
        # Set options
        And I set the field "Name" to "Field 01"
        And I set the field "Description" to "Field description 01"
        And I fill textarea "Options" with "Option 1\nOption 2\nOption 3\nOption 4" 
        
        # Save
        And I press "Save changes"
        Then I see "Field 01"
        
        # Add a default view
        When I follow "Views"
        And I add a dataform view "aligned" with "View 01"
        Then I see "View 01"
        And I see "Default view is not set."
        When I set "View 01" as default view
        Then I do not see "Default view is not set."

        # BROWSE
        ################################
        When I follow "Browse"
        Then I see "Add a new entry"        

        # Add a new entry without ticking any checkboxes
        ################################
        When I follow "Add a new entry"
        And I press "Save"
        Then "id_editentry1" "link" should exist
        And I do not see "Option 1"
        And I do not see "Option 2"
        And I do not see "Option 3"
        And I do not see "Option 4"
        
        # Edit existing entry field with no content and tick checkboxes
        ################################
        When I follow "id_editentry1"
        And I set the field "Option 1" to "checked"
        And I set the field "Option 2" to "checked"
        And I press "Save"
        Then "id_editentry1" "link" should exist
        And I see "Option 1"
        And I see "Option 2"
        And I do not see "Option 3"
        And I do not see "Option 4"
        
        # Edit existing entry with content and change selection
        ################################
        When I follow "id_editentry1"
        And I set the field "Option 1" to ""
        And I set the field "Option 3" to "checked"
        And I set the field "Option 4" to "checked"
        And I press "Save"
        Then "id_editentry1" "link" should exist
        And I do not see "Option 1"
        And I see "Option 2"
        And I see "Option 3"
        And I see "Option 4"
        
        # Edit existing entry with content and clear content
        ################################
        When I follow "id_editentry1"
        And I set the field "Option 1" to ""
        And I set the field "Option 2" to ""
        And I set the field "Option 3" to ""
        And I set the field "Option 4" to ""
        And I press "Save"
        Then "id_editentry1" "link" should exist
        And I do not see "Option 1"
        And I do not see "Option 2"
        And I do not see "Option 3"
        And I do not see "Option 4"
        
        #Clean up
        And I delete this dataform
