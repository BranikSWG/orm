<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\ORM\Core;

use DateTime;
use RuntimeException;
use Opis\Database\SQL\Select;
use Opis\ORM\{Entity, EntityManager};
use Opis\ORM\Relations\{BelongsTo, HasOneOrMany, HasOneOrManyThrough};

class DataMapper
{
    /** @var array  */
    protected $rawColumns;

    /** @var array  */
    protected $columns = [];

    /** @var  LazyLoader[] */
    protected $loaders;

    /** @var EntityManager  */
    protected $manager;

    /** @var EntityMapper  */
    protected $mapper;

    /** @var bool  */
    protected $isReadOnly;

    /** @var bool  */
    protected $isNew;

    /** @var  string|null */
    protected $sequence;

    /** @var array */
    protected $modified = [];

    /** @var array */
    protected $relations = [];

    /** @var bool */
    protected $dehydrated = false;

    /** @var bool  */
    protected $deleted = false;

    /** @var array */
    protected $pendingLinks = [];

    /**
     * DataMapper constructor.
     * @param EntityManager $entityManager
     * @param EntityMapper $entityMapper
     * @param array $columns
     * @param LazyLoader[] $loaders
     * @param bool $isReadOnly
     * @param bool $isNew
     */
    public function __construct(EntityManager $entityManager, EntityMapper $entityMapper, array $columns, array $loaders, bool $isReadOnly, bool $isNew)
    {
        $this->manager = $entityManager;
        $this->mapper = $entityMapper;
        $this->loaders = $loaders;
        $this->isReadOnly = $isReadOnly;
        $this->isNew = $isNew;
        $this->rawColumns = $columns;

        if($isNew && !empty($columns)){
            $this->rawColumns = [];
            $this->assign($columns);
        }
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->manager;
    }

    /**
     * @return EntityMapper
     */
    public function getEntityMapper(): EntityMapper
    {
        return $this->mapper;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return array
     */
    public function getRawColumns(): array
    {
        return $this->rawColumns;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param bool $list
     * @return array
     */
    public function getModifiedColumns(bool $list = true): array
    {
        return $list ? array_keys($this->modified) : $this->modified;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getColumn(string $name)
    {
        if($this->dehydrated){
            $this->hydrate();
        }

        if($this->deleted){
            throw new RuntimeException("The record was deleted");
        }

        if(array_key_exists($name, $this->columns)){
            return $this->columns[$name];
        }

        if(!array_key_exists($name, $this->rawColumns)){
            throw new RuntimeException("Unknown column '$name'");
        }

        $value = $this->rawColumns[$name];
        $casts = $this->mapper->getTypeCasts();

        if(isset($casts[$name])){
            $value = $this->castGet($value, $casts[$name]);
        }

        if($name === $this->mapper->getPrimaryKey()){
            return $this->columns[$name] = $value;
        }

        $getters = $this->mapper->getGetters();

        if(isset($getters[$name])){
            $value = $getters[$name]($value);
        }

        return $this->columns[$name] = $value;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setColumn(string $name, $value)
    {
        if($this->isReadOnly){
            throw new RuntimeException("The record is readonly");
        }

        if($this->deleted){
            throw new RuntimeException("The record was deleted");
        }

        if($this->dehydrated){
            $this->hydrate();
        }

        $casts = $this->mapper->getTypeCasts();
        $setters = $this->mapper->getSetters();

        if(isset($setters[$name])){
            $value = $setters[$name]($value);
        }

        if(isset($casts[$name])){
            $value = $this->castSet($value, $casts[$name]);
        }

        $this->modified[$name] = 1;
        unset($this->columns[$name]);
        $this->rawColumns[$name] = $value;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setRawColumn(string $name, $value)
    {
        $this->modified[$name] = 1;
        unset($this->columns[$name]);
        $this->rawColumns[$name] = $value;
    }

    /**
     * @param string $name
     * @param callable|null $callback
     * @return mixed
     */
    public function getRelated(string $name, callable $callback = null)
    {
        if(array_key_exists($name, $this->relations)){
            return $this->relations[$name];
        }

        $relations = $this->mapper->getRelations();

        if(!isset($relations[$name])){
            throw new RuntimeException("Unknown relation '$name'");
        }

        $this->hydrate();

        if(isset($this->relations[$name])){
            return $this->relations[$name];
        }

        if(isset($this->loaders[$name])){
            return $this->relations[$name] = $this->loaders[$name]->getResult($this);
        }

        return $this->relations[$name] = Proxy::instance()->getRelationResult($relations[$name], $this, $callback);
    }

    /**
     * @param string $relation
     * @param Entity|null $entity
     */
    public function setRelated(string $relation, Entity $entity = null)
    {
        $relations = $this->mapper->getRelations();

        if(!isset($relations[$relation])){
            throw new RuntimeException("Unknown relation '$relation'");
        }

        $rel = $relations[$relation];

        /** @var $rel BelongsTo|HasOneOrMany */
        if(!($rel instanceof BelongsTo) && !($rel instanceof HasOneOrMany)){
            throw new RuntimeException("Unsupported relation type");
        }

        if($entity === null && !($rel instanceof BelongsTo)){
            throw new RuntimeException("Unsupported relation type");
        }

        $rel->addRelatedEntity($this, $entity);
    }

    /**
     * @param string $relation
     * @param $items
     */
    public function link(string $relation, $items)
    {
        $relations = $this->mapper->getRelations();
        if(!isset($relations[$relation])){
            throw new RuntimeException("Unknown relation '$relation'");
        }

        /** @var $rel HasOneOrManyThrough */
        if(!(($rel = $relations[$relation]) instanceof HasOneOrManyThrough)){
            throw new RuntimeException("Unsupported relation type");
        }

        if($this->isNew){
            $this->pendingLinks[] = [
                'relation' => $rel,
                'items' => $items,
                'link' => true,
            ];
            return;
        }

        $rel->link($this, $items);
    }

    /**
     * @param string $relation
     * @param $items
     */
    public function unlink(string $relation, $items)
    {
        $relations = $this->mapper->getRelations();
        if(!isset($relations[$relation])){
            throw new RuntimeException("Unknown relation '$relation'");
        }

        /** @var $rel HasOneOrManyThrough */
        if(!(($rel = $relations[$relation]) instanceof HasOneOrManyThrough)){
            throw new RuntimeException("Unsupported relation type");
        }

        if($this->isNew){
            $this->pendingLinks[] = [
                'relation' => $rel,
                'items' => $items,
                'link' => false,
            ];
            return;
        }

        $rel->unlink($this, $items);
    }

    /**
     * @param array $columns
     */
    public function assign(array $columns)
    {
        if(null !== $fillable = $this->mapper->getFillableColumns()){
            $columns = array_intersect_key($columns, array_flip($fillable));
        } elseif (null !== $guarded = $this->mapper->getGuardedColumns()){
            $columns = array_diff_key($columns, array_flip($guarded));
        }
        foreach ($columns as $name => $value){
            $this->setColumn($name, $value);
        }
    }

    /**
     * @param $id
     * @return bool
     */
    protected function markAsSaved($id): bool
    {
        $pk = $this->mapper->getPrimaryKey();

        if ($pk->isComposite()) {
            $this->rawColumns[$pk->columns()[0]] = $id;
        } else {
            foreach ($pk->columns() as $pk_index => $pk_column) {
                $this->rawColumns[$pk_column] = $id[$pk_index];
            }
        }

        $this->dehydrated = true;
        $this->isNew = false;
        $this->modified = [];
        if(!empty($this->pendingLinks)){
            $this->executePendingLinkage();
        }
        return true;
    }

    /**
     * @param string|null $updatedAt
     * @return bool
     */
    protected function markAsUpdated(string $updatedAt =  null): bool
    {
        if($updatedAt !== null){
            unset($this->columns['updated_at']);
            $this->rawColumns['updated_at'] = $updatedAt;
        }
        $this->modified = [];
        return true;
    }

    /**
     * @return bool
     */
    protected function markAsDeleted(): bool
    {
        return $this->deleted = true;
    }

    /**
     * @param $value
     * @param string $cast
     * @return mixed
     */
    protected function castGet($value, string $cast)
    {
        $originalCast = $cast;

        if($cast[0] === '?'){
            if($value === null){
                return null;
            }
            $cast = substr($cast, 1);
        }

        switch ($cast){
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'string':
                return (string) $value;
            case 'date':
                return DateTime::createFromFormat($this->manager->getDateFormat(), $value);
            case 'json':
                return json_decode($value);
            case 'json-assoc':
                return json_decode($value, true);
        }

        throw new RuntimeException("Invalid cast type '$originalCast'");
    }

    /**
     * @param $value
     * @param string $cast
     * @return float|int|string
     */
    protected function castSet($value, string $cast)
    {
        $originalCast = $cast;

        if($cast[0] === '?'){
            if($value === null){
                return null;
            }
            $cast = substr($cast, 1);
        }

        switch ($cast){
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (int) $value;
            case 'string':
                return (string) $value;
            case 'date':
                /** @var $value DateTime */
                return $value->format($this->manager->getDateFormat());
            case 'json':
            case 'json-assoc':
                return json_encode($value);
        }

        throw new RuntimeException("Invalid cast type '$originalCast'");
    }

    /**
     * Hydrate
     */
    protected function hydrate()
    {
        if(!$this->dehydrated){
            return;
        }

        $select = new Select($this->manager->getConnection(), $this->mapper->getTable());

        foreach ($this->mapper->getPrimaryKey()->getValue($this->rawColumns, true) as $pk_column => $pk_value) {
            $select->where($pk_column)->is($pk_value);
        }

        $columns = $select->select()->fetchAssoc()->first();

        if($columns === false){
            $this->deleted = true;
            return;
        }

        $this->rawColumns = $columns;
        $this->columns = [];
        $this->relations = [];
        $this->loaders = [];
        $this->dehydrated = false;
    }

    /**
     * Execute pending linkage
     */
    protected function executePendingLinkage()
    {
        foreach ($this->pendingLinks as $item){
            /** @var HasOneOrManyThrough $rel */
            $rel = $item['relation'];
            if($item['link']){
                $rel->link($this, $item['items']);
            } else {
                $rel->unlink($this, $item['items']);
            }
        }

        $this->pendingLinks = [];
    }
}