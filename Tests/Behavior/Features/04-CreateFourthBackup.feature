Feature: Remove an existing File and create another Backup
  As a Webdeveloper I wanna remove Files in the Backup.

  Scenario:
    Given I remove the file "test.txt"
    And I execute "./flow backup:now" on local shell
    Then I should see the Feedback "Backup Finished"
    And I should see the Feedback "Removed: 1"
    And I should see the Feedback "Updated: 0"
    And I should not see the Feedback "Checked: 0"