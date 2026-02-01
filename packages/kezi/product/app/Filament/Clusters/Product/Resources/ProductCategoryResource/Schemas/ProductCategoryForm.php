<?php

namespace Kezi\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Product\Models\ProductCategory;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('parent_id')
                    ->label(__('Parent Category'))
                    ->relationship('parent', 'name', modifyQueryUsing: function (Builder $query, ?ProductCategory $record) {
                        if ($record) {
                            $query->where('id', '!=', $record->id);
                        }
                    })
                    ->searchable()
                    ->preload(),
            ]);
    }
}
