<?php

declare(strict_types=1);

namespace UniGale\Foundation\Concerns;

use Closure;
use UniGale\Foundation\Contracts\HasLabel;
use UniGale\Foundation\Contracts\Registrable;
use UniGale\Foundation\Exceptions\DuplicateItemException;
use UniGale\Foundation\Exceptions\UnexpectedItemTypeException;
use UniGale\Foundation\Exceptions\UnresolvableItemException;

/**
 * @template T of Registrable
 */
abstract class Registry
{
    protected array $modifyItemsUsing = [];

    /**
     * @return class-string<T>
     */
    abstract public function getRegistrableType(): string;

    /**
     * @var Registrable
     */
    protected array $items = [];

    public function register(Registrable ...$items): void
    {
        foreach ($items as $item) {

            $expectedType = $this->getRegistrableType();
            $identifier = $item->identifier();
            if (! is_a($item, $expectedType)) {
                throw new UnexpectedItemTypeException($identifier, $this);
            }

            if (array_key_exists($identifier, $this->items)) {
                throw new DuplicateItemException($identifier, $this);
            }

            $this->registerItem($identifier, $item);
        }
    }

    public function get(string $identifier): Registrable
    {
        if (! array_key_exists($identifier, $this->all())) {
            throw new UnresolvableItemException($identifier, $this);
        }

        return $this->all()[$identifier];
    }

    public function safeGet(string $identifier): ?Registrable
    {
        try {
            return $this->get($identifier);
        } catch (UnresolvableItemException $e) {
            return null;
        }
    }

    /**
     * @return Registrable
     */
    public function all(): array
    {
        $modules = $this->items;
        foreach ($this->modifyItemsUsing as $callback) {
            $modules = $callback($modules);
        }

        return $modules;
    }

    /**
     * @return array<string,string>
     */
    public function toIdentifierLabelMap(): array
    {
        return array_map(function (Registrable $item) {
            if ($item instanceof HasLabel) {
                return $item->label();
            }

            return $item->identifier();
        }, $this->all());
    }

    protected function registerItem(string $identifier, Registrable $item): void
    {
        $this->items[$identifier] = $item;
    }

    /**
     * @param Closure($item T, $identifier string): void $callback
     */
    public function modifyItemsUsing(Closure $callback): void
    {
        $this->modifyItemsUsing[] = $callback;
    }
}
