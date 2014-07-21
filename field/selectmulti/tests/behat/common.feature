@mod @mod_dataform @dataformfield @dataformfield_selectmulti
Feature: Common

    @javascript
    Scenario: Manage field
        Given I run dataform scenario "manage field" with:
            | fieldtype | selectmulti |
