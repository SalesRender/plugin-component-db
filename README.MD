# salesrender/plugin-component-db

Database abstraction layer for SalesRender plugins, built on top of [Medoo](https://medoo.in/) ORM with SQLite as the storage engine.

## Overview

`plugin-component-db` provides a structured way to persist data in SalesRender plugins using SQLite. It introduces a base `Model` class with automatic serialization/deserialization, schema-based table creation, and built-in scoping for multi-tenant plugin environments.

The component supports three distinct model usage patterns, each suited to different data isolation needs:

- **Basic Model** (`ModelInterface`) -- standalone models with no automatic scoping. Suitable for global data shared across all plugin instances.
- **Plugin Model** (`PluginModelInterface`) -- models scoped by `companyId`, `pluginAlias`, and `pluginId`. Each query and write operation is automatically filtered to the current plugin context. Ideal for per-company, per-plugin-instance data.
- **Single Plugin Model** (`SinglePluginModelInterface`) -- a singleton pattern where exactly one record exists per plugin instance. The record's `id` is automatically set to the current `pluginId`. Used for plugin-level configuration or state (e.g., settings, tokens).

The component also provides console commands for automated table creation and cleanup, a UUID helper for generating unique identifiers, and a `DatabaseException` guard for consistent error handling.

## Installation

```bash
composer require salesrender/plugin-component-db
```

## Requirements

- PHP >= 7.4
- Extensions: `ext-json`, `ext-sqlite3`
- Dependencies:
  - `catfan/medoo` ^1.7 -- database framework
  - `symfony/console` ^5.0 -- console commands
  - `ramsey/uuid` ^3.9 -- UUID generation
  - `haydenpierce/class-finder` ^0.4.0 -- automatic model class discovery

## Key Classes

### `Connector`

**Namespace:** `SalesRender\Plugin\Components\Db\Components`

Static singleton that holds the Medoo database connection and the current `PluginReference`. Must be configured before any database operations.

**Methods:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `config` | `static config(Medoo $medoo): void` | Set the Medoo database connection |
| `db` | `static db(): Medoo` | Get the configured Medoo instance. Throws `RuntimeException` if not configured |
| `setReference` | `static setReference(PluginReference $reference): void` | Set the current plugin reference (company + plugin context) |
| `getReference` | `static getReference(): PluginReference` | Get the current plugin reference. Throws `RuntimeException` if not set |
| `hasReference` | `static hasReference(): bool` | Check if a plugin reference has been set |

### `PluginReference`

**Namespace:** `SalesRender\Plugin\Components\Db\Components`

Immutable value object that identifies the current plugin context: which company, which plugin alias, and which plugin instance.

**Constructor:**

```php
public function __construct(string $companyId, string $alias, string $id)
```

**Methods:**

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getCompanyId()` | `string` | Company identifier |
| `getAlias()` | `string` | Plugin alias (type identifier) |
| `getId()` | `string` | Plugin instance identifier |

### `Model` (abstract)

**Namespace:** `SalesRender\Plugin\Components\Db`

The base abstract class for all database models. Handles CRUD operations, serialization, identity mapping, and automatic plugin scoping.

**Abstract methods to implement:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `schema` | `static schema(): array` | Define the table columns using Medoo CREATE syntax |

**Instance methods:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `getId` | `getId(): string` | Get the model's unique identifier |
| `save` | `save(): void` | Insert (if new) or update the record |
| `delete` | `delete(): void` | Delete the record from the database |
| `isNewModel` | `isNewModel(): bool` | Check if this model has not yet been persisted |

**Static query methods:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `findById` | `static findById(string $id): ?self` | Find a single model by its ID |
| `findByIds` | `static findByIds(array $ids): array` | Find multiple models by their IDs |
| `findByCondition` | `static findByCondition(array $where): array` | Find models by [Medoo where-clause](https://medoo.in/api/where). Auto-scopes for `PluginModelInterface` |
| `find` | `static find(): ?Model` | Find the singleton record. Only works with `SinglePluginModelInterface` |
| `findByConditionWithoutScope` | `static findByConditionWithoutScope(array $where): array` | Query without automatic plugin scoping (internal use) |
| `tableName` | `static tableName(): string` | Table name (defaults to short class name; can be overridden) |
| `freeUpMemory` | `static freeUpMemory(): void` | Clear the identity map cache |

**Lifecycle hooks:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `beforeSave` | `protected beforeSave(bool $isNew): void` | Called before each save operation |
| `afterFind` | `protected afterFind(): void` | Called after a model is loaded from the database |
| `beforeWrite` | `protected static beforeWrite(array $data): array` | Transform data before writing to DB (e.g., JSON encode) |
| `afterRead` | `protected static afterRead(array $data): array` | Transform data after reading from DB (e.g., JSON decode) |
| `afterTableCreate` | `static afterTableCreate(Medoo $db): void` | Called after the table is created (e.g., to create indexes) |

**Save event handlers:**

| Method | Signature | Description |
|--------|-----------|-------------|
| `addOnSaveHandler` | `static addOnSaveHandler(callable $handler, string $name = null): void` | Register a callback invoked after each save |
| `removeOnSaveHandler` | `static removeOnSaveHandler(string $name): void` | Remove a previously registered save handler |

### `ModelInterface`

**Namespace:** `SalesRender\Plugin\Components\Db`

The base interface for all models. Defines the contract for CRUD operations, schema definition, and table creation hooks.

**Methods defined:**

- `save(): void`
- `delete(): void`
- `isNewModel(): bool`
- `static findById(string $id): ?self`
- `static findByIds(array $ids): array`
- `static findByCondition(array $where): array`
- `static tableName(): string`
- `static schema(): array`
- `static afterTableCreate(Medoo $db): void`

### `PluginModelInterface`

**Namespace:** `SalesRender\Plugin\Components\Db`

Extends `ModelInterface`. A marker interface that enables automatic scoping by `companyId`, `pluginAlias`, and `pluginId`. When a model implements this interface:

- `save()` automatically includes the plugin reference fields
- `findByCondition()` automatically filters by the current plugin context
- `delete()` automatically scopes the deletion
- The table's primary key becomes a composite key: `(companyId, pluginAlias, pluginId, id)`

### `SinglePluginModelInterface`

**Namespace:** `SalesRender\Plugin\Components\Db`

Extends `PluginModelInterface`. For singleton-per-plugin-instance models. When a model implements this interface:

- The model's `id` is automatically set to the current `pluginId`
- The static `find()` method (with no arguments) returns the single record for the current plugin instance
- Only one record can exist per plugin instance

**Additional method:**

- `static find(): ?Model`

### `UuidHelper`

**Namespace:** `SalesRender\Plugin\Components\Db\Helpers`

Generates UUID v4 identifiers for use as model IDs.

```php
$id = UuidHelper::getUuid(); // e.g. "550e8400-e29b-41d4-a716-446655440000"
```

### `DatabaseException`

**Namespace:** `SalesRender\Plugin\Components\Db\Exceptions`

Exception class thrown when a database operation fails. Contains the Medoo error information and the last executed SQL query.

**Constructor:**

```php
public function __construct(Medoo $db)
```

**Static guard method:**

```php
// Throws DatabaseException if the last query produced an error
DatabaseException::guard(Medoo $db): void
```

### `CreateTablesCommand`

**Namespace:** `SalesRender\Plugin\Components\Db\Commands`

Symfony Console command registered as `db:create-tables`. Automatically discovers all `ModelInterface` implementations in the `SalesRender\Plugin` namespace using `ClassFinder` and creates their database tables based on `schema()` definitions.

Table creation logic:

- For basic models (`ModelInterface`): creates a table with `id VARCHAR(255) PRIMARY KEY` plus your custom schema fields.
- For plugin models (`PluginModelInterface`): creates a table with `companyId INT`, `pluginAlias VARCHAR(255)`, `pluginId INT`, `id VARCHAR(255)`, plus your custom schema fields, with a composite primary key on `(companyId, pluginAlias, pluginId, id)`.
- Calls `afterTableCreate()` on each model class after its table is created.

```bash
php console.php db:create-tables
```

### `TableCleanerCommand`

**Namespace:** `SalesRender\Plugin\Components\Db\Commands`

Symfony Console command registered as `db:cleaner`. Deletes records older than a specified number of hours based on a timestamp field.

```bash
php console.php db:cleaner <table> <by> [hours]
```

**Arguments:**

| Argument | Required | Default | Description |
|----------|----------|---------|-------------|
| `table` | Yes | -- | Table name to clean |
| `by` | Yes | -- | Name of the integer timestamp field to compare against |
| `hours` | No | 24 | Age threshold in hours; records older than this are deleted |

### `ReflectionHelper`

**Namespace:** `SalesRender\Plugin\Components\Db\Helpers`

Internal utility class used by the `Model` during deserialization. Provides methods to:

- Create object instances without calling the constructor (`newWithoutConstructor`)
- Get and set private/protected properties via reflection (`getProperty`, `setProperty`)
- Cache `ReflectionMethod` instances (`getMethod`)

## Usage

### 1. Configuring the Database Connection

In your plugin's `bootstrap.php`, configure the Medoo connection:

```php
use SalesRender\Plugin\Components\Db\Components\Connector;
use Medoo\Medoo;
use XAKEPEHOK\Path\Path;

// Configure SQLite database connection
// The *.db file and its parent directory must be writable
Connector::config(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => Path::root()->down('db/database.db'),
]));
```

### 2. Basic Model (no scoping)

A simple model with no automatic tenant isolation. Use when data is shared across all plugin instances.

```php
use SalesRender\Plugin\Components\Db\Model;
use SalesRender\Plugin\Components\Db\Helpers\UuidHelper;

class ChatMessage extends Model
{
    protected int $createdAt;
    protected string $content;
    protected string $externalId;

    public function __construct(string $content, string $externalId)
    {
        $this->id = UuidHelper::getUuid();
        $this->createdAt = time();
        $this->content = $content;
        $this->externalId = $externalId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public static function schema(): array
    {
        return [
            'createdAt' => ['INT', 'NOT NULL'],
            'content' => ['TEXT', 'NOT NULL'],
            'externalId' => ['VARCHAR(255)', 'NOT NULL'],
        ];
    }
}

// Create and save
$message = new ChatMessage('Hello!', 'ext-123');
$message->save();

// Find by ID
$found = ChatMessage::findById($message->getId());

// Find by condition (Medoo where syntax)
$messages = ChatMessage::findByCondition([
    'createdAt[>]' => time() - 3600,
]);

// Delete
$found->delete();
```

### 3. Plugin Model (company + plugin scoped)

Models that are automatically isolated per company and plugin instance. The fields `companyId`, `pluginAlias`, and `pluginId` are managed automatically -- do NOT define them in your `schema()`.

```php
use SalesRender\Plugin\Components\Db\Model;
use SalesRender\Plugin\Components\Db\PluginModelInterface;
use SalesRender\Plugin\Components\Db\Helpers\UuidHelper;
use Medoo\Medoo;
use SalesRender\Plugin\Components\Db\Exceptions\DatabaseException;

class Call extends Model implements PluginModelInterface
{
    protected int $startedAt;
    protected string $callTo;
    protected int $callerId;

    public function __construct(string $id, int $callerId, string $callTo)
    {
        $this->id = $id;
        $this->startedAt = time();
        $this->callerId = $callerId;
        $this->callTo = $callTo;
    }

    // Override tableName() to use a custom table name instead of the class name
    public static function tableName(): string
    {
        return 'calls';
    }

    public static function schema(): array
    {
        return [
            'startedAt' => ['INT', 'NOT NULL'],
            'callTo' => ['VARCHAR(50)', 'NOT NULL'],
            'callerId' => ['INT', 'NOT NULL'],
        ];
    }

    // Create indexes after the table is created
    public static function afterTableCreate(Medoo $db): void
    {
        $db->exec(
            'CREATE INDEX `calls_callTo` ON calls (`startedAt`, `callTo`)'
        );
        DatabaseException::guard($db);
    }
}

// All queries are automatically scoped to the current PluginReference
$call = new Call('unique-id', 42, '+1234567890');
$call->save();

// findByCondition automatically adds companyId, pluginAlias, pluginId to the WHERE clause
$calls = Call::findByCondition([
    'startedAt[>]' => time() - 86400,
]);
```

### 4. Single Plugin Model (singleton per plugin instance)

For models where exactly one record exists per plugin instance. The `id` is automatically set to the current `pluginId`. Use the `find()` method (no arguments) to retrieve the singleton.

```php
use SalesRender\Plugin\Components\Db\Model;
use SalesRender\Plugin\Components\Db\SinglePluginModelInterface;

class Token extends Model implements SinglePluginModelInterface
{
    protected string $accessToken;
    protected string $refreshToken;
    protected int $expiresAt;

    public function __construct(string $accessToken, string $refreshToken)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = time() + 3600;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < time();
    }

    public static function schema(): array
    {
        return [
            'accessToken' => ['TEXT', 'NOT NULL'],
            'refreshToken' => ['TEXT', 'NOT NULL'],
            'expiresAt' => ['INT', 'NOT NULL'],
        ];
    }
}

// Save the singleton (id is auto-set to pluginId)
$token = new Token('access_xxx', 'refresh_yyy');
$token->save();

// Retrieve the singleton -- no arguments needed
$token = Token::find();
if ($token !== null && !$token->isExpired()) {
    echo $token->getAccessToken();
}
```

### 5. Using `beforeWrite` / `afterRead` for Complex Types

When a model property is non-scalar (e.g., an array or object), you must serialize it before writing and deserialize it after reading. Override the `beforeWrite()` and `afterRead()` static methods:

```php
use SalesRender\Plugin\Components\Db\Model;
use SalesRender\Plugin\Components\Db\PluginModelInterface;
use SalesRender\Plugin\Components\Db\Helpers\UuidHelper;

class Cache extends Model implements PluginModelInterface
{
    protected string $k;
    protected int $expiredAt;
    protected array $data = [];

    public function __construct(string $key)
    {
        $this->id = UuidHelper::getUuid();
        $this->k = $key;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    protected static function beforeWrite(array $data): array
    {
        // Encode array to JSON string before saving to DB
        $data['data'] = json_encode($data['data']);
        return parent::beforeWrite($data);
    }

    protected static function afterRead(array $data): array
    {
        // Decode JSON string back to array after loading from DB
        $data['data'] = json_decode($data['data'], true);
        return parent::afterRead($data);
    }

    public static function schema(): array
    {
        return [
            'k' => ['VARCHAR(255)', 'NOT NULL'],
            'data' => ['TEXT', 'NOT NULL'],
            'expiredAt' => ['INT', 'NULL'],
        ];
    }

    public static function tableName(): string
    {
        return 'cache';
    }
}
```

### 6. Using Save Event Handlers

You can register named callbacks that fire after each successful `save()` call on a model:

```php
use SalesRender\Plugin\Components\Settings\Settings;

// Register a named handler
Settings::addOnSaveHandler(function (Settings $settings) {
    // React to settings being saved, e.g., push config to an external API
}, 'config-sync');

// Remove handler later if needed
Settings::removeOnSaveHandler('config-sync');
```

### 7. Using `beforeSave` and `afterFind` Hooks

Override `beforeSave()` to run logic before the model is persisted, and `afterFind()` for post-load processing:

```php
use SalesRender\Plugin\Components\Db\Model;

class AuditLog extends Model
{
    protected int $createdAt;
    protected ?int $updatedAt = null;
    protected string $action;

    protected function beforeSave(bool $isNew): void
    {
        if ($isNew) {
            $this->createdAt = time();
        } else {
            $this->updatedAt = time();
        }
    }

    protected function afterFind(): void
    {
        // Post-load processing, e.g., type casting
    }

    public static function schema(): array
    {
        return [
            'createdAt' => ['INT', 'NOT NULL'],
            'updatedAt' => ['INT', 'NULL'],
            'action' => ['VARCHAR(255)', 'NOT NULL'],
        ];
    }
}
```

## Schema Definition Rules

When implementing `schema()`, follow these rules:

1. **DO NOT** use `AUTO_INCREMENT`. Use `UuidHelper::getUuid()` or `Ramsey\Uuid\Uuid::uuid4()->toString()` for model IDs.
2. **DO NOT** define `PRIMARY KEY` in the schema. It is generated automatically:
   - For basic models: `id` is the primary key
   - For plugin models: composite key of `(companyId, pluginAlias, pluginId, id)`
3. **DO NOT** include `id`, `companyId`, `pluginAlias`, or `pluginId` fields in your schema. They are managed automatically.
4. Use [Medoo CREATE syntax](https://medoo.in/api/create) for column definitions.
5. All model properties should be **scalar or null**. Non-scalar types must be converted using `beforeWrite()`/`afterRead()`.

```php
public static function schema(): array
{
    return [
        'name'      => ['VARCHAR(255)', 'NOT NULL'],
        'value'     => ['TEXT'],
        'amount'    => ['INT', 'NOT NULL'],
        'isActive'  => ['INT', 'NOT NULL'],        // Use INT for boolean
        'createdAt' => ['INT', 'NOT NULL'],        // Use INT for timestamps
        'metadata'  => ['TEXT', 'NULL'],            // Use TEXT for JSON data
    ];
}
```

## Configuration

### Database Connection

```php
use SalesRender\Plugin\Components\Db\Components\Connector;
use Medoo\Medoo;

Connector::config(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => '/path/to/database.db',
]));
```

### Plugin Reference

The plugin reference is typically set automatically by the plugin core framework during HTTP request or console command processing. If you need to set it manually (e.g., in tests or scripts):

```php
use SalesRender\Plugin\Components\Db\Components\PluginReference;
use SalesRender\Plugin\Components\Db\Components\Connector;

Connector::setReference(new PluginReference(
    '12345',          // companyId
    'my-plugin',      // pluginAlias
    '67890'           // pluginId
));

// Check if reference is set
if (Connector::hasReference()) {
    $ref = Connector::getReference();
    echo $ref->getCompanyId();  // "12345"
    echo $ref->getAlias();      // "my-plugin"
    echo $ref->getId();         // "67890"
}
```

## Console Commands

Register the commands in your Symfony Console application:

```php
use SalesRender\Plugin\Components\Db\Commands\CreateTablesCommand;
use SalesRender\Plugin\Components\Db\Commands\TableCleanerCommand;

$application->add(new CreateTablesCommand());
$application->add(new TableCleanerCommand());
```

Then run:

```bash
# Create all tables for models found in the SalesRender\Plugin namespace
php console.php db:create-tables

# Clean old records: delete from 'logs' where 'createdAt' older than 48 hours
php console.php db:cleaner logs createdAt 48

# Default is 24 hours
php console.php db:cleaner messages createdAt
```

## API Reference

### `Connector`

```php
static config(Medoo $medoo): void
static db(): Medoo
static hasReference(): bool
static getReference(): PluginReference
static setReference(PluginReference $reference): void
```

### `PluginReference`

```php
__construct(string $companyId, string $alias, string $id)
getCompanyId(): string
getAlias(): string
getId(): string
```

### `Model`

```php
// Instance
getId(): string
save(): void
delete(): void
isNewModel(): bool

// Static - querying
static findById(string $id): ?self
static findByIds(array $ids): array
static findByCondition(array $where): array
static find(): ?Model                              // SinglePluginModelInterface only
static findByConditionWithoutScope(array $where): array  // Internal

// Static - configuration
static tableName(): string
static schema(): array                              // Abstract
static afterTableCreate(Medoo $db): void
static freeUpMemory(): void

// Static - events
static addOnSaveHandler(callable $handler, string $name = null): void
static removeOnSaveHandler(string $name): void

// Protected hooks
protected beforeSave(bool $isNew): void
protected afterFind(): void
protected static beforeWrite(array $data): array
protected static afterRead(array $data): array
```

### `UuidHelper`

```php
static getUuid(): string
```

### `DatabaseException`

```php
__construct(Medoo $db)
static guard(Medoo $db): void
```

## Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `catfan/medoo` | ^1.7 | Lightweight database framework providing query building and SQLite support |
| `symfony/console` | ^5.0 | Console command infrastructure for `CreateTablesCommand` and `TableCleanerCommand` |
| `ramsey/uuid` | ^3.9 | UUID v4 generation for model identifiers |
| `haydenpierce/class-finder` | ^0.4.0 | Automatic discovery of Model classes in `CreateTablesCommand` |

## See Also

- [Medoo Documentation](https://medoo.in/doc) -- query syntax for `findByCondition()` and `schema()` definitions
- [Medoo Where Clause](https://medoo.in/api/where) -- full reference for query conditions
- [Medoo Create Table](https://medoo.in/api/create) -- column definition syntax used in `schema()`
- [`salesrender/plugin-component-settings`](https://github.com/SalesRender/plugin-component-settings) -- `Settings` class as a real-world `SinglePluginModelInterface` example
- [`salesrender/plugin-component-access`](https://github.com/SalesRender/plugin-component-access) -- `Registration` class using `SinglePluginModelInterface`
