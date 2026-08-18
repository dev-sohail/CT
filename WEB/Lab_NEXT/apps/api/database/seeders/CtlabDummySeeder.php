<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CtlabDummySeeder extends Seeder
{
    private int $u1 = 1;
    private int $u2 = 2;
    private int $u3 = 3;

    public function run(): void
    {
        $this->seedWiki();
        $this->seedSettings();
        $this->seedCalendar();
        $this->seedPeople();
        $this->seedDocuments();
        $this->seedTags();
        $this->seedAuditLogs();
        $this->seedSearchIndex();
        $this->seedNotifications();
        $this->seedExports();
        $this->seedWidgets();
        $this->seedRules();
        $this->seedScheduledTasks();
        $this->seedFileWatcher();
        $this->seedPlanner();
        $this->seedGoals();
        $this->seedTimeEntries();
        $this->seedShifts();
        $this->seedProjects();
        $this->seedCourses();
        $this->seedCertifications();
        $this->seedPapers();
        $this->seedFlashcards();
        $this->seedBookmarks();
        $this->seedCodeSnippets();
        $this->seedDocumentArchives();
        $this->seedMediaItems();
        $this->seedAssets();
        $this->seedSecrets();
        $this->seedSubscriptions();
        $this->seedReceipts();
        $this->seedHabits();
        $this->seedJournal();
        $this->seedDecisions();
        $this->seedScorecard();
        $this->seedVisionItems();
        $this->seedTimeline();
        $this->seedWorkouts();
        $this->seedMeals();
        $this->seedSleep();
        $this->seedMedications();
        $this->seedMedicalRecords();
        $this->seedBodyObs();
        $this->seedHydration();
        $this->seedExpenses();
        $this->seedFinance();
        $this->seedHome();
        $this->seedCareer();
        $this->seedEntertainment();
        $this->seedDevOps();
        $this->seedSecurity();
        $this->command->info('✅ All dummy data seeded!');
    }

    private int $uidCounter = 0;
    private function uid(): int { return [$this->u1, $this->u2, $this->u3][$this->uidCounter++ % 3]; }
    private function ago(int $max = 90): string { return now()->subDays(rand(1, $max))->toDateTimeString(); }
    private function future(int $max = 90): string { return now()->addDays(rand(1, $max))->toDateTimeString(); }
    private function j(mixed $v): string { return json_encode($v); }

    private function seedWiki(): void
    {
        $this->command->info('  Wiki (26)...');
        DB::table('wiki_workspaces')->insert([
            ['user_id' => $this->u1, 'name' => 'Personal Knowledge Base', 'description' => 'Main wiki', 'color' => '#3b82f6', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Project Notes', 'description' => 'Work projects', 'color' => '#10b981', 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'name' => 'Team Wiki', 'description' => 'Shared docs', 'color' => '#8b5cf6', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('wiki_notebooks')->insert([
            ['workspace_id' => 1, 'name' => 'Learning Notes', 'icon' => '📚', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['workspace_id' => 1, 'name' => 'Meeting Notes', 'icon' => '📝', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['workspace_id' => 2, 'name' => 'Project Alpha', 'icon' => '🚀', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['workspace_id' => 3, 'name' => 'Onboarding', 'icon' => '🤝', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('wiki_sections')->insert([
            ['notebook_id' => 1, 'name' => 'JavaScript', 'icon' => '🟨', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['notebook_id' => 1, 'name' => 'Laravel', 'icon' => '🔴', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['notebook_id' => 2, 'name' => 'Sprint 12', 'icon' => '📋', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['notebook_id' => 3, 'name' => 'Architecture', 'icon' => '🏗️', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['notebook_id' => 4, 'name' => 'Getting Started', 'icon' => '👋', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $pages = [];
        $titles = ['Closures Deep Dive', 'Event Loop', 'Middleware Patterns', 'Sprint Retrospective', 'API Architecture', 'React Hooks', 'Docker Compose', 'Git Workflow', 'Testing Guide', 'Auth Flow', 'Performance Tips', 'Security Checklist', 'Deployment Steps', 'CI/CD Pipeline', 'Code Review Guide', 'Database Design', 'Queue Workers', 'Cache Strategy', 'Logging Best Practices', 'Error Handling'];
        for ($i = 0; $i < 20; $i++) {
            $pages[] = ['section_id' => ($i % 5) + 1, 'title' => $titles[$i], 'content' => fake()->paragraphs(3, true), 'icon' => ['📄','📝','📋','📌'][$i % 4], 'type' => 'article', 'status' => 'published', 'difficulty' => rand(1, 5), 'is_favorite' => $i % 5 === 0, 'sort_order' => $i, 'created_at' => $this->ago(), 'updated_at' => now()];
        }
        DB::table('wiki_pages')->insert($pages);

        $tasks = [];
        $tTitles = ['Write docs', 'Review PR', 'Update deps', 'Fix bug', 'Add tests', 'Deploy', 'Code review', 'Refactor', 'Audit', 'Benchmark'];
        for ($i = 0; $i < 10; $i++) {
            $tasks[] = ['workspace_id' => rand(1, 3), 'title' => $tTitles[$i], 'done' => rand(0, 1), 'due_date' => $this->future(14), 'priority' => ['low', 'medium', 'high'][rand(0, 2)], 'notes' => fake()->sentence(), 'created_at' => $this->ago(30), 'updated_at' => now()];
        }
        DB::table('wiki_tasks')->insert($tasks);

        DB::table('wiki_projects')->insert([
            ['workspace_id' => 1, 'name' => 'Website Redesign', 'description' => 'Overhaul', 'status' => 'active', 'color' => '#ef4444', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['workspace_id' => 2, 'name' => 'Mobile App', 'description' => 'v2', 'status' => 'planned', 'color' => '#3b82f6', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('wiki_reviews')->insert([
            ['workspace_id' => 1, 'page_id' => 1, 'type' => 'spaced', 'status' => 'pending', 'scheduled_for' => $this->future(7), 'last_reviewed_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['workspace_id' => 1, 'page_id' => 2, 'type' => 'spaced', 'status' => 'completed', 'scheduled_for' => now()->subDays(2), 'last_reviewed_at' => now()->subDays(2), 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('wiki_templates')->insert([
            ['name' => 'Meeting Notes', 'slug' => 'meeting-notes', 'description' => 'Template for meetings', 'icon' => '📝', 'blocks' => $this->j(['attendees', 'agenda', 'notes', 'actions']), 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Project Kickoff', 'slug' => 'project-kickoff', 'description' => 'New project template', 'icon' => '🚀', 'blocks' => $this->j(['overview', 'goals', 'team', 'timeline']), 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('wiki_questions')->insert([
            ['workspace_id' => 1, 'title' => 'What is a closure?', 'answer' => 'A function with access to outer scope variables.', 'status' => 'answered', 'difficulty' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['workspace_id' => 1, 'title' => 'How does Sanctum work?', 'answer' => 'Token-based auth for SPAs and mobile apps.', 'status' => 'answered', 'difficulty' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('wiki_references')->insert([
            ['workspace_id' => 1, 'title' => 'Laravel Docs', 'url' => 'https://laravel.com/docs', 'type' => 'documentation', 'author' => 'Laravel', 'notes' => 'Official docs', 'created_at' => now(), 'updated_at' => now()],
            ['workspace_id' => 1, 'title' => 'MDN Web Docs', 'url' => 'https://developer.mozilla.org', 'type' => 'documentation', 'author' => 'Mozilla', 'notes' => 'Web reference', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('wiki_tags')->insert([
            ['name' => 'javascript', 'color' => '#f7df1e', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'laravel', 'color' => '#ff2d20', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'docker', 'color' => '#2496ed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'react', 'color' => '#61dafb', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'devops', 'color' => '#ff6f00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $pt = [];
        for ($i = 1; $i <= 20; $i++) $pt[] = ['page_id' => $i, 'tag_id' => rand(1, 5), 'created_at' => now(), 'updated_at' => now()];
        DB::table('wiki_page_tag')->insert($pt);
    }

    private function seedSettings(): void
    {
        $this->command->info('  Settings (10)...');
        $rows = [];
        foreach ([$this->u1, $this->u2, $this->u3] as $uid) {
            foreach (['theme' => 'dark', 'language' => 'en', 'timezone' => 'UTC', 'date_format' => 'YYYY-MM-DD'] as $k => $v) {
                $rows[] = ['user_id' => $uid, 'key' => $k, 'value' => $this->j($v), 'created_at' => now(), 'updated_at' => now()];
            }
        }
        DB::table('settings')->insert($rows);
    }

    private function seedCalendar(): void
    {
        $this->command->info('  Calendar (6)...');
        $rows = [];
        $t = ['Team Standup', 'Client Meeting', 'Sprint Planning', 'Code Review', 'Gym', 'Dentist', 'Birthday Party', 'Conference', 'Workshop', '1-on-1', 'Product Demo', 'Architecture Review', 'Team Building', 'Holiday', 'Lunch'];
        for ($i = 0; $i < 20; $i++) { [$s, $e] = [$this->ago(60), $this->future(30)]; $rows[] = ['user_id' => $this->uid(), 'title' => $t[array_rand($t)], 'description' => fake()->sentence(), 'location' => fake()->city(), 'starts_at' => $s, 'ends_at' => $e, 'is_all_day' => rand(0, 1), 'timezone' => 'UTC', 'status' => ['confirmed', 'tentative', 'cancelled'][rand(0, 2)], 'created_at' => $this->ago(), 'updated_at' => now()]; }
        DB::table('calendar_events')->insert($rows);
    }

    private function seedPeople(): void
    {
        $this->command->info('  People (7)...');
        $n = ['Alice Johnson', 'Bob Smith', 'Carol White', 'David Lee', 'Eva Brown', 'Frank Garcia', 'Grace Kim', 'Henry Wang', 'Iris Patel', 'Jack Wilson', 'Karen Davis', 'Leo Martinez', 'Mia Thompson', 'Nathan Clark', 'Olivia Hall'];
        $p = []; foreach ($n as $i => $name) { $p[] = ['user_id' => $this->uid(), 'name' => $name, 'nickname' => explode(' ', $name)[0], 'email' => strtolower(str_replace(' ', '.', $name)).'@example.com', 'phone' => fake()->phoneNumber(), 'company' => fake()->company(), 'relationship_type' => ['friend', 'colleague', 'family'][rand(0, 2)], 'created_at' => $this->ago(), 'updated_at' => now()]; }
        DB::table('people')->insert($p);
        $c = []; for ($i = 1; $i <= 15; $i++) { $c[] = ['person_id' => $i, 'type' => 'email', 'value' => fake()->safeEmail(), 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()]; if ($i <= 8) $c[] = ['person_id' => $i, 'type' => 'phone', 'value' => fake()->phoneNumber(), 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()]; }
        DB::table('contact_methods')->insert($c);
        $r = []; for ($i = 1; $i < 8; $i++) $r[] = ['user_id' => $this->uid(), 'person_id' => $i, 'related_person_id' => $i + 1, 'type' => ['colleague', 'friend'][rand(0, 1)], 'created_at' => now(), 'updated_at' => now()];
        DB::table('relationships')->insert($r);
    }

    private function seedDocuments(): void
    {
        $this->command->info('  Documents (4)...');
        $d = []; $n = ['Proposal.pdf', 'Budget.xlsx', 'Notes.docx', 'Diagram.png', 'Guide.pdf', 'Spec.yaml', 'README.md', 'Changelog.txt', 'License.pdf', 'Contract.docx'];
        foreach ($n as $i => $name) { $ext = pathinfo($name, PATHINFO_EXTENSION); $d[] = ['user_id' => $this->uid(), 'name' => $name, 'extension' => $ext, 'mime_type' => 'application/'.$ext, 'size' => rand(1024, 10485760), 'folder' => '/docs', 'current_version' => rand(1, 3), 'created_at' => $this->ago(), 'updated_at' => now()]; }
        DB::table('documents')->insert($d);
        $v = []; for ($i = 1; $i <= 10; $i++) for ($ver = 1; $ver <= 2; $ver++) $v[] = ['document_id' => $i, 'version' => $ver, 'disk' => 'local', 'path' => "docs/$i/v$ver", 'original_name' => $d[$i-1]['name'], 'mime_type' => $d[$i-1]['mime_type'], 'size' => rand(1024, 10485760), 'created_by' => $this->uid(), 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('document_versions')->insert($v);
    }

    private function seedTags(): void
    {
        $this->command->info('  Tags (8)...');
        $t = []; foreach ([['important','#ef4444'],['work','#3b82f6'],['personal','#8b5cf6'],['urgent','#f59e0b'],['reference','#10b981'],['archive','#6b7280'],['tutorial','#14b8a6'],['idea','#ec4899']] as [$n, $c]) $t[] = ['user_id' => $this->uid(), 'name' => $n, 'slug' => Str::slug($n), 'color' => $c, 'created_at' => now(), 'updated_at' => now()];
        DB::table('tags')->insert($t);
    }

    private function seedAuditLogs(): void
    {
        $this->command->info('  Audit Logs (9)...');
        $a = []; $acts = ['user.login', 'user.logout', 'document.create', 'settings.update', 'wiki.page.create', 'export.start']; for ($i = 0; $i < 50; $i++) $a[] = ['user_id' => $this->uid(), 'action' => $acts[array_rand($acts)], 'meta' => $this->j(['detail' => fake()->sentence()]), 'ip' => fake()->ipv4(), 'user_agent' => fake()->userAgent(), 'created_at' => $this->ago(30), 'updated_at' => now()];
        DB::table('audit_logs')->insert($a);
    }

    private function seedSearchIndex(): void
    {
        $this->command->info('  Search (5)...');
        $s = []; foreach (['Laravel Guide', 'Docker Setup', 'React Tutorial', 'API Design', 'Database Schema', 'Git Workflow', 'CI/CD Pipeline', 'Security Tips', 'Performance Guide', 'Testing Manual'] as $i => $t) $s[] = ['user_id' => $this->uid(), 'searchable_type' => 'wiki_page', 'searchable_id' => ($i % 20) + 1, 'title' => $t, 'content' => fake()->paragraph(), 'weight' => rand(1, 10), 'created_at' => now(), 'updated_at' => now()];
        DB::table('search_index')->insert($s);
    }

    private function seedNotifications(): void
    {
        $this->command->info('  Notifications (3)...');
        $n = []; foreach ([$this->u1, $this->u2] as $uid) for ($i = 0; $i < 10; $i++) $n[] = ['id' => Str::uuid(), 'type' => 'App\\Notifications\\TaskDue', 'notifiable_type' => 'App\\Models\\User', 'notifiable_id' => $uid, 'data' => $this->j(['message' => fake()->sentence()]), 'read_at' => rand(0,1) ? now()->subDays(1) : null, 'created_at' => $this->ago(14), 'updated_at' => now()];
        DB::table('notifications')->insert($n);
        $p = []; foreach ([$this->u1, $this->u2, $this->u3] as $uid) foreach (['email', 'push'] as $t) $p[] = ['user_id' => $uid, 'type' => $t, 'enabled' => true, 'channels' => $this->j([$t]), 'created_at' => now(), 'updated_at' => now()];
        DB::table('notification_preferences')->insert($p);
    }

    private function seedExports(): void
    {
        $this->command->info('  Exports (11)...');
        $e = []; $owners = [$this->u1, $this->u2, $this->u3]; for ($i = 0; $i < 6; $i++) $e[] = ['user_id' => $owners[$i % 3], 'status' => ['completed', 'queued'][rand(0,1)], 'format' => 'json', 'domains' => $this->j(['wiki']), 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('exports')->insert($e);
        $im = []; for ($i = 0; $i < 3; $i++) $im[] = ['user_id' => $owners[$i % 3], 'domain' => 'wiki', 'format' => 'json', 'status' => 'completed', 'item_counts' => $this->j(['imported' => rand(10, 100)]), 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('imports')->insert($im);
    }

    private function seedWidgets(): void
    {
        $this->command->info('  Widgets (12)...');
        DB::table('widget_registrations')->insert([
            ['key' => 'clock', 'title' => 'Clock', 'category' => 'utility', 'default_size_x' => 2, 'default_size_y' => 1, 'refresh_interval' => 60, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'weather', 'title' => 'Weather', 'category' => 'info', 'default_size_x' => 2, 'default_size_y' => 2, 'refresh_interval' => 1800, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'tasks', 'title' => 'Tasks', 'category' => 'productivity', 'default_size_x' => 3, 'default_size_y' => 2, 'refresh_interval' => 300, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'habits', 'title' => 'Habits', 'category' => 'health', 'default_size_x' => 4, 'default_size_y' => 2, 'refresh_interval' => 300, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $l = []; $x = 0; foreach (['clock', 'weather', 'tasks', 'habits'] as $i => $k) { $w = [2,2,3,4][$i]; $l[] = ['user_id' => $this->u1, 'widget_key' => $k, 'position_x' => $x, 'position_y' => 0, 'width' => $w, 'height' => 2, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]; $x += $w; if ($x >= 12) $x = 0; }
        DB::table('user_widget_layout')->insert($l);
    }

    private function seedRules(): void
    {
        $this->command->info('  Rules (16)...');
        $r = []; foreach (['Auto-categorize expenses', 'Send digest', 'Archive docs', 'Budget alert', 'Weekly report', 'Notify on due'] as $i => $n) $r[] = ['user_id' => $this->uid(), 'name' => $n, 'is_active' => $i < 4, 'trigger_type' => ['event', 'schedule'][rand(0,1)], 'trigger_config' => $this->j(['event' => 'created']), 'conditions_logic' => 'all', 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('rules')->insert($r);
        $c = []; for ($i = 1; $i <= 6; $i++) $c[] = ['rule_id' => $i, 'type' => 'comparison', 'field' => 'amount', 'operator' => 'gte', 'value' => $this->j(100), 'order' => 0, 'created_at' => now(), 'updated_at' => now()];
        DB::table('rule_conditions')->insert($c);
        $a = []; for ($i = 1; $i <= 6; $i++) $a[] = ['rule_id' => $i, 'type' => 'notify', 'config' => $this->j(['channel' => 'email']), 'order' => 0, 'created_at' => now(), 'updated_at' => now()];
        DB::table('rule_actions')->insert($a);
        $e = []; for ($i = 1; $i <= 6; $i++) for ($j = 0; $j < 3; $j++) $e[] = ['rule_id' => $i, 'triggered' => rand(0,1), 'context' => $this->j(['src' => 'test']), 'results' => $this->j(['ok' => true]), 'created_at' => $this->ago(10), 'updated_at' => now()];
        DB::table('rule_execution_log')->insert($e);
    }

    private function seedScheduledTasks(): void
    {
        $this->command->info('  Scheduled Tasks (17)...');
        DB::table('scheduled_tasks')->insert([
            ['user_id' => $this->u1, 'name' => 'Daily Backup', 'command' => 'backup:run', 'cron_expression' => '0 2 * * *', 'is_active' => true, 'last_status' => 'success', 'created_at' => $this->ago(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Weekly Report', 'command' => 'reports:weekly', 'cron_expression' => '0 8 * * 1', 'is_active' => true, 'last_status' => 'success', 'created_at' => $this->ago(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'name' => 'Cleanup', 'command' => 'files:cleanup', 'cron_expression' => '0 3 * * 0', 'is_active' => false, 'last_status' => 'failed', 'created_at' => $this->ago(), 'updated_at' => now()],
        ]);
        $l = []; for ($i = 1; $i <= 3; $i++) for ($j = 0; $j < 5; $j++) $l[] = ['scheduled_task_id' => $i, 'status' => ['success', 'failed'][rand(0,1)], 'output' => 'Done in '.rand(1,30).'s', 'created_at' => $this->ago(14), 'updated_at' => now()];
        DB::table('scheduled_task_logs')->insert($l);
    }

    private function seedFileWatcher(): void
    {
        $this->command->info('  File Watcher (18)...');
        DB::table('file_watcher_rules')->insert([
            ['user_id' => $this->u1, 'name' => 'Watch Downloads', 'source_disk' => 'local', 'source_path' => '/downloads', 'pattern' => '*.pdf', 'action' => 'move', 'destination_path' => '/docs', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Watch Screenshots', 'source_disk' => 'local', 'source_path' => '/screenshots', 'pattern' => '*.png', 'action' => 'copy', 'destination_path' => '/media', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('file_watcher_logs')->insert([
            ['file_watcher_rule_id' => 1, 'filename' => 'report.pdf', 'status' => 'processed', 'message' => 'Moved', 'created_at' => now(), 'updated_at' => now()],
            ['file_watcher_rule_id' => 2, 'filename' => 'shot.png', 'status' => 'processed', 'message' => 'Copied', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedPlanner(): void
    {
        $this->command->info('  Planner (19)...');
        $p = []; foreach (['Write blog', 'Review PRs', 'Update docs', 'Plan sprint', 'Meeting prep', 'Research', 'Fix bug', 'Design review', 'Demo prep', 'Q planning', 'Portfolio', 'Clean inbox', 'Book travel', 'Gym', 'Read chapter'] as $i => $t) $p[] = ['user_id' => $this->uid(), 'scope' => ['day', 'week', 'month'][rand(0,2)], 'title' => $t, 'description' => fake()->sentence(), 'due_at' => $this->future(14), 'status' => ['pending', 'in_progress', 'completed'][rand(0,2)], 'priority' => ['low', 'medium', 'high'][rand(0,2)], 'sort_order' => $i, 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('planner_items')->insert($p);
    }

    private function seedGoals(): void
    {
        $this->command->info('  Goals (20)...');
        $g = []; foreach (['Learn TypeScript', 'Launch SaaS', 'Run Half Marathon', 'Save $10k', 'Read 24 Books', 'Build OSS'] as $i => $t) $g[] = ['user_id' => $this->uid(), 'title' => $t, 'description' => fake()->sentence(), 'level' => ['goal', 'year'][rand(0,1)], 'start_at' => $this->ago(90), 'target_at' => $this->future(180), 'status' => ['active', 'completed'][rand(0,1)], 'progress' => rand(0, 100), 'sort_order' => $i, 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('goals')->insert($g);
        $k = []; $krs = [['Complete course', 'Build 3 projects', 'Assessment'],['MVP shipped', '100 users', '$1k MRR'],['Run 5k', 'Training plan', 'Sign up'],['Track expenses', 'Cut 30%', 'Open HYSA'],['2 books/mo', 'Write reviews', 'Book club'],['Release v1', '50 stars', 'Docs']];
        for ($gi = 0; $gi < 6; $gi++) foreach ($krs[$gi] as $i => $t) $k[] = ['goal_id' => $gi + 1, 'title' => $t, 'current_value' => rand(0, 100), 'target_value' => 100, 'status' => ['on_track', 'at_risk', 'done'][rand(0,2)], 'progress' => rand(0, 100), 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()];
        DB::table('key_results')->insert($k);
    }

    private function seedTimeEntries(): void
    {
        $this->command->info('  Time Entries (21)...');
        $e = []; foreach (['Deep work', 'Email', 'Code review', 'Standup', 'Debug', 'Docs', 'Learning', 'Client call', 'Planning', 'Lunch'] as $a) { $s = $this->ago(14); $e[] = ['user_id' => $this->uid(), 'started_at' => $s, 'ended_at' => now()->subHours(rand(1,4)), 'activity' => $a, 'category' => ['work', 'personal', 'learning'][rand(0,2)], 'created_at' => now(), 'updated_at' => now()]; }
        DB::table('time_entries')->insert($e);
    }

    private function seedShifts(): void
    {
        $this->command->info('  Shifts (23)...');
        $s = []; for ($i = 0; $i < 10; $i++) { $st = now()->addDays(rand(-7,14))->setTime(rand(6,18),0); $s[] = ['user_id' => $this->uid(), 'title' => ['Morning', 'Afternoon', 'Evening'][rand(0,2)].' Shift', 'starts_at' => $st, 'ends_at' => $st->copy()->addHours(8), 'location' => fake()->city(), 'client' => fake()->company(), 'hourly_rate' => rand(15,75)+0.5, 'status' => ['scheduled', 'completed'][rand(0,1)], 'created_at' => now(), 'updated_at' => now()]; }
        DB::table('shifts')->insert($s);
    }

    private function seedProjects(): void
    {
        $this->command->info('  Projects (24)...');
        $p = []; foreach (['E-Commerce', 'Mobile Banking', 'CRM', 'Analytics', 'CMS', 'Task Tool', 'Social App', 'Inventory'] as $i => $n) $p[] = ['user_id' => $this->uid(), 'name' => $n, 'description' => fake()->sentence(), 'status' => ['planned', 'in_progress', 'completed'][rand(0,2)], 'priority' => ['low', 'medium', 'high'][rand(0,2)], 'client' => fake()->company(), 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('projects')->insert($p);
        $m = []; for ($pi = 1; $pi <= 8; $pi++) for ($j = 0; $j < rand(2,4); $j++) $m[] = ['project_id' => $pi, 'title' => ['Alpha', 'Beta', 'GA', 'Docs'][rand(0,3)].' Release', 'status' => ['pending', 'completed'][rand(0,1)], 'sort_order' => $j, 'created_at' => now(), 'updated_at' => now()];
        DB::table('project_milestones')->insert($m);
    }

    private function seedCourses(): void
    {
        $this->command->info('  Courses (27)...');
        DB::table('courses')->insert([
            ['user_id' => $this->u1, 'title' => 'Advanced Laravel', 'subject' => 'PHP', 'provider' => 'Laracasts', 'status' => 'in_progress', 'hours_target' => 40, 'hours_spent' => 18.5, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'React Mastery', 'subject' => 'JS', 'provider' => 'Udemy', 'status' => 'not_started', 'hours_target' => 60, 'hours_spent' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'title' => 'Docker Deep Dive', 'subject' => 'DevOps', 'provider' => 'Pluralsight', 'status' => 'completed', 'hours_target' => 25, 'hours_spent' => 25, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedCertifications(): void
    {
        $this->command->info('  Certifications (28)...');
        DB::table('certifications')->insert([
            ['user_id' => $this->u1, 'title' => 'AWS Developer', 'issuer' => 'Amazon', 'status' => 'attained', 'issued_at' => '2025-03-15', 'expiry_at' => '2027-03-15', 'skills' => $this->j(['AWS', 'Lambda']), 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'K8s Admin', 'issuer' => 'CNCF', 'status' => 'in_progress', 'issued_at' => null, 'expiry_at' => null, 'skills' => $this->j(['Kubernetes']), 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'title' => 'GCP Professional', 'issuer' => 'Google', 'status' => 'planned', 'issued_at' => null, 'expiry_at' => null, 'skills' => $this->j(['GCP']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedPapers(): void
    {
        $this->command->info('  Papers (29)...');
        $p = []; foreach (['Attention Is All You Need', 'BERT', 'ResNet', 'GANs', 'Dropout'] as $i => $t) $p[] = ['user_id' => $this->uid(), 'title' => $t, 'authors' => $this->j(["Author $i"]), 'year' => (string)rand(2017,2025), 'source' => ['arxiv', 'journal'][rand(0,1)], 'abstract' => fake()->paragraph(), 'status' => ['unread', 'read'][rand(0,1)], 'rating' => rand(1,5), 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('papers')->insert($p);
        $a = []; for ($pi = 1; $pi <= 5; $pi++) for ($j = 0; $j < rand(1,3); $j++) $a[] = ['paper_id' => $pi, 'page' => rand(1,20), 'text' => fake()->sentence(), 'note' => fake()->sentence(), 'color' => ['yellow','green'][rand(0,1)], 'created_at' => now(), 'updated_at' => now()];
        DB::table('paper_annotations')->insert($a);
    }

    private function seedFlashcards(): void
    {
        $this->command->info('  Flashcards (30)...');
        DB::table('decks')->insert([
            ['user_id' => $this->u1, 'title' => 'JavaScript', 'description' => 'Core JS', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'Laravel', 'description' => 'Laravel tips', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'title' => 'Docker', 'description' => 'Docker commands', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $c = []; foreach ([['What is a closure?', 'A function with outer scope access.'],['=== vs ==', 'Strict vs loose equality.'],['Event bubbling?', 'Events propagate up the DOM.'],['Middleware?', 'Request filtering layer.'],['Artisan?', 'CLI for Laravel.'],['Docker compose?', 'Multi-container tool.'],['Virtual DOM?', 'Lightweight DOM copy.'],['npm?', 'Node package manager.']] as $i => [$q,$a]) $c[] = ['deck_id' => ($i % 3) + 1, 'user_id' => $this->uid(), 'question' => $q, 'answer' => $a, 'ease_factor' => 2.5, 'interval_days' => rand(0,30), 'repetitions' => rand(0,10), 'due_at' => $this->future(7), 'created_at' => now(), 'updated_at' => now()];
        DB::table('flashcards')->insert($c);
        $r = []; for ($ci = 1; $ci <= 8; $ci++) for ($j = 0; $j < rand(1,3); $j++) $r[] = ['flashcard_id' => $ci, 'quality' => rand(0,5), 'created_at' => $this->ago(14), 'updated_at' => now()];
        DB::table('flashcard_reviews')->insert($r);
    }

    private function seedBookmarks(): void
    {
        $this->command->info('  Bookmarks (31)...');
        $b = []; foreach ([['https://laravel.com', 'Laravel', 'PHP framework'], ['https://tailwindcss.com', 'Tailwind', 'CSS framework'], ['https://react.dev', 'React', 'UI library'], ['https://docker.com', 'Docker', 'Containers'], ['https://github.com', 'GitHub', 'Code hosting'], ['https://stackoverflow.com', 'SO', 'Q&A'], ['https://medium.com', 'Medium', 'Articles'], ['https://figma.com', 'Figma', 'Design']] as [$u,$t,$d]) $b[] = ['user_id' => $this->uid(), 'url' => $u, 'title' => $t, 'description' => $d, 'status' => ['unread', 'read'][rand(0,1)], 'domain' => parse_url($u, PHP_URL_HOST), 'tags' => $this->j(['tech']), 'created_at' => $this->ago(), 'updated_at' => now()];
        DB::table('bookmarks')->insert($b);
    }

    private function seedCodeSnippets(): void
    {
        $this->command->info('  Code Snippets (32)...');
        DB::table('code_snippets')->insert([
            ['user_id' => $this->u1, 'title' => 'Laravel Route', 'language' => 'php', 'code' => "Route::resource('posts', PostController::class);", 'tags' => $this->j(['laravel']), 'favorite' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'React useState', 'language' => 'javascript', 'code' => 'const [c, setC] = useState(0);', 'tags' => $this->j(['react']), 'favorite' => false, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'title' => 'Docker Compose', 'language' => 'yaml', 'code' => "version: '3'\nservices:\n  app:\n    build: .", 'tags' => $this->j(['docker']), 'favorite' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedDocumentArchives(): void
    {
        $this->command->info('  Doc Archives (34)...');
        DB::table('document_archives')->insert([
            ['user_id' => $this->u1, 'title' => 'Tax Returns 2025', 'category' => 'finance', 'tags' => $this->j(['tax']), 'status' => 'active', 'expires_at' => $this->future(365), 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'Lease Agreement', 'category' => 'legal', 'tags' => $this->j(['housing']), 'status' => 'active', 'expires_at' => $this->future(365), 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'title' => 'Insurance Policy', 'category' => 'insurance', 'tags' => $this->j(['insurance']), 'status' => 'active', 'expires_at' => $this->future(365), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedMediaItems(): void
    {
        $this->command->info('  Media (35/83)...');
        DB::table('media_items')->insert([
            ['user_id' => $this->u1, 'type' => 'book', 'title' => 'Atomic Habits', 'creator' => 'James Clear', 'year' => 2018, 'genre' => 'Self-Help', 'rating' => 9, 'status' => 'finished', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'type' => 'book', 'title' => 'Deep Work', 'creator' => 'Cal Newport', 'year' => 2016, 'genre' => 'Productivity', 'rating' => 8, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'type' => 'movie', 'title' => 'Dune Part Two', 'creator' => 'Villeneuve', 'year' => 2024, 'genre' => 'Sci-Fi', 'rating' => 9, 'status' => 'finished', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'type' => 'series', 'title' => 'Severance', 'creator' => 'Apple TV+', 'year' => 2022, 'genre' => 'Thriller', 'rating' => 10, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'type' => 'podcast', 'title' => 'Syntax FM', 'creator' => 'Wes Bos', 'year' => 2026, 'genre' => 'Tech', 'rating' => 8, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u3, 'type' => 'game', 'title' => 'BG3', 'creator' => 'Larian', 'year' => 2023, 'genre' => 'RPG', 'rating' => 10, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedAssets(): void
    {
        $this->command->info('  Assets (36)...');
        $a = []; foreach ([['MacBook Pro', 'electronics', 'Office', 2499], ['Headphones', 'electronics', 'Office', 349], ['Standing Desk', 'furniture', 'Office', 599], ['iPad Pro', 'electronics', 'Living Room', 1099], ['PS5', 'entertainment', 'Living Room', 499], ['Camera', 'electronics', 'Storage', 2499], ['Monitor', 'electronics', 'Office', 649], ['Keyboard', 'electronics', 'Office', 199]] as [$n,$c,$l,$v]) $a[] = ['user_id' => $this->uid(), 'name' => $n, 'category' => $c, 'location' => $l, 'qr_token' => Str::random(32), 'status' => 'in_place', 'value' => $v, 'purchased_at' => now()->subDays(rand(30,365)), 'tags' => $this->j([$c]), 'created_at' => now(), 'updated_at' => now()];
        DB::table('assets')->insert($a);
    }

    private function seedSecrets(): void
    {
        $this->command->info('  Secrets (37)...');
        DB::table('secret_entries')->insert([
            ['user_id' => $this->u1, 'name' => 'GitHub Token', 'category' => 'api_keys', 'username' => 'user', 'tags' => $this->j(['dev']), 'totp_enabled' => false, 'favorite' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'AWS Root', 'category' => 'accounts', 'username' => 'admin@co.com', 'tags' => $this->j(['aws']), 'totp_enabled' => true, 'favorite' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'name' => 'Production DB', 'category' => 'database', 'username' => 'root', 'tags' => $this->j(['prod']), 'totp_enabled' => true, 'favorite' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedSubscriptions(): void
    {
        $this->command->info('  Subscriptions (38)...');
        DB::table('subscriptions')->insert([
            ['user_id' => $this->u1, 'name' => 'Netflix', 'company' => 'Netflix', 'category' => 'entertainment', 'amount' => 15.99, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'status' => 'active', 'next_billing_at' => now()->addMonth(), 'payment_method' => 'Visa *1234', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Spotify', 'company' => 'Spotify', 'category' => 'entertainment', 'amount' => 9.99, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'status' => 'active', 'next_billing_at' => now()->addMonth(), 'payment_method' => 'Visa *5678', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'GitHub Pro', 'company' => 'GitHub', 'category' => 'dev_tools', 'amount' => 4.00, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'status' => 'active', 'next_billing_at' => now()->addMonth(), 'payment_method' => 'Visa *5678', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'name' => 'AWS', 'company' => 'Amazon', 'category' => 'cloud', 'amount' => 150.00, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'status' => 'active', 'next_billing_at' => now()->addMonth(), 'payment_method' => 'Visa *5678', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedReceipts(): void
    {
        $this->command->info('  Receipts (39)...');
        $r = []; $owners = [$this->u1, $this->u2, $this->u3]; foreach ([['Headphones', 'Best Buy', 'electronics', 349], ['Groceries', 'Whole Foods', 'groceries', 127], ['Gas', 'Shell', 'auto', 52], ['Dinner', 'Olive Garden', 'dining', 89], ['Software', 'JetBrains', 'software', 249], ['Coffee', 'Starbucks', 'dining', 12], ['Shoes', 'Nike', 'shopping', 130], ['Books', 'Amazon', 'education', 45]] as $i => [$t,$m,$c,$a]) $r[] = ['user_id' => $owners[$i % 3], 'title' => $t, 'merchant' => $m, 'category' => $c, 'amount' => $a, 'currency' => 'USD', 'purchased_at' => now()->subDays(rand(1,60)), 'created_at' => now(), 'updated_at' => now()];
        DB::table('receipts')->insert($r);
    }

    private function seedHabits(): void
    {
        $this->command->info('  Habits (40)...');
        DB::table('habits')->insert([
            ['user_id' => $this->u1, 'name' => 'Meditation', 'frequency' => 'daily', 'target_count' => 1, 'color' => '#8b5cf6', 'start_date' => now()->subDays(60), 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Exercise', 'frequency' => 'daily', 'target_count' => 1, 'color' => '#ef4444', 'start_date' => now()->subDays(60), 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Read 30min', 'frequency' => 'daily', 'target_count' => 1, 'color' => '#3b82f6', 'start_date' => now()->subDays(60), 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'name' => 'Drink Water', 'frequency' => 'daily', 'target_count' => 8, 'color' => '#06b6d4', 'start_date' => now()->subDays(60), 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'No Social AM', 'frequency' => 'daily', 'target_count' => 1, 'color' => '#f59e0b', 'start_date' => now()->subDays(60), 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u3, 'name' => 'Journal', 'frequency' => 'daily', 'target_count' => 1, 'color' => '#10b981', 'start_date' => now()->subDays(60), 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $l = []; for ($h = 1; $h <= 6; $h++) for ($d = 0; $d < 20; $d++) if (rand(0,3) > 0) $l[] = ['habit_id' => $h, 'user_id' => [$this->u1,$this->u1,$this->u1,$this->u2,$this->u1,$this->u3][$h-1], 'logged_for' => now()->subDays($d), 'count' => rand(1,3), 'completed' => true, 'created_at' => now(), 'updated_at' => now()];
        DB::table('habit_logs')->insert($l);
    }

    private function seedJournal(): void
    {
        $this->command->info('  Journal (41)...');
        $e = []; for ($i = 0; $i < 20; $i++) $e[] = ['user_id' => $this->uid(), 'entry_date' => now()->subDays($i * 2), 'title' => 'Day '.($i+1), 'body' => fake()->paragraphs(2, true), 'mood' => rand(4,10), 'energy' => rand(4,10), 'gratitude' => $this->j(['Family','Health']), 'wins' => $this->j(['Shipped feature']), 'tags' => $this->j(['personal']), 'is_private' => true, 'created_at' => now(), 'updated_at' => now()];
        DB::table('journal_entries')->insert($e);
    }

    private function seedDecisions(): void
    {
        $this->command->info('  Decisions (42)...');
        DB::table('decisions')->insert([
            ['user_id' => $this->u1, 'title' => 'Switch to Postgres?', 'context' => 'MySQL JSON limits', 'options' => $this->j(['Stay MySQL', 'Migrate Postgres']), 'decision' => 'Migrate Postgres', 'rationale' => 'Better JSON', 'confidence' => 8, 'status' => 'decided', 'decided_at' => now()->subDays(10), 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'Next learning?', 'context' => 'Career growth', 'options' => $this->j(['Kubernetes', 'Rust', 'System Design']), 'decision' => null, 'rationale' => null, 'decided_at' => null, 'status' => 'open', 'confidence' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'title' => 'Office vs Remote?', 'context' => 'Hybrid option', 'options' => $this->j(['Remote', 'Hybrid', 'Office']), 'decision' => 'Hybrid', 'rationale' => 'Best balance', 'confidence' => 7, 'status' => 'decided', 'decided_at' => now()->subDays(5), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedScorecard(): void
    {
        $this->command->info('  Scorecard (43)...');
        DB::table('scorecard_metrics')->insert([
            ['user_id' => $this->u1, 'name' => 'Daily Steps', 'category' => 'health', 'unit' => 'steps', 'target' => 10000, 'direction' => 'higher', 'frequency' => 'daily', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Deep Work Hours', 'category' => 'productivity', 'unit' => 'hours', 'target' => 4, 'direction' => 'higher', 'frequency' => 'daily', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Sleep', 'category' => 'health', 'unit' => 'hours', 'target' => 8, 'direction' => 'higher', 'frequency' => 'daily', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $m = []; for ($mi = 1; $mi <= 3; $mi++) for ($d = 0; $d < 20; $d++) $m[] = ['metric_id' => $mi, 'user_id' => $this->u1, 'measured_on' => now()->subDays($d), 'value' => rand(5000,12000), 'created_at' => now(), 'updated_at' => now()];
        DB::table('scorecard_measurements')->insert($m);
    }

    private function seedVisionItems(): void
    {
        $this->command->info('  Vision (44)...');
        DB::table('vision_items')->insert([
            ['user_id' => $this->u1, 'title' => 'Launch SaaS Q3', 'type' => 'goal', 'category' => 'career', 'status' => 'active', 'priority' => 'high', 'target_date' => '2026-09-30', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'Visit Japan', 'type' => 'vision', 'category' => 'travel', 'status' => 'active', 'priority' => 'medium', 'target_date' => '2027-03-01', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'Run Marathon', 'type' => 'goal', 'category' => 'health', 'status' => 'active', 'priority' => 'high', 'target_date' => '2026-12-01', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedTimeline(): void
    {
        $this->command->info('  Timeline (45)...');
        DB::table('timeline_events')->insert([
            ['user_id' => $this->u1, 'event_date' => '2020-06-15', 'title' => 'First Job', 'description' => 'Junior Dev', 'type' => 'milestone', 'category' => 'career', 'significance' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'event_date' => '2022-07-01', 'title' => 'Promoted Senior', 'description' => 'Promoted to senior developer', 'type' => 'milestone', 'category' => 'career', 'significance' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'event_date' => '2024-01-01', 'title' => 'Fitness Journey', 'description' => 'Started fitness routine', 'type' => 'event', 'category' => 'health', 'significance' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedWorkouts(): void
    {
        $this->command->info('  Workouts (46)...');
        $w = []; for ($i = 0; $i < 15; $i++) $w[] = ['user_id' => $this->uid(), 'name' => ['Push Day', 'Cardio', 'Yoga', 'HIIT', 'Leg Day'][rand(0,4)], 'type' => ['strength', 'cardio', 'flexibility', 'hiit'][rand(0,3)], 'duration_minutes' => rand(20,90), 'calories_burned' => rand(150,800), 'status' => ['planned', 'completed'][rand(0,1)], 'exercises' => $this->j([['name' => 'Bench', 'sets' => 4, 'reps' => 8]]), 'created_at' => now(), 'updated_at' => now()];
        DB::table('workouts')->insert($w);
    }

    private function seedMeals(): void
    {
        $this->command->info('  Meals (47)...');
        $m = []; for ($i = 0; $i < 20; $i++) $m[] = ['user_id' => $this->uid(), 'eaten_on' => now()->subDays(rand(0,14)), 'meal_type' => ['breakfast', 'lunch', 'dinner', 'snack'][rand(0,3)], 'name' => ['Oatmeal', 'Chicken Salad', 'Salmon', 'Shake', 'Pasta'][rand(0,4)], 'calories' => rand(200,800), 'protein_grams' => rand(10,50), 'carbs_grams' => rand(20,100), 'fat_grams' => rand(5,40), 'created_at' => now(), 'updated_at' => now()];
        DB::table('meals')->insert($m);
    }

    private function seedSleep(): void
    {
        $this->command->info('  Sleep (48)...');
        $s = []; for ($i = 0; $i < 30; $i++) $s[] = ['user_id' => $this->uid(), 'sleep_date' => now()->subDays($i), 'bedtime' => now()->subDays($i)->subHours(20), 'wake_time' => now()->subDays($i)->subHours(12), 'duration_minutes' => rand(300,540), 'quality' => rand(3,10), 'created_at' => now(), 'updated_at' => now()];
        DB::table('sleep_logs')->insert($s);
    }

    private function seedMedications(): void
    {
        $this->command->info('  Medications (49)...');
        DB::table('medications')->insert([
            ['user_id' => $this->u1, 'name' => 'Vitamin D3', 'dosage' => '2000 IU', 'frequency' => 'daily', 'started_on' => now()->subDays(90), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'name' => 'Omega-3', 'dosage' => '1000mg', 'frequency' => 'daily', 'started_on' => now()->subDays(60), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $l = []; for ($m = 1; $m <= 2; $m++) for ($d = 0; $d < 14; $d++) $l[] = ['medication_id' => $m, 'user_id' => $m === 1 ? $this->u1 : $this->u2, 'taken_at' => now()->subDays($d)->setTime(8,0), 'status' => rand(0,10) > 8 ? 'skipped' : 'taken', 'created_at' => now(), 'updated_at' => now()];
        DB::table('medication_logs')->insert($l);
    }

    private function seedMedicalRecords(): void
    {
        $this->command->info('  Medical (50)...');
        DB::table('medical_records')->insert([
            ['user_id' => $this->u1, 'record_type' => 'checkup', 'title' => 'Annual Physical', 'provider' => 'Dr. Smith', 'record_date' => now()->subMonths(3), 'status' => 'normal', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'record_type' => 'lab', 'title' => 'Blood Work', 'provider' => 'Quest', 'record_date' => now()->subMonths(2), 'status' => 'normal', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u2, 'record_type' => 'dental', 'title' => 'Cleaning', 'provider' => 'Dr. Johnson', 'record_date' => now()->subMonths(6), 'status' => 'normal', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function seedBodyObs(): void
    {
        $this->command->info('  Body Obs (51)...');
        $o = []; for ($i = 0; $i < 30; $i++) $o[] = ['user_id' => $this->uid(), 'observed_at' => now()->subDays($i), 'observation_type' => ['weight', 'heart_rate', 'blood_pressure'][rand(0,2)], 'name' => 'Reading', 'value' => rand(60,200), 'unit' => ['kg', 'bpm', 'mmHg'][rand(0,2)], 'created_at' => now(), 'updated_at' => now()];
        DB::table('body_observations')->insert($o);
    }

    private function seedHydration(): void
    {
        $this->command->info('  Hydration (52)...');
        $h = []; for ($i = 0; $i < 30; $i++) $h[] = ['user_id' => $this->uid(), 'drank_at' => now()->subDays($i)->subHours(rand(8,20)), 'milliliters' => rand(200,800), 'beverage' => ['water', 'coffee', 'tea'][rand(0,2)], 'created_at' => now(), 'updated_at' => now()];
        DB::table('hydration_logs')->insert($h);
        $m = []; for ($i = 0; $i < 30; $i++) $m[] = ['user_id' => $this->uid(), 'moved_at' => now()->subDays($i), 'minutes' => rand(10,90), 'activity' => ['walking', 'running', 'cycling'][rand(0,2)], 'steps' => rand(2000,12000), 'created_at' => now(), 'updated_at' => now()];
        DB::table('movement_logs')->insert($m);
    }

    private function seedExpenses(): void
    {
        $this->command->info('  Expenses (53)...');
        $e = []; for ($i = 0; $i < 40; $i++) $e[] = ['user_id' => $this->uid(), 'spent_on' => now()->subDays(rand(0,60)), 'description' => fake()->words(3, true), 'category' => ['food', 'transport', 'entertainment', 'utilities', 'health', 'shopping'][rand(0,5)], 'amount' => rand(5,500)+0.99, 'currency' => 'USD', 'payment_method' => ['credit', 'debit', 'cash'][rand(0,2)], 'recurring' => rand(0,3) > 2, 'tags' => $this->j(['expense']), 'created_at' => now(), 'updated_at' => now()];
        DB::table('expenses')->insert($e);
        $b = []; $owners = [$this->u1, $this->u2, $this->u3]; $i = 0; foreach (['food', 'transport', 'entertainment', 'utilities', 'health', 'shopping'] as $c) { $b[] = ['user_id' => $owners[$i % 3], 'category' => $c, 'amount' => rand(100,2000), 'period' => 'monthly', 'starts_on' => now()->startOfMonth(), 'active' => true, 'created_at' => now(), 'updated_at' => now()]; $i++; }
        DB::table('budgets')->insert($b);
    }

    private function seedFinance(): void
    {
        $this->command->info('  Finance (54-60)...');
        $r = []; $types = ['bills', 'savings', 'investments', 'loans', 'insurance', 'tax_documents', 'purchases']; foreach (['Electric Bill', 'Savings Account', 'VTSAX Fund', 'Student Loan', 'Health Insurance', 'Tax', 'Laptop', 'Internet', 'Car Insurance', '401k', 'Roth IRA', 'Mortgage', 'Property Tax', 'Vacation Fund', 'Phone Bill'] as $n) $r[] = ['user_id' => $this->uid(), 'record_type' => $types[rand(0,6)], 'name' => $n, 'amount' => rand(20,2000), 'currency' => 'USD', 'status' => ['active', 'completed'][rand(0,1)], 'due_date' => now()->addDays(rand(1,30)), 'created_at' => now(), 'updated_at' => now()];
        DB::table('finance_records')->insert($r);
    }

    private function seedHome(): void
    {
        $this->command->info('  Home (61-62)...');
        DB::table('home_projects')->insert([
            ['user_id' => $this->u1, 'name' => 'Kitchen Remodel', 'description' => 'Update cabinets', 'status' => 'in_progress', 'priority' => 'high', 'budget' => 15000, 'spent' => 8500, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'name' => 'Backyard Landscaping', 'description' => 'Outdoor beautification', 'status' => 'planned', 'priority' => 'medium', 'budget' => 5000, 'spent' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('maintenance_tasks')->insert([
            ['user_id' => $this->u1, 'title' => 'HVAC Filter', 'category' => 'hvac', 'due_on' => now()->addMonths(2), 'status' => 'pending', 'priority' => 'medium', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->u1, 'title' => 'Gutter Cleaning', 'category' => 'exterior', 'due_on' => now()->addMonths(3), 'status' => 'pending', 'priority' => 'low', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $ht = []; $types = ['appliances', 'utilities', 'garden', 'inventory', 'trips', 'travel_journal', 'travel_documents', 'vehicles', 'routes']; foreach (['Car Oil Change', 'Washer Repair', 'Electric Bill', 'Lawn Mower', 'Furniture', 'NYC Trip', 'Passport', 'Tire Rotation', 'Fridge', 'Water Bill'] as $n) $ht[] = ['user_id' => $this->uid(), 'record_type' => $types[rand(0,8)], 'name' => $n, 'date' => now()->subDays(rand(0,90)), 'status' => ['completed', 'pending'][rand(0,1)], 'amount' => rand(10,500), 'created_at' => now(), 'updated_at' => now()];
        DB::table('home_travel_records')->insert($ht);
    }

    private function seedCareer(): void
    {
        $this->command->info('  Career (72-82)...');
        $r = []; $types = ['resumes', 'job_applications', 'achievements', 'meetings', 'invoices', 'clients', 'relationships', 'follow_ups', 'templates', 'important_dates', 'gifts', 'family_records']; foreach (['Resume v3', 'Google Application', 'Salary Notes', 'Client Meeting', 'Invoice #1042', 'CRM Contact', 'Follow-up', 'Birthday Reminder', 'Gift Idea', 'Emergency Contacts', 'Meeting Template'] as $n) $r[] = ['user_id' => $this->uid(), 'record_type' => $types[rand(0,11)], 'title' => $n, 'status' => ['active', 'completed', 'archived'][rand(0,2)], 'record_date' => now()->subDays(rand(0,90)), 'created_at' => now(), 'updated_at' => now()];
        DB::table('career_business_records')->insert($r);
    }

    private function seedEntertainment(): void
    {
        $this->command->info('  Entertainment (83-90)...');
        $r = []; $types = ['watch', 'reading', 'music', 'chess', 'wishlist', 'writing', 'publishing', 'ideas']; foreach (['Project Hail Mary', 'Dark Side of the Moon', 'Sicilian Defense', 'AirPods Max', 'Blog Post', 'Laravel Tips', 'App Idea', 'Book Club'] as $n) $r[] = ['user_id' => $this->uid(), 'record_type' => $types[rand(0,7)], 'title' => $n, 'status' => ['in_progress', 'completed', 'planned'][rand(0,2)], 'rating' => rand(1,10), 'record_date' => now()->subDays(rand(0,60)), 'created_at' => now(), 'updated_at' => now()];
        DB::table('entertainment_writing_records')->insert($r);
    }

    private function seedDevOps(): void
    {
        $this->command->info('  DevOps (91-101)...');
        $r = []; $types = ['api_testing', 'scaffolding', 'schemas', 'packages', 'environments', 'deployments', 'containers', 'servers', 'monitoring', 'certificates', 'docs', 'backup']; foreach (['API Tests', 'Scaffold App', 'DB Schema', 'NPM Registry', 'Dev Dashboard', 'Deploy Prod', 'Docker Prod', 'Web Server', 'Uptime Monitor', 'Daily Backup', 'SSL Cert'] as $n) $r[] = ['user_id' => $this->uid(), 'record_type' => $types[rand(0,11)], 'name' => $n, 'status' => ['active', 'completed', 'pending'][rand(0,2)], 'environment' => ['staging', 'production'][rand(0,1)], 'created_at' => now(), 'updated_at' => now()];
        DB::table('devops_records')->insert($r);
    }

    private function seedSecurity(): void
    {
        $this->command->info('  Security (102-106)...');
        $r = []; $types = ['security_audits', 'devices', 'analytics', 'life_statistics', 'reports']; foreach (['Security Scan', 'MacBook Pro', 'Monthly Analytics', 'Life Stats', 'Revenue Report', 'Login History', 'Vuln Scan', 'iPhone 15', 'Weekly KPIs', 'Expense Report'] as $n) $r[] = ['user_id' => $this->uid(), 'record_type' => $types[rand(0,4)], 'name' => $n, 'status' => ['passed', 'in_progress', 'pending'][rand(0,2)], 'recorded_at' => now()->subDays(rand(0,30)), 'created_at' => now(), 'updated_at' => now()];
        DB::table('security_analytics_records')->insert($r);
    }
}
