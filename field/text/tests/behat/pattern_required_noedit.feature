@mod @mod_dataform @dataformfield @dataformfield_text
Feature: Pattern required noedit

    @javascript
    Scenario: Use required or noedit patterns
        Given I start afresh with dataform "Test text field"

        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test text field"

        ## Field
        And I go to manage dataform "fields"
        And I add a dataform field "text" with "Text"

        ## View
        And I go to manage dataform "views"
        And I add a dataform view "aligned" with "View 01"
        And I set "View 01" as default view

        And I follow "Browse"

        # No rules no content
        And I follow "Add a new entry"
        And I press "Save"
        Then "id_editentry1" "link" exists

        # Required *
        When I go to manage dataform "views"
        And I follow "Edit View 01"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[Text]]" with "[[*Text]]"
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
        And I replace in field "Entry template" "[[*Text]]" with "[[!Text]]"
        And I press "Save changes"
        And I follow "Browse"
        And I follow "id_editentry1"
        Then "id_field_1_1" "field" should not exist
        And I press "Save"
        Then I see "This world is required"
