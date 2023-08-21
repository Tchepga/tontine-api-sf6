<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230815091526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE cash_flow_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE deposit_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE event_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE loan_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE meeting_report_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE sanction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE status_loan_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tontine_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tontine_config_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE cash_flow (id INT NOT NULL, currency VARCHAR(255) NOT NULL, balance INT NOT NULL, dividendes INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE deposit (id INT NOT NULL, author_id INT NOT NULL, cash_flow_id INT NOT NULL, creation_date DATE NOT NULL, amount INT NOT NULL, currency VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, reasons TEXT DEFAULT NULL, is_activated BOOLEAN DEFAULT NULL, delete_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_95DB9D39F675F31B ON deposit (author_id)');
        $this->addSql('CREATE INDEX IDX_95DB9D39205A1F47 ON deposit (cash_flow_id)');
        $this->addSql('CREATE TABLE event (id INT NOT NULL, author_id INT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7F675F31B ON event (author_id)');
        $this->addSql('CREATE TABLE event_member (event_id INT NOT NULL, member_id INT NOT NULL, PRIMARY KEY(event_id, member_id))');
        $this->addSql('CREATE INDEX IDX_427D8D2A71F7E88B ON event_member (event_id)');
        $this->addSql('CREATE INDEX IDX_427D8D2A7597D3FE ON event_member (member_id)');
        $this->addSql('CREATE TABLE loan (id INT NOT NULL, author_id INT NOT NULL, cash_flow_id INT DEFAULT NULL, amount INT NOT NULL, creation_date DATE NOT NULL, status VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, redemption_date DATE NOT NULL, update_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, voters JSON DEFAULT NULL, rate DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C5D30D03F675F31B ON loan (author_id)');
        $this->addSql('CREATE INDEX IDX_C5D30D03205A1F47 ON loan (cash_flow_id)');
        $this->addSql('CREATE TABLE meeting_report (id INT NOT NULL, author_id INT NOT NULL, tontine_id INT NOT NULL, content TEXT NOT NULL, creation_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, update_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C44746C6F675F31B ON meeting_report (author_id)');
        $this->addSql('CREATE INDEX IDX_C44746C6DEB5C9FD ON meeting_report (tontine_id)');
        $this->addSql('CREATE TABLE member (id INT NOT NULL, username VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone INT NOT NULL, country VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70E4FA78F85E0677 ON member (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70E4FA78444F97DD ON member (phone)');
        $this->addSql('CREATE TABLE sanction (id INT NOT NULL, member_id INT DEFAULT NULL, tontine_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, reason VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6D6491AF7597D3FE ON sanction (member_id)');
        $this->addSql('CREATE INDEX IDX_6D6491AFDEB5C9FD ON sanction (tontine_id)');
        $this->addSql('CREATE TABLE status_loan (id INT NOT NULL, tontine_id INT NOT NULL, loan_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FDD5A097DEB5C9FD ON status_loan (tontine_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FDD5A097CE73868F ON status_loan (loan_id)');
        $this->addSql('CREATE TABLE tontine (id INT NOT NULL, fund_id INT DEFAULT NULL, configuration_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, legacy TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_activated BOOLEAN DEFAULT NULL, delete_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3F164B7F25A38F89 ON tontine (fund_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3F164B7F73F32DD8 ON tontine (configuration_id)');
        $this->addSql('COMMENT ON COLUMN tontine.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE tontine_member (tontine_id INT NOT NULL, member_id INT NOT NULL, PRIMARY KEY(tontine_id, member_id))');
        $this->addSql('CREATE INDEX IDX_B180CD43DEB5C9FD ON tontine_member (tontine_id)');
        $this->addSql('CREATE INDEX IDX_B180CD437597D3FE ON tontine_member (member_id)');
        $this->addSql('CREATE TABLE tontine_config (id INT NOT NULL, description TEXT DEFAULT NULL, interval VARCHAR(255) NOT NULL, type_tontine VARCHAR(255) NOT NULL, person_per_movement INT NOT NULL, max_duration_loan INT DEFAULT NULL, default_loan_rate DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT FK_95DB9D39F675F31B FOREIGN KEY (author_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT FK_95DB9D39205A1F47 FOREIGN KEY (cash_flow_id) REFERENCES cash_flow (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7F675F31B FOREIGN KEY (author_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_member ADD CONSTRAINT FK_427D8D2A71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_member ADD CONSTRAINT FK_427D8D2A7597D3FE FOREIGN KEY (member_id) REFERENCES member (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03F675F31B FOREIGN KEY (author_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03205A1F47 FOREIGN KEY (cash_flow_id) REFERENCES cash_flow (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meeting_report ADD CONSTRAINT FK_C44746C6F675F31B FOREIGN KEY (author_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meeting_report ADD CONSTRAINT FK_C44746C6DEB5C9FD FOREIGN KEY (tontine_id) REFERENCES tontine (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sanction ADD CONSTRAINT FK_6D6491AF7597D3FE FOREIGN KEY (member_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sanction ADD CONSTRAINT FK_6D6491AFDEB5C9FD FOREIGN KEY (tontine_id) REFERENCES tontine (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE status_loan ADD CONSTRAINT FK_FDD5A097DEB5C9FD FOREIGN KEY (tontine_id) REFERENCES tontine (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE status_loan ADD CONSTRAINT FK_FDD5A097CE73868F FOREIGN KEY (loan_id) REFERENCES loan (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tontine ADD CONSTRAINT FK_3F164B7F25A38F89 FOREIGN KEY (fund_id) REFERENCES cash_flow (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tontine ADD CONSTRAINT FK_3F164B7F73F32DD8 FOREIGN KEY (configuration_id) REFERENCES tontine_config (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tontine_member ADD CONSTRAINT FK_B180CD43DEB5C9FD FOREIGN KEY (tontine_id) REFERENCES tontine (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tontine_member ADD CONSTRAINT FK_B180CD437597D3FE FOREIGN KEY (member_id) REFERENCES member (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE cash_flow_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE deposit_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE event_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE loan_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE meeting_report_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE member_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE sanction_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE status_loan_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tontine_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tontine_config_id_seq CASCADE');
        $this->addSql('ALTER TABLE deposit DROP CONSTRAINT FK_95DB9D39F675F31B');
        $this->addSql('ALTER TABLE deposit DROP CONSTRAINT FK_95DB9D39205A1F47');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA7F675F31B');
        $this->addSql('ALTER TABLE event_member DROP CONSTRAINT FK_427D8D2A71F7E88B');
        $this->addSql('ALTER TABLE event_member DROP CONSTRAINT FK_427D8D2A7597D3FE');
        $this->addSql('ALTER TABLE loan DROP CONSTRAINT FK_C5D30D03F675F31B');
        $this->addSql('ALTER TABLE loan DROP CONSTRAINT FK_C5D30D03205A1F47');
        $this->addSql('ALTER TABLE meeting_report DROP CONSTRAINT FK_C44746C6F675F31B');
        $this->addSql('ALTER TABLE meeting_report DROP CONSTRAINT FK_C44746C6DEB5C9FD');
        $this->addSql('ALTER TABLE sanction DROP CONSTRAINT FK_6D6491AF7597D3FE');
        $this->addSql('ALTER TABLE sanction DROP CONSTRAINT FK_6D6491AFDEB5C9FD');
        $this->addSql('ALTER TABLE status_loan DROP CONSTRAINT FK_FDD5A097DEB5C9FD');
        $this->addSql('ALTER TABLE status_loan DROP CONSTRAINT FK_FDD5A097CE73868F');
        $this->addSql('ALTER TABLE tontine DROP CONSTRAINT FK_3F164B7F25A38F89');
        $this->addSql('ALTER TABLE tontine DROP CONSTRAINT FK_3F164B7F73F32DD8');
        $this->addSql('ALTER TABLE tontine_member DROP CONSTRAINT FK_B180CD43DEB5C9FD');
        $this->addSql('ALTER TABLE tontine_member DROP CONSTRAINT FK_B180CD437597D3FE');
        $this->addSql('DROP TABLE cash_flow');
        $this->addSql('DROP TABLE deposit');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_member');
        $this->addSql('DROP TABLE loan');
        $this->addSql('DROP TABLE meeting_report');
        $this->addSql('DROP TABLE member');
        $this->addSql('DROP TABLE sanction');
        $this->addSql('DROP TABLE status_loan');
        $this->addSql('DROP TABLE tontine');
        $this->addSql('DROP TABLE tontine_member');
        $this->addSql('DROP TABLE tontine_config');
    }
}
