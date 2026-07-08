interface Props {
    className?: string;
}

/** The TCL wordmark logo. Decorative sizing is controlled by the caller. */
export default function TclLogo({ className = '' }: Props) {
    return (
        <img
            src="/images/tcl-logo.webp"
            alt="TCL"
            draggable={false}
            className={`select-none ${className}`}
        />
    );
}
