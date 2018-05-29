---
layout: project
version: 1.x
title: Relationships
---
# Relationships

## Relationship types

**Opis ORM** allows you to define multiple types of relationships between entities.
Defining a relationship is done by calling the `relation` method, on a entity mapper instance,
and passing the name that uniquely identifies that relation on that entity mapper.
The return value of the method is a factory object that provides several methods 
that will help you to declare the type of the relationship being defined.


### Has one

Setting a `has one` relation is done with the help of the `hasOne` method.

```php
class User extends Entity implements IMappableEntity
{
    public static function mapEntity(IEntityMapper $mapper)
    {
        $mapper->relation('profile')->hasOne(Profile::class);
    }
}
```

### Has many

A `has many` relation is defined by using the `hasMany` method

```php
class User extends Entity implements IMappableEntity
{
    public static function mapEntity(IEntityMapper $mapper)
    {
        $mapper->relation('articles')->hasMany(Article::class);
    }
}
```

### Belongs to

This is the inverse relation of the `has one` or `has many` relationships. It
is defined with the help of the `belongsTo` method

```php
class Article extends Entity implements IMappableEntity
{
    public static function mapEntity(IEntityMapper $mapper)
    {
        $mapper->relation('author')->belongsTo(User::class);
    }
}
```