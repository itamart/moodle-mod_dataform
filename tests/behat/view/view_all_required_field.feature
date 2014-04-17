@mod @mod_dataform @dataformview
Feature: Use required fields in view
    In order to force entering entry content in certain field
    As a teacher
    I need to use field patterns with required flag in entry template
    

    @javascript
    Scenario: Use required pattern with a text field
        Given I start afresh with dataform "Test Dataform"
        And I log in as "teacher1"
        And I follow "Course 1"
        And I follow "Test Dataform"

        # Add a text field
        When I go to manage dataform "fields"
        And I add a dataform field "text" with "Text 01"        

        # Add views
        Then I follow "Views"

        And I add a dataform view "aligned" with "View Aligned"        
        And I follow "Edit View Aligned"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[Text 01]]" with "[[*Text 01]]"
        And I press "Save changes"

        And I add a dataform view "csv" with "View Csv"
        And I follow "Edit View Csv"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[Text 01]]" with "[[*Text 01]]"
        And I press "Save changes"

        And I add a dataform view "grid" with "View Grid"
        And I follow "Edit View Grid"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[Text 01]]" with "[[*Text 01]]"
        And I press "Save changes"

        And I add a dataform view "interval" with "View Interval"
        And I follow "Edit View Interval"
        And I expand all fieldsets
        And I replace in field "Entry template" "[[Text 01]]" with "[[*Text 01]]"
        And I press "Save changes"

        And I add a dataform view "rss" with "View Rss"
        And I follow "Edit View Rss"
        And I expand all fieldsets
        And I replace in field "Item description" "[[Text 01]]" with "[[*Text 01]]"
        And I press "Save changes"

        And I add a dataform view "tabular" with "View Tabular"
        And I follow "Edit View Tabular"
        And I expand all fieldsets
        And I replace in field "Table design" "[[Text 01]]" with "[[*Text 01]]"
        And I press "Save changes"


        Then I set "View Aligned" as default view

        Then I follow "Browse"

        # Aligned
        And I follow "Add a new entry"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_-1" to "The field is required in Aligned view"
        And I press "Save"
        Then I see "The field is required in Aligned view"

        # Csv
        And I follow "View Csv"
        And I follow "Add a new entry"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_-1" to "The field is required in Csv view"
        And I press "Save"
        Then I see "The field is required in Csv view"

        # Grid
        And I follow "View Grid"
        And I follow "Add a new entry"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_-1" to "The field is required in Grid view"
        And I press "Save"
        Then I see "The field is required in Grid view"

        # Interval
        And I follow "View Interval"
        And I follow "Add a new entry"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_-1" to "The field is required in Interval view"
        And I press "Save"
        Then I see "The field is required in Interval view"

        # Rss
        And I follow "View Rss"
        And I follow "Add a new entry"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_-1" to "The field is required in Rss view"
        And I press "Save"
        Then I see "The field is required in Rss view"

        # Tabular
        And I follow "View Tabular"
        And I follow "Add a new entry"
        And I press "Save"
        Then I see "You must supply a value here."
        And I set the field "id_field_1_-1" to "The field is required in Tabular view"
        And I press "Save"
        Then I see "The field is required in Tabular view"



        #Clean up
        And I delete this dataform