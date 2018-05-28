---
layout: project
version: 1.x
title: Data mapper
---
# Data mapper

A data mapper is an instance of a class that implements the `Opis\Orm\IDataMapper` interface.
This interface provides several methods that can be used to manipulate the data
associated with an entity. This data represents a row that is or will be stored into a table.

## Handling columns

Getting the value of a column is done with the help of the `getColumn` method.

```php
class User extends Entity
{
    public function name(): string
    {
        return $this->orm()->getColumn('name');
    }
}
```

Setting the value of a column is done by using the `setColumn` method

```php
class User extends Entity
{
    public function setName(string $value): void
    {
        $this->orm()->setColumn('name', $value);
    }
}
```
