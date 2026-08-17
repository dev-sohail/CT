<?php

use App\Domains\SecondBrainPersonalWiki\Http\Controllers\BlockController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\NotebookController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\PageController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\PageVersionController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\ProjectController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\QuestionController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\ReferenceController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\ReviewController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\SectionController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\TagController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\TaskController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\TemplateController;
use App\Domains\SecondBrainPersonalWiki\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Workspaces
    Route::get('workspaces', [WorkspaceController::class, 'index'])->name('wiki.workspaces.index');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('wiki.workspaces.store');
    Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('wiki.workspaces.show');
    Route::put('workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('wiki.workspaces.update');
    Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('wiki.workspaces.destroy');

    // Notebooks
    Route::get('notebooks', [NotebookController::class, 'index'])->name('wiki.notebooks.index');
    Route::get('notebooks/{notebook}', [NotebookController::class, 'show'])->name('wiki.notebooks.show');
    Route::post('workspaces/{workspace}/notebooks', [NotebookController::class, 'store'])->name('wiki.notebooks.store');
    Route::put('notebooks/{notebook}', [NotebookController::class, 'update'])->name('wiki.notebooks.update');
    Route::delete('notebooks/{notebook}', [NotebookController::class, 'destroy'])->name('wiki.notebooks.destroy');

    // Sections
    Route::get('sections', [SectionController::class, 'index'])->name('wiki.sections.index');
    Route::get('sections/{section}', [SectionController::class, 'show'])->name('wiki.sections.show');
    Route::post('notebooks/{notebook}/sections', [SectionController::class, 'store'])->name('wiki.sections.store');
    Route::put('sections/{section}', [SectionController::class, 'update'])->name('wiki.sections.update');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('wiki.sections.destroy');

    // Pages
    Route::get('pages', [PageController::class, 'index'])->name('wiki.pages.index');
    Route::get('pages/trash', [PageController::class, 'trash'])->name('wiki.pages.trash');
    Route::post('sections/{section}/pages', [PageController::class, 'store'])->name('wiki.pages.store');
    Route::get('pages/{page}', [PageController::class, 'show'])->name('wiki.pages.show');
    Route::put('pages/{page}', [PageController::class, 'update'])->name('wiki.pages.update');
    Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('wiki.pages.destroy');
    Route::post('pages/{page}/favorite', [PageController::class, 'toggleFavorite'])->name('wiki.pages.favorite');
    Route::post('pages/{page}/restore', [PageController::class, 'restore'])->name('wiki.pages.restore')->withTrashed();
    Route::delete('pages/{page}/force', [PageController::class, 'forceDestroy'])->name('wiki.pages.force')->withTrashed();
    Route::get('pages/{page}/backlinks', [PageController::class, 'backlinks'])->name('wiki.pages.backlinks');

    // Blocks
    Route::get('pages/{page}/blocks', [BlockController::class, 'index'])->name('wiki.blocks.index');
    Route::post('pages/{page}/blocks/sync', [BlockController::class, 'sync'])->name('wiki.blocks.sync');
    Route::get('blocks/{block}', [BlockController::class, 'show'])->name('wiki.blocks.show');
    Route::put('blocks/{block}', [BlockController::class, 'update'])->name('wiki.blocks.update');
    Route::delete('blocks/{block}', [BlockController::class, 'destroy'])->name('wiki.blocks.destroy');

    // Tags
    Route::get('wiki-tags', [TagController::class, 'index'])->name('wiki.tags.index');
    Route::post('wiki-tags', [TagController::class, 'store'])->name('wiki.tags.store');

    // Templates
    Route::get('templates', [TemplateController::class, 'index'])->name('wiki.templates.index');
    Route::post('templates', [TemplateController::class, 'store'])->name('wiki.templates.store');
    Route::get('templates/{template}', [TemplateController::class, 'show'])->name('wiki.templates.show');
    Route::put('templates/{template}', [TemplateController::class, 'update'])->name('wiki.templates.update');
    Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('wiki.templates.destroy');
    Route::post('templates/{template}/use/{section}', [TemplateController::class, 'use'])->name('wiki.templates.use');

    // Page versions
    Route::get('pages/{page}/versions', [PageVersionController::class, 'index'])->name('wiki.versions.index');
    Route::post('pages/{page}/versions', [PageVersionController::class, 'store'])->name('wiki.versions.store');
    Route::post('versions/{version}/restore', [PageVersionController::class, 'restore'])->name('wiki.versions.restore');

    // Projects
    Route::get('projects', [ProjectController::class, 'index'])->name('wiki.projects.index');
    Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'store'])->name('wiki.projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('wiki.projects.show');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('wiki.projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('wiki.projects.destroy');
    Route::post('projects/{project}/pages/{page}', [ProjectController::class, 'attachPage'])->name('wiki.projects.pages.attach');
    Route::delete('projects/{project}/pages/{page}', [ProjectController::class, 'detachPage'])->name('wiki.projects.pages.detach');

    // Questions
    Route::get('questions', [QuestionController::class, 'index'])->name('wiki.questions.index');
    Route::post('workspaces/{workspace}/questions', [QuestionController::class, 'store'])->name('wiki.questions.store');
    Route::get('questions/{question}', [QuestionController::class, 'show'])->name('wiki.questions.show');
    Route::put('questions/{question}', [QuestionController::class, 'update'])->name('wiki.questions.update');
    Route::delete('questions/{question}', [QuestionController::class, 'destroy'])->name('wiki.questions.destroy');

    // References
    Route::get('references', [ReferenceController::class, 'index'])->name('wiki.references.index');
    Route::post('workspaces/{workspace}/references', [ReferenceController::class, 'store'])->name('wiki.references.store');
    Route::get('references/{reference}', [ReferenceController::class, 'show'])->name('wiki.references.show');
    Route::put('references/{reference}', [ReferenceController::class, 'update'])->name('wiki.references.update');
    Route::delete('references/{reference}', [ReferenceController::class, 'destroy'])->name('wiki.references.destroy');

    // Tasks
    Route::get('tasks', [TaskController::class, 'index'])->name('wiki.tasks.index');
    Route::post('workspaces/{workspace}/tasks', [TaskController::class, 'store'])->name('wiki.tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('wiki.tasks.show');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('wiki.tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('wiki.tasks.destroy');

    // Reviews
    Route::get('reviews', [ReviewController::class, 'index'])->name('wiki.reviews.index');
    Route::post('workspaces/{workspace}/reviews', [ReviewController::class, 'store'])->name('wiki.reviews.store');
    Route::get('reviews/{review}', [ReviewController::class, 'show'])->name('wiki.reviews.show');
    Route::put('reviews/{review}', [ReviewController::class, 'update'])->name('wiki.reviews.update');
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('wiki.reviews.destroy');
    Route::post('reviews/{review}/done', [ReviewController::class, 'markDone'])->name('wiki.reviews.done');
});
