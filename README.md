# PHP Records

**Une bibliothèque de structures de données typées pour la communication interne entre les couches de votre application.**

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## Introduction

**PHP Records** est une bibliothèque qui remplace les tableaux bruts (`array`) par des structures de données **typées**, **immuables** et **prévisibles**.

### Le problème

```php
// ❌ On ne sait pas ce qu'il y a dans ce tableau
function updateUser(array $data): void
{
    // $data['name'] ? $data['email'] ? $data['role'] ?
    // Personne ne le sait vraiment.
}
```

### La solution

```php
// ✅ On sait exactement ce qu'on reçoit
function updateUser(UserRecord $user): void
{
    // $user->name, $user->email, $user->role
    // Le compilateur guide le développeur.
}
```

| Problème des tableaux | Solution avec Record |
|-----------------------|----------------------|
| On ne sait pas ce qu'ils contiennent | Propriétés typées explicites |
| Pas de validation à l'ajout | Types garantis à la construction |
| Documentation implicite | Auto-documenté par le code |
| Refactoring dangereux | Le compilateur guide les modifications |

---

## Installation

```bash
composer require andydefer/php-records
```

### Prérequis

- PHP 8.1 ou supérieur
- Aucune dépendance externe obligatoire

---

## Concept fondamental

### Qu'est-ce qu'un Record ?

Un **Record** est une structure de données typée utilisée pour la **communication interne** entre les couches de l'application (Services, Repositories, Tasks, Workers).

```
Record → Remplace les tableaux bruts par des structures typées et immutables
```

### Philosophie

> **Un Record est un sac de données typé, sans aucune logique métier. Il ne fait que transporter des données d'un point A à un point B.**

### Séparation des responsabilités

| Composant | Rôle |
|-----------|------|
| **Record** | Communication interne (Services, Repositories) |
| **Service** | Logique métier |
| **Data/Resource** | Réponse API (si nécessaire dans votre architecture) |

---

## Les Records

### Définition d'un Record

```php
use AndyDefer\Records\AbstractRecord;

final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly UserRole $role,
        public readonly TypedCollection $tags = new TypedCollection('string'),
    ) {}
}
```

### Règles fondamentales

| Règle | Explication |
|-------|-------------|
| Étendre `AbstractRecord` | Tous les Records doivent hériter de la classe abstraite |
| Nommage `{Description}Record` | Ex: `UserRecord`, `PaymentResultRecord` |
| Propriétés `public` | La sérialisation automatique utilise la réflexion |
| `readonly` recommandé | Immuabilité garantie |
| Pas de logique métier | Ni `isValid()`, ni `save()`, ni autre méthode métier |

### Types autorisés dans un Record

| Type | Exemple | Notes |
|------|---------|-------|
| `int` | `public readonly int $id` | Scalaire |
| `string` | `public readonly string $name` | Scalaire |
| `float` | `public readonly float $price` | Scalaire |
| `bool` | `public readonly bool $isActive` | Scalaire |
| `null` | `public readonly ?string $value` | Nullable |
| `Enum` | `public readonly UserRole $role` | Backed enum recommandé |
| `Record` | `public readonly AddressRecord $address` | Record imbriqué |
| `TypedCollection` | `public readonly TypedCollection $items` | Collection typée |

### Types à éviter (ou à convertir avant)

| Type | Alternative | Pourquoi |
|------|-------------|----------|
| `array` brut | `TypedCollection` | Perte d'information sur le contenu |
| `Model` (Eloquent) | `UserRecord`, `DoctorRecord` | Contient de la logique et des relations |
| `Collection` | `TypedCollection` | Non typée |
| `Carbon` / `DateTime` | `string` ISO 8601 | Contient des comportements |

```php
final class GoodRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $items,   // ✅ TypedCollection<ItemRecord>
        public readonly int $userId,              // ✅ int
        public readonly string $createdAt,        // ✅ string ISO
    ) {}
}
```

### Le Record optionnel : `EmptyRecord`

Pour les cas où un paramètre Record est optionnel, utilisez `EmptyRecord` plutôt que `null` :

```php
use AndyDefer\Records\EmptyRecord;

final class FindByRecord extends AbstractRecord
{
    public function __construct(
        public readonly Recordable $filters = new EmptyRecord(),
        public readonly ?int $limit = 100,
    ) {}
}

// Utilisation - pas de condition ternaire !
$filtersArray = $record->filters->toArray(); // [] si EmptyRecord
```

| Avec `null` | Avec `EmptyRecord` |
|-------------|---------------------|
| `$filters?->toArray() ?? []` | `$filters->toArray()` |
| Condition ternaire partout | Pas de condition |
| Risque d'erreur | Type-safe garanti |

### Sérialisation automatique

`AbstractRecord` fournit trois méthodes de sérialisation :

```php
$record = new UserRecord(
    name: 'John Doe',
    email: 'john@example.com',
    role: UserRole::ADMIN,
    createdAt: '2024-01-15T14:30:00Z',
);

// Insertion en base (conserve les null)
DB::table('users')->insert($record->toArray());

// Update (exclut les champs null)
DB::table('users')->where('id', 1)->update($record->toDatabase());

// Envoi à une API externe
Http::post('https://api.external.com/users', $record->toJson());
```

### Normalisation automatique

| Type d'entrée | Sortie |
|---------------|--------|
| `Record` imbriqué | `array` via `toArray()` |
| `TypedCollection` | `array` typé |
| `BackedEnum` | Valeur scalaire (`$enum->value`) |
| `PureEnum` | Nom de l'enum (`$enum->name`) |
| `DateTimeInterface` | `Y-m-d\TH:i:s\Z` |
| `null` | `null` (conservé) |

### Conversion snake_case

Toutes les clés sont automatiquement converties en `snake_case` :

```php
// Propriété en camelCase dans le Record
public readonly string $emailVerifiedAt;

// Devient 'email_verified_at' dans le tableau
$record->toArray(); // ['email_verified_at' => '2024-01-15T14:30:00Z']
```

---

## Les TypedCollection

### Définition

**TypedCollection** est une collection **type-safe** qui remplace les tableaux bruts. Elle garantit que tous les éléments qu'elle contient sont du type déclaré à la construction.

```php
use AndyDefer\Records\Collections\TypedCollection;

// ✅ Collection de strings
$tags = new TypedCollection('string');
$tags->add('developer', 'laravel', 'php');
```

### Pourquoi remplacer les tableaux ?

| Problème des tableaux | Solution avec TypedCollection |
|-----------------------|-------------------------------|
| On ne sait pas ce qu'ils contiennent | Type explicite (`TypedCollection<string>`) |
| Pas de validation à l'ajout | Validation automatique |
| Modification dangereuse | Type-safe garanti |
| Pas de méthodes utilitaires | Nombreuses méthodes disponibles |

### Types supportés

| Type | Description | Exemple |
|------|-------------|---------|
| `'int'` | Entier | `new TypedCollection('int')` |
| `'string'` | Chaîne | `new TypedCollection('string')` |
| `'float'` | Décimal | `new TypedCollection('float')` |
| `'bool'` | Booléen | `new TypedCollection('bool')` |
| `'null'` | Null | `new TypedCollection('string', 'null')` |
| `Record::class` | Record | `new TypedCollection(UserRecord::class)` |
| `TypedCollection::class` | Collection imbriquée | `new TypedCollection(TypedCollection::class)` |
| `stdClass::class` | Objet simple | `new TypedCollection(stdClass::class)` |

### Types multiples

```php
// Collection acceptant plusieurs types scalaires
$mixed = new TypedCollection('int', 'float', 'string');
$mixed->add(42, 3.14, 'text');

// Collection acceptant Records et scalaires
$items = new TypedCollection(ProductRecord::class, 'string');
$items->add(new ProductRecord(name: 'Laptop'), 'Description');
```

### Règle : Record vs Collection

> **Un Record représente un ÉLÉMENT UNIQUE. Une collection d'éléments utilise `TypedCollection`.**

| Situation | Type à utiliser |
|-----------|-----------------|
| Un seul utilisateur | `UserRecord $user` |
| Plusieurs utilisateurs | `TypedCollection $users` |

```php
final class DashboardDataRecord extends AbstractRecord
{
    public function __construct(
        public readonly UserRecord $currentUser,
        public readonly TypedCollection $recentOrders,  // TypedCollection<OrderRecord>
        public readonly TypedCollection $tags,          // TypedCollection<string>
    ) {}
}
```

### Création d'une collection

```php
// Collection de strings
$tags = new TypedCollection('string');
$tags->add('developer', 'laravel', 'php');

// Collection d'entiers
$ids = new TypedCollection('int');
$ids->add(1, 2, 3, 4, 5);

// Collection de Records
$products = new TypedCollection(ProductRecord::class);
$products->add(new ProductRecord(name: 'Laptop', price: 999));

// Collection de collections (imbriquée)
$nested = new TypedCollection(TypedCollection::class);
$nested->add($tags, $ids);
```

### Méthodes de base

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `add(...$items)` | Ajoute des éléments | `$tags->add('a', 'b', 'c')` |
| `all(): array` | Retourne tous les éléments | `$tags->all()` |
| `toArray(): array` | Retourne tous les éléments | `$tags->toArray()` |
| `count(): int` | Nombre d'éléments | `$tags->count()` |
| `isEmpty(): bool` | Collection vide ? | `$tags->isEmpty()` |
| `isNotEmpty(): bool` | Collection non vide ? | `$tags->isNotEmpty()` |
| `firstItem(): mixed` | Premier élément | `$tags->firstItem()` |
| `first(int $limit): static` | N premiers éléments | `$tags->first(3)` |
| `lastItem(): mixed` | Dernier élément | `$tags->lastItem()` |
| `last(int $limit): static` | N derniers éléments | `$tags->last(3)` |
| `getAllowedTypes(): array` | Types autorisés | `$tags->getAllowedTypes()` |

### Transformation

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `map(Closure): static` | Transforme chaque élément | `$tags->map(fn($t) => strtoupper($t))` |
| `filter(Closure): static` | Filtre les éléments | `$tags->filter(fn($t) => strlen($t) > 3)` |
| `reject(Closure): static` | Rejette les éléments | `$tags->reject(fn($t) => strlen($t) > 3)` |
| `each(Closure): static` | Action sur chaque élément | `$tags->each(fn($t) => echo $t)` |
| `sort(): static` | Trie les éléments | `$numbers->sort()` |
| `sortBy(Closure|string, bool): static` | Trie par clé/fonction | `$products->sortBy('price')` |
| `reverse(): static` | Inverse l'ordre | `$collection->reverse()` |
| `shuffle(): static` | Mélange aléatoirement | `$collection->shuffle()` |

### Calculs

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `sum(?Closure): int|float` | Somme | `$numbers->sum()` ou `$orders->sum(fn($o) => $o->price)` |
| `avg(?Closure): ?float` | Moyenne | `$numbers->avg()` |
| `max(?Closure): mixed` | Valeur max | `$numbers->max()` |
| `min(?Closure): mixed` | Valeur min | `$numbers->min()` |

### Filtrage par type

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `ofType(string): static` | Filtrer par type | `$collection->ofType('string')` |
| `exceptType(string): static` | Exclure un type | `$collection->exceptType('int')` |
| `records(): static` | Filtrer les Records | `$collection->records()` |
| `scalars(): static` | Filtrer les scalaires | `$collection->scalars()` |
| `ofRecord(string): static` | Filtrer par classe Record | `$collection->ofRecord(UserRecord::class)` |
| `anyRecord(): static` | Tous les Records | `$collection->anyRecord()` |
| `getTypes(): static` | Types distincts présents | `$collection->getTypes()` |

### Recherche

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `where(string, mixed): static` | Filtrer par propriété | `$products->where('price', 100)` |
| `whereNotNull(string): static` | Propriété non nulle | `$products->whereNotNull('price')` |
| `whereNull(string): static` | Propriété nulle | `$products->whereNull('price')` |
| `contains(mixed): bool` | Élément existe ? | `$tags->contains('laravel')` |
| `containsType(string): bool` | Type présent ? | `$collection->containsType('int')` |
| `isOnlyType(string): bool` | Tous du même type ? | `$collection->isOnlyType('int')` |

### Slicing et pagination

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `take(int): static` | N premiers | `$collection->take(10)` |
| `skip(int): static` | Ignorer N premiers | `$collection->skip(5)` |
| `slice(int, ?int): static` | Extraire une plage | `$collection->slice(2, 3)` |
| `nth(int, int): static` | Un élément sur N | `$collection->nth(2)` |
| `values(): static` | Réindexer les clés | `$filtered->values()` |

### Manipulation avancée

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `unique(?Closure): static` | Supprimer doublons | `$collection->unique()` |
| `merge(self): static` | Fusionner | `$c1->merge($c2)` |
| `intersect(self): static` | Éléments communs | `$c1->intersect($c2)` |
| `diff(self): static` | Éléments uniques | `$c1->diff($c2)` |
| `flatMap(Closure): static` | Aplatir | `$nested->flatMap(fn($i) => $i)` |
| `filterNull(): static` | Supprimer null | `$collection->filterNull()` |
| `random(int): static` | Éléments aléatoires | `$collection->random(3)` |

### Validation et assertions

| Méthode | Description |
|---------|-------------|
| `isHomogeneous(): bool` | Tous les éléments du même type ? |
| `isHeterogeneous(): bool` | Types différents ? |
| `assertAllOfType(string): self` | Vérifie que tous sont d'un type |
| `assertNotEmpty(): self` | Vérifie non vide |
| `assertContainsType(string): self` | Vérifie qu'un type est présent |
| `assertAllImplement(string): self` | Vérifie l'implémentation |
| `assertScalar(): self` | Vérifie que tous sont scalaires |
| `assertRecords(): self` | Vérifie que tous sont des Records |
| `validate(Closure): self` | Validation personnalisée |

---

## Les collections utilitaires

Le package fournit des collections pré-typées pour les cas d'usage les plus courants.

### Liste des collections

| Classe | Type | Méthodes spécifiques |
|--------|------|---------------------|
| `StringTypedCollection` | `string` | `toLowercase()`, `toUppercase()`, `containsSubstring()`, `startsWith()`, `endsWith()`, `filterEmpty()`, `trim()`, `truncate()` |
| `IntTypedCollection` | `int` | `even()`, `odd()`, `median()`, `zero()`, `nonNegative()` |
| `FloatTypedCollection` | `float` | `round()`, `ceil()`, `floor()`, `format()` |
| `BoolTypedCollection` | `bool` | `trueOnly()`, `falseOnly()`, `countTrue()`, `countFalse()`, `allTrue()`, `allFalse()`, `anyTrue()`, `anyFalse()` |
| `NumberTypedCollection` | `int\|float` | `positive()`, `negative()`, `between()`, `average()`, `zero()`, `nonNegative()` |

### Utilisation

```php
use AndyDefer\Records\Collections\Utility\StringTypedCollection;
use AndyDefer\Records\Collections\Utility\IntTypedCollection;
use AndyDefer\Records\Collections\Utility\BoolTypedCollection;

final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $name,
        public readonly StringTypedCollection $tags = new StringTypedCollection(),
        public readonly IntTypedCollection $counts = new IntTypedCollection(),
        public readonly BoolTypedCollection $flags = new BoolTypedCollection(),
    ) {}
}

// Utilisation des méthodes spécifiques
$tags = new StringTypedCollection();
$tags->add('HELLO', 'WORLD');
$lower = $tags->toLowercase(); // ['hello', 'world']

$numbers = new IntTypedCollection();
$numbers->add(1, 2, 3, 4, 5);
$even = $numbers->even(); // [2, 4]
$median = $numbers->median(); // 3.0

$bools = new BoolTypedCollection();
$bools->add(true, false, true);
$trueCount = $bools->countTrue(); // 2
```

### Création de collections personnalisées

```php
use AndyDefer\Records\Collections\TypedCollection;
use App\Records\ProductRecord;

final class ProductCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(ProductRecord::class);
    }
    
    public function getTotalPrice(): float
    {
        return $this->sum(fn($product) => $product->price);
    }
    
    public function getInStock(): self
    {
        return $this->filter(fn($product) => $product->stock > 0);
    }
    
    public function filterByCategory(string $category): self
    {
        return $this->filter(fn($product) => $product->category === $category);
    }
}

// Utilisation
$products = new ProductCollection();
$products->add(
    new ProductRecord(name: 'Laptop', price: 999, stock: 5, category: 'electronics'),
    new ProductRecord(name: 'Mouse', price: 29, stock: 0, category: 'electronics'),
    new ProductRecord(name: 'Book', price: 19, stock: 10, category: 'books'),
);

$totalValue = $products->getTotalPrice();  // 1047
$availableProducts = $products->getInStock();  // Laptop et Book
$electronics = $products->filterByCategory('electronics');  // Laptop et Mouse
```

---

## L'interface Recordable

```php
namespace AndyDefer\Records;

interface Recordable
{
    public function toArray(): array;
    public function toDatabase(): array;
    public function toJson(): string;
}
```

Tous les Records implémentent automatiquement cette interface via `AbstractRecord`.

### Utilisation dans une signature

```php
// Accepter n'importe quel Record
function processRecord(Recordable $record): void
{
    $data = $record->toArray();
    // ...
}

// Ou spécifiquement un EmptyRecord pour les options
function findUsers(Recordable $filters = new EmptyRecord(): array
{
    $filtersArray = $filters->toArray(); // [] si EmptyRecord
    // ...
}
```

---

## Le trait Enumable

Le trait `Enumable` ajoute des méthodes utilitaires à vos énumérations PHP 8.1+.

```php
use AndyDefer\Records\Traits\Enumable;

enum UserRole: string
{
    use Enumable;
    
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}
```

### Méthodes disponibles

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `values(): array` | Retourne toutes les valeurs | `UserRole::values()` → `['admin', 'user', 'guest']` |
| `names(): array` | Retourne tous les noms | `UserRole::names()` → `['ADMIN', 'USER', 'GUEST']` |
| `typesInOrder(): array` | Retourne les cas dans l'ordre | `UserRole::typesInOrder()` |
| `isValid(string\|int): bool` | Vérifie si une valeur existe | `UserRole::isValid('admin')` → `true` |
| `fromValue(string\|int): ?self` | Récupère un cas par sa valeur | `UserRole::fromValue('admin')` → `UserRole::ADMIN` |

### Exemple complet

```php
enum TestUserStatus
{
    use Enumable;
    
    case ACTIVE;
    case INACTIVE;
    case SUSPENDED;
}

// Pure enum (non-backed)
TestUserStatus::values();    // ['ACTIVE', 'INACTIVE', 'SUSPENDED']
TestUserStatus::names();     // ['ACTIVE', 'INACTIVE', 'SUSPENDED']
TestUserStatus::isValid('ACTIVE');  // true
TestUserStatus::fromValue('ACTIVE'); // TestUserStatus::ACTIVE
```

---

## Bonnes pratiques

### 1. Toujours typer explicitement les `TypedCollection`

```php
// ✅ BON - Type explicite
public readonly TypedCollection $tags = new TypedCollection('string');
```

### 2. Utiliser `EmptyRecord` plutôt que `null`

```php
// ✅ BON
public readonly Recordable $filters = new EmptyRecord();
```

### 3. Préférer les `BackedEnum`

```php
// ✅ Recommandé
enum UserRole: string
{
    case ADMIN = 'admin';
}

// ⚠️ Acceptable mais moins pratique
enum UserStatus
{
    case ACTIVE;
}
```

### 4. Ne pas mettre de logique métier dans un Record

```php
final class UserRecord extends AbstractRecord
{
    // ✅ BON - Que des données
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

### 5. Convertir avant la construction

```php
// Conversion avant le Record
$tags = new TypedCollection('string');
foreach ($user->tags as $tag) {
    $tags->add($tag);
}

return new UserRecord(
    name: $user->name,
    tags: $tags,  // Déjà en TypedCollection
    createdAt: $user->created_at->toISOString(),  // Déjà en string
);
```

### 6. Valeur par défaut pour les collections

```php
// ✅ BON
public readonly TypedCollection $tags = new TypedCollection('string');

// ✅ BON - Collection de Records
public readonly TypedCollection $items = new TypedCollection(ItemRecord::class);
```

### 7. Un Record = un élément, TypedCollection = plusieurs

```php
public readonly UserRecord $user;           // Un utilisateur
public readonly TypedCollection $users;     // Plusieurs utilisateurs
```

---

## Exemples complets

### Record simple

```php
use AndyDefer\Records\AbstractRecord;

final class UserCredentialsRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $rememberMe,
    ) {}
}

// Utilisation
$credentials = new UserCredentialsRecord(
    email: 'john@example.com',
    password: 'secret',
    rememberMe: true,
);

// Insertion
DB::table('login_attempts')->insert($credentials->toArray());
```

### Record avec Enum et TypedCollection

```php
use AndyDefer\Records\AbstractRecord;
use AndyDefer\Records\Collections\TypedCollection;
use App\Enums\UserRole;

final class UserListFilterRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?UserRole $role = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $search = null,
        public readonly TypedCollection $excludedIds = new TypedCollection('int'),
        public readonly ?DashboardFilterRecord $dashboardFilters = null,
    ) {}
}

// Utilisation
$filters = new UserListFilterRecord(
    role: UserRole::ADMIN,
    isActive: true,
    excludedIds: (new TypedCollection('int'))->add(1, 2, 3),
);
```

### Service qui utilise un Record

```php
final class UserService
{
    public function updateUserField(UserCredentialsRecord $credentials): UserUpdateResultRecord
    {
        // On sait exactement ce qu'on reçoit
        $user = User::where('email', $credentials->email)->first();
        
        // Traitement...
        
        return new UserUpdateResultRecord(
            success: true,
            userId: $user->id,
        );
    }
}
```

### Repository avec Record

```php
final class UserRepository
{
    public function create(UserRecord $record): User
    {
        $id = DB::table('users')->insertGetId($record->toArray());
        return User::find($id);
    }
    
    public function update(int $id, UserRecord $record): User
    {
        DB::table('users')->where('id', $id)->update($record->toDatabase());
        return User::find($id);
    }
}
```

### Appel API externe avec Record

```php
final class PaymentGatewayService
{
    public function createPayment(PaymentRequestRecord $request): PaymentResponseRecord
    {
        $response = Http::post(
            'https://api.payment.com/v1/payments',
            $request->toJson()
        );
        
        return new PaymentResponseRecord(
            transactionId: $response->json('transaction_id'),
            status: $response->json('status'),
        );
    }
}
```

### Record avec d'autres Records

```php
final class DashboardContextRecord extends AbstractRecord
{
    public function __construct(
        public readonly UserContextRecord $user,
        public readonly DashboardFilterRecord $filters,
        public readonly string $timezone,
    ) {}
}

// Utilisation
$context = new DashboardContextRecord(
    user: new UserContextRecord(id: 1, name: 'John'),
    filters: new DashboardFilterRecord(dateRange: 'last-30-days'),
    timezone: 'UTC',
);
```

### Manipulation de TypedCollection dans un Service

```php
final class OrderService
{
    public function calculateTotal(OrderRecord $order): float
    {
        return $order->items->sum(fn($item) => $item->price * $item->quantity);
    }
    
    public function getExpensiveItems(OrderRecord $order, float $threshold): TypedCollection
    {
        return $order->items->filter(fn($item) => $item->price > $threshold);
    }
    
    public function getProductNames(OrderRecord $order): TypedCollection
    {
        return $order->items->map(fn($item) => $item->productName);
    }
}
```

---

## API Reference

### AbstractRecord

| Méthode | Retour | Description |
|---------|--------|-------------|
| `toArray()` | `array<string, mixed>` | Convertit en tableau (conserve null) |
| `toDatabase()` | `array<string, mixed>` | Convertit en tableau (exclut null) |
| `toJson()` | `string` | Convertit en JSON |

### TypedCollection

| Méthode | Retour | Description |
|---------|--------|-------------|
| `add(...$items)` | `self` | Ajoute des éléments |
| `all()` | `array` | Tous les éléments |
| `toArray()` | `array` | Tous les éléments |
| `count()` | `int` | Nombre d'éléments |
| `isEmpty()` | `bool` | Collection vide ? |
| `isNotEmpty()` | `bool` | Collection non vide ? |
| `getAllowedTypes()` | `array<string>` | Types autorisés |
| `firstItem()` | `mixed|null` | Premier élément |
| `first(int $limit)` | `self` | N premiers éléments |
| `lastItem()` | `mixed|null` | Dernier élément |
| `last(int $limit)` | `self` | N derniers éléments |
| `map(Closure)` | `self` | Transforme chaque élément |
| `filter(Closure)` | `self` | Filtre les éléments |
| `reject(Closure)` | `self` | Rejette les éléments |
| `each(Closure)` | `self` | Exécute une action |
| `sort()` | `self` | Trie les éléments |
| `sortBy(Closure\|string, bool)` | `self` | Trie par clé/fonction |
| `reverse()` | `self` | Inverse l'ordre |
| `shuffle()` | `self` | Mélange aléatoirement |
| `sum(?Closure)` | `int\|float` | Somme |
| `avg(?Closure)` | `?float` | Moyenne |
| `max(?Closure)` | `mixed` | Valeur max |
| `min(?Closure)` | `mixed` | Valeur min |
| `ofType(string)` | `self` | Filtrer par type |
| `exceptType(string)` | `self` | Exclure un type |
| `records()` | `self` | Filtrer les Records |
| `scalars()` | `self` | Filtrer les scalaires |
| `ofRecord(string)` | `self` | Filtrer par classe Record |
| `anyRecord()` | `self` | Tous les Records |
| `getTypes()` | `self` | Types distincts |
| `where(string, mixed)` | `self` | Filtrer par propriété |
| `whereNotNull(string)` | `self` | Propriété non nulle |
| `whereNull(string)` | `self` | Propriété nulle |
| `contains(mixed)` | `bool` | Élément existe ? |
| `containsType(string)` | `bool` | Type présent ? |
| `isOnlyType(string)` | `bool` | Tous du même type ? |
| `take(int)` | `self` | N premiers |
| `skip(int)` | `self` | Ignorer N premiers |
| `slice(int, ?int)` | `self` | Extraire une plage |
| `nth(int, int)` | `self` | Un élément sur N |
| `values()` | `self` | Réindexer |
| `unique(?Closure)` | `self` | Supprimer doublons |
| `merge(self)` | `self` | Fusionner |
| `intersect(self)` | `self` | Éléments communs |
| `diff(self)` | `self` | Éléments uniques |
| `flatMap(Closure)` | `self` | Aplatir |
| `filterNull()` | `self` | Supprimer null |
| `random(int)` | `self` | Éléments aléatoires |
| `isHomogeneous()` | `bool` | Tous du même type ? |
| `isHeterogeneous()` | `bool` | Types différents ? |
| `assertAllOfType(string)` | `self` | Vérifie le type |
| `assertNotEmpty()` | `self` | Vérifie non vide |
| `assertContainsType(string)` | `self` | Vérifie présence type |
| `assertAllImplement(string)` | `self` | Vérifie interface |
| `assertScalar()` | `self` | Vérifie scalaire |
| `assertRecords()` | `self` | Vérifie Record |
| `validate(Closure)` | `self` | Validation |

### StringTypedCollection

| Méthode | Description |
|---------|-------------|
| `toLowercase(): self` | Convertit en minuscules |
| `toUppercase(): self` | Convertit en majuscules |
| `containsSubstring(string): self` | Filtre par sous-chaîne |
| `startsWith(string): self` | Filtre par préfixe |
| `endsWith(string): self` | Filtre par suffixe |
| `filterEmpty(): self` | Supprime les chaînes vides |
| `trim(): self` | Supprime les espaces |
| `truncate(int, string): self` | Limite la longueur |

### IntTypedCollection

| Méthode | Description |
|---------|-------------|
| `even(): self` | Nombres pairs |
| `odd(): self` | Nombres impairs |
| `median(): float` | Médiane |
| `zero(): self` | Zéro |
| `nonNegative(): self` | Non négatifs |

### FloatTypedCollection

| Méthode | Description |
|---------|-------------|
| `round(int): self` | Arrondit |
| `ceil(): self` | Entier supérieur |
| `floor(): self` | Entier inférieur |
| `format(int): self` | Arrondit |

### BoolTypedCollection

| Méthode | Description |
|---------|-------------|
| `trueOnly(): self` | Uniquement true |
| `falseOnly(): self` | Uniquement false |
| `countTrue(): int` | Nombre de true |
| `countFalse(): int` | Nombre de false |
| `allTrue(): bool` | Tous true ? |
| `allFalse(): bool` | Tous false ? |
| `anyTrue(): bool` | Au moins un true ? |
| `anyFalse(): bool` | Au moins un false ? |

### AbstractNumberTypedCollection

| Méthode | Description |
|---------|-------------|
| `positive(): self` | Nombres positifs (> 0) |
| `negative(): self` | Nombres négatifs (< 0) |
| `between(int\|float, int\|float): self` | Intervalle |
| `average(): float` | Moyenne |
| `range(start, end, step): static` | Génère une séquence |

---

## Licence

MIT © [Andy Defer](https://github.com/andydefer)