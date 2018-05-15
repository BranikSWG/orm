<?php
/* ===========================================================================
 * Copyright 2018 The Opis Project
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

namespace Opis\ORM\Test;

use Opis\ORM\Test\Entities\Tag;
use Opis\ORM\Test\Entities\User;
use function Opis\ORM\Test\{
    entityManager as em,
    query as entity
};
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    public function testUpdate()
    {
        /** @var User $user */
        $user = entity(User::class)->find(1);
        $user->setAge(33);
        $this->assertTrue(em()->save($user));
        $this->assertEquals(33, $user->age());
        /** @var User $user */
        $user = entity(User::class)->find(1);
        $this->assertEquals(33, $user->age());
    }

    public function testFailUpdatePrimaryKeyIfNotNew()
    {
        /** @var Tag $tag */
        $tag = entity(Tag::class)->find('tag3');
        $tag->setName('foo');
        $this->assertTrue(em()->save($tag));
        $this->assertEquals('tag3', $tag->name());
    }

    public function testUpdatePrimaryKeyIfNotNew()
    {
        $success = entity(Tag::class)
            ->where('id')->is('tag3')
            ->update(['id' => 'foo']);
        $this->assertEquals(1, $success);
        /** @var Tag $tag */
        $tag = entity(Tag::class)->find('foo');
        $this->assertNotNull($tag);
        $this->assertNull(entity(Tag::class)->find('tag3'));
        $this->assertEquals('foo', $tag->name());
    }
}