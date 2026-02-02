<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\RelationManagers;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kezi\Manufacturing\Enums\BOMType;
use Kezi\Manufacturing\Models\BillOfMaterial;

class StructureRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static string|BackedEnum|null $icon = 'heroicon-o-view-columns';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('manufacturing::manufacturing.bom.structure');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('manufacturing::manufacturing.bom.component'))
                    ->formatStateUsing(function ($state, $record) {
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('manufacturing::manufacturing.bom.qty'))
                    ->numeric(decimalPlaces: 4),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label(__('manufacturing::manufacturing.bom.unit_cost'))
                    ->money(fn ($record) => $record->currency_code ?? 'USD'),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label(__('manufacturing::manufacturing.lines.total_cost'))
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->getStateUsing(fn ($record) => (float) $record->quantity * (float) ($record->unit_cost->getAmount()->toFloat())),
            ])
            ->paginated(false)
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->contentFooter(function (BillOfMaterial $ownerRecord) {
                $total = $this->calculateTotalCost($ownerRecord);
                $currency = $ownerRecord->company->currency->code ?? 'USD';

                return view('manufacturing::bom-structure-footer', [
                    'total' => $total,
                    'currency' => $currency,
                    'label' => __('manufacturing::manufacturing.bom.total_rollup_cost'),
                ]);
            });
    }

    private function calculateTotalCost(BillOfMaterial $bom, float $parentQty = 1.0, int $depth = 1): float
    {
        if ($depth > 10) {
            return 0;
        }

        $total = 0;
        foreach ($bom->lines as $line) {
            $lineQty = (float) $line->quantity * $parentQty;

            $subBom = BillOfMaterial::where('product_id', $line->product_id)
                ->where('is_active', true)
                ->first();

            if ($subBom && in_array($subBom->type, [BOMType::Kit, BOMType::Phantom])) {
                $total += $this->calculateTotalCost($subBom, $lineQty, $depth + 1);
            } else {
                $total += $lineQty * (float) ($line->unit_cost->getAmount()->toFloat());
            }
        }

        return $total;
    }
}
