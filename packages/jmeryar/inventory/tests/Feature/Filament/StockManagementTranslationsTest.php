<?php

namespace Jmeryar\Inventory\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Enums\Inventory\ValuationMethod;
use Tests\TestCase;

class StockManagementTranslationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_english_translations_for_stock_location()
    {
        app()->setLocale('en');

        $this->assertEquals('Stock Location', __('inventory::stock_location.label'));
        $this->assertEquals('Stock Locations', __('inventory::stock_location.plural_label'));
        $this->assertEquals('Basic Information', __('inventory::stock_location.basic_information'));
        $this->assertEquals('Location Type', __('inventory::stock_location.type'));
    }

    /** @test */
    public function it_has_kurdish_translations_for_stock_location()
    {
        app()->setLocale('ckb');

        $this->assertEquals('شوێنی کۆگا', __('inventory::stock_location.label'));
        $this->assertEquals('شوێنەکانی کۆگا', __('inventory::stock_location.plural_label'));
        $this->assertEquals('زانیاری بنەڕەتی', __('inventory::stock_location.basic_information'));
        $this->assertEquals('جۆری شوێن', __('inventory::stock_location.type'));
    }

    /** @test */
    public function it_has_english_translations_for_stock_move()
    {
        app()->setLocale('en');

        $this->assertEquals('Stock Movement', __('inventory::stock_move.label'));
        $this->assertEquals('Stock Movements', __('inventory::stock_move.plural_label'));
        $this->assertEquals('Movement Details', __('inventory::stock_move.movement_details'));
        $this->assertEquals('Quantity', __('inventory::stock_move.quantity'));
    }

    /** @test */
    public function it_has_kurdish_translations_for_stock_move()
    {
        app()->setLocale('ckb');

        $this->assertEquals('جووڵەی کۆگا', __('inventory::stock_move.label'));
        $this->assertEquals('جووڵەکانی کۆگا', __('inventory::stock_move.plural_label'));
        $this->assertEquals('وردەکارییەکانی جووڵە', __('inventory::stock_move.movement_details'));
        $this->assertEquals('بڕ', __('inventory::stock_move.quantity'));
    }

    /** @test */
    public function it_has_english_translations_for_inventory_cost_layer()
    {
        app()->setLocale('en');

        $this->assertEquals('Cost Layer', __('inventory::inventory_cost_layer.label'));
        $this->assertEquals('Cost Layers', __('inventory::inventory_cost_layer.plural_label'));
        $this->assertEquals('Purchase Date', __('inventory::inventory_cost_layer.purchase_date'));
        $this->assertEquals('Remaining Quantity', __('inventory::inventory_cost_layer.remaining_quantity'));
    }

    /** @test */
    public function it_has_kurdish_translations_for_inventory_cost_layer()
    {
        app()->setLocale('ckb');

        $this->assertEquals('چینی تێچوو', __('inventory::inventory_cost_layer.label'));
        $this->assertEquals('چینەکانی تێچوو', __('inventory::inventory_cost_layer.plural_label'));
        $this->assertEquals('بەرواری کڕین', __('inventory::inventory_cost_layer.purchase_date'));
        $this->assertEquals('بڕی ماوە', __('inventory::inventory_cost_layer.remaining_quantity'));
    }

    /** @test */
    public function it_has_english_translations_for_product_inventory_fields()
    {
        app()->setLocale('en');

        $this->assertEquals('Inventory Management', __('product.inventory_management'));
        $this->assertEquals('Valuation Method', __('product.inventory_valuation_method'));
        $this->assertEquals('Average Cost', __('product.average_cost'));
        $this->assertEquals('Stock Movements', __('product.stock_moves'));
    }

    /** @test */
    public function it_has_kurdish_translations_for_product_inventory_fields()
    {
        app()->setLocale('ckb');

        $this->assertEquals('بەڕێوەبردنی کۆگا', __('product.inventory_management'));
        $this->assertEquals('شێوازی پێوانی', __('product.inventory_valuation_method'));
        $this->assertEquals('تێچووی ناوەند', __('product.average_cost'));
        $this->assertEquals('جووڵەی کۆگا', __('product.stock_moves'));
    }

    /** @test */
    public function it_has_navigation_group_translations()
    {
        app()->setLocale('en');
        $this->assertEquals('Inventory Management', __('navigation.groups.inventory'));

        app()->setLocale('ckb');
        $this->assertEquals('بەڕێوەبردنی کۆگا', __('navigation.groups.inventory'));
    }

    /** @test */
    public function enum_labels_work_correctly()
    {
        app()->setLocale('en');

        $this->assertEquals('First In, First Out (FIFO)', ValuationMethod::FIFO->label());
        $this->assertEquals('Internal', StockLocationType::Internal->label());
        $this->assertEquals('Incoming', StockMoveType::Incoming->label());
        $this->assertEquals('Draft', StockMoveStatus::Draft->label());

        app()->setLocale('ckb');

        $this->assertEquals('یەکەم هات، یەکەم چوو (FIFO)', ValuationMethod::FIFO->label());
        $this->assertEquals('ناوخۆیی', StockLocationType::Internal->label());
        $this->assertEquals('هاتووە ژوورەوە', StockMoveType::Incoming->label());
        $this->assertEquals('ڕەشنووس', StockMoveStatus::Draft->label());
    }
}
