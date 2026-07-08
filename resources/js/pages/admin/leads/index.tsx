import { Head } from '@inertiajs/react';
import { Download, Trophy } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface LeadRow {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    is_winner: boolean;
    score: string;
    created_at: string | null;
}

interface Props {
    leads: LeadRow[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leads', href: '/admin/leads' },
];

export default function Index({ leads }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Leads" />
            <div className="p-4">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Leads</h1>
                        <p className="text-sm text-muted-foreground">
                            {leads.length} captured contact
                            {leads.length === 1 ? '' : 's'}
                        </p>
                    </div>
                    <Button asChild disabled={leads.length === 0}>
                        <a href="/admin/leads/export">
                            <Download className="mr-1 size-4" /> Export CSV
                        </a>
                    </Button>
                </div>

                {leads.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-10 text-center text-muted-foreground">
                        No leads captured yet.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Phone</th>
                                    <th className="px-4 py-3">Result</th>
                                    <th className="px-4 py-3">Score</th>
                                    <th className="px-4 py-3">Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                {leads.map((lead) => (
                                    <tr
                                        key={lead.id}
                                        className="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {lead.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            {lead.email ? (
                                                <a
                                                    href={`mailto:${lead.email}`}
                                                    className="text-tcl-red hover:underline"
                                                >
                                                    {lead.email}
                                                </a>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {lead.phone ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {lead.is_winner ? (
                                                <Badge className="bg-gold text-ink">
                                                    <Trophy className="mr-1 size-3" />{' '}
                                                    Winner
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Player
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-mono">
                                            {lead.score}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {lead.created_at ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
