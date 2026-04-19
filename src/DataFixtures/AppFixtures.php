<?php

namespace App\DataFixtures;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setName('Ina Admin');
        $admin->setDescription('Compte administrateur');
        $admin->setEmail('admin@example.com');
        $admin->setPassword('password');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setBlocked(false);
        $manager->persist($admin);

        $guestActive = new User();
        $guestActive->setName('Invite Actif');
        $guestActive->setDescription('Invite visible');
        $guestActive->setEmail('guest.active@example.com');
        $guestActive->setPassword('password');
        $guestActive->setRoles(['ROLE_USER']);
        $guestActive->setBlocked(false);
        $manager->persist($guestActive);

        $guestBlocked = new User();
        $guestBlocked->setName('Invite Bloque');
        $guestBlocked->setDescription('Invite bloque');
        $guestBlocked->setEmail('guest.blocked@example.com');
        $guestBlocked->setPassword('password');
        $guestBlocked->setRoles(['ROLE_USER']);
        $guestBlocked->setBlocked(true);
        $manager->persist($guestBlocked);

        $albumAdmin = new Album();
        $albumAdmin->setName('Album Admin');
        $manager->persist($albumAdmin);

        $albumGuests = new Album();
        $albumGuests->setName('Album Invites');
        $manager->persist($albumGuests);

        $mediaAdmin = new Media();
        $mediaAdmin->setTitle('Photo admin');
        $mediaAdmin->setPath('uploads/admin-photo.jpg');
        $mediaAdmin->setUser($admin);
        $mediaAdmin->setAlbum($albumAdmin);
        $manager->persist($mediaAdmin);

        $mediaGuestActive = new Media();
        $mediaGuestActive->setTitle('Photo invite active');
        $mediaGuestActive->setPath('uploads/guest-active-photo.jpg');
        $mediaGuestActive->setUser($guestActive);
        $mediaGuestActive->setAlbum($albumGuests);
        $manager->persist($mediaGuestActive);

        $mediaGuestBlocked = new Media();
        $mediaGuestBlocked->setTitle('Photo invite bloque');
        $mediaGuestBlocked->setPath('uploads/guest-blocked-photo.jpg');
        $mediaGuestBlocked->setUser($guestBlocked);
        $mediaGuestBlocked->setAlbum($albumGuests);
        $manager->persist($mediaGuestBlocked);

        $manager->flush();
    }
}
