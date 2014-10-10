@mod1 @mod_dataform1 @dataformview1 @dataformviewimageintemplate @_file_upload
Feature: View image in template

    @javascript
    Scenario: View image in template
        Given I run dataform scenario "view image in template" with:
            | viewtype  | entrytemplate     |
            | aligned   | Entry template    |
            | csv       | Entry template    |
            | grid      | Entry template    |
            | interval  | Entry template    |
            | rss       | Item description  |
            | tabular   | Table design  |
