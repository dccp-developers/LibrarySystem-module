<?php

declare(strict_types=1);

namespace Modules\Library\Filament;

use App\Models\GeneralSetting;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Library\Filament\Resources\AuthorResource;
use Modules\Library\Filament\Resources\CategoryResource;
use Modules\Library\Filament\Resources\BookResource;
use Modules\Library\Filament\Resources\BorrowRecordResource;

final class LibraryPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'library';
    }

    public function register(Panel $panel): void
    {
        // Check if the library module is enabled
        $generalSettings = GeneralSetting::first();

        if ($generalSettings && $generalSettings->library_module_enabled) {
            $panel
                ->resources([
                    AuthorResource::class,
                    CategoryResource::class,
                    BookResource::class,
                    BorrowRecordResource::class,
                ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
