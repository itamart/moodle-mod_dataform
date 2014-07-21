@mod @mod_dataform @dataformview @dataformview_csv
Feature: Common

    @javascript
    Scenario: Manage view
        Given I run dataform scenario "manage view" with:
            | viewtype | csv |


    @javascript
    Scenario: Required field
        Given I run dataform scenario "view required field" with:
            | viewtype      | csv       |
            | entrytemplate | Entry template|

            
    @javascript
    Scenario: Submission buttons
        Given I run dataform scenario "view submission buttons" with:
            | viewtype  | csv       |
            | actor     | student1  |
