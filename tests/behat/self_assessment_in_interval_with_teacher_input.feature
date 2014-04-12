@mod @mod_dataform @dataformactivity
Feature: Self assessment in interval with teacher input
    Students must submit a self-assessment in intervals - a series of multiple choice and text responses.
    Once submitted teacher to be able to see it, and respond to each of the same questions themselves.  
    In the end, both student's and teacher's responses are visible together on the same form. In the end, both the teacher and student should be able to see both responses to each question.

    @javascript
    Scenario: 4 weeks interval, multiple choice and text response
        # 129 steps
        
        ### Activity setup ###
        
        Given I start afresh with dataform "Test Dataform"
        And the following "permission overrides" exist:
            | capability                    | permission    | role           | contextlevel    | reference |
            | mod/dataform:entryownupdate   | Prevent       | student        | Activity module | dataform1 |
            | mod/dataform:entryowndelete   | Prevent       | student        | Activity module | dataform1 |
            
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "This dataform appears to be new or with incomplete setup"
        
        # Set the time interval and separate participants
        #---------------------------
        When I follow "Edit settings"
        And I expand all fieldsets       
        And I set the field "Maximum entries" to "1" 
        And I set the field "id_timeavailable_enabled" to "checked" 
        And I set the field "timeinterval[number]" to "4" 
        And I set the field "timeinterval[timeunit]" to "weeks" 
        And I set the field "Number of intervals" to "10" 
        And I set the field "Separate participants" to "Yes" 
        And I press "Save and display"
        Then I see "This dataform appears to be new or with incomplete setup"

        # Add a text and select fields for student responses
        #---------------------------
        Then I go to manage dataform "fields"
        And I add a dataform field "text" with "Student Response 01"        
        And I add a dataform field "select" with "Student Response 02"
        And I follow "Student Response 02"
        And I set the field "Options" to
            """
            Never
            Rarely
            Frequently
            Always
            """
        And I press "Save changes"

        # Add a text and select fields for teacher responses (non-editable) so that students cannot edit 
        #---------------------------
        Then I add a dataform field "text" with "Teacher Response 01"        
        And I follow "Teacher Response 01"
        And I set the field "Editable" to "No"
        And I press "Save changes"

        Then I add a dataform field "select" with "Teacher Response 02"        
        And I follow "Teacher Response 02"
        And I set the field "Editable" to "No"
        And I set the field "Options" to
            """
            Never
            Rarely
            Frequently
            Always
            """
        And I press "Save changes"
        
        # Add a view with with default submission buttons
        #---------------------------
        When I follow "Views"
        And I set the field "Add a view" to "grid"
        And I set the field "Name" to "View 01"
        And I press "Save changes"
        
        Then I see "View 01"
        And I see "Default view is not set."
        When I set "View 01" as default view
        Then I do not see "Default view is not set."

        And I log out
        
        ### INTERVAL 1 ###
        
        # Student 1 submits self assessment and can update only the student responses
        #---------------------------------------------
        When I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "Add a new entry"
        
        When I follow "Add a new entry"
        Then "field_1_-1" "field" should exist
        And "field_2_-1_selected" "field" should exist
        But "field_3_-1" "field" should not exist
        And "field_4_-1_selected" "field" should not exist

        And I set the field "field_1_-1" to "Student response to item 01 in Submission 01"
        And I set the field "field_2_-1_selected" to "Frequently"
        And I press "Save"
        Then I see "Student response to item 01 in Submission 01"
        And I see "Frequently"
        And I do not see "Add a new entry"
        And "id_editentry1" "link" should not exist
        And "id_deleteentry1" "link" should not exist
        
        And I log out
        
        # Teacher adds teacher responses to student 1 submission
        #---------------------------
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "Student response to item 01 in Submission 01"

        When I follow "id_editentry1"
        Then "field_1_1" "field" should exist
        And "field_2_1_selected" "field" should exist
        And "field_3_1" "field" should exist
        And "field_4_1_selected" "field" should exist
        
        Then I set the field "field_3_1" to "Teacher response to item 01 in Submission 01"
        And I set the field "field_4_1_selected" to "Rarely"
        And I press "Save"
        Then I see "Student response to item 01 in Submission 01"
        And I see "Teacher response to item 01 in Submission 01"
        And I see "Frequently"
        And I see "Rarely"
        
        And I log out

        # Student 1 views own and teacher responses
        #---------------------------------------------
        When I log in as "student1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I see "Student response to item 01 in Submission 01"
        And I see "Teacher response to item 01 in Submission 01"
        And I see "Frequently"
        And I see "Rarely"
        
        And I log out

        # Student 2 cannot view student 1 submission
        #---------------------------------------------
        When I log in as "student2"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I do not see "Student response to item 01 in Submission 01"
        And I do not see "Teacher response to item 01 in Submission 01"
        And I do not see "Frequently"
        And I do not see "Rarely"
        
        And I log out
        
        # Clean up
        #---------------------------------------------
        When I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        Then I delete this dataform