<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

/**
 * Guards the subtheme validation rule used by the admin abstract update
 * (App\Http\Controllers\Admin\AbstractManagementController::update).
 *
 * Several official subtheme names contain commas. Laravel's string form
 * `in:a,b,c` splits on commas, so building the rule with implode(',', ...)
 * silently breaks those values and yields "The selected subtheme is invalid".
 * The fix uses Rule::in($subthemes) (array form), which never parses commas.
 */
class AbstractSubthemeValidationRuleTest extends TestCase
{
    private function subthemes(): array
    {
        return array_keys(config('conference.subtheme_prefixes', []));
    }

    private function passesWithArrayRule(string $subtheme): bool
    {
        return Validator::make(
            ['subtheme' => $subtheme],
            ['subtheme' => ['required', 'string', Rule::in($this->subthemes())]]
        )->passes();
    }

    private function passesWithLegacyStringRule(string $subtheme): bool
    {
        // The old, buggy construction.
        return Validator::make(
            ['subtheme' => $subtheme],
            ['subtheme' => 'required|string|in:' . implode(',', $this->subthemes())]
        )->passes();
    }

    public function test_every_configured_subtheme_passes_the_array_rule(): void
    {
        $subthemes = $this->subthemes();
        $this->assertNotEmpty($subthemes, 'Expected configured subthemes.');

        foreach ($subthemes as $subtheme) {
            $this->assertTrue(
                $this->passesWithArrayRule($subtheme),
                "Subtheme should be accepted by the fixed rule: {$subtheme}"
            );
        }
    }

    public function test_comma_containing_subthemes_exist_and_pass(): void
    {
        $commaSubthemes = array_values(array_filter(
            $this->subthemes(),
            fn (string $s) => str_contains($s, ',')
        ));

        $this->assertNotEmpty(
            $commaSubthemes,
            'Expected at least one comma-containing subtheme — this is the case the fix protects.'
        );

        foreach ($commaSubthemes as $subtheme) {
            $this->assertTrue(
                $this->passesWithArrayRule($subtheme),
                "Comma-containing subtheme must be accepted: {$subtheme}"
            );
        }
    }

    public function test_legacy_string_rule_demonstrates_the_original_bug(): void
    {
        // Confirms the regression we fixed: the old implode-based rule
        // wrongly rejected every comma-containing subtheme.
        $commaSubthemes = array_values(array_filter(
            $this->subthemes(),
            fn (string $s) => str_contains($s, ',')
        ));

        $this->assertNotEmpty($commaSubthemes);

        foreach ($commaSubthemes as $subtheme) {
            $this->assertFalse(
                $this->passesWithLegacyStringRule($subtheme),
                "Legacy rule was expected to (incorrectly) reject: {$subtheme}"
            );
        }
    }

    public function test_unknown_subtheme_is_still_rejected_by_array_rule(): void
    {
        $this->assertFalse(
            $this->passesWithArrayRule('Totally Made Up Subtheme'),
            'The fix must not make the rule permissive — unknown values stay invalid.'
        );
    }
}
