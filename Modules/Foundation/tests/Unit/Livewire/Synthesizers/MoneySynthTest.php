<?php

namespace Modules\Foundation\Tests\Unit\Livewire\Synthesizers;

use App\Livewire\Synthesizers\MoneySynth;
use Brick\Money\Money;
use PHPUnit\Framework\TestCase;

class MoneySynthTest extends TestCase
{
    private \Modules\Foundation\App\Livewire\Synthesizers\MoneySynth $synthesizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synthesizer = new \Modules\Foundation\App\Livewire\Synthesizers\MoneySynth();
    }

    /** @test */
    public function it_matches_money_objects(): void
    {
        $money = Money::of(100, 'USD');
        
        $this->assertTrue(\Modules\Foundation\App\Livewire\Synthesizers\MoneySynth::match($money));
        $this->assertFalse(\Modules\Foundation\App\Livewire\Synthesizers\MoneySynth::match('100'));
        $this->assertFalse(\Modules\Foundation\App\Livewire\Synthesizers\MoneySynth::match(100));
        $this->assertFalse(\Modules\Foundation\App\Livewire\Synthesizers\MoneySynth::match([]));
    }

    /** @test */
    public function it_dehydrates_money_objects_correctly(): void
    {
        $money = Money::of('123.45', 'EUR');
        
        [$payload, $meta] = $this->synthesizer->dehydrate($money);
        
        $this->assertIsArray($payload);
        $this->assertIsArray($meta);
        $this->assertEquals('123.45', $payload['amount']);
        $this->assertEquals('EUR', $payload['currency']);
        $this->assertEmpty($meta);
    }

    /** @test */
    public function it_hydrates_array_payloads_correctly(): void
    {
        $payload = [
            'amount' => '123.45',
            'currency' => 'EUR'
        ];
        
        $money = $this->synthesizer->hydrate($payload);
        
        $this->assertInstanceOf(Money::class, $money);
        $this->assertEquals('123.45', $money->getAmount()->__toString());
        $this->assertEquals('EUR', $money->getCurrency()->getCurrencyCode());
    }

    /** @test */
    public function it_returns_null_for_empty_array_payloads(): void
    {
        $this->assertNull($this->synthesizer->hydrate([]));
        $this->assertNull($this->synthesizer->hydrate(['amount' => '']));
        $this->assertNull($this->synthesizer->hydrate(['currency' => 'USD']));
        $this->assertNull($this->synthesizer->hydrate(['amount' => '', 'currency' => '']));
    }

    /** @test */
    public function it_returns_null_for_string_payloads(): void
    {
        // This is the key fix - string payloads should return null
        // and let MoneyInput component handle the conversion
        $this->assertNull($this->synthesizer->hydrate('1900'));
        $this->assertNull($this->synthesizer->hydrate('123.45'));
        $this->assertNull($this->synthesizer->hydrate(''));
    }

    /** @test */
    public function it_returns_null_for_other_payload_types(): void
    {
        $this->assertNull($this->synthesizer->hydrate(1900));
        $this->assertNull($this->synthesizer->hydrate(123.45));
        $this->assertNull($this->synthesizer->hydrate(null));
        $this->assertNull($this->synthesizer->hydrate(true));
    }

    /** @test */
    public function it_handles_round_trip_correctly(): void
    {
        $originalMoney = Money::of('999.99', 'GBP');
        
        // Dehydrate
        [$payload, $meta] = $this->synthesizer->dehydrate($originalMoney);
        
        // Hydrate
        $hydratedMoney = $this->synthesizer->hydrate($payload);
        
        $this->assertInstanceOf(Money::class, $hydratedMoney);
        $this->assertTrue($originalMoney->isEqualTo($hydratedMoney));
    }
}
