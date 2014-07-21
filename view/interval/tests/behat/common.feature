@mod @mod_dataform @dataformview @dataformview_interval
Feature: Common

    @javascript
    Scenario: Manage view
        Given I run dataform scenario "manage view" with:
            | viewtype | interval |


    @javascript
    Scenario: Required field
        Given I run dataform scenario "view required field" with:
            | viewtype      | interval       |
            | entrytemplate | Entry template|

            
    @javascript
    Scenario: Submission buttons
        Given I run dataform scenario "view submission buttons" with:
            | viewtype  | interval  |
            | actor     | student1  |
