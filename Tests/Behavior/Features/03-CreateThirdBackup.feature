Feature: Update an existing File and create another Backup
  As a Webdeveloper I wanna update Files in the Backup.

  Scenario:
    Given I add the file "test.txt" with "an existing file updated" as content
    And I execute "./flow backup:now" on local shell
    Then I should see the Feedback "Backup Finished"
    And I should see the Feedback "Updated: 1"
    And I should see the Feedback "Added:   0"
    And I should not see the Feedback "Checked: 0"