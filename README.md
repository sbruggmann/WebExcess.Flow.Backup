# WebExcess.Flow.Backup

A Neos &amp; Flow Framework **Incremental Backup Package** for Database &amp; Persistent Files.

Note: This package is still experimental and not for production.

Important: The backup files are encrypted. If you lose the keyfile, your backup is worthless.

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
