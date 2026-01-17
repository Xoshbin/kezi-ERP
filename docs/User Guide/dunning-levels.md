# Dunning Levels

Dunning Levels help you automate the debt collection process by defining stages of overdue payments and the actions to take at each stage. This feature allows you to send reminders, warnings, and apply penalties systematically.

## Overview

The Dunning process consists of multiple levels, each triggered when an invoice is overdue by a specific number of days.
Common use cases include:
- Sending a polite reminder 5 days after the due date.
- Sending a warning letter 15 days overdue.
- Applying a late fee and sending a legal notice 30 days overdue.

## Accessing Dunning Levels
To manage Dunning Levels, navigate to **Accounting > Configuration > Dunning Levels**.

## Configuring a Dunning Level

When creating or editing a Dunning Level, you can configure the following:

### General Information
- **Name**: A descriptive name for the level (e.g., "First Reminder", "Legal Notice").
- **Days Overdue**: The number of days after the invoice due date when this level triggers.
- **Send Email**: Automatically send an email to the customer when this level is reached.
- **Print Letter**: Flag this level to generate a PDF letter for printing.

### Late Fee Configuration
You can automatically apply late fees to overdue invoices:
- **Charge Fee**: Enable this to apply a fee.
- **Fee Product**: Select the service product representing the late fee (e.g., "Late Payment Fee").
- **Fee Amount**: A fixed amount to charge (e.g., $50).
- **Fee Percentage**: A percentage of the overdue amount to charge (e.g., 2%).

### Email Configuration
If **Send Email** is enabled, customize the message:
- **Email Subject**: Subject line of the email.
- **Email Body**: Content of the email. You can use placeholders if supported by the system (check with your administrator).

## How it Works
1.  **Monitoring**: The system monitors unpaid invoices daily.
2.  **Triggering**: When an invoice exceeds the "Days Overdue" for a level, it is flagged.
3.  **Action**: Depending on configuration, an email is queued/sent, or the invoice is listed for letter printing. Late fees are added if configured.
4.  **Progression**: Invoices move through levels sequentially as they remain unpaid.

## Best Practices
- **Start Polite**: Level 1 should be a gentle reminder.
- **Escalate Gradually**: Increase the firmness of the language and potential penalties in higher levels.
- **Clarity**: Ensure your email bodies clearly state the invoice number, amount due, and how to pay.
