Feature: Create the first Backup
  As a Webdeveloper I wanna create the first Backup.

  Scenario:
    Given I never started the Backup before
    And I execute "./flow backup:key" on local shell
    And I execute "./flow backup:now" on local shell
    Then I should see the Feedback "Backup Finished"
    And I should see the Feedback "Checked: 0"
    And I should not see the Feedback "Added:   0"