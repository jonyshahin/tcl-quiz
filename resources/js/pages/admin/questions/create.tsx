import { Head } from '@inertiajs/react';
import QuestionForm from '@/components/admin/question-form';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Props {
    nextOrder: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Questions', href: '/admin/questions' },
    { title: 'New question', href: '/admin/questions/create' },
];

export default function Create({ nextOrder }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New question" />
            <div className="p-4">
                <h1 className="mb-6 text-2xl font-bold">New question</h1>
                <QuestionForm
                    action="/admin/questions"
                    method="post"
                    submitLabel="Create question"
                    initial={{
                        prompt: '',
                        explanation: '',
                        order: nextOrder,
                        is_active: true,
                        correct_index: 0,
                        options: [
                            { label: 'A', text: '' },
                            { label: 'B', text: '' },
                            { label: 'C', text: '' },
                            { label: 'D', text: '' },
                        ],
                    }}
                />
            </div>
        </AppLayout>
    );
}
