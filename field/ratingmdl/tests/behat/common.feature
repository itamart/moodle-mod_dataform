@mod @mod_dataform @dataformfield @dataformfield_ratingmdl
Feature: Common

    @javascript
    Scenario: Manage field
        Given I run dataform scenario "manage field" with:
            | fieldtype | ratingmdl |
            | fieldname | therating |
