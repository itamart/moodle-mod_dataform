@mod @mod_dataform @dataformactivity
Feature: Add dataform to courses
    In order to provide tools for students learning
    As a teacher
    I need to add dataforms to a course

    @javascript
    Scenario: Add a dataform to a course
        # 111 steps
        
        Given a fresh site with dataform "Test Dataform"
        And I log in as "teacher1"
        And I follow "Course 1"

        # Remove the default dataform
        Then I follow "Test Dataform"
        And I delete this dataform
        
        # Add a dataform
        Then I turn editing mode on
        And I add a "Dataform" to section "1"
        And I set the field "Name" to "Dataform activity 01"
        And I press "Save and display"
        
        # Add fields
        Then I go to manage dataform "fields"
        And I add a dataform field "text" with "Text 01"        

        And I add a dataform field "number" with "Number 01"               
        And I add a dataform field "select" with "Select 01"        
            And I set dataform field "Select 01" options to "SL 01\nSL 02\nSL 03\nSL 04"        
        And I add a dataform field "radiobutton" with "Radiobutton 01"        
            And I set dataform field "Radiobutton 01" options to "RB 01\nRB 02\nRB 03\nRB 04"        
        And I add a dataform field "selectmulti" with "Selectmulti 01"        
            And I set dataform field "Selectmulti 01" options to "SLM 01\nSLM 02\nSLM 03\nSLM 04"        
        And I add a dataform field "checkbox" with "Checkbox 01"        
            And I set dataform field "Checkbox 01" options to "CB 01\nCB 02\nCB 03\nCB 04"        
        And I add a dataform field "textarea" with "Textarea 01"        
        And I add a dataform field "time" with "Time 01"
        And I add a dataform field "url" with "Url 01"
        And I add a dataform field "duration" with "Duration 01"        
        And I add a dataform field "file" with "File 01"        
        And I add a dataform field "picture" with "Picture 01"        
        
        Then I see "Text 01"
        And I see "Number 01"
        And I see "Select 01"
        And I see "Radiobutton 01"
        And I see "Selectmulti 01"
        And I see "Checkbox 01"
        And I see "Textarea 01"
        And I see "Time 01"
        And I see "Url 01"
        And I see "Duration 01"
        And I see "File 01"
        And I see "Picture 01"        

        # Add views
        Then I follow "Views"
        And I set the field "Add a view" to "aligned"
        And I expand all fieldsets
        And I set the field "Name" to "View Aligned"
        And I prepend "<p>##advancedfilter##</p>" to field "View template"
        And I press "Save changes"
        
        
        And I add a dataform view "csv" with "View Csv"
        And I add a dataform view "grid" with "View Grid"
        And I add a dataform view "interval" with "View Interval"
        And I add a dataform view "rss" with "View Rss"
        And I add a dataform view "tabular" with "View Tabular"
        
        Then I see "View Aligned"
        And I see "View Csv"
        And I see "View Grid"
        And I see "View Interval"
        And I see "View Rss"
        And I see "View Tabular"
        And I see "Default view is not set."

        Then I set "View Aligned" as default view
        And I do not see "Default view is not set."
        
        # Add a blank entry in each view
        #------------------------------
        # Aligned (Default)
        Then I follow "Browse"
        And I see "Add a new entry"
        Then I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 01"
        And I press "Save"
        Then I see "Entry 01"

        # Csv
        Then I follow "View Csv"
        And I see "Add a new entry"
        Then I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 02"
        And I press "Save"
        Then I see "Entry 02"
        
        # Grid
        Then I follow "View Grid"
        And I see "Add a new entry"
        Then I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 03"
        And I press "Save"
        Then I see "Entry 03"

        # Interval
        Then I follow "View Interval"
        And I see "Add a new entry"
        Then I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 04"
        And I press "Save"
        Then I see "Entry 04"

        # Rss
        Then I follow "View Rss"
        And I see "Add a new entry"
        Then I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 05"
        And I press "Save"
        Then I see "Entry 05"
        
        # Tabular
        Then I follow "View Tabular"
        And I see "Add a new entry"
        Then I follow "Add a new entry"
        And I set the field "field_1_-1" to "Entry 06"
        And I press "Save"
        Then I see "Entry 06"

        ### Quick filtering ###
        
        # Per page
        #-------------------------
        And I do not see "Quick filter"
        And I do not see "Next"
        And I do not see "Previous"
        
        Then I set the field "uperpage" to "1"
        And I see "Quick filter"
        And I see "Next"
        And I do not see "Previous"
        And I see "Entry 01"
        And I do not see "Entry 02"
        
        Then I click on ".page1 a" "css_element"
        And I see "Quick filter"
        And I see "Next"
        And I see "Previous"
        And I do not see "Entry 01"
        And I see "Entry 02"
        
        Then I set the field "uperpage" to "2"
        And I see "Quick filter"
        And I see "Next"
        And I see "Previous"
        And I do not see "Entry 01"
        And I do not see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04"
        
        Then I set the field "uperpage" to "3"
        And I see "Quick filter"
        And I do not see "Next"
        And I see "Previous"
        And I do not see "Entry 01"
        And I do not see "Entry 02"
        And I do not see "Entry 03"
        And I see "Entry 04"
        And I see "Entry 05"
        And I see "Entry 06"
        
        Then I set the field "id_filtersmenu" to "* Reset quick filter"
        And I do not see "Quick filter"
        And I do not see "Next"
        And I do not see "Previous"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04"
        And I see "Entry 05"
        And I see "Entry 06"
        
        # Quick search
        #-------------------------
        Then I set the field "usearch" to "Entry 01"
        And I press Enter on "usearch" "field"
        And I see "Quick filter"
        And I see "Entry 01"
        And I do not see "Entry 02"

        # Define and apply a standard filter
        #-------------------------
        
        # Add filter: Last 2 entries
        Then I go to manage dataform "filters"
        And I see "There are no filters defined for this dataform."
        Then I follow "Add a filter"
        And I set the field "Name" to "Last 2 Entries"
        And I set the field "Per page" to "2"
        And I set sort criterion "1" to "1,content" "1"
        And I press "Save changes"
        Then I see "Add a filter"
        And I see "Last 2 Entries"
        
        # Add filter: With "Entry 01" content
        Then I follow "Add a filter"
        And I set the field "Name" to "With Entry_01"
        And I set search criterion "1" to "AND" "1,content" "" "=" "Entry 01"
        And I press "Save changes"
        Then I see "Add a filter"
        And I see "With Entry_01"
        
        # Add filter: With Entry 01 and Entry_05 content
        Then I follow "Add a filter"
        And I set the field "Name" to "With Entry_01 and Entry_05"
        And I set search criterion "1" to "AND" "1,content" "" "=" "Entry 01"
        And I set search criterion "2" to "AND" "1,content" "" "=" "Entry 05"
        And I press "Save changes"
        Then I see "Add a filter"
        And I see "With Entry_01 and Entry_05"

        # Add filter: With Entry 01 or Entry_05 content
        Then I follow "Add a filter"
        And I set the field "Name" to "With Entry_01 or Entry_05"
        And I set search criterion "1" to "OR" "1,content" "" "=" "Entry 01"
        And I set search criterion "2" to "OR" "1,content" "" "=" "Entry 05"
        And I press "Save changes"
        Then I see "Add a filter"
        And I see "With Entry_01 or Entry_05"

        # Browse and filter
        Then I follow "Browse"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04"
        And I see "Entry 05"
        And I see "Entry 06"

        Then I set the field "id_filtersmenu" to "Last 2 Entries"
        And I see "Entry 05"
        And I see "Entry 06"
        And I do not see "Entry 01"
        And I do not see "Entry 02"        
        
        Then I set the field "id_filtersmenu" to "With Entry_01"
        And I see "Entry 01"
        And I do not see "Entry 02"        
        And I do not see "Entry 03"
        And I do not see "Entry 04"        
        And I do not see "Entry 05"
        And I do not see "Entry 06"        
        
        Then I set the field "id_filtersmenu" to "With Entry_01 and Entry_05"
        And I do not see "Entry 01"
        And I do not see "Entry 02"        
        And I do not see "Entry 03"
        And I do not see "Entry 04"        
        And I do not see "Entry 05"
        And I do not see "Entry 06"        
        
        Then I set the field "id_filtersmenu" to "With Entry_01 or Entry_05"
        And I see "Entry 01"
        And I do not see "Entry 02"        
        And I do not see "Entry 03"
        And I do not see "Entry 04"        
        And I see "Entry 05"
        And I do not see "Entry 06"        
        
        # Define and apply an advanced filter
        #-------------------------
        Then I follow "View Aligned"
        
        # Add filter: My Entry 01 or Entry_03 or Entry_06 content
        Then I follow "Advanced filter"
        And I set the field "Name" to "My Entry 01 or Entry_03 or Entry_06"
        And I set search criterion "1" to "OR" "1,content" "" "=" "Entry 03"
        And I set search criterion "2" to "OR" "1,content" "" "=" "Entry 01"
        And I set search criterion "3" to "OR" "1,content" "" "=" "Entry 06"
        And I press "Save changes"
        
        Then I follow "View Aligned"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04"
        And I see "Entry 05"
        And I see "Entry 06"

        Then I set the field "id_filtersmenu" to "My Entry 01 or Entry_03 or Entry_06"
        And I see "Entry 01"
        And I do not see "Entry 02"
        And I see "Entry 03"
        And I do not see "Entry 04"
        And I do not see "Entry 05"
        And I see "Entry 06"

        Then I set the field "id_filtersmenu" to "* Reset saved filters"
        And I see "Entry 01"
        And I see "Entry 02"
        And I see "Entry 03"
        And I see "Entry 04"
        And I see "Entry 05"
        And I see "Entry 06"
        
        
        # Clean up
        And I delete this dataform        
