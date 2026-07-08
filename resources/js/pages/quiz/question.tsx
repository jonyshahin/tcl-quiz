import { Head, router } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { ArrowRight, Trophy } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import FullscreenStage from '@/components/quiz/fullscreen-stage';
import OptionCard from '@/components/quiz/option-card';
import type {OptionStatus} from '@/components/quiz/option-card';
import ProgressBar from '@/components/quiz/progress-bar';
import TclLogo from '@/components/quiz/tcl-logo';
import { postJson } from '@/lib/http';

interface Option {
    id: number;
    label: string;
    text: string;
}

interface AnswerResult {
    selected_option_id: number;
    correct_option_id: number;
    is_correct: boolean;
    explanation: string | null;
}

interface Props {
    token: string;
    index: number;
    total: number;
    isLast: boolean;
    question: {
        id: number;
        prompt: string;
        options: Option[];
    };
    answeredResult: AnswerResult | null;
}

export default function Question({
    token,
    index,
    total,
    isLast,
    question,
    answeredResult,
}: Props) {
    const reduce = useReducedMotion();
    const [result, setResult] = useState<AnswerResult | null>(answeredResult);
    const [submitting, setSubmitting] = useState(false);
    const [leaving, setLeaving] = useState(false);
    const [focusIndex, setFocusIndex] = useState(0);
    const buttonRefs = useRef<Array<HTMLButtonElement | null>>([]);

    const answered = result !== null;

    const nextUrl = isLast
        ? `/quiz/${token}/result`
        : `/quiz/${token}/q/${index + 1}`;

    const select = async (option: Option) => {
        if (answered || submitting) {
return;
}

        setSubmitting(true);

        try {
            const data = await postJson<
                AnswerResult & { already_answered: boolean }
            >(`/quiz/${token}/answer`, {
                question_id: question.id,
                option_id: option.id,
            });
            setResult({
                selected_option_id: data.selected_option_id,
                correct_option_id: data.correct_option_id,
                is_correct: data.is_correct,
                explanation: data.explanation,
            });
        } catch {
            setSubmitting(false);
        }
    };

    const goNext = () => {
        if (!answered) {
return;
}

        setLeaving(true);
    };

    const statusFor = (option: Option): OptionStatus => {
        if (!result) {
return 'idle';
}

        if (option.id === result.correct_option_id) {
return 'correct';
}

        if (option.id === result.selected_option_id) {
return 'wrong';
}

        return 'muted';
    };

    const onKeyDown = (e: React.KeyboardEvent) => {
        if (answered) {
return;
}

        const count = question.options.length;

        if (['ArrowDown', 'ArrowRight'].includes(e.key)) {
            e.preventDefault();
            const n = (focusIndex + 1) % count;
            setFocusIndex(n);
            buttonRefs.current[n]?.focus();
        } else if (['ArrowUp', 'ArrowLeft'].includes(e.key)) {
            e.preventDefault();
            const n = (focusIndex - 1 + count) % count;
            setFocusIndex(n);
            buttonRefs.current[n]?.focus();
        }
    };

    const enter = useMemo(
        () =>
            reduce
                ? { initial: { opacity: 0 }, animate: { opacity: 1 } }
                : {
                      initial: { opacity: 0, x: 60 },
                      animate: { opacity: 1, x: 0 },
                  },
        [reduce],
    );

    return (
        <FullscreenStage>
            <Head title={`Question ${index + 1} of ${total}`} />

            <div className="flex h-full w-full max-w-3xl flex-col justify-center gap-[clamp(0.8rem,2.5vh,1.6rem)] py-[clamp(0.5rem,2vh,1.5rem)]">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex-1">
                        <ProgressBar current={index + 1} total={total} />
                    </div>
                    <div className="hidden flex-none rounded-lg bg-tcl-white/90 p-1.5 shadow-md sm:block">
                        <TclLogo className="h-6 w-auto" />
                    </div>
                </div>

                <AnimatePresence
                    mode="wait"
                    onExitComplete={() => router.visit(nextUrl)}
                >
                    {!leaving && (
                        <motion.div
                            key={index}
                            initial={enter.initial}
                            animate={enter.animate}
                            exit={
                                reduce ? { opacity: 0 } : { opacity: 0, x: -60 }
                            }
                            transition={{ duration: 0.4, ease: 'easeInOut' }}
                            className="grid grid-cols-1 gap-x-[clamp(1rem,4vw,2.5rem)] gap-y-[clamp(0.7rem,2.2vh,1.3rem)] md:grid-cols-2 md:items-center landscape:grid-cols-2 landscape:items-center"
                        >
                            <h2 className="font-display text-[clamp(1rem,min(4.4vw,4vh),1.9rem)] leading-tight font-bold text-tcl-white">
                                {question.prompt}
                            </h2>

                            <div className="flex flex-col gap-[clamp(0.6rem,2vh,1rem)]">
                                <div
                                    role="radiogroup"
                                    aria-label="Answer options"
                                    onKeyDown={onKeyDown}
                                    className="flex flex-col gap-[clamp(0.5rem,1.6vh,0.85rem)]"
                                >
                                    {question.options.map((option, i) => (
                                        <div
                                            key={option.id}
                                            ref={(el) => {
                                                buttonRefs.current[i] =
                                                    el?.querySelector(
                                                        'button',
                                                    ) ?? null;
                                            }}
                                        >
                                            <OptionCard
                                                label={option.label}
                                                text={option.text}
                                                status={statusFor(option)}
                                                answered={answered}
                                                selected={
                                                    result?.selected_option_id ===
                                                    option.id
                                                }
                                                disabled={
                                                    answered || submitting
                                                }
                                                focusable={
                                                    !answered &&
                                                    focusIndex === i
                                                }
                                                onSelect={() => select(option)}
                                            />
                                        </div>
                                    ))}
                                </div>

                                <AnimatePresence>
                                    {answered && result?.explanation && (
                                        <motion.p
                                            initial={{ opacity: 0, height: 0 }}
                                            animate={{
                                                opacity: 1,
                                                height: 'auto',
                                            }}
                                            className="rounded-xl bg-tcl-white/15 px-4 py-3 text-[clamp(0.82rem,2.4vw,0.98rem)] text-tcl-white/90"
                                        >
                                            {result.explanation}
                                        </motion.p>
                                    )}
                                </AnimatePresence>

                                <div className="flex justify-end pt-1">
                                    <motion.button
                                        type="button"
                                        onClick={goNext}
                                        disabled={!answered}
                                        initial={false}
                                        animate={{
                                            opacity: answered ? 1 : 0.35,
                                        }}
                                        whileHover={
                                            !answered || reduce
                                                ? undefined
                                                : { scale: 1.04 }
                                        }
                                        whileTap={
                                            !answered || reduce
                                                ? undefined
                                                : { scale: 0.97 }
                                        }
                                        className="inline-flex items-center gap-2 rounded-full bg-tcl-white px-[clamp(1.4rem,5vw,2.2rem)] py-[clamp(0.6rem,2vw,0.9rem)] font-display text-[clamp(0.95rem,3vw,1.2rem)] font-bold text-tcl-red shadow-xl transition-colors hover:bg-gold hover:text-ink disabled:cursor-not-allowed"
                                    >
                                        {isLast ? 'See results' : 'Next'}
                                        {isLast ? (
                                            <Trophy className="h-[1.1em] w-[1.1em]" />
                                        ) : (
                                            <ArrowRight className="h-[1.1em] w-[1.1em]" />
                                        )}
                                    </motion.button>
                                </div>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>
        </FullscreenStage>
    );
}
