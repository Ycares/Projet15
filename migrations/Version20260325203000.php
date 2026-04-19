<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Authentification BDD : password, roles (JSON), blocked ; suppression de la colonne admin.
 */
final class Version20260325203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: password, roles JSON, blocked ; migrer admin bool vers roles ; mot de passe provisoire dev';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD roles JSON NOT NULL DEFAULT \'[]\'::json');
        $this->addSql('ALTER TABLE "user" ADD blocked BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('UPDATE "user" SET roles = \'["ROLE_ADMIN","ROLE_USER"]\'::json WHERE admin = true');
        $this->addSql('UPDATE "user" SET roles = \'["ROLE_USER"]\'::json WHERE admin = false');
        // Mot de passe initial : « password » (à changer en production — voir compte_rendu / README)
        $this->addSql("UPDATE \"user\" SET password = '\$2y\$13\$JxRUb00r62O2rT/7csxeDe8Jevw7B6GTQwnpr4b.skIjGVZ7AvVi6' WHERE password IS NULL");
        $this->addSql('ALTER TABLE "user" ALTER password SET NOT NULL');
        $this->addSql('ALTER TABLE "user" DROP admin');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD admin BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('UPDATE "user" SET admin = true WHERE roles::text LIKE \'%ROLE_ADMIN%\'');
        $this->addSql('UPDATE "user" SET admin = false WHERE roles::text NOT LIKE \'%ROLE_ADMIN%\'');
        $this->addSql('ALTER TABLE "user" DROP password');
        $this->addSql('ALTER TABLE "user" DROP roles');
        $this->addSql('ALTER TABLE "user" DROP blocked');
    }
}
