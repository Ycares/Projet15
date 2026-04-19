<?php

namespace App\Tests\Functional;

use App\Entity\Album;
use App\Entity\User;
use App\Tests\Support\DatabaseWebTestCase;

class FrontOfficeBlockedGuestTest extends DatabaseWebTestCase
{
    public function testGuestsPageDoesNotDisplayBlockedGuest(): void
    {
        $crawler = $this->client->request('GET', '/guests');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Invite Actif', $this->client->getResponse()->getContent() ?? '');
        self::assertStringNotContainsString('Invite Bloque', $this->client->getResponse()->getContent() ?? '');
        self::assertGreaterThanOrEqual(1, $crawler->filter('.guest')->count());
    }

    public function testGuestPageReturns404ForBlockedGuest(): void
    {
        $blockedGuest = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => 'guest.blocked@example.com',
        ]);

        self::assertNotNull($blockedGuest);

        $this->client->request('GET', '/guest/' . $blockedGuest->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testPortfolioPagesDoNotDisplayBlockedGuestMedia(): void
    {
        $albumGuests = $this->entityManager->getRepository(Album::class)->findOneBy([
            'name' => 'Album Invites',
        ]);

        self::assertNotNull($albumGuests);

        $this->client->request('GET', '/portfolio');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Photo invite bloque', $this->client->getResponse()->getContent() ?? '');

        $this->client->request('GET', '/portfolio/' . $albumGuests->getId());
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Photo invite active', $this->client->getResponse()->getContent() ?? '');
        self::assertStringNotContainsString('Photo invite bloque', $this->client->getResponse()->getContent() ?? '');
    }
}
