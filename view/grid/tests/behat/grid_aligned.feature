@mod @mod_dataform @dataformview @dataformview_grid
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
            | grid      | dataform1 | List view   |

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
        And I replace in field "View template" "##entries##" with "<table id=\"custom-horizontal-table\" class=\"generaltable\"><theader><th>Author</th><th>Text</th><th></th></theader><tbody>##entries##</tbody></table>"
        And I set the field "Entry template" to "<tr><td>[[EAU:name]]</td><td>[[Field Text]]</td><td>[[EAC:edit]] [[EAC:delete]]</td></tr>"
        And I press "Save changes"       

        # Browse
        #---------------------------
        Then I follow "Browse"
        And "custom-horizontal-table" "table" exists
        And I see "1 Entry by Teacher 01"
        And I see "2 Entry by Assistant 01"
        And I see "3 Entry by Student 01"
        And I see "4 Entry by Student 02"
        # ... same for other entries
 
        # Clean up
        #---------------------------------------------
        Then I delete this dataform