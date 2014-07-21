@mod @mod_dataform @dataformfield @dataformfield_text
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
        And I add a dataform field "text" with "Text 01"        

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
        And I set the field "id_field_1_1" to "Hello world"
        And I press "Save"
        Then I see "Hello world"
        
        When I follow "id_editentry1"
        And I set the field "id_field_1_1" to ""
        And I press "Save"
        Then I do not see "Hello world"
        
        # Required *
        When I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I fill textarea "Entry template" with "[[*Text 01]]\n[[EAC:edit]]\n[[EAC:delete]]"
        And I press "Save changes"
        And I follow "Browse"
        And I follow "id_editentry1"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_1" to "This world is required"
        And I press "Save"
        Then I see "This world is required"

        # No edit !
        When I go to manage dataform "views"
        And I follow "id_editview1"
        And I expand all fieldsets
        And I fill textarea "Entry template" with "[[!Text 01]]\n[[EAC:edit]]\n[[EAC:delete]]"
        And I press "Save changes"
        And I follow "Browse"
        And I follow "id_editentry1"
        Then "id_field_1_1" "field" should not exist
        And I press "Save"
        Then I see "This world is required"       

        #Clean up
        And I delete this dataform
        
    @javascript
    Scenario Outline: Add dataform entry with text field
        Given I start afresh with dataform "Test Dataform"
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Add a field field
        When I go to manage dataform "fields"
        And I add a dataform field "text" with "<fielddata>"       
        
        # Add a default view
        When I follow "Views"
        And I add a dataform view "grid" with "View 01"
        Then I see "View 01"
        And I see "Default view is not set."
        When I set "View 01" as default view
        Then I do not see "Default view is not set."

        # Go to browse view
        When I follow "Browse"
        Then I see "Add a new entry"        

        # Add an entry with content
        When I follow "Add a new entry"
        And I set the field "field_1_-1" to "<result>"
        And I press "Save"
        And I wait to be redirected
        Then I see "<result>"
        
        #Clean up
        And I delete this dataform
        
    Examples:
| result | fielddata |
#|    Any thing goes    |    Field 01    Field description 01    Managers only    1        1    100    px                    |
#|    Alphanumeric123456    |    Field 02    Field description 02    Owner and managers    0        0    240    px    alphanumeric                |
#|    Lettersonly    |    Field 03    Field description 03    Everyone    1        1    100    %    lettersonly                |
#|    123456    |    Field 04    Field description 04    Managers only    0        0    50    %    numeric    minlength    2        |
#|    email@email.com    |    Field 05    Field description 05    Owner and managers    1        1    60    em    email    maxlength        50    |
#|    Nopunctuation    |    Field 06    Field description 06    Everyone    0        0    100    em    nopunctuation    rangelength    2    100    |
