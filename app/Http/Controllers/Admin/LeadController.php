<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(): Response
    {
        $leads = Lead::query()
            ->latest()
            ->get()
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'is_winner' => $lead->is_winner,
                'score' => $lead->score,
                'created_at' => $lead->created_at?->toDayDateTimeString(),
            ]);

        return Inertia::render('admin/leads/index', [
            'leads' => $leads,
        ]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'tcl-quiz-leads-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Name', 'Email', 'Phone', 'Winner', 'Score', 'Submitted At']);

            Lead::query()->latest()->chunk(200, function ($leads) use ($handle) {
                foreach ($leads as $lead) {
                    fputcsv($handle, [
                        $lead->name,
                        $lead->email,
                        $lead->phone,
                        $lead->is_winner ? 'Yes' : 'No',
                        $lead->score,
                        $lead->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
