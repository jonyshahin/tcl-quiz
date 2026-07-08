import { Head, router, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface QuestionRow {
    id: number;
    prompt: string;
    order: number;
    is_active: boolean;
    correct_index: number | false;
    options: { id: number; label: string; text: string; is_correct: boolean }[];
}

interface Props {
    questions: QuestionRow[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Questions', href: '/admin/questions' },
];

export default function Index({ questions }: Props) {
    const flash = usePage().props.flash as { success?: string } | undefined;

    useEffect(() => {
        if (flash?.success) {
toast.success(flash.success);
}
    }, [flash?.success]);

    const remove = (q: QuestionRow) => {
        if (confirm('Delete this question and its options?')) {
            router.delete(`/admin/questions/${q.id}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Questions" />
            <div className="p-4">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Questions</h1>
                        <p className="text-sm text-muted-foreground">
                            {questions.length} question
                            {questions.length === 1 ? '' : 's'} in the bank
                        </p>
                    </div>
                    <Button asChild>
                        <a href="/admin/questions/create">
                            <Plus className="mr-1 size-4" /> New question
                        </a>
                    </Button>
                </div>

                {questions.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-10 text-center text-muted-foreground">
                        No questions yet. Create one, or run{' '}
                        <code className="rounded bg-muted px-1">
                            php artisan db:seed --class=QuestionSeeder
                        </code>
                        .
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs text-muted-foreground uppercase">
                                <tr>
                                    <th className="w-16 px-4 py-3">Order</th>
                                    <th className="px-4 py-3">Prompt</th>
                                    <th className="px-4 py-3">
                                        Correct answer
                                    </th>
                                    <th className="w-24 px-4 py-3">Status</th>
                                    <th className="w-28 px-4 py-3 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {questions.map((q) => {
                                    const correct = q.options.find(
                                        (o) => o.is_correct,
                                    );

                                    return (
                                        <tr
                                            key={q.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3 font-mono">
                                                {q.order}
                                            </td>
                                            <td className="max-w-md px-4 py-3">
                                                <span className="line-clamp-2">
                                                    {q.prompt}
                                                </span>
                                            </td>
                                            <td className="max-w-xs px-4 py-3 text-muted-foreground">
                                                <span className="line-clamp-2">
                                                    {correct
                                                        ? `${correct.label}) ${correct.text}`
                                                        : '—'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                {q.is_active ? (
                                                    <Badge className="bg-success text-white">
                                                        Active
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary">
                                                        Inactive
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <a
                                                            href={`/admin/questions/${q.id}/edit`}
                                                            aria-label="Edit"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </a>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            remove(q)
                                                        }
                                                        aria-label="Delete"
                                                    >
                                                        <Trash2 className="size-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
