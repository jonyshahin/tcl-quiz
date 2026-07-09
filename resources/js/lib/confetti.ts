import confetti from 'canvas-confetti';

const TCL_COLORS = ['#E60012', '#F5C518', '#FFFFFF', '#B3000E'];

/**
 * Fire a celebratory confetti burst in the TCL palette.
 * Density is reduced on small screens (and when the user prefers reduced motion).
 */
export function celebrate(): void {
    const prefersReduced =
        typeof window !== 'undefined' &&
        window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

    if (prefersReduced) {
        return;
    }

    const isSmall = typeof window !== 'undefined' && window.innerWidth < 640;
    const particleCount = isSmall ? 45 : 110;

    const fire = (particleRatio: number, opts: confetti.Options) => {
        confetti({
            origin: { y: 0.6 },
            colors: TCL_COLORS,
            disableForReducedMotion: true,
            particleCount: Math.floor(particleCount * particleRatio),
            ...opts,
        });
    };

    fire(0.25, { spread: 26, startVelocity: 55 });
    fire(0.2, { spread: 60 });
    fire(0.35, { spread: 100, decay: 0.91, scalar: 0.9 });
    fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
    fire(0.1, { spread: 120, startVelocity: 45 });
}

/**
 * A bigger, sustained finale burst for the Lucky Draw reveal of all winners.
 * Fires from both bottom corners for a couple of seconds. Same TCL palette and
 * reduced-motion / small-screen guards as {@link celebrate}.
 */
export function celebrateBig(): void {
    const prefersReduced =
        typeof window !== 'undefined' &&
        window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

    if (prefersReduced) {
        return;
    }

    const isSmall = typeof window !== 'undefined' && window.innerWidth < 640;
    const duration = isSmall ? 1400 : 2200;
    const end = Date.now() + duration;
    const particleCount = isSmall ? 3 : 6;

    const frame = () => {
        confetti({
            particleCount,
            angle: 60,
            spread: 70,
            origin: { x: 0, y: 0.7 },
            colors: TCL_COLORS,
            disableForReducedMotion: true,
        });
        confetti({
            particleCount,
            angle: 120,
            spread: 70,
            origin: { x: 1, y: 0.7 },
            colors: TCL_COLORS,
            disableForReducedMotion: true,
        });

        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    };

    frame();
}
