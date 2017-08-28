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

use Opis\ORM\Entity;

class EntityProxy extends Entity
{
    /**
     * @param Entity $entity
     * @return DataMapper
     */
    public static function getDataMapper(Entity $entity): DataMapper
    {
        return $entity->orm();
    }

    /**
     * @param Entity $entity
     * @return mixed
     */
    public static function getPKValue(Entity $entity)
    {
        $data = $entity->orm();
        return $data->getColumn($data->getEntityMapper()->getPrimaryKey());
    }

    /**
     * @param Entity[] $entities
     * @param string $key
     * @return array
     */
    public static function getForeignKeys(array $entities, string $key): array
    {
        $list = [];
        foreach ($entities as $entity){
            $list[] = $entity->dataMapperArgs[2][$key];
        }
        return $list;
    }

}