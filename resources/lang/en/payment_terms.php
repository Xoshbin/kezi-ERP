<?php

return [
    'immediate_payment' => 'Immediate Payment',
    'net_days' => 'Net :days days',
    'installment_description' => ':percentage% in :days days',
    'immediate' => 'Immediate',
    'end_of_month' => 'End of Month',
    'end_of_month_plus_days' => 'End of Month + :days days',
    'day_of_month' => ':day of month + :days days',
    'with_discount' => '(:percentage% discount if paid within :days days)',

    'types' => [
        'net' => 'Net Days',
        'end_of_month' => 'End of Month',
        'day_of_month' => 'Day of Month',
        'immediate' => 'Immediate',
        'net_description' => 'Payment due after specified number of days from document date',
        'end_of_month_description' => 'Payment due at end of month plus additional days',
        'day_of_month_description' => 'Payment due on specific day of month',
        'immediate_description' => 'Payment due immediately upon receipt',
    ],

    'common' => [
        'immediate' => 'Immediate Payment',
        'net_15' => 'Net 15',
        'net_30' => 'Net 30',
        'net_60' => 'Net 60',
        'eom' => 'End of Month',
        'eom_plus_30' => 'End of Month + 30',
    ],

    'fields' => [
        'name' => 'Payment Term Name',
        'description' => 'Description',
        'is_active' => 'Active',
        'lines' => 'Payment Term Lines',
        'sequence' => 'Sequence',
        'type' => 'Type',
        'days' => 'Days',
        'percentage' => 'Percentage',
        'day_of_month' => 'Day of Month',
        'discount_percentage' => 'Discount %',
        'discount_days' => 'Discount Days',
    ],

    'actions' => [
        'create' => 'Create Payment Term',
        'edit' => 'Edit Payment Term',
        'delete' => 'Delete Payment Term',
        'add_line' => 'Add Line',
        'remove_line' => 'Remove Line',
    ],

    'messages' => [
        'created' => 'Payment term created successfully.',
        'updated' => 'Payment term updated successfully.',
        'deleted' => 'Payment term deleted successfully.',
        'cannot_delete_in_use' => 'Cannot delete payment term that is in use.',
    ],

    'validation' => [
        'name_required' => 'Payment term name is required.',
        'percentage_sum' => 'Total percentage must equal 100%.',
        'percentage_positive' => 'Percentage must be positive.',
        'days_required' => 'Days is required for this type.',
        'day_of_month_required' => 'Day of month is required for this type.',
        'day_of_month_range' => 'Day of month must be between 1 and 31.',
    ],
];
