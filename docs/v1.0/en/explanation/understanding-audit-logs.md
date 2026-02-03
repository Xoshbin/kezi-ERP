# Understanding Audit Logs

Audit Logs provide a complete trail of all actions taken in your accounting system. They are essential for compliance, security, and troubleshooting.

## Overview

Every action in Kezi is recorded with details about who did what, when, and from where. This immutable record ensures accountability and supports regulatory compliance.

## Key Information Captured

Each audit log entry records:

*   **User**: Who performed the action
*   **Action**: What was done (create, update, delete, post, reverse)
*   **Model**: The type of record affected (Invoice, Payment, Journal Entry)
*   **Record ID**: The specific record modified
*   **Changes**: Before and after values for updates
*   **Timestamp**: When the action occurred
*   **IP Address**: Where the request originated

## Viewing Audit Logs

1.  Navigate to **Accounting > Audit Logs**.
2.  Use filters to narrow results:
    *   Date range
    *   User
    *   Model type
    *   Action type
3.  Click on any entry to view full details including old and new values.

## Common Use Cases

### Compliance Auditing
Review all changes to financial records during a specific period for external auditors.

### Security Monitoring
Track unusual activity patterns such as off-hours access or bulk modifications.

### Troubleshooting
Trace the sequence of events that led to a data issue.

### User Training
Review actions to identify training opportunities.

## Data Integrity

Audit logs in Kezi:
*   **Cannot be edited**: Once recorded, entries are permanent
*   **Cannot be deleted**: System maintains complete history
*   **Include hash verification**: Tamper detection is built in

## Best Practices

1.  **Regular Review**: Check audit logs periodically for anomalies
2.  **Retention Policy**: Maintain logs according to your regulatory requirements
3.  **Access Control**: Limit who can view sensitive audit information
4.  **Export for Archival**: Back up logs for long-term storage

## Related Topics

*   [Lock Dates](lock-dates.md)
*   [User Management](employee-management.md)
