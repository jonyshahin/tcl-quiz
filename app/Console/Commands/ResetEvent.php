<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Draw;
use App\Models\DrawWinner;
use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ResetEvent extends Command
{
    protected $signature = 'app:reset-event {--force : Skip the confirmation prompt (for non-interactive runs)}';

    protected $description = 'Clear all lead submissions and draw history for a clean event start. Keeps questions, the admin account, and quiz attempts.';

    public function handle(): int
    {
        $leads = Lead::count();
        $draws = Draw::count();
        $winners = DrawWinner::count();

        $this->components->twoColumnDetail('Leads (submissions)', (string) $leads);
        $this->components->twoColumnDetail('Draws', (string) $draws);
        $this->components->twoColumnDetail('Draw winners', (string) $winners);

        if ($leads === 0 && $draws === 0 && $winners === 0) {
            $this->components->info('Nothing to clear — already a clean slate.');

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm('Permanently delete all leads and draw history? This cannot be undone.')) {
            $this->components->warn('Aborted. Nothing was deleted.');

            return self::SUCCESS;
        }

        Schema::withoutForeignKeyConstraints(function (): void {
            DrawWinner::truncate();
            Draw::truncate();
            Lead::truncate();
        });

        $this->components->info('Cleared all leads and draw history. Questions, admin, and quiz attempts were kept.');

        return self::SUCCESS;
    }
}
