<?php

/*
 * @copyright C UAB NFQ Technologies
 *
 * This Software is the property of NFQ Technologies
 * and is protected by copyright law – it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license key
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * Contact UAB NFQ Technologies:
 * E-mail: info@nfq.lt
 * https://www.nfq.lt
 */

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Test\CustomApiTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class UserResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateUser(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/users', [
            'json' => [
                'email' => 'test@example.com',
                'username' => 'testuser',
                'password' => 'testing',
            ]
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->logIn($client, 'test@example.com', 'testing');
    }

    public function testUpdateUser(): void
    {
        $client = self::createClient();
        $user = $this->createUserAndLogIn($client, 'test@example.com', 'testing');

        $client->request('PUT', '/api/users/'.$user->getId(), [
            'json' => [
                'username' => 'newusername',
                'roles' => ['ROLES_ADMIN']
            ],

        ]);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'username' => 'newusername'
        ]);

        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($user->getId());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testGetUser(): void
    {
        $client = self::createClient();
        $user = $this->createUser('test@example.com', 'testing');
        $this->createUserAndLogIn($client, 'authenticated@example.com', 'foo');

        $user->setPhoneNumber('555.342.777');
        $em = $this->getEntityManager();
        $em->flush();

        $client->request('GET', '/api/users/'.$user->getId());
        self::assertJsonContains([
            'username' => 'test'
        ]);

        $data = $client->getResponse()->toArray();
        self::assertArrayNotHasKey('phoneNumber', $data);

        //refresh the user & elevate
        $user = $em->getRepository(User::class)->find($user->getId());
        $user->setRoles(['ROLE_ADMIN']);
        $em->flush();
        $this->logIn($client, 'test@example.com', 'testing');

        $client->request('GET', '/api/users/'.$user->getId());
        self::assertJsonContains([
            'phoneNumber' => '555.342.777'
        ]);
    }
}
