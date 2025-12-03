## General code instructions

- Don't generate code comments above the methods or code blocks if they are obvious. Don't add docblock comments when defining variables, unless instructed to, like `/** @var \App\Models\User $currentUser */`. Generate comments only for something that needs extra explanation for the reasons why that code was written.
- All the generated code should be in English, including variable names, phpdocs, documentation, etc, even if the prompt is given in a different language.

---

## PHP instructions

- In PHP, use `match` operator over `switch` whenever possible
- Use PHP 8 constructor property promotion. Don't create an empty Constructor method if it doesn't have any parameters.
- Using Services in Controllers: if Service class is used only in ONE method of Controller, inject it directly into that method with type-hinting. If Service class is used in MULTIPLE methods of Controller, initialize it in Constructor.
- Use return types in functions whenever possible, adding the full path to classname to the top in `use` section
- Generate Enums always in the folder `app/Enums`, not in the main `app/` folder, unless instructed differently.
- Import all classes with `use` and reference only their short names; no fully-qualified class names in code.
- Keep the condition structure simple, using return fist paradigm to avoid else or elseif and messy condition nesting.

---

## Laravel instructions

- For DB pivot tables, use correct alphabetical order, like "project_role" instead of "role_project"
- **Eloquent Observers** should be registered in Eloquent Models with PHP Attributes, and not in AppServiceProvider. Example: `#[ObservedBy([UserObserver::class])]` with `use Illuminate\Database\Eloquent\Attributes\ObservedBy;` on top
- When generating Controllers, put validation in Form Request classes and use `$request->validated()` instead of listing inputs one by one.
- Use Laravel helpers instead of `use` section classes whenever possible. Examples: use `auth()->id()` instead of `Auth::id()` and adding `Auth` in the `use` section. Another example: use `redirect()->route()` instead of `Redirect::route()`.
- Don't use `whereKey()` or `whereKeyNot()`, use specific fields like `id`. Example: instead of `->whereKeyNot($currentUser->getKey())`, use `->where('id', '!=', $currentUser->id)`.
- In Livewire projects, don't use Livewire Volt. Only Livewire class components.
- When adding columns in a migration, update the model’s `$fillable` array to include those new attributes.
- Prefer defining a static `$unguarded = true` property on Eloquent Model rather than populating the `$fillable` array.
- The model should also include generated PHPDoc annotations for all properties, use `CarbonImmutable` for all date fields when available, and apply `@property-read` to the primary key
- Enums: If a PHP Enum exists for a domain concept, always use its cases (or their `->value`) instead of raw strings everywhere — routes, middleware, migrations, seeds, configs, and UI defaults.

---

## Use Laravel 11+ skeleton structure

- **Service Providers**: there are no other service providers except AppServiceProvider. Don't create new service providers unless absolutely necessary. Use Laravel 11+ new features, instead. Or, if you really need to create a new service provider, register it in `bootstrap/providers.php` and not `config/app.php` like it used to be before Laravel 11.
- **Event Listeners**: since Laravel 11, Listeners auto-listen for the events if they are type-hinted correctly.
- **Console Scheduler**: scheduled commands should be in `routes/console.php` and not `app/Console/Kernel.php` which doesn't exist since Laravel 11.
- **Middleware**: whenever possible, use Middleware by class name in the routes. But if you do need to register Middleware alias, it should be registered in `bootstrap/app.php` and not `app/Http/Kernel.php` which doesn't exist since Laravel 11.
- **Tailwind**: in new Blade pages, use Tailwind and not Bootstrap, unless instructed otherwise in the prompt. Tailwind is already pre-configured since Laravel 11, with Vite.
- **Faker**: in Factories, use `fake()` helper instead of `$this->faker`.
- **Policies**: Laravel automatically auto-discovers Policies, no need to register them in the Service Providers.

---

## Testing instructions

Use Pest and not PHPUnit. Run tests with `php artisan test`.

Every test method should be structured with Arrange-Act-Assert.

In the Arrange phase, use Laravel factories but add meaningful column values and variable names if they help to understand failed tests better.
Bad example: `$user1 = User::factory()->create();`
Better example: `$adminUser = User::factory()->create(['email' => 'admin@admin.com'])`;

In the Assert phase, perform these assertions when applicable:
- HTTP status code returned from Act: `assertStatus()`
- Structure/data returned from Act (Blade or JSON): functions like `assertViewHas()`, `assertSee()`, `assertDontSee()` or `assertJsonContains()`
- Or, redirect assertions like `assertRedirect()` and `assertSessionHas()` in case of Flash session values passed
- DB changes if any create/update/delete operation was performed: functions like `assertDatabaseHas()`, `assertDatabaseMissing()`, `expect($variable)->toBe()` and similar.
