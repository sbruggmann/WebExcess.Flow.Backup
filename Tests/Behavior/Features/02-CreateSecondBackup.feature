Feature: Create a new File and create another Backup
  As a Webdeveloper I wanna add Files to the Backup.

  Scenario:
    Given I add the file "test.txt" with "a new file" as content
    And I execute "./flow backup:now" on local shell
    Then I should see the Feedback "Backup Finished"
    And I should see the Feedback "Added:   1"
    And I should not see the Feedback "Checked: 0"