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

namespace Opis\ORM\Relations;

use Opis\Database\SQL\SQLStatement;
use Opis\ORM\{Entity, EntityManager};
use Opis\ORM\Internal\{DataMapper, EntityMapper, EntityQuery, LazyLoader, Relation, Query};

class BelongsTo extends Relation
{
    /**
     * @param DataMapper $owner
     * @param Entity|null $entity
     */
    public function addRelatedEntity(DataMapper $owner, Entity $entity = null)
    {
        if($entity === null){
            $value = null;
            $mapper = $owner->getEntityManager()->resolveEntityMapper($this->entityClass);
        } else {
            /** @var DataMapper $related */
            $related = (function(){
                return $this->orm();
            })->call($entity);
            $mapper = $related->getEntityMapper();
            $value = $related->getColumn($mapper->getPrimaryKey());
        }

        if($this->foreignKey === null){
            $this->foreignKey = $mapper->getForeignKey();
        }

        $owner->setColumn($this->foreignKey, $value);
    }

    /**
     * @param EntityManager $manager
     * @param EntityMapper $owner
     * @param array $options
     * @return LazyLoader
     */
    protected function getLazyLoader(EntityManager $manager, EntityMapper $owner, array $options)
    {
        $related = $manager->resolveEntityMapper($this->entityClass);

        if($this->foreignKey === null){
            $this->foreignKey = $related->getForeignKey();
        }

        $ids = [];
        foreach ($options['results'] as $result){
            $ids[] = $result[$this->foreignKey];
        }

        $statement = new SQLStatement();
        $select = new EntityQuery($manager, $related, $statement);

        $select->where($related->getPrimaryKey())->in($ids);
        
        if($options['callback'] !== null){
            $options['callback'](new Query($statement));
        }

        $select->with($options['with'], $options['immediate']);

        return new LazyLoader($select, $this->foreignKey, $related->getPrimaryKey(), false, $options['immediate']);
    }
    
    /**
     * @param DataMapper $data
     * @param callable|null $callback
     * @return mixed
     */
    protected function getResult(DataMapper $data, callable $callback = null)
    {
        $manager = $data->getEntityManager();
        $related = $manager->resolveEntityMapper($this->entityClass);

        if($this->foreignKey === null){
            $this->foreignKey = $related->getForeignKey();
        }

        $statement = new SQLStatement();
        $select = new EntityQuery($manager, $related, $statement);

        $select->where($related->getPrimaryKey())->is($data->getColumn($this->foreignKey));

        if($this->queryCallback !== null || $callback !== null){
            $query = $select;//new Query($statement);
            if($this->queryCallback !== null){
                ($this->queryCallback)($query);
            }
            if($callback !== null){
                $callback($query);
            }
        }

        return $select->get();
    }
}