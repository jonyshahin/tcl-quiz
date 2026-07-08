import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion } from 'framer-motion';
import { ArrowRight } from 'lucide-react';
import FullscreenStage from '@/components/quiz/fullscreen-stage';
import TclLogo from '@/components/quiz/tcl-logo';

interface Props {
    totalQuestions: number;
}

export default function Start({ totalQuestions }: Props) {
    const reduce = useReducedMotion();
    const { post, processing } = useForm();

    const start = () => post('/quiz/start');

    return (
        <FullscreenStage>
            <Head title="TCL VRF Knowledge Challenge" />

            <div className="flex max-w-2xl flex-col items-center text-center">
                <motion.div
                    initial={{ opacity: 0, scale: 0.8, y: 12 }}
                    animate={{ opacity: 1, scale: 1, y: 0 }}
                    transition={{ type: 'spring', stiffness: 140, damping: 14 }}
                    className="rounded-3xl bg-tcl-white p-[clamp(0.8rem,3vw,1.4rem)] shadow-2xl"
                >
                    <TclLogo className="h-[clamp(3rem,12vw,5.5rem)] w-auto" />
                </motion.div>

                <motion.h1
                    initial={{ opacity: 0, y: 16 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.15, duration: 0.5 }}
                    className="mt-[clamp(1.2rem,4vw,2rem)] font-display text-[clamp(1.8rem,6.5vw,3.4rem)] leading-[1.05] font-bold text-tcl-white"
                >
                    VRF Knowledge Challenge
                </motion.h1>

                <motion.p
                    initial={{ opacity: 0, y: 16 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.28, duration: 0.5 }}
                    className="mt-[clamp(0.6rem,2vw,1rem)] max-w-md text-[clamp(0.95rem,3vw,1.2rem)] text-tcl-white/85"
                >
                    Test your expertise on TCL VRF air-conditioning systems.
                    Answer every question correctly to win.
                </motion.p>

                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.42, duration: 0.5 }}
                    className="mt-[clamp(1.6rem,5vw,2.6rem)]"
                >
                    <motion.button
                        type="button"
                        onClick={start}
                        disabled={processing || totalQuestions === 0}
                        whileHover={reduce ? undefined : { scale: 1.05, y: -2 }}
                        whileTap={reduce ? undefined : { scale: 0.97 }}
                        animate={
                            reduce || processing
                                ? undefined
                                : {
                                      boxShadow: [
                                          '0 10px 30px rgba(0,0,0,0.25)',
                                          '0 14px 42px rgba(245,197,24,0.45)',
                                          '0 10px 30px rgba(0,0,0,0.25)',
                                      ],
                                  }
                        }
                        transition={{
                            duration: 2,
                            repeat: Infinity,
                            ease: 'easeInOut',
                        }}
                        className="group inline-flex items-center gap-3 rounded-full bg-tcl-white px-[clamp(2rem,7vw,3.2rem)] py-[clamp(0.8rem,2.6vw,1.15rem)] font-display text-[clamp(1.1rem,3.6vw,1.5rem)] font-bold text-tcl-red shadow-2xl transition-colors hover:bg-gold hover:text-ink disabled:opacity-60"
                    >
                        {totalQuestions === 0 ? 'Coming soon' : 'START'}
                        {totalQuestions > 0 && (
                            <ArrowRight className="h-[1.1em] w-[1.1em] transition-transform group-hover:translate-x-1" />
                        )}
                    </motion.button>

                    {totalQuestions > 0 && (
                        <p className="mt-4 text-[clamp(0.75rem,2.4vw,0.9rem)] font-medium tracking-wide text-tcl-white/70 uppercase">
                            {totalQuestions} question
                            {totalQuestions === 1 ? '' : 's'} · takes under a
                            minute
                        </p>
                    )}
                </motion.div>
            </div>
        </FullscreenStage>
    );
}
