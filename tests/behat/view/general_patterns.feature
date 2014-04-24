@mod @mod_dataform @dataformview
Feature: Dataform view general patterns
    In order to modify look and behavior of a Dataform view
    As a Teacher
    I need to add and remove view general patterns in the View template

    @javascript
    Scenario: General patterns
        ### Background ###        
        Given I start afresh with dataform "Test Dataform"
        And the following dataform "fields" exist:
            | type  | dataform  | name        |
            | text  | dataform1 | Field Text  |

        And the following dataform "views" exist:
            | type      | dataform  | name          |
            | aligned   | dataform1 | List view     |
            | grid      | dataform1 | Single view   |
            | grid      | dataform1 | Entry edit   |

        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  | Field Text                |
            | dataform1 | teacher1      |       |               |               | 1 Entry by Teacher 01     |
            | dataform1 | assistant1    |       |               |               | 2 Entry by Assistant 01   |
            | dataform1 | student1      |       |               |               | 3 Entry by Student 01     |
            | dataform1 | student2      |       |               |               | 4 Entry by Student 02     |
            | dataform1 | student3      |       |               |               | 5 Entry by Student 03     |

            
        # Set up
        #---------------------------
        
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"
        And I go to manage dataform "views"
        
        Then I set "List view" as default view

        # Adjust the List view
        And I follow "Edit List view"
        And I expand all fieldsets
        And I replace in field "View template" "##addnewentry##" with "##viewlink:Entry edit;Add a new entry from list;;##"
        And I press "Save changes"
        
        # Adjust the Single view
        And I follow "Edit Single view"
        And I expand all fieldsets
        And I set the field "Per page" to "1"
        And I replace in field "View template" "##addnewentry##" with "##viewlink:Entry edit;Add a new entry from single;;##"
        And I replace in field "Entry template" "[[EAC:edit]]" with "[[EAC:edit:Entry edit]]"
        And I press "Save changes"
        
        # Adjust the Entry edit view
        And I follow "Edit Entry edit"
        And I expand all fieldsets
        And I set the field "View template" to "<h3>Entry editing form</h3>##editentry##"
        And I set the field "Redirect to another view" to "Single view"
        And I press "Save changes"
        

        # Browse
        #---------------------------
        Then I follow "Browse"
        And I see "1 Entry by Teacher 01"
        And I see "2 Entry by Assistant 01"
        And I see "3 Entry by Student 01"
        And I see "4 Entry by Student 02"
        # ... same for other entries
        
        # Add
        Then I follow "Add a new entry from list"
        And I see "Entry editing form"
        And I set the field "id_field_1_-1" to "6 Entry by Teacher 01"
        And I press "Save"

        Then I see "Add a new entry from single"
        And I see "6 Entry by Teacher 01"
        And I do not see "1 Entry by Teacher 01"

        # Update
        Then I follow "Edit Entry 6"
        And I see "Entry editing form"
        And I set the field "id_field_1_6" to "6 Entry by Teacher 01 updated"
        And I press "Save"

        Then I see "Add a new entry from single"
        And I see "6 Entry by Teacher 01 updated"
        And I do not see "1 Entry by Teacher 01"

 
        # Clean up
        #---------------------------------------------
        Then I delete this dataform