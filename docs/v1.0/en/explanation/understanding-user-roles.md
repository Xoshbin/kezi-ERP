---
title: Understanding User Roles
icon: heroicon-o-shield-check
---

# Understanding User Roles

Kezi ERP uses a role-based access control (RBAC) system to manage what users can see and do. This ensures that employees have access only to the information effectively needed for their work.

## What is a Role?

A **Role** is a collection of permissions. Instead of assigning permissions to each user individually, you assign permissions to a role, and then assign the role to users.

Example Roles:
*   **Administrator**: Full access to all settings and modules.
*   **Sales Manager**: Can approve sales orders and view sales reports.
*   **Inventory User**: Can create stock moves but cannot change inventory settings.

## Permissions Structure

Permissions are usually categorized by Module and Action:
*   **Read**: View records.
*   **Users**: Create or Edit records.
*   **Delete**: Remove records (usually restricted).
*   **Approve**: Validating documents like Bills or Leave Requests.

## Hierarchy

In some modules, roles function in a hierarchy:
*   **User**: Can see their own documents.
*   **Manager / All Documents**: Can see documents created by everyone.
*   **Administrator**: Can configure the module itself.

## Best Practices

*   **Least Privilege**: Give users the slightly level of access they need.
*   **Use Groups**: Assign roles to groups, then add users to groups for easier management.
*   **Review Regularly**: Periodically check who has Administrator access.

## See Also

- [Managing Users](../how-to/manage-users.md)
- [System Settings Reference](../reference/system-settings-reference.md)
