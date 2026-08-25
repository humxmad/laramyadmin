<?php

namespace LaraMyAdmin\Services;

use Illuminate\Support\Str;

class LaravelCodeGeneratorService
{
    public function __construct(
        protected SchemaService $schemaService
    ) {}

    public function generateMigration(string $table): string
    {
        $columns = $this->schemaService->getTableColumns($table);
        $foreignKeys = $this->schemaService->getTableForeignKeys($table);

        $lines = [];
        $hasTimestamps = false;
        $createdAt = false;
        $updatedAt = false;

        foreach ($columns as $col) {
            $name = $col['name'];
            if ($name === 'created_at') $createdAt = true;
            if ($name === 'updated_at') $updatedAt = true;
        }

        $hasTimestamps = $createdAt && $updatedAt;

        foreach ($columns as $col) {
            $name = $col['name'];
            if ($hasTimestamps && in_array($name, ['created_at', 'updated_at'])) {
                continue;
            }

            $type = strtolower($col['type']);
            $method = $this->mapColumnTypeToMigrationMethod($type, $name, $col);

            $line = "\$table->{$method}('{$name}'";
            if ($method === 'decimal' || $method === 'float') {
                $line .= ", 10, 2";
            }
            $line .= ")";

            if (!empty($col['primary']) && $method !== 'id') {
                $line .= "->primary()";
            }
            if (!empty($col['unique'])) {
                $line .= "->unique()";
            }
            if ($col['nullable']) {
                $line .= "->nullable()";
            }
            if ($col['default'] !== null && $col['default'] !== '') {
                $def = is_numeric($col['default']) ? $col['default'] : "'" . addslashes($col['default']) . "'";
                $line .= "->default({$def})";
            }
            if (!empty($col['comment'])) {
                $line .= "->comment('" . addslashes($col['comment']) . "')";
            }

            $line .= ";";
            $lines[] = "            " . $line;
        }

        if ($hasTimestamps) {
            $lines[] = "            \$table->timestamps();";
        }

        // Add foreign keys
        foreach ($foreignKeys as $fk) {
            $lines[] = "            \$table->foreign('{$fk['column']}')->references('{$fk['foreign_column']}')->on('{$fk['foreign_table']}')->onDelete('{$fk['on_delete']}');";
        }

        $code = "<?php\n\n";
        $code .= "use Illuminate\\Database\\Migrations\\Migration;\n";
        $code .= "use Illuminate\\Database\\Schema\\Blueprint;\n";
        $code .= "use Illuminate\\Support\Facades\\Schema;\n\n";
        $code .= "return new class extends Migration\n{\n";
        $code .= "    /**\n     * Run the migrations.\n     */\n";
        $code .= "    public function up(): void\n    {\n";
        $code .= "        Schema::create('{$table}', function (Blueprint \$table) {\n";
        $code .= implode("\n", $lines) . "\n";
        $code .= "        });\n    }\n\n";
        $code .= "    /**\n     * Reverse the migrations.\n     */\n";
        $code .= "    public function down(): void\n    {\n";
        $code .= "        Schema::dropIfExists('{$table}');\n    }\n};\n";

        return $code;
    }

    public function generateModel(string $table): string
    {
        $className = Str::studly(Str::singular($table));
        $columns = $this->schemaService->getTableColumns($table);
        $foreignKeys = $this->schemaService->getTableForeignKeys($table);

        $fillables = [];
        $casts = [];
        $hidden = [];

        foreach ($columns as $col) {
            $name = $col['name'];
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            if (in_array($name, ['password', 'remember_token', 'secret', 'token'])) {
                $hidden[] = "'{$name}'";
            } else {
                $fillables[] = "'{$name}'";
            }

            $type = strtolower($col['type']);
            if (in_array($type, ['boolean', 'bool', 'tinyint(1)'])) {
                $casts[] = "        '{$name}' => 'boolean',";
            } elseif (in_array($type, ['json', 'jsonb'])) {
                $casts[] = "        '{$name}' => 'array',";
            } elseif (in_array($type, ['datetime', 'timestamp'])) {
                $casts[] = "        '{$name}' => 'datetime',";
            } elseif (in_array($type, ['date'])) {
                $casts[] = "        '{$name}' => 'date',";
            } elseif (in_array($type, ['decimal', 'double', 'float'])) {
                $casts[] = "        '{$name}' => 'decimal:2',";
            } elseif (in_array($type, ['int', 'integer', 'bigint', 'smallint'])) {
                $casts[] = "        '{$name}' => 'integer',";
            }
        }

        $relationships = [];
        foreach ($foreignKeys as $fk) {
            $relMethod = Str::camel(Str::singular(preg_replace('/_id$/', '', $fk['column'])));
            $relatedModel = Str::studly(Str::singular($fk['foreign_table']));
            $relationships[] = "    public function {$relMethod}(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n    {\n        return \$this->belongsTo({$relatedModel}::class, '{$fk['column']}', '{$fk['foreign_column']}');\n    }";
        }

        $code = "<?php\n\n";
        $code .= "namespace App\\Models;\n\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Model;\n\n";
        $code .= "class {$className} extends Model\n{\n";
        $code .= "    use HasFactory;\n\n";
        $code .= "    protected \$table = '{$table}';\n\n";
        
        $code .= "    protected \$fillable = [\n        " . implode(",\n        ", $fillables) . ",\n    ];\n\n";

        if (!empty($hidden)) {
            $code .= "    protected \$hidden = [\n        " . implode(",\n        ", $hidden) . ",\n    ];\n\n";
        }

        if (!empty($casts)) {
            $code .= "    protected function casts(): array\n    {\n        return [\n" . implode("\n", $casts) . "\n        ];\n    }\n\n";
        }

        if (!empty($relationships)) {
            $code .= implode("\n\n", $relationships) . "\n\n";
        }

        $code .= "}\n";

        return $code;
    }

    public function generateFactory(string $table): string
    {
        $className = Str::studly(Str::singular($table));
        $columns = $this->schemaService->getTableColumns($table);

        $definitions = [];

        foreach ($columns as $col) {
            $name = $col['name'];
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fakerCall = $this->mapColumnToFaker($name, strtolower($col['type']));
            $definitions[] = "            '{$name}' => {$fakerCall},";
        }

        $code = "<?php\n\n";
        $code .= "namespace Database\\Factories;\n\n";
        $code .= "use App\\Models\\{$className};\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Factories\\Factory;\n\n";
        $code .= "/**\n * @extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<\\App\\Models\\{$className}>\n */\n";
        $code .= "class {$className}Factory extends Factory\n{\n";
        $code .= "    protected \$model = {$className}::class;\n\n";
        $code .= "    public function definition(): array\n    {\n";
        $code .= "        return [\n" . implode("\n", $definitions) . "\n        ];\n    }\n}\n";

        return $code;
    }

    protected function mapColumnTypeToMigrationMethod(string $type, string $name, array $col): string
    {
        if ($name === 'id' && !empty($col['primary'])) return 'id';
        if (str_ends_with($name, '_id')) return 'foreignId';

        return match (true) {
            str_contains($type, 'int') && !empty($col['primary']) => 'id',
            str_contains($type, 'bigint') => 'bigInteger',
            str_contains($type, 'smallint') => 'smallInteger',
            str_contains($type, 'tinyint(1)') || str_contains($type, 'bool') => 'boolean',
            str_contains($type, 'int') => 'integer',
            str_contains($type, 'varchar') || str_contains($type, 'string') => 'string',
            str_contains($type, 'longtext') => 'longText',
            str_contains($type, 'mediumtext') => 'mediumText',
            str_contains($type, 'text') => 'text',
            str_contains($type, 'datetime') => 'dateTime',
            str_contains($type, 'timestamp') => 'timestamp',
            str_contains($type, 'date') => 'date',
            str_contains($type, 'time') => 'time',
            str_contains($type, 'decimal') => 'decimal',
            str_contains($type, 'float') || str_contains($type, 'double') => 'float',
            str_contains($type, 'json') => 'json',
            str_contains($type, 'uuid') => 'uuid',
            default => 'string',
        };
    }

    protected function mapColumnToFaker(string $name, string $type): string
    {
        $lname = strtolower($name);

        if ($lname === 'name' || $lname === 'full_name') return "fake()->name()";
        if ($lname === 'first_name') return "fake()->firstName()";
        if ($lname === 'last_name') return "fake()->lastName()";
        if ($lname === 'email') return "fake()->unique()->safeEmail()";
        if ($lname === 'phone' || $lname === 'phone_number') return "fake()->phoneNumber()";
        if ($lname === 'address') return "fake()->address()";
        if ($lname === 'city') return "fake()->city()";
        if ($lname === 'country') return "fake()->country()";
        if ($lname === 'postal_code' || $lname === 'zip') return "fake()->postcode()";
        if ($lname === 'title') return "fake()->sentence(4)";
        if ($lname === 'description' || $lname === 'body' || $lname === 'content') return "fake()->paragraph()";
        if ($lname === 'password') return "bcrypt('password')";
        if ($lname === 'price' || $lname === 'amount' || $lname === 'total') return "fake()->randomFloat(2, 10, 1000)";
        if ($lname === 'sku' || $lname === 'code') return "fake()->unique()->bothify('SKU-####-????')";
        if ($lname === 'status') return "fake()->randomElement(['pending', 'active', 'completed', 'inactive'])";
        if ($lname === 'is_active' || $lname === 'status_flag') return "fake()->boolean(80)";
        if (str_ends_with($lname, '_id')) return "1";

        return match (true) {
            str_contains($type, 'bool') => "fake()->boolean()",
            str_contains($type, 'int') => "fake()->numberBetween(1, 100)",
            str_contains($type, 'decimal') || str_contains($type, 'float') => "fake()->randomFloat(2, 5, 500)",
            str_contains($type, 'date') => "fake()->date()",
            str_contains($type, 'datetime') || str_contains($type, 'timestamp') => "fake()->dateTime()",
            str_contains($type, 'json') => "json_encode(['key' => fake()->word()])",
            str_contains($type, 'text') => "fake()->paragraphs(2, true)",
            default => "fake()->word()",
        };
    }
}
