# AT Loops OS — Backup, Retention & Recovery Protocol

## 1. Backup Strategy Overview

AT Loops OS includes standalone, CLI-only backup and restoration workers located in `scripts/`:
- **`scripts/backup.php`**: Generates full schema and data SQL dumps into `storage/backups/`. Automatically enforces a 30-day retention pruning policy.
- **`scripts/restore.php`**: Restores SQL backups into target databases with mandatory safety interlocks preventing accidental destruction of production data.

---

## 2. Automated Daily Backups

### Execution Command
```bash
php scripts/backup.php --database=mysql
```

### Execution Mechanics
1. Inspects active database configuration for `mysql` connection.
2. Creates storage directory at `storage/backups/` if not present.
3. Queries `information_schema.tables` to locate all 37 application tables.
4. Generates an ACID-consistent snapshot:
   - Sets `FOREIGN_KEY_CHECKS = 0`.
   - Iterates through tables exporting `CREATE TABLE` DDL.
   - Streams row data in batches using prepared statements, formatting values into SQL `INSERT` statements with proper byte escaping.
   - Sets `FOREIGN_KEY_CHECKS = 1`.
5. Writes compressed `.sql` dump named with format: `backup_{database}_{YYYYMMDD_HHMMSS}.sql`.
6. Enforces Retention Policy: Scans `storage/backups/` for backup files older than 30 days and removes them to conserve disk storage.

---

## 3. Safe Isolated Restoration Verification Protocol

To verify backup integrity on a staging or testing server, restore into the isolated `atloops_os_test` database:

```bash
php scripts/restore.php --file=storage/backups/backup_atloops_os_20261026_035555.sql --database=testing
```

The script will:
1. Target `atloops_os_test` exclusively.
2. Read and parse the SQL statements from the file.
3. Rebuild the schema and insert all rows.
4. Execute automated verification queries:
   - Check restored table count (must equal 37).
   - Check user count and vital foreign keys.
   - Print `[SUCCESS] Database Integrity: VERIFIED`.

---

## 4. Production Safeguards & Emergency Disaster Recovery

### Accidental Overwrite Safeguard
Running `scripts/restore.php` with `--database=mysql` without confirmation is **strictly blocked**:
```
[ABORTED] Restoring directly into production database 'atloops_os' is blocked.
To restore into an isolated test database for verification, use: --database=testing
To explicitly force restore into production, you must supply flag: --force-production-restore-confirmation
```

### Emergency Production Restoration Command
Only in a genuine disaster recovery scenario with documented authorization:
```bash
php scripts/restore.php --file=storage/backups/backup_atloops_os_TARGET.sql --database=mysql --force-production-restore-confirmation
```
