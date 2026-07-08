import { Head, useForm } from '@inertiajs/react';
import { animate, motion, useReducedMotion } from 'framer-motion';
import { PartyPopper, RefreshCw, Sparkles, Trophy } from 'lucide-react';
import { useEffect, useRef, useState  } from 'react';
import type {FormEvent} from 'react';
import FullscreenStage from '@/components/quiz/fullscreen-stage';
import TclLogo from '@/components/quiz/tcl-logo';
import { celebrate } from '@/lib/confetti';

interface Props {
    token: string;
    isWinner: boolean;
    correctCount: number;
    totalQuestions: number;
    score: string;
    leadSubmitted: boolean;
}

/** Animated count-up from 0 to `to`. */
function CountUp({ to }: { to: number }) {
    const reduce = useReducedMotion();
    const [value, setValue] = useState(reduce ? to : 0);

    useEffect(() => {
        if (reduce) {
            return;
        }

        const controls = animate(0, to, {
            duration: 1,
            delay: 0.3,
            ease: 'easeOut',
            onUpdate: (v) => setValue(Math.round(v)),
        });

        return () => controls.stop();
    }, [to, reduce]);

    return <span>{value}</span>;
}

export default function Result({
    isWinner,
    correctCount,
    totalQuestions,
    token,
    leadSubmitted,
}: Props) {
    const reduce = useReducedMotion();
    const firedRef = useRef(false);
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
    });

    useEffect(() => {
        if (isWinner && !leadSubmitted && !firedRef.current) {
            firedRef.current = true;
            celebrate();
        }
    }, [isWinner, leadSubmitted]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/quiz/${token}/lead`, { preserveScroll: true });
    };

    return (
        <FullscreenStage watermark={!isWinner}>
            <Head title={isWinner ? 'You won!' : 'Your results'} />

            <div className="grid h-full w-full max-w-4xl grid-cols-1 items-center gap-[clamp(0.8rem,3vw,2.5rem)] md:grid-cols-2 landscape:grid-cols-2">
                {/* Celebration / score */}
                <div className="flex flex-col items-center text-center md:items-start md:text-left landscape:items-start landscape:text-left">
                    <motion.div
                        initial={{ scale: 0, rotate: -20 }}
                        animate={{ scale: 1, rotate: 0 }}
                        transition={{
                            type: 'spring',
                            stiffness: 160,
                            damping: 12,
                            delay: 0.1,
                        }}
                        className={`flex h-[clamp(3.5rem,14vw,5.5rem)] w-[clamp(3.5rem,14vw,5.5rem)] items-center justify-center rounded-2xl shadow-2xl ${
                            isWinner
                                ? 'bg-gold text-tcl-red-deep'
                                : 'bg-tcl-white/95 text-tcl-red'
                        }`}
                    >
                        {isWinner ? (
                            <Trophy className="h-1/2 w-1/2" strokeWidth={2.2} />
                        ) : (
                            <Sparkles
                                className="h-1/2 w-1/2"
                                strokeWidth={2.2}
                            />
                        )}
                    </motion.div>

                    <motion.h1
                        initial={{ opacity: 0, y: 14 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2 }}
                        className="mt-[clamp(0.6rem,2vh,1.4rem)] font-display text-[clamp(1.4rem,min(6vw,7vh),3rem)] leading-none font-bold text-tcl-white"
                    >
                        {isWinner ? 'You nailed it!' : 'Nice effort!'}
                    </motion.h1>

                    <motion.p
                        initial={{ opacity: 0, y: 14 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.3 }}
                        className="mt-2 max-w-sm text-[clamp(0.9rem,2.8vw,1.1rem)] text-tcl-white/85"
                    >
                        {isWinner
                            ? 'A perfect score on TCL VRF knowledge. You are a true expert.'
                            : 'You are on your way to VRF mastery — give it another go to score a perfect round.'}
                    </motion.p>

                    <div className="mt-[clamp(0.6rem,2vh,1.4rem)] flex items-baseline gap-2 font-display font-bold text-tcl-white">
                        <span className="text-[clamp(2rem,min(10vw,11vh),4.5rem)] leading-none text-gold">
                            <CountUp to={correctCount} />
                        </span>
                        <span className="text-[clamp(1.2rem,4vw,2rem)] text-tcl-white/70">
                            / {totalQuestions}
                        </span>
                    </div>
                </div>

                {/* Lead capture / thank-you */}
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.35 }}
                    className="w-full rounded-3xl bg-tcl-white px-[clamp(1rem,3.5vw,1.8rem)] py-[clamp(0.7rem,2.4vh,1.7rem)] text-ink shadow-2xl"
                >
                    {leadSubmitted ? (
                        <div className="flex flex-col items-center gap-3 py-4 text-center">
                            <motion.div
                                initial={{ scale: 0 }}
                                animate={{ scale: 1 }}
                                transition={{
                                    type: 'spring',
                                    stiffness: 200,
                                    damping: 12,
                                }}
                                className="flex h-14 w-14 items-center justify-center rounded-full bg-success text-tcl-white"
                            >
                                <PartyPopper className="h-7 w-7" />
                            </motion.div>
                            <h2 className="font-display text-[clamp(1.1rem,3.5vw,1.5rem)] font-bold">
                                Thank you!
                            </h2>
                            <p className="text-[clamp(0.85rem,2.5vw,1rem)] text-ink/70">
                                Your details were saved. The TCL team may reach
                                out to you.
                            </p>
                            <PlayAgain reduce={reduce} />
                        </div>
                    ) : (
                        <form
                            onSubmit={submit}
                            className="flex flex-col gap-[clamp(0.5rem,1.6vh,0.9rem)]"
                        >
                            <div className="flex items-center gap-2">
                                <div className="rounded-md bg-tcl-red p-1">
                                    <TclLogo className="h-4 w-auto" />
                                </div>
                                <h2 className="font-display text-[clamp(1rem,3vw,1.3rem)] font-bold">
                                    Be one of our winner
                                </h2>
                            </div>

                            <Field
                                label="Name"
                                required
                                value={data.name}
                                onChange={(v) => setData('name', v)}
                                error={errors.name}
                                autoComplete="name"
                            />
                            <Field
                                label="Email"
                                type="email"
                                required
                                value={data.email}
                                onChange={(v) => setData('email', v)}
                                error={errors.email}
                                autoComplete="email"
                            />
                            <Field
                                label="Phone (optional)"
                                type="tel"
                                value={data.phone}
                                onChange={(v) => setData('phone', v)}
                                error={errors.phone}
                                autoComplete="tel"
                            />

                            <div className="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
                                <motion.button
                                    type="submit"
                                    disabled={processing}
                                    whileTap={
                                        reduce ? undefined : { scale: 0.97 }
                                    }
                                    className="inline-flex flex-1 items-center justify-center rounded-full bg-tcl-red px-5 py-[clamp(0.55rem,2vw,0.8rem)] font-display text-[clamp(0.9rem,2.8vw,1.1rem)] font-bold text-tcl-white shadow-lg transition-colors hover:bg-tcl-red-dark disabled:opacity-60"
                                >
                                    {processing ? 'Saving…' : 'Submit'}
                                </motion.button>
                                <PlayAgain reduce={reduce} subtle />
                            </div>
                        </form>
                    )}
                </motion.div>
            </div>
        </FullscreenStage>
    );
}

function PlayAgain({
    reduce,
    subtle,
}: {
    reduce: boolean | null;
    subtle?: boolean;
}) {
    return (
        <motion.a
            href="/"
            whileTap={reduce ? undefined : { scale: 0.97 }}
            className={
                subtle
                    ? 'inline-flex items-center justify-center gap-1.5 rounded-full px-4 py-[clamp(0.5rem,2vw,0.8rem)] text-[clamp(0.85rem,2.6vw,1rem)] font-semibold text-tcl-red transition-colors hover:bg-mist'
                    : 'mt-2 inline-flex items-center gap-2 rounded-full bg-tcl-red px-5 py-2.5 font-display font-bold text-tcl-white transition-colors hover:bg-tcl-red-dark'
            }
        >
            <RefreshCw className="h-[1.1em] w-[1.1em]" />
            Play again
        </motion.a>
    );
}

interface FieldProps {
    label: string;
    value: string;
    onChange: (v: string) => void;
    error?: string;
    type?: string;
    required?: boolean;
    autoComplete?: string;
}

function Field({
    label,
    value,
    onChange,
    error,
    type = 'text',
    required,
    autoComplete,
}: FieldProps) {
    return (
        <label className="block">
            <span className="mb-1 block text-[clamp(0.72rem,2.2vw,0.85rem)] font-semibold text-ink/70">
                {label}
                {required && <span className="text-tcl-red"> *</span>}
            </span>
            <input
                type={type}
                required={required}
                value={value}
                autoComplete={autoComplete}
                onChange={(e) => onChange(e.target.value)}
                className={`w-full rounded-xl border-2 bg-mist px-3 py-[clamp(0.4rem,1.5vh,0.6rem)] text-[clamp(0.88rem,min(2.6vw,2.8vh),1rem)] text-ink transition-colors outline-none focus:border-tcl-red focus:bg-tcl-white ${
                    error ? 'border-danger' : 'border-transparent'
                }`}
            />
            {error && (
                <span className="mt-1 block text-xs font-medium text-danger">
                    {error}
                </span>
            )}
        </label>
    );
}
