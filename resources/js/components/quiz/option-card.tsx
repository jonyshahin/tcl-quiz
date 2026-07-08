import { motion, useAnimationControls, useReducedMotion } from 'framer-motion';
import { Check, X } from 'lucide-react';
import { useEffect } from 'react';

export type OptionStatus = 'idle' | 'correct' | 'wrong' | 'muted';

interface Props {
    label: string;
    text: string;
    status: OptionStatus;
    selected: boolean;
    disabled: boolean;
    focusable: boolean;
    onSelect: () => void;
    /** Bump this to re-trigger the shake when the same wrong option is picked again. */
    shakeKey?: number;
}

const surface: Record<OptionStatus, string> = {
    idle: 'bg-tcl-white text-ink ring-2 ring-transparent hover:ring-white hover:-translate-y-0.5 hover:shadow-xl',
    correct: 'bg-success text-tcl-white ring-2 ring-white/70 shadow-xl',
    wrong: 'bg-danger text-tcl-white ring-2 ring-white/60 shadow-xl',
    muted: 'bg-tcl-white/55 text-ink/60 ring-2 ring-transparent',
};

const badge: Record<OptionStatus, string> = {
    idle: 'bg-tcl-red text-tcl-white',
    correct: 'bg-tcl-white/25 text-tcl-white',
    wrong: 'bg-tcl-white/25 text-tcl-white',
    muted: 'bg-ink/10 text-ink/50',
};

/** A selectable answer option rendered as a custom, keyboard-accessible radio. */
export default function OptionCard({
    label,
    text,
    status,
    selected,
    disabled,
    focusable,
    onSelect,
    shakeKey = 0,
}: Props) {
    const reduce = useReducedMotion();
    const controls = useAnimationControls();

    // Wrong picks shake to warn; a correct pick pops. Re-runs when shakeKey bumps.
    useEffect(() => {
        if (reduce) {
            return;
        }

        if (status === 'wrong') {
            controls.start({ x: [0, -10, 10, -8, 8, 0], transition: { duration: 0.4 } });
        } else if (status === 'correct') {
            controls.start({ scale: [1, 1.04, 1], transition: { duration: 0.4 } });
        } else {
            controls.start({ x: 0, scale: 1 });
        }
    }, [status, shakeKey, reduce, controls]);

    return (
        <motion.button
            type="button"
            role="radio"
            aria-checked={selected}
            aria-label={`${label}. ${text}`}
            tabIndex={focusable ? 0 : -1}
            disabled={disabled}
            onClick={onSelect}
            animate={controls}
            whileTap={disabled || reduce ? undefined : { scale: 0.97 }}
            className={`flex w-full items-center gap-[clamp(0.6rem,2vw,1rem)] rounded-2xl px-[clamp(0.9rem,3vw,1.4rem)] py-[clamp(0.5rem,1.7vh,1.05rem)] text-left transition-all duration-200 outline-none focus-visible:ring-4 focus-visible:ring-gold disabled:cursor-default ${surface[status]}`}
        >
            <span
                className={`flex h-[clamp(1.7rem,min(6vw,5vh),2.5rem)] w-[clamp(1.7rem,min(6vw,5vh),2.5rem)] flex-none items-center justify-center rounded-full text-[clamp(0.8rem,min(2.6vw,2.8vh),1.05rem)] font-bold font-display transition-colors ${badge[status]}`}
            >
                {status === 'correct' ? (
                    <Check className="h-[55%] w-[55%]" strokeWidth={3} />
                ) : status === 'wrong' ? (
                    <X className="h-[55%] w-[55%]" strokeWidth={3} />
                ) : (
                    label
                )}
            </span>

            <span className="text-[clamp(0.85rem,min(2.6vw,3vh),1.1rem)] leading-snug font-medium">
                {text}
            </span>
        </motion.button>
    );
}
