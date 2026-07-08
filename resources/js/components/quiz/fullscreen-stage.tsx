import type {PropsWithChildren} from 'react';

interface Props {
    /** Extra classes applied to the stage container. */
    className?: string;
    /** Hide the ambient watermark logo (e.g. on the busy results screen). */
    watermark?: boolean;
}

/**
 * The single full-viewport layout primitive for every public quiz screen.
 * Fills 100% width and 100dvh with no scrolling, paints the immersive brand
 * background, and centers its content inside a viewport-scaled padding box.
 */
export default function FullscreenStage({
    children,
    className = '',
    watermark = true,
}: PropsWithChildren<Props>) {
    return (
        <div
            className={`tcl-stage-bg relative flex h-[100dvh] w-screen items-center justify-center overflow-hidden text-tcl-white ${className}`}
        >
            {watermark && (
                <img
                    src="/images/tcl-logo.webp"
                    alt=""
                    aria-hidden="true"
                    draggable={false}
                    className="pointer-events-none absolute -right-10 -bottom-12 w-[42vw] max-w-[460px] rotate-[8deg] opacity-[0.08] select-none"
                />
            )}

            <div className="relative z-10 flex h-full w-full items-center justify-center px-[clamp(1rem,4vw,3rem)] py-[clamp(0.6rem,2.5vh,3rem)]">
                {children}
            </div>
        </div>
    );
}
