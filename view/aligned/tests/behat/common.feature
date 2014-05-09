@mod @mod_dataform @dataformview @dataformview_aligned
Feature: Common

    @javascript
    Scenario: Manage view
        Given I run dataform scenario "manage view" with:
            | viewtype | aligned |


    @javascript
    Scenario: Required field
        Given I run dataform scenario "view required field" with:
            | viewtype      | aligned       |
            | entrytemplate | Entry template|

            
    @javascript
    Scenario: Submission buttons
        Given I run dataform scenario "view submission buttons" with:
            | viewtype  | aligned   |
            | actor     | student1  |
