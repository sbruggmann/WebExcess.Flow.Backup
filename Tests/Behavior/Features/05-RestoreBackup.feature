Feature: Restore the previous Backup
  As a Webdeveloper I wanna restore Backups.

  Scenario:
    Given The file "test.txt" does not exist
    And I use the last but "one" Backup Version as local shell argument "--selected-backup"
    And I execute "./flow backup:restore %s %s" on local shell
    Then I should see the Feedback "Restored"
    And The file "test.txt" contains "an existing file updated"