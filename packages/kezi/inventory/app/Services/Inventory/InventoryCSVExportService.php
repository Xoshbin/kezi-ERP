<?php

namespace Kezi\Inventory\Services\Inventory;

use BackedEnum;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Filament\Facades\Filament;

class InventoryCSVExportService
{
    /**
     * Export inventory valuation report to CSV format
     */
    public function exportValuationReport(array $data, array $options = []): string
    {
        $includeMetadata = $options['include_metadata'] ?? false;
        $csv = '';

        // Add metadata if requested
        if ($includeMetadata) {
            $csv .= $this->generateMetadata('Inventory Valuation Report', $data);
        }

        // Add header
        $csv .= "Product Name,SKU,Quantity,Unit Cost,Total Value,Valuation Method\n";

        // Add data rows from by_product array
        if (isset($data['by_product']) && is_array($data['by_product'])) {
            foreach ($data['by_product'] as $productData) {
                $unitCost = $productData['quantity'] > 0
                    ? $productData['value']->dividedBy($productData['quantity'], RoundingMode::HALF_UP)
                    : Money::of(0, $productData['value']->getCurrency());

                $valuationMethod = $productData['valuation_method'] ?? 'N/A';
                if ($valuationMethod instanceof BackedEnum) {
                    $valuationMethod = $valuationMethod->value;
                } elseif (is_object($valuationMethod) && method_exists($valuationMethod, 'value')) {
                    $valuationMethod = $valuationMethod->value;
                }

                $csv .= sprintf(
                    '"%s","%s",%s,%s,%s,"%s"'."\n",
                    $this->escapeCsvValue($productData['product_name'] ?? ''),
                    $this->escapeCsvValue($productData['product_sku'] ?? $productData['sku'] ?? ''),
                    number_format($productData['quantity'], 4),
                    $this->formatMoney($unitCost),
                    $this->formatMoney($productData['value']),
                    $valuationMethod
                );
            }
        }

        return $csv;
    }

    /**
     * Export inventory aging report to CSV format
     */
    public function exportAgingReport(array $data, array $options = []): string
    {
        $includeMetadata = $options['include_metadata'] ?? false;
        $csv = '';

        // Add metadata if requested
        if ($includeMetadata) {
            $csv .= $this->generateMetadata('Inventory Aging Report', $data);
        }

        // Add header for bucket summary
        $csv .= "Age Bucket,Quantity,Value\n";

        // Add bucket data
        if (isset($data['buckets']) && is_array($data['buckets'])) {
            foreach ($data['buckets'] as $bucketLabel => $bucketData) {
                $csv .= sprintf(
                    '"%s",%s,%s'."\n",
                    $this->escapeCsvValue($bucketLabel),
                    number_format($bucketData['quantity'], 4),
                    $this->formatMoney($bucketData['value'])
                );
            }
        }

        // Add summary row
        $csv .= "\nSummary\n";
        $csv .= sprintf(
            '"Total",%s,%s'."\n",
            number_format($data['total_quantity'] ?? 0, 4),
            $this->formatMoney($data['total_value'] ?? Money::of(0, 'IQD'))
        );

        return $csv;
    }

    /**
     * Export inventory turnover report to CSV format
     */
    public function exportTurnoverReport(array $data, array $options = []): string
    {
        $includeMetadata = $options['include_metadata'] ?? false;
        $csv = '';

        // Add metadata if requested
        if ($includeMetadata) {
            $csv .= $this->generateMetadata('Inventory Turnover Report', $data);
        }

        // Add header for summary metrics
        $csv .= "Metric,Value\n";

        // Add turnover metrics
        $csv .= sprintf(
            '"Period Start","%s"'."\n",
            $data['period_start']->format('Y-m-d')
        );
        $csv .= sprintf(
            '"Period End","%s"'."\n",
            $data['period_end']->format('Y-m-d')
        );
        $csv .= sprintf(
            '"Total COGS",%s'."\n",
            $this->formatMoney($data['total_cogs'])
        );
        $csv .= sprintf(
            '"Average Inventory Value",%s'."\n",
            $this->formatMoney($data['average_inventory_value'])
        );
        $csv .= sprintf(
            '"Inventory Turnover Ratio",%s'."\n",
            number_format($data['inventory_turnover_ratio'], 2)
        );
        $csv .= sprintf(
            '"Days Sales Inventory",%s'."\n",
            number_format($data['days_sales_inventory'], 0)
        );

        return $csv;
    }

    /**
     * Export reorder status report to CSV format
     */
    public function exportReorderStatusReport(array $data, array $options = []): string
    {
        $includeMetadata = $options['include_metadata'] ?? false;
        $csv = '';

        // Add metadata if requested
        if ($includeMetadata) {
            $csv .= $this->generateMetadata('Reorder Status Report', $data);
        }

        // Add header
        $csv .= "Product Name,Location,Current Stock,Reserved,Available,Min Quantity,Max Quantity,Safety Stock,Suggested Quantity,Priority\n";

        // Add data rows from below_minimum array
        if (isset($data['below_minimum']) && is_array($data['below_minimum'])) {
            foreach ($data['below_minimum'] as $item) {
                $csv .= sprintf(
                    '"%s","%s",%s,%s,%s,%s,%s,%s,%s,"%s"'."\n",
                    $this->escapeCsvValue($item['product_name'] ?? ''),
                    $this->escapeCsvValue($item['location_name'] ?? ''),
                    number_format($item['current_qty'], 4),
                    number_format($item['reserved_qty'], 4),
                    number_format($item['available_qty'], 4),
                    number_format($item['min_qty'], 4),
                    number_format($item['max_qty'], 4),
                    number_format($item['safety_stock'] ?? 0, 4),
                    number_format($item['suggested_qty'], 4),
                    $this->escapeCsvValue($item['priority'] ?? 'Normal')
                );
            }
        }

        // Add summary section
        if (isset($data['summary'])) {
            $csv .= "\nSummary\n";
            $csv .= "Metric,Value\n";
            $csv .= sprintf(
                '"Total On Hand",%s'."\n",
                number_format($data['summary']['total_on_hand'], 4)
            );
            $csv .= sprintf(
                '"Total Reserved",%s'."\n",
                number_format($data['summary']['total_reserved'], 4)
            );
            $csv .= sprintf(
                '"Total Available",%s'."\n",
                number_format($data['summary']['total_available'], 4)
            );
            $csv .= sprintf(
                '"Reorder Warnings",%d'."\n",
                $data['summary']['reorder_warnings_count']
            );
        }

        return $csv;
    }

    /**
     * Export lot traceability report to CSV format
     */
    public function exportLotTraceabilityReport(array $data, array $options = []): string
    {
        $includeMetadata = $options['include_metadata'] ?? false;
        $csv = '';

        // Add metadata if requested
        if ($includeMetadata) {
            $csv .= $this->generateMetadata('Lot Traceability Report', $data);
        }

        // Add lot summary
        $csv .= "Lot Information\n";
        $csv .= "Field,Value\n";
        $csv .= sprintf('"Lot Code","%s"'."\n", $this->escapeCsvValue($data['lot_code'] ?? ''));
        $csv .= sprintf('"Product","%s"'."\n", $this->escapeCsvValue($data['product_name'] ?? ''));
        $csv .= sprintf(
            '"Expiration Date","%s"'."\n",
            isset($data['expiration_date']) ? $data['expiration_date']->format('Y-m-d') : 'N/A'
        );
        $csv .= sprintf('"Current Quantity",%s'."\n", number_format($data['current_quantity'] ?? 0, 4));
        $csv .= sprintf('"Total Value",%s'."\n", $this->formatMoney($data['total_value'] ?? Money::of(0, 'IQD')));

        // Add movements section
        $csv .= "\nMovement History\n";
        $csv .= "Date,Type,Quantity,From Location,To Location,Reference\n";

        if (isset($data['movements']) && is_array($data['movements'])) {
            foreach ($data['movements'] as $movement) {
                $csv .= sprintf(
                    '"%s","%s",%s,"%s","%s","%s"'."\n",
                    $movement['move_date']->format('Y-m-d'),
                    $this->escapeCsvValue($movement['move_type'] ?? ''),
                    number_format($movement['quantity'] ?? 0, 4),
                    $this->escapeCsvValue($movement['from_location'] ?? ''),
                    $this->escapeCsvValue($movement['to_location'] ?? ''),
                    $this->escapeCsvValue($movement['reference'] ?? '')
                );
            }
        }

        return $csv;
    }

    /**
     * Generate metadata section for CSV exports
     */
    private function generateMetadata(string $reportTitle, array $data): string
    {
        $metadata = '';
        $metadata .= $reportTitle."\n";
        $metadata .= 'Generated on: '.Carbon::now()->format('Y-m-d H:i:s')."\n";

        // Try to get company name from current tenant
        try {
            $company = Filament::getTenant();
            if ($company && method_exists($company, 'getAttribute')) {
                $metadata .= 'Company: '.$company->name."\n";
            }
        } catch (Exception $e) {
            // Fallback if no tenant context
            $metadata .= 'Company: '.(config('app.name') ?? 'Unknown')."\n";
        }

        // Add report-specific metadata
        if (isset($data['as_of_date'])) {
            $metadata .= 'As of Date: '.$data['as_of_date']->format('Y-m-d')."\n";
        }
        if (isset($data['period_start']) && isset($data['period_end'])) {
            $metadata .= 'Period: '.$data['period_start']->format('Y-m-d').' to '.$data['period_end']->format('Y-m-d')."\n";
        }
        if (isset($data['currency'])) {
            $metadata .= 'Currency: '.$data['currency']."\n";
        }

        $metadata .= "\n";

        return $metadata;
    }

    /**
     * Escape CSV values to handle quotes and special characters
     */
    private function escapeCsvValue(string $value): string
    {
        return str_replace('"', '""', $value);
    }

    /**
     * Format Money object for CSV output
     */
    private function formatMoney(Money $money): string
    {
        // Convert to float and format with 2 decimal places, no thousands separator
        $amount = $money->getAmount()->toFloat();

        return number_format($amount, 2, '.', '');
    }
}
