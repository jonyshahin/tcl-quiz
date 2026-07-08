import { motion } from 'framer-motion';

interface Props {
    /** 1-based index of the current question. */
    current: number;
    total: number;
}

/** Slim animated progress indicator shown at the top of the question screen. */
export default function ProgressBar({ current, total }: Props) {
    const pct = total > 0 ? Math.min(100, (current / total) * 100) : 0;

    return (
        <div className="w-full">
            <div className="mb-2 flex items-center justify-between text-[clamp(0.7rem,2.2vw,0.85rem)] font-semibold tracking-wide text-tcl-white/80 uppercase">
                <span>
                    Question {current} of {total}
                </span>
                <span aria-hidden="true">{Math.round(pct)}%</span>
            </div>

            <div
                className="h-2.5 w-full overflow-hidden rounded-full bg-tcl-white/20"
                role="progressbar"
                aria-valuenow={current}
                aria-valuemin={0}
                aria-valuemax={total}
            >
                <motion.div
                    className="h-full rounded-full bg-tcl-white"
                    initial={{ width: 0 }}
                    animate={{ width: `${pct}%` }}
                    transition={{ type: 'spring', stiffness: 120, damping: 20 }}
                />
            </div>
        </div>
    );
}
