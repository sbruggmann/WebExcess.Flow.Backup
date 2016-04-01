# WebExcess.Flow.Backup

**An incremental & encrypted Backup Package for Neos & Flow Framework**

Note: This package is still experimental and not for production and I'm happy about every inputs!

Important
---------

**Test it tough!** And test it on the stage of your final environment.

The backup files are encrypted. If you lose the keyfile, your backup is worthless.


Installation
------------

```
composer require webexcess/flow-backup
```

Quick start
-----------

First, create an encryption keyfile:

```
./flow backup:key
```

Create a Backup:

```
./flow backup:now
```

List available Backups:

```
./flow backup:list
```

Restore a Backup:

```
./flow backup:restore
```

Remove all Backups:

```
./flow backup:clear
```

Configuration
-------------

If you really have to backup more than the Database and the Persistent Files, feel free to add more directories. 

Settings.yaml

```
WebExcess:
  Flow:
    Backup:
      HistoryLimit: 30
      Folders:
        Sources:
          - %FLOW_PATH_DATA%Persistent/
          # - %FLOW_PATH_ROOT%Configuration/
          # - %FLOW_PATH_PACKAGES%Plugins/
          # - %FLOW_PATH_PACKAGES%Framework/
          # - %FLOW_PATH_PACKAGES%Libraries/
        LocalTarget: %FLOW_PATH_DATA%Backup/
```

Signals Reference
-----------------

- BackupStarted *()*
- BackupFinished *(OutputInterface $output, array $stats)*
- RestoreStarted *($versionToRestore)*
- RestoreAborted *(OutputInterface $output)*
- RestoreFinished *(OutputInterface $output, array $stats)*
- BackupVersionsRemoved *(OutputInterface $output, array $removedVersions)*
