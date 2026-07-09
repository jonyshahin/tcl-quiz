import { Head } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import {
    Crown,
    Gift,
    PartyPopper,
    Sparkles,
    Ticket,
    Trophy,
} from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import FullscreenStage from '@/components/quiz/fullscreen-stage';
import TclLogo from '@/components/quiz/tcl-logo';
import { celebrate, celebrateBig } from '@/lib/confetti';

interface Winner {
    name: string;
    maskedPhone: string | null;
}

interface PickResponse {
    position: number;
    winner: Winner;
    revealedCount: number;
    maxWinners: number;
    complete: boolean;
}

interface Props {
    reelNames: string[];
    eligibleCount: number;
    maxWinners: number;
}

type Slot =
    | { status: 'pending' }
    | { status: 'spinning'; display: string }
    | { status: 'revealed'; winner: Winner };

const SPIN_DURATION = 2200;

/** Read Laravel's XSRF-TOKEN cookie for same-origin JSON POSTs. */
function csrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function postJson<T>(url: string): Promise<T> {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!res.ok) {
        const body = (await res.json().catch(() => null)) as {
            message?: string;
        } | null;

        throw new Error(body?.message ?? 'The draw could not be completed.');
    }

    return (await res.json()) as T;
}

export default function DrawIndex({
    reelNames,
    eligibleCount,
    maxWinners,
}: Props) {
    const reduce = useReducedMotion();

    const [slots, setSlots] = useState<Slot[]>(() =>
        Array.from({ length: Math.max(maxWinners, 0) }, () => ({
            status: 'pending' as const,
        })),
    );
    const [drawId, setDrawId] = useState<number | null>(null);
    const [busy, setBusy] = useState(false);
    const [finished, setFinished] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const spinTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const revealedCount = slots.filter((s) => s.status === 'revealed').length;
    const hasPool = eligibleCount > 0 && maxWinners > 0;

    const setSlot = useCallback((index: number, slot: Slot) => {
        setSlots((prev) => prev.map((s, i) => (i === index ? slot : s)));
    }, []);

    /** Cycle random names in the target card, decelerate, then land on the winner. */
    const spinAndReveal = useCallback(
        (index: number, winner: Winner) =>
            new Promise<void>((resolve) => {
                if (reduce || reelNames.length === 0) {
                    setSlot(index, { status: 'revealed', winner });
                    resolve();

                    return;
                }

                const start = Date.now();
                const pool = reelNames;

                const tick = () => {
                    const elapsed = Date.now() - start;

                    if (elapsed >= SPIN_DURATION) {
                        setSlot(index, { status: 'revealed', winner });
                        resolve();

                        return;
                    }

                    const display =
                        pool[Math.floor(Math.random() * pool.length)];
                    setSlot(index, { status: 'spinning', display });

                    // Decelerate: 60ms → ~210ms as we approach the end.
                    const delay = 60 + (elapsed / SPIN_DURATION) * 150;
                    spinTimer.current = setTimeout(tick, delay);
                };

                tick();
            }),
        [reduce, reelNames, setSlot],
    );

    const reset = useCallback(() => {
        setSlots(
            Array.from({ length: Math.max(maxWinners, 0) }, () => ({
                status: 'pending' as const,
            })),
        );
        setDrawId(null);
        setFinished(false);
        setError(null);
    }, [maxWinners]);

    const handleDraw = useCallback(async () => {
        if (busy || !hasPool) {
            return;
        }

        if (finished) {
            reset();

            return;
        }

        setBusy(true);
        setError(null);
        const index = revealedCount;

        try {
            let id = drawId;

            if (id === null) {
                const started = await postJson<{ drawId: number }>(
                    '/draw/start',
                );
                id = started.drawId;
                setDrawId(id);
            }

            const result = await postJson<PickResponse>(`/draw/${id}/pick`);

            await spinAndReveal(index, result.winner);

            if (!reduce) {
                celebrate();
            }

            if (result.complete) {
                setFinished(true);

                if (!reduce) {
                    window.setTimeout(celebrateBig, 250);
                }
            }
        } catch (e) {
            if (spinTimer.current) {
                clearTimeout(spinTimer.current);
            }

            setSlot(index, { status: 'pending' });
            setError(
                e instanceof Error
                    ? e.message
                    : 'The draw could not be completed. Please try again.',
            );
        } finally {
            setBusy(false);
        }
    }, [
        busy,
        hasPool,
        finished,
        reset,
        revealedCount,
        drawId,
        spinAndReveal,
        reduce,
        setSlot,
    ]);

    const buttonLabel = (() => {
        if (busy) {
            return 'Drawing…';
        }

        if (finished) {
            return 'Draw again';
        }

        if (maxWinners === 1) {
            return 'Draw the winner';
        }

        return `Draw winner ${revealedCount + 1}`;
    })();

    const winnersWord =
        maxWinners === 1 ? 'our winner' : `our ${maxWinners} winners`;

    return (
        <FullscreenStage watermark={!finished}>
            <Head title="TCL Lucky Draw" />

            <div className="flex h-full w-full max-w-6xl flex-col items-center justify-between gap-[clamp(0.6rem,2.5vh,2rem)] py-[clamp(0.3rem,1vh,1rem)]">
                {/* Header */}
                <header className="flex flex-col items-center text-center">
                    <div className="flex items-center gap-2">
                        <div className="rounded-lg bg-tcl-red p-1.5 shadow-lg">
                            <TclLogo className="h-[clamp(1rem,3vw,1.5rem)] w-auto" />
                        </div>
                        <h1 className="font-display text-[clamp(1.4rem,min(6vw,7vh),3.2rem)] leading-none font-bold text-tcl-white">
                            TCL Lucky Draw
                        </h1>
                    </div>
                    <p className="mt-[clamp(0.3rem,1.2vh,0.7rem)] font-display text-[clamp(0.85rem,min(3vw,3.4vh),1.4rem)] font-semibold text-gold">
                        {maxWinners === 1
                            ? '1 lucky winner'
                            : `${maxWinners} lucky winners`}
                    </p>
                    {hasPool && (
                        <p className="mt-1 flex items-center gap-1.5 text-[clamp(0.7rem,2.2vw,0.95rem)] text-tcl-white/70">
                            <Ticket className="h-[1.1em] w-[1.1em]" />
                            Drawing from {eligibleCount} participant
                            {eligibleCount === 1 ? '' : 's'}
                        </p>
                    )}
                </header>

                {/* Stage */}
                {hasPool ? (
                    <div className="flex w-full flex-1 flex-col items-center justify-center gap-[clamp(0.6rem,2vh,1.5rem)]">
                        <div
                            className={`grid w-full gap-[clamp(0.5rem,1.8vw,1.25rem)] ${
                                maxWinners === 1
                                    ? 'max-w-md grid-cols-1'
                                    : maxWinners === 2
                                      ? 'max-w-3xl grid-cols-1 sm:grid-cols-2 landscape:grid-cols-2'
                                      : 'grid-cols-1 sm:grid-cols-3 landscape:grid-cols-3'
                            }`}
                        >
                            {slots.map((slot, i) => (
                                <PodiumCard
                                    key={i}
                                    position={i + 1}
                                    slot={slot}
                                    reduce={reduce}
                                />
                            ))}
                        </div>

                        <div className="flex min-h-[clamp(1.6rem,5vh,2.6rem)] flex-col items-center gap-1">
                            <AnimatePresence>
                                {finished && (
                                    <motion.div
                                        initial={
                                            reduce
                                                ? { opacity: 0 }
                                                : {
                                                      opacity: 0,
                                                      y: 16,
                                                      scale: 0.9,
                                                  }
                                        }
                                        animate={{ opacity: 1, y: 0, scale: 1 }}
                                        exit={{ opacity: 0 }}
                                        transition={{
                                            type: 'spring',
                                            stiffness: 180,
                                            damping: 14,
                                        }}
                                        className="flex items-center gap-2 rounded-full bg-gold px-[clamp(0.9rem,3vw,1.6rem)] py-[clamp(0.35rem,1.4vh,0.7rem)] font-display text-[clamp(0.85rem,min(3vw,3.4vh),1.4rem)] font-bold text-tcl-red-deep shadow-xl"
                                    >
                                        <PartyPopper className="h-[1.15em] w-[1.15em]" />
                                        Congratulations to {winnersWord}!
                                    </motion.div>
                                )}
                            </AnimatePresence>
                            {error && (
                                <p
                                    role="alert"
                                    className="rounded-full bg-danger/95 px-4 py-1.5 text-[clamp(0.75rem,2.2vw,0.95rem)] font-semibold text-tcl-white shadow-lg"
                                >
                                    {error}
                                </p>
                            )}
                        </div>
                    </div>
                ) : (
                    <EmptyState reduce={reduce} />
                )}

                {/* Control */}
                <footer className="flex flex-col items-center">
                    {hasPool ? (
                        <motion.button
                            type="button"
                            onClick={handleDraw}
                            disabled={busy}
                            whileTap={
                                reduce || busy ? undefined : { scale: 0.96 }
                            }
                            className={
                                finished
                                    ? 'inline-flex items-center gap-2 rounded-full border-2 border-tcl-white/70 px-[clamp(1.4rem,5vw,2.5rem)] py-[clamp(0.55rem,2vh,0.9rem)] font-display text-[clamp(0.95rem,min(3vw,3.4vh),1.35rem)] font-bold text-tcl-white transition-colors hover:bg-tcl-white/10 disabled:opacity-60'
                                    : 'inline-flex items-center gap-2 rounded-full bg-tcl-white px-[clamp(1.6rem,6vw,3rem)] py-[clamp(0.6rem,2.3vh,1rem)] font-display text-[clamp(1rem,min(3.4vw,3.8vh),1.5rem)] font-bold text-tcl-red shadow-2xl transition-colors hover:bg-mist disabled:cursor-not-allowed disabled:opacity-70'
                            }
                        >
                            {finished ? (
                                <Sparkles className="h-[1.15em] w-[1.15em]" />
                            ) : (
                                <Gift className="h-[1.15em] w-[1.15em]" />
                            )}
                            {buttonLabel}
                        </motion.button>
                    ) : (
                        <a
                            href="/"
                            className="text-[clamp(0.8rem,2.5vw,1rem)] font-semibold text-tcl-white/80 underline-offset-4 hover:underline"
                        >
                            Back to the quiz
                        </a>
                    )}
                </footer>
            </div>
        </FullscreenStage>
    );
}

function PodiumCard({
    position,
    slot,
    reduce,
}: {
    position: number;
    slot: Slot;
    reduce: boolean | null;
}) {
    const revealed = slot.status === 'revealed';

    return (
        <div
            className={`relative flex min-h-[clamp(6rem,22vh,13rem)] flex-col items-center justify-center overflow-hidden rounded-3xl px-[clamp(0.6rem,2vw,1.4rem)] py-[clamp(0.7rem,2.2vh,1.6rem)] text-center transition-colors duration-300 ${
                revealed
                    ? 'bg-tcl-white text-ink shadow-2xl'
                    : 'border-2 border-dashed border-tcl-white/40 bg-tcl-white/5 text-tcl-white/80'
            }`}
        >
            {/* Gold top accent + glow on reveal */}
            {revealed && (
                <>
                    <span className="absolute inset-x-0 top-0 h-1.5 bg-gold" />
                    <span className="pointer-events-none absolute -inset-8 -z-0 bg-gold/20 blur-2xl" />
                </>
            )}

            {/* Position badge */}
            <span
                className={`absolute top-2 left-3 font-display text-[clamp(0.85rem,2.6vw,1.1rem)] font-bold ${
                    revealed ? 'text-gold' : 'text-tcl-white/40'
                }`}
            >
                #{position}
            </span>

            {slot.status === 'pending' && (
                <>
                    <span className="font-display text-[clamp(2rem,8vw,4rem)] leading-none font-bold text-tcl-white/25">
                        {position}
                    </span>
                    <Gift className="mt-2 h-[clamp(1.1rem,4vw,2rem)] w-[clamp(1.1rem,4vw,2rem)] text-tcl-white/45" />
                </>
            )}

            {slot.status === 'spinning' && (
                <span className="w-full truncate font-display text-[clamp(1rem,min(4vw,4.5vh),1.8rem)] font-bold text-tcl-white/90 blur-[0.4px]">
                    {slot.display}
                </span>
            )}

            {revealed && (
                <motion.div
                    initial={
                        reduce
                            ? { opacity: 0 }
                            : { scale: 0.6, opacity: 0, y: 10 }
                    }
                    animate={{ scale: 1, opacity: 1, y: 0 }}
                    transition={{
                        type: 'spring',
                        stiffness: 220,
                        damping: 13,
                    }}
                    className="relative z-10 flex flex-col items-center gap-1"
                >
                    <div className="flex h-[clamp(1.8rem,6vw,3rem)] w-[clamp(1.8rem,6vw,3rem)] items-center justify-center rounded-full bg-gold text-tcl-red-deep shadow-md">
                        {position === 1 ? (
                            <Crown className="h-1/2 w-1/2" strokeWidth={2.2} />
                        ) : (
                            <Trophy className="h-1/2 w-1/2" strokeWidth={2.2} />
                        )}
                    </div>
                    <span className="mt-1 line-clamp-2 max-w-full font-display text-[clamp(1rem,min(3.4vw,4vh),1.7rem)] leading-tight font-bold text-ink">
                        {slot.winner.name}
                    </span>
                    <span className="font-mono text-[clamp(0.75rem,2.4vw,1rem)] tracking-wide text-ink/60">
                        {slot.winner.maskedPhone ?? 'No phone on file'}
                    </span>
                </motion.div>
            )}
        </div>
    );
}

function EmptyState({ reduce }: { reduce: boolean | null }) {
    return (
        <div className="flex flex-1 flex-col items-center justify-center gap-[clamp(0.6rem,2vh,1.2rem)] text-center">
            <motion.div
                initial={reduce ? { opacity: 0 } : { scale: 0, rotate: -15 }}
                animate={reduce ? { opacity: 1 } : { scale: 1, rotate: 0 }}
                transition={{ type: 'spring', stiffness: 160, damping: 12 }}
                className="flex h-[clamp(3rem,12vw,5rem)] w-[clamp(3rem,12vw,5rem)] items-center justify-center rounded-2xl bg-tcl-white/95 text-tcl-red shadow-2xl"
            >
                <Sparkles className="h-1/2 w-1/2" strokeWidth={2.2} />
            </motion.div>
            <h2 className="font-display text-[clamp(1.2rem,min(5vw,6vh),2.4rem)] font-bold text-tcl-white">
                No participants yet
            </h2>
            <p className="max-w-md text-[clamp(0.85rem,2.8vw,1.1rem)] text-tcl-white/80">
                Check back once people have played the quiz — winners are drawn
                from everyone who enters.
            </p>
        </div>
    );
}
