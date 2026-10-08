<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * The role a person is working in right now. Someone with several roles sees
 * one role at a time: its sidebar, its dashboard and its search results, and
 * switches between them from the sidebar. Admins can also work in every staff
 * area. The workspace only shapes what is shown; access is still checked by
 * the role middleware on each route.
 */
class Workspace
{
    private const SESSION_KEY = 'workspace';

    /** Workspaces in order of preference: a person starts in the first one they can use. */
    private const ORDER = [
        Role::Admin,
        Role::Executive,
        Role::ScientificAdmin,
        Role::FinanceOfficer,
        Role::RegistrationOfficer,
        Role::Reviewer,
        Role::Participant,
        Role::Photographer,
        Role::Judge,
    ];

    /** Staff areas an admin can also work in. */
    private const ADMIN_AREAS = [Role::ScientificAdmin, Role::FinanceOfficer, Role::RegistrationOfficer, Role::Photographer];

    /** Screens that belong to one workspace. Opening one moves the person into it. */
    private const ROUTES = [
        'registration.*' => Role::Participant,
        'abstracts.*' => Role::Participant,
        'reviews.*' => Role::Reviewer,
        'scientific.*' => Role::ScientificAdmin,
        'committee.awards.*' => Role::ScientificAdmin,
        'judging.*' => Role::Judge,
        'finance.*' => Role::FinanceOfficer,
        'desk.*' => Role::RegistrationOfficer,
        'media.albums.*' => Role::Photographer,
        'media.removal-requests.*' => Role::Admin,
        'admin.*' => Role::Admin,
    ];

    /**
     * The workspaces this person can switch between.
     *
     * @return list<Role>
     */
    public static function available(User $user): array
    {
        $admin = $user->hasRole(Role::Admin->value);

        return array_values(array_filter(self::ORDER, fn (Role $role) => $user->hasRole($role->value)
            || ($admin && in_array($role, self::ADMIN_AREAS, true))));
    }

    public static function current(User $user): Role
    {
        $available = self::available($user);
        $chosen = Role::tryFrom((string) session(self::SESSION_KEY));

        // People with no portal role of their own (a session chair, say) use the participant view.
        return in_array($chosen, $available, true) ? $chosen : ($available[0] ?? Role::Participant);
    }

    public static function is(User $user, Role ...$roles): bool
    {
        return in_array(self::current($user), $roles, true);
    }

    /** Move into a workspace. Returns false when the person cannot use it. */
    public static function switchTo(User $user, Role $role): bool
    {
        if (! in_array($role, self::available($user), true)) {
            return false;
        }

        session([self::SESSION_KEY => $role->value]);

        return true;
    }

    /** Follow the person into the workspace of the screen they opened, from a notification or a search result, say. */
    public static function follow(Request $request): void
    {
        $user = $request->user();
        $route = $request->route()?->getName();

        if (! $user || ! $route) {
            return;
        }

        foreach (self::ROUTES as $pattern => $role) {
            if (str($route)->is($pattern)) {
                if (! self::is($user, $role)) {
                    self::switchTo($user, $role);
                }

                return;
            }
        }
    }

    public static function label(Role $role): string
    {
        return match ($role) {
            Role::Participant => 'Participant',
            Role::Reviewer => 'Reviewer',
            Role::ScientificAdmin => 'Scientific committee',
            Role::FinanceOfficer => 'Finance',
            Role::RegistrationOfficer => 'Registration desk',
            Role::Photographer => 'Photo gallery',
            Role::Judge => 'Awards judging',
            Role::Executive => 'Executive',
            Role::Admin => 'Administration',
            default => $role->label(),
        };
    }

    public static function description(Role $role): string
    {
        return match ($role) {
            Role::Participant => 'Your registration, payment, badge and abstracts.',
            Role::Reviewer => 'Abstracts assigned to you for blind review.',
            Role::ScientificAdmin => 'Abstracts, reviewers, decisions and awards.',
            Role::FinanceOfficer => 'Verify payments and confirm registrations.',
            Role::RegistrationOfficer => 'The registry, check-in and badge printing.',
            Role::Photographer => 'Albums, photo uploads and the public gallery.',
            Role::Judge => 'Score the finalists in your awards.',
            Role::Executive => 'The executive summary: registrations, revenue and science.',
            Role::Admin => 'Executive summary, people, roles and settings.',
            default => '',
        };
    }

    public static function icon(Role $role): string
    {
        return match ($role) {
            Role::Participant => 'ticket',
            Role::Reviewer => 'clipboard',
            Role::ScientificAdmin => 'document',
            Role::FinanceOfficer => 'banknotes',
            Role::RegistrationOfficer => 'qr',
            Role::Photographer => 'camera',
            Role::Judge => 'trophy',
            Role::Executive => 'chart',
            Role::Admin => 'shield',
            default => 'user',
        };
    }
}
