import { useForm } from '@inertiajs/react';
import type {FormEvent} from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export interface QuestionFormValues {
    prompt: string;
    explanation: string;
    order: number;
    is_active: boolean;
    options: { label: string; text: string }[];
    correct_index: number;
}

interface Props {
    initial: QuestionFormValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
}

export default function QuestionForm({
    initial,
    action,
    method,
    submitLabel,
}: Props) {
    const { data, setData, post, put, processing, errors } =
        useForm<QuestionFormValues>(initial);

    const setOption = (index: number, key: 'label' | 'text', value: string) => {
        const options = data.options.map((o, i) =>
            i === index ? { ...o, [key]: value } : o,
        );
        setData('options', options);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const fn = method === 'post' ? post : put;
        fn(action);
    };

    return (
        <form onSubmit={submit} className="max-w-2xl space-y-6">
            <div className="space-y-2">
                <Label htmlFor="prompt">Question prompt</Label>
                <textarea
                    id="prompt"
                    value={data.prompt}
                    onChange={(e) => setData('prompt', e.target.value)}
                    rows={3}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                {errors.prompt && (
                    <p className="text-sm text-destructive">{errors.prompt}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="explanation">Explanation (optional)</Label>
                <textarea
                    id="explanation"
                    value={data.explanation}
                    onChange={(e) => setData('explanation', e.target.value)}
                    rows={2}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                {errors.explanation && (
                    <p className="text-sm text-destructive">
                        {errors.explanation}
                    </p>
                )}
            </div>

            <fieldset className="space-y-3">
                <legend className="mb-1 text-sm font-medium">
                    Options — select the single correct answer
                </legend>
                {data.options.map((option, i) => (
                    <div key={i} className="flex items-center gap-3">
                        <input
                            type="radio"
                            name="correct_index"
                            checked={data.correct_index === i}
                            onChange={() => setData('correct_index', i)}
                            aria-label={`Mark option ${option.label || i + 1} correct`}
                            className="size-4 accent-[var(--color-tcl-red)]"
                        />
                        <Input
                            value={option.label}
                            onChange={(e) =>
                                setOption(i, 'label', e.target.value)
                            }
                            className="w-16"
                            aria-label={`Option ${i + 1} label`}
                        />
                        <Input
                            value={option.text}
                            onChange={(e) =>
                                setOption(i, 'text', e.target.value)
                            }
                            className="flex-1"
                            placeholder={`Option ${i + 1} text`}
                            aria-label={`Option ${i + 1} text`}
                        />
                    </div>
                ))}
                {errors.correct_index && (
                    <p className="text-sm text-destructive">
                        {errors.correct_index}
                    </p>
                )}
                {Object.keys(errors)
                    .filter((k) => k.startsWith('options.'))
                    .map((k) => (
                        <p key={k} className="text-sm text-destructive">
                            {errors[k as keyof typeof errors] as string}
                        </p>
                    ))}
            </fieldset>

            <div className="flex flex-wrap items-end gap-6">
                <div className="space-y-2">
                    <Label htmlFor="order">Display order</Label>
                    <Input
                        id="order"
                        type="number"
                        min={0}
                        value={data.order}
                        onChange={(e) =>
                            setData('order', Number(e.target.value))
                        }
                        className="w-28"
                    />
                    {errors.order && (
                        <p className="text-sm text-destructive">
                            {errors.order}
                        </p>
                    )}
                </div>

                <label className="flex items-center gap-2 pb-2">
                    <Checkbox
                        checked={data.is_active}
                        onCheckedChange={(v) =>
                            setData('is_active', v === true)
                        }
                    />
                    <span className="text-sm font-medium">Active</span>
                </label>
            </div>

            <div className="flex gap-3">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
                <Button type="button" variant="outline" asChild>
                    <a href="/admin/questions">Cancel</a>
                </Button>
            </div>
        </form>
    );
}
