@mod @mod_dataform @dataformfield @dataformfield_textarea
Feature: Common

    @javascript
    Scenario: Manage field
        Given I run dataform scenario "manage field" with:
            | fieldtype | textarea |
