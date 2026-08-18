<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Database\Seeders;

use App\Domains\CoreIdentityAndAccessKernel\Models\Permission;
use App\Domains\CoreIdentityAndAccessKernel\Models\Role;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::create(['name' => 'Platform Owner', 'email' => 'owner@ctlab.local', 'password' => bcrypt('admin123'), 'is_active' => true]);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@ctlabs.io', 'password' => bcrypt('admin123456789'), 'is_active' => true]);
        $member = User::create(['name' => 'Member', 'email' => 'member@ctlab.local', 'password' => bcrypt('password'), 'is_active' => true]);

        $ownerRole = Role::create(['name' => 'Platform Owner', 'slug' => 'owner', 'description' => 'Full system access', 'is_system' => true]);
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrative access', 'is_system' => true]);
        $memberRole = Role::create(['name' => 'Member', 'slug' => 'member', 'description' => 'Standard member', 'is_system' => true]);

        $permissionSlugs = [
            'core.access', 'core.manage', 'core.settings', 'core.users.manage', 'core.users.view', 'core.roles.manage', 'core.roles.view', 'core.permissions.manage', 'core.permissions.view', 'core.audit.view', 'core.export.manage', 'core.import.manage', 'core.notifications.manage', 'core.search.manage', 'core.widgets.manage', 'core.settings.manage', 'core.integrations.manage',
            'wiki.create', 'wiki.read', 'wiki.update', 'wiki.delete', 'wiki.manage',
            'calendar.create', 'calendar.read', 'calendar.update', 'calendar.delete', 'calendar.manage',
            'people.create', 'people.read', 'people.update', 'people.delete', 'people.manage',
            'documents.create', 'documents.read', 'documents.update', 'documents.delete', 'documents.manage',
            'tags.create', 'tags.read', 'tags.update', 'tags.delete', 'tags.manage',
            'goals.create', 'goals.read', 'goals.update', 'goals.delete', 'goals.manage',
            'planner.create', 'planner.read', 'planner.update', 'planner.delete', 'planner.manage',
            'time-tracker.create', 'time-tracker.read', 'time-tracker.update', 'time-tracker.delete', 'time-tracker.manage',
            'shifts.create', 'shifts.read', 'shifts.update', 'shifts.delete', 'shifts.manage',
            'projects.create', 'projects.read', 'projects.update', 'projects.delete', 'projects.manage',
            'courses.create', 'courses.read', 'courses.update', 'courses.delete', 'courses.manage',
            'certifications.create', 'certifications.read', 'certifications.update', 'certifications.delete', 'certifications.manage',
            'papers.create', 'papers.read', 'papers.update', 'papers.delete', 'papers.manage',
            'flashcards.create', 'flashcards.read', 'flashcards.update', 'flashcards.delete', 'flashcards.manage',
            'bookmarks.create', 'bookmarks.read', 'bookmarks.update', 'bookmarks.delete', 'bookmarks.manage',
            'code-snippets.create', 'code-snippets.read', 'code-snippets.update', 'code-snippets.delete', 'code-snippets.manage',
            'document-archives.create', 'document-archives.read', 'document-archives.update', 'document-archives.delete', 'document-archives.manage',
            'media-tracker.create', 'media-tracker.read', 'media-tracker.update', 'media-tracker.delete', 'media-tracker.manage',
            'assets.create', 'assets.read', 'assets.update', 'assets.delete', 'assets.manage',
            'secrets.create', 'secrets.read', 'secrets.update', 'secrets.delete', 'secrets.manage',
            'subscriptions.create', 'subscriptions.read', 'subscriptions.update', 'subscriptions.delete', 'subscriptions.manage',
            'receipts.create', 'receipts.read', 'receipts.update', 'receipts.delete', 'receipts.manage',
            'habits.create', 'habits.read', 'habits.update', 'habits.delete', 'habits.manage',
            'journal.create', 'journal.read', 'journal.update', 'journal.delete', 'journal.manage',
            'decisions.create', 'decisions.read', 'decisions.update', 'decisions.delete', 'decisions.manage',
            'scorecard.create', 'scorecard.read', 'scorecard.update', 'scorecard.delete', 'scorecard.manage',
            'vision-board.create', 'vision-board.read', 'vision-board.update', 'vision-board.delete', 'vision-board.manage',
            'timeline.create', 'timeline.read', 'timeline.update', 'timeline.delete', 'timeline.manage',
            'workouts.create', 'workouts.read', 'workouts.update', 'workouts.delete', 'workouts.manage',
            'meals.create', 'meals.read', 'meals.update', 'meals.delete', 'meals.manage',
            'sleep-tracker.create', 'sleep-tracker.read', 'sleep-tracker.update', 'sleep-tracker.delete', 'sleep-tracker.manage',
            'medications.create', 'medications.read', 'medications.update', 'medications.delete', 'medications.manage',
            'medical-records.create', 'medical-records.read', 'medical-records.update', 'medical-records.delete', 'medical-records.manage',
            'body-observations.create', 'body-observations.read', 'body-observations.update', 'body-observations.delete', 'body-observations.manage',
            'hydration.create', 'hydration.read', 'hydration.update', 'hydration.delete', 'hydration.manage',
            'expenses.create', 'expenses.read', 'expenses.update', 'expenses.delete', 'expenses.manage',
            'budgets.create', 'budgets.read', 'budgets.update', 'budgets.delete', 'budgets.manage',
            'finance-records.create', 'finance-records.read', 'finance-records.update', 'finance-records.delete', 'finance-records.manage',
            'home-projects.create', 'home-projects.read', 'home-projects.update', 'home-projects.delete', 'home-projects.manage',
            'maintenance.create', 'maintenance.read', 'maintenance.update', 'maintenance.delete', 'maintenance.manage',
            'career-business.create', 'career-business.read', 'career-business.update', 'career-business.delete', 'career-business.manage',
            'entertainment-writing.create', 'entertainment-writing.read', 'entertainment-writing.update', 'entertainment-writing.delete', 'entertainment-writing.manage',
            'devops-records.create', 'devops-records.read', 'devops-records.update', 'devops-records.delete', 'devops-records.manage',
            'security-analytics.create', 'security-analytics.read', 'security-analytics.update', 'security-analytics.delete', 'security-analytics.manage',
            'rules.create', 'rules.read', 'rules.update', 'rules.delete', 'rules.manage',
            'scheduled-tasks.create', 'scheduled-tasks.read', 'scheduled-tasks.update', 'scheduled-tasks.delete', 'scheduled-tasks.manage',
            'file-watcher.create', 'file-watcher.read', 'file-watcher.update', 'file-watcher.delete', 'file-watcher.manage',
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign_role',
            'settings.manage', 'roles.manage',
        ];

        $permissions = [];
        foreach ($permissionSlugs as $slug) {
            $parts = explode('.', $slug);
            $group = $parts[0];
            $name = ucwords(str_replace('-', ' ', $slug));
            $permissions[$slug] = Permission::create(['name' => $name, 'slug' => $slug, 'group' => $group]);
        }

        $adminRole->permissions()->sync(collect($permissions)->pluck('id'));
        $memberRole->permissions()->sync(collect($permissions)->pluck('id'));
        $ownerRole->permissions()->sync(collect($permissions)->pluck('id'));

        $owner->syncRoles([$ownerRole]);
        $admin->syncRoles([$adminRole]);
        $member->syncRoles([$memberRole]);
    }
}
