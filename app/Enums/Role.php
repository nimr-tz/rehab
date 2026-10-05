<?php

namespace App\Enums;

/**
 * Roles in the portal. Everyone who registers is a participant; staff roles
 * are granted by an admin. A person can hold several roles.
 */
enum Role: string
{
    case Participant = 'participant';
    case Reviewer = 'reviewer';
    case Chair = 'chair';
    case Rapporteur = 'rapporteur';
    case ChiefRapporteur = 'chief_rapporteur';
    case ScientificAdmin = 'scientific_admin';
    case FinanceOfficer = 'finance_officer';
    case RegistrationOfficer = 'registration_officer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Participant => 'Participant',
            self::Reviewer => 'Reviewer',
            self::Chair => 'Session chair',
            self::Rapporteur => 'Rapporteur',
            self::ChiefRapporteur => 'Chief rapporteur',
            self::ScientificAdmin => 'Scientific admin',
            self::FinanceOfficer => 'Finance officer',
            self::RegistrationOfficer => 'Registration officer',
            self::Admin => 'Administrator',
        };
    }
}
