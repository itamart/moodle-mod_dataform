@mod @mod_dataform @dataformview @dataformview_tabular
Feature: Common

    @javascript
    Scenario: Manage view
        Given I run dataform scenario "manage view" with:
            | viewtype | tabular |


    @javascript
    Scenario: Required field
        Given I run dataform scenario "view required field" with:
            | viewtype      | tabular       |
            | entrytemplate | Table design  |

            
    @javascript
    Scenario: Submission buttons
        Given I run dataform scenario "view submission buttons" with:
            | viewtype  | tabular   |
            | actor     | student1  |
