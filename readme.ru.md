# salesrender/plugin-component-db

Слой абстракции базы данных для плагинов SalesRender, построенный на основе ORM [Medoo](https://medoo.in/) с использованием SQLite в качестве хранилища.

## Обзор

`plugin-component-db` предоставляет структурированный способ хранения данных в плагинах SalesRender с использованием SQLite. Компонент вводит базовый класс `Model` с автоматической сериализацией/десериализацией, созданием таблиц на основе схемы и встроенной изоляцией данных для мультитенантной среды плагинов.

Компонент поддерживает три различных паттерна использования моделей, каждый из которых подходит для определённых требований к изоляции данных:

- **Базовая модель** (`ModelInterface`) -- самостоятельные модели без автоматической изоляции. Подходит для глобальных данных, общих для всех экземпляров плагина.
- **Модель плагина** (`PluginModelInterface`) -- модели с автоматической привязкой к `companyId`, `pluginAlias` и `pluginId`. Каждый запрос и операция записи автоматически фильтруются по текущему контексту плагина. Идеально подходит для данных, привязанных к конкретной компании и экземпляру плагина.
- **Единичная модель плагина** (`SinglePluginModelInterface`) -- паттерн singleton, при котором существует ровно одна запись на каждый экземпляр плагина. Идентификатор записи (`id`) автоматически устанавливается равным текущему `pluginId`. Используется для конфигурации или состояния на уровне плагина (например, настройки, токены).

Компонент также предоставляет консольные команды для автоматического создания таблиц и очистки устаревших данных, хелпер для генерации UUID и механизм `DatabaseException` для единообразной обработки ошибок.

## Установка

```bash
composer require salesrender/plugin-component-db
```

## Требования

- PHP >= 7.4
- Расширения: `ext-json`, `ext-sqlite3`
- Зависимости:
  - `catfan/medoo` ^1.7 -- фреймворк для работы с базой данных
  - `symfony/console` ^5.0 -- консольные команды
  - `ramsey/uuid` ^3.9 -- генерация UUID
  - `haydenpierce/class-finder` ^0.4.0 -- автоматическое обнаружение классов моделей

## Ключевые классы

### `Connector`

**Namespace:** `SalesRender\Plugin\Components\Db\Components`

Статический singleton, который хранит подключение к базе данных Medoo и текущую ссылку на плагин (`PluginReference`). Должен быть сконфигурирован до выполнения любых операций с базой данных.

**Методы:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `config` | `static config(Medoo $medoo): void` | Установить подключение к базе данных Medoo |
| `db` | `static db(): Medoo` | Получить сконфигурированный экземпляр Medoo. Выбрасывает `RuntimeException`, если не сконфигурирован |
| `setReference` | `static setReference(PluginReference $reference): void` | Установить текущую ссылку на плагин (контекст компании + плагина) |
| `getReference` | `static getReference(): PluginReference` | Получить текущую ссылку на плагин. Выбрасывает `RuntimeException`, если не установлена |
| `hasReference` | `static hasReference(): bool` | Проверить, установлена ли ссылка на плагин |

### `PluginReference`

**Namespace:** `SalesRender\Plugin\Components\Db\Components`

Неизменяемый объект-значение, идентифицирующий текущий контекст плагина: какая компания, какой алиас плагина и какой экземпляр плагина.

**Конструктор:**

```php
public function __construct(string $companyId, string $alias, string $id)
```

**Методы:**

| Метод | Возвращаемый тип | Описание |
|-------|------------------|----------|
| `getCompanyId()` | `string` | Идентификатор компании |
| `getAlias()` | `string` | Алиас плагина (идентификатор типа) |
| `getId()` | `string` | Идентификатор экземпляра плагина |

### `Model` (абстрактный)

**Namespace:** `SalesRender\Plugin\Components\Db`

Базовый абстрактный класс для всех моделей базы данных. Обеспечивает CRUD-операции, сериализацию, карту идентичности (identity map) и автоматическую привязку к контексту плагина.

**Абстрактные методы для реализации:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `schema` | `static schema(): array` | Определить столбцы таблицы в формате Medoo CREATE |

**Методы экземпляра:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `getId` | `getId(): string` | Получить уникальный идентификатор модели |
| `save` | `save(): void` | Вставить (если новая) или обновить запись |
| `delete` | `delete(): void` | Удалить запись из базы данных |
| `isNewModel` | `isNewModel(): bool` | Проверить, была ли модель уже сохранена |

**Статические методы запросов:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `findById` | `static findById(string $id): ?self` | Найти модель по идентификатору |
| `findByIds` | `static findByIds(array $ids): array` | Найти несколько моделей по идентификаторам |
| `findByCondition` | `static findByCondition(array $where): array` | Найти модели по [условию Medoo](https://medoo.in/api/where). Автоматически добавляет привязку к плагину для `PluginModelInterface` |
| `find` | `static find(): ?Model` | Найти единичную запись. Работает только с `SinglePluginModelInterface` |
| `findByConditionWithoutScope` | `static findByConditionWithoutScope(array $where): array` | Запрос без автоматической привязки к плагину (для внутреннего использования) |
| `tableName` | `static tableName(): string` | Имя таблицы (по умолчанию -- короткое имя класса; можно переопределить) |
| `freeUpMemory` | `static freeUpMemory(): void` | Очистить кеш карты идентичности |

**Хуки жизненного цикла:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `beforeSave` | `protected beforeSave(bool $isNew): void` | Вызывается перед каждой операцией сохранения |
| `afterFind` | `protected afterFind(): void` | Вызывается после загрузки модели из базы данных |
| `beforeWrite` | `protected static beforeWrite(array $data): array` | Преобразовать данные перед записью в БД (например, JSON-кодирование) |
| `afterRead` | `protected static afterRead(array $data): array` | Преобразовать данные после чтения из БД (например, JSON-декодирование) |
| `afterTableCreate` | `static afterTableCreate(Medoo $db): void` | Вызывается после создания таблицы (например, для создания индексов) |

**Обработчики события сохранения:**

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `addOnSaveHandler` | `static addOnSaveHandler(callable $handler, string $name = null): void` | Зарегистрировать callback, вызываемый после каждого сохранения |
| `removeOnSaveHandler` | `static removeOnSaveHandler(string $name): void` | Удалить ранее зарегистрированный обработчик сохранения |

### `ModelInterface`

**Namespace:** `SalesRender\Plugin\Components\Db`

Базовый интерфейс для всех моделей. Определяет контракт для CRUD-операций, определения схемы и хуков создания таблиц.

**Определённые методы:**

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

Расширяет `ModelInterface`. Маркерный интерфейс, включающий автоматическую привязку к `companyId`, `pluginAlias` и `pluginId`. Когда модель реализует этот интерфейс:

- `save()` автоматически добавляет поля ссылки на плагин
- `findByCondition()` автоматически фильтрует по текущему контексту плагина
- `delete()` автоматически ограничивает удаление текущим контекстом
- Первичный ключ таблицы становится составным: `(companyId, pluginAlias, pluginId, id)`

### `SinglePluginModelInterface`

**Namespace:** `SalesRender\Plugin\Components\Db`

Расширяет `PluginModelInterface`. Для моделей-одиночек (singleton) на каждый экземпляр плагина. Когда модель реализует этот интерфейс:

- Идентификатор модели (`id`) автоматически устанавливается равным текущему `pluginId`
- Статический метод `find()` (без аргументов) возвращает единственную запись для текущего экземпляра плагина
- Может существовать только одна запись на экземпляр плагина

**Дополнительный метод:**

- `static find(): ?Model`

### `UuidHelper`

**Namespace:** `SalesRender\Plugin\Components\Db\Helpers`

Генерирует идентификаторы UUID v4 для использования в качестве идентификаторов моделей.

```php
$id = UuidHelper::getUuid(); // например, "550e8400-e29b-41d4-a716-446655440000"
```

### `DatabaseException`

**Namespace:** `SalesRender\Plugin\Components\Db\Exceptions`

Класс исключения, выбрасываемого при сбое операции с базой данных. Содержит информацию об ошибке Medoo и последний выполненный SQL-запрос.

**Конструктор:**

```php
public function __construct(Medoo $db)
```

**Статический метод-страж:**

```php
// Выбрасывает DatabaseException, если последний запрос завершился с ошибкой
DatabaseException::guard(Medoo $db): void
```

### `CreateTablesCommand`

**Namespace:** `SalesRender\Plugin\Components\Db\Commands`

Консольная команда Symfony, зарегистрированная как `db:create-tables`. Автоматически обнаруживает все реализации `ModelInterface` в namespace `SalesRender\Plugin` с помощью `ClassFinder` и создаёт таблицы базы данных на основе определений `schema()`.

Логика создания таблиц:

- Для базовых моделей (`ModelInterface`): создаёт таблицу с `id VARCHAR(255) PRIMARY KEY` плюс пользовательские поля из схемы.
- Для моделей плагина (`PluginModelInterface`): создаёт таблицу с полями `companyId INT`, `pluginAlias VARCHAR(255)`, `pluginId INT`, `id VARCHAR(255)`, плюс пользовательские поля, с составным первичным ключом `(companyId, pluginAlias, pluginId, id)`.
- Вызывает `afterTableCreate()` для каждого класса модели после создания его таблицы.

```bash
php console.php db:create-tables
```

### `TableCleanerCommand`

**Namespace:** `SalesRender\Plugin\Components\Db\Commands`

Консольная команда Symfony, зарегистрированная как `db:cleaner`. Удаляет записи старше указанного количества часов на основе поля с временной меткой.

```bash
php console.php db:cleaner <table> <by> [hours]
```

**Аргументы:**

| Аргумент | Обязательный | По умолчанию | Описание |
|----------|-------------|--------------|----------|
| `table` | Да | -- | Имя таблицы для очистки |
| `by` | Да | -- | Имя целочисленного поля с временной меткой (Unix timestamp) |
| `hours` | Нет | 24 | Порог возраста в часах; записи старше этого значения удаляются |

### `ReflectionHelper`

**Namespace:** `SalesRender\Plugin\Components\Db\Helpers`

Внутренний утилитный класс, используемый `Model` при десериализации. Предоставляет методы для:

- Создания экземпляров объектов без вызова конструктора (`newWithoutConstructor`)
- Получения и установки private/protected свойств через рефлексию (`getProperty`, `setProperty`)
- Кеширования экземпляров `ReflectionMethod` (`getMethod`)

## Использование

### 1. Настройка подключения к базе данных

В файле `bootstrap.php` вашего плагина настройте подключение Medoo:

```php
use SalesRender\Plugin\Components\Db\Components\Connector;
use Medoo\Medoo;
use XAKEPEHOK\Path\Path;

// Настройка подключения к базе данных SQLite
// Файл *.db и его родительский каталог должны быть доступны для записи
Connector::config(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => Path::root()->down('db/database.db'),
]));
```

### 2. Базовая модель (без привязки)

Простая модель без автоматической изоляции данных. Используйте, когда данные общие для всех экземпляров плагина.

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

// Создание и сохранение
$message = new ChatMessage('Привет!', 'ext-123');
$message->save();

// Поиск по идентификатору
$found = ChatMessage::findById($message->getId());

// Поиск по условию (синтаксис Medoo where)
$messages = ChatMessage::findByCondition([
    'createdAt[>]' => time() - 3600,
]);

// Удаление
$found->delete();
```

### 3. Модель плагина (привязка к компании + плагину)

Модели с автоматической изоляцией данных по компании и экземпляру плагина. Поля `companyId`, `pluginAlias` и `pluginId` управляются автоматически -- НЕ определяйте их в вашем `schema()`.

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

    // Переопределение tableName() для использования собственного имени таблицы вместо имени класса
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

    // Создание индексов после создания таблицы
    public static function afterTableCreate(Medoo $db): void
    {
        $db->exec(
            'CREATE INDEX `calls_callTo` ON calls (`startedAt`, `callTo`)'
        );
        DatabaseException::guard($db);
    }
}

// Все запросы автоматически привязаны к текущему PluginReference
$call = new Call('unique-id', 42, '+1234567890');
$call->save();

// findByCondition автоматически добавляет companyId, pluginAlias, pluginId в WHERE
$calls = Call::findByCondition([
    'startedAt[>]' => time() - 86400,
]);
```

### 4. Единичная модель плагина (singleton на экземпляр плагина)

Для моделей, где существует ровно одна запись на каждый экземпляр плагина. Идентификатор (`id`) автоматически устанавливается равным текущему `pluginId`. Используйте метод `find()` (без аргументов) для получения записи.

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

// Сохранение singleton (id автоматически устанавливается равным pluginId)
$token = new Token('access_xxx', 'refresh_yyy');
$token->save();

// Получение singleton -- аргументы не нужны
$token = Token::find();
if ($token !== null && !$token->isExpired()) {
    echo $token->getAccessToken();
}
```

### 5. Использование `beforeWrite` / `afterRead` для сложных типов

Когда свойство модели не является скалярным (например, массив или объект), необходимо сериализовать его перед записью и десериализовать после чтения. Переопределите статические методы `beforeWrite()` и `afterRead()`:

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
        // Кодирование массива в JSON-строку перед сохранением в БД
        $data['data'] = json_encode($data['data']);
        return parent::beforeWrite($data);
    }

    protected static function afterRead(array $data): array
    {
        // Декодирование JSON-строки обратно в массив после загрузки из БД
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

### 6. Использование обработчиков события сохранения

Вы можете зарегистрировать именованные callback-функции, которые срабатывают после каждого успешного вызова `save()` на модели:

```php
use SalesRender\Plugin\Components\Settings\Settings;

// Регистрация именованного обработчика
Settings::addOnSaveHandler(function (Settings $settings) {
    // Реагирование на сохранение настроек, например, отправка конфигурации во внешний API
}, 'config-sync');

// Удаление обработчика при необходимости
Settings::removeOnSaveHandler('config-sync');
```

### 7. Использование хуков `beforeSave` и `afterFind`

Переопределите `beforeSave()` для выполнения логики перед сохранением модели, и `afterFind()` для постобработки после загрузки:

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
        // Постобработка после загрузки, например, приведение типов
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

## Правила определения схемы

При реализации `schema()` следуйте этим правилам:

1. **НЕ ИСПОЛЬЗУЙТЕ** `AUTO_INCREMENT`. Используйте `UuidHelper::getUuid()` или `Ramsey\Uuid\Uuid::uuid4()->toString()` для идентификаторов моделей.
2. **НЕ ОПРЕДЕЛЯЙТЕ** `PRIMARY KEY` в схеме. Он генерируется автоматически:
   - Для базовых моделей: `id` является первичным ключом
   - Для моделей плагина: составной ключ `(companyId, pluginAlias, pluginId, id)`
3. **НЕ ВКЛЮЧАЙТЕ** поля `id`, `companyId`, `pluginAlias` или `pluginId` в вашу схему. Они управляются автоматически.
4. Используйте [синтаксис Medoo CREATE](https://medoo.in/api/create) для определения столбцов.
5. Все свойства модели должны быть **скалярными или null**. Нескалярные типы необходимо преобразовывать с помощью `beforeWrite()`/`afterRead()`.

```php
public static function schema(): array
{
    return [
        'name'      => ['VARCHAR(255)', 'NOT NULL'],
        'value'     => ['TEXT'],
        'amount'    => ['INT', 'NOT NULL'],
        'isActive'  => ['INT', 'NOT NULL'],        // Используйте INT для boolean
        'createdAt' => ['INT', 'NOT NULL'],        // Используйте INT для временных меток
        'metadata'  => ['TEXT', 'NULL'],            // Используйте TEXT для JSON-данных
    ];
}
```

## Конфигурация

### Подключение к базе данных

```php
use SalesRender\Plugin\Components\Db\Components\Connector;
use Medoo\Medoo;

Connector::config(new Medoo([
    'database_type' => 'sqlite',
    'database_file' => '/path/to/database.db',
]));
```

### Ссылка на плагин (Plugin Reference)

Ссылка на плагин обычно устанавливается автоматически ядром плагинного фреймворка при обработке HTTP-запроса или консольной команды. Если вам нужно установить её вручную (например, в тестах или скриптах):

```php
use SalesRender\Plugin\Components\Db\Components\PluginReference;
use SalesRender\Plugin\Components\Db\Components\Connector;

Connector::setReference(new PluginReference(
    '12345',          // companyId
    'my-plugin',      // pluginAlias
    '67890'           // pluginId
));

// Проверка, установлена ли ссылка
if (Connector::hasReference()) {
    $ref = Connector::getReference();
    echo $ref->getCompanyId();  // "12345"
    echo $ref->getAlias();      // "my-plugin"
    echo $ref->getId();         // "67890"
}
```

## Консольные команды

Зарегистрируйте команды в вашем приложении Symfony Console:

```php
use SalesRender\Plugin\Components\Db\Commands\CreateTablesCommand;
use SalesRender\Plugin\Components\Db\Commands\TableCleanerCommand;

$application->add(new CreateTablesCommand());
$application->add(new TableCleanerCommand());
```

Затем выполните:

```bash
# Создание всех таблиц для моделей из namespace SalesRender\Plugin
php console.php db:create-tables

# Очистка старых записей: удалить из 'logs', где 'createdAt' старше 48 часов
php console.php db:cleaner logs createdAt 48

# По умолчанию -- 24 часа
php console.php db:cleaner messages createdAt
```

## Справочник API

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
// Методы экземпляра
getId(): string
save(): void
delete(): void
isNewModel(): bool

// Статические методы -- запросы
static findById(string $id): ?self
static findByIds(array $ids): array
static findByCondition(array $where): array
static find(): ?Model                              // Только для SinglePluginModelInterface
static findByConditionWithoutScope(array $where): array  // Для внутреннего использования

// Статические методы -- конфигурация
static tableName(): string
static schema(): array                              // Абстрактный
static afterTableCreate(Medoo $db): void
static freeUpMemory(): void

// Статические методы -- события
static addOnSaveHandler(callable $handler, string $name = null): void
static removeOnSaveHandler(string $name): void

// Защищённые хуки
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

## Зависимости

| Пакет | Версия | Назначение |
|-------|--------|------------|
| `catfan/medoo` | ^1.7 | Легковесный фреймворк для работы с базами данных с поддержкой SQLite |
| `symfony/console` | ^5.0 | Инфраструктура консольных команд для `CreateTablesCommand` и `TableCleanerCommand` |
| `ramsey/uuid` | ^3.9 | Генерация UUID v4 для идентификаторов моделей |
| `haydenpierce/class-finder` | ^0.4.0 | Автоматическое обнаружение классов моделей в `CreateTablesCommand` |

## Смотрите также

- [Документация Medoo](https://medoo.in/doc) -- синтаксис запросов для `findByCondition()` и определения `schema()`
- [Medoo Where Clause](https://medoo.in/api/where) -- полный справочник по условиям запросов
- [Medoo Create Table](https://medoo.in/api/create) -- синтаксис определения столбцов, используемый в `schema()`
- [`salesrender/plugin-component-settings`](https://github.com/SalesRender/plugin-component-settings) -- класс `Settings` как реальный пример `SinglePluginModelInterface`
- [`salesrender/plugin-component-access`](https://github.com/SalesRender/plugin-component-access) -- класс `Registration`, использующий `SinglePluginModelInterface`
