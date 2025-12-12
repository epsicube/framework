<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Filament;

use Closure;
use Filament\Forms\Components\Repeater;

class CustomRepeater extends Repeater
{
    protected function setUp(): void
    {
        parent::setUp();

        // Fix simple hydration not work, when state was set dynamically
        $initial = $this->afterStateHydrated;
        $this->afterStateHydrated(static function (Repeater $component, ?array $rawState) use (&$initial): void {
            $component->hydratedDefaultState = null;
            $component->evaluate($initial, ['rawState' => $rawState]);
        });
    }

    public function uniqueItems(bool|Closure $condition = true): static
    {
        $this->rule(static function (self $component) {
            return function (string $attribute, mixed $value, Closure $fail) use ($component) {
                if ($simpleField = $component->getSimpleField()) {
                    $values = collect($component->getRawState() ?? [])
                        ->values()
                        ->pluck($simpleField->getName())
                        ->all();
                } else {
                    $values = array_values($component->getRawState() ?? []);
                }

                $duplicates = collect($values)->duplicates()->all();

                if (! empty($duplicates)) {
                    return $fail(__('Each item must be unique. Duplicates detected: :duplicates', [
                        'duplicates' => implode(', ', array_map(
                            fn (mixed $item) => is_scalar($item) ? $item : json_encode($item),
                            $duplicates
                        )),
                    ]));
                }

                return null;
            };
        }, $condition);

        return $this;
    }
}
