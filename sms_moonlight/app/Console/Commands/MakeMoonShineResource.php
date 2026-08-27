<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeMoonShineResource extends Command
{
    protected $signature = 'moonshine:resource-custom {model}';
    protected $description = 'Generate a MoonShine resource based on a model';

    public function handle(): int
    {
        $modelClass = $this->argument('model');

        if (!class_exists($modelClass)) {
            $this->error("Model {$modelClass} does not exist.");
            return 1;
        }

        $model = new $modelClass();
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);

        $resourceClassName = class_basename($modelClass) . 'Resource';
        $modelName = class_basename($modelClass);
        $namespace = "App\\MoonShine\\Resources\\{$modelName}";

        $fieldsCode = $this->generateFieldsCode($columns, $table);

        // Generate pages
        $pagesCode = $this->generatePagesCode($modelName);
        $pagesImport = $this->generatePagesImport($modelName);

        $resourceTemplate = <<<PHP
<?php

namespace {$namespace};

use {$modelClass};
{$pagesImport}
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Fields\Relationships\{BelongsTo,HasMany};
use MoonShine\UI\Fields\{
    ID,
    Text,
    Textarea,
    Date,
    Time,
    Number,
    Toggle,
    Json,
    Select
};

/**
 * Auto-generated MoonShine resource
 */
class {$resourceClassName} extends ModelResource
{
    public string \$model = {$modelName}::class;

    protected function pages(): array
    {
        return [
            {$pagesCode}
        ];
    }

    public function indexFields(): array
    {
        return [
{$fieldsCode}
        ];
    }

    public function formFields(): array
    {
        return [
{$fieldsCode}
        ];
    }

    public function detailsFields(): array
    {
        return [
{$fieldsCode}
        ];
    }
}

PHP;

        // Save Resource File
        $path = app_path("MoonShine/Resources/{$modelName}/{$resourceClassName}.php");
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, $resourceTemplate);
        $this->info("MoonShine Resource created: {$path}");

        // Generate Pages
        $this->generateIndexPage($modelName);
        $this->generateFormPage($modelName);
        $this->generateDetailPage($modelName);

        return 0;
    }

    protected function humanize(string $value): string
    {
        return Str::of($value)->snake()->replace('_', ' ')->title();
    }

    protected function generateFieldsCode(array $columns, string $table): string
    {
        $fields = [];
        $modelClass = $this->guessModelFromTable($table);
        if (!$modelClass)
            return '';

        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        foreach ($columns as $column) {
            if (in_array($column, ['deleted_at', 'created_at', 'updated_at']))
                continue;

            $label = $this->humanize($column);

            // PRIMARY KEY
            if ($column === 'id') {
                $fields[] = "            ID::make(__('{$label}')),";
                continue;
            }

            // BELONGS TO
            if (Str::endsWith($column, '_id')) {
                $relation = Str::before($column, '_id');
                $relatedModel = $this->relatedModelExists($relation);

                if ($relatedModel) {
                    $fields[] = "            BelongsTo::make(__('{$label}'), '{$relation}', {$relatedModel}::class),";
                } else {
                    $fields[] = "            Number::make(__('{$label}'), '{$column}'),";
                }
                continue;
            }

            // HAS MANY (safe for all drivers)
            try {
                if ($driver === 'sqlite') {
                    // SQLite: loop all tables and check foreign keys
                    $tables = $connection->select("SELECT name FROM sqlite_master WHERE type='table'");
                    foreach ($tables as $tblObj) {
                        $otherTable = $tblObj->name;

                        if ($otherTable === $table)
                            continue;

                        $foreignKeys = $connection->select("PRAGMA foreign_key_list({$otherTable})");

                        foreach ($foreignKeys as $fk) {
                            if ($fk->table === $table) { // points back to current table
                                $related = $this->guessModelFromTable($otherTable);
                                if ($related) {
                                    $relationName = Str::camel(Str::plural($otherTable));
                                    $fields[] = "            HasMany::make(__('{$relationName}'), '{$relationName}', {$related}::class),";
                                }
                            }
                        }
                    }
                } else {
                    // MySQL/Postgres: use Doctrine
                    $schemaManager = $connection->getDoctrineSchemaManager();
                    $tables = $schemaManager->listTableNames();
                    foreach ($tables as $otherTable) {
                        if ($otherTable === $table)
                            continue;
                        $otherCols = Schema::getColumnListing($otherTable);
                        $foreignKey = Str::snake(Str::singular($table)) . '_id';

                        if (in_array($foreignKey, $otherCols)) {
                            $related = $this->guessModelFromTable($otherTable);
                            if ($related) {
                                $relationName = Str::camel(Str::plural($otherTable));
                                $fields[] = "            HasMany::make(__('{$relationName}'), '{$relationName}', {$related}::class),";
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // silently ignore if detection fails
            }


            // ENUM (MySQL only)
            $columnType = Schema::getColumnType($table, $column);
            try {
                if ($driver === 'mysql') {
                    $details = $connection->select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column])[0];
                    if (str_starts_with($details->Type, 'enum(')) {
                        preg_match_all("/'([^']+)'/", $details->Type, $matches);
                        $options = array_map(fn($v) => "'$v' => '$v'", $matches[1]);
                        $fields[] = "            Select::make(__('{$label}'), '{$column}')->options([" . implode(', ', $options) . "]),";
                        continue;
                    }
                }
            } catch (\Throwable $e) {
            }

            // JSON
            if (in_array($columnType, ['json', 'jsonb'])) {
                $fields[] = "            Json::make(__('{$label}'), '{$column}'),";
                continue;
            }

            // DATE / DATETIME / TIME / BOOLEAN / TEXT / NUMBER
            switch ($columnType) {
                case 'date':
                    $fields[] = "            Date::make(__('{$label}'), '{$column}'),";
                    break;
                case 'datetime':
                case 'timestamp':
                    $fields[] = "            Date::make(__('{$label}'), '{$column}'),";
                    break;
                case 'time':
                    $fields[] = "            Date::make(__('{$label}'), '{$column}'),";
                    break;
                case 'boolean':
                    $fields[] = "            Toggle::make(__('{$label}'), '{$column}'),";
                    break;
                case 'text':
                case 'mediumtext':
                case 'longtext':
                    $fields[] = "            Textarea::make(__('{$label}'), '{$column}'),";
                    break;
                case 'integer':
                case 'smallint':
                case 'bigint':
                    $fields[] = "            Number::make(__('{$label}'), '{$column}'),";
                    break;
                default:
                    $fields[] = "            Text::make(__('{$label}'), '{$column}'),";
            }
        }

        return implode("\n", $fields);
    }


    protected function generatePagesCode(string $modelName): string
    {
        return "{$modelName}IndexPage::class,
            {$modelName}FormPage::class,
            {$modelName}DetailPage::class";
    }

    protected function generatePagesImport(string $modelName): string
    {
        return <<<PHP
use App\MoonShine\Resources\\{$modelName}\Pages\\{$modelName}IndexPage;
use App\MoonShine\Resources\\{$modelName}\Pages\\{$modelName}FormPage;
use App\MoonShine\Resources\\{$modelName}\Pages\\{$modelName}DetailPage;
PHP;
    }

    // ------------------------------
    // MODEL / RELATION HELPERS
    // ------------------------------
    protected function guessModelFromTable(string $table): ?string
    {
        $model = 'App\\Models\\' . Str::studly(Str::singular($table));
        return class_exists($model) ? $model : null;
    }

    protected function relatedModelExists(string $name): ?string
    {
        $class = "App\\Models\\" . Str::studly($name);
        return class_exists($class) ? $class : null;
    }

    // ------------------------------------------------------------
    // PAGE GENERATORS
    // ------------------------------------------------------------

    protected function generateIndexPage(string $modelName): void
    {
        $namespace = "App\\MoonShine\\Resources\\{$modelName}\\Pages";
        $resourceClass = "{$modelName}Resource";

        $template = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\ID;
use MoonShine\Support\ListOf;
use Throwable;
use App\MoonShine\Resources\\{$modelName}\\{$resourceClass};

/**
 * @extends IndexPage<{$resourceClass}>
 */
class {$modelName}IndexPage extends IndexPage
{
    protected bool \$isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return \$this->getResource()->indexFields(); // reuse dynamic fields
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  \$component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract \$component): ComponentContract
    {
        return \$component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}

PHP;

        $path = app_path("MoonShine/Resources/{$modelName}/Pages/{$modelName}IndexPage.php");
        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $template);

        $this->info("Generated IndexPage: {$path}");
    }

    protected function generateFormPage(string $modelName): void
    {
        $namespace = "App\\MoonShine\\Resources\\{$modelName}\\Pages";
        $resourceClass = "{$modelName}Resource";

        // YOUR PROVIDED TEMPLATE INSERTED HERE
        $template = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use App\MoonShine\Resources\\{$modelName}\\{$resourceClass};
use MoonShine\Support\ListOf;

/**
 * @extends FormPage<{$resourceClass}>
 */
class {$modelName}FormPage extends FormPage
{
/**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        \$formfields = 
                       \$this->getResource()->formFields(); // reuse dynamic fields
        return [
            Box::make(
                \$formfields
            ),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    protected function rules(DataWrapperContract \$item): array
    {
        return [];
    }

    /**
     * @param  FormBuilder  \$component
     *
     * @return FormBuilder
     */
    protected function modifyFormComponent(FormBuilderContract \$component): FormBuilderContract
    {
        return \$component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}

PHP;

        $path = app_path("MoonShine/Resources/{$modelName}/Pages/{$modelName}FormPage.php");
        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $template);

        $this->info("Generated FormPage: {$path}");
    }

    protected function generateDetailPage(string $modelName): void
    {
        $namespace = "App\\MoonShine\\Resources\\{$modelName}\\Pages";
        $resourceClass = "{$modelName}Resource";

        $template = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Support\ListOf;
use Throwable;
use App\MoonShine\Resources\\{$modelName}\\{$resourceClass};

class {$modelName}DetailPage extends DetailPage
{
/**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return \$this->getResource()->detailsFields(); // reuse dynamic fields 
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  \$component
     *
     * @return TableBuilder
     */
    protected function modifyDetailComponent(ComponentContract \$component): ComponentContract
    {
        return \$component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}

PHP;

        $path = app_path("MoonShine/Resources/{$modelName}/Pages/{$modelName}DetailPage.php");
        if (!is_dir(dirname($path)))
            mkdir(dirname($path), 0755, true);
        file_put_contents($path, $template);

        $this->info("Generated DetailPage: {$path}");
    }
}


