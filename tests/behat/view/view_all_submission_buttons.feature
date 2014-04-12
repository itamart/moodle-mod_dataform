@mod @mod_dataform @dataformview
Feature: View submission buttons
    This feature should allow various method of entry submission
    via designated submission buttons in the entry form. 

    @javascript
    Scenario Outline: Submit entries with different submission buttons
        # 255 steps
        
        Given I start afresh with dataform "Test Dataform"
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Add a text field
        
        When I go to manage dataform "fields"
        And I add a dataform field "text" with "Field 01"        
        Then I see "Field 01"
        
        # Add a view with all submission buttons

        When I follow "Views"
        And I set the field "Add a view" to "<viewtype>"
        And I expand all fieldsets
        And I set the field "Name" to "View 01"
        And I set the field "savecontbuttonenable" to "check"
        And I set the field "savenewbuttonenable" to "check"
        And I set the field "savecontnewbuttonenable" to "check"
        And I set the field "savenewcontbuttonenable" to "check"
        And I press "Save changes"
        
        Then I see "View 01"
        And I see "Default view is not set."
        When I set "View 01" as default view
        Then I do not see "Default view is not set."

        # Go to browse view
        
        When I follow "Browse"
        Then I see "Add a new entry"

        And I log out
        
        # TEACHER SUBMISSION
        #############################
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "Add a new entry"

        # SAVE
        # The entry should be added
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 01"
        And I press "Save"
        Then I see "Entry 01"
        
        # CANCEL
        # The entry should not be added
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 02"
        And I press "Cancel"
        Then I see "Entry 01"
        And I do not see "Entry 02"
        
        # SAVE and CONTINUE
        # The entry should be added and should stay in form
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 03"
        And I press "Save and Continue"
        Then I do not see "Add a new entry"
        And I do not see "Entry 01"
        And the field "field_1_2" matches value "Entry 03"
        
        When I set the field "field_1_2" to "Entry 02"
        And I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 01"
        And I see "Entry 02"
        
        # SAVE as NEW (existing entry)
        # A new entry should be added
        
        When I follow "id_editentry2"
        And I set the field "field_1_2" to "Entry 03"
        And I press "Save as New"
        Then I see "Add a new entry"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        
        # SAVE as NEW (new entry)
        # The entry should be added
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 04"
        And I press "Save as New"
        Then I see "Add a new entry"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04"
        
        # SAVE and START NEW (new entry)
        # The entry should be added and and new entry form opened

        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 05"
        And I press "Save and Start New"
        Then I do not see "Add a new entry"
        And the field "field_1_-1" matches value ""
        
        When I set the field "field_1_-1" to "Entry 06"
        And I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04"
        And I see "Entry 05"
        And I see "Entry 06"

        # SAVE and START NEW (existing entry)
        # The entry should be updated and new entry form opened

        When I follow "id_editentry4"
        Then the field "field_1_4" matches value "Entry 04"
        
        When I set the field "field_1_4" to "Entry 04 modified"
        And I press "Save and Start New"
        Then I do not see "Add a new entry"
        And the field "field_1_-1" matches value ""
        
        When I set the field "field_1_-1" to "Entry 07"
        And I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04 modified"
        And I see "Entry 05"
        And I see "Entry 06"
        And I see "Entry 07"

        # SAVE as NEW and CONTINUE (new entry):
        # The entry should be added and and remain in its form
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 08"
        And I press "Save as New and Continue"
        Then I do not see "Add a new entry"
        And the field "field_1_8" matches value "Entry 08"
        
        When I set the field "field_1_8" to "Entry 08 modified"
        And I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04 modified"
        And I see "Entry 05"
        And I see "Entry 06"
        And I see "Entry 07"
        And I see "Entry 08 modified"
        
        # SAVE as NEW and CONTINUE (existing entry)
        # The entry should be added and remain in its form

        When I follow "id_editentry8"
        Then the field "field_1_8" matches value "Entry 08 modified"
        
        And I set the field "field_1_8" to "Entry 09"
        And I press "Save as New and Continue"
        Then I do not see "Add a new entry"
        And the field "field_1_9" matches value "Entry 09"
        
        When I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04 modified"
        And I see "Entry 05"
        And I see "Entry 06"
        And I see "Entry 07"
        And I see "Entry 08 modified"
        And I see "Entry 09"

        And I log out
        
        # STUDENT SUBMISSION
        #############################
        When I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "Add a new entry"

        # SAVE
        # The entry should be added
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 10"
        And I press "Save"
        Then I see "Entry 10"
        
        # CANCEL
        # The entry should not be added
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 11"
        And I press "Cancel"
        Then I see "Entry 10"
        And I do not see "Entry 11"
        
        # SAVE and CONTINUE
        # The entry should be added and should stay in form
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 12"
        And I press "Save and Continue"
        Then I do not see "Add a new entry"
        And I do not see "Entry 10"
        And the field "field_1_11" matches value "Entry 12"
        
        When I set the field "field_1_11" to "Entry 11"
        And I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 10"
        And I see "Entry 11"
        
        # SAVE as NEW (existing entry)
        # A new entry should be added
        
        When I follow "id_editentry11"
        And I set the field "field_1_11" to "Entry 12"
        And I press "Save as New"
        Then I see "Add a new entry"
        And I see "Entry 10"
        And I see "Entry 11"
        And I see "Entry 12"
        
        # SAVE as NEW (new entry)
        # The entry should be added
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 13"
        And I press "Save as New"
        Then I see "Add a new entry"
        And I see "Entry 10"
        And I see "Entry 11"
        And I see "Entry 12"
        And I see "Entry 13"
        
        # SAVE and START NEW (new entry)
        # The entry should be added and and new entry form opened

        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 14"
        And I press "Save and Start New"
        Then I do not see "Add a new entry"
        And the field "field_1_-1" matches value ""
        
        When I set the field "field_1_-1" to "Entry 15"
        And I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 10"
        And I see "Entry 11"
        And I see "Entry 12"
        And I see "Entry 13"
        And I see "Entry 14"
        And I see "Entry 15"

        # SAVE and START NEW (existing entry)
        # The entry should be updated and new entry form opened

        When I follow "id_editentry13"
        Then the field "field_1_13" matches value "Entry 13"
        
        When I set the field "field_1_13" to "Entry 13 modified"
        And I press "Save and Start New"
        Then I do not see "Add a new entry"
        And the field "field_1_-1" matches value ""
        
        When I set the field "field_1_-1" to "Entry 16"
        And I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 10"
        And I see "Entry 11"
        And I see "Entry 12"
        And I see "Entry 13 modified"
        And I see "Entry 14"
        And I see "Entry 15"
        And I see "Entry 16"

        # SAVE as NEW and CONTINUE (new entry):
        # The entry should be added and and remain in its form
        
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 17"
        And I press "Save as New and Continue"
        Then I do not see "Add a new entry"
        And the field "field_1_17" matches value "Entry 17"
        
        When I set the field "field_1_17" to "Entry 17 modified"
        And I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 10"
        And I see "Entry 11"
        And I see "Entry 12"
        And I see "Entry 13 modified"
        And I see "Entry 14"
        And I see "Entry 15"
        And I see "Entry 16"
        And I see "Entry 17 modified"
        
        # SAVE as NEW and CONTINUE (existing entry)
        # The entry should be added and remain in its form

        When I follow "id_editentry17"
        Then the field "field_1_17" matches value "Entry 17 modified"
        
        And I set the field "field_1_17" to "Entry 18"
        And I press "Save as New and Continue"
        Then I do not see "Add a new entry"
        And the field "field_1_18" matches value "Entry 18"
        
        When I press "Save"
        Then I see "Add a new entry"
        And I see "Entry 10"
        And I see "Entry 11"
        And I see "Entry 12"
        And I see "Entry 13 modified"
        And I see "Entry 14"
        And I see "Entry 15"
        And I see "Entry 16"
        And I see "Entry 17 modified"
        And I see "Entry 18"

        And I log out
        
        #Clean up
        #############################
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I delete this dataform

    Examples:
        | viewtype |
        | aligned |
        | grid |
        | tabular |
        | pdf |
        | rss |


    @javascript
    Scenario Outline: Submission should not be allowed when no submission buttons enabled
        # 100 steps
        
        Given I start afresh with dataform "Test Dataform"
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Add a text field       
        When I go to manage dataform "fields"
        And I add a dataform field "text" with "Field 01"        
        Then I see "Field 01"
        
        # Add a view with with default submission buttons
        When I follow "Views"
        And I set the field "Add a view" to "<viewtype>"
        And I set the field "Name" to "View 01"
        And I press "Save changes"
        
        Then I see "View 01"
        And I see "Default view is not set."
        When I set "View 01" as default view
        Then I do not see "Default view is not set."

        # Go to browse view        
        When I follow "Browse"
        Then I see "Add a new entry"

        And I log out
        
        # TEACHER SUBMITS AN ENTRY
        #############################
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "Add a new entry"

        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 01"
        And I press "Save"
        Then I see "Entry 01"
        And "id_editentry1" "link" should exist
        
        And I log out

        # STUDENT SUBMITS AN ENTRY
        #############################
        When I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "Add a new entry"

        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 02"
        And I press "Save"
        Then I see "Entry 02"
        And "id_editentry1" "link" should not exist
        And "id_editentry2" "link" should exist
        
        And I log out

        # DISABLE SUBMISSION BUTTONS
        #############################
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        And I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I set the field "savebuttonenable" to ""
        And I set the field "cancelbuttonenable" to ""
        And I press "Save changes"        
        Then I see "View 01"
        
        And I log out
                
        # TEACHER EDITING ENTRY SHOULD BE PREVENED
        #############################
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I do not see "Add a new entry"
        And "id_editentry1" "link" should not exist
        And "id_editentry2" "link" should not exist

        # I shouldn't be able to edit via the url
        
        And I log out
        
        # STUDENT EDITING ENTRY SHOULD BE PREVENED
        #############################
        When I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I do not see "Add a new entry"
        And "id_editentry1" "link" should not exist
        And "id_editentry2" "link" should not exist

        # I shouldn't be able to edit via the url
        
        And I log out
        
        #Clean up
        #############################
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I delete this dataform
        
    Examples:
        | viewtype |
        | aligned |
        | grid |
        | tabular |
        | pdf |
        | rss |