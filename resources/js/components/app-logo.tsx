export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-tcl-red p-1">
                <img
                    src="/images/tcl-logo.webp"
                    alt="TCL"
                    className="h-full w-full object-contain"
                />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    TCL VRF Quiz
                </span>
            </div>
        </>
    );
}
