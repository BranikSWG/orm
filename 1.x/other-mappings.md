---
layout: project
version: 1.x
title: Other mappings
---
# Other mappings

## Timestamps

**Opis ORM** allows you to automatically set timestamps to a row by using the
`useTimestamps` method. In order for this method to work, the table must
have a column, named `created_at`, and a another column that allows `null` values, 
named `updated_at`. The type of these columns must be set as being 
`date` and `?date`, respectively.

```php
class Article extends Entity implements IMappableEntity
{
    public static function mapEntity(IEntityMapper $mapper)
    {
        $mapper->cast([
            'created_at' => 'date',
            'updated_at' => '?date'
        ]);
        
        $mapper->useTimestamp();
    }
}
```

Now, every time you create and persist a new entity, the `created_at` column is automatically
filled, and every time you update an existing entity the `updated_at` column is
automatically filled for you.

```php
$article = $orm(Article::class)->create();
// Do something with this entity
// ...
// Save it
$orm->save($article); 
// Change something
$article->setTitle('Updated');
// Persist chnages
$orm->save($article);
```

## Soft delete

Soft deletion is technique used to mark a row as being deleted, without actually deleting it.
In order to be able to use soft deletes, we must use the `useSoftDelete` method.
Having a column named `deleted_at`, that accepts `?date` values, is also mandatory.

```php
class Article extends Entity implements IMappableEntity
{
    public static function mapEntity(IEntityMapper $mapper)
    {
        $mapper->cast([
            'deleted_at' => '?date'
        ]);
        
        $mapper->useSoftDelete();
    }
}
```

Now, evey time you delete an entity that uses soft deletion, instead of being 
permanently removed, it will only be marked as deleted.

```php
$article = $orm(Article::class)->find(123);
// Soft deleted
$orm->delete($article);

// Soft delete unpublished articles
$orm(Article::class)
    ->where('published')->is(false)
    ->delete();
```

Of course, you can force a permanently deletion of an entity

```php
$article = $orm(Article::class)->find(123);
// Permanently deleted
$orm->delete($article, true);

// Permanently delete unpublished articles
$orm(Article::class)
    ->where('published')->is(false)
    ->delete(true);
```
