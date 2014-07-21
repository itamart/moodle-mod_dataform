@mod @mod_dataform @dataformfield @dataformfield_commentmdl
Feature: Common

    @javascript
    Scenario: Manage field
        Given I run dataform scenario "manage field" with:
            | fieldtype | commentmdl |
            | fieldname | thecomment |
