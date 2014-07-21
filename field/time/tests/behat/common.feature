@mod @mod_dataform @dataformfield @dataformfield_time
Feature: Common

    @javascript
    Scenario: Manage field
        Given I run dataform scenario "manage field" with:
            | fieldtype | time |
