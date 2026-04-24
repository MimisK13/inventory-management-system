<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        cache()->forget('settings');
    }

    public function test_settings_returns_cached_object(): void
    {
        $settings = (object) [
            'default_currency_position' => 'prefix',
            'currency' => (object) [
                'symbol' => 'EUR ',
                'decimal_separator' => ',',
                'thousand_separator' => '.',
            ],
        ];

        cache()->put('settings', $settings, now()->addMinutes(10));

        $this->assertSame($settings, settings());
    }

    public function test_format_currency_returns_raw_value_when_formatting_is_disabled(): void
    {
        $this->assertSame(1234.5, format_currency(1234.5, false));
    }

    public function test_format_currency_supports_prefix_position(): void
    {
        $settings = (object) [
            'default_currency_position' => 'prefix',
            'currency' => (object) [
                'symbol' => 'EUR ',
                'decimal_separator' => ',',
                'thousand_separator' => '.',
            ],
        ];

        cache()->put('settings', $settings, now()->addMinutes(10));

        $this->assertSame('EUR 1.234,50', format_currency(1234.5));
    }

    public function test_format_currency_supports_suffix_position(): void
    {
        $settings = (object) [
            'default_currency_position' => 'suffix',
            'currency' => (object) [
                'symbol' => ' EUR',
                'decimal_separator' => '.',
                'thousand_separator' => ',',
            ],
        ];

        cache()->put('settings', $settings, now()->addMinutes(10));

        $this->assertSame('1,234.50 EUR', format_currency(1234.5));
    }

    public function test_make_reference_id_generates_padded_identifier(): void
    {
        $this->assertSame('PO-00042', make_reference_id('PO', 42));
    }

    public function test_array_merge_numeric_values_sums_numeric_keys_and_skips_non_numeric_values(): void
    {
        $merged = array_merge_numeric_values(
            ['a' => 1, 'b' => 2, 'name' => 'ignore'],
            ['a' => 3, 'b' => '4', 'name' => 'skip'],
            ['c' => 10]
        );

        $this->assertSame(['a' => 4, 'b' => 6, 'c' => 10], $merged);
    }
}
