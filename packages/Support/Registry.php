<?php

declare(strict_types=1);

namespace Epsicube\Support;

use Closure;
use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;
use Epsicube\Support\Exceptions\DuplicateItemException;
use Epsicube\Support\Exceptions\UnexpectedItemTypeException;
use Epsicube\Support\Exceptions\UnresolvableItemException;

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
     * @var array<string, T>
     */
    protected array $items = [];

    /**
     * @param  T  ...$items
     */
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

    /**
     * @return T
     *
     * @throws UnresolvableItemException
     */
    public function get(string $identifier): Registrable
    {
        if (! array_key_exists($identifier, $this->all())) {
            throw new UnresolvableItemException($identifier, $this);
        }

        return $this->all()[$identifier];
    }

    /**
     * @return T|null
     */
    public function safeGet(string $identifier): ?Registrable
    {
        try {
            return $this->get($identifier);
        } catch (UnresolvableItemException $e) {
            return null;
        }
    }

    /**
     * @return array<string,T>
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
     * @param Closure($item T, $identifier string): void $callback
     */
    public function modifyItemsUsing(Closure $callback): void
    {
        $this->modifyItemsUsing[] = $callback;
    }

    /**
     * @return array<string,string> Key -> label
     */
    public function toIdentifierLabelMap(): array
    {
        return array_map(function (Registrable $r) {
            if (is_a($r, HasLabel::class)) {
                return $r->label();
            }

            return $r->identifier();
        }, $this->items);
    }

    protected function registerItem(string $identifier, Registrable $item): void
    {
        $this->items[$identifier] = $item;
    }
}
