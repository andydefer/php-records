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
| `toArray(): array` | Retourne tous les éléments | `$tags->toArray()` |
| `count(): int` | Nombre d'éléments | `$tags->count()` |
| `isEmpty(): bool` | Collection vide ? | `$tags->isEmpty()` |
| `isNotEmpty(): bool` | Collection non vide ? | `$tags->isNotEmpty()` |
| `getAllowedTypes(): array` | Types autorisés | `$tags->getAllowedTypes()` |
| `firstItem(): mixed` | Premier élément | `$tags->firstItem()` |
| `first(int $limit): static` | N premiers éléments | `$tags->first(3)` |
| `lastItem(): mixed` | Dernier élément | `$tags->lastItem()` |
| `last(int $limit): static` | N derniers éléments | `$tags->last(3)` |

### Transformation et requêtes

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `every(Closure): bool` | Tous les éléments satisfont ? | `$collection->every(fn($i) => $i > 0)` |
| `some(Closure): bool` | Un élément satisfait ? | `$collection->some(fn($i) => $i > 10)` |
| `map(Closure): static` | Transforme chaque élément | `$tags->map(fn($t) => strtoupper($t))` |
| `filter(Closure): static` | Filtre les éléments | `$tags->filter(fn($t) => strlen($t) > 3)` |
| `reject(Closure): static` | Rejette les éléments | `$tags->reject(fn($t) => strlen($t) > 3)` |
| `each(Closure): static` | Action sur chaque élément | `$tags->each(fn($t) => echo $t)` |
| `sort(int): static` | Trie les éléments | `$numbers->sort()` |
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

### Recherche et présence

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

### StringTypedCollection

Collection spécialisée pour les chaînes de caractères.

```php
use AndyDefer\Records\Collections\Utility\StringTypedCollection;

$strings = new StringTypedCollection();
$strings->add('  HELLO  ', 'world', 'PHP', '', '  test  ');
```

#### Méthodes disponibles

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `toLowercase(): self` | Convertit en minuscules | `$strings->toLowercase()` |
| `toUppercase(): self` | Convertit en majuscules | `$strings->toUppercase()` |
| `containsSubstring(string): self` | Filtre par sous-chaîne | `$strings->containsSubstring('ell')` |
| `startsWith(string): self` | Filtre par préfixe | `$strings->startsWith('he')` |
| `endsWith(string): self` | Filtre par suffixe | `$strings->endsWith('lo')` |
| `filterEmpty(): self` | Supprime les chaînes vides | `$strings->filterEmpty()` |
| `trim(string): self` | Supprime les espaces | `$strings->trim()` |
| `truncate(int, string): self` | Limite la longueur | `$strings->truncate(5, '...')` |
| `matchingRegex(string): self` | Filtre par regex | `$strings->matchingRegex('/^\d+$/')` |
| `join(string): string` | Joint toutes les chaînes | `$strings->join(', ')` |
| `lengths(): TypedCollection<int>` | Longueurs des chaînes | `$strings->lengths()` |
| `pad(int, string, int): self` | Padde les chaînes | `$strings->pad(10, '-')` |
| `replace(string|array, string|array): self` | Remplace des valeurs | `$strings->replace('hello', 'hi')` |
| `firstCharacter(): self` | Premier caractère | `$strings->firstCharacter()` |
| `lastCharacter(): self` | Dernier caractère | `$strings->lastCharacter()` |
| `substring(int, ?int): self` | Extrait une sous-chaîne | `$strings->substring(0, 3)` |
| `countMatchingRegex(string): int` | Compte les regex | `$strings->countMatchingRegex('/\d/')` |
| `hasMatchingRegex(string): bool` | Vérifie si match | `$strings->hasMatchingRegex('/\d/')` |
| `uniqueCaseInsensitive(): self` | Valeurs uniques (insensible) | `$strings->uniqueCaseInsensitive()` |
| `sortCaseInsensitive(bool): self` | Tri insensible | `$strings->sortCaseInsensitive()` |
| `removeWhitespace(): self` | Supprime les espaces | `$strings->removeWhitespace()` |
| `slugify(): self` | Convertit en slug URL | `$strings->slugify()` |
| `wrap(string, ?string): self` | Encadre les chaînes | `$strings->wrap('[', ']')` |
| `removePrefix(string): self` | Supprime un préfixe | `$strings->removePrefix('pre_')` |
| `removeSuffix(string): self` | Supprime un suffixe | `$strings->removeSuffix('_suf')` |

#### Exemples d'utilisation

```php
$strings = new StringTypedCollection();
$strings->add('Hello World!', '  PHP 8  ', 'test@example.com');

// Transformations
$lowercase = $strings->toLowercase(); // ['hello world!', '  php 8  ', 'test@example.com']
$trimmed = $strings->trim(); // ['Hello World!', 'PHP 8', 'test@example.com']
$slugified = $strings->slugify(); // ['hello-world', 'php-8', 'test-example-com']

// Filtrage
$emails = $strings->matchingRegex('/^[^@]+@[^@]+\.[^@]+$/'); // ['test@example.com']
$startsHello = $strings->startsWith('Hello'); // ['Hello World!']

// Manipulation
$wrapped = $strings->wrap('**'); // ['**Hello World!**', '**  PHP 8  **', '**test@example.com**']
$joined = $strings->join(', '); // 'Hello World!,   PHP 8  , test@example.com'

// Suppression de suffixe
$withSuffix = new StringTypedCollection();
$withSuffix->add('user_suffix', 'admin_suffix');
$withoutSuffix = $withSuffix->removeSuffix('_suffix'); // ['user', 'admin']
```

### IntTypedCollection

Collection spécialisée pour les entiers.

```php
use AndyDefer\Records\Collections\Utility\IntTypedCollection;

$numbers = new IntTypedCollection();
$numbers->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
```

#### Méthodes disponibles

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `even(): self` | Nombres pairs | `$numbers->even()` → `[2, 4, 6, 8, 10]` |
| `odd(): self` | Nombres impairs | `$numbers->odd()` → `[1, 3, 5, 7, 9]` |
| `zero(): self` | Zéros | `$numbers->zero()` |
| `nonNegative(): self` | Non négatifs | `$numbers->nonNegative()` |
| `median(): float` | Médiane | `$numbers->median()` → `5.5` |

#### Exemples d'utilisation

```php
$numbers = new IntTypedCollection();
$numbers->add(10, 23, 5, 8, 15, 42, 7);

$evenNumbers = $numbers->even(); // [10, 8, 42]
$oddNumbers = $numbers->odd(); // [23, 5, 15, 7]
$median = $numbers->median(); // 10.0 (après tri: [5,7,8,10,15,23,42])
$positive = $numbers->nonNegative(); // Tous les nombres (aucun négatif)
```

### FloatTypedCollection

Collection spécialisée pour les nombres décimaux.

```php
use AndyDefer\Records\Collections\Utility\FloatTypedCollection;

$floats = new FloatTypedCollection();
$floats->add(1.234, 2.567, 3.891);
```

#### Méthodes disponibles

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `round(int): self` | Arrondit à une précision | `$floats->round(2)` → `[1.23, 2.57, 3.89]` |
| `ceil(): self` | Entier supérieur | `$floats->ceil()` → `[2.0, 3.0, 4.0]` |
| `floor(): self` | Entier inférieur | `$floats->floor()` → `[1.0, 2.0, 3.0]` |
| `format(int): self` | Arrondit (alias de round) | `$floats->format(1)` → `[1.2, 2.6, 3.9]` |

### BoolTypedCollection

Collection spécialisée pour les booléens.

```php
use AndyDefer\Records\Collections\Utility\BoolTypedCollection;

$bools = new BoolTypedCollection();
$bools->add(true, false, true, false, true);
```

#### Méthodes disponibles

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `trueOnly(): self` | Uniquement `true` | `$bools->trueOnly()` → `[true, true, true]` |
| `falseOnly(): self` | Uniquement `false` | `$bools->falseOnly()` → `[false, false]` |
| `countTrue(): int` | Nombre de `true` | `$bools->countTrue()` → `3` |
| `countFalse(): int` | Nombre de `false` | `$bools->countFalse()` → `2` |
| `allTrue(): bool` | Tous `true` ? | `$bools->allTrue()` → `false` |
| `allFalse(): bool` | Tous `false` ? | `$bools->allFalse()` → `false` |
| `anyTrue(): bool` | Au moins un `true` ? | `$bools->anyTrue()` → `true` |
| `anyFalse(): bool` | Au moins un `false` ? | `$bools->anyFalse()` → `true` |

### NumberTypedCollection

Collection pour les nombres mixtes (int + float).

```php
use AndyDefer\Records\Collections\Utility\NumberTypedCollection;

$numbers = new NumberTypedCollection();
$numbers->add(1, 2.5, 3, 4.7, 5);
```

#### Méthodes disponibles

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `positive(): self` | Nombres positifs (> 0) | Hérité de `AbstractNumberTypedCollection` |
| `negative(): self` | Nombres négatifs (< 0) | Hérité de `AbstractNumberTypedCollection` |
| `between(int\|float, int\|float): self` | Intervalle | Hérité de `AbstractNumberTypedCollection` |
| `average(): float` | Moyenne | Hérité de `AbstractNumberTypedCollection` |
| `zero(): self` | Zéros (0 ou 0.0) | `$numbers->zero()` |
| `nonNegative(): self` | Non négatifs (>= 0) | `$numbers->nonNegative()` |
| `areAllIntegers(): bool` | Tous entiers ? | `$numbers->areAllIntegers()` → `false` |
| `hasAnyFloat(): bool` | Au moins un float ? | `$numbers->hasAnyFloat()` → `true` |
| `toFloats(): FloatTypedCollection` | Convertit en floats | `$numbers->toFloats()` → `[1.0, 2.5, 3.0, 4.7, 5.0]` |
| `toIntegers(): IntTypedCollection` | Convertit en ints | `$numbers->toIntegers()` → `[1, 2, 3, 4, 5]` |
| `separateTypes(): array` | Sépare ints et floats | `$numbers->separateTypes()` |

#### Exemples d'utilisation

```php
$numbers = new NumberTypedCollection();
$numbers->add(5, 3.14, 0, -2, 7.5, 0.0);

$positive = $numbers->positive(); // [5, 3.14, 7.5]
$zero = $numbers->zero(); // [0, 0.0]
$nonNegative = $numbers->nonNegative(); // [5, 3.14, 0, 7.5, 0.0]

$allInts = $numbers->areAllIntegers(); // false
$hasFloat = $numbers->hasAnyFloat(); // true

$floats = $numbers->toFloats(); // FloatTypedCollection avec [5.0, 3.14, 0.0, -2.0, 7.5, 0.0]
$ints = $numbers->toIntegers(); // IntTypedCollection avec [5, 3, 0, -2, 7, 0]

$separated = $numbers->separateTypes();
$integers = $separated['integers']; // IntTypedCollection avec [5, 0, -2, 0]
$floatValues = $separated['floats']; // FloatTypedCollection avec [3.14, 7.5]
```

### AbstractNumberTypedCollection

Classe de base pour les collections numériques.

```php
use AndyDefer\Records\Collections\Utility\AbstractNumberTypedCollection;

// Méthodes disponibles dans IntTypedCollection, FloatTypedCollection et NumberTypedCollection
```

#### Méthodes statiques

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `range(start, end, step): static` | Génère une séquence | `IntTypedCollection::range(1, 10, 2)` → `[1, 3, 5, 7, 9]` |

```php
// Génération de séquences
$evenNumbers = IntTypedCollection::range(2, 20, 2); // [2, 4, 6, ..., 20]
$descending = IntTypedCollection::range(10, 1, -1); // [10, 9, 8, ..., 1]
$floats = FloatTypedCollection::range(0, 1, 0.25); // [0, 0.25, 0.5, 0.75, 1.0]
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
    
    public function getFeatured(): self
    {
        return $this->filter(fn($product) => $product->isFeatured === true);
    }
}

// Utilisation
$products = new ProductCollection();
$products->add(
    new ProductRecord(name: 'Laptop', price: 999, stock: 5, category: 'electronics', isFeatured: true),
    new ProductRecord(name: 'Mouse', price: 29, stock: 0, category: 'electronics', isFeatured: false),
    new ProductRecord(name: 'Book', price: 19, stock: 10, category: 'books', isFeatured: true),
);

$totalValue = $products->getTotalPrice();  // 1047
$availableProducts = $products->getInStock();  // Laptop et Book
$electronics = $products->filterByCategory('electronics');  // Laptop et Mouse
$featured = $products->getFeatured(); // Laptop et Book
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

### 8. Utiliser `every()` et `some()` pour les validations

```php
// Vérifier que tous les produits sont en stock
if ($products->every(fn($p) => $p->stock > 0)) {
    // Tous disponibles
}

// Vérifier qu'au moins un produit est en promotion
if ($products->some(fn($p) => $p->isOnSale)) {
    // Appliquer réduction
}
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
    
    public function validateOrder(OrderRecord $order): bool
    {
        return $order->items->every(fn($item) => $item->quantity > 0)
            && $order->items->some(fn($item) => $item->price > 0);
    }
}
```

### Utilisation avancée des StringTypedCollection

```php
final class ContentService
{
    public function processContent(StringTypedCollection $strings): array
    {
        return $strings
            ->trim()
            ->filterEmpty()
            ->toLowercase()
            ->uniqueCaseInsensitive()
            ->slugify()
            ->wrap('**')
            ->join("\n");
    }
    
    public function extractEmails(StringTypedCollection $content): StringTypedCollection
    {
        return $content->matchingRegex('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/');
    }
}

// Utilisation
$content = new StringTypedCollection();
$content->add('  Hello World!  ', '', '  PHP 8  ', 'Contact: john@example.com', 'HELLO WORLD');

$processed = $contentService->processContent($content);
// Retourne: "**hello-world**\n**php-8**\n**contact-john-example-com**"

$emails = $contentService->extractEmails($content);
// Retourne: ['john@example.com']
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
| `toArray()` | `array` | Tous les éléments |
| `count()` | `int` | Nombre d'éléments |
| `isEmpty()` | `bool` | Collection vide ? |
| `isNotEmpty()` | `bool` | Collection non vide ? |
| `getAllowedTypes()` | `array<string>` | Types autorisés |
| `firstItem()` | `mixed|null` | Premier élément |
| `first(int $limit)` | `self` | N premiers éléments |
| `lastItem()` | `mixed|null` | Dernier élément |
| `last(int $limit)` | `self` | N derniers éléments |
| `every(Closure)` | `bool` | Tous satisfont ? |
| `some(Closure)` | `bool` | Un satisfait ? |
| `map(Closure)` | `self` | Transforme chaque élément |
| `filter(Closure)` | `self` | Filtre les éléments |
| `reject(Closure)` | `self` | Rejette les éléments |
| `each(Closure)` | `self` | Exécute une action |
| `sort(int)` | `self` | Trie les éléments |
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

| Méthode | Retour | Description |
|---------|--------|-------------|
| `toLowercase()` | `self` | Convertit en minuscules |
| `toUppercase()` | `self` | Convertit en majuscules |
| `containsSubstring(string)` | `self` | Filtre par sous-chaîne |
| `startsWith(string)` | `self` | Filtre par préfixe |
| `endsWith(string)` | `self` | Filtre par suffixe |
| `filterEmpty()` | `self` | Supprime les chaînes vides |
| `trim(string)` | `self` | Supprime les espaces |
| `truncate(int, string)` | `self` | Limite la longueur |
| `matchingRegex(string)` | `self` | Filtre par regex |
| `join(string)` | `string` | Joint toutes les chaînes |
| `lengths()` | `TypedCollection<int>` | Longueurs des chaînes |
| `pad(int, string, int)` | `self` | Padde les chaînes |
| `replace(string\|array, string\|array)` | `self` | Remplace des valeurs |
| `firstCharacter()` | `self` | Premier caractère |
| `lastCharacter()` | `self` | Dernier caractère |
| `substring(int, ?int)` | `self` | Extrait une sous-chaîne |
| `countMatchingRegex(string)` | `int` | Compte les regex |
| `hasMatchingRegex(string)` | `bool` | Vérifie si match |
| `uniqueCaseInsensitive()` | `self` | Valeurs uniques (insensible) |
| `sortCaseInsensitive(bool)` | `self` | Tri insensible à la casse |
| `removeWhitespace()` | `self` | Supprime les espaces |
| `slugify()` | `self` | Convertit en slug URL |
| `wrap(string, ?string)` | `self` | Encadre les chaînes |
| `removePrefix(string)` | `self` | Supprime un préfixe |
| `removeSuffix(string)` | `self` | Supprime un suffixe |

### IntTypedCollection

| Méthode | Retour | Description |
|---------|--------|-------------|
| `even()` | `self` | Nombres pairs |
| `odd()` | `self` | Nombres impairs |
| `median()` | `float` | Médiane |
| `zero()` | `self` | Zéros |
| `nonNegative()` | `self` | Non négatifs |

### FloatTypedCollection

| Méthode | Retour | Description |
|---------|--------|-------------|
| `round(int)` | `self` | Arrondit à une précision |
| `ceil()` | `self` | Entier supérieur |
| `floor()` | `self` | Entier inférieur |
| `format(int)` | `self` | Arrondit (alias) |

### BoolTypedCollection

| Méthode | Retour | Description |
|---------|--------|-------------|
| `trueOnly()` | `self` | Uniquement `true` |
| `falseOnly()` | `self` | Uniquement `false` |
| `countTrue()` | `int` | Nombre de `true` |
| `countFalse()` | `int` | Nombre de `false` |
| `allTrue()` | `bool` | Tous `true` ? |
| `allFalse()` | `bool` | Tous `false` ? |
| `anyTrue()` | `bool` | Au moins un `true` ? |
| `anyFalse()` | `bool` | Au moins un `false` ? |

### NumberTypedCollection

| Méthode | Retour | Description |
|---------|--------|-------------|
| `positive()` | `self` | Nombres positifs (> 0) |
| `negative()` | `self` | Nombres négatifs (< 0) |
| `between(int\|float, int\|float)` | `self` | Intervalle |
| `average()` | `float` | Moyenne |
| `zero()` | `self` | Zéros (0 ou 0.0) |
| `nonNegative()` | `self` | Non négatifs (>= 0) |
| `areAllIntegers()` | `bool` | Tous entiers ? |
| `hasAnyFloat()` | `bool` | Au moins un float ? |
| `toFloats()` | `FloatTypedCollection` | Convertit en floats |
| `toIntegers()` | `IntTypedCollection` | Convertit en ints |
| `separateTypes()` | `array` | Sépare ints et floats |

### AbstractNumberTypedCollection

| Méthode | Retour | Description |
|---------|--------|-------------|
| `positive()` | `self` | Nombres positifs (> 0) |
| `negative()` | `self` | Nombres négatifs (< 0) |
| `between(int\|float, int\|float)` | `self` | Intervalle |
| `average()` | `float` | Moyenne |
| `range(start, end, step)` | `static` | Génère une séquence |

---

## Licence

MIT © [Andy Defer](https://github.com/andydefer)
