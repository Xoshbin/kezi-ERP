<?php

return [
    'cheque' => [
        'receivable_only' => 'ReceiveChequeAction is only for receivable cheques.',
        'must_be_active_to_bounce' => 'Cheque must be active (Handed Over/Deposited) to bounce.',
        'receivable_only_for_deposit' => 'Only receivable cheques can be deposited.',
        'must_be_draft_to_deposit' => 'Cheque must be in Draft status to be deposited.',
        'payable_only_for_issue' => 'IssueChequeAction is only for payable cheques.',
        'must_be_active_to_clear' => 'Cheque must be Handed Over or Deposited to be cleared.',
        'draft_only_for_cancel' => 'Only draft cheques can be cancelled. Use Void or Bounce for processed cheques.',
        'payable_only_for_handover' => 'Only payable cheques can be handed over.',
        'must_be_draft_or_printed_for_handover' => 'Cheque must be in Draft or Printed status to be handed over.',
    ],
    'lc' => [
        'invalid_status_or_expired' => 'LC cannot be utilized in current status or is expired.',
        'utilization_exceeds_balance' => 'Utilization amount exceeds LC balance.',
        'draft_only_for_issue' => 'Only draft LCs can be issued.',
        'config_missing' => 'Default bank journal or bank account not configured for the company.',
        'cancel_condition' => 'LC can only be cancelled if it is draft or issued without utilization.',
        'cannot_cancel_utilized' => 'Cannot cancel LC that has been utilized.',
    ],
    'payment' => [
        'draft_only_for_update' => 'Only draft payments can be updated.',
        'settlement_needs_documents' => 'Settlement payments must be linked to at least one document.',
        'non_partner_needs_partner' => 'Payments without document links must specify a partner.',
        'mixed_documents_not_allowed' => 'A payment cannot be linked to both invoices and vendor bills simultaneously.',
        'refresh_failed' => 'Failed to refresh payment after update.',
    ],
    'petty_cash' => [
        'zero_balance_required_for_close' => 'Cannot close petty cash fund with non-zero balance.',
        'closed_fund_not_replenishable' => 'Cannot replenish a closed fund.',
        'closed_fund_not_postable' => 'Cannot post voucher for a closed fund.',
        'insufficient_balance' => 'Insufficient petty cash balance.',
        'cash_journal_missing' => 'No cash journal found for company. Please configure a default cash journal.',
    ],
];
