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

use Closure;
use Opis\ORM\Relations\{
    BelongsTo, HasOneOrManyThrough, HasOneOrMany, ShareOneOrMany
};

class RelationFactory
{
    /** @var  string */
    protected $name;

    /** @var  Closure */
    protected $callback;

    /**
     * RelationFactory constructor.
     * @param string $name
     * @param Closure $callback
     */
    public function __construct(string $name, Closure $callback)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * @param string $entityClass
     * @param ForeignKey|null $foreignKey
     * @return Relation
     */
    public function hasOne(string $entityClass, ForeignKey $foreignKey = null): Relation
    {
        $relation = new HasOneOrMany($entityClass, $foreignKey);
        $callback = $this->callback;
        return $callback($this->name, $relation);
    }

    /**
     * @param string $entityClass
     * @param ForeignKey|null $foreignKey
     * @return Relation
     */
    public function hasMany(string $entityClass, ForeignKey $foreignKey = null): Relation
    {
        $relation = new HasOneOrMany($entityClass, $foreignKey, true);
        $callback = $this->callback;
        return $callback($this->name, $relation);
    }

    /**
     * @param string $entityClass
     * @param ForeignKey|null $foreignKey
     * @return Relation
     */
    public function belongsTo(string $entityClass, ForeignKey $foreignKey = null): Relation
    {
        $relation = new BelongsTo($entityClass, $foreignKey);
        $callback = $this->callback;
        return $callback($this->name, $relation);
    }

    /**
     * @param string $entityClass
     * @param ForeignKey|null $foreignKey
     * @param Junction|null $junction
     * @return Relation
     */
    public function shareOne(string $entityClass, ForeignKey $foreignKey = null, Junction $junction = null): Relation
    {
        $relation = new ShareOneOrMany($entityClass, $foreignKey, $junction);
        $callback = $this->callback;
        return $callback($this->name, $relation);
    }

    /**
     * @param string $entityClass
     * @param ForeignKey|null $foreignKey
     * @param Junction|null $junction
     * @return Relation
     */
    public function shareMany(string $entityClass, ForeignKey $foreignKey = null, Junction $junction = null): Relation
    {
        $relation = new ShareOneOrMany($entityClass, $foreignKey, $junction, true);
        $callback = $this->callback;
        return $callback($this->name, $relation);
    }
}