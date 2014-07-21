@mod @mod_dataform @dataformview @dataformview_rss
Feature: Common

    @javascript
    Scenario: Manage view
        Given I run dataform scenario "manage view" with:
            | viewtype | rss |


    @javascript
    Scenario: Required field
        Given I run dataform scenario "view required field" with:
            | viewtype      | rss           |
            | entrytemplate | Item description  |

            
    @javascript
    Scenario: Submission buttons
        Given I run dataform scenario "view submission buttons" with:
            | viewtype  | rss       |
            | actor     | student1  |
