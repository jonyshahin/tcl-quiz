import { Head } from '@inertiajs/react';
import QuestionForm from '@/components/admin/question-form';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface QuestionProp {
    id: number;
    prompt: string;
    explanation: string | null;
    order: number;
    is_active: boolean;
    correct_index: number | false;
    options: { id: number; label: string; text: string; is_correct: boolean }[];
}

interface Props {
    question: QuestionProp;
}

export default function Edit({ question }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Questions', href: '/admin/questions' },
        { title: 'Edit', href: `/admin/questions/${question.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit question" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-bold">Edit question</h1>
                <QuestionForm
                    action={`/admin/questions/${question.id}`}
                    method="put"
                    submitLabel="Save changes"
                    initial={{
                        prompt: question.prompt,
                        explanation: question.explanation ?? '',
                        order: question.order,
                        is_active: question.is_active,
                        correct_index:
                            question.correct_index === false
                                ? 0
                                : question.correct_index,
                        options: question.options.map((o) => ({
                            label: o.label,
                            text: o.text,
                        })),
                    }}
                />
            </div>
        </AppLayout>
    );
}
